<?php
session_start();
require_once "koneksi.php";
require_once "fungsi.php";

// Cek login dan pastikan role-nya adalah 'admin'
if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$nama_admin = $_SESSION['nama_lengkap'];

// Proses tombol Setujui / Tolak (dikirim lewat form POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id    = (int) $_POST['id'];
    $aksi  = $_POST['aksi'];   // "acc" atau "tolak"
    $pesan = "";

    // Ambil data pengajuan yang dipilih
    $stmt = mysqli_prepare($koneksi, "SELECT * FROM pengajuan_cuti WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $cuti = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    // Hanya pengajuan yang masih Pending yang boleh diproses
    if ($cuti && $cuti['status'] == "Pending") {
        $status_baru = ($aksi == "acc") ? "Disetujui" : "Ditolak";

        // Ubah status pengajuan
        $stmt = mysqli_prepare($koneksi, "UPDATE pengajuan_cuti SET status = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "si", $status_baru, $id);
        mysqli_stmt_execute($stmt);

        // Jika Cuti Tahunan disetujui, kurangi sisa cuti sebanyak hari kerjanya
        if ($status_baru == "Disetujui" && $cuti['jenis_cuti'] == "Cuti Tahunan") {
            $hari_kerja = hitung_hari_kerja($cuti['tanggal_mulai'], $cuti['tanggal_selesai']);
            $uid        = $cuti['user_id'];

            $stmt = mysqli_prepare($koneksi, "UPDATE users SET sisa_cuti = sisa_cuti - ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "ii", $hari_kerja, $uid);
            mysqli_stmt_execute($stmt);
        }

        $pesan = "Pengajuan cuti berhasil " . strtolower($status_baru) . ".";
    }

    header("Location: dashboard_admin.php?pesan=" . urlencode($pesan));
    exit;
}

// Filter & pencarian (dikirim lewat URL, metode GET)
$status = isset($_GET['status']) ? $_GET['status'] : "";
$cari   = isset($_GET['cari']) ? trim($_GET['cari']) : "";

// Tanda % artinya "apa saja". Kalau status kosong, hasilnya "%%" sehingga semua status cocok.
$status_like = "%" . $status . "%";
$cari_like   = "%" . $cari . "%";

// Ambil data pengajuan cuti digabung dengan nama karyawan dari tabel users (INNER JOIN)
$stmt = mysqli_prepare($koneksi, "SELECT pengajuan_cuti.*, users.nama_lengkap
    FROM pengajuan_cuti
    INNER JOIN users ON pengajuan_cuti.user_id = users.id
    WHERE users.nama_lengkap LIKE ? AND pengajuan_cuti.status LIKE ?
    ORDER BY pengajuan_cuti.id DESC");
mysqli_stmt_bind_param($stmt, "ss", $cari_like, $status_like);
mysqli_stmt_execute($stmt);
$daftar = mysqli_stmt_get_result($stmt);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Dashboard Admin - Aplikasi Cuti</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

    <header>
        <h2>Dashboard Admin / HRD</h2>
        <div>
            <span>Halo, <?= htmlspecialchars($nama_admin) ?></span>
            <a href="logout.php">Logout</a>
        </div>
    </header>

    <div class="container container-lebar">
        <?php if (isset($_GET['pesan']) && $_GET['pesan'] != ""): ?>
        <p class="pesan-sukses"><?= htmlspecialchars($_GET['pesan']) ?></p>
        <?php endif; ?>

        <h3>Daftar Permohonan Cuti Karyawan</h3>

        <form action="dashboard_admin.php" method="GET" class="filter-form">
            <div>
                <label>Status</label><br>
                <select name="status">
                    <option value="">Semua status</option>
                    <option value="Pending" <?= $status == "Pending" ? "selected" : "" ?>>Pending</option>
                    <option value="Disetujui" <?= $status == "Disetujui" ? "selected" : "" ?>>Disetujui</option>
                    <option value="Ditolak" <?= $status == "Ditolak" ? "selected" : "" ?>>Ditolak</option>
                </select>
            </div>
            <div>
                <label>Nama karyawan</label><br>
                <input type="text" name="cari" value="<?= htmlspecialchars($cari) ?>" placeholder="Cari nama...">
            </div>
            <div>
                <button type="submit" class="btn-kecil btn-edit">Terapkan</button>
                <a href="dashboard_admin.php" class="btn-kecil btn-reset">Reset</a>
            </div>
        </form>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama Karyawan</th>
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
                    <?php while ($row = mysqli_fetch_assoc($daftar)): ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><?= htmlspecialchars($row['nama_lengkap']) ?></td>
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
                            <form action="dashboard_admin.php" method="POST" class="form-inline">
                                <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                <button type="submit" name="aksi" value="acc" class="btn-kecil btn-setuju"
                                    onclick="return confirm('Setujui cuti ini?')">Setujui</button>
                                <button type="submit" name="aksi" value="tolak" class="btn-kecil btn-tolak"
                                    onclick="return confirm('Tolak cuti ini?')">Tolak</button>
                            </form>
                            <?php else: ?>
                            <span>Selesai</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>

                    <?php if ($no == 1): ?>
                    <tr>
                        <td colspan="10">Tidak ada pengajuan cuti yang cocok.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</body>

</html>
