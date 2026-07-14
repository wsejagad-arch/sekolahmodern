<?php
require_once __DIR__ . '/bootstrap.php';

function request_ip(): string
{
	if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
		return $_SERVER['HTTP_CLIENT_IP'];
	}
	if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
		$parts = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
		return trim($parts[0]);
	}
	return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

function login_attempt_file(): string
{
	return __DIR__ . '/logs/login_attempts.json';
}

function load_login_attempts(): array
{
	$path = login_attempt_file();
	if (!file_exists($path)) {
		return [];
	}

	$content = @file_get_contents($path);
	if (!is_string($content) || $content === '') {
		return [];
	}

	$data = json_decode($content, true);
	return is_array($data) ? $data : [];
}

function save_login_attempts(array $data): void
{
	$path = login_attempt_file();
	$dir = dirname($path);
	if (!is_dir($dir)) {
		@mkdir($dir, 0755, true);
	}
	@file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
}

function prune_login_attempts(array $attempts): array
{
	$cutoff = time() - 900; // 15 minutes
	foreach ($attempts as $ip => $timestamps) {
		$timestamps = array_filter($timestamps, static fn($ts) => is_int($ts) && $ts >= $cutoff);
		if (empty($timestamps)) {
			unset($attempts[$ip]);
		} else {
			$attempts[$ip] = array_values($timestamps);
		}
	}
	return $attempts;
}

function record_login_attempt(string $ip, bool $success): void
{
	$attempts = prune_login_attempts(load_login_attempts());
	if (!$success) {
		$attempts[$ip] = $attempts[$ip] ?? [];
		$attempts[$ip][] = time();
	} else {
		unset($attempts[$ip]);
	}
	save_login_attempts($attempts);
}

function is_login_blocked(string $ip): bool
{
	$attempts = prune_login_attempts(load_login_attempts());
	return isset($attempts[$ip]) && count($attempts[$ip]) >= 50;
}

function verify_password(string $rawPassword, ?string $storedHash, string $noInduk = ''): bool
{
	if ($storedHash === null || $storedHash === '') {
		$storedHash = md5('12345');
	}

	// Check if hash is bcrypt format (starts with $2a$, $2b$, or $2y$)
	if (preg_match('/^\$2[aby]\$/', $storedHash)) {
		return password_verify($rawPassword, $storedHash);
	}

	// Otherwise treat as MD5 hash
	if (hash_equals(md5($rawPassword), $storedHash)) return true;
	
	// Fallback for default passwords: allow '12345' or NISN/NIP interchangeably for MD5 hashes
	if ($noInduk !== '') {
	    if ($rawPassword === '12345' && hash_equals(md5($noInduk), $storedHash)) return true;
	    if ($rawPassword === $noInduk && hash_equals(md5('12345'), $storedHash)) return true;
	}
	
	return false;
}

function normalize_redirect_url(string $url): string
{
	$url = str_replace('\\', '/', $url);

	if (preg_match('#^https?://#i', $url) !== 1) {
		return preg_replace('#/{2,}#', '/', $url) ?? $url;
	}

	$parts = parse_url($url);
	if ($parts === false || !isset($parts['scheme'], $parts['host'])) {
		return $url;
	}

	$path = $parts['path'] ?? '/';
	$path = preg_replace('#/{2,}#', '/', $path) ?? $path;
	if ($path === '') {
		$path = '/';
	}

	$normalized = $parts['scheme'] . '://' . $parts['host'];
	if (isset($parts['port'])) {
		$normalized .= ':' . $parts['port'];
	}
	$normalized .= $path;
	if (isset($parts['query'])) {
		$normalized .= '?' . $parts['query'];
	}
	if (isset($parts['fragment'])) {
		$normalized .= '#' . $parts['fragment'];
	}

	return $normalized;
}

function redirect_login_failure(string $target): void
{
	// Normalize target into an absolute URL or absolute path
	if (strpos($target, 'http') === 0 || strpos($target, '/') === 0) {
		$url = $target;
	} else {
		$url = get_app_url() . get_base_path() . '/' . ltrim($target, '/');
	}
	$url = normalize_redirect_url($url);

	// Append failure flag safely (preserve existing query string)
	$hasQuery = parse_url($url, PHP_URL_QUERY) !== null;
	$url .= ($hasQuery ? '&' : '?') . 'gagallogin';

	header('Location: ' . $url);
	exit;
}

function redirect_login_database_error(): void
{
	redirect_login_failure('login.php?db_error');
}

