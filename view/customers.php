<?php
// Parameter 'edit' pada query string menentukan apakah form bekerja dalam mode tambah atau perbarui
$editId = $_GET['edit'] ?? null;
// Ambil record spesifik untuk diisikan ke dalam form saat mode edit aktif
$customerEdit = $editId ? $customerModel->getById((int)$editId) : null;
// Beri tahu pengguna jika ID edit pada URL tidak ditemukan di database
$editNotFound = $editId !== null && $customerEdit === null;
// Ambil seluruh data pelanggan untuk ditampilkan pada tabel
$customers = $customerModel->getAll();
?>
<h2>Manajemen Pelanggan</h2>
<?php if ($editNotFound): ?>
    <div class="alert alert-error"><span>Data pelanggan yang ingin diubah tidak ditemukan.</span></div>
<?php endif; ?>

<div class="card form-card">
    <h3><?= $customerEdit ? 'Edit Pelanggan' : 'Tambah Pelanggan Baru' ?></h3>
    <form method="POST" action="index.php?page=customers">
        <!-- Field tersembunyi untuk mengidentifikasi aksi controller yang tepat di index.php -->
        <input type="hidden" name="action" value="<?= $customerEdit ? 'update_customer' : 'create_customer' ?>">
        <?php if ($customerEdit): ?>
            <!-- Sertakan ID pelanggan sebagai acuan klausa WHERE pada operasi UPDATE -->
            <input type="hidden" name="id" value="<?= $customerEdit['id'] ?>">
        <?php endif; ?>
        
        <div class="form-grid">
            <div>
                <label>Nama Lengkap</label>
                <!-- htmlspecialchars digunakan untuk mencegah kerentanan Cross-Site Scripting (XSS) saat menampilkan data -->
                <input type="text" name="name" required value="<?= htmlspecialchars($customerEdit['name'] ?? '') ?>">
            </div>
            <div>
                <label>NIK (KTP)</label>
                <!-- Batasi panjang input maksimal 16 digit sesuai standar NIK nasional -->
                <input type="text" name="nik" required maxlength="16" value="<?= htmlspecialchars($customerEdit['nik'] ?? '') ?>">
            </div>
            <div>
                <label>No. Telepon</label>
                <input type="text" name="phone" required value="<?= htmlspecialchars($customerEdit['phone'] ?? '') ?>">
            </div>
            <div>
                <label>Alamat</label>
                <input type="text" name="address" required value="<?= htmlspecialchars($customerEdit['address'] ?? '') ?>">
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><?= $customerEdit ? 'Perbarui' : 'Simpan' ?></button>
            <?php if ($customerEdit): ?>
                <!-- Tombol batal mengarahkan kembali ke daftar bersih tanpa parameter edit di URL -->
                <a href="index.php?page=customers" class="btn btn-secondary">Batal</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="card">
    <h3>Daftar Pelanggan</h3>
    <div class="table-responsive">
        <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Nama</th>
                <th>NIK</th>
                <th>Telepon</th>
                <th>Alamat</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($customers)): ?>
                <!-- Tampilan baris informatif ketika database belum memiliki data pelanggan -->
                <tr><td colspan="6" style="text-align:center;">Belum ada data pelanggan.</td></tr>
            <?php else: $no = 1; foreach ($customers as $c): ?>
                <tr>
                    <!-- Penomoran baris dihitung di tampilan agar tetap rapi meski ada ID database yang terhapus -->
                    <td><?= $no++ ?></td>
                    <td><?= htmlspecialchars($c['name']) ?></td>
                    <td><?= htmlspecialchars($c['nik']) ?></td>
                    <td><?= htmlspecialchars($c['phone']) ?></td>
                    <td><?= htmlspecialchars($c['address']) ?></td>
                    <td>
                        <a href="index.php?page=customers&edit=<?= $c['id'] ?>" class="btn btn-edit">Edit</a>
                        <!-- Dialog konfirmasi mencegah penghapusan data secara tidak sengaja oleh pengguna -->
                        <a href="index.php?action=delete_customer&id=<?= $c['id'] ?>" class="btn btn-delete" onclick="return confirm('Hapus pelanggan ini?')">Hapus</a>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
    </div>
</div>
