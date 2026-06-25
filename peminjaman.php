<?php
include 'includes/header.php';

$role = $_SESSION['role'] ?? 'user';
$user_id = $_SESSION['id'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['id'])) {
    $id = intval($_POST['id']);
    $action = $_POST['action'];
    $msg = 'Status berhasil diperbarui.';

    if ($role === 'admin' && ($action === 'approve' || $action === 'reject')) {
        $status = ($action === 'approve') ? 'Disetujui' : 'Ditolak';
        $catatan = !empty($_POST['catatan_pinjaman']) ? $_POST['catatan_pinjaman'] : "";

        $response = call_api('PUT', '/api/peminjaman/persetujuan', [
            'id' => $id,
            'status' => $status,
            'catatan_pinjaman' => $catatan
        ]);

        if (isset($response['http_code']) && $response['http_code'] >= 200 && $response['http_code'] < 300) {
            $msg = ($action === 'approve') ? 'Peminjaman telah disetujui.' : 'Peminjaman telah ditolak.';
        } else {
            $msg = 'Gagal: ' . ($response['message'] ?? 'Unknown error');
        }
    } elseif ($action === 'verifikasi_kembali') {
        // Asumsi kondisi baik secara default jika hanya klik verifikasi
        $kondisi = $_POST['kondisi_alat'] ?? 'baik';
        $catatan = $_POST['catatan_pengembalian'] ?? 'Dikembalikan';
        $jumlah_kembali = intval($_POST['jumlah_kembali'] ?? 1); // fallback

        $response = call_api('POST', '/api/pengembalian', [
            'peminjaman_id' => $id,
            'tanggal_kembali_aktual' => date('Y-m-d'),
            'kondisi_alat' => $kondisi,
            'jumlah_kembali' => $jumlah_kembali,
            'catatan_pengembalian' => $catatan
        ]);

        if (isset($response['http_code']) && $response['http_code'] >= 200 && $response['http_code'] < 300) {
            $msg = 'Pengembalian berhasil diverifikasi.';
        } else {
            $msg = 'Gagal: ' . ($response['message'] ?? 'Unknown error');
        }
    } elseif ($action === 'lunas' && $role === 'admin') {
        $response = call_api('PUT', '/api/admin/denda/lunas', [
            'id' => $id // Ini ID denda, diasumsikan form mengirim ID denda
        ]);

        if (isset($response['http_code']) && $response['http_code'] >= 200 && $response['http_code'] < 300) {
            $msg = 'Pembayaran denda telah lunas.';
        } else {
            $msg = 'Gagal: ' . ($response['message'] ?? 'Unknown error');
        }
    }

    $_SESSION['flash_message'] = $msg;
    echo "<script>window.location.href='peminjaman.php';</script>";
    exit;
}

$pending_list = [];
$loans = [];

// Get Peminjaman from API
$res_pinjaman = call_api('GET', '/api/peminjaman/riwayat');
$all_loans = $res_pinjaman['data'] ?? [];

// Get Alat from API untuk referensi Kode dan Foto
$res_alat = call_api('GET', '/api/alat');
$all_alat = $res_alat['data'] ?? [];
$alat_dict = [];
foreach ($all_alat as $a) {
    $alat_dict[$a['id']] = $a;
}

// Get Denda from API
$denda_endpoint = ($role === 'admin') ? '/api/admin/denda' : '/api/user/denda';
$res_denda = call_api('GET', $denda_endpoint);
$all_denda = $res_denda['data'] ?? [];
$denda_dict = [];
foreach ($all_denda as $d) {
    $denda_dict[$d['peminjaman_id']] = $d;
}