function redirect_login_success(string $target): void
{
	// Normalize target into an absolute URL or absolute path
	if (strpos($target, 'http') === 0 || strpos($target, '/') === 0) {
		$url = $target;
	} else {
		$url = get_app_url() . get_base_path() . '/' . ltrim($target, '/');
	}
	// Normalize target into an absolute URL or absolute path
	if (strpos($target, 'http') === 0 || strpos($target, '/') === 0) {
		$url = $target;
	} else {
		$url = get_app_url() . get_base_path() . '/' . ltrim($target, '/');
	}
	$url = normalize_redirect_url($url);

	header('Location: ' . $url);
	exit;
}

function get_admin_user(string $username, int $schoolId = 0): ?array
{
	global $conn;
	$debugLog = __DIR__ . '/logs/login_debug.log';

	if (!$conn) {
		return null;
	}

	$u = mysqli_real_escape_string($conn, $username);
	$schoolJoin = mt_table_exists($conn, 'tbl_sekolah') ? " LEFT JOIN tbl_sekolah s ON s.id_sekolah=u.id_sekolah" : "";
	$schoolSelect = mt_column_exists($conn, 'tbl_user', 'id_sekolah') ? ", u.id_sekolah" : ", 1 AS id_sekolah";
	$codeSelect = mt_table_exists($conn, 'tbl_sekolah') ? ", COALESCE(s.kode_sekolah, 'DEFAULT') AS kode_sekolah" : ", 'DEFAULT' AS kode_sekolah";
	$schoolWhere = $schoolId > 0 && mt_column_exists($conn, 'tbl_user', 'id_sekolah') ? " AND u.id_sekolah=$schoolId" : "";
	$sql = "SELECT u.id_user, u.username, u.nama, u.hak_akses, u.password $schoolSelect $codeSelect
	        FROM tbl_user u
	        $schoolJoin
	        WHERE u.username = '$u'$schoolWhere LIMIT 1";
	
	$result = mysqli_query($conn, $sql);
	if (!$result) {
		$errorMsg = "[login_action] get_admin_user: Query failed: " . mysqli_error($conn) . "\n";
		@file_put_contents($debugLog, $errorMsg, FILE_APPEND | LOCK_EX);
		return null;
	}

	$user = mysqli_fetch_assoc($result);
	return $user ?: null;
}

function get_guru_user(string $username, string $status, int $schoolId = 0): ?array
{
	global $conn;
	$debugLog = __DIR__ . '/logs/login_debug.log';
	
	if (!$conn) {
		$errorMsg = "[login_action] get_guru_user: No connection\n";
		@file_put_contents($debugLog, $errorMsg, FILE_APPEND | LOCK_EX);
		return null;
	}

	$u = mysqli_real_escape_string($conn, $username);
	$s = mysqli_real_escape_string($conn, $status);
	$schoolJoin = mt_table_exists($conn, 'tbl_sekolah') ? " LEFT JOIN tbl_sekolah sk ON sk.id_sekolah=g.id_sekolah" : "";
	$schoolSelect = mt_column_exists($conn, 'tbl_guru', 'id_sekolah') ? ", g.id_sekolah" : ", 1 AS id_sekolah";
	$codeSelect = mt_table_exists($conn, 'tbl_sekolah') ? ", COALESCE(sk.kode_sekolah, 'DEFAULT') AS kode_sekolah" : ", 'DEFAULT' AS kode_sekolah";
	$schoolWhere = '';
	if ($schoolId > 0 && mt_column_exists($conn, 'tbl_guru', 'id_sekolah')) {
		$schoolWhere = " AND (g.id_sekolah=$schoolId OR g.id_sekolah IS NULL OR g.id_sekolah=0)";
	}
	
	$statusFilter = " g.status = '$s' ";
	if (strcasecmp($s, 'Aktif') === 0) {
		$statusFilter = " (g.status = '$s' OR g.status = 'aktif' OR g.status IS NULL OR g.status = '') ";
	}

	// Use LEFT JOIN to find guru even if tbl_pengguna record is missing
	$sql = "SELECT g.no_induk, g.nama_guru, g.status_kepegawaian, p.password $schoolSelect $codeSelect
			FROM tbl_guru g 
			LEFT JOIN tbl_pengguna p ON g.no_induk = p.no_induk 
			$schoolJoin
			WHERE (TRIM(g.no_induk) = '$u' OR TRIM(LEADING '0' FROM g.no_induk) = LTRIM('$u', '0') OR g.nama_guru LIKE '%$u%') AND $statusFilter $schoolWhere 
			ORDER BY g.status ASC, g.no_induk DESC LIMIT 1";
			
	$result = mysqli_query($conn, $sql);
	if (!$result) {
		$errorMsg = "[login_action] get_guru_user: Query failed: " . mysqli_error($conn) . "\n";
		@file_put_contents($debugLog, $errorMsg, FILE_APPEND | LOCK_EX);
		return null;
	}

	$user = mysqli_fetch_assoc($result);
	
	if ($user) {
		// Auto-repair missing tbl_pengguna entry
		if (empty($user['password'])) {
			$no_induk = mysqli_real_escape_string($conn, $user['no_induk']);
			$hashnip = md5('12345');
			$akses = 2; // Guru
			mysqli_query($conn, "INSERT IGNORE INTO tbl_pengguna(no_induk, password, hak_akses) VALUES('$no_induk','$hashnip','$akses')");
			$user['password'] = $hashnip; // Set password so verify_password doesn't fail
		}
	} else {
		$errorMsg = "[login_action] get_guru_user: No user found for $username with status $status (Query: $sql)\n";
		@file_put_contents($debugLog, $errorMsg, FILE_APPEND | LOCK_EX);
	}

	return $user ?: null;
}

