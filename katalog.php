<?php
include_once 'config/koneksi.php';

// Amankan role jika session belum siap
$role = $_SESSION['role'] ?? 'user';

// Logika Peminjaman (Booking)
if (isset($_POST['pinjam'])) {
    $id = intval($_POST['id']);
    $tgl_pinjam = $_POST['tgl_pinjam'];
    $tgl_kembali = $_POST['tgl_kembali'];
    $jumlah = intval($_POST['jumlah']);

    $response = call_api('POST', '/api/booking', [
        'alat_id' => $id,
        'tanggal_pinjam' => $tgl_pinjam,
        'tanggal_kembali_rencana' => $tgl_kembali,
        'jumlah' => $jumlah
    ]);

    if (isset($response['http_code']) && $response['http_code'] >= 200 && $response['http_code'] < 300) {
        $_SESSION['flash_message'] = 'Peminjaman berhasil diajukan! Menunggu verifikasi admin.';
        header("Location: peminjaman.php");
        exit;
    } else {
        $err = htmlspecialchars($response['message'] ?? 'Gagal meminjam');
        $_SESSION['flash_message'] = 'Gagal: ' . $err;
    }
}


// Logika Edit Alat (Admin)
if ($role === 'admin' && isset($_POST['edit_submit'])) {
    $id = intval($_POST['edit_id']);

    $updateData = [
        'id' => $id,
        'kode_alat' => $_POST['kode_alat'],
        'nama_alat' => $_POST['nama'],
        'kategori' => strtolower($_POST['kategori']),
        'stok_total' => intval(str_replace('.', '', $_POST['stok'])),
        'denda_per_hari' => intval(str_replace('.', '', $_POST['denda_hari'])),
        'denda_rusak' => intval(str_replace('.', '', $_POST['denda_rusak'])),
        'denda_hilang' => intval(str_replace('.', '', $_POST['denda_hilang'])),
        'spesifikasi' => $_POST['deskripsi']
    ];

    $fotoUrls = [];
    if (isset($_FILES['fotos'])) {
        foreach ($_FILES['fotos']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['fotos']['error'][$key] === UPLOAD_ERR_OK && !empty($tmp_name)) {
                $original_name = $_FILES['fotos']['name'][$key];
                $mime_type = $_FILES['fotos']['type'][$key];
                $res = upload_file_api('/api/upload', $tmp_name, $original_name, $mime_type, 'foto');
                if (isset($res['file_url'])) {
                    $fotoUrls[] = $res['file_url'];
                }
            }
        }
    }

    $existingKept = $_POST['existing_foto'] ?? '';
    $existingArr = $existingKept ? array_filter(explode('|', $existingKept)) : [];

    $allPhotos = array_merge($existingArr, $fotoUrls);
    if (!empty($allPhotos)) {
        $updateData['foto'] = implode('|', $allPhotos);
    } else {
        $updateData['foto'] = '';
    }

    $response = call_api('PUT', '/api/alat', $updateData);

    if ((isset($response['status']) && $response['status'] === 'success') || (isset($response['http_code']) && in_array($response['http_code'], [200, 201]))) {
        $_SESSION['flash_message'] = 'Data alat berhasil diperbarui.';
    } else {
        $_SESSION['flash_message'] = 'Gagal: ' . ($response['message'] ?? 'Unknown error');
    }
    header('Location: katalog.php');
    exit;
}

// Logika Tambah Alat (Admin)
if ($role === 'admin' && isset($_POST['tambah_submit'])) {
    $fotoUrls = [];
    if (isset($_FILES['fotos'])) {
        foreach ($_FILES['fotos']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['fotos']['error'][$key] === UPLOAD_ERR_OK && !empty($tmp_name)) {
                $original_name = $_FILES['fotos']['name'][$key];
                $mime_type = $_FILES['fotos']['type'][$key];
                $res = upload_file_api('/api/upload', $tmp_name, $original_name, $mime_type, 'foto');
                if (isset($res['file_url'])) {
                    $fotoUrls[] = $res['file_url'];
                }
            }
        }
    }
    $fotoStr = !empty($fotoUrls) ? implode('|', $fotoUrls) : 'https://images.unsplash.com/photo-1558655146-d09347e92766?w=600';

    $createData = [
        'kode_alat' => $_POST['kode_alat'],
        'nama_alat' => $_POST['nama'],
        'spesifikasi' => $_POST['deskripsi'],
        'stok_total' => intval(str_replace('.', '', $_POST['stok'])),
        'foto' => $fotoStr,
        'kategori' => strtolower($_POST['kategori']),
        'denda_per_hari' => intval(str_replace('.', '', $_POST['denda_hari'])),
        'denda_rusak' => intval(str_replace('.', '', $_POST['denda_rusak'])),
        'denda_hilang' => intval(str_replace('.', '', $_POST['denda_hilang']))
    ];

    $response = call_api('POST', '/api/alat', $createData);

    if ((isset($response['status']) && $response['status'] === 'success') || (isset($response['http_code']) && in_array($response['http_code'], [200, 201]))) {
        $_SESSION['flash_message'] = 'Alat baru berhasil ditambahkan.';
    } else {
        $_SESSION['flash_message'] = 'Gagal: ' . ($response['message'] ?? 'Unknown error');
    }
    header('Location: katalog.php');
    exit;
}

