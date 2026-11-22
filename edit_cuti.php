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
$id           = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$error        = "";

// Ambil data pengajuan: harus milik karyawan ini dan statusnya masih Pending
$stmt = mysqli_prepare($koneksi, "SELECT * FROM pengajuan_cuti WHERE id = ? AND user_id = ? AND status = 'Pending'");
mysqli_stmt_bind_param($stmt, "ii", $id, $user_id);
mysqli_stmt_execute($stmt);
$data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$data) {
    header("Location: dashboard_karyawan.php?pesan=" . urlencode("Pengajuan tidak ditemukan atau sudah diproses."));
    exit;
}

// Isi awal form = data lama
$jenis_cuti      = $data['jenis_cuti'];
$tanggal_mulai   = $data['tanggal_mulai'];
$tanggal_selesai = $data['tanggal_selesai'];
$alasan          = $data['alasan'];
$bukti_lama      = $data['bukti_surat'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $jenis_cuti      = $_POST['jenis_cuti'];
    $tanggal_mulai   = $_POST['tanggal_mulai'];
    $tanggal_selesai = $_POST['tanggal_selesai'];
    $alasan          = trim($_POST['alasan']);

    // Ambil sisa cuti karyawan
    $stmt = mysqli_prepare($koneksi, "SELECT sisa_cuti FROM users WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    // 1. Validasi isian form
    $error = validasi_pengajuan($jenis_cuti, $tanggal_mulai, $tanggal_selesai, $alasan, $user['sisa_cuti']);

    // 2. Validasi file bukti (hanya kalau ada file baru yang diunggah)
    $ada_file = ($_FILES['bukti_surat']['error'] != UPLOAD_ERR_NO_FILE);

    if ($error == "" && $ada_file) {
        $error = cek_file_bukti($_FILES['bukti_surat']);
    }
    if ($error == "" && $jenis_cuti == "Cuti Sakit" && !$ada_file && $bukti_lama == "") {
        $error = "Cuti Sakit wajib melampirkan surat dokter (JPG/PNG).";
    }

    // 3. Kalau semua valid, ubah data di database
    if ($error == "") {
        $nama_file = $bukti_lama;   // tidak ada file baru -> pakai file lama
        if ($ada_file) {
            $nama_file = simpan_file_bukti($_FILES['bukti_surat']);
        }

        $stmt = mysqli_prepare($koneksi, "UPDATE pengajuan_cuti SET jenis_cuti = ?, tanggal_mulai = ?, tanggal_selesai = ?, alasan = ?, bukti_surat = ? WHERE id = ? AND user_id = ? AND status = 'Pending'");
        mysqli_stmt_bind_param($stmt, "sssssii", $jenis_cuti, $tanggal_mulai, $tanggal_selesai, $alasan, $nama_file, $id, $user_id);
        mysqli_stmt_execute($stmt);

        if ($ada_file) {
            hapus_file_bukti($bukti_lama);   // file lama sudah diganti, jadi dihapus
        }

        header("Location: dashboard_karyawan.php?pesan=" . urlencode("Pengajuan cuti berhasil diubah!"));
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Edit Pengajuan Cuti</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

    <div class="container">
        <h2>Edit Pengajuan Cuti</h2>

        <?php if ($error != ""): ?>
        <p class="pesan-error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <form action="edit_cuti.php?id=<?= $id ?>" method="POST" enctype="multipart/form-data">
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
                <?php if ($bukti_lama != ""): ?>
                <p class="catatan">File saat ini: <a href="uploads/<?= htmlspecialchars($bukti_lama) ?>" target="_blank">lihat bukti</a>.
                    Pilih file baru hanya jika ingin menggantinya.</p>
                <?php endif; ?>
            </div><br>

            <button type="submit">Simpan Perubahan</button>
        </form>

        <br>
        <a href="dashboard_karyawan.php">&larr; Kembali ke Dashboard</a>
    </div>

</body>

</html>