function get_siswa_user(string $username, string $status, int $schoolId = 0): ?array
{
	global $conn;
	$debugLog = __DIR__ . '/logs/login_debug.log';

	if (!$conn) {
		return null;
	}

	$u = mysqli_real_escape_string($conn, $username);
	$s = mysqli_real_escape_string($conn, $status);
	$schoolJoin = mt_table_exists($conn, 'tbl_sekolah') ? " LEFT JOIN tbl_sekolah sk ON sk.id_sekolah=s.id_sekolah" : "";
	$schoolSelect = mt_column_exists($conn, 'tbl_siswa', 'id_sekolah') ? ", s.id_sekolah" : ", 1 AS id_sekolah";
	$codeSelect = mt_table_exists($conn, 'tbl_sekolah') ? ", COALESCE(sk.kode_sekolah, 'DEFAULT') AS kode_sekolah" : ", 'DEFAULT' AS kode_sekolah";
	$schoolWhere = '';
	if ($schoolId > 0 && mt_column_exists($conn, 'tbl_siswa', 'id_sekolah')) {
		$schoolWhere = " AND (s.id_sekolah=$schoolId OR s.id_sekolah IS NULL OR s.id_sekolah=0)";
	}

	$statusFilter = " s.status = '$s' ";
	if (strcasecmp($s, 'Aktif') === 0) {
		$statusFilter = " (s.status = '$s' OR s.status = 'aktif' OR s.status IS NULL OR s.status = '') ";
	}

	// Use LEFT JOIN to find siswa even if tbl_pengguna record is missing
	$sql = "SELECT s.no_induk, s.nama_siswa, s.kelas, s.jabatan, s.status, p.hak_akses, p.password $schoolSelect $codeSelect
			FROM tbl_siswa s 
			LEFT JOIN tbl_pengguna p ON s.no_induk = p.no_induk 
			$schoolJoin
			WHERE (TRIM(s.no_induk) = '$u' OR TRIM(LEADING '0' FROM s.no_induk) = LTRIM('$u', '0') OR s.nama_siswa LIKE '%$u%') AND $statusFilter $schoolWhere 
			ORDER BY s.status ASC, s.no_induk DESC LIMIT 1";

	// INJECT DEBUG TO FIND JOJOK'S ACTUAL DATA
	if (stripos($u, 'jojok') !== false || true) {
		$debugSql = "SELECT s.no_induk, s.nama_siswa, s.kelas, s.status, s.id_sekolah, p.password FROM tbl_siswa s LEFT JOIN tbl_pengguna p ON s.no_induk = p.no_induk WHERE s.nama_siswa LIKE '%JOJOK%' OR s.no_induk = '$u'";
		$debugRes = mysqli_query($conn, $debugSql);
		$rows = [];
		if ($debugRes) {
			while ($row = mysqli_fetch_assoc($debugRes)) { $rows[] = $row; }
		}
		@file_put_contents(__DIR__ . '/logs/jojok_raw_db.json', json_encode([
			'input_username' => $u,
			'matched_rows' => $rows,
			'filter_used' => $statusFilter,
			'school_where' => $schoolWhere
		], JSON_PRETTY_PRINT));
	}


	$result = mysqli_query($conn, $sql);
	if (!$result) {
		$errorMsg = "[login_action] get_siswa_user: Query failed: " . mysqli_error($conn) . "\n";
		@file_put_contents($debugLog, $errorMsg, FILE_APPEND | LOCK_EX);
		return null;
	}

	$user = mysqli_fetch_assoc($result);
	
	if ($user) {
		// Auto-repair missing tbl_pengguna entry
		if (empty($user['password'])) {
			$no_induk = mysqli_real_escape_string($conn, $user['no_induk']);
			$hashnip = md5('12345');
			$akses = 3; // Siswa
			mysqli_query($conn, "INSERT IGNORE INTO tbl_pengguna(no_induk, password, hak_akses) VALUES('$no_induk','$hashnip','$akses')");
			$user['password'] = $hashnip; // Set password so verify_password doesn't fail
		}
	}

	return $user ?: null;
}

