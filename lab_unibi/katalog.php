<?php
include 'includes/header.php';

// Amankan role jika session belum siap
$role = $_SESSION['role'] ?? 'user';

// Logika Peminjaman (Digabung dari detail_alat.php)
if (isset($_POST['pinjam'])) {
    $id = intval($_POST['id']);
    $item = null;
    foreach ($_SESSION['dummy_alat'] as $a) {
        if ($a['id'] == $id) {
            $item = $a;
            break;
        }
    }
    
    if ($item) {
        $tgl_pinjam = $_POST['tgl_pinjam'];
        $tgl_kembali = $_POST['tgl_kembali'];
        $jumlah = intval($_POST['jumlah']);
        
        $user_nim = '224111006';
        if (isset($_SESSION['dummy_users'])) {
            foreach ($_SESSION['dummy_users'] as $u) {
                if ($u['id'] == $_SESSION['id']) {
                    $user_nim = $u['nim'];
                    break;
                }
            }
        }
        
        $_SESSION['dummy_verifikasi'][] = [
            'id' => count($_SESSION['dummy_verifikasi']) + 1,
            'user_nama' => $_SESSION['nama'],
            'user_nim' => $user_nim,
            'alat_nama' => $item['nama'],
            'img' => $item['img'],
            'tgl_pinjam' => $tgl_pinjam,
            'jumlah' => $jumlah
        ];
        
        echo "<script>
            alert('Peminjaman berhasil diajukan! Menunggu verifikasi admin.');
            window.location.href = 'riwayat.php';
        </script>";
        exit;
    }
}

// Logika Edit Alat (Digabung dari edit_alat.php)
if ($role === 'admin' && isset($_POST['edit_submit'])) {
    $id = intval($_POST['edit_id']);
    $item_key = -1;
    foreach ($_SESSION['dummy_alat'] as $key => $a) {
        if ($a['id'] == $id) {
            $item_key = $key;
            break;
        }
    }
    
    if ($item_key !== -1) {
        $_SESSION['dummy_alat'][$item_key]['nama'] = $_POST['nama'];
        $_SESSION['dummy_alat'][$item_key]['stok'] = intval($_POST['stok']);
        $_SESSION['dummy_alat'][$item_key]['denda_hari'] = intval($_POST['denda_hari']);
        $_SESSION['dummy_alat'][$item_key]['denda_rusak'] = intval($_POST['denda_rusak']);
        $_SESSION['dummy_alat'][$item_key]['denda_hilang'] = intval($_POST['denda_hilang']);
        $_SESSION['dummy_alat'][$item_key]['deskripsi'] = $_POST['deskripsi'];
        
        $_SESSION['flash_message'] = 'Data alat berhasil diperbarui.';
        header('Location: katalog.php');
        exit;
    }
}

// Logika Tambah Alat (Digabung dari tambah_alat.php)
if ($role === 'admin' && isset($_POST['tambah_submit'])) {
    $nama = $_POST['nama'];
    $stok = intval($_POST['stok']);
    $denda_hari = intval($_POST['denda_hari']);
    $denda_rusak = intval($_POST['denda_rusak']);
    $denda_hilang = intval($_POST['denda_hilang']);
    $deskripsi = $_POST['deskripsi'];
    
    $max_id = 0;
    foreach ($_SESSION['dummy_alat'] as $item) {
        if ($item['id'] > $max_id) {
            $max_id = $item['id'];
        }
    }
    $next_id = $max_id + 1;
    $kode = 'UNI-' . str_pad($next_id, 3, '0', STR_PAD_LEFT);
    $img = 'https://images.unsplash.com/photo-1558655146-d09347e92766?w=600';
    
    $_SESSION['dummy_alat'][] = [
        'id' => $next_id,
        'kode' => $kode,
        'nama' => $nama,
        'deskripsi' => $deskripsi,
        'stok' => $stok,
        'denda_hari' => $denda_hari,
        'denda_rusak' => $denda_rusak,
        'denda_hilang' => $denda_hilang,
        'img' => $img
    ];
    
    $_SESSION['flash_message'] = 'Alat baru berhasil ditambahkan.';
    header('Location: katalog.php');
    exit;
}

// 1. OPTIMASI HAPUS DATA: Menggunakan Post/Redirect/Get Pattern tanpa blocking JS alert
if ($role === 'admin' && isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    foreach ($_SESSION['dummy_alat'] as $key => $item) {
        if ($item['id'] == $delete_id) {
            unset($_SESSION['dummy_alat'][$key]);
            $_SESSION['dummy_alat'] = array_values($_SESSION['dummy_alat']);
            break;
        }
    }
    // Simpan pesan ke session, lalu redirect langsung via server-side header
    $_SESSION['flash_message'] = 'Alat berhasil dihapus.';
    header('Location: katalog.php');
    exit;
}

