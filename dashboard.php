<?php
include 'includes/header.php';

$role = $_SESSION['role'] ?? 'user';
$user_id = $_SESSION['id'] ?? 1;
$nama = $_SESSION['nama'] ?? 'User';

// 1. Get Total Alat
$res_alat = call_api('GET', '/api/alat');
$all_alat = $res_alat['data'] ?? [];
$total_alat = count($all_alat);

$alat_dict = [];
$gambar_dict = [];
foreach ($all_alat as $a) {
    if (!empty($a['nama_alat'])) {
        $alat_dict[$a['nama_alat']] = $a['kode_alat'] ?? '-';
        $fotoStr = $a['foto'] ?? '';
        $fotoArr = $fotoStr ? explode('|', $fotoStr) : [];
        $gambar_dict[$a['nama_alat']] = !empty($fotoArr[0]) ? $fotoArr[0] : 'https://images.unsplash.com/photo-1558655146-d09347e92766?w=600';
    }
}

// 2. Get Peminjaman
$res_pinjaman = call_api('GET', '/api/peminjaman/riwayat');
$peminjaman_list = $res_pinjaman['data'] ?? [];

// 3. Get Denda
if ($role === 'admin') {
    $res_denda = call_api('GET', '/api/admin/denda');
} else {
    $res_denda = call_api('GET', '/api/user/denda');
}
$denda_list = $res_denda['data'] ?? [];

$pinjaman_aktif_admin = [];
$total_pinjaman_aktif = 0;
$total_pengembalian = 0;

$user_pinjaman_aktif = [];
$user_riwayat = [];

foreach ($peminjaman_list as $loan) {
    if ($loan['status'] === 'Disetujui' || $loan['status'] === 'Dipinjam' || $loan['status'] === 'Aktif') {
        if ($role === 'admin') {
            $total_pinjaman_aktif++;
            $pinjaman_aktif_admin[] = $loan;
        }
        if ($loan['user_id'] == $user_id) {
            $user_pinjaman_aktif[] = $loan;
        }
    } elseif ($loan['status'] === 'Selesai' || $loan['status'] === 'Dikembalikan') {
        if ($role === 'admin') {
            $total_pengembalian++;
        }
        if ($loan['user_id'] == $user_id) {
            $user_riwayat[] = $loan;
        }
    }
}

$denda_belum_bayar_admin = [];
$total_denda_admin = 0;
$user_total_denda = 0;

foreach ($denda_list as $denda) {
    $status_bayar = $denda['status_bayar'] ?? $denda['status_pembayaran'] ?? $denda['status'] ?? '';
    if ($status_bayar === 'Belum Lunas') {
        $amount = floatval($denda['jumlah_denda'] ?? $denda['denda'] ?? 0);
        if ($role === 'admin') {
            $total_denda_admin += $amount;
            $denda_belum_bayar_admin[] = $denda;
        }
        if ($denda['user_id'] == $user_id || $role !== 'admin') {
            $user_total_denda += $amount;
        }
    }
}
?>