foreach ($all_loans as $loan) {
    // Hitung Keterlambatan
    $tgl_kembali = $loan['tanggal_kembali_rencana'] ?? date('Y-m-d');
    $terlambat = 0;
    if (($loan['status'] ?? '') !== 'Dikembalikan' && ($loan['status'] ?? '') !== 'Ditolak') {
        if (strtotime($tgl_kembali) < strtotime(date('Y-m-d'))) {
            $diff = strtotime(date('Y-m-d')) - strtotime($tgl_kembali);
            $terlambat = floor($diff / (60 * 60 * 24));
        }
    }

    $alat_info = $alat_dict[$loan['alat_id']] ?? [];
    $kode_alat = $alat_info['kode_alat'] ?? ($loan['kode_alat'] ?? '-');
    $foto = $alat_info['foto'] ?? ($loan['foto'] ?? 'https://images.unsplash.com/photo-1587829741301-dc798b83add3?w=150&q=70');

    if (strpos($foto, '|') !== false) {
        $foto_parts = explode('|', $foto);
        $foto = $foto_parts[0];
    }

    $mapped_loan = [
        'id' => $loan['id'],
        'user_nama' => $loan['nama_mahasiswa'] ?? $loan['nama_user'] ?? $loan['user_nama'] ?? 'User',
        'user_nim' => $loan['nim_nip'] ?? $loan['user_nim'] ?? '-',
        'alat_nama' => $loan['nama_alat'] ?? 'Alat',
        'kode_alat' => $kode_alat,
        'spesifikasi' => $alat_info['spesifikasi'] ?? '-',
        'img' => $foto,
        'tgl_pinjam' => $loan['tanggal_pinjam'] ?? date('Y-m-d'),
        'tgl_kembali' => $tgl_kembali,
        'jumlah' => $loan['jumlah'] ?? 1,
        'status' => $loan['status'] ?? 'Menunggu',
        'api_status' => $loan['status'] ?? 'Menunggu',
        'denda' => 0, // Akan di-update jika digabung dengan API denda
        'terlambat' => $terlambat,
        'catatan_pinjaman' => !empty($loan['catatan_pinjaman']) ? $loan['catatan_pinjaman'] : '-',
        'catatan_kembali' => !empty($loan['catatan_pengembalian']) ? $loan['catatan_pengembalian'] : '-',
        'catatan' => !empty($loan['catatan_pengembalian']) ? $loan['catatan_pengembalian'] : (!empty($loan['catatan_pinjaman']) ? $loan['catatan_pinjaman'] : '-')
    ];

    $denda_info = $denda_dict[$loan['id']] ?? null;
    if ($denda_info) {
        $status_bayar = $denda_info['status_bayar'] ?? $denda_info['status_pembayaran'] ?? $denda_info['status'] ?? '';
        if ($status_bayar === 'Belum Lunas') {
            $mapped_loan['status'] = 'Belum Lunas';
            $mapped_loan['denda'] = floatval($denda_info['jumlah_denda'] ?? $denda_info['denda'] ?? 0);
            $mapped_loan['denda_id'] = $denda_info['id'] ?? null;
        } elseif ($status_bayar === 'Lunas') {
            $mapped_loan['status'] = 'Lunas';
            $mapped_loan['denda'] = floatval($denda_info['jumlah_denda'] ?? $denda_info['denda'] ?? 0);
            $mapped_loan['denda_id'] = $denda_info['id'] ?? null;
        }
    } elseif ($mapped_loan['terlambat'] > 0 && ($mapped_loan['status'] === 'Dipinjam' || $mapped_loan['status'] === 'Disetujui')) {
        $mapped_loan['status'] = 'Belum Lunas';
        $mapped_loan['denda'] = $mapped_loan['terlambat'] * 10000;
    }

    if ($mapped_loan['status'] === 'Menunggu') {
        if ($role === 'admin' || $loan['user_id'] == $user_id) {
            $pending_list[] = $mapped_loan;
        }
    } else {
        if ($role === 'admin' || $loan['user_id'] == $user_id) {
            // Mapping status API to UI status
            if ($mapped_loan['status'] === 'Dipinjam' || $mapped_loan['status'] === 'Disetujui') {
                $mapped_loan['status'] = 'Aktif';
            } elseif ($mapped_loan['status'] === 'Dikembalikan') {
                $mapped_loan['status'] = 'Selesai';
            }
            $loans[] = $mapped_loan;
        }
    }
}
?>

