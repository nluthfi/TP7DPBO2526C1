<?php
// Main entry point dan controller aksi CRUD aplikasi
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}

require_once __DIR__ . '/class/Customer.php';
require_once __DIR__ . '/class/Vehicle.php';
require_once __DIR__ . '/class/Rental.php';

// Simpan pesan notifikasi ke session agar tetap muncul setelah redirect PRG
function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = [
        'type'    => $type,
        'message' => $message,
    ];
}

// Default halaman adalah rentals; whitelist mencegah penyertaan file sembarangan (LFI)
$allowedPages = ['rentals', 'vehicles', 'customers'];
$requestedPage = $_GET['page'] ?? 'rentals';
$page = in_array($requestedPage, $allowedPages, true) ? $requestedPage : 'rentals';
if (isset($_GET['page']) && !in_array($requestedPage, $allowedPages, true)) {
    $flash = ['type' => 'error', 'message' => 'Halaman yang diminta tidak ditemukan. Dialihkan ke halaman utama.'];
}

try {
    // Inisialisasi object model untuk masing-masing entitas tabel
    $customerModel = new Customer();
    $vehicleModel  = new Vehicle();
    $rentalModel   = new Rental();
} catch (Throwable $e) {
    // Tampilkan halaman fallback rapi ketika database belum aktif atau gagal terhubung
    $flash = ['type' => 'error', 'message' => $e->getMessage()];
    include __DIR__ . '/view/header.php';
    echo '<div class="card"><p>Pastikan service MySQL aktif dan database <strong>db_rental</strong> sudah di-import.</p></div>';
    include __DIR__ . '/view/footer.php';
    exit;
}

// Tangkap parameter aksi baik dari form submit (POST) maupun link tombol hapus (GET)
$action = $_POST['action'] ?? $_GET['action'] ?? null;

// Terapkan pola PRG (Post/Redirect/Get) untuk mencegah resubmit form ganda saat user refresh halaman
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $redirectUrl = "index.php?page={$page}";

    try {
        // Penanganan form data pelanggan
        if ($action === 'create_customer') {
            $redirectUrl = "index.php?page=customers";
            $customerModel->create($_POST['name'] ?? '', $_POST['nik'] ?? '', $_POST['phone'] ?? '', $_POST['address'] ?? '');
            setFlash('success', 'Data pelanggan berhasil ditambahkan.');
        } elseif ($action === 'update_customer') {
            $id = (int)($_POST['id'] ?? 0);
            $redirectUrl = "index.php?page=customers";
            $customerModel->update($id, $_POST['name'] ?? '', $_POST['nik'] ?? '', $_POST['phone'] ?? '', $_POST['address'] ?? '');
            setFlash('success', 'Data pelanggan berhasil diperbarui.');
        } elseif ($action === 'create_vehicle') {
            $redirectUrl = "index.php?page=vehicles";
            $vehicleModel->create($_POST['brand'] ?? '', $_POST['plate_number'] ?? '', (float)($_POST['daily_rate'] ?? 0), $_POST['status'] ?? 'available');
            setFlash('success', 'Data kendaraan berhasil ditambahkan.');
        } elseif ($action === 'update_vehicle') {
            $id = (int)($_POST['id'] ?? 0);
            $redirectUrl = "index.php?page=vehicles";
            $vehicleModel->update($id, $_POST['brand'] ?? '', $_POST['plate_number'] ?? '', (float)($_POST['daily_rate'] ?? 0), $_POST['status'] ?? 'available');
            setFlash('success', 'Data kendaraan berhasil diperbarui.');
        } elseif ($action === 'create_rental') {
            $redirectUrl = "index.php?page=rentals";
            $rentalModel->create((int)($_POST['customer_id'] ?? 0), (int)($_POST['vehicle_id'] ?? 0), $_POST['rental_date'] ?? '');
            setFlash('success', 'Transaksi sewa berhasil dibuat.');
        } elseif ($action === 'update_rental') {
            $id = (int)($_POST['id'] ?? 0);
            $redirectUrl = "index.php?page=rentals";
            $returnDate = !empty($_POST['return_date']) ? $_POST['return_date'] : null;
            $rentalModel->update(
                $id,
                (int)($_POST['customer_id'] ?? 0),
                (int)($_POST['vehicle_id'] ?? 0),
                $_POST['rental_date'] ?? '',
                // String kosong dikonversi ke null agar status transaksi terbuka tetap valid di database
                $returnDate,
                (float)($_POST['total_price'] ?? 0),
                $_POST['status'] ?? 'active'
            );
            setFlash('success', 'Transaksi sewa berhasil diperbarui.');
        } elseif ($action === 'complete_rental') {
            $redirectUrl = "index.php?page=rentals";
            $total = $rentalModel->completeRental((int)($_POST['id'] ?? 0), $_POST['return_date'] ?? '');
            setFlash('success', 'Kendaraan berhasil dikembalikan. Total biaya: Rp ' . number_format($total, 0, ',', '.'));
        } else {
            throw new InvalidArgumentException("Aksi formulir tidak dikenali.");
        }
    } catch (Throwable $e) {
        setFlash('error', $e->getMessage());
        // Pertahankan parameter edit saat validasi gagal agar user dapat langsung memperbaiki input
        if (str_starts_with((string)$action, 'update_') && !empty($_POST['id'])) {
            $redirectUrl .= '&edit=' . (int)$_POST['id'];
        }
    }

    header("Location: {$redirectUrl}");
    exit;
}

// Penanganan aksi hapus data via parameter query string GET
if (in_array($action, ['delete_customer', 'delete_vehicle', 'delete_rental'], true)) {
    $targetPage = match ($action) {
        'delete_customer' => 'customers',
        'delete_vehicle'  => 'vehicles',
        'delete_rental'   => 'rentals',
    };

    try {
        if (!isset($_GET['id']) || (int)$_GET['id'] <= 0) {
            throw new InvalidArgumentException("ID data yang akan dihapus tidak valid.");
        }
        $id = (int)$_GET['id'];

        if ($action === 'delete_customer') {
            $customerModel->delete($id);
            setFlash('success', 'Data pelanggan berhasil dihapus.');
        } elseif ($action === 'delete_vehicle') {
            $vehicleModel->delete($id);
            setFlash('success', 'Data kendaraan berhasil dihapus.');
        } elseif ($action === 'delete_rental') {
            $rentalModel->delete($id);
            setFlash('success', 'Transaksi sewa berhasil dihapus.');
        }
    } catch (Throwable $e) {
        setFlash('error', $e->getMessage());
    }

    header("Location: index.php?page={$targetPage}");
    exit;
}

// Ambil pesan flash dari session lalu hapus agar hanya tampil sekali setelah redirect
if (!isset($flash) && isset($_SESSION['flash'])) {
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
}

// Render layout antarmuka berdasarkan nilai parameter query string $page
include __DIR__ . '/view/header.php';
match ($page) {
    'customers' => include __DIR__ . '/view/customers.php',
    'vehicles'  => include __DIR__ . '/view/vehicles.php',
    default     => include __DIR__ . '/view/rentals.php',
};
include __DIR__ . '/view/footer.php';
