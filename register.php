<?php
include 'config/koneksi.php';

if(isset($_POST['daftar'])){
    $nama = $_POST['nama'];
    $nim = $_POST['nim'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];

    if ($password !== $password_confirm) {
        echo "<script>alert('Konfirmasi password tidak cocok!');</script>";
    } else {
        $password_md5 = md5($password);
        register_dummy_user($nama, $nim, $email, $password_md5);
        echo "<script>
            alert('Pendaftaran berhasil! Silakan login.');
            window.location.href = 'login.php';
        </script>";
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register UNIBI LAB</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="container-auth">
    <div class="auth-card">
        <h2 class="title">Daftar Akun Baru</h2>
        <p class="subtitle">Isi data untuk mulai meminjam alat</p>

        <form action="register.php" method="POST">
            <div class="form-group">
                <label>Nama Lengkap</label>
                <input type="text" name="nama" class="form-control" required>
            </div>

            <div class="form-group">
                <label>NIM</label>
                <input type="text" name="nim" class="form-control" required>
            </div>

            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" class="form-control" required>
            </div>

            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>

            <div class="form-group">
                <label>Konfirmasi Password</label>
                <input type="password" name="password_confirm" class="form-control" required>
            </div>

            <button type="submit" name="daftar" class="btn-primary">Daftar</button>
        </form>

        <div class="auth-footer">
            Sudah punya akun?
            <a href="login.php">Masuk di sini</a>
        </div>
    </div>
</div>

</body>
</html>