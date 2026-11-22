<?php
session_start();
require_once "koneksi.php";
require_once "fungsi.php";

// Cek login dan role karyawan
if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'karyawan') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Proses tombol "Batalkan" (menghapus pengajuan yang masih Pending)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id    = (int) $_POST['id'];
    $pesan = "";

    // Ambil nama file bukti dulu (pastikan milik sendiri dan masih Pending)
    $stmt = mysqli_prepare($koneksi, "SELECT bukti_surat FROM pengajuan_cuti WHERE id = ? AND user_id = ? AND status = 'Pending'");
    mysqli_stmt_bind_param($stmt, "ii", $id, $user_id);
    mysqli_stmt_execute($stmt);
    $data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if ($data) {
        $stmt = mysqli_prepare($koneksi, "DELETE FROM pengajuan_cuti WHERE id = ? AND user_id = ?");
        mysqli_stmt_bind_param($stmt, "ii", $id, $user_id);
        mysqli_stmt_execute($stmt);

        hapus_file_bukti($data['bukti_surat']);   // file suratnya ikut dihapus
        $pesan = "Pengajuan cuti berhasil dibatalkan.";
    }

    header("Location: dashboard_karyawan.php?pesan=" . urlencode($pesan));
    exit;
}

// Ambil sisa cuti karyawan
$stmt = mysqli_prepare($koneksi, "SELECT sisa_cuti FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

// Ambil riwayat cuti karyawan ini
$stmt = mysqli_prepare($koneksi, "SELECT * FROM pengajuan_cuti WHERE user_id = ? ORDER BY id DESC");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$riwayat = mysqli_stmt_get_result($stmt);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Dashboard Karyawan</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

    <header>
        <h2>Dashboard Karyawan</h2>
        <div>
            <span>Halo, <?= htmlspecialchars($_SESSION['nama_lengkap']) ?></span>
            <a href="logout.php">Logout</a>
        </div>
    </header>

    <div class="container container-lebar">
        <?php if (isset($_GET['pesan']) && $_GET['pesan'] != ""): ?>
        <p class="pesan-sukses"><?= htmlspecialchars($_GET['pesan']) ?></p>
        <?php endif; ?>

        <div class="info-cuti">
            <div>
                <h3>Sisa Cuti Tahunan: <?= $user['sisa_cuti'] ?> Hari</h3>
                <p class="catatan">Dihitung per hari kerja (Senin - Jumat).</p>
            </div>
            <a href="form_cuti.php">+ Ajukan Cuti Baru</a>
        </div>

        <h3>Riwayat Pengajuan Cuti</h3>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Jenis Cuti</th>
                        <th>Tanggal Mulai</th>
                        <th>Tanggal Selesai</th>
                        <th>Hari Kerja</th>
                        <th>Alasan</th>
                        <th>Bukti</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no = 1; ?>
                    <?php while ($row = mysqli_fetch_assoc($riwayat)): ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><?= htmlspecialchars($row['jenis_cuti']) ?></td>
                        <td><?= $row['tanggal_mulai'] ?></td>
                        <td><?= $row['tanggal_selesai'] ?></td>
                        <td><?= hitung_hari_kerja($row['tanggal_mulai'], $row['tanggal_selesai']) ?></td>
                        <td><?= htmlspecialchars($row['alasan']) ?></td>
                        <td>
                            <?php if ($row['bukti_surat'] != ""): ?>
                            <a href="uploads/<?= htmlspecialchars($row['bukti_surat']) ?>" target="_blank">Lihat</a>
                            <?php else: ?>
                            -
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($row['status']) ?></td>
                        <td>
                            <?php if ($row['status'] == 'Pending'): ?>
                            <a href="edit_cuti.php?id=<?= $row['id'] ?>" class="btn-kecil btn-edit">Edit</a>
                            <form action="dashboard_karyawan.php" method="POST" class="form-inline"
                                onsubmit="return confirm('Batalkan pengajuan cuti ini?')">
                                <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                <button type="submit" class="btn-kecil btn-hapus">Batalkan</button>
                            </form>
                            <?php else: ?>
                            -
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

</body>

</html>
