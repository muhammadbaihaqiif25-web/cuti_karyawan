<?php
session_start();
require_once "koneksi.php";
require_once "fungsi.php";

// Cek login dan pastikan role-nya adalah 'karyawan'
if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'karyawan') {
    header("Location: login.php");
    exit;
}

$user_id      = $_SESSION['user_id'];
$nama_lengkap = $_SESSION['nama_lengkap'];
$error        = "";

// Isi awal form (kosong). Kalau ada error, isian tetap tampil supaya tidak mengetik ulang.
$jenis_cuti      = "";
$tanggal_mulai   = "";
$tanggal_selesai = "";
$alasan          = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $jenis_cuti      = $_POST['jenis_cuti'];
    $tanggal_mulai   = $_POST['tanggal_mulai'];
    $tanggal_selesai = $_POST['tanggal_selesai'];
    $alasan          = trim($_POST['alasan']);

    // Ambil sisa cuti karyawan (dipakai untuk validasi Cuti Tahunan)
    $stmt = mysqli_prepare($koneksi, "SELECT sisa_cuti FROM users WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    // 1. Validasi isian form
    $error = validasi_pengajuan($jenis_cuti, $tanggal_mulai, $tanggal_selesai, $alasan, $user['sisa_cuti']);

    // 2. Validasi file bukti (kalau ada file yang diunggah)
    $ada_file = ($_FILES['bukti_surat']['error'] != UPLOAD_ERR_NO_FILE);

    if ($error == "" && $ada_file) {
        $error = cek_file_bukti($_FILES['bukti_surat']);
    }
    if ($error == "" && $jenis_cuti == "Cuti Sakit" && !$ada_file) {
        $error = "Cuti Sakit wajib melampirkan surat dokter (JPG/PNG).";
    }

    // 3. Kalau semua valid, simpan ke database
    if ($error == "") {
        $nama_file = null;
        if ($ada_file) {
            $nama_file = simpan_file_bukti($_FILES['bukti_surat']);
        }

        $stmt = mysqli_prepare($koneksi, "INSERT INTO pengajuan_cuti (user_id, jenis_cuti, tanggal_mulai, tanggal_selesai, alasan, bukti_surat, status) VALUES (?, ?, ?, ?, ?, ?, 'Pending')");
        mysqli_stmt_bind_param($stmt, "isssss", $user_id, $jenis_cuti, $tanggal_mulai, $tanggal_selesai, $alasan, $nama_file);
        mysqli_stmt_execute($stmt);

        header("Location: dashboard_karyawan.php?pesan=" . urlencode("Pengajuan cuti berhasil dikirim!"));
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Form Pengajuan Cuti</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

    <div class="container">
        <h2>Formulir Pengajuan Cuti</h2>

        <?php if ($error != ""): ?>
        <p class="pesan-error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <form action="form_cuti.php" method="POST" enctype="multipart/form-data">
            <div>
                <label>Nama Karyawan</label><br>
                <input type="text" value="<?= htmlspecialchars($nama_lengkap) ?>" readonly>
            </div><br>

            <div>
                <label>Jenis Cuti</label><br>
                <select name="jenis_cuti" required>
                    <option value="">-- Pilih Jenis Cuti --</option>
                    <?php foreach (JENIS_CUTI as $jenis): ?>
                    <option value="<?= $jenis ?>" <?= $jenis_cuti == $jenis ? "selected" : "" ?>><?= $jenis ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="catatan">Hanya Cuti Tahunan yang memotong sisa cuti. Sabtu dan Minggu tidak dihitung.</p>
            </div><br>

            <div>
                <label>Tanggal Mulai</label><br>
                <input type="date" name="tanggal_mulai" value="<?= htmlspecialchars($tanggal_mulai) ?>" required>
            </div><br>

            <div>
                <label>Tanggal Selesai</label><br>
                <input type="date" name="tanggal_selesai" value="<?= htmlspecialchars($tanggal_selesai) ?>" required>
            </div><br>

            <div>
                <label>Alasan Cuti</label><br>
                <textarea name="alasan" rows="4" required><?= htmlspecialchars($alasan) ?></textarea>
            </div><br>

            <div>
                <label>Surat Dokter / Bukti (JPG atau PNG, maks. 2 MB)</label><br>
                <input type="file" name="bukti_surat" accept=".jpg,.jpeg,.png">
                <p class="catatan">Wajib untuk Cuti Sakit, opsional untuk jenis cuti lain.</p>
            </div><br>

            <button type="submit">Kirim Pengajuan</button>
        </form>

        <br>
        <a href="dashboard_karyawan.php">&larr; Kembali ke Dashboard</a>
    </div>

</body>

</html>
