<?php
// Fungsi yang dipakai di beberapa halaman.

// Daftar jenis cuti (dipakai di form tambah, form edit, dan validasi)
const JENIS_CUTI = ["Cuti Tahunan", "Cuti Sakit", "Cuti Keperluan Penting"];


// 1. Hitung jumlah hari kerja (Senin - Jumat) dari tanggal mulai sampai selesai.
//    Sabtu dan Minggu tidak dihitung, jadi tidak memotong cuti tahunan.
function hitung_hari_kerja($mulai, $selesai)
{
    $jumlah  = 0;
    $tanggal = strtotime($mulai);
    $akhir   = strtotime($selesai);

    while ($tanggal <= $akhir) {
        $hari = date("N", $tanggal);   // 1 = Senin ... 7 = Minggu
        if ($hari <= 5) {
            $jumlah++;
        }
        $tanggal = strtotime("+1 day", $tanggal);
    }
    return $jumlah;
}


// 2. Cek isian form pengajuan cuti.
//    Hasilnya teks error, atau "" (kosong) kalau semua isian sudah benar.
function validasi_pengajuan($jenis, $mulai, $selesai, $alasan, $sisa_cuti)
{
    if (!in_array($jenis, JENIS_CUTI)) {
        return "Pilih jenis cuti terlebih dahulu.";
    }
    if ($mulai == "" || $selesai == "") {
        return "Tanggal mulai dan tanggal selesai wajib diisi.";
    }
    if ($selesai < $mulai) {
        return "Tanggal selesai tidak boleh lebih awal dari tanggal mulai.";
    }
    if ($alasan == "") {
        return "Alasan cuti wajib diisi.";
    }

    // Cuti Tahunan memotong jatah, jadi hari kerjanya tidak boleh melebihi sisa cuti
    if ($jenis == "Cuti Tahunan") {
        $hari_kerja = hitung_hari_kerja($mulai, $selesai);
        if ($hari_kerja == 0) {
            return "Tanggal yang dipilih hanya Sabtu/Minggu, tidak perlu mengajukan cuti tahunan.";
        }
        if ($hari_kerja > $sisa_cuti) {
            return "Sisa cuti tidak cukup. Sisa: $sisa_cuti hari, diajukan: $hari_kerja hari kerja.";
        }
    }
    return "";
}


// 3. Cek file bukti (surat dokter) yang diunggah: harus .jpg / .png dan maksimal 2 MB.
//    Hasilnya teks error, atau "" kalau file boleh disimpan.
function cek_file_bukti($file)
{
    if ($file['error'] != UPLOAD_ERR_OK) {
        return "Upload file gagal. Pastikan ukuran file tidak lebih dari 2 MB.";
    }
    if ($file['size'] > 2 * 1024 * 1024) {
        return "Ukuran file maksimal 2 MB.";
    }

    $ekstensi = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($ekstensi != "jpg" && $ekstensi != "jpeg" && $ekstensi != "png") {
        return "Format file harus .jpg atau .png.";
    }
    return "";
}


// 4. Simpan file ke folder uploads dengan nama unik. Hasilnya nama file yang tersimpan.
function simpan_file_bukti($file)
{
    if (!is_dir("uploads")) {
        mkdir("uploads");
    }

    $ekstensi  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $nama_baru = uniqid("bukti_") . "." . $ekstensi;
    move_uploaded_file($file['tmp_name'], "uploads/" . $nama_baru);

    return $nama_baru;
}


// 5. Hapus file bukti dari folder uploads
function hapus_file_bukti($nama_file)
{
    if ($nama_file != "" && file_exists("uploads/" . $nama_file)) {
        unlink("uploads/" . $nama_file);
    }
}
?>
