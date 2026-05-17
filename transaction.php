<!DOCTYPE html>
<html>

<head>
<link rel="stylesheet"
href="css/style.css">
</head>

<body>

<div class="layout">

<?php include
'components/sidebar.php'; ?>

<div class="content">

<?php include
'components/navbar.php'; ?>

<h1>

Riwayat Transaksi

</h1>

<table class="tbl">

<tr>

<th>ID</th>

<th>User</th>

<th>Alat</th>

<th>Tanggal</th>

<th>Status</th>

</tr>

<tr>

<td>TRX001</td>

<td>Budi</td>

<td>Microscope</td>

<td>20 Mei</td>

<td>

<span class="done">

Selesai

</span>

</td>

</tr>

<tr>

<td>TRX002</td>

<td>Siti</td>

<td>Beaker</td>

<td>21 Mei</td>

<td>

<span class="wait">

Dipinjam

</span>

</td>

</tr>

</table>

</div>

</div>

</body>

</html>