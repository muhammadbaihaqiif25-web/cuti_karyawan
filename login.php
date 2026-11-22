<?php
session_start();
require_once "koneksi.php";

// Jika sudah login, langsung arahkan sesuai role
if (isset($_SESSION['login']) && $_SESSION['login'] === true) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: dashboard_admin.php");
    } else {
        header("Location: dashboard_karyawan.php");
    }
    exit;
}

$error = "";
$saved_username = isset($_COOKIE['remember_username']) ? $_COOKIE['remember_username'] : "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $role     = $_POST['role'];

    // Cari user dengan PREPARED STATEMENT (aman dari SQL injection).
    // Langkahnya selalu sama: prepare -> bind -> execute -> ambil hasil.
    $stmt = mysqli_prepare($koneksi, "SELECT * FROM users WHERE username = ? AND role = ?");
    mysqli_stmt_bind_param($stmt, "ss", $username, $role);
    mysqli_stmt_execute($stmt);
    $hasil = mysqli_stmt_get_result($stmt);
    $user  = mysqli_fetch_assoc($hasil);

    // Cocokkan password yang diketik dengan HASH yang tersimpan di database
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['login']        = true;
        $_SESSION['user_id']      = $user['id'];
        $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
        $_SESSION['role']         = $user['role'];

        // Atur cookie username jika checkbox "Ingat username saya" dicentang
        if (isset($_POST['ingat_saya'])) {
            setcookie("remember_username", $user['username'], time() + (7 * 24 * 3600), "/");
        } else {
            setcookie("remember_username", "", time() - 3600, "/");
        }

        if ($user['role'] === 'admin') {
            header("Location: dashboard_admin.php");
        } else {
            header("Location: dashboard_karyawan.php");
        }
        exit;
    } else {
        $error = "Username, password, atau peran salah!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Login - Aplikasi Cuti</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

    <div class="login-box">
        <h2>Login Aplikasi Cuti</h2>

        <?php if ($error != ""): ?>
        <p class="pesan-error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <div>
                <label>Username</label><br>
                <input type="text" name="username" value="<?= htmlspecialchars($saved_username) ?>" required>
            </div><br>

            <div>
                <label>Password</label><br>
                <input type="password" name="password" required>
            </div><br>

            <div>
                <label>Login Sebagai</label><br>
                <select name="role" required>
                    <option value="">-- Pilih Peran --</option>
                    <option value="karyawan">Karyawan</option>
                    <option value="admin">Admin / HRD</option>
                </select>
            </div><br>

            <div>
                <label>
                    <input type="checkbox" name="ingat_saya" value="1" <?= $saved_username != "" ? "checked" : "" ?>>
                    Ingat username saya
                </label>
            </div><br>

            <button type="submit">Masuk</button>
        </form>

        <br>
        <p>Belum punya akun? <a href="register.php">Daftar di sini</a></p>
    </div>

</body>

</html>
