<?php
session_start();
require_once "koneksi.php";

// Jika sudah login, arahkan ke dashboard sesuai role
if (isset($_SESSION['login']) && $_SESSION['login'] === true) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: dashboard_admin.php");
    } else {
        header("Location: dashboard_karyawan.php");
    }
    exit;
}

$error  = "";
$sukses = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama_lengkap = trim($_POST['nama_lengkap']);
    $username     = trim($_POST['username']);
    $password     = $_POST['password'];
    $konfirmasi   = $_POST['konfirmasi_password'];

    // Validasi isian
    if ($nama_lengkap == "" || $username == "" || $password == "") {
        $error = "Semua field wajib diisi!";
    } elseif ($password != $konfirmasi) {
        $error = "Konfirmasi password tidak cocok!";
    } else {
        // Cek apakah username sudah dipakai
        $stmt = mysqli_prepare($koneksi, "SELECT id FROM users WHERE username = ?");
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $hasil = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($hasil) > 0) {
            $error = "Username sudah terdaftar, silakan gunakan username lain!";
        } else {
            // Password disimpan dalam bentuk HASH, bukan teks asli.
            // Role bawaan 'karyawan' dan jatah awal sisa_cuti 12 hari.
            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = mysqli_prepare($koneksi, "INSERT INTO users (nama_lengkap, username, password, role, sisa_cuti) VALUES (?, ?, ?, 'karyawan', 12)");
            mysqli_stmt_bind_param($stmt, "sss", $nama_lengkap, $username, $password_hash);
            mysqli_stmt_execute($stmt);

            $sukses = "Registrasi berhasil! Silakan login menggunakan akun Anda.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Register Akun Karyawan</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

    <div class="login-box">
        <h2>Daftar Akun Karyawan</h2>

        <?php if ($error != ""): ?>
        <p class="pesan-error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <?php if ($sukses != ""): ?>
        <p class="pesan-sukses"><?= htmlspecialchars($sukses) ?></p>
        <?php endif; ?>

        <form action="register.php" method="POST">
            <div>
                <label>Nama Lengkap</label><br>
                <input type="text" name="nama_lengkap" required>
            </div><br>

            <div>
                <label>Username</label><br>
                <input type="text" name="username" required>
            </div><br>

            <div>
                <label>Password</label><br>
                <input type="password" name="password" required>
            </div><br>

            <div>
                <label>Konfirmasi Password</label><br>
                <input type="password" name="konfirmasi_password" required>
            </div><br>

            <button type="submit">Daftar</button>
        </form>

        <br>
        <p>Sudah punya akun? <a href="login.php">Login di sini</a></p>
    </div>

</body>

</html>
