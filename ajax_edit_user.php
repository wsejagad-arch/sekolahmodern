<?php
/**
 * AJAX endpoint: Edit User Staff (tbl_user)
 * POST params: id_user, nama, username, password (opsional)
 */
session_start();
header('Content-Type: application/json');

// Auth check
if (!isset($_SESSION['username']) || $_SESSION['hak_akses'] != 1) {
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak.']);
    exit;
}

include 'koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Method tidak valid.']);
    exit;
}

$id_user  = intval($_POST['id_user'] ?? 0);
$nama     = trim($_POST['nama'] ?? '');
$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');

if ($id_user <= 0 || empty($nama) || empty($username)) {
    echo json_encode(['status' => 'error', 'message' => 'Nama dan username tidak boleh kosong.']);
    exit;
}

// Escape
$nama_esc     = mysqli_real_escape_string($conn, $nama);
$username_esc = mysqli_real_escape_string($conn, $username);

// Cek username tidak duplikat (kecuali milik sendiri)
$cek = mysqli_query($conn, "SELECT id_user FROM tbl_user WHERE username = '$username_esc' AND id_user != $id_user");
if ($cek && mysqli_num_rows($cek) > 0) {
    echo json_encode(['status' => 'error', 'message' => "Username '$username' sudah dipakai user lain."]);
    exit;
}

// Build query
if (!empty($password)) {
    $pwd_plain = mysqli_real_escape_string($conn, $password);
    $pwd_md5   = md5($password);
    $sql = "UPDATE tbl_user SET nama = '$nama_esc', username = '$username_esc', password = '$pwd_md5', password_plain = '$pwd_plain' WHERE id_user = $id_user";
} else {
    $sql = "UPDATE tbl_user SET nama = '$nama_esc', username = '$username_esc' WHERE id_user = $id_user";
}

$result = mysqli_query($conn, $sql);
if ($result) {
    echo json_encode([
        'status'   => 'success',
        'message'  => 'Data user berhasil diperbarui.',
        'nama'     => $nama,
        'username' => $username,
        'password' => !empty($password) ? $password : null,
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Gagal update: ' . mysqli_error($conn)]);
}
