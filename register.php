<?php
include 'config/koneksi.php';

if(isset($_POST['daftar'])){
    $nama = $_POST['nama'];
    $nim = $_POST['nim'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];

    if ($password !== $password_confirm) {
        $swal_error = 'Konfirmasi password tidak cocok!';
    } else {
        $response = call_api('POST', '/api/register', [
            'nama' => $nama,
            'nim_nip' => $nim,
            'email' => $email,
            'password' => $password
        ]);

        if (isset($response['status']) && $response['status'] === 'success') {
            $swal_success = 'Pendaftaran berhasil! Silakan login.';
        } else {
            $swal_error = htmlspecialchars($response['message'] ?? 'Pendaftaran gagal');
        }
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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

        <div class="auth-footer" style="text-align: center; margin-top: 20px;">
            Sudah punya akun? <a href="login.php" class="link-cta">Masuk di sini</a>
        </div>
    </div>
</div>

<?php if(isset($swal_error)): ?>
<script>
    Swal.fire({
        icon: 'error',
        title: 'Oops...',
        text: <?= json_encode($swal_error) ?>,
        confirmButtonColor: '#7dd3fc'
    });
</script>
<?php endif; ?>

<?php if(isset($swal_success)): ?>
<script>
    Swal.fire({
        icon: 'success',
        title: 'Berhasil',
        text: <?= json_encode($swal_success) ?>,
        confirmButtonColor: '#7dd3fc'
    }).then((result) => {
        window.location.href = 'login.php';
    });
</script>
<?php endif; ?>

</body>
</html>