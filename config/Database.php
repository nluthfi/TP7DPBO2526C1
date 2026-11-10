<?php
// Enkapsulasi koneksi database MySQL menggunakan PDO
class Database {
    // Gunakan 127.0.0.1 bukan localhost agar koneksi lewat TCP dan menghindari konflik unix socket XAMPP
    private string $host = "127.0.0.1";
    private int $port    = 3306;
    private string $user = "root";
    private string $pass = "";
    private string $db   = "db_rental";
    public ?PDO $conn = null;

    public function __construct() {
        try {
            // Charset utf8mb4 memastikan dukungan penuh karakter internasional dan emoji
            $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->db};charset=utf8mb4";
            $this->conn = new PDO($dsn, $this->user, $this->pass, [
                // Melempar exception otomatis saat query gagal agar penanganan error terpusat
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                // Format hasil query sebagai array asosiatif untuk kemudahan akses kolom
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                // Nonaktifkan emulasi prepared statement agar query dieksekusi native di server database
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            // Lempar RuntimeException agar layer pemanggil dapat menampilkan halaman error tanpa bocor detail kredensial
            throw new RuntimeException("Koneksi ke database gagal: " . $e->getMessage(), 0, $e);
        }
    }
}