<div class="riwayat-container" style="padding: 20px 25px;">

    <?php
    // Tampilkan flash message jika ada, menggunakan SweetAlert2
    if (isset($_SESSION['flash_message'])): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var msg = <?= json_encode($_SESSION['flash_message']) ?>;
                var isError = msg.indexOf('Gagal') !== -1;
                Swal.fire({
                    title: isError ? 'Gagal' : 'Berhasil',
                    text: msg,
                    icon: isError ? 'error' : 'success',
                    confirmButtonColor: '#7dd3fc'
                });
            });
        </script>
        <?php unset($_SESSION['flash_message']); ?>
    <?php endif; ?>

    <div style="display: flex; justify-content: flex-end; align-items: center; margin-bottom: 25px;">
        <div style="position: relative; width: 320px;">
            <i class="fa-solid fa-search"
                style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #94a3b8; pointer-events: none;"></i>
            <input type="text" id="searchInputRiwayat" oninput="filterRiwayat()" placeholder="Cari ..."
                style="width: 100%; padding: 12px 15px 12px 40px; border-radius: 12px; border: 1px solid #cbd5e1; font-size: 14px; outline: none; transition: 0.2s ease;">
        </div>
    </div>

    <h3 style="margin-bottom: 15px; font-weight: 800; color: #0f172a;">Menunggu Verifikasi</h3>
    <?php if (empty($pending_list)): ?>
        <div class="riwayat-card" style="text-align: center; padding: 30px;">
            <p style="color: #64748b;">Tidak ada pengajuan peminjaman baru.</p>
        </div>
    <?php else: ?>
        <div class="list-ke-bawah-container">
            <div class="list-header-row">
                <div>Alat</div>
                <div></div>
                <div>Peminjam</div>
                <div>Tanggal</div>
                <div>Jumlah</div>
                <div>Status / Aksi</div>
            </div>

            <?php foreach ($pending_list as $pending): ?>
                <?php $js_pending = json_encode($pending); ?>
                <div class="list-item-row" style="<?= $role === 'admin' ? 'cursor: pointer;' : '' ?>" <?= $role === 'admin' ? "onclick='openVerifikasiModal(" . htmlspecialchars($js_pending, ENT_QUOTES, "UTF-8") . ")'" : "" ?>>
                    <div>
                        <img src="<?= htmlspecialchars($pending['img']) ?>"
                            style="width: 50px; height: 50px; object-fit: cover; border-radius: 10px; border: 1px solid #e2e8f0;">
                    </div>
                    <div class="data-text" style="font-weight: 700; color: #0f172a;">
                        <div style="font-weight: 700; color: #0f172a;"><?= htmlspecialchars($pending['alat_nama']) ?></div>
                        <div style="font-family: monospace; font-weight: 700; color: #64748b; font-size: 12px;">
                            <?= htmlspecialchars($pending['kode_alat']) ?>
                        </div>
                    </div>
                    <div class="data-text"><?= htmlspecialchars($pending['user_nama']) ?></div>
                    <div class="data-text" style="font-size: 12px;">
                        <div style="color: #64748b;">Pinjam: <span
                                style="color:#0f172a; font-weight:600;"><?= date('d/m/Y', strtotime($pending['tgl_pinjam'])) ?></span>
                        </div>
                        <div style="color: #64748b;">Kembali: <span
                                style="color:#0f172a; font-weight:600;"><?= date('d/m/Y', strtotime($pending['tgl_kembali'])) ?></span>
                        </div>
                    </div>
                    <div class="data-text"><?= htmlspecialchars($pending['jumlah']) ?> Unit</div>
                    <div>
                        <?php if ($role === 'admin'): ?>
                            <button class="btn-warning-table">Belum Verifikasi</button>
                        <?php else: ?>
                            <span class="badge badge-warning">Menunggu</span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
            <div class="no-data-msg"
                style="display: none; text-align: center; padding: 20px; color: #64748b; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.01), 0 4px 12px rgba(15,23,42,0.03); margin-top: 10px;">
                Data pencarian tidak ditemukan.</div>
        </div>
    <?php endif; ?>

    <h3 style="margin-top: 40px; margin-bottom: 15px; font-weight: 800; color: #0f172a;">Peminjaman Aktif & Selesai</h3>
    <?php if (empty($loans)): ?>
        <div class="riwayat-card" style="text-align: center; padding: 30px;">
            <p style="color: #64748b;">Tidak ada data peminjaman.</p>
        </div>
    <?php else: ?>
        <div class="list-ke-bawah-container" id="riwayat-list-container">
            <div class="list-header-row">
                <div>Alat</div>
                <div></div>
                <div>Peminjam</div>
                <div>Tanggal</div>
                <div>Keterangan</div>
                <div>Status</div>
            </div>

            <?php foreach ($loans as $loan): ?>
                <?php $js_loan = json_encode($loan); ?>
                <div class="list-item-row" style="<?= $role === 'admin' ? 'cursor: pointer;' : '' ?>" <?= $role === 'admin' ? "onclick='openPeminjamanModal(" . htmlspecialchars($js_loan, ENT_QUOTES, "UTF-8") . ")'" : "" ?>>
                    <div>
                        <img src="<?= htmlspecialchars($loan['img']) ?>"
                            style="width: 50px; height: 50px; object-fit: cover; border-radius: 10px; border: 1px solid #e2e8f0;">
                    </div>
                    <div class="data-text">
                        <div style="font-weight: 700; color: #0f172a;"><?= htmlspecialchars($loan['alat_nama']) ?></div>
                        <div style="font-family: monospace; font-weight: 700; color: #64748b; font-size: 12px;">
                            <?= htmlspecialchars($loan['kode_alat']) ?>
                        </div>
                    </div>
                    <div class="data-text"><?= htmlspecialchars($loan['user_nama']) ?></div>
                    <div class="data-text" style="font-size: 12px;">
                        <div style="color: #64748b;">Pinjam: <span
                                style="color:#0f172a; font-weight:600;"><?= date('d/m/Y', strtotime($loan['tgl_pinjam'])) ?></span>
                        </div>
                        <div style="color: #64748b;">Kembali: <span
                                style="color:#0f172a; font-weight:600;"><?= date('d/m/Y', strtotime($loan['tgl_kembali'])) ?></span>
                        </div>
                    </div>
                    <div class="data-text">
                        <?php if ($loan['status'] === 'Aktif'): ?>
                            <span style="color: #10b981; font-weight: 600;">Berlangsung</span>
                        <?php elseif ($loan['status'] === 'Belum Lunas'): ?>
                            <?php if ($loan['terlambat'] > 0): ?>
                                <span style="color: #ff9800; font-weight: 600;">Terlambat <?= htmlspecialchars($loan['terlambat']) ?>
                                    Hari</span><br>
                            <?php endif; ?>
                            <span style="color: #ef4444; font-weight: 600;">Denda Rp.
                                <?= number_format($loan['denda'], 0, ',', '.') ?></span>
                        <?php elseif ($loan['status'] === 'Ditolak'): ?>
                            <span
                                style="color: #ef4444; font-weight: 600; font-size: 12px;"><?= htmlspecialchars($loan['catatan']) ?></span>
                        <?php elseif ($loan['status'] === 'Lunas'): ?>
                            <span style="color: #10b981; font-weight: 600;">Lunas <br><span
                                    style="font-size:11px; color:#64748b;">Denda Rp. <?= number_format($loan['denda'], 0, ',', '.') ?></span></span>
                        <?php else: ?>
                            <span style="color: #10b981; font-weight: 600;">Selesai <br><span
                                    style="font-size:11px; color:#64748b;"><?= htmlspecialchars($loan['catatan']) ?></span></span>
                        <?php endif; ?>
                    </div>
                    <div style="display: flex; gap: 10px; align-items: center; justify-content: space-between;">
                        <?php
                        $badge_class = 'badge-success';
                        if ($loan['status'] === 'Aktif') {
                            $badge_class = 'badge-active';
                        } elseif ($loan['status'] === 'Belum Lunas' || $loan['status'] === 'Ditolak') {
                            $badge_class = 'badge-danger';
                        }
                        ?>
                        <span class="badge <?= $badge_class ?>"><?= htmlspecialchars($loan['status']) ?></span>

                        <button class="btn-detail-table">Detail</button>
                    </div>
                </div>
            <?php endforeach; ?>
            <div class="no-data-msg"
                style="display: none; text-align: center; padding: 20px; color: #64748b; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.01), 0 4px 12px rgba(15,23,42,0.03); margin-top: 10px;">
                Data pencarian tidak ditemukan.</div>
        </div>
    <?php endif; ?>

