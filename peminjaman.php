<?php
include 'includes/header.php';

$role = $_SESSION['role'] ?? 'user';
$user_id = $_SESSION['id'] ?? 0;

if ($role === 'admin' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['id'])) {
    $id = intval($_POST['id']);
    $action = $_POST['action'];
    $msg = 'Status berhasil diperbarui.';
    
    if ($action === 'approve' || $action === 'reject') {
        foreach ($_SESSION['dummy_verifikasi'] as $key => $pending) {
            if ($pending['id'] == $id) {
                if ($action === 'approve') {
                    $u_id = 1;
                    foreach ($_SESSION['dummy_users'] as $u) {
                        if ($u['nim'] === $pending['user_nim']) {
                            $u_id = $u['id'];
                            break;
                        }
                    }
                    
                    $kode_alat = 'UNI-001';
                    $alat_id = 1;
                    foreach ($_SESSION['dummy_alat'] as $a) {
                        if ($a['nama'] === $pending['alat_nama']) {
                            $kode_alat = $a['kode'];
                            $alat_id = $a['id'];
                            break;
                        }
                    }
                    
                    $_SESSION['dummy_peminjaman'][] = [
                        'id' => count($_SESSION['dummy_peminjaman']) + 1,
                        'user_id' => $u_id,
                        'user_nama' => $pending['user_nama'],
                        'user_nim' => $pending['user_nim'],
                        'alat_id' => $alat_id,
                        'kode_alat' => $kode_alat,
                        'nama_alat' => $pending['alat_nama'],
                        'tgl_pinjam' => $pending['tgl_pinjam'],
                        'tgl_kembali' => date('Y-m-d', strtotime($pending['tgl_pinjam'] . ' + 7 days')),
                        'jumlah' => $pending['jumlah'],
                        'denda' => 0,
                        'status' => 'Aktif',
                        'terlambat' => 0
                    ];
                    $msg = 'Peminjaman telah disetujui.';
                } else {
                    $msg = 'Peminjaman telah ditolak.';
                }
                
                unset($_SESSION['dummy_verifikasi'][$key]);
                $_SESSION['dummy_verifikasi'] = array_values($_SESSION['dummy_verifikasi']);
                break;
            }
        }
    } else {
        foreach ($_SESSION['dummy_peminjaman'] as &$loan) {
            if ($loan['id'] == $id) {
                if ($action === 'verifikasi_kembali') {
                    if ($loan['denda'] > 0) {
                        $loan['status'] = 'Belum Lunas';
                        $msg = 'Pengembalian diverifikasi. Status diubah menjadi Belum Lunas karena terdapat denda.';
                    } else {
                        $loan['status'] = 'Selesai';
                        $msg = 'Pengembalian diverifikasi. Pinjaman telah Selesai.';
                    }
                } elseif ($action === 'lunas') {
                    $loan['status'] = 'Selesai';
                    $msg = 'Pembayaran denda telah lunas. Pinjaman Selesai.';
                }
                break;
            }
        }
        unset($loan);
    }
    
    echo "<script>
        alert('$msg');
        window.location.href = 'peminjaman.php';
    </script>";
    exit;
}

$pending_list = [];
$loans = [];

if ($role === 'admin') {
    $pending_list = $_SESSION['dummy_verifikasi'] ?? [];
    $loans = $_SESSION['dummy_peminjaman'] ?? [];
} else {
    if (isset($_SESSION['dummy_verifikasi'])) {
        foreach ($_SESSION['dummy_verifikasi'] as $pending) {
            if ($pending['user_nama'] === $_SESSION['nama']) {
                $pending_list[] = $pending;
            }
        }
    }
    if (isset($_SESSION['dummy_peminjaman'])) {
        foreach ($_SESSION['dummy_peminjaman'] as $loan) {
            if ($loan['user_id'] == $user_id) {
                $loans[] = $loan;
            }
        }
    }
}
?>

