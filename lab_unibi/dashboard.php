<?php
include 'includes/header.php';

$role = $_SESSION['role'];
$user_id = $_SESSION['id'];
$nama = $_SESSION['nama'];

$total_alat = count($_SESSION['dummy_alat']);

$pinjaman_aktif_admin = [];
$denda_belum_bayar_admin = [];
$total_pinjaman_aktif = 0;
$total_pengembalian = 0;
$total_denda_admin = 0;

foreach ($_SESSION['dummy_peminjaman'] as $loan) {
    if ($loan['status'] === 'Aktif') {
        $total_pinjaman_aktif++;
        $pinjaman_aktif_admin[] = $loan;
    } elseif ($loan['status'] === 'Selesai') {
        $total_pengembalian++;
    }
    
    if ($loan['status'] === 'Belum Lunas') {
        $total_denda_admin += $loan['denda'];
        $denda_belum_bayar_admin[] = $loan;
    }
}

$user_pinjaman_aktif = [];
$user_riwayat = [];
$user_total_denda = 0;

foreach ($_SESSION['dummy_peminjaman'] as $loan) {
    if ($loan['user_id'] == $user_id) {
        if ($loan['status'] === 'Aktif') {
            $user_pinjaman_aktif[] = $loan;
        } else {
            $user_riwayat[] = $loan;
        }
        
        if ($loan['status'] === 'Belum Lunas') {
            $user_total_denda += $loan['denda'];
        }
    }
}
?>

<div class="dashboard">

<?php if ($role === 'admin'): ?>
    <div class="admin-dashboard">
        <div class="admin-header">
            <h3>Dashboard Admin</h3>
        </div>

        <div class="stats-grid">
            <div class="stat-card"><h3><?= $total_alat ?></h3><p>Total Alat</p></div>
            <div class="stat-card"><h3><?= $total_pinjaman_aktif ?></h3><p>Pinjaman</p></div>
            <div class="stat-card"><h3><?= $total_pengembalian ?></h3><p>Pengembalian</p></div>
            <div class="stat-card"><h3>Rp<?= number_format($total_denda_admin / 1000, 0, ',', '.') ?>K</h3><p>Total Denda</p></div>
        </div>

        <div class="section">
            <div class="section-title"><h3>Pinjaman Aktif</h3></div>
            <?php if (empty($pinjaman_aktif_admin)): ?>
                <div class="card-item"><p>Tidak ada pinjaman aktif.</p></div>
            <?php else: ?>
                <div style="display: flex; width: 100%; padding: 0 20px 10px; font-size: 12px; font-weight: bold; color: #888; text-transform: uppercase;">
                    <div style="width: 20%;">Tanggal</div>
                    <div style="width: 40%;">Nama Alat / Peminjam</div>
                    <div style="width: 40%;">Status</div>
                </div>
                <?php foreach ($pinjaman_aktif_admin as $loan): ?>
                    <div class="card-item" style="display: flex; align-items: center; padding: 15px 20px; margin-bottom: 8px;">
                        <div style="width: 20%;"><?= date('d/m/Y', strtotime($loan['tgl_kembali'])) ?></div>
                        <div style="width: 40%;"><strong><?= htmlspecialchars($loan['nama_alat']) ?></strong><br><small><?= htmlspecialchars($loan['user_nama']) ?></small></div>
                        <div style="width: 40%;"><span class="status status-success">Aktif</span></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="section">
            <div class="section-title"><h3>Denda Belum Bayar</h3></div>
            <?php if (empty($denda_belum_bayar_admin)): ?>
                <div class="card-item"><p>Tidak ada denda belum lunas.</p></div>
            <?php else: ?>
                <div style="display: flex; width: 100%; padding: 0 20px 10px; font-size: 12px; font-weight: bold; color: #888; text-transform: uppercase;">
                    <div style="width: 40%;">Peminjam</div>
                    <div style="width: 30%;">Kode Alat</div>
                    <div style="width: 30%;">Jumlah Denda</div>
                </div>
                <?php foreach ($denda_belum_bayar_admin as $loan): ?>
                    <div class="card-item" style="display: flex; align-items: center; padding: 15px 20px; margin-bottom: 8px;">
                        <div style="width: 40%;"><?= htmlspecialchars($loan['user_nama']) ?></div>
                        <div style="width: 30%;"><?= htmlspecialchars($loan['kode_alat']) ?></div>
                        <div style="width: 30%;">Rp <?= number_format($loan['denda'], 0, ',', '.') ?></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

<?php else: ?>
    <div class="header-dashboard">
        <div class="welcome"><h2>Selamat Datang, <?= htmlspecialchars($nama) ?> 👋</h2></div>
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
        <div style="display: flex; width: 100%; padding: 0 20px 10px 20px; font-size: 12px; font-weight: bold; color: #888; text-transform: uppercase;">
            <div style="width: 30%;">Tanggal Pinjam</div>
            <div style="width: 40%;">Nama Alat</div>
            <div style="width: 30%; text-align: right;">Status</div>
        </div>

        <?php foreach ($user_pinjaman_aktif as $loan): ?>
            <div class="card-item" style="display: flex; align-items: center; width: 100%; padding: 15px 20px; margin-bottom: 8px;">
                <div style="width: 30%; font-size: 14px;"><?= date('d/m/Y', strtotime($loan['tgl_pinjam'])) ?></div>
                <div style="width: 40%;">
                    <div style="font-weight: 600;"><?= htmlspecialchars($loan['nama_alat']) ?></div>
                    <small style="color: #666;">Kode: <?= htmlspecialchars($loan['kode_alat']) ?></small>
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

    <div style="display: flex; width: 100%; padding: 0 20px 10px 20px; font-size: 12px; font-weight: bold; color: #888; text-transform: uppercase;">
        <div style="width: 30%;">Tanggal Pinjam</div>
        <div style="width: 40%;">Nama Alat</div>
        <div style="width: 30%; text-align: right;">Status</div>
    </div>

    <?php foreach (array_slice($user_riwayat, 0, 2) as $loan): ?>
        <div class="card-item" style="display: flex; align-items: center; width: 100%; padding: 15px 20px; margin-bottom: 8px;">
            <div style="width: 30%; font-size: 14px;"><?= date('d/m/Y', strtotime($loan['tgl_pinjam'])) ?></div>
            <div style="width: 40%; font-weight: 600;"><?= htmlspecialchars($loan['nama_alat']) ?></div>
            <div style="width: 30%; text-align: right;">
                <span class="status <?= $loan['status'] === 'Selesai' ? 'status-success' : 'status-danger' ?>">
                    <?= htmlspecialchars($loan['status']) ?>
                </span>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>