// OPTIMASI HAPUS DATA: Menggunakan Post/Redirect/Get Pattern
if ($role === 'admin' && isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);

    $response = call_api('DELETE', '/api/alat', ['id' => $delete_id]);

    if ((isset($response['status']) && $response['status'] === 'success') || (isset($response['http_code']) && in_array($response['http_code'], [200, 201]))) {
        $_SESSION['flash_message'] = 'Alat berhasil dihapus.';
    } else {
        $_SESSION['flash_message'] = 'Gagal: ' . ($response['message'] ?? 'Unknown error');
    }
    header('Location: katalog.php');
    exit;
}

// Get All Alat
include 'includes/header.php';
$res_alat = call_api('GET', '/api/alat');
$items_raw = $res_alat['data'] ?? [];

// Mapping response API ke format template yang ada untuk meminimalisir perubahan UI
$items = array_map(function ($item) {
    // Pisahkan kategori
    $kategoriStr = $item['kategori'] ?? '';
    $kategoriArr = $kategoriStr ? explode('|', $kategoriStr) : [];

    // Pisahkan foto
    $fotoStr = $item['foto'] ?? '';
    $fotoArr = $fotoStr ? explode('|', $fotoStr) : [];
    $img = !empty($fotoArr[0]) ? $fotoArr[0] : 'https://images.unsplash.com/photo-1558655146-d09347e92766?w=600';

    return [
        'id' => $item['id'],
        'kode' => $item['kode_alat'] ?? '-',
        'nama' => $item['nama_alat'] ?? 'Unknown',
        'kategori' => $kategoriArr,
        'deskripsi' => $item['spesifikasi'] ?? '-',
        'stok' => $item['stok_total'] ?? 0,
        'stok_tersedia' => $item['stok_tersedia'] ?? $item['stok_total'] ?? 0,
        'denda_hari' => $item['denda_per_hari'] ?? 0,
        'denda_rusak' => $item['denda_rusak'] ?? 0,
        'denda_hilang' => $item['denda_hilang'] ?? 0,
        'img' => $img,
        'foto_array' => $fotoArr,
        'qr_code' => $item['qr_code'] ?? ''
    ];
}, $items_raw);
?>

<style>
    .alat-card.is-hidden {
        display: none !important;
    }
</style>

<div class="katalog-container">

    <?php
    // Tampilkan flash message jika ada, menggantikan javascript alert()
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

    <?php
    $all_categories = [];
    foreach ($items as $item) {
        foreach ($item['kategori'] as $kat) {
            $kat = trim($kat);
            if ($kat && !in_array($kat, $all_categories)) {
                $all_categories[] = $kat;
            }
        }
    }
    ?>

    <div class="search-katalog" style="display: flex; gap: 10px; margin-bottom: 15px;">
        <input type="text" id="search-input" placeholder="Cari alat..." autocomplete="off" spellcheck="false"
            style="flex: 1; padding: 10px 15px; border-radius: 10px; border: 1px solid #ccc;">
        <button id="btn-scan-qr" onclick="openScannerModal()" class="btn-primary"
            style="height: 50px; width: 50px; display: flex; align-items: center; justify-content: center; border-radius: 10px; cursor: pointer; padding: 0;">
            <i class="fa-solid fa-qrcode" style="font-size: 20px;"></i>
        </button>
        <?php if ($role === 'admin'): ?>
            <button onclick="openPrintModal()" class="btn-primary"
                style="height: 50px; width: 120px; display: flex; align-items: center; justify-content: center; border-radius: 10px; cursor: pointer; padding: 0;">
                Cetak QR
            </button>
        <?php endif; ?>
    </div>

    <!-- Category filters -->
    <?php if (!empty($all_categories)): ?>
        <div class="kategori-filters" style="display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 25px;"
            id="kategori-filters">
            <span class="badge-category active" onclick="filterCategory('Semua', this)">Semua</span>
            <?php foreach ($all_categories as $kat): ?>
                <span class="badge-category" onclick="filterCategory('<?= htmlspecialchars($kat) ?>', this)">
                    <?= htmlspecialchars(ucfirst($kat)) ?>
                </span>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($role === 'admin'): ?>
        <button onclick="openTambahModal()" class="btn-detail"
            style="margin-bottom:20px; display:inline-block; border: none; padding: 12px 20px;">
            + Tambah Alat
        </button>
    <?php endif; ?>

    <div class="grid-alat" id="catalog-grid">
        <?php if (empty($items)): ?>
            <div class="empty-state no-data">
                <i class="fa-solid fa-box-open" style="font-size: 40px; margin-bottom: 15px; color: #ccc;"></i>
                <p>Tidak ada alat dalam katalog.</p>
            </div>
        <?php else: ?>
            <?php foreach ($items as $item): ?>
                <?php
                $js_item = json_encode([
                    'id' => $item['id'],
                    'nama' => $item['nama'],
                    'kategori' => $item['kategori'],
                    'deskripsi' => $item['deskripsi'],
                    'stok' => $item['stok'],
                    'img' => $item['img'],
                    'foto_array' => $item['foto_array'],
                    'denda_hari' => $item['denda_hari'],
                    'denda_rusak' => $item['denda_rusak'],
                    'denda_hilang' => $item['denda_hilang'],
                    'kode' => $item['kode'],
                    'qr_code' => $item['qr_code']
                ]);
                ?>
                <div class="alat-card" onclick='openDetailModal(<?= htmlspecialchars($js_item, ENT_QUOTES, "UTF-8") ?>)'
                    style="cursor:pointer;">
                    <img src="<?= htmlspecialchars($item['img']) ?>" class="alat-img" loading="lazy">
                    <div class="alat-body">
                        <h4><?= htmlspecialchars($item['nama']) ?></h4>
                        <?php if (!empty($item['kategori'])): ?>
                            <div style="margin-bottom: 8px; display: flex; gap: 5px; flex-wrap: wrap;">
                                <?php foreach ($item['kategori'] as $kat): ?>
                                    <span
                                        style="font-size: 10px; background: #e2e8f0; color: #64748b; padding: 2px 8px; border-radius: 10px; font-weight: 600;">
                                        <?= htmlspecialchars(ucfirst($kat)) ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <p><?= htmlspecialchars(substr($item['deskripsi'], 0, 50)) ?>...</p>
                        <?php if ($role === 'admin'): ?>
                            <div style="display:flex; gap:10px; margin-top: auto;">
                                <button onclick='openEditModal(<?= htmlspecialchars($js_item, ENT_QUOTES, "UTF-8") ?>, event)'
                                    class="btn-detail"
                                    style="flex:1; padding: 8px; font-size: 13px; text-align: center; border:none; cursor:pointer;">
                                    Edit
                                </button>
                                <a href="javascript:void(0)" onclick="event.stopPropagation(); confirmDelete(<?= $item['id'] ?>);"
                                    class="btn-detail"
                                    style="flex:1; background:#ef4444; color:#ffffff; padding: 8px; font-size: 13px; text-align: center; text-decoration: none;">
                                    Hapus
                                </a>
                            </div>
                        <?php else: ?>
                            <button onclick='openDetailModal(<?= htmlspecialchars($js_item, ENT_QUOTES, "UTF-8") ?>)'
                                class="btn-detail" style="width:100%; border:none; cursor:pointer; margin-top: auto;">
                                Detail Alat
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        <div id="no-results" class="empty-state" style="display: none;">
            <i class="fa-solid fa-search" style="font-size: 40px; margin-bottom: 15px; color: #ccc;"></i>
            <p>Alat yang Anda cari tidak ditemukan.</p>
        </div>
    </div>