$items = $_SESSION['dummy_alat'] ?? [];
?>

<style>
    .alat-card.is-hidden {
        display: none !important;
    }
    .alert-notif {
        padding: 12px 20px; 
        background: #d4edda; 
        color: #155724; 
        border: 1px solid #c3e6cb; 
        border-radius: 8px; 
        margin-bottom: 20px;
        font-weight: 500;
    }
</style>

<div class="katalog-container">

<?php 
// Tampilkan flash message jika ada, menggantikan javascript alert()
if (isset($_SESSION['flash_message'])): ?>
    <div class="alert-notif">
        <?= htmlspecialchars($_SESSION['flash_message']) ?>
    </div>
    <?php unset($_SESSION['flash_message']); ?>
<?php endif; ?>

    <div class="search-katalog" style="display: flex; gap: 10px;">
        <input type="text" id="search-input" placeholder="Cari alat..." autocomplete="off" spellcheck="false" style="flex: 1;">
        <button id="btn-scan-qr" onclick="openScannerModal()" class="btn-primary" style="height: 50px; width: 50px; display: flex; align-items: center; justify-content: center; border-radius: 10px; cursor: pointer; padding: 0;">
            <i class="fa-solid fa-qrcode" style="font-size: 20px;"></i>
        </button>
    </div>

    <?php if ($role === 'admin'): ?>
        <button onclick="openTambahModal()" class="btn-detail" style="margin-bottom:20px; display:inline-block; border: none; padding: 12px 20px;">
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
                <div class="alat-card">
                    <img src="<?= htmlspecialchars($item['img']) ?>" class="alat-img" loading="lazy">
                    <div class="alat-body">
                        <h4><?= htmlspecialchars($item['nama']) ?></h4>
                        <p><?= htmlspecialchars(substr($item['deskripsi'], 0, 50)) ?>...</p>
                        <?php 
                        $js_item = json_encode([
                            'id' => $item['id'],
                            'nama' => $item['nama'],
                            'deskripsi' => $item['deskripsi'],
                            'stok' => $item['stok'],
                            'img' => $item['img'],
                            'denda_hari' => $item['denda_hari'],
                            'denda_rusak' => $item['denda_rusak'],
                            'denda_hilang' => $item['denda_hilang'],
                            'kode' => $item['kode']
                        ]); 
                        ?>
                        
                        <?php if ($role === 'admin'): ?>
                            <div style="display:flex; gap:10px; margin-top: auto;">
                                <button onclick='openEditModal(<?= htmlspecialchars($js_item, ENT_QUOTES, "UTF-8") ?>)' class="btn-detail" style="flex:1; padding: 8px; font-size: 13px; text-align: center; border:none; cursor:pointer;">
                                    Edit
                                </button>
                                <a href="katalog.php?delete_id=<?= $item['id'] ?>" onclick="return confirm('Apakah Anda yakin ingin menghapus alat ini?')" class="btn-detail" style="flex:1; background:#ffb3b3; color:#cc0000; padding: 8px; font-size: 13px; text-align: center; text-decoration: none;">
                                    Hapus
                                </a>
                            </div>
                        <?php else: ?>
                            <button onclick='openDetailModal(<?= htmlspecialchars($js_item, ENT_QUOTES, "UTF-8") ?>)' class="btn-detail" style="width:100%; border:none; cursor:pointer; margin-top: auto;">
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
            <form class="form-pinjam" method="POST" action="katalog.php">
                <input type="hidden" name="edit_id" id="edit-id">
                
                <label style="font-weight:600; font-size:13px; display:block; margin-bottom:5px;">Nama Alat</label>
                <input type="text" name="nama" id="edit-nama" class="form-control" required style="margin-bottom:15px; height:45px;">

                <label style="font-weight:600; font-size:13px; display:block; margin-bottom:5px;">Jumlah Stok</label>
                <input type="number" name="stok" id="edit-stok" class="form-control" required style="margin-bottom:15px; height:45px;">

                <label style="font-weight:600; font-size:13px; display:block; margin-bottom:5px;">Denda Per Hari (Rp)</label>
                <input type="number" name="denda_hari" id="edit-denda-hari" class="form-control" required style="margin-bottom:15px; height:45px;">

                <label style="font-weight:600; font-size:13px; display:block; margin-bottom:5px;">Denda Rusak (Rp)</label>
                <input type="number" name="denda_rusak" id="edit-denda-rusak" class="form-control" required style="margin-bottom:15px; height:45px;">

                <label style="font-weight:600; font-size:13px; display:block; margin-bottom:5px;">Denda Hilang (Rp)</label>
                <input type="number" name="denda_hilang" id="edit-denda-hilang" class="form-control" required style="margin-bottom:15px; height:45px;">

                <label style="font-weight:600; font-size:13px; display:block; margin-bottom:5px;">Deskripsi Alat</label>
                <textarea name="deskripsi" id="edit-deskripsi" class="form-control" rows="5" required style="margin-bottom:20px; height:auto; padding: 10px 15px;"></textarea>

                <button type="submit" name="edit_submit" class="btn-primary" style="width:100%; border:none; cursor:pointer;">
                    Update Data
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Modal Tambah Alat -->
<div id="tambahModal" class="modal-overlay">
    <form class="modal-content detail-card" method="POST" action="katalog.php" style="max-height: 90vh; overflow-y: auto;">
        <span class="close-btn" onclick="closeTambahModal()">&times;</span>
        <div class="detail-body">
            <h2 style="margin-bottom:20px; text-align: center;">Tambah Alat Baru</h2>

            <label style="font-weight:600; font-size:13px; display:block; margin-bottom:5px;">Nama Alat</label>
            <input type="text" name="nama" class="form-control" placeholder="Nama Alat" required style="margin-bottom:15px; height:50px;">

            <label style="font-weight:600; font-size:13px; display:block; margin-bottom:5px;">Jumlah Stok</label>
            <input type="number" name="stok" class="form-control" placeholder="Stok" required style="margin-bottom:15px; height:50px;">

            <label style="font-weight:600; font-size:13px; display:block; margin-bottom:5px;">Denda Per Hari (Rp)</label>
            <input type="number" name="denda_hari" class="form-control" placeholder="Denda Per Hari" required style="margin-bottom:15px; height:50px;">

            <label style="font-weight:600; font-size:13px; display:block; margin-bottom:5px;">Denda Rusak (Rp)</label>
            <input type="number" name="denda_rusak" class="form-control" placeholder="Denda Rusak" required style="margin-bottom:15px; height:50px;">

            <label style="font-weight:600; font-size:13px; display:block; margin-bottom:5px;">Denda Hilang (Rp)</label>
            <input type="number" name="denda_hilang" class="form-control" placeholder="Denda Hilang" required style="margin-bottom:15px; height:50px;">

            <label style="font-weight:600; font-size:13px; display:block; margin-bottom:5px;">Deskripsi Alat</label>
            <textarea name="deskripsi" class="form-control" rows="5" placeholder="Deskripsi" required style="margin-bottom:15px; height:auto; padding: 10px 15px;"></textarea>

            <button type="submit" name="tambah_submit" class="btn-primary" style="width:100%;">
                Simpan Alat
            </button>
        </div>
    </form>
