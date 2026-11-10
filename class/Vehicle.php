<?php
require_once __DIR__ . '/../config/Database.php';

// Class model untuk mengelola inventaris armada mobil
class Vehicle {
    private PDO $db;
    private const VALID_STATUSES = ['available', 'rented', 'maintenance'];

    // Inisialisasi koneksi PDO ke database
    public function __construct(?PDO $db = null) {
        $this->db = $db ?? (new Database())->conn;
    }

    // Validasi input armada sebelum dikirim ke query database
    private function validate(string $brand, string $plate_number, float $daily_rate, string $status): void {
        if (trim($brand) === '' || trim($plate_number) === '') {
            throw new InvalidArgumentException("Merk kendaraan dan plat nomor wajib diisi.");
        }
        if ($daily_rate <= 0) {
            throw new InvalidArgumentException("Tarif harian harus lebih besar dari Rp 0.");
        }
        if (!in_array($status, self::VALID_STATUSES, true)) {
            throw new InvalidArgumentException("Status kendaraan tidak valid.");
        }
    }

    // Ambil seluruh armada kendaraan untuk ditampilkan pada tabel inventaris
    public function getAll(): array {
        $stmt = $this->db->prepare("SELECT * FROM vehicles ORDER BY id ASC");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Ambil hanya mobil berstatus available untuk dropdown transaksi sewa
    public function getAvailable(): array {
        // Filter mengecualikan unit yang sedang disewa atau masuk jadwal perawatan servis
        $stmt = $this->db->prepare("SELECT * FROM vehicles WHERE status = 'available' ORDER BY brand ASC");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Ambil informasi lengkap satu unit kendaraan berdasarkan ID
    public function getById(int $id): ?array {
        if ($id <= 0) {
            return null;
        }
        $stmt = $this->db->prepare("SELECT * FROM vehicles WHERE id = ?");
        $stmt->execute([$id]);
        $res = $stmt->fetch();
        // Null digunakan sebagai penanda bahwa mobil tidak ditemukan
        return $res ?: null;
    }

    // Tambah armada baru ke basis data inventaris
    public function create(string $brand, string $plate_number, float $daily_rate, string $status = 'available'): bool {
        $this->validate($brand, $plate_number, $daily_rate, $status);

        try {
            // Normalisasi plat nomor ke huruf kapital agar konsisten dan menghindari duplikasi beda case
            $plate = strtoupper(trim($plate_number));
            // Status default adalah available sehingga mobil langsung siap disewakan
            $stmt = $this->db->prepare("INSERT INTO vehicles (brand, plate_number, daily_rate, status) VALUES (?, ?, ?, ?)");
            return $stmt->execute([trim($brand), $plate, $daily_rate, $status]);
        } catch (PDOException $e) {
            // Kode 1062 menandakan pelanggaran constraint UNIQUE pada plat nomor
            if (($e->errorInfo[1] ?? null) === 1062) {
                throw new RuntimeException("Plat nomor kendaraan sudah terdaftar.");
            }
            throw new RuntimeException("Gagal menambahkan kendaraan.");
        }
    }

    // Perbarui seluruh atribut kendaraan pada menu edit armada
    public function update(int $id, string $brand, string $plate_number, float $daily_rate, string $status): bool {
        if (!$this->getById($id)) {
            throw new InvalidArgumentException("Data kendaraan tidak ditemukan.");
        }
        $this->validate($brand, $plate_number, $daily_rate, $status);

        try {
            $plate = strtoupper(trim($plate_number));
            $stmt = $this->db->prepare("UPDATE vehicles SET brand = ?, plate_number = ?, daily_rate = ?, status = ? WHERE id = ?");
            return $stmt->execute([trim($brand), $plate, $daily_rate, $status, $id]);
        } catch (PDOException $e) {
            if (($e->errorInfo[1] ?? null) === 1062) {
                throw new RuntimeException("Plat nomor sudah digunakan oleh kendaraan lain.");
            }
            throw new RuntimeException("Gagal memperbarui data kendaraan.");
        }
    }

    // Method khusus untuk toggle status mobil secara terisolasi
    public function setStatus(int $id, string $status): bool {
        if (!in_array($status, self::VALID_STATUSES, true)) {
            throw new InvalidArgumentException("Status kendaraan tidak valid.");
        }
        // Menghindari penulisan ulang kolom tarif atau plat nomor saat siklus sewa/kembali berjalan
        $stmt = $this->db->prepare("UPDATE vehicles SET status = ? WHERE id = ?");
        return $stmt->execute([$status, $id]);
    }

    // Hapus data armada dari basis data
    public function delete(int $id): bool {
        $vehicle = $this->getById($id);
        if (!$vehicle) {
            throw new InvalidArgumentException("Data kendaraan yang akan dihapus tidak ditemukan.");
        }
        // Cegah penghapusan armada yang masih aktif disewa agar riwayat transaksi berjalan tidak terputus
        if ($vehicle['status'] === 'rented') {
            throw new RuntimeException("Kendaraan sedang disewa dan tidak dapat dihapus sebelum dikembalikan.");
        }
        $stmt = $this->db->prepare("DELETE FROM vehicles WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