</div>

<!-- Modal Edit Alat (Admin) -->
<div id="editModal" class="modal-overlay">
    <div class="modal-content detail-card">
        <span class="close-btn" onclick="closeEditModal()">&times;</span>

        <div class="detail-body">
            <h2 style="margin-bottom: 20px;">Edit Data Alat</h2>
            <form class="form-pinjam" method="POST" action="katalog.php" enctype="multipart/form-data"
                onsubmit="return validateForm('edit')">
                <input type="hidden" name="edit_id" id="edit-id">
                <input type="hidden" name="existing_foto" id="edit-existing-foto">

                <label style="font-weight:600; font-size:13px; display:block; margin-bottom:5px;">Kode Alat</label>
                <input type="text" name="kode_alat" id="edit-kode" class="form-control" required
                    style="margin-bottom:15px; height:45px;">

                <label style="font-weight:600; font-size:13px; display:block; margin-bottom:5px;">Nama Alat</label>
                <input type="text" name="nama" id="edit-nama" class="form-control" required
                    style="margin-bottom:15px; height:45px;">

                <label style="font-weight:600; font-size:13px; display:block; margin-bottom:5px;">Kategori Alat</label>
                <div id="edit-kategori-container" class="category-tags-container"></div>
                <div class="category-input-wrapper">
                    <input type="text" id="edit-kategori-input" placeholder="+ Tambah Kategori Baru">
                    <button type="button" onclick="addCategory('edit')">Tambah</button>
                </div>
                <input type="hidden" name="kategori" id="edit-kategori-hidden" required>
                <p id="edit-kategori-error"
                    style="color: #ef4444; font-size: 12px; margin-top: 5px; margin-bottom: 15px; display: none;">Wajib
                    memilih minimal satu kategori</p>

                <label style="font-weight:600; font-size:13px; display:block; margin-bottom:5px;">Jumlah Stok</label>
                <input type="text" name="stok" id="edit-stok" class="form-control format-rupiah" required
                    style="margin-bottom:15px; height:45px;">

                <label style="font-weight:600; font-size:13px; display:block; margin-bottom:5px;">Denda Per Hari
                    (Rp)</label>
                <input type="text" name="denda_hari" id="edit-denda-hari" class="form-control format-rupiah" required
                    style="margin-bottom:15px; height:45px;">

                <label style="font-weight:600; font-size:13px; display:block; margin-bottom:5px;">Denda Rusak
                    (Rp)</label>
                <input type="text" name="denda_rusak" id="edit-denda-rusak" class="form-control format-rupiah" required
                    style="margin-bottom:15px; height:45px;">

                <label style="font-weight:600; font-size:13px; display:block; margin-bottom:5px;">Denda Hilang
                    (Rp)</label>
                <input type="text" name="denda_hilang" id="edit-denda-hilang" class="form-control format-rupiah"
                    required style="margin-bottom:15px; height:45px;">

                <label style="font-weight:600; font-size:13px; display:block; margin-bottom:5px;">Deskripsi Alat</label>
                <textarea name="deskripsi" id="edit-deskripsi" class="form-control" rows="5" required
                    style="margin-bottom:20px; height:auto; padding: 10px 15px;"></textarea>

                <label style="font-weight:600; font-size:13px; display:block; margin-bottom:5px;">Upload Gambar Alat
                    (Hingga 6 gambar, maksimum 5MB per gambar)</label>
                <div class="photo-upload-grid" id="edit-photo-grid"></div>
                <div id="edit-photo-inputs" style="display: none;"></div>
                <p id="edit-foto-error"
                    style="color: #ef4444; font-size: 12px; margin-top: 5px; margin-bottom: 15px; display: none;">Ukuran
                    gambar melebihi 5MB</p>

                <button type="submit" name="edit_submit" class="btn-primary"
                    style="width:100%; border:none; cursor:pointer; margin-top: 15px;">
                    Update Data
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Modal Tambah Alat -->
<div id="tambahModal" class="modal-overlay">
    <form class="modal-content detail-card" method="POST" action="katalog.php" enctype="multipart/form-data"
        style="max-height: 90vh; overflow-y: auto;" onsubmit="return validateForm('tambah')">
        <span class="close-btn" onclick="closeTambahModal()">&times;</span>
        <div class="detail-body">
            <h2 style="margin-bottom:20px; text-align: center;">Tambah Alat Baru</h2>

            <label style="font-weight:600; font-size:13px; display:block; margin-bottom:5px;">Kode Alat</label>
            <input type="text" name="kode_alat" class="form-control" placeholder="Kode Alat (contoh: ALT-001)" required
                style="margin-bottom:15px; height:50px;">

            <label style="font-weight:600; font-size:13px; display:block; margin-bottom:5px;">Nama Alat</label>
            <input type="text" name="nama" class="form-control" placeholder="Nama Alat" required
                style="margin-bottom:15px; height:50px;">

            <label style="font-weight:600; font-size:13px; display:block; margin-bottom:5px;">Kategori Alat</label>
            <div id="tambah-kategori-container" class="category-tags-container"></div>
            <div class="category-input-wrapper">
                <input type="text" id="tambah-kategori-input" placeholder="+ Tambah Kategori Baru">
                <button type="button" onclick="addCategory('tambah')">Tambah</button>
            </div>
            <input type="hidden" name="kategori" id="tambah-kategori-hidden" required>
            <p id="tambah-kategori-error"
                style="color: #ef4444; font-size: 12px; margin-top: 5px; margin-bottom: 15px; display: none;">Wajib
                memilih minimal satu kategori</p>

            <label style="font-weight:600; font-size:13px; display:block; margin-bottom:5px;">Jumlah Stok</label>
            <input type="text" name="stok" class="form-control format-rupiah" placeholder="Stok" required
                style="margin-bottom:15px; height:50px;">

            <label style="font-weight:600; font-size:13px; display:block; margin-bottom:5px;">Denda Per Hari
                (Rp)</label>
            <input type="text" name="denda_hari" class="form-control format-rupiah" placeholder="Denda Per Hari"
                required style="margin-bottom:15px; height:50px;">

            <label style="font-weight:600; font-size:13px; display:block; margin-bottom:5px;">Denda Rusak (Rp)</label>
            <input type="text" name="denda_rusak" class="form-control format-rupiah" placeholder="Denda Rusak" required
                style="margin-bottom:15px; height:50px;">

            <label style="font-weight:600; font-size:13px; display:block; margin-bottom:5px;">Denda Hilang (Rp)</label>
            <input type="text" name="denda_hilang" class="form-control format-rupiah" placeholder="Denda Hilang"
                required style="margin-bottom:15px; height:50px;">

            <label style="font-weight:600; font-size:13px; display:block; margin-bottom:5px;">Deskripsi Alat</label>
            <textarea name="deskripsi" class="form-control" rows="5" placeholder="Deskripsi" required
                style="margin-bottom:15px; height:auto; padding: 10px 15px;"></textarea>

            <label style="font-weight:600; font-size:13px; display:block; margin-bottom:5px;">Upload Gambar Alat (Hingga
                6 gambar, maksimum 5MB per gambar)</label>
            <div class="photo-upload-grid" id="tambah-photo-grid"></div>
            <div id="tambah-photo-inputs" style="display: none;"></div>
            <p id="tambah-foto-error"
                style="color: #ef4444; font-size: 12px; margin-top: 5px; margin-bottom: 15px; display: none;">Ukuran
                gambar melebihi 5MB</p>

            <button type="submit" name="tambah_submit" class="btn-primary" style="width:100%; margin-top: 15px;">
                Simpan Alat
            </button>
        </div>
    </form>
