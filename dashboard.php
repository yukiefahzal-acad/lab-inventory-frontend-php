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
    <div class="admin-dashboard" style="min-height: auto; padding-bottom: 0;">
        <div class="admin-header">
            <div class="search-box" style="margin-top: 15px;">
                <form action="katalog.php" method="GET">
                    <input type="text" name="search" placeholder="Cari alat..." autocomplete="off" spellcheck="false">
                </form>
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

        <div class="admin-section">
            <h3>Pinjaman Aktif</h3>
            <?php if (empty($pinjaman_aktif_admin)): ?>
                <div class="admin-card">
                    <p>Tidak ada pinjaman aktif.</p>
                </div>
            <?php else: ?>
                <?php foreach ($pinjaman_aktif_admin as $loan): ?>
                    <div class="admin-card">
                        <h4>Kode : <?= htmlspecialchars($loan['kode_alat']) ?></h4>
                        <p><?= htmlspecialchars($loan['nama_alat']) ?> - <?= htmlspecialchars($loan['user_nama']) ?></p>
                        <p>Jatuh tempo : <?= date('d/m/Y', strtotime($loan['tgl_kembali'])) ?></p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="admin-section">
            <h3>Denda Belum Bayar</h3>
            <?php if (empty($denda_belum_bayar_admin)): ?>
                <div class="admin-card">
                    <p>Tidak ada denda belum lunas.</p>
                </div>
            <?php else: ?>
                <?php foreach ($denda_belum_bayar_admin as $loan): ?>
                    <div class="admin-card">
                        <h4><?= htmlspecialchars($loan['user_nama']) ?></h4>
                        <p>Kode : <?= htmlspecialchars($loan['kode_alat']) ?></p>
                        <p>Rp <?= number_format($loan['denda'], 0, ',', '.') ?></p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

<?php else: ?>
    <div class="header-dashboard">
        <div class="search-box">
            <form action="katalog.php" method="GET">
                <input type="text" name="search" placeholder="Cari alat..." autocomplete="off" spellcheck="false">
            </form>
        </div>
        <div class="welcome">
            <h2>Selamat Datang, <?= htmlspecialchars($nama) ?> 👋</h2>
            <p>Kelola peminjaman alat laboratorium dengan mudah.</p>
        </div>
    </div>

    <div class="section">
        <div class="section-title">
            <h3>Status Pinjaman Aktif</h3>
            <a href="riwayat.php">Lihat Semua</a>
        </div>
        <?php if (empty($user_pinjaman_aktif)): ?>
            <div class="card-item">
                <p>Tidak ada pinjaman aktif.</p>
            </div>
        <?php else: ?>
            <?php foreach ($user_pinjaman_aktif as $loan): ?>
                <div class="card-item">
                    <h4>Kode: <?= htmlspecialchars($loan['kode_alat']) ?></h4>
                    <p>Nama Alat : <?= htmlspecialchars($loan['nama_alat']) ?></p>
                    <p>Dipinjam : <?= date('d/m/Y', strtotime($loan['tgl_pinjam'])) ?></p>
                    <p>Jatuh Tempo : <?= date('d/m/Y', strtotime($loan['tgl_kembali'])) ?></p>
                    <br>
                    <span class="status status-success">Aktif</span>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="section">
        <div class="section-title">
            <h3>Riwayat Peminjaman</h3>
            <a href="riwayat.php">Lihat Semua</a>
        </div>
        <?php if (empty($user_riwayat)): ?>
            <div class="card-item">
                <p>Belum ada riwayat peminjaman.</p>
            </div>
        <?php else: ?>
            <?php foreach (array_slice($user_riwayat, 0, 2) as $loan): ?>
                <div class="card-item">
                    <h4><?= htmlspecialchars($loan['nama_alat']) ?></h4>
                    <p><?= date('d/m/Y', strtotime($loan['tgl_pinjam'])) ?></p>
                    <span class="status <?= $loan['status'] === 'Selesai' ? 'status-success' : 'status-danger' ?>">
                        <?= htmlspecialchars($loan['status']) ?>
                    </span>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="section">
        <div class="section-title">
            <h3>Manajemen Denda</h3>
        </div>
        <div class="denda-card">
            <p>Total Denda</p>
            <h2>Rp <?= number_format($user_total_denda, 0, ',', '.') ?></h2>
            <p>Status : <?= $user_total_denda > 0 ? 'Belum Lunas' : 'Lunas' ?></p>
            <br>
            <span class="status <?= $user_total_denda > 0 ? 'status-danger' : 'status-success' ?>">
                <?= $user_total_denda > 0 ? 'Belum Lunas' : 'Lunas' ?>
            </span>
        </div>
    </div>
<?php endif; ?>

</div>


<?php include 'includes/footer.php'; ?>