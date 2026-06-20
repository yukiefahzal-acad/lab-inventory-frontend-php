<?php
session_start();
$role = $_SESSION['role'] ?? 'user';
if ($role !== 'admin') {
    die("Akses ditolak.");
}

$qr_codes = $_POST['qr_codes'] ?? [];

if (empty($qr_codes)) {
    echo "<script>alert('Tidak ada QR code yang dipilih untuk dicetak.'); window.close();</script>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Cetak QR Code</title>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Manrope', Arial, sans-serif;
            margin: 20px;
            background: #fff;
            color: #0f172a;
        }

        .qr-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 20px;
        }

        .qr-item {
            text-align: center;
            border: 1px dashed #cbd5e1;
            padding: 15px;
            border-radius: 12px;
            page-break-inside: avoid;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .qr-item img {
            width: 120px;
            height: 120px;
            margin-bottom: 12px;
        }

        .kode {
            font-weight: 800;
            font-size: 16px;
            color: #0f172a;
        }

        .nama {
            font-size: 13px;
            color: #64748b;
            margin-top: 5px;
            font-weight: 600;
            word-wrap: break-word;
            max-width: 100%;
        }

        @media print {
            .no-print {
                display: none !important;
            }

            body {
                margin: 0;
            }

            .qr-item {
                border: 1px solid #000;
                border-radius: 0;
            }
        }

        .btn {
            background: #7dd3fc;
            color: #0f172a;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            transition: 0.2s;
        }

        .btn:hover {
            background: #60a5fa;
            color: #fff;
        }

        .btn-cancel {
            background: #f1f5f9;
            color: #64748b;
        }

        .btn-cancel:hover {
            background: #e2e8f0;
            color: #0f172a;
        }
    </style>
</head>

<body onload="setTimeout(() => { window.print(); }, 500)">
    <div class="no-print" style="margin-bottom: 25px; display: flex; gap: 10px;">
        <button onclick="window.print()" class="btn">🖨️</button>
        <button onclick="window.close()" class="btn btn-cancel">Tutup</button>
    </div>

    <div class="qr-grid">
        <?php foreach ($qr_codes as $item):
            $parts = explode('|', $item);
            if (count($parts) >= 3) {
                $kode = htmlspecialchars($parts[0]);
                $nama = htmlspecialchars($parts[1]);
                $qr_string = htmlspecialchars($parts[2]);
                // Men-generate QR Code menggunakan API publik
                $qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . urlencode($qr_string);
                ?>
                <div class="qr-item">
                    <img src="<?= $qr_url ?>" alt="<?= $qr_string ?>">
                    <div class="kode"><?= $qr_string ?></div>
                    <div class="nama"><?= $nama ?></div>
                </div>
                <?php
            }
        endforeach; ?>
    </div>
</body>

</html>