</div>

<!-- Modal Detail Alat -->
<div id="detailModal" class="modal-overlay">
    <div class="modal-content detail-card">
        <span class="close-btn" onclick="closeDetailModal()">&times;</span>

        <style>
            .detail-img-carousel::-webkit-scrollbar {
                display: none;
            }

            .detail-img-carousel.dragging {
                scroll-snap-type: none;
                scroll-behavior: auto;
            }

            .detail-img-carousel.dragging img {
                pointer-events: none;
            }
        </style>
        <div style="position: relative; margin-bottom: 15px;">
            <div id="modal-carousel" class="detail-img-carousel"
                style="display: flex; overflow-x: auto; scroll-snap-type: x mandatory; scroll-behavior: smooth; gap: 10px; border-radius: 15px; scrollbar-width: none; -ms-overflow-style: none;">
                <img id="modal-img" src="" class="detail-img"
                    style="min-width: 100%; scroll-snap-align: center; object-fit: contain; background: #f8fafc;">
            </div>
            <button id="carousel-prev" onclick="scrollCarousel(-1)"
                style="position: absolute; top: 50%; left: 10px; transform: translateY(-50%); background: rgba(0,0,0,0.5); color: white; border: none; border-radius: 50%; width: 30px; height: 30px; cursor: pointer; display: none; align-items: center; justify-content: center;"><i
                    class="fa-solid fa-chevron-left"></i></button>
            <button id="carousel-next" onclick="scrollCarousel(1)"
                style="position: absolute; top: 50%; right: 10px; transform: translateY(-50%); background: rgba(0,0,0,0.5); color: white; border: none; border-radius: 50%; width: 30px; height: 30px; cursor: pointer; display: none; align-items: center; justify-content: center;"><i
                    class="fa-solid fa-chevron-right"></i></button>
        </div>

        <div class="detail-body">
            <h2 id="modal-nama"></h2>
            <p id="modal-deskripsi"></p>

            <div class="info-box">
                <p><b>Stok :</b> <span id="modal-stok"></span> Unit</p>
                <p><b>Denda per hari :</b> Rp <span id="modal-denda-hari"></span></p>
                <p><b>Denda rusak :</b> Rp <span id="modal-denda-rusak"></span></p>
                <p><b>Denda hilang :</b> Rp <span id="modal-denda-hilang"></span></p>
            </div>

            <form class="form-pinjam" method="POST" action="katalog.php">
                <input type="hidden" name="id" id="modal-id">

                <div class="info-box" style="margin-bottom: 20px;">
                    <center>
                        <img id="modal-qr" src="" alt="QR Code Alat">
                        <p style="font-size:12px; color:#666; margin-top:5px;">Kode Alat: <span id="modal-kode"></span>
                        </p>
                    </center>
                </div>

                <?php if ($role !== 'admin'): ?>
                    <label style="font-weight:600; font-size:13px; display:block; margin-bottom:5px;">Tanggal Pinjam</label>
                    <input type="date" name="tgl_pinjam" required class="form-control"
                        style="margin-bottom:15px; height:50px;">

                    <label style="font-weight:600; font-size:13px; display:block; margin-bottom:5px;">Tanggal
                        Kembali</label>
                    <input type="date" name="tgl_kembali" required class="form-control"
                        style="margin-bottom:15px; height:50px;">

                    <label style="font-weight:600; font-size:13px; display:block; margin-bottom:5px;">Jumlah</label>
                    <input type="number" name="jumlah" id="modal-jumlah" value="1" min="1" required class="form-control"
                        style="margin-bottom:15px; height:50px;">

                    <button type="submit" name="pinjam" class="btn-pinjam" style="width:100%;">
                        Pinjam Alat
                    </button>
                <?php endif; ?>
            </form>
        </div>
    </div>