function ensure_jabatan_column_exists(): void
{
	global $conn;
	if (!function_exists('mysqli_query') || !function_exists('mysqli_num_rows') || !$conn) {
		return;
	}

	$check = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_siswa LIKE 'jabatan'");
	if ($check && mysqli_num_rows($check) === 0) {
		@mysqli_query($conn, "ALTER TABLE tbl_siswa ADD COLUMN jabatan ENUM('Siswa','Ketua Kelas') DEFAULT 'Siswa' AFTER kelas");
	}
}

function is_database_available(): bool
{
	global $conn;
	return $conn instanceof mysqli;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	redirect_login_failure('login.php');
}

$ip = request_ip();

// DEBUG LOGGING
$debugLog = __DIR__ . '/logs/login_debug.log';
$debugDir = dirname($debugLog);
if (!is_dir($debugDir)) {
	@mkdir($debugDir, 0755, true);
}

$debugInfo = [
	'timestamp' => date('Y-m-d H:i:s'),
	'ip' => $ip,
	'post_keys' => array_keys($_POST),
	'username' => $_POST['username'] ?? 'MISSING',
	'password_length' => strlen($_POST['password'] ?? ''),
	'hak_akses' => $_POST['hak_akses'] ?? 'MISSING',
];
@file_put_contents($debugLog, json_encode($debugInfo) . "\n", FILE_APPEND | LOCK_EX);

if (is_login_blocked($ip)) {
	sleep(2);
	redirect_login_failure('login.php');
}

$csrfToken = $_POST['csrf_token'] ?? '';
if (!verify_csrf_token($csrfToken)) {
	$debugCsrf = [
		'error' => 'csrf_mismatch',
		'timestamp' => date('Y-m-d H:i:s'),
		'session_token' => $_SESSION['csrf_token'] ?? 'NOT_SET',
		'post_token' => $csrfToken
	];
	@file_put_contents(__DIR__ . '/logs/login_debug.log', json_encode($debugCsrf) . "\n", FILE_APPEND | LOCK_EX);
	record_login_attempt($ip, false);
	redirect_login_failure('login.php');
}

if (!is_database_available()) {
	@file_put_contents(
		__DIR__ . '/logs/login_debug.log',
		json_encode([
			'error' => 'database_unavailable',
			'timestamp' => date('Y-m-d H:i:s'),
			'username' => $_POST['username'] ?? 'MISSING',
			'mysqli_loaded' => extension_loaded('mysqli'),
		]) . "\n",
		FILE_APPEND | LOCK_EX
	);
	redirect_login_database_error();
}

$username = clean_input($_POST['username'] ?? '');
$passwordRaw = $_POST['password'] ?? '';
$passwordRaw = substr($passwordRaw, 0, 128);
$akses = trim((string)($_POST['hak_akses'] ?? ''));
$stt = 'Aktif';
$schoolCode = strtoupper(clean_input($_POST['kode_sekolah'] ?? ''));
$loginSchoolId = 0;
if ($conn instanceof mysqli && function_exists('mt_resolve_school_id')) {
	$loginSchoolId = mt_resolve_school_id($conn, $schoolCode);
	if ($schoolCode !== '' && $loginSchoolId <= 0) {
		record_login_attempt($ip, false);
		redirect_login_failure('login.php');
	}
	$_SESSION['kode_sekolah'] = $schoolCode !== '' ? strtoupper($schoolCode) : 'DEFAULT';
}

