<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "db_cuti_karyawan";

// Tampilkan error database dengan jelas (memudahkan mencari kesalahan)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$koneksi = mysqli_connect($host, $user, $pass, $db);

if (!$koneksi) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}
?>
