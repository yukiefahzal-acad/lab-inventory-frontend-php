<!DOCTYPE html>
<html>

<head>

<link rel="stylesheet"
href="css/style.css">

</head>

<body>

<div class="topbar">

<div class="logo-area">

<div class="cube">⬡</div>

<div>

<h2>Lab System</h2>

<p>Peminjaman Alat</p>

</div>

</div>

<div class="page-title">

Denda

</div>

<div class="avatar">

A

</div>

</div>


<div class="main">

<?php include
'components/sidebar.php'; ?>


<div class="dash-content">

<h1>

Data Denda

</h1>

<table class="tbl">

<tr>

<th>Nama</th>

<th>Alat</th>

<th>Denda</th>

<th>Status</th>

</tr>

<tr>

<td>Budi</td>

<td>Mikroskop</td>

<td>Rp 50.000</td>

<td>

<span class="wait">

Belum Bayar

</span>

</td>

</tr>

<tr>

<td>Siti</td>

<td>Beaker</td>

<td>Rp 0</td>

<td>

<span class="done">

Lunas

</span>

</td>

</tr>

</table>

</div>

</div>

</body>

</html>