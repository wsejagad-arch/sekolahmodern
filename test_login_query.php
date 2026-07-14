<?php
require_once 'bootstrap.php';
function get_guru_user_test(string $username, string $status, int $schoolId = 0)
{
	global $conn;
	$u = mysqli_real_escape_string($conn, $username);
	$s = mysqli_real_escape_string($conn, $status);
	$schoolJoin = " LEFT JOIN tbl_sekolah sk ON sk.id_sekolah=g.id_sekolah";
	$schoolSelect = ", g.id_sekolah";
	$codeSelect = ", COALESCE(sk.kode_sekolah, 'DEFAULT') AS kode_sekolah";
	$schoolWhere = '';
	if ($schoolId > 0) {
		$schoolWhere = " AND (g.id_sekolah=$schoolId OR g.id_sekolah IS NULL OR g.id_sekolah=0)";
	}
	
	$statusFilter = " g.status = '$s' ";
	if (strcasecmp($s, 'Aktif') === 0) {
		$statusFilter = " (g.status = '$s' OR g.status = 'aktif' OR g.status IS NULL OR g.status = '') ";
	}

	$sql = "SELECT g.no_induk, g.nama_guru, g.status_kepegawaian, p.password $schoolSelect $codeSelect
			FROM tbl_guru g 
			LEFT JOIN tbl_pengguna p ON g.no_induk = p.no_induk 
			$schoolJoin
			WHERE (TRIM(g.no_induk) = '$u' OR TRIM(LEADING '0' FROM g.no_induk) = LTRIM('$u', '0') OR g.nama_guru LIKE '%$u%') AND $statusFilter $schoolWhere 
			ORDER BY g.status ASC, g.no_induk DESC LIMIT 1";
			
	$result = mysqli_query($conn, $sql);
	if (!$result) die(mysqli_error($conn));
	return mysqli_fetch_assoc($result);
}

$guru = get_guru_user_test('0002', 'Aktif', 0);
if($guru) {
    echo "GURU FOUND:\n";
    print_r($guru);
} else {
    echo "GURU NOT FOUND\n";
}