</div>

<div id="verifikasiModal" class="modal-overlay">
    <div class="modal-content detail-card">
        <span class="close-btn" onclick="closeVerifikasiModal()">&times;</span>
        <img id="v-modal-img" src="" class="detail-img">
        <div class="detail-body">
            <h2 id="v-modal-nama-alat" style="margin-bottom: 5px;"></h2>
            <p id="v-modal-spesifikasi" style="font-size: 13px; color: #64748b; margin-bottom: 20px;"></p>
            <div class="info-box" style="margin-bottom: 20px;">
                <div style="display: flex; margin-bottom: 8px; align-items: flex-start;">
                    <div style="width: 130px; font-weight: bold;">Peminjam</div>
                    <div style="margin-right: 10px;">:</div>
                    <div style="flex: 1; text-align: right;"><span id="v-modal-mahasiswa"></span></div>
                </div>
                <div style="display: flex; margin-bottom: 8px; align-items: flex-start;">
                    <div style="width: 130px; font-weight: bold;">Tanggal Pinjam</div>
                    <div style="margin-right: 10px;">:</div>
                    <div style="flex: 1; text-align: right;"><span id="v-modal-tgl-pinjam"></span></div>
                </div>
                <div style="display: flex; margin-bottom: 8px; align-items: flex-start;">
                    <div style="width: 130px; font-weight: bold;">Tanggal Kembali</div>
                    <div style="margin-right: 10px;">:</div>
                    <div style="flex: 1; text-align: right;"><span id="v-modal-tgl-kembali"></span></div>
                </div>
                <div style="display: flex; margin-bottom: 8px; align-items: flex-start;">
                    <div style="width: 130px; font-weight: bold;">Jumlah</div>
                    <div style="margin-right: 10px;">:</div>
                    <div style="flex: 1; text-align: right;"><span id="v-modal-jumlah"></span> Unit</div>
                </div>
                <div style="display: flex; margin-bottom: 8px; align-items: flex-start;">
                    <div style="width: 130px; font-weight: bold;">Catatan Pinjam</div>
                    <div style="margin-right: 10px;">:</div>
                    <div style="flex: 1; text-align: right;"><span id="v-modal-catatan-pinjam"></span></div>
                </div>
            </div>
            <form method="POST" action="peminjaman.php">
                <input type="hidden" name="id" id="v-modal-id">
                <div style="margin-bottom: 15px; text-align: left;">
                    <label
                        style="display: block; margin-bottom: 5px; font-weight: 700; font-size: 14px; color: #0f172a;">Catatan
                        (Opsional)</label>
                    <textarea name="catatan_pinjaman" rows="2"
                        style="width: 100%; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px; font-family: inherit; resize: vertical;"
                        placeholder="Tambahkan catatan jika diperlukan..."></textarea>
                </div>
                <div style="display: flex; gap: 10px;">
                    <button type="submit" name="action" value="approve" class="btn-primary"
                        style="flex: 1; border: none; cursor: pointer; padding: 12px; border-radius: 10px; font-weight: 700; background-color: #7dd3fc; color: #0f172a;">
                        Verifikasi Pinjaman
                    </button>
                    <button type="submit" name="action" value="reject" class="btn-logout"
                        style="flex: 1; border: none; cursor: pointer; padding: 12px; border-radius: 10px; font-weight: 700; background-color: #ef4444; color: #ffffff; margin-top: 0;">
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
        <img id="p-modal-img" src="" class="detail-img">
        <div class="detail-body">
            <h2 id="p-modal-nama-alat" style="margin-bottom: 5px;"></h2>
            <p id="p-modal-spesifikasi" style="font-size: 13px; color: #64748b; margin-bottom: 20px;"></p>
            <div class="info-box" style="margin-bottom: 20px;">
                <div style="display: flex; margin-bottom: 8px; align-items: flex-start;">
                    <div style="width: 130px; font-weight: bold;">Peminjam</div>
                    <div style="margin-right: 10px;">:</div>
                    <div style="flex: 1; text-align: right;"><span id="p-modal-user"></span></div>
                </div>
                <div style="display: flex; margin-bottom: 8px; align-items: flex-start;">
                    <div style="width: 130px; font-weight: bold;">Tanggal Pinjam</div>
                    <div style="margin-right: 10px;">:</div>
                    <div style="flex: 1; text-align: right;"><span id="p-modal-tgl-pinjam"></span></div>
                </div>
                <div style="display: flex; margin-bottom: 8px; align-items: flex-start;">
                    <div style="width: 130px; font-weight: bold;">Tanggal Kembali</div>
                    <div style="margin-right: 10px;">:</div>
                    <div style="flex: 1; text-align: right;"><span id="p-modal-tgl-kembali"></span></div>
                </div>
                <div style="display: flex; margin-bottom: 8px; align-items: flex-start;">
                    <div style="width: 130px; font-weight: bold;">Jumlah Pinjam</div>
                    <div style="margin-right: 10px;">:</div>
                    <div style="flex: 1; text-align: right;"><span id="p-modal-jumlah"></span> Unit</div>
                </div>
                <div style="display: flex; margin-bottom: 8px; align-items: flex-start;">
                    <div style="width: 130px; font-weight: bold;">Catatan Pinjam</div>
                    <div style="margin-right: 10px;">:</div>
                    <div style="flex: 1; text-align: right;"><span id="p-modal-catatan-pinjam"></span></div>
                </div>
                <div style="display: flex; margin-bottom: 8px; align-items: flex-start;">
                    <div style="width: 130px; font-weight: bold;">Catatan Kembali</div>
                    <div style="margin-right: 10px;">:</div>
                    <div style="flex: 1; text-align: right;"><span id="p-modal-catatan-kembali"></span></div>
                </div>
                <hr style="margin: 10px 0; border: none; border-top: 1px solid #eee;">
                <div style="display: flex; margin-bottom: 8px; align-items: flex-start;">
                    <div style="width: 130px; font-weight: bold;">Status</div>
                    <div style="margin-right: 10px;">:</div>
                    <div style="flex: 1; text-align: right;"><span id="p-modal-status" style="font-weight: 800;"></span>
                    </div>
                </div>
                <div id="p-modal-denda-container"
                    style="display: none; margin-bottom: 8px; align-items: flex-start; color: #ef4444;">
                    <div style="width: 130px; font-weight: bold;">Total Denda</div>
                    <div style="margin-right: 10px;">:</div>
                    <div style="flex: 1; text-align: right; font-weight: bold;">Rp <span id="p-modal-denda"></span>
                    </div>
                </div>
            </div>
            <form method="POST" action="peminjaman.php" id="p-modal-form" style="display: none;">
                <input type="hidden" name="id" id="p-modal-id">

                <div id="p-modal-pengembalian-fields" style="display: none;">
                    <div style="margin-bottom: 15px; text-align: left;">
                        <label
                            style="display: block; margin-bottom: 5px; font-weight: 700; font-size: 14px; color: #0f172a;">Kondisi
                            Alat</label>
                        <select name="kondisi_alat" required
                            style="width: 100%; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px; font-family: inherit;">
                            <option value="baik">Baik</option>
                            <option value="rusak">Rusak</option>
                            <option value="hilang">Hilang</option>
                        </select>
                    </div>

                    <div style="margin-bottom: 15px; text-align: left;">
                        <label
                            style="display: block; margin-bottom: 5px; font-weight: 700; font-size: 14px; color: #0f172a;">Jumlah
                            Kembali</label>
                        <input type="number" name="jumlah_kembali" id="p-modal-input-jumlah" required min="0"
                            style="width: 100%; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px; font-family: inherit;">
                    </div>

                    <div style="margin-bottom: 15px; text-align: left;">
                        <label
                            style="display: block; margin-bottom: 5px; font-weight: 700; font-size: 14px; color: #0f172a;">Catatan
                            Pengembalian (Opsional)</label>
                        <textarea name="catatan_pengembalian" rows="2"
                            style="width: 100%; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px; font-family: inherit; resize: vertical;"
                            placeholder="Tambahkan catatan..."></textarea>
                    </div>
                </div>

                <button type="submit" name="action" id="p-modal-action-btn" value="" class="btn-primary"
                    style="width: 100%; border: none; cursor: pointer; padding: 12px; border-radius: 10px; font-weight: 700; background-color: #7dd3fc; color: #0f172a;">
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
        document.getElementById('v-modal-spesifikasi').innerText = item.spesifikasi;
        document.getElementById('v-modal-mahasiswa').innerText = item.user_nama;
        document.getElementById('v-modal-tgl-pinjam').innerText = formatDate(item.tgl_pinjam);
        document.getElementById('v-modal-tgl-kembali').innerText = formatDate(item.tgl_kembali);
        document.getElementById('v-modal-jumlah').innerText = item.jumlah;
        document.getElementById('v-modal-catatan-pinjam').innerText = item.catatan_pinjaman || '-';

        document.getElementById('verifikasiModal').classList.add('show');
        document.body.style.overflow = 'hidden';
    }

    function closeVerifikasiModal() {
        document.getElementById('verifikasiModal').classList.remove('show');
        document.body.style.overflow = '';
    }

    document.getElementById('verifikasiModal').addEventListener('click', function (e) {
        if (e.target === this) {
            closeVerifikasiModal();
        }
    });

    function openPeminjamanModal(item) {
        document.getElementById('p-modal-id').value = item.denda_id ? item.denda_id : item.id;
        document.getElementById('p-modal-img').src = item.img;
        document.getElementById('p-modal-nama-alat').innerText = item.alat_nama;
        document.getElementById('p-modal-spesifikasi').innerText = item.spesifikasi;
        document.getElementById('p-modal-user').innerText = item.user_nama;
        document.getElementById('p-modal-tgl-pinjam').innerText = formatDate(item.tgl_pinjam);
        document.getElementById('p-modal-tgl-kembali').innerText = formatDate(item.tgl_kembali);
        document.getElementById('p-modal-jumlah').innerText = item.jumlah;
        document.getElementById('p-modal-catatan-pinjam').innerText = item.catatan_pinjaman || '-';
        document.getElementById('p-modal-catatan-kembali').innerText = item.catatan_kembali || '-';
        document.getElementById('p-modal-status').innerText = item.status;
        document.getElementById('p-modal-input-jumlah').max = item.jumlah;
        document.getElementById('p-modal-input-jumlah').value = item.jumlah;

        const dendaContainer = document.getElementById('p-modal-denda-container');
        if (item.denda > 0) {
            document.getElementById('p-modal-denda').innerText = formatRupiah(item.denda);
            dendaContainer.style.display = 'flex';
        } else {
            dendaContainer.style.display = 'none';
        }

        const form = document.getElementById('p-modal-form');
        const actionBtn = document.getElementById('p-modal-action-btn');
        const pengembalianFields = document.getElementById('p-modal-pengembalian-fields');

        if (item.status === 'Aktif' || ((item.status === 'Belum Lunas' || item.status === 'Aktif') && (item.api_status === 'Dipinjam' || item.api_status === 'Disetujui'))) {
            form.style.display = 'block';
            pengembalianFields.style.display = 'block';
            actionBtn.value = 'verifikasi_kembali';
            actionBtn.innerText = 'Verifikasi Pengembalian';
            actionBtn.className = 'btn-primary';
        } else if (item.status === 'Belum Lunas') {
            form.style.display = 'block';
            pengembalianFields.style.display = 'none';
            actionBtn.value = 'lunas';
            actionBtn.innerText = 'Lunas';
            actionBtn.className = 'btn-pinjam';
        } else {
            form.style.display = 'none';
            pengembalianFields.style.display = 'none';
        }

        document.getElementById('peminjamanModal').classList.add('show');
        document.body.style.overflow = 'hidden';
    }

    function closePeminjamanModal() {
        document.getElementById('peminjamanModal').classList.remove('show');
        document.body.style.overflow = '';
    }

    document.getElementById('peminjamanModal').addEventListener('click', function (e) {
        if (e.target === this) {
            closePeminjamanModal();
        }
    });

    function filterRiwayat() {
        let input = document.getElementById('searchInputRiwayat').value.toLowerCase();
        let lists = document.querySelectorAll('.list-ke-bawah-container');

        lists.forEach(function (list) {
            let items = list.querySelectorAll('.list-item-row');
            let hasVisible = false;

            items.forEach(function (item) {
                let text = item.innerText.toLowerCase();
                if (text.includes(input)) {
                    item.style.setProperty('display', 'grid', 'important');
                    hasVisible = true;
                } else {
                    item.style.setProperty('display', 'none', 'important');
                }
            });

            let noDataMsg = list.querySelector('.no-data-msg');
            if (noDataMsg) {
                if (!hasVisible && items.length > 0) {
                    noDataMsg.style.display = 'block';
                } else {
                    noDataMsg.style.display = 'none';
                }
            }
        });
    }
</script>