</div>

<!-- Modal Scanner QR -->
<div id="scannerModal" class="modal-overlay">
    <div class="modal-content detail-card" style="text-align: center; max-width: 400px; padding: 20px;">
        <span class="close-btn" onclick="closeScannerModal()">&times;</span>
        <h2 style="margin-bottom: 15px;">Scan QR Code Alat</h2>
        <div id="reader" style="width: 100%; border-radius: 15px; overflow: hidden; margin-bottom: 15px;"></div>
        <p style="font-size: 13px; color: #666;">Arahkan kamera pada QR Code alat untuk melihat detail.</p>
    </div>
</div>

<!-- Modal Print QR -->
<div id="printModal" class="modal-overlay">
    <div class="modal-content detail-card" style="max-width: 600px;">
        <span class="close-btn" onclick="closePrintModal()">&times;</span>
        <div class="detail-body">
            <h2 style="margin-bottom: 20px; color: #0f172a; font-weight: 800;">Pilih Alat untuk Dicetak (QR Code)</h2>
            <form action="cetak_qr.php" method="POST" target="_blank" onsubmit="setTimeout(closePrintModal, 500)">
                <div style="margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid #e2e8f0;">
                    <label
                        style="display: flex; align-items: center; gap: 10px; cursor: pointer; font-weight: 700; color: #0f172a;">
                        <input type="checkbox" id="selectAllPrint" onclick="toggleAllPrint(this)"
                            style="width: 18px; height: 18px; cursor: pointer;">
                        Pilih Semua
                    </label>
                </div>
                <div
                    style="max-height: 400px; overflow-y: auto; display: flex; flex-direction: column; gap: 10px; margin-bottom: 20px; padding-right: 10px;">
                    <?php foreach ($items as $item): ?>
                        <label
                            style="display: flex; align-items: center; gap: 15px; padding: 10px; border: 1px solid #e2e8f0; border-radius: 12px; cursor: pointer; transition: 0.2s;">
                            <input type="checkbox" name="qr_codes[]" class="print-checkbox"
                                value="<?= htmlspecialchars($item['kode'] . '|' . $item['nama'] . '|' . $item['qr_code']) ?>"
                                style="width: 18px; height: 18px; cursor: pointer;">
                            <img src="<?= htmlspecialchars($item['img']) ?>"
                                style="width: 40px; height: 40px; border-radius: 8px; object-fit: cover;">
                            <div style="flex: 1;">
                                <div style="font-weight: 700; color: #0f172a; font-size: 14px;">
                                    <?= htmlspecialchars($item['nama']) ?>
                                </div>
                                <div style="color: #64748b; font-size: 12px; font-family: monospace; font-weight: 600;">
                                    <?= htmlspecialchars($item['kode']) ?>
                                </div>
                            </div>
                            <div style="font-size: 20px; color: #cbd5e1;">
                                <i class="fa-solid fa-qrcode"></i>
                            </div>
                        </label>
                    <?php endforeach; ?>
                </div>
                <button type="submit" class="btn-primary"
                    style="width: 100%; border: none; cursor: pointer; display: flex; justify-content: center; align-items: center; gap: 10px;">
                    <i class="fa-solid fa-print"></i> Cetak QR Terpilih
                </button>
            </form>
        </div>
    </div>
</div>