</div>

<!-- Modal Detail Alat -->
<div id="detailModal" class="modal-overlay">
    <div class="modal-content detail-card">
        <span class="close-btn" onclick="closeDetailModal()">&times;</span>
        
        <img id="modal-img" src="" class="detail-img">
        
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
                
                <label style="font-weight:600; font-size:13px; display:block; margin-bottom:5px;">Tanggal Pinjam</label>
                <input type="date" name="tgl_pinjam" required class="form-control" style="margin-bottom:15px; height:50px;">
                
                <label style="font-weight:600; font-size:13px; display:block; margin-bottom:5px;">Tanggal Kembali</label>
                <input type="date" name="tgl_kembali" required class="form-control" style="margin-bottom:15px; height:50px;">
                
                <label style="font-weight:600; font-size:13px; display:block; margin-bottom:5px;">Jumlah</label>
                <input type="number" name="jumlah" id="modal-jumlah" value="1" min="1" required class="form-control" style="margin-bottom:15px; height:50px;">
                
                <div class="info-box" style="margin-bottom: 20px;">
                    <center>
                        <img id="modal-qr" src="" alt="QR Code Alat">
                        <p style="font-size:12px; color:#666; margin-top:5px;">Kode Alat: <span id="modal-kode"></span></p>
                    </center>
                </div>
                
                <button type="submit" name="pinjam" class="btn-pinjam" style="width:100%;">
                    Pinjam Alat
                </button>
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

<script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
<script>
const alatData = <?= json_encode(array_values($items)) ?>;
function formatRupiah(number) {
    return new Intl.NumberFormat('id-ID').format(number);
}

