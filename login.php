<?php

include 'config/koneksi.php';

if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header("Location: login.php");
    exit;
}

if(isset($_POST['login'])){

    $email = $_POST['email'];
    // Do not md5 the password here, the API probably handles hashing/checking raw password
    $password = $_POST['password'];

    $response = call_api('POST', '/api/login', [
        'email' => $email,
        'password' => $password
    ]);

    if(isset($response['token']) || (isset($response['status']) && $response['status'] === 'success')){
        // Fallback for token location
        $token = $response['token'] ?? ($response['data']['token'] ?? '');
        $_SESSION['token'] = $token;

        // Try to get user data from response or decode JWT
        $userData = $response['user'] ?? ($response['data']['user'] ?? null);
        
        if (!$userData && isset($response['id']) && isset($response['role'])) {
            $userData = $response;
        }
        
        if (!$userData && $token) {
            $parts = explode('.', $token);
            if (count($parts) === 3) {
                $payload = json_decode(base64_decode($parts[1]), true);
                $userData = $payload['data'] ?? $payload;
            }
        }

        $_SESSION['id'] = $userData['id'] ?? $userData['sub'] ?? 1;
        $_SESSION['nama'] = $userData['nama'] ?? $userData['name'] ?? 'User';
        $_SESSION['role'] = $userData['role'] ?? 'user';
        $_SESSION['email'] = $userData['email'] ?? $email;
        $_SESSION['nim'] = $userData['nim_nip'] ?? $userData['nim'] ?? '-';

        header("Location: dashboard.php");
        exit;
    }else{
        $error_msg = htmlspecialchars($response['message'] ?? 'Login gagal');
        echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'error',
                title: 'Login Gagal',
                text: '$error_msg',
                confirmButtonColor: '#7dd3fc'
            });
        });
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
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

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

<div class="auth-footer" style="text-align: center; margin-top: 20px;">
Belum punya akun? <a href="register.php" class="link-cta">Daftar Sekarang</a>
</div>

</div>

</div>

</body>
</html>