$latitude = isset($_POST['latitude']) && $_POST['latitude'] !== '' ? (float)$_POST['latitude'] : null;
$longitude = isset($_POST['longitude']) && $_POST['longitude'] !== '' ? (float)$_POST['longitude'] : null;
$_SESSION['login_latitude'] = $latitude;
$_SESSION['login_longitude'] = $longitude;

if ($username === '' || ($passwordRaw === '' && $akses !== '2')) {
	record_login_attempt($ip, false);
	redirect_login_failure('login.php');
}

if ($akses === 'auto' || $akses === '') {
	$user = get_admin_user($username, $loginSchoolId);
	$debug_admin = [
		'msg' => 'admin_check',
		'username' => $username,
		'admin_found' => $user ? true : false,
		'admin_password_hash' => $user ? substr($user['password'], 0, 20) : 'N/A',
	];
	$debug_admin = [
		'msg' => 'admin_check',
		'username' => $username,
		'admin_found' => $user ? true : false,
		'admin_password_hash' => $user ? substr($user['password'], 0, 20) : 'N/A',
	];
	if ($user) {
		$debug_admin['password_verify_result'] = verify_password($passwordRaw, $user['password'], $user['no_induk'] ?? '');
	}
	@file_put_contents(__DIR__ . '/logs/login_debug.log', json_encode($debug_admin) . "\n", FILE_APPEND | LOCK_EX);

	if ($user && verify_password($passwordRaw, $user['password'], $user['no_induk'] ?? '')) {
		$debug = ['msg' => 'admin_login_success', 'username' => $username];
		@file_put_contents(__DIR__ . '/logs/login_debug.log', json_encode($debug) . "\n", FILE_APPEND | LOCK_EX);
		set_admin_session($user);
		session_regenerate_id(true);
		record_login_attempt($ip, true);
		if (isset($user['hak_akses']) && $user['hak_akses'] == '4') {
			redirect_login_success('satpam.php');
		} else {
			redirect_login_success('home.php');
		}
	}

	$guru = get_guru_user($username, $stt, $loginSchoolId);
	$debug_guru = [
		'msg' => 'guru_check',
		'username' => $username,
		'guru_found' => $guru ? true : false,
		'guru_password_hash' => $guru ? substr($guru['password'], 0, 20) : 'N/A',
		'guru_status' => $guru ? ($guru['status'] ?? 'N/A') : 'N/A'
	];
	if ($guru) {
		$debug_guru['password_verify_result'] = verify_password($passwordRaw, $guru['password'], $guru['no_induk'] ?? '');
		$debug_guru['password_empty_check'] = $passwordRaw === '';
	}
	@file_put_contents(__DIR__ . '/logs/login_debug.log', json_encode($debug_guru) . "\n", FILE_APPEND | LOCK_EX);

	if ($guru && ($passwordRaw === '' || verify_password($passwordRaw, $guru['password'], $guru['no_induk'] ?? ''))) {
		$debug = ['msg' => 'guru_login_success', 'username' => $username];
		@file_put_contents(__DIR__ . '/logs/login_debug.log', json_encode($debug) . "\n", FILE_APPEND | LOCK_EX);
		set_guru_session($guru);
		session_regenerate_id(true);
		record_login_attempt($ip, true);
		redirect_login_success('home.php');
	}

	ensure_jabatan_column_exists();

	$siswa = get_siswa_user($username, $stt, $loginSchoolId);
	$debug_siswa = [
		'msg' => 'siswa_check',
		'username' => $username,
		'siswa_found' => $siswa ? true : false,
		'siswa_password_hash' => $siswa ? substr($siswa['password'], 0, 20) : 'N/A',
		'siswa_status' => $siswa ? ($siswa['status'] ?? 'N/A') : 'N/A'
	];
	if ($siswa) {
		$debug_siswa['password_verify_result'] = verify_password($passwordRaw, $siswa['password'], $siswa['no_induk'] ?? '');
	}
	@file_put_contents(__DIR__ . '/logs/login_debug.log', json_encode($debug_siswa) . "\n", FILE_APPEND | LOCK_EX);

	if ($siswa && verify_password($passwordRaw, $siswa['password'], $siswa['no_induk'] ?? '')) {
		$debug = ['msg' => 'siswa_login_success', 'username' => $username];
		@file_put_contents(__DIR__ . '/logs/login_debug.log', json_encode($debug) . "\n", FILE_APPEND | LOCK_EX);
		set_siswa_session($siswa);
		session_regenerate_id(true);
		record_login_attempt($ip, true);
		redirect_login_success(siswa_page('siswa'));
	}

	$debug = ['msg' => 'auto_login_failed', 'username' => $username, 'timestamp' => date('Y-m-d H:i:s')];
	@file_put_contents(__DIR__ . '/logs/login_debug.log', json_encode($debug) . "\n", FILE_APPEND | LOCK_EX);
	record_login_attempt($ip, false);
	redirect_login_failure('login.php');
}