<div class="dashboard">

    <?php if ($role === 'admin'): ?>
        <div class="admin-dashboard">
            <div class="admin-header">
                <div class="welcome">
                    <h2>Selamat Datang,
                        <?= htmlspecialchars($nama) ?> 👋
                    </h2>
                </div>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <h3><?= $total_alat ?></h3>
                    <p>Total Alat</p>
                </div>
                <div class="stat-card">
                    <h3><?= $total_pinjaman_aktif ?></h3>
                    <p>Peminjaman</p>
                </div>
                <div class="stat-card">
                    <h3><?= $total_pengembalian ?></h3>
                    <p>Pengembalian</p>
                </div>
                <div class="stat-card">
                    <h3>Rp<?= number_format($total_denda_admin / 1000, 0, ',', '.') ?>K</h3>
                    <p>Total Denda</p>
                </div>
            </div>

            <div class="section">
                <div class="section-title">
                    <h3>Peminjaman Aktif</h3>
                </div>
                <?php if (empty($pinjaman_aktif_admin)): ?>
                    <div class="card-item">
                        <p>Tidak ada peminjaman aktif.</p>
                    </div>
                <?php else: ?>
                    <div
                        style="display: flex; width: 100%; padding: 0 20px 10px; font-size: 12px; font-weight: bold; color: #888; text-transform: uppercase;">
                        <div style="width: 20%;">Tanggal</div>
                        <div style="width: 40%;">Nama Alat / Peminjam</div>
                        <div style="width: 40%;">Status</div>
                    </div>
                    <?php foreach ($pinjaman_aktif_admin as $loan): ?>
                        <div class="card-item" style="display: flex; align-items: center; padding: 15px 20px; margin-bottom: 8px;">
                            <div style="width: 20%;">
                                <?= date('d/m/Y', strtotime($loan['tanggal_kembali_rencana'] ?? $loan['tgl_kembali'] ?? 'now')) ?>
                            </div>
                            <div style="width: 40%;">
                                <strong><?= htmlspecialchars($loan['nama_alat'] ?? 'Unknown') ?></strong><br><small><?= htmlspecialchars($loan['user_nama'] ?? $loan['nama_user'] ?? 'User') ?></small>
                            </div>
                            <div style="width: 40%;"><span class="status status-success">Aktif</span></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="section">
                <div class="section-title">
                    <h3>Denda Belum Bayar</h3>
                </div>
                <?php if (empty($denda_belum_bayar_admin)): ?>
                    <div class="card-item">
                        <p>Tidak ada denda belum lunas.</p>
                    </div>
                <?php else: ?>
                    <div
                        style="display: flex; width: 100%; padding: 0 20px 10px; font-size: 12px; font-weight: bold; color: #888; text-transform: uppercase;">
                        <div style="width: 30%;">Peminjam</div>
                        <div style="width: 40%;">Nama & Kode Alat</div>
                        <div style="width: 30%;">Jenis & Jumlah Denda</div>
                    </div>
                    <?php foreach ($denda_belum_bayar_admin as $loan): ?>
                        <?php $kode = $alat_dict[$loan['nama_alat'] ?? ''] ?? '-'; ?>
                        <div class="card-item" style="display: flex; align-items: center; padding: 15px 20px; margin-bottom: 8px;">
                            <div style="width: 30%;">
                                <?= htmlspecialchars($loan['nama_mahasiswa'] ?? $loan['user_nama'] ?? $loan['nama_user'] ?? 'User') ?>
                            </div>
                            <div style="width: 40%;">
                                <strong><?= htmlspecialchars($loan['nama_alat'] ?? 'Unknown') ?></strong><br>
                                <small style="font-family: monospace; color: #64748b;"><?= htmlspecialchars($kode) ?></small>
                            </div>
                            <div style="width: 30%;">
                                <strong><?= htmlspecialchars($loan['jenis_denda'] ?? '-') ?></strong><br>
                                <span style="color: #ef4444; font-weight: 600;">Rp
                                    <?= number_format($loan['jumlah_denda'] ?? $loan['denda'] ?? 0, 0, ',', '.') ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

    <?php else: ?>
        <div class="header-dashboard">
            <div class="welcome">
                <h2>Selamat Datang, <?= htmlspecialchars($nama) ?> 👋</h2>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <h3><?= count($user_pinjaman_aktif) ?></h3>
                <p>Peminjaman Aktif</p>
            </div>
            <div class="stat-card">
                <h3><?= count($user_riwayat) ?></h3>
                <p>Riwayat Selesai</p>
            </div>
            <!-- Dummy child ke-3 agar Total Denda menjadi child ke-4 (merah) -->
            <div style="display: none;"></div>
            <div class="stat-card">
                <h3>Rp<?= number_format($user_total_denda / 1000, 0, ',', '.') ?>K</h3>
                <p>Total Denda</p>
            </div>
        </div>

        <div class="section" style="margin-bottom: 30px;">
            <div class="section-title"
                style="margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center;">
                <h3>Peminjaman Aktif</h3>
                <a href="peminjaman.php" class="badge-category" style="text-decoration: none;">Lihat Semua &gt;</a>
            </div>

            <?php if (empty($user_pinjaman_aktif)): ?>
                <div class="card-item" style="padding: 20px; text-align: center; color: #666;">
                    <p>Tidak ada peminjaman aktif.</p>
                </div>

            <?php else: ?>
                <?php foreach (array_slice($user_pinjaman_aktif, 0, 5) as $loan): ?>
                    <?php
                    $nama_alat = $loan['nama_alat'] ?? 'Unknown';
                    $kode = $alat_dict[$nama_alat] ?? $loan['kode_alat'] ?? '-';
                    $img = $gambar_dict[$nama_alat] ?? 'https://images.unsplash.com/photo-1558655146-d09347e92766?w=600';
                    ?>
                    <div class="card-item"
                        style="display: flex; padding: 20px; align-items: center; margin-bottom: 15px; border-radius: 16px;">
                        <div
                            style="width: 80px; height: 80px; background: #ffffff; border-radius: 12px; margin-right: 20px; overflow: hidden; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <img src="<?= htmlspecialchars($img) ?>"
                                style="max-width: 100%; max-height: 100%; object-fit: contain;">
                        </div>
                        <div style="flex: 1; font-size: 13px; color: #64748b; line-height: 1.6;">
                            <div style="font-size: 14px; font-weight: 700; color: #0f172a; margin-bottom: 5px;">
                                Kode: <?= htmlspecialchars($kode) ?>
                            </div>
                            <div style="display: flex; align-items: center;">
                                <span style="display: inline-block; width: 85px;">Dipinjam:</span>
                                <?= date('Y-m-d', strtotime($loan['tanggal_pinjam'] ?? $loan['tgl_pinjam'] ?? 'now')) ?>
                            </div>
                            <div style="display: flex; align-items: center;">
                                <span style="display: inline-block; width: 85px;">Jatuh tempo:</span>
                                <?= date('Y-m-d', strtotime($loan['tanggal_kembali_rencana'] ?? $loan['tgl_kembali'] ?? 'now')) ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="section">
            <div class="section-title" style="margin-bottom: 15px;">
                <h3>Manajemen Denda</h3>
            </div>

            <div class="card-item" style="padding: 20px;">
                <div style="font-size: 24px; font-weight: 700; color: #0f172a; margin-bottom: 15px;">
                    Rp <?= number_format($user_total_denda, 0, ',', '.') ?>
                </div>
                <div style="font-size: 14px; margin-bottom: 20px; color: #64748b;">
                    Status:
                    <?php if ($user_total_denda == 0): ?>
                        <span style="color: #10b981; font-weight: 600;">Lunas</span>
                    <?php else: ?>
                        <span style="color: #ef4444; font-weight: 600;">Belum Lunas</span>
                    <?php endif; ?>
                </div>

                <hr style="border: 0; border-top: 1px solid #e2e8f0; margin-bottom: 15px;">

                <div
                    style="display: flex; align-items: flex-start; gap: 10px; color: #64748b; font-size: 12px; line-height: 1.5;">
                    <div style="margin-top: 2px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="16" x2="12" y2="12"></line>
                            <line x1="12" y1="8" x2="12.01" y2="8"></line>
                        </svg>
                    </div>
                    <div>
                        <?php if ($user_total_denda > 0): ?>
                            Segera lunasi denda Anda untuk menghindari pembatasan peminjaman!
                        <?php else: ?>
                            Bayar denda tepat waktu untuk menghindari pembatasan peminjaman!
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>