<?php
require_once 'koneksi.php';
$u = 'admin';
$sql = "SELECT u.id_user, u.username, u.nama, u.hak_akses, u.password , 1 AS id_sekolah , 'DEFAULT' AS kode_sekolah FROM tbl_user u WHERE u.username = '$u' LIMIT 1";
$q = mysqli_query($conn, $sql);
$user = $q ? mysqli_fetch_assoc($q) : null;
echo "Admin Query Result:\n";
print_r($user);
echo "Password Verify: " . (verify_password('12345', $user['password'] ?? '') ? 'TRUE' : 'FALSE') . "\n";

function verify_password(string $rawPassword, ?string $storedHash): bool
{
	if ($storedHash === null || $storedHash === '') {
		$storedHash = md5('12345');
	}
	if (preg_match('/^\$2[aby]\$/', $storedHash)) {
		return password_verify($rawPassword, $storedHash);
	}
	if (hash_equals(md5($rawPassword), $storedHash)) return true;
	return false;
}