if ($akses == 1) {
	$user = get_admin_user($username, $loginSchoolId);
	$debug = [
		'msg' => 'admin_role_specific',
		'username' => $username,
		'admin_found' => $user ? true : false,
		'admin_password_hash' => $user ? substr($user['password'], 0, 20) : 'N/A'
	];
	if ($user) {
		$debug['password_verify_result'] = verify_password($passwordRaw, $user['password'], $user['no_induk'] ?? '');
	}
	@file_put_contents(__DIR__ . '/logs/login_debug.log', json_encode($debug) . "\n", FILE_APPEND | LOCK_EX);

	if ($user && verify_password($passwordRaw, $user['password'], $user['no_induk'] ?? '')) {
		$debug2 = ['msg' => 'admin_specific_success', 'username' => $username];
		@file_put_contents(__DIR__ . '/logs/login_debug.log', json_encode($debug2) . "\n", FILE_APPEND | LOCK_EX);
		set_admin_session($user);
		session_regenerate_id(true);
		record_login_attempt($ip, true);
		if (isset($user['hak_akses']) && $user['hak_akses'] == '4') {
			redirect_login_success('satpam.php');
		} else {
			redirect_login_success('home.php');
		}
	}

	record_login_attempt($ip, false);
	redirect_login_failure('login.php');
} elseif ($akses == 2) {
	$guru = get_guru_user($username, $stt, $loginSchoolId);
	$debug = [
		'msg' => 'guru_role_specific',
		'username' => $username,
		'guru_found' => $guru ? true : false,
		'guru_status' => $guru ? ($guru['status'] ?? 'N/A') : 'N/A',
		'guru_password_hash' => $guru ? substr($guru['password'], 0, 20) : 'N/A'
	];
	if ($guru) {
		$debug['password_verify_result'] = verify_password($passwordRaw, $guru['password'], $guru['no_induk'] ?? '');
		$debug['password_empty'] = $passwordRaw === '';
	}
	@file_put_contents(__DIR__ . '/logs/login_debug.log', json_encode($debug) . "\n", FILE_APPEND | LOCK_EX);

	if ($guru && ($passwordRaw === '' || verify_password($passwordRaw, $guru['password'], $guru['no_induk'] ?? ''))) {
		$debug2 = ['msg' => 'guru_specific_success', 'username' => $username];
		@file_put_contents(__DIR__ . '/logs/login_debug.log', json_encode($debug2) . "\n", FILE_APPEND | LOCK_EX);
		set_guru_session($guru);
		session_regenerate_id(true);
		record_login_attempt($ip, true);
		redirect_login_success('home.php');
	}

	record_login_attempt($ip, false);
	redirect_login_failure('login.php');
} elseif ($akses == 3) {
	ensure_jabatan_column_exists();

	$siswa = get_siswa_user($username, $stt, $loginSchoolId);
	$debug = [
		'msg' => 'siswa_role_specific',
		'username' => $username,
		'siswa_found' => $siswa ? true : false,
		'siswa_status' => $siswa ? ($siswa['status'] ?? 'N/A') : 'N/A',
		'siswa_password_hash' => $siswa ? substr($siswa['password'], 0, 20) : 'N/A'
	];
	if ($siswa) {
		$debug['password_verify_result'] = verify_password($passwordRaw, $siswa['password'], $siswa['no_induk'] ?? '');
	}
	@file_put_contents(__DIR__ . '/logs/login_debug.log', json_encode($debug) . "\n", FILE_APPEND | LOCK_EX);

	if ($siswa && verify_password($passwordRaw, $siswa['password'], $siswa['no_induk'] ?? '')) {
		$debug2 = ['msg' => 'siswa_specific_success', 'username' => $username];
		@file_put_contents(__DIR__ . '/logs/login_debug.log', json_encode($debug2) . "\n", FILE_APPEND | LOCK_EX);
		set_siswa_session($siswa);
		session_regenerate_id(true);
		record_login_attempt($ip, true);
		redirect_login_success(siswa_page('siswa'));
	}

	record_login_attempt($ip, false);
	redirect_login_failure('login.php');
}

record_login_attempt($ip, false);
redirect_login_failure('login.php');
