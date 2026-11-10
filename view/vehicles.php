<?php
// Cek keberadaan parameter edit untuk menentukan aksi form (tambah baru vs edit data lama)
$editId = $_GET['edit'] ?? null;
// Ambil record armada yang akan diedit untuk mengisi otomatis nilai field form
$vehicleEdit = $editId ? $vehicleModel->getById((int)$editId) : null;
// Beri tahu pengguna jika ID edit pada URL tidak ditemukan di database
$editNotFound = $editId !== null && $vehicleEdit === null;
// Ambil seluruh data mobil untuk tabel inventaris
$vehicles = $vehicleModel->getAll();
?>
<h2>Manajemen Kendaraan</h2>
<?php if ($editNotFound): ?>
    <div class="alert alert-error"><span>Data kendaraan yang ingin diubah tidak ditemukan.</span></div>
<?php endif; ?>

<div class="card form-card">
    <h3><?= $vehicleEdit ? 'Edit Kendaraan' : 'Tambah Kendaraan Baru' ?></h3>
    <form method="POST" action="index.php?page=vehicles">
        <!-- Input hidden action membedakan handler create_vehicle dan update_vehicle di controller -->
        <input type="hidden" name="action" value="<?= $vehicleEdit ? 'update_vehicle' : 'create_vehicle' ?>">
        <?php if ($vehicleEdit): ?>
            <!-- Sertakan ID mobil yang sedang diedit -->
            <input type="hidden" name="id" value="<?= $vehicleEdit['id'] ?>">
        <?php endif; ?>

        <div class="form-grid">
            <div>
                <label>Merk & Tipe Kendaraan</label>
                <input type="text" name="brand" required placeholder="Contoh: Toyota Avanza" value="<?= htmlspecialchars($vehicleEdit['brand'] ?? '') ?>">
            </div>
            <div>
                <label>Plat Nomor</label>
                <input type="text" name="plate_number" required placeholder="D 1234 ABC" value="<?= htmlspecialchars($vehicleEdit['plate_number'] ?? '') ?>">
            </div>
            <div>
                <label>Tarif Harian (Rp)</label>
                <!-- step 0.01 mengakomodasi input mata uang dengan angka desimal jika diperlukan -->
                <input type="number" step="0.01" name="daily_rate" required value="<?= htmlspecialchars($vehicleEdit['daily_rate'] ?? '') ?>">
            </div>
            <div>
                <label>Status</label>
                <!-- Atribut selected disematkan secara dinamis sesuai data status mobil saat ini -->
                <select name="status">
                    <option value="available" <?= ($vehicleEdit['status'] ?? '') === 'available' ? 'selected' : '' ?>>Tersedia</option>
                    <option value="rented" <?= ($vehicleEdit['status'] ?? '') === 'rented' ? 'selected' : '' ?>>Disewa</option>
                    <option value="maintenance" <?= ($vehicleEdit['status'] ?? '') === 'maintenance' ? 'selected' : '' ?>>Perawatan</option>
                </select>
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><?= $vehicleEdit ? 'Perbarui' : 'Simpan' ?></button>
            <?php if ($vehicleEdit): ?>
                <!-- Tombol batal membersihkan form dan mengembalikan tampilan ke mode tambah baru -->
                <a href="index.php?page=vehicles" class="btn btn-secondary">Batal</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="card">
    <h3>Daftar Kendaraan</h3>
    <div class="table-responsive">
        <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Merk</th>
                <th>Plat Nomor</th>
                <th>Tarif/Hari</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($vehicles)): ?>
                <tr><td colspan="6" style="text-align:center;">Belum ada armada.</td></tr>
            <?php else: $no = 1; foreach ($vehicles as $v): ?>
                <tr>
                    <!-- Penomoran baris dihitung di tampilan agar tidak bergantung pada auto-increment ID -->
                    <td><?= $no++ ?></td>
                    <td><?= htmlspecialchars($v['brand']) ?></td>
                    <td><?= htmlspecialchars($v['plate_number']) ?></td>
                    <!-- Format angka ke format mata uang rupiah tanpa desimal sen -->
                    <td>Rp <?= number_format($v['daily_rate'], 0, ',', '.') ?></td>
                    <!-- Class badge disesuaikan dengan status mobil agar memiliki warna indikator berbeda -->
                    <td><span class="badge badge-<?= $v['status'] ?>"><?= ucfirst($v['status']) ?></span></td>
                    <td>
                        <a href="index.php?page=vehicles&edit=<?= $v['id'] ?>" class="btn btn-edit">Edit</a>
                        <a href="index.php?action=delete_vehicle&id=<?= $v['id'] ?>" class="btn btn-delete" onclick="return confirm('Hapus kendaraan ini?')">Hapus</a>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
    </div>
</div>
