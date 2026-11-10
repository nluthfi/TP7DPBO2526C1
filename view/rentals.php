<?php
// Tangkap parameter query string 'edit' untuk membedakan mode form booking baru vs revisi transaksi
$editId = $_GET['edit'] ?? null;
// Ambil data transaksi spesifik jika sedang berada dalam mode edit
$rentalEdit = $editId ? $rentalModel->getById((int)$editId) : null;
// Beri tahu pengguna jika ID transaksi pada URL tidak ditemukan di database
$editNotFound = $editId !== null && $rentalEdit === null;
// Muat riwayat seluruh transaksi sewa untuk tabel bawah
$rentals = $rentalModel->getAll();
// Ambil seluruh data pelanggan untuk mengisi opsi dropdown pemilihan penyewa
$customers = $customerModel->getAll();
// Mode transaksi baru hanya menampilkan mobil berstatus 'available'
$availableVehicles = $vehicleModel->getAvailable();
// Mode edit membutuhkan seluruh mobil agar mobil yang sedang tersewa tetap terpilih di dropdown
$allVehicles = $vehicleModel->getAll();
?>
<h2>Transaksi Sewa Kendaraan</h2>
<?php if ($editNotFound): ?>
    <div class="alert alert-error"><span>Transaksi sewa yang ingin diubah tidak ditemukan.</span></div>
<?php endif; ?>

<div class="card form-card">
    <h3><?= $rentalEdit ? 'Edit Transaksi Sewa' : 'Buat Transaksi Sewa Baru' ?></h3>
    <form method="POST" action="index.php?page=rentals">
        <!-- Handler controller dibedakan via nilai atribut input hidden action -->
        <input type="hidden" name="action" value="<?= $rentalEdit ? 'update_rental' : 'create_rental' ?>">
        <?php if ($rentalEdit): ?>
            <!-- Sertakan ID transaksi saat melakukan pembaruan data -->
            <input type="hidden" name="id" value="<?= $rentalEdit['id'] ?>">
        <?php endif; ?>

        <div class="form-grid">
            <div>
                <label>Pelanggan</label>
                <select name="customer_id" required>
                    <option value="">-- Pilih Pelanggan --</option>
                    <?php foreach ($customers as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= ($rentalEdit['customer_id'] ?? '') == $c['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['name']) ?> (<?= htmlspecialchars($c['nik']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label>Kendaraan</label>
                <select name="vehicle_id" required>
                    <option value="">-- Pilih Kendaraan --</option>
                    <?php if ($rentalEdit): ?>
                        <!-- Pada mode edit, tampilkan seluruh armada agar mobil yang sedang disewa tetap muncul terpilih -->
                        <?php foreach ($allVehicles as $v): ?>
                            <option value="<?= $v['id'] ?>" <?= $rentalEdit['vehicle_id'] == $v['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($v['brand']) ?> [<?= $v['plate_number'] ?>] - Rp <?= number_format($v['daily_rate'], 0, ',', '.') ?>/hari
                            </option>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <!-- Pada mode buat sewa baru, hanya mobil yang berstatus available yang boleh disewa -->
                        <?php foreach ($availableVehicles as $v): ?>
                            <option value="<?= $v['id'] ?>">
                                <?= htmlspecialchars($v['brand']) ?> [<?= $v['plate_number'] ?>] - Rp <?= number_format($v['daily_rate'], 0, ',', '.') ?>/hari
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div>
                <label>Tanggal Sewa</label>
                <!-- Default nilai tanggal adalah hari ini jika form dalam mode buat baru -->
                <input type="date" name="rental_date" required value="<?= $rentalEdit['rental_date'] ?? date('Y-m-d') ?>">
            </div>
            <?php if ($rentalEdit): ?>
                <!-- Bagian tanggal kembali, total biaya, dan status hanya ditampilkan saat mode edit/revisi -->
                <div>
                    <label>Tanggal Kembali</label>
                    <input type="date" name="return_date" value="<?= $rentalEdit['return_date'] ?? '' ?>">
                </div>
                <div>
                    <label>Total Biaya (Rp)</label>
                    <input type="number" step="0.01" name="total_price" value="<?= $rentalEdit['total_price'] ?? 0 ?>">
                </div>
                <div>
                    <label>Status</label>
                    <select name="status">
                        <option value="active" <?= $rentalEdit['status'] === 'active' ? 'selected' : '' ?>>Aktif</option>
                        <option value="completed" <?= $rentalEdit['status'] === 'completed' ? 'selected' : '' ?>>Selesai</option>
                        <option value="cancelled" <?= $rentalEdit['status'] === 'cancelled' ? 'selected' : '' ?>>Dibatalkan</option>
                    </select>
                </div>
            <?php endif; ?>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><?= $rentalEdit ? 'Perbarui' : 'Sewa Sekarang' ?></button>
            <?php if ($rentalEdit): ?>
                <a href="index.php?page=rentals" class="btn btn-secondary">Batal</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="card">
    <h3>Riwayat & Daftar Sewa Aktif</h3>
    <div class="table-responsive">
        <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Pelanggan</th>
                <th>Kendaraan</th>
                <th>Tgl Sewa</th>
                <th>Tgl Kembali</th>
                <th>Total Biaya</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($rentals)): ?>
                <tr><td colspan="8" style="text-align:center;">Belum ada data penyewaan.</td></tr>
            <?php else: $no = 1; foreach ($rentals as $r): ?>
                <tr>
                    <!-- Penomoran baris dihitung di tampilan agar tetap urut tanpa celah ID -->
                    <td><?= $no++ ?></td>
                    <td><?= htmlspecialchars($r['customer_name']) ?><br><small><?= htmlspecialchars($r['customer_phone']) ?></small></td>
                    <td><?= htmlspecialchars($r['vehicle_brand']) ?><br><small><?= htmlspecialchars($r['plate_number']) ?></small></td>
                    <td><?= $r['rental_date'] ?></td>
                    <!-- Tampilkan teks peringatan oranye jika kendaraan belum dikembalikan -->
                    <td><?= $r['return_date'] ?? '<span style="color:#f59e0b">Belum Kembali</span>' ?></td>
                    <td>Rp <?= number_format($r['total_price'], 0, ',', '.') ?></td>
                    <td><span class="badge badge-<?= $r['status'] ?>"><?= ucfirst($r['status']) ?></span></td>
                    <td>
                        <?php if ($r['status'] === 'active'): ?>
                            <!-- Form cepat satu tombol untuk proses pengembalian otomatis berdasarkan tanggal hari ini -->
                            <form method="POST" action="index.php?page=rentals" style="display:inline-block; margin-bottom:4px;">
                                <input type="hidden" name="action" value="complete_rental">
                                <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                <input type="hidden" name="return_date" value="<?= date('Y-m-d') ?>">
                                <button type="submit" class="btn btn-success" onclick="return confirm('Proses pengembalian mobil sekarang?')">Kembalikan</button>
                            </form>
                        <?php endif; ?>
                        <a href="index.php?page=rentals&edit=<?= $r['id'] ?>" class="btn btn-edit">Edit</a>
                        <a href="index.php?action=delete_rental&id=<?= $r['id'] ?>" class="btn btn-delete" onclick="return confirm('Hapus transaksi ini?')">Hapus</a>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
    </div>
</div>