<div class="riwayat-container" style="padding: 20px 25px;">

    <h3 style="margin-bottom: 15px; font-weight: 800; color: #0f172a;">Menunggu Verifikasi</h3>
    <?php if (empty($pending_list)): ?>
        <div class="riwayat-card" style="text-align: center; padding: 30px;">
            <p style="color: #64748b;">Tidak ada pengajuan peminjaman baru.</p>
        </div>
    <?php else: ?>
        <div class="list-ke-bawah-container">
            <div class="list-header-row">
                <div>Gambar</div>
                <div>Nama Alat</div>
                <div>Peminjam (NIM)</div>
                <div>Jumlah</div>
                <div>Status / Aksi</div>
            </div>
            
            <?php foreach ($pending_list as $pending): ?>
                <?php $js_pending = json_encode($pending); ?>
                <div class="list-item-row" style="<?= $role === 'admin' ? 'cursor: pointer;' : '' ?>" <?= $role === 'admin' ? "onclick='openVerifikasiModal(" . htmlspecialchars($js_pending, ENT_QUOTES, "UTF-8") . ")'" : "" ?>>
                    <div>
                        <img src="<?= htmlspecialchars($pending['img']) ?>" style="width: 50px; height: 50px; object-fit: cover; border-radius: 10px; border: 1px solid #e2e8f0;">
                    </div>
                    <div class="data-text" style="font-weight: 700; color: #0f172a;"><?= htmlspecialchars($pending['alat_nama']) ?></div>
                    <div class="data-text"><?= htmlspecialchars($pending['user_nama']) ?> <span style="color: #64748b; font-size: 12px; display: block;">NIM: <?= htmlspecialchars($pending['user_nim']) ?></span></div>
                    <div class="data-text"><?= htmlspecialchars($pending['jumlah']) ?> Unit</div>
                    <div>
                        <?php if ($role === 'admin'): ?>
                            <button class="btn-detail-table">Lihat & Verifikasi</button>
                        <?php else: ?>
                            <span class="badge badge-warning">Diproses Admin</span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <h3 style="margin-top: 40px; margin-bottom: 15px; font-weight: 800; color: #0f172a;">Pinjaman Aktif & Selesai</h3>
    <?php if (empty($loans)): ?>
        <div class="riwayat-card" style="text-align: center; padding: 30px;">
            <p style="color: #64748b;">Tidak ada data peminjaman.</p>
        </div>
    <?php else: ?>
        <div class="list-ke-bawah-container">
            <div class="list-header-row">
                <div>Kode</div>
                <div>Nama Alat</div>
                <div>Peminjam (NIM)</div>
                <div>Keterangan</div>
                <div>Status</div>
            </div>

            <?php foreach ($loans as $loan): ?>
                <?php $js_loan = json_encode($loan); ?>
                <div class="list-item-row" style="<?= $role === 'admin' ? 'cursor: pointer;' : '' ?>" <?= $role === 'admin' ? "onclick='openPeminjamanModal(" . htmlspecialchars($js_loan, ENT_QUOTES, "UTF-8") . ")'" : "" ?>>
                    <div class="data-text" style="font-family: monospace; font-weight: 700; color: #64748b;"><?= htmlspecialchars($loan['kode_alat']) ?></div>
                    <div class="data-text" style="font-weight: 700; color: #0f172a;"><?= htmlspecialchars($loan['nama_alat']) ?></div>
                    <div class="data-text"><?= htmlspecialchars($loan['user_nama']) ?> <span style="color: #64748b; font-size: 12px; display: block;">NIM: <?= htmlspecialchars($loan['user_nim']) ?></span></div>
                    <div class="data-text">
                        <?php if ($loan['status'] === 'Aktif'): ?>
                            <span style="color: #ff9800; font-weight: 600;">Terlambat: <?= htmlspecialchars($loan['terlambat']) ?> Hari</span>
                        <?php elseif ($loan['status'] === 'Belum Lunas'): ?>
                            <span style="color: #ef4444; font-weight: 600;">Denda: Rp <?= number_format($loan['denda'], 0, ',', '.') ?></span>
                        <?php else: ?>
                            <span style="color: #10b981;">Waktu Selesai</span>
                        <?php endif; ?>
                    </div>
                    <div>
                        <?php
                        $badge_class = 'badge-success';
                        if ($loan['status'] === 'Aktif') {
                            $badge_class = 'badge-warning';
                        } elseif ($loan['status'] === 'Belum Lunas') {
                            $badge_class = 'badge-danger';
                        }
                        ?>
                        <span class="badge <?= $badge_class ?>"><?= htmlspecialchars($loan['status']) ?></span>
                        
                        <?php if ($loan['status'] !== 'Selesai' && $role === 'admin'): ?>
                            <button class="btn-detail-table" style="margin-top: 5px; display: block;">Detail</button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