function openDetailModal(item) {
    document.getElementById('modal-id').value = item.id;
    document.getElementById('modal-img').src = item.img;
    document.getElementById('modal-nama').innerText = item.nama;
    document.getElementById('modal-deskripsi').innerText = item.deskripsi;
    document.getElementById('modal-stok').innerText = item.stok;
    document.getElementById('modal-jumlah').max = item.stok;
    
    document.getElementById('modal-denda-hari').innerText = formatRupiah(item.denda_hari);
    document.getElementById('modal-denda-rusak').innerText = formatRupiah(item.denda_rusak);
    document.getElementById('modal-denda-hilang').innerText = formatRupiah(item.denda_hilang);
    
    document.getElementById('modal-kode').innerText = item.kode;
    document.getElementById('modal-qr').src = "https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=" + encodeURIComponent(item.kode);
    
    document.getElementById('detailModal').classList.add('show');
    document.body.style.overflow = 'hidden'; 
}

function closeDetailModal() {
    document.getElementById('detailModal').classList.remove('show');
    document.body.style.overflow = '';
}

document.getElementById('detailModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeDetailModal();
    }
});

// Fungsi Edit Modal
function openEditModal(item) {
    document.getElementById('edit-id').value = item.id;
    document.getElementById('edit-nama').value = item.nama;
    document.getElementById('edit-stok').value = item.stok;
    document.getElementById('edit-denda-hari').value = item.denda_hari;
    document.getElementById('edit-denda-rusak').value = item.denda_rusak;
    document.getElementById('edit-denda-hilang').value = item.denda_hilang;
    document.getElementById('edit-deskripsi').value = item.deskripsi;
    
    document.getElementById('editModal').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeEditModal() {
    document.getElementById('editModal').classList.remove('show');
    document.body.style.overflow = '';
}

document.getElementById('editModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeEditModal();
    }
});

document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('search-input');
    const noResults = document.getElementById('no-results');
    
    if (searchInput) {
        let cardCache = [];
        const cards = document.querySelectorAll('.alat-card:not(#no-results):not(.no-data)');
        
        // Buat cache data teks di awal agar pencarian tidak membaca DOM berulang kali
        for (let i = 0; i < cards.length; i++) {
            const card = cards[i];
            const titleEl = card.querySelector('h4');
            const codeEl = card.querySelector('p');
            const title = titleEl ? titleEl.textContent : '';
            const code = codeEl ? codeEl.textContent : '';
            cardCache.push({
                element: card,
                text: (title + ' ' + code).toLowerCase()
            });
        }
        
        function debounce(func, delay) {
            let timeoutId;
            return function(...args) {
                if (timeoutId) clearTimeout(timeoutId);
                timeoutId = setTimeout(() => {
                    func.apply(this, args);
                }, delay);
            };
        }
        
        // OPTIMASI PENCARIAN: Menggunakan classList toggling (menghindari layout thrashing)
        const filterCards = debounce(function() {
            const query = searchInput.value.toLowerCase().trim();
            let visibleCount = 0;
            
            // Loop berjalan cepat di memory, browser hanya melakukan render ulang sekali di akhir
            for (let i = 0; i < cardCache.length; i++) {
                const item = cardCache[i];
                if (item.text.includes(query)) {
                    item.element.classList.remove('is-hidden');
                    visibleCount++;
                } else {
                    item.element.classList.add('is-hidden');
                }
            }
            
            if (noResults) {
                noResults.style.display = (visibleCount === 0 && query !== '') ? '' : 'none';
            }
        }, 150); // Dikurangi ke 150ms agar pencarian terasa instan namun tetap hemat resource

        searchInput.addEventListener('input', filterCards);
        
        const urlParams = new URLSearchParams(window.location.search);
        const searchQuery = urlParams.get('search');
        if (searchQuery) {
            searchInput.value = searchQuery;
            filterCards();
        }
    }
});

// Fungsi Tambah Modal
function openTambahModal() {
    document.getElementById('tambahModal').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeTambahModal() {
    document.getElementById('tambahModal').classList.remove('show');
    document.body.style.overflow = '';
}

document.getElementById('tambahModal').addEventListener('click', function(e) {
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
            "reader", { fps: 10, qrbox: {width: 250, height: 250} }, false);
            
        function onScanSuccess(decodedText, decodedResult) {
            const queryKode = decodedText.trim();
            const foundItem = alatData.find(a => a.kode === queryKode);
            
            if (foundItem) {
                closeScannerModal();
                // Memberikan jeda waktu agar modal kamera tertutup sempurna sebelum membuka modal detail
                setTimeout(() => {
                    openDetailModal(foundItem);
                }, 400);
            } else {
                alert("Alat dengan kode " + queryKode + " tidak ditemukan di katalog.");
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
        html5QrcodeScanner.clear().catch(error => {
            console.error("Gagal mematikan kamera: ", error);
        });
        html5QrcodeScanner = null;
    }
}

document.getElementById('scannerModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeScannerModal();
    }
});
</script>

<?php include 'includes/footer.php'; ?>