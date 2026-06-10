<?php

include 'config/koneksi.php';

if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header("Location: login.php");
    exit;
}

if(isset($_POST['login'])){

    $email = $_POST['email'];
    $password = md5($_POST['password']);

    $data = get_dummy_user($email, $password);

    if($data){
        $_SESSION['id']=$data['id'];
        $_SESSION['nama']=$data['nama'];
        $_SESSION['role']=$data['role'];

        header("Location: dashboard.php");
        exit;
    }else{
        echo "<script>
        alert('Login gagal');
        </script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Login UNIBI LAB</title>

<link rel="stylesheet" href="assets/css/style.css">

</head>
<body>

<div class="container-auth">

<div class="auth-card">

<img src="https://ap2tpi.id/wp-content/uploads/2025/02/member-unibi.png"
class="logo">

<h2 class="title">
UNIBI LAB
</h2>

<p class="subtitle">
Sistem Peminjaman Alat Laboratorium
</p>

<form method="POST">

<div class="form-group">
<label>Email</label>
<input
type="email"
name="email"
class="form-control"
required>
</div>

<div class="form-group">
<label>Password</label>
<input
type="password"
name="password"
class="form-control"
required>
</div>

<button
type="submit"
name="login"
class="btn-primary">
Masuk
</button>

</form>

<div class="auth-footer">
Belum punya akun?
<a href="register.php">
Daftar Sekarang
</a>
</div>

</div>

</div>

</body>
</html>