<div id="verifikasiModal" class="modal-overlay">
    <div class="modal-content detail-card">
        <span class="close-btn" onclick="closeVerifikasiModal()">&times;</span>
        <img id="v-modal-img" src="" class="detail-img">
        <div class="detail-body">
            <h2 id="v-modal-nama-alat"></h2>
            <div class="info-box" style="margin-bottom: 20px;">
                <p><b>Mahasiswa :</b> <span id="v-modal-mahasiswa"></span></p>
                <p><b>NIM :</b> <span id="v-modal-nim"></span></p>
                <p><b>Tanggal Pinjam :</b> <span id="v-modal-tgl"></span></p>
                <p><b>Jumlah :</b> <span id="v-modal-jumlah"></span> Unit</p>
            </div>
            <form method="POST" action="peminjaman.php">
                <input type="hidden" name="id" id="v-modal-id">
                <div style="display: flex; gap: 10px;">
                    <button type="submit" name="action" value="approve" class="btn-primary" style="flex: 1; border: none; cursor: pointer;">
                        Verifikasi Pinjaman
                    </button>
                    <button type="submit" name="action" value="reject" class="btn-logout" style="flex: 1; border: none; cursor: pointer; margin-top: 0;">
                        Tolak
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="peminjamanModal" class="modal-overlay">
    <div class="modal-content detail-card">
        <span class="close-btn" onclick="closePeminjamanModal()">&times;</span>
        <div class="detail-body">
            <h2 id="p-modal-title" style="margin-bottom: 20px;">Detail Peminjaman</h2>
            <div class="info-box" style="margin-bottom: 20px;">
                <p><b>Alat :</b> <span id="p-modal-alat"></span></p>
                <p><b>Kode Alat :</b> <span id="p-modal-kode"></span></p>
                <p><b>Peminjam :</b> <span id="p-modal-user"></span> (<span id="p-modal-nim"></span>)</p>
                <p><b>Tanggal Pinjam :</b> <span id="p-modal-tgl"></span></p>
                <p><b>Jumlah :</b> <span id="p-modal-jumlah"></span> Unit</p>
                <hr style="margin: 10px 0; border: none; border-top: 1px solid #eee;">
                <p><b>Status :</b> <span id="p-modal-status" style="font-weight: 800;"></span></p>
                <p id="p-modal-denda-container" style="display: none; color: #ff4d4d;">
                    <b>Total Denda :</b> Rp <span id="p-modal-denda"></span>
                </p>
            </div>
            <form method="POST" action="peminjaman.php" id="p-modal-form" style="display: none;">
                <input type="hidden" name="id" id="p-modal-id">
                <button type="submit" name="action" id="p-modal-action-btn" value="" class="btn-primary" style="width: 100%; border: none; cursor: pointer;">
                    Tindakan
                </button>
            </form>
        </div>
    </div>
</div>

<script>
function formatDate(dateStr) {
    const d = new Date(dateStr);
    const day = ('0' + d.getDate()).slice(-2);
    const month = ('0' + (d.getMonth() + 1)).slice(-2);
    return day + '/' + month + '/' + d.getFullYear();
}

function formatRupiah(number) {
    return new Intl.NumberFormat('id-ID').format(number);
}

function openVerifikasiModal(item) {
    document.getElementById('v-modal-id').value = item.id;
    document.getElementById('v-modal-img').src = item.img;
    document.getElementById('v-modal-nama-alat').innerText = item.alat_nama;
    document.getElementById('v-modal-mahasiswa').innerText = item.user_nama;
    document.getElementById('v-modal-nim').innerText = item.user_nim;
    document.getElementById('v-modal-tgl').innerText = formatDate(item.tgl_pinjam);
    document.getElementById('v-modal-jumlah').innerText = item.jumlah;
    
    document.getElementById('verifikasiModal').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeVerifikasiModal() {
    document.getElementById('verifikasiModal').classList.remove('show');
    document.body.style.overflow = '';
}

document.getElementById('verifikasiModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeVerifikasiModal();
    }
});

function openPeminjamanModal(item) {
    document.getElementById('p-modal-id').value = item.id;
    document.getElementById('p-modal-alat').innerText = item.nama_alat;
    document.getElementById('p-modal-kode').innerText = item.kode_alat;
    document.getElementById('p-modal-user').innerText = item.user_nama;
    document.getElementById('p-modal-nim').innerText = item.user_nim;
    document.getElementById('p-modal-tgl').innerText = formatDate(item.tgl_pinjam);
    document.getElementById('p-modal-jumlah').innerText = item.jumlah;
    document.getElementById('p-modal-status').innerText = item.status;
    
    const dendaContainer = document.getElementById('p-modal-denda-container');
    if (item.denda > 0) {
        document.getElementById('p-modal-denda').innerText = formatRupiah(item.denda);
        dendaContainer.style.display = 'block';
    } else {
        dendaContainer.style.display = 'none';
    }
    
    const form = document.getElementById('p-modal-form');
    const actionBtn = document.getElementById('p-modal-action-btn');
    
    if (item.status === 'Aktif') {
        form.style.display = 'block';
        actionBtn.value = 'verifikasi_kembali';
        actionBtn.innerText = 'Verifikasi Pengembalian';
        actionBtn.className = 'btn-primary';
    } else if (item.status === 'Belum Lunas') {
        form.style.display = 'block';
        actionBtn.value = 'lunas';
        actionBtn.innerText = 'Lunas';
        actionBtn.className = 'btn-pinjam';
    } else {
        form.style.display = 'none';
    }
    
    document.getElementById('peminjamanModal').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closePeminjamanModal() {
    document.getElementById('peminjamanModal').classList.remove('show');
    document.body.style.overflow = '';
}

document.getElementById('peminjamanModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closePeminjamanModal();
    }
});
</script>

<?php include 'includes/footer.php'; ?>