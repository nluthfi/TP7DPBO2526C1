<?php
require_once __DIR__ . '/../config/Database.php';

// Class model untuk mengelola data entitas pelanggan
class Customer {
    private PDO $db;

    // Dapatkan instance koneksi PDO tunggal untuk seluruh operasi class
    public function __construct(?PDO $db = null) {
        $this->db = $db ?? (new Database())->conn;
    }

    // Validasi aturan input pelanggan di satu tempat agar create dan update konsisten
    private function validate(string $name, string $nik, string $phone, string $address): void {
        if (trim($name) === '' || trim($nik) === '' || trim($phone) === '' || trim($address) === '') {
            throw new InvalidArgumentException("Semua field pelanggan wajib diisi.");
        }
        if (!preg_match('/^\d{16}$/', trim($nik))) {
            throw new InvalidArgumentException("NIK harus terdiri dari tepat 16 digit angka.");
        }
        if (!preg_match('/^[0-9+\-\s]{8,20}$/', trim($phone))) {
            throw new InvalidArgumentException("Format nomor telepon tidak valid (minimal 8 karakter angka).");
        }
    }

    // Ambil seluruh daftar pelanggan dengan urutan ID naik dari atas ke bawah
    public function getAll(): array {
        // ID ASC menjaga urutan kronologis data awal berada di bagian atas tabel
        $stmt = $this->db->prepare("SELECT * FROM customers ORDER BY id ASC");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Cari detail satu pelanggan berdasarkan ID utama
    public function getById(int $id): ?array {
        if ($id <= 0) {
            return null;
        }
        // Prepared statement dengan placeholder '?' untuk mencegah injeksi SQL pada parameter numerik
        $stmt = $this->db->prepare("SELECT * FROM customers WHERE id = ?");
        $stmt->execute([$id]);
        $res = $stmt->fetch();
        // Kembalikan null jika data tidak ditemukan agar pemanggil mendapat tipe data konsisten
        return $res ?: null;
    }

    // Simpan data pelanggan baru ke dalam tabel customers
    public function create(string $name, string $nik, string $phone, string $address): bool {
        $this->validate($name, $nik, $phone, $address);

        try {
            // Seluruh nilai input di-bind secara aman melalui execute array
            $stmt = $this->db->prepare("INSERT INTO customers (name, nik, phone, address) VALUES (?, ?, ?, ?)");
            return $stmt->execute([trim($name), trim($nik), trim($phone), trim($address)]);
        } catch (PDOException $e) {
            // Kode 1062 menandakan pelanggaran constraint UNIQUE pada kolom NIK
            if (($e->errorInfo[1] ?? null) === 1062) {
                throw new RuntimeException("NIK sudah terdaftar pada pelanggan lain.");
            }
            throw new RuntimeException("Gagal menyimpan data pelanggan.");
        }
    }

    // Perbarui informasi profil pelanggan yang sudah ada
    public function update(int $id, string $name, string $nik, string $phone, string $address): bool {
        if (!$this->getById($id)) {
            throw new InvalidArgumentException("Data pelanggan tidak ditemukan.");
        }
        $this->validate($name, $nik, $phone, $address);

        try {
            // ID ditaruh di akhir parameter array sesuai urutan placeholder pada klausa WHERE
            $stmt = $this->db->prepare("UPDATE customers SET name = ?, nik = ?, phone = ?, address = ? WHERE id = ?");
            return $stmt->execute([trim($name), trim($nik), trim($phone), trim($address), $id]);
        } catch (PDOException $e) {
            if (($e->errorInfo[1] ?? null) === 1062) {
                throw new RuntimeException("NIK sudah digunakan oleh pelanggan lain.");
            }
            throw new RuntimeException("Gagal memperbarui data pelanggan.");
        }
    }

    // Hapus baris data pelanggan berdasarkan ID
    public function delete(int $id): bool {
        if (!$this->getById($id)) {
            throw new InvalidArgumentException("Data pelanggan yang akan dihapus tidak ditemukan.");
        }
        // Penghapusan data transaksi sewa terkait ditangani otomatis oleh aturan CASCADE di schema database
        $stmt = $this->db->prepare("DELETE FROM customers WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
