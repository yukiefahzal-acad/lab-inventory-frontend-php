<?php
include_once __DIR__ . '/../config/koneksi.php';

$current_page = basename($_SERVER['PHP_SELF']);
if ($current_page !== 'login.php' && $current_page !== 'register.php') {
    if (!isset($_SESSION['id'])) {
        header("Location: login.php");
        exit;
    }
}

$page_title = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') ? "Admin UNIBI LAB" : "UNIBI LAB";
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <?php if ($current_page !== 'login.php' && $current_page !== 'register.php'): ?>
        <?php
        $role_header = $_SESSION['role'] ?? 'user';
        $user_id_header = $_SESSION['id'] ?? null;
        $user_nama_header = $_SESSION['nama'] ?? '';
        $user_email_header = '';
        $user_nim_header = '';

        if ($user_id_header) {
            // Ideally this comes from session or a profile API endpoint
            $user_email_header = $_SESSION['email'] ?? '';
            $user_nim_header = $_SESSION['nim'] ?? '';
        }

        $user_total_denda_header = 0;
        if ($role_header !== 'admin') {
            $res_denda = call_api('GET', '/api/user/denda');
            $denda_list = $res_denda['data'] ?? [];
            foreach ($denda_list as $denda) {
                if (($denda['status_pembayaran'] ?? $denda['status'] ?? '') === 'Belum Lunas') {
                    $user_total_denda_header += ($denda['jumlah_denda'] ?? $denda['denda'] ?? 0);
                }
            }
        }
        ?>

        <?php
        $page_titles_map = [
            'dashboard.php' => 'Dashboard',
            'katalog.php' => ($role_header === 'admin' ? 'Kelola Alat' : 'Katalog Alat'),
            'riwayat.php' => 'Riwayat Peminjaman',
            'peminjaman.php' => 'Manajemen Peminjaman',
            'verifikasi.php' => 'Verifikasi Pengembalian',
            'scanqr.php' => 'Scan QR',
            'tambah_alat.php' => 'Tambah Alat',
            'edit_alat.php' => 'Edit Alat'
        ];
        $display_topbar_title = $page_titles_map[$current_page] ?? 'UNIBI LAB';
        ?>

        <div class="app-wrapper">
            <?php include_once __DIR__ . '/navbar.php'; ?>
            <main class="main-content">
                <header class="topbar">

                    <div style="font-weight:800; font-size: 20px; color: #222;">
                        <?= htmlspecialchars($display_topbar_title) ?>
                    </div>

                    <div class="profile-dropdown-container">
                        <div class="profile-trigger" id="profile-dropdown-toggle"
                            style="display: flex; align-items: center; justify-content: center; gap: 12px;">
                            <div class="profile-avatar-small">
                                <i class="fa-solid <?= $role_header === 'admin' ? 'fa-user-shield' : 'fa-user' ?>"></i>
                            </div>
                            <span class="profile-name"
                                style="line-height: 1; margin: 0; display: flex; align-items: center;"><?= htmlspecialchars($user_nama_header) ?></span>
                        </div>

                        <div class="profile-dropdown-menu" id="profile-dropdown-menu">
                            <div class="dropdown-header">
                                <p style="font-weight: 600; color: #333; margin-bottom: 5px;">
                                    <?= htmlspecialchars($user_email_header) ?>
                                </p>
                                <?php if ($role_header !== 'admin'): ?>
                                    <p><?= htmlspecialchars($user_nim_header) ?></p>
                                <?php endif; ?>
                            </div>
                            <?php if ($role_header !== 'admin'): ?>
                                <div class="dropdown-body">
                                    <p><span>Denda Belum Lunas</span> <b>Rp
                                            <?= number_format($user_total_denda_header, 0, ',', '.') ?></b></p>
                                    <p><span>Status</span>
                                        <?= $user_total_denda_header > 0 ? '<span style="color:#d60000; font-weight:700;">Belum Lunas</span>' : '<span style="color:#0d8d3a; font-weight:700;">Lunas</span>' ?>
                                    </p>
                                </div>
                            <?php endif; ?>
                            <div class="dropdown-footer">
                                <a href="login.php?action=logout" class="btn-logout-small"><i
                                        class="fa-solid fa-right-from-bracket"></i> Logout</a>
                            </div>
                        </div>
                    </div>
                </header>

                <script>
                    document.addEventListener('DOMContentLoaded', function () {
                        const profileToggle = document.getElementById('profile-dropdown-toggle');
                        const profileMenu = document.getElementById('profile-dropdown-menu');

                        if (profileToggle && profileMenu) {
                            profileToggle.addEventListener('click', function (e) {
                                e.stopPropagation();
                                profileMenu.classList.toggle('show');
                            });

                            document.addEventListener('click', function (e) {
                                if (!profileMenu.contains(e.target) && !profileToggle.contains(e.target)) {
                                    profileMenu.classList.remove('show');
                                }
                            });
                        }
                    });
                </script>
            <?php endif; ?>