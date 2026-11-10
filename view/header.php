<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Rental Kendaraan</title>
    <!-- Preconnect ke server Google Fonts mempercepat inisialisasi handshake DNS dan TLS -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Parameter versi memaksa browser memuat ulang stylesheet terbaru tanpa cache lama -->
    <link rel="stylesheet" href="style.css?v=4">
</head>
<body>
    <header class="navbar">
        <div class="nav-container">
            <a href="index.php?page=rentals" class="brand">
                <span class="brand-icon">🚗</span>
                <span class="brand-text">Rental Mobil</span>
            </a>
            <nav class="nav-links">
                <!-- Class 'active' disematkan secara dinamis berdasarkan nilai parameter query string $page -->
                <a href="index.php?page=rentals" class="nav-item <?= ($page ?? '') === 'rentals' ? 'active' : '' ?>">
                    <span>Transaksi Sewa</span>
                </a>
                <a href="index.php?page=vehicles" class="nav-item <?= ($page ?? '') === 'vehicles' ? 'active' : '' ?>">
                    <span>Daftar Mobil</span>
                </a>
                <a href="index.php?page=customers" class="nav-item <?= ($page ?? '') === 'customers' ? 'active' : '' ?>">
                    <span>Data Pelanggan</span>
                </a>
            </nav>
        </div>
    </header>
    <!-- Kontainer utama tempat konten halaman dirender -->
    <main class="container">
        <?php if (!empty($flash)): ?>
            <!-- Render pesan error atau sukses hasil validasi server-side dengan sanitasi XSS -->
            <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'error' ?>">
                <span><?= htmlspecialchars($flash['message']) ?></span>
            </div>
        <?php endif; ?>
