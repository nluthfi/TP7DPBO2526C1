# Tugas Praktikum 7 - Desain dan Pemrograman Berorientasi Objek (DPBO) 2026

## Janji
Saya Luthfi Naufal Alfareza dengan NIM 2511437 mengerjakan Tugas Praktikum 7 dalam mata kuliah Desain dan Pemrograman Berorientasi Objek (DPBO) untuk keberkahan-Nya maka saya tidak akan melakukan kecurangan seperti yang telah dispesifikasikan. Aamiin.

---

## 1. Tema Website
Sistem Informasi Manajemen Rental Kendaraan (**Car Rental Management System**) berbasis PHP Native dengan penerapan konsep **Object-Oriented Programming (OOP)** dan GUI web. Sistem ini mengelola daftar kendaraan, basis data pelanggan, serta proses transaksi peminjaman dan pengembalian mobil.

---

## 2. Struktur Direktori Proyek
```
TP7DPBO2526C1/
├── class/
│   ├── Customer.php     
│   ├── Vehicle.php      
│   └── Rental.php        
├── config/
│   └── Database.php     
├── database/
│   └── db_rental.sql     
├── view/
│   ├── header.php        
│   ├── footer.php        
│   ├── customers.php     
│   ├── vehicles.php      
│   └── rentals.php       
├── Dokumentasi/          
├── index.php             
├── style.css             
└── README.md
```

---

## 3. Penjelasan Database & Relasi Antar Tabel
Database `db_rental` dirancang dengan 3 entitas berelasi menggunakan storage engine `InnoDB`:
1. **`customers` (Entitas Pelanggan)**
   * `id` (INT, PK, Auto Increment)
   * `name` (VARCHAR 100)
   * `nik` (VARCHAR 16, Unique)
   * `phone` (VARCHAR 20)
   * `address` (TEXT)
   * `created_at` (TIMESTAMP)
2. **`vehicles` (Entitas Kendaraan)**
   * `id` (INT, PK, Auto Increment)
   * `brand` (VARCHAR 100)
   * `plate_number` (VARCHAR 15, Unique)
   * `daily_rate` (DECIMAL 10,2)
   * `status` (ENUM: `'available'`, `'rented'`, `'maintenance'`)
   * `created_at` (TIMESTAMP)
3. **`rentals` (Entitas Transaksi Sewa - Foreign Key Relasi)**
   * `id` (INT, PK, Auto Increment)
   * `customer_id` (INT, Foreign Key ke `customers.id`)
   * `vehicle_id` (INT, Foreign Key ke `vehicles.id`)
   * `rental_date` (DATE)
   * `return_date` (DATE, Nullable)
   * `total_price` (DECIMAL 10,2)
   * `status` (ENUM: `'active'`, `'completed'`, `'cancelled'`)
   * `created_at` (TIMESTAMP)

---

## 4. Alur & Konsep OOP (Flow of Code)
1. **Database Abstraction & Prepared Statements**:
   * Koneksi database diinisialisasi melalui class `Database` menggunakan driver `PDO`.
   * **Seluruh query CRUD 100% menggunakan Prepared Statement** (`$db->prepare()` dan `$stmt->execute()`) untuk menjamin keamanan dari SQL Injection. Tidak ada query mentah langsung (`->query()`).
2. **Modular Class & Separation of Concerns**:
   * Tiap tabel dikontrol oleh class tersendiri di folder `class/` (`Customer`, `Vehicle`, `Rental`).
   * Setiap class mengimplementasikan method CRUD lengkap (`getAll()`, `getById()`, `create()`, `update()`, `delete()`).
3. **Business Logic Integration**:
   * Saat transaksi sewa dibuat (`Rental::create()`), status kendaraan otomatis diubah menjadi `rented`.
   * Saat mobil dikembalikan (`Rental::completeRental()`), sistem menghitung durasi hari sewa dikali tarif harian (`daily_rate`), menghitung total biaya, menandai sewa `completed`, dan mengembalikan status mobil menjadi `available`.
4. **View Modularization**:
   * Logika tampilan frontend dipisahkan ke dalam folder `view/` dan dimuat secara dinamis oleh router di `index.php` berdasarkan parameter query string `?page=...`.

---

## 5. Dokumentasi
https://github.com/user-attachments/assets/404a07aa-626e-4be2-a706-8f0e99f14403



