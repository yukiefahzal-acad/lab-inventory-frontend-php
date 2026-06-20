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
foreach ($all_alat as $a) {
    if (!empty($a['nama_alat'])) {
        $alat_dict[$a['nama_alat']] = $a['kode_alat'] ?? '-';
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
                    <p>Pinjaman</p>
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
                    <h3>Pinjaman Aktif</h3>
                </div>
                <?php if (empty($pinjaman_aktif_admin)): ?>
                    <div class="card-item">
                        <p>Tidak ada pinjaman aktif.</p>
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
                                <span style="color: #ef4444; font-weight: 600;">Rp <?= number_format($loan['jumlah_denda'] ?? $loan['denda'] ?? 0, 0, ',', '.') ?></span>
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
                <p>Pinjaman Aktif</p>
            </div>
            <div class="stat-card">
                <h3><?= count($user_riwayat) ?></h3>
                <p>Riwayat Selesai</p>
            </div>
            <div class="stat-card">
                <h3>Rp<?= number_format($user_total_denda / 1000, 0, ',', '.') ?>K</h3>
                <p>Total Denda</p>
            </div>
        </div>

        <div class="section" style="margin-bottom: 30px;">
            <div class="section-title" style="margin-bottom: 15px;">
                <h3>Status Pinjaman Aktif</h3>
            </div>

            <?php if (empty($user_pinjaman_aktif)): ?>
                <div class="card-item" style="padding: 20px; text-align: center; color: #666;">
                    <p>Tidak ada pinjaman aktif.</p>
                </div>

            <?php else: ?>
                <div
                    style="display: flex; width: 100%; padding: 0 20px 10px 20px; font-size: 12px; font-weight: bold; color: #888; text-transform: uppercase;">
                    <div style="width: 30%;">Tanggal Pinjam</div>
                    <div style="width: 40%;">Nama Alat</div>
                    <div style="width: 30%; text-align: right;">Status</div>
                </div>

                <?php foreach ($user_pinjaman_aktif as $loan): ?>
                    <div class="card-item"
                        style="display: flex; align-items: center; width: 100%; padding: 15px 20px; margin-bottom: 8px;">
                        <div style="width: 30%; font-size: 14px;">
                            <?= date('d/m/Y', strtotime($loan['tanggal_pinjam'] ?? $loan['tgl_pinjam'] ?? 'now')) ?></div>
                        <div style="width: 40%;">
                            <div style="font-weight: 600;"><?= htmlspecialchars($loan['nama_alat'] ?? 'Unknown') ?></div>
                            <small style="color: #666;">Kode: <?= htmlspecialchars($loan['kode_alat'] ?? '-') ?></small>
                        </div>
                        <div style="width: 30%; text-align: right;">
                            <span class="status status-success">Aktif</span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="section">
            <div class="section-title" style="margin-bottom: 15px;">
                <h3>Riwayat Peminjaman</h3>
            </div>

            <div
                style="display: flex; width: 100%; padding: 0 20px 10px 20px; font-size: 12px; font-weight: bold; color: #888; text-transform: uppercase;">
                <div style="width: 30%;">Tanggal Pinjam</div>
                <div style="width: 40%;">Nama Alat</div>
                <div style="width: 30%; text-align: right;">Status</div>
            </div>

            <?php foreach (array_slice($user_riwayat, 0, 2) as $loan): ?>
                <div class="card-item"
                    style="display: flex; align-items: center; width: 100%; padding: 15px 20px; margin-bottom: 8px;">
                    <div style="width: 30%; font-size: 14px;">
                        <?= date('d/m/Y', strtotime($loan['tanggal_pinjam'] ?? $loan['tgl_pinjam'] ?? 'now')) ?></div>
                    <div style="width: 40%; font-weight: 600;"><?= htmlspecialchars($loan['nama_alat'] ?? 'Unknown') ?></div>
                    <div style="width: 30%; text-align: right;">
                        <span
                            class="status <?= ($loan['status'] === 'Selesai' || $loan['status'] === 'Dikembalikan') ? 'status-success' : 'status-danger' ?>">
                            <?= htmlspecialchars($loan['status']) ?>
                        </span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>