<?php
$current = basename($_SERVER['PHP_SELF']);
$role = isset($_SESSION['role']) ? $_SESSION['role'] : 'user';
?>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-header" id="burger-toggle" style="cursor: pointer;">
        <i class="fa-solid fa-desktop"></i>
        <span>UNIBI LAB</span>
    </div>
    <div class="sidebar-nav">
        <a href="dashboard.php" class="<?= $current=='dashboard.php' ? 'active':'' ?>">
            <i class="fa-solid fa-house"></i>
            <span>Beranda</span>
        </a>

        <a href="katalog.php" class="<?= in_array($current, ['katalog.php', 'detail_alat.php', 'tambah_alat.php', 'edit_alat.php']) ? 'active':'' ?>">
            <i class="fa-solid fa-box"></i>
            <span>Katalog</span>
        </a>

        <?php if ($role === 'admin'): ?>

            <a href="peminjaman.php" class="<?= $current=='peminjaman.php' ? 'active':'' ?>">
                <i class="fa-solid fa-list"></i>
                <span>Pinjaman</span>
            </a>
        <?php else: ?>
            <a href="peminjaman.php" class="<?= $current=='peminjaman.php' ? 'active':'' ?>">
                <i class="fa-solid fa-clock-rotate-left"></i>
                <span>Riwayat</span>
            </a>
        <?php endif; ?>

    </div>
</aside>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const burger = document.getElementById('burger-toggle');
    const sidebar = document.getElementById('sidebar');
    
    if (burger && sidebar) {
        burger.addEventListener('click', function() {
            sidebar.classList.toggle('collapsed');
        });
    }
});
</script>