<script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
<script>
    const alatData = <?= json_encode(array_values($items)) ?>;
    let activeCategory = 'Semua';

    function formatRupiah(number) {
        return new Intl.NumberFormat('id-ID').format(number);
    }

    function openDetailModal(item) {
        document.getElementById('modal-id').value = item.id;

        // Handle carousel
        const carouselContainer = document.getElementById('modal-carousel');
        carouselContainer.innerHTML = ''; // Clear previous images

        if (item.foto_array && item.foto_array.length > 0) {
            item.foto_array.forEach(src => {
                if (src.trim() !== '') {
                    const img = document.createElement('img');
                    img.src = src;
                    img.className = 'detail-img';
                    img.style.minWidth = '100%';
                    img.style.scrollSnapAlign = 'center';
                    img.style.objectFit = 'contain';
                    img.style.background = '#f8fafc';
                    img.style.borderRadius = '15px';
                    carouselContainer.appendChild(img);
                }
            });
        }

        // Fallback if empty array or invalid
        if (carouselContainer.children.length === 0) {
            const img = document.createElement('img');
            img.src = item.img || 'https://images.unsplash.com/photo-1558655146-d09347e92766?w=600';
            img.className = 'detail-img';
            img.style.minWidth = '100%';
            img.style.scrollSnapAlign = 'center';
            img.style.objectFit = 'contain';
            img.style.background = '#f8fafc';
            img.style.borderRadius = '15px';
            carouselContainer.appendChild(img);
        }

        // Toggle buttons visibility based on child nodes
        const btnPrev = document.getElementById('carousel-prev');
        const btnNext = document.getElementById('carousel-next');
        if (carouselContainer.children.length > 1) {
            btnPrev.style.display = 'flex';
            btnNext.style.display = 'flex';
        } else {
            btnPrev.style.display = 'none';
            btnNext.style.display = 'none';
        }

        document.getElementById('modal-nama').innerText = item.nama;
        document.getElementById('modal-deskripsi').innerText = item.deskripsi;
        document.getElementById('modal-stok').innerText = formatRupiah(item.stok);

        const modalJumlah = document.getElementById('modal-jumlah');
        if (modalJumlah) {
            modalJumlah.max = item.stok;
        }

        document.getElementById('modal-denda-hari').innerText = formatRupiah(item.denda_hari);
        document.getElementById('modal-denda-rusak').innerText = formatRupiah(item.denda_rusak);
        document.getElementById('modal-denda-hilang').innerText = formatRupiah(item.denda_hilang);

        document.getElementById('modal-kode').innerText = item.qr_code;
        document.getElementById('modal-qr').src = "https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=" + encodeURIComponent(item.qr_code);

        document.getElementById('detailModal').classList.add('show');
        document.body.style.overflow = 'hidden';
    }

    function closeDetailModal() {
        document.getElementById('detailModal').classList.remove('show');
        document.body.style.overflow = '';
    }

    document.getElementById('detailModal').addEventListener('click', function (e) {
        if (e.target === this) {
            closeDetailModal();
        }
    });

    // Carousel Logic
    function scrollCarousel(dir) {
        const carousel = document.getElementById('modal-carousel');
        const width = carousel.clientWidth;
        carousel.scrollBy({ left: dir * width, behavior: 'smooth' });
    }

    const carousel = document.getElementById('modal-carousel');
    let isDown = false;
    let startX;
    let scrollLeft;

    carousel.addEventListener('mousedown', (e) => {
        isDown = true;
        carousel.classList.add('dragging');
        startX = e.pageX - carousel.offsetLeft;
        scrollLeft = carousel.scrollLeft;
    });
    carousel.addEventListener('mouseleave', () => {
        isDown = false;
        carousel.classList.remove('dragging');
    });
    carousel.addEventListener('mouseup', () => {
        isDown = false;
        carousel.classList.remove('dragging');
    });
    carousel.addEventListener('mousemove', (e) => {
        if (!isDown) return;
        e.preventDefault();
        const x = e.pageX - carousel.offsetLeft;
        const walk = (x - startX) * 2; // scroll-fast
        carousel.scrollLeft = scrollLeft - walk;
    });

    // Fungsi Filter Kategori
    function filterCategory(category, element) {
        activeCategory = category;

        // Update active UI
        const badges = document.querySelectorAll('#kategori-filters .badge-category');
        badges.forEach(b => {
            b.classList.remove('active');
        });
        element.classList.add('active');

        applyFilters();
    }

    function applyFilters() {
        const searchInput = document.getElementById('search-input');
        const query = searchInput ? searchInput.value.toLowerCase() : '';
        const cards = document.querySelectorAll('.alat-card:not(#no-results):not(.no-data)');
        let hasVisible = false;

        cards.forEach(card => {
            const title = (card.querySelector('h4') ? card.querySelector('h4').textContent : '').toLowerCase();
            // Fallback for code which might be deep in modal or not rendered. The item name is usually enough, but we can also check the onclick string
            const onClickStr = card.getAttribute('onclick') ? card.getAttribute('onclick').toLowerCase() : '';
            const matchSearch = title.includes(query) || onClickStr.includes(query);

            let matchCategory = true;
            if (activeCategory !== 'Semua') {
                const categoriesEls = card.querySelectorAll('.alat-body span');
                let itemCategories = [];
                categoriesEls.forEach(el => itemCategories.push(el.textContent.trim().toUpperCase()));
                matchCategory = itemCategories.includes(activeCategory.toUpperCase());
            }

            if (matchSearch && matchCategory) {
                card.classList.remove('is-hidden');
                hasVisible = true;
            } else {
                card.classList.add('is-hidden');
            }
        });

        const noResults = document.getElementById('no-results');
        if (noResults) {
            noResults.style.display = hasVisible ? 'none' : 'block';
        }
    }

    // Script Format Rupiah & Validasi
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.format-rupiah').forEach(input => {
            input.addEventListener('input', function (e) {
                let val = this.value.replace(/[^0-9]/g, '');
                if (val) {
                    this.value = formatRupiah(val);
                } else {
                    this.value = '';
                }
            });
        });
    });

    let availableCategoriesData = <?php echo json_encode(array_values($all_categories)); ?>;
    let availableCategories = availableCategoriesData.map(c => c.toLowerCase());

    let kategoriState = { 'edit': [], 'tambah': [] };
    let photoState = { 'edit': [], 'tambah': [] };

    function initGrid(type) {
        photoState[type] = [null, null, null, null, null, null];
        const grid = document.getElementById(type + '-photo-grid');
        const inputs = document.getElementById(type + '-photo-inputs');
        if (!grid || !inputs) return;

        grid.innerHTML = '';
        inputs.innerHTML = '';
        for (let i = 0; i < 6; i++) {
            grid.innerHTML += `
            <div class="photo-slot" onclick="document.getElementById('${type}-foto-${i}').click()">
                <div class="plus-icon">+</div>
                <img id="${type}-preview-${i}" style="display:none;">
                <button type="button" class="remove-photo" style="display:none;" onclick="removePhoto(event, ${i}, '${type}')"><i class="fa-solid fa-xmark"></i></button>
            </div>
        `;
            inputs.innerHTML += `<input type="file" name="fotos[]" id="${type}-foto-${i}" accept="image/*" onchange="handlePhotoChange(this, ${i}, '${type}')">`;
        }
    }

    function toggleCategory(type, cat) {
        cat = cat.toLowerCase();
        const index = kategoriState[type].indexOf(cat);
        if (index > -1) {
            kategoriState[type].splice(index, 1);
        } else {
            kategoriState[type].push(cat);
        }
        renderCategories(type);
    }

    function addCategory(type) {
        const input = document.getElementById(type + '-kategori-input');
        const val = input.value.trim().toLowerCase();
        if (val) {
            if (!availableCategories.includes(val)) {
                availableCategories.push(val);
            }
            if (!kategoriState[type].includes(val)) {
                kategoriState[type].push(val);
            }
            renderCategories(type);
            input.value = '';
        }
    }

    function renderCategories(type) {
        const container = document.getElementById(type + '-kategori-container');
        if (!container) return;
        container.innerHTML = '';

        // Urutkan kategori agar rapi
        let sortedCategories = [...availableCategories].sort();

        sortedCategories.forEach((cat) => {
            const isActive = kategoriState[type].includes(cat);
            const activeClass = isActive ? 'active' : '';
            const icon = isActive ? '<i class="fa-solid fa-check" style="margin-left: 4px; color: #0f172a;"></i>' : '<i class="fa-solid fa-plus" style="margin-left: 4px; color: #94a3b8;"></i>';

            container.innerHTML += `
            <div class="category-tag-item ${activeClass}" style="cursor: pointer; user-select: none;" onclick="toggleCategory('${type}', '${cat}')">
                ${cat}
                ${icon}
            </div>
        `;
        });

        document.getElementById(type + '-kategori-hidden').value = kategoriState[type].join('|');
        if (kategoriState[type].length > 0) {
            document.getElementById(type + '-kategori-error').style.display = 'none';
        }
    }

    function handlePhotoChange(input, index, type) {
        if (input.files && input.files[0]) {
            const file = input.files[0];
            if (file.size > 5 * 1024 * 1024) {
                document.getElementById(type + '-foto-error').style.display = 'block';
                input.value = "";
                return;
            }
            document.getElementById(type + '-foto-error').style.display = 'none';

            photoState[type][index] = file;
            const reader = new FileReader();
            reader.onload = function (e) {
                const preview = document.getElementById(`${type}-preview-${index}`);
                preview.src = e.target.result;
                preview.style.display = 'block';
                preview.nextElementSibling.style.display = 'flex';
            }
            reader.readAsDataURL(file);
        }
    }

    function removePhoto(e, index, type) {
        e.stopPropagation();
        photoState[type][index] = null;

        const input = document.getElementById(`${type}-foto-${index}`);
        if (input) input.value = "";

        const preview = document.getElementById(`${type}-preview-${index}`);
        if (preview) {
            preview.src = "";
            preview.style.display = 'none';
            preview.nextElementSibling.style.display = 'none';
        }

        if (type === 'edit') updateExistingFoto();
    }

    function updateExistingFoto() {
        let keptUrls = [];
        photoState['edit'].forEach((item) => {
            if (typeof item === 'string' && item.startsWith('http')) {
                keptUrls.push(item);
            }
        });
        const existing = document.getElementById('edit-existing-foto');
        if (existing) existing.value = keptUrls.join('|');
    }

    function validateForm(type) {
        if (kategoriState[type].length === 0) {
            document.getElementById(type + '-kategori-error').style.display = 'block';
            return false;
        }
        return true;
    }

    // Initializing the grids
    document.addEventListener('DOMContentLoaded', () => {
        initGrid('tambah');
        initGrid('edit');
    });

    // Fungsi Edit Modal
    function openEditModal(item, e) {
        if (e) e.stopPropagation();
        document.getElementById('edit-id').value = item.id;
        document.getElementById('edit-kode').value = item.kode;
        document.getElementById('edit-nama').value = item.nama;

        // Setup Kategori State
        kategoriState['edit'] = item.kategori ? item.kategori : [];
        renderCategories('edit');

        // Setup Photo State
        initGrid('edit');
        if (item.foto_array) {
            item.foto_array.forEach((url, i) => {
                if (i < 6 && url.trim() !== '') {
                    photoState['edit'][i] = url;
                    const preview = document.getElementById(`edit-preview-${i}`);
                    if (preview) {
                        preview.src = url;
                        preview.style.display = 'block';
                        preview.nextElementSibling.style.display = 'flex';
                    }
                }
            });
        }
        updateExistingFoto();

        document.getElementById('edit-stok').value = formatRupiah(item.stok);
        document.getElementById('edit-denda-hari').value = formatRupiah(item.denda_hari);
        document.getElementById('edit-denda-rusak').value = formatRupiah(item.denda_rusak);
        document.getElementById('edit-denda-hilang').value = formatRupiah(item.denda_hilang);
        document.getElementById('edit-deskripsi').value = item.deskripsi;

        document.getElementById('editModal').classList.add('show');
        document.body.style.overflow = 'hidden';
    }

    function closeEditModal() {
        document.getElementById('editModal').classList.remove('show');
        document.body.style.overflow = '';
    }

    document.getElementById('editModal').addEventListener('click', function (e) {
        if (e.target === this) {
            closeEditModal();
        }
    });

    document.addEventListener('DOMContentLoaded', function () {
        const searchInput = document.getElementById('search-input');

        if (searchInput) {
            function debounce(func, delay) {
                let timeoutId;
                return function (...args) {
                    if (timeoutId) clearTimeout(timeoutId);
                    timeoutId = setTimeout(() => {
                        func.apply(this, args);
                    }, delay);
                };
            }

            const filterCards = debounce(function () {
                applyFilters();
            }, 150);

            searchInput.addEventListener('input', filterCards);

            const urlParams = new URLSearchParams(window.location.search);
            const searchQuery = urlParams.get('search');
            if (searchQuery) {
                searchInput.value = searchQuery;
                applyFilters();
            }
        }
    });

    // Fungsi Tambah Modal
    function openTambahModal() {
        kategoriState['tambah'] = [];
        renderCategories('tambah');
        initGrid('tambah');
        document.getElementById('tambahModal').classList.add('show');
        document.body.style.overflow = 'hidden';
    }

    function closeTambahModal() {
        document.getElementById('tambahModal').classList.remove('show');
        document.body.style.overflow = '';
    }

    document.getElementById('tambahModal').addEventListener('click', function (e) {
        if (e.target === this) {
            closeTambahModal();
        }
    });

    let html5QrcodeScanner = null;

    function openScannerModal() {
        document.getElementById('scannerModal').classList.add('show');
        document.body.style.overflow = 'hidden';

        if (!html5QrcodeScanner) {
            // Initialize the scanner
            html5QrcodeScanner = new Html5QrcodeScanner(
                "reader", { fps: 10, qrbox: { width: 250, height: 250 } }, false);

            let isScanning = true;

            function onScanSuccess(decodedText, decodedResult) {
                if (!isScanning) return;
                isScanning = false;

                const queryKode = decodedText.trim();
                const foundItem = alatData.find(a => a.qr_code === queryKode || a.kode === queryKode);

                if (foundItem) {
                    closeScannerModal();
                    Swal.fire({
                        icon: 'success',
                        title: 'Scan Berhasil',
                        text: 'Membuka detail alat...',
                        timer: 1200,
                        showConfirmButton: false
                    }).then(() => {
                        openDetailModal(foundItem);
                        isScanning = true;
                    });
                } else {
                    closeScannerModal();
                    setTimeout(() => {
                        Swal.fire({
                            icon: 'error',
                            title: 'Tidak Ditemukan',
                            text: 'Alat dengan QR Code tersebut tidak terdaftar di katalog.',
                            confirmButtonColor: '#ef4444'
                        }).then(() => {
                            isScanning = true;
                        });
                    }, 400);
                }
            }

            function onScanFailure(error) {
                // abaikan kegagalan scan per frame
            }

            html5QrcodeScanner.render(onScanSuccess, onScanFailure);
        }
    }

    function closeScannerModal() {
        document.getElementById('scannerModal').classList.remove('show');
        document.body.style.overflow = '';

        if (html5QrcodeScanner) {
            html5QrcodeScanner.clear().then(() => {
                html5QrcodeScanner = null;
            }).catch(error => {
                console.error("Gagal mematikan kamera: ", error);
                html5QrcodeScanner = null;
            });
        }
    }

    document.getElementById('scannerModal').addEventListener('click', function (e) {
        if (e.target === this) {
            closeScannerModal();
        }
    });

    function confirmDelete(id) {
        Swal.fire({
            title: 'Apakah Anda yakin?',
            text: "Data alat ini akan dihapus permanen!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#7dd3fc',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'katalog.php?delete_id=' + id;
            }
        });
    }

    function openPrintModal() {
        document.getElementById('printModal').classList.add('show');
        document.body.style.overflow = 'hidden';
    }

    function closePrintModal() {
        document.getElementById('printModal').classList.remove('show');
        document.body.style.overflow = '';
    }

    document.getElementById('printModal').addEventListener('click', function (e) {
        if (e.target === this) {
            closePrintModal();
        }
    });

    function toggleAllPrint(source) {
        const checkboxes = document.querySelectorAll('.print-checkbox');
        checkboxes.forEach(cb => {
            cb.checked = source.checked;
        });
    }
</script>

<?php include 'includes/footer.php'; ?>