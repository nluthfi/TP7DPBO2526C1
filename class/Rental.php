<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/Customer.php';
require_once __DIR__ . '/Vehicle.php';

// Class model transaksi sewa yang menghubungkan tabel customers dan vehicles
class Rental {
    private PDO $db;
    private const VALID_STATUSES = ['active', 'completed', 'cancelled'];

    // Inisialisasi koneksi PDO ke database
    public function __construct(?PDO $db = null) {
        $this->db = $db ?? (new Database())->conn;
    }

    // Validasi ketat format tanggal kalender YYYY-MM-DD agar kalkulasi durasi dan query SQL tidak meleset
    private function isValidDate(string $date): bool {
        $dt = DateTime::createFromFormat('Y-m-d', $date);
        return $dt && $dt->format('Y-m-d') === $date;
    }

    // Ambil seluruh riwayat transaksi sewa beserta data relasi pelanggan dan mobil
    public function getAll(): array {
        // Eager JOIN tabel customers dan vehicles agar nama pelanggan dan merk mobil langsung terbaca tanpa query N+1
        $sql = "SELECT r.*, c.name AS customer_name, c.phone AS customer_phone, 
                       v.brand AS vehicle_brand, v.plate_number, v.daily_rate
                FROM rentals r
                JOIN customers c ON r.customer_id = c.id
                JOIN vehicles v ON r.vehicle_id = v.id
                ORDER BY r.id ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Ambil detail satu transaksi sewa berdasarkan ID
    public function getById(int $id): ?array {
        if ($id <= 0) {
            return null;
        }
        $stmt = $this->db->prepare("SELECT * FROM rentals WHERE id = ?");
        $stmt->execute([$id]);
        $res = $stmt->fetch();
        return $res ?: null;
    }

    // Buka transaksi sewa baru dan kunci unit kendaraan secara atomik
    public function create(int $customer_id, int $vehicle_id, string $rental_date): bool {
        if (!$this->isValidDate($rental_date)) {
            throw new InvalidArgumentException("Format tanggal sewa tidak valid.");
        }

        $customerModel = new Customer($this->db);
        $vehicleModel  = new Vehicle($this->db);

        if (!$customerModel->getById($customer_id)) {
            throw new InvalidArgumentException("Pelanggan yang dipilih tidak ditemukan.");
        }

        $vehicle = $vehicleModel->getById($vehicle_id);
        if (!$vehicle) {
            throw new InvalidArgumentException("Kendaraan yang dipilih tidak ditemukan.");
        }
        // Cegah double-booking jika request dikirim ulang atau armada sedang disewa/perawatan
        if ($vehicle['status'] !== 'available') {
            throw new RuntimeException("Kendaraan tidak tersedia untuk disewa saat ini.");
        }

        try {
            // Transaksi DB menjamin insert rental dan update status armada berhasil atau batal bersamaan
            $this->db->beginTransaction();

            // Status awal transaksi selalu 'active' sampai penyewa mengembalikan kendaraan
            $stmt = $this->db->prepare("INSERT INTO rentals (customer_id, vehicle_id, rental_date, status) VALUES (?, ?, ?, 'active')");
            $stmt->execute([$customer_id, $vehicle_id, $rental_date]);

            // Ubah status mobil menjadi 'rented' agar tidak bisa dipilih pelanggan lain pada waktu bersamaan
            $vehicleModel->setStatus($vehicle_id, 'rented');

            return $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw new RuntimeException("Gagal membuat transaksi sewa.", 0, $e);
        }
    }

    // Perbarui data transaksi pada menu edit sewa
    public function update(int $id, int $customer_id, int $vehicle_id, string $rental_date, ?string $return_date, float $total_price, string $status): bool {
        if (!$this->getById($id)) {
            throw new InvalidArgumentException("Transaksi sewa tidak ditemukan.");
        }
        if (!(new Customer($this->db))->getById($customer_id)) {
            throw new InvalidArgumentException("Pelanggan yang dipilih tidak ditemukan.");
        }
        if (!(new Vehicle($this->db))->getById($vehicle_id)) {
            throw new InvalidArgumentException("Kendaraan yang dipilih tidak ditemukan.");
        }
        if (!$this->isValidDate($rental_date)) {
            throw new InvalidArgumentException("Format tanggal sewa tidak valid.");
        }
        if ($return_date !== null) {
            if (!$this->isValidDate($return_date)) {
                throw new InvalidArgumentException("Format tanggal kembali tidak valid.");
            }
            if ($return_date < $rental_date) {
                throw new InvalidArgumentException("Tanggal kembali tidak boleh lebih awal dari tanggal sewa.");
            }
        }
        if ($total_price < 0) {
            throw new InvalidArgumentException("Total biaya tidak boleh bernilai negatif.");
        }
        if (!in_array($status, self::VALID_STATUSES, true)) {
            throw new InvalidArgumentException("Status transaksi sewa tidak valid.");
        }

        $stmt = $this->db->prepare("UPDATE rentals SET customer_id = ?, vehicle_id = ?, rental_date = ?, return_date = ?, total_price = ?, status = ? WHERE id = ?");
        return $stmt->execute([$customer_id, $vehicle_id, $rental_date, $return_date, $total_price, $status, $id]);
    }

    // Proses pengembalian mobil sekaligus hitung total biaya tagihan
    public function completeRental(int $id, string $return_date): float {
        $rental = $this->getById($id);
        if (!$rental) {
            throw new InvalidArgumentException("Transaksi sewa tidak ditemukan.");
        }
        if ($rental['status'] !== 'active') {
            throw new RuntimeException("Hanya transaksi berstatus aktif yang dapat dikembalikan.");
        }
        if (!$this->isValidDate($return_date)) {
            throw new InvalidArgumentException("Format tanggal kembali tidak valid.");
        }
        if ($return_date < $rental['rental_date']) {
            throw new InvalidArgumentException("Tanggal kembali tidak boleh lebih awal dari tanggal sewa.");
        }

        $vehicleModel = new Vehicle($this->db);
        $vehicle = $vehicleModel->getById($rental['vehicle_id']);
        // Hitung selisih hari; gunakan nilai minimum 1 hari jika mobil dikembalikan pada hari yang sama
        $days = (int) max(1, (strtotime($return_date) - strtotime($rental['rental_date'])) / 86400);
        $total = $days * (float) ($vehicle['daily_rate'] ?? 0);

        try {
            $this->db->beginTransaction();

            // Catat tanggal kembali, total biaya akhir, dan set status transaksi ke 'completed'
            $stmt = $this->db->prepare("UPDATE rentals SET return_date = ?, total_price = ?, status = 'completed' WHERE id = ?");
            $stmt->execute([$return_date, $total, $id]);

            // Kembalikan status armada menjadi 'available' agar siap disewakan kembali
            $vehicleModel->setStatus($rental['vehicle_id'], 'available');

            $this->db->commit();
            return $total;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw new RuntimeException("Gagal memproses pengembalian kendaraan.", 0, $e);
        }
    }

    // Hapus data transaksi sewa
    public function delete(int $id): bool {
        $rental = $this->getById($id);
        if (!$rental) {
            throw new InvalidArgumentException("Transaksi sewa yang akan dihapus tidak ditemukan.");
        }

        try {
            $this->db->beginTransaction();

            if ($rental['status'] === 'active') {
                // Pulihkan status mobil ke 'available' jika transaksi aktif dihapus sebelum pengembalian selesai
                (new Vehicle($this->db))->setStatus($rental['vehicle_id'], 'available');
            }
            $stmt = $this->db->prepare("DELETE FROM rentals WHERE id = ?");
            $stmt->execute([$id]);

            return $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw new RuntimeException("Gagal menghapus data transaksi sewa.", 0, $e);
        }
    }
}
