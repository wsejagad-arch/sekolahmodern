<?php
/**
 * hapus-izin.php
 * Handler khusus untuk menghapus pengajuan izin siswa.
 * Hanya bisa diakses oleh admin (hak_akses = 1).
 * Setelah proses, redirect ke monitoring-izin dengan pesan flash.
 */

require_once __DIR__ . '/bootstrap.php';
require_login();

// Hanya admin
if (!is_admin()) {
    header('Location: 403.php');
    exit;
}

// Harus POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: home.php?page=monitoring-izin');
    exit;
}

$hapusId = (int)($_POST['hapus_izin'] ?? 0);

if ($hapusId <= 0) {
    $_SESSION['admin_flash'] = ['type' => 'danger', 'msg' => 'ID izin tidak valid.'];
    header('Location: home.php?page=monitoring-izin');
    exit;
}

// Cek data izin ada
$stmt = $conn->prepare("SELECT id_izin, foto_selfie FROM tbl_izin_siswa WHERE id_izin = ? LIMIT 1");
if (!$stmt) {
    $_SESSION['admin_flash'] = ['type' => 'danger', 'msg' => 'Terjadi kesalahan pada database.'];
    header('Location: home.php?page=monitoring-izin');
    exit;
}
$stmt->bind_param('i', $hapusId);
$stmt->execute();
$rowIzin = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$rowIzin) {
    $_SESSION['admin_flash'] = ['type' => 'warning', 'msg' => 'Data izin tidak ditemukan atau sudah dihapus.'];
    header('Location: home.php?page=monitoring-izin');
    exit;
}

// Hapus foto selfie jika ada
if (!empty($rowIzin['foto_selfie'])) {
    $fotoPath = __DIR__ . '/uploads/izin/' . basename($rowIzin['foto_selfie']);
    if (file_exists($fotoPath)) {
        @unlink($fotoPath);
    }
}

// Hapus record
$stmtDel = $conn->prepare("DELETE FROM tbl_izin_siswa WHERE id_izin = ?");
if ($stmtDel) {
    $stmtDel->bind_param('i', $hapusId);
    $stmtDel->execute();
    $affected = $stmtDel->affected_rows;
    $stmtDel->close();

    if ($affected > 0) {
        $_SESSION['admin_flash'] = ['type' => 'success', 'msg' => 'Pengajuan izin berhasil dihapus.'];
    } else {
        $_SESSION['admin_flash'] = ['type' => 'warning', 'msg' => 'Data tidak berhasil dihapus.'];
    }
} else {
    $_SESSION['admin_flash'] = ['type' => 'danger', 'msg' => 'Gagal menjalankan query hapus.'];
}

// Kembalikan ke halaman monitoring dengan filter yang sama
$qs = http_build_query(array_filter([
    'page'          => 'monitoring-izin',
    'filter_kelas'  => $_POST['filter_kelas']  ?? '',
    'filter_status' => $_POST['filter_status'] ?? '',
    'filter_tgl'    => $_POST['filter_tgl']    ?? '',
]));

header('Location: home.php?' . $qs);
exit;
