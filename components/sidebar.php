<div class="sidebar">

<a href="dashboard.php"
class="menu <?= basename($_SERVER['PHP_SELF'])=='dashboard.php'?'active':'' ?>">

🏠 Dashboard

</a>


<a href="catalog.php"
class="menu <?= basename($_SERVER['PHP_SELF'])=='catalog.php'?'active':'' ?>">

📦 Katalog Alat

</a>


<a href="borrow.php"
class="menu <?= basename($_SERVER['PHP_SELF'])=='borrow.php'?'active':'' ?>">

📅 Peminjaman

</a>


<a href="return.php"
class="menu <?= basename($_SERVER['PHP_SELF'])=='return.php'?'active':'' ?>">

↩ Pengembalian

</a>


<a href="fine.php"
class="menu <?= basename($_SERVER['PHP_SELF'])=='fine.php'?'active':'' ?>">

💲 Denda

</a>

</div>