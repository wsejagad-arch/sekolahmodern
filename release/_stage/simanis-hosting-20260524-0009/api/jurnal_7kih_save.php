<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['no_induk']) || (int)($_SESSION['hak_akses'] ?? 0) !== 3) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../koneksi.php';
date_default_timezone_set('Asia/Jakarta');

function kih_json(array $data, int $code = 200): void
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function kih_habits(): array
{
    return [
        'bangun_pagi' => ['label' => 'Bangun Pagi', 'start' => '04:00:00', 'end' => '06:00:00'],
        'beribadah' => ['label' => 'Beribadah', 'start' => '04:00:00', 'end' => '21:00:00'],
        'berolahraga' => ['label' => 'Berolahraga', 'start' => '05:00:00', 'end' => '07:30:00'],
        'makan_sehat' => ['label' => 'Makan Sehat dan Bergizi', 'start' => '06:00:00', 'end' => '20:00:00'],
        'gemar_belajar' => ['label' => 'Gemar Belajar', 'start' => '18:00:00', 'end' => '21:00:00'],
        'bermasyarakat' => ['label' => 'Bermasyarakat', 'start' => '15:00:00', 'end' => '18:00:00'],
        'tidur_cepat' => ['label' => 'Tidur Cepat', 'start' => '20:00:00', 'end' => '22:00:00'],
    ];
}

function kih_prayers(): array
{
    return [
        'subuh' => ['label' => 'Subuh', 'start' => '04:00:00', 'end' => '06:00:00'],
        'dzuhur' => ['label' => 'Dzuhur', 'start' => '11:30:00', 'end' => '13:30:00'],
        'ashar' => ['label' => 'Ashar', 'start' => '15:00:00', 'end' => '16:30:00'],
        'maghrib' => ['label' => 'Maghrib', 'start' => '17:30:00', 'end' => '18:30:00'],
        'isya' => ['label' => 'Isya', 'start' => '19:00:00', 'end' => '20:30:00'],
    ];
}

function kih_create_table(mysqli $conn): void
{
    @mysqli_query($conn, "
        CREATE TABLE IF NOT EXISTS tbl_7kih_jurnal (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            no_induk VARCHAR(50) NOT NULL,
            nama_siswa VARCHAR(150) NOT NULL DEFAULT '',
            kelas VARCHAR(60) NOT NULL DEFAULT '',
            tanggal DATE NOT NULL,
            habit_key VARCHAR(40) NOT NULL,
            habit_label VARCHAR(120) NOT NULL,
            prayer_key VARCHAR(30) NOT NULL DEFAULT '',
            submitted_at DATETIME NOT NULL,
            window_start TIME DEFAULT NULL,
            window_end TIME DEFAULT NULL,
            timeliness_status ENUM('sangat_tepat','tepat','terlambat','di_luar_waktu') NOT NULL DEFAULT 'tepat',
            score DECIMAL(5,2) NOT NULL DEFAULT 0,
            photo_path VARCHAR(255) DEFAULT NULL,
            photo_size INT UNSIGNED NOT NULL DEFAULT 0,
            photo_hash VARCHAR(80) DEFAULT NULL,
            is_photo_stored TINYINT(1) NOT NULL DEFAULT 1,
            user_agent VARCHAR(255) DEFAULT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uniq_7kih_daily_slot (no_induk, tanggal, habit_key, prayer_key),
            KEY idx_7kih_tanggal_kelas (tanggal, kelas),
            KEY idx_7kih_siswa_bulan (no_induk, tanggal),
            KEY idx_7kih_habit (habit_key, prayer_key)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
}

function kih_column_exists(mysqli $conn, string $table, string $column): bool
{
    $tableEsc = mysqli_real_escape_string($conn, $table);
    $columnEsc = mysqli_real_escape_string($conn, $column);
    $q = @mysqli_query($conn, "SHOW COLUMNS FROM `$tableEsc` LIKE '$columnEsc'");
    return $q && mysqli_num_rows($q) > 0;
}

function kih_score(string $date, string $nowTime, string $start, string $end): array
{
    $now = strtotime($date . ' ' . $nowTime);
    $s = strtotime($date . ' ' . $start);
    $e = strtotime($date . ' ' . $end);
    if ($now === false || $s === false || $e === false) {
        return ['di_luar_waktu', 55];
    }
    if ($now >= $s && $now <= $e) {
        $span = max(1, $e - $s);
        $progress = ($now - $s) / $span;
        return $progress <= 0.35 ? ['sangat_tepat', 100] : ['tepat', 90];
    }
    if ($now > $e && $now <= strtotime('+2 hours', $e)) {
        return ['terlambat', 70];
    }
    return ['di_luar_waktu', 45];
}

function kih_save_photo(string $dataUrl, string $nis, string $habit, ?string $prayer): array
{
    if (!preg_match('/^data:image\/(jpeg|jpg|png|webp);base64,/i', $dataUrl)) {
        kih_json(['success' => false, 'message' => 'Format foto tidak valid. Ambil selfie ulang.'], 400);
    }
    $raw = base64_decode(preg_replace('/^data:image\/\w+;base64,/i', '', $dataUrl), true);
    if ($raw === false || strlen($raw) < 1200) {
        kih_json(['success' => false, 'message' => 'Foto selfie belum terbaca. Ambil ulang foto.'], 400);
    }
    if (strlen($raw) > 1500000) {
        kih_json(['success' => false, 'message' => 'Foto terlalu besar. Sistem sudah mengompres di browser, coba ambil ulang.'], 400);
    }

    $dir = __DIR__ . '/../uploads/7kih';
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    if (!is_dir($dir) || !is_writable($dir)) {
        kih_json(['success' => false, 'message' => 'Folder upload 7KIH tidak siap. Hubungi admin.'], 500);
    }

    $slot = $prayer ? $habit . '_' . $prayer : $habit;
    $safeNis = preg_replace('/[^a-zA-Z0-9_-]/', '', $nis);
    $filename = 'kih_' . $safeNis . '_' . date('Ymd') . '_' . preg_replace('/[^a-z0-9_-]/', '', $slot) . '_' . time() . '.jpg';
    $path = $dir . '/' . $filename;

    $written = false;
    if (function_exists('imagecreatefromstring') && function_exists('imagejpeg')) {
        $src = @imagecreatefromstring($raw);
        if ($src) {
            $w = imagesx($src);
            $h = imagesy($src);
            $max = 420;
            $ratio = min($max / max(1, $w), $max / max(1, $h), 1);
            $nw = max(1, (int)round($w * $ratio));
            $nh = max(1, (int)round($h * $ratio));
            $dst = imagecreatetruecolor($nw, $nh);
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
            $written = imagejpeg($dst, $path, 38);
            imagedestroy($dst);
            imagedestroy($src);
        }
    }
    if (!$written) {
        $written = file_put_contents($path, $raw) !== false;
    }
    if (!$written || !file_exists($path)) {
        kih_json(['success' => false, 'message' => 'Gagal menyimpan foto selfie.'], 500);
    }

    return [
        'relative' => 'uploads/7kih/' . $filename,
        'absolute' => $path,
        'size' => filesize($path) ?: 0,
        'hash' => hash_file('sha256', $path) ?: null,
    ];
}

function kih_cleanup_storage(mysqli $conn, int $quotaBytes = 104857600): void
{
    $dir = __DIR__ . '/../uploads/7kih';
    if (!is_dir($dir)) {
        return;
    }
    $files = glob($dir . '/*.jpg') ?: [];
    $total = 0;
    foreach ($files as $file) {
        $total += is_file($file) ? filesize($file) : 0;
    }
    if ($total <= $quotaBytes) {
        return;
    }

    usort($files, static fn($a, $b) => filemtime($a) <=> filemtime($b));
    foreach ($files as $file) {
        if ($total <= (int)($quotaBytes * 0.82)) {
            break;
        }
        $rel = 'uploads/7kih/' . basename($file);
        $size = filesize($file) ?: 0;
        @unlink($file);
        $relEsc = mysqli_real_escape_string($conn, $rel);
        @mysqli_query($conn, "UPDATE tbl_7kih_jurnal SET photo_path=NULL, photo_size=0, is_photo_stored=0 WHERE photo_path='$relEsc'");
        $total -= $size;
    }
}

kih_create_table($conn);

$habits = kih_habits();
$prayers = kih_prayers();
$habit = strtolower(trim((string)($_POST['habit_key'] ?? '')));
$prayer = strtolower(trim((string)($_POST['prayer_key'] ?? '')));
$photoData = (string)($_POST['photo_data'] ?? '');
$today = date('Y-m-d');
$nowTime = date('H:i:s');
$submittedAt = date('Y-m-d H:i:s');

if (!isset($habits[$habit])) {
    kih_json(['success' => false, 'message' => 'Jenis jurnal 7KIH tidak valid.'], 400);
}

$nis = (string)$_SESSION['no_induk'];
$nisEsc = mysqli_real_escape_string($conn, $nis);
$agamaSelect = kih_column_exists($conn, 'tbl_siswa', 'agama') ? 'agama' : "'' AS agama";
$qSiswa = mysqli_query($conn, "SELECT no_induk, nama_siswa, kelas, $agamaSelect FROM tbl_siswa WHERE no_induk='$nisEsc' LIMIT 1");
$siswa = $qSiswa ? mysqli_fetch_assoc($qSiswa) : [];
$agama = strtolower(trim((string)($siswa['agama'] ?? '')));
$isIslam = strpos($agama, 'islam') !== false;

if ($habit === 'beribadah') {
    if ($isIslam) {
        if (!isset($prayers[$prayer])) {
            kih_json(['success' => false, 'message' => 'Pilih waktu sholat yang valid.'], 400);
        }
        $window = $prayers[$prayer];
    } else {
        $prayer = '';
        $window = $habits[$habit];
    }
} else {
    $prayer = '';
    $window = $habits[$habit];
}

if ($photoData === '') {
    kih_json(['success' => false, 'message' => 'Selfie wajib diambil sebelum kirim jurnal.'], 400);
}

$photo = kih_save_photo($photoData, $nis, $habit, $prayer !== '' ? $prayer : null);
kih_cleanup_storage($conn);

$oldPrayerSql = "prayer_key='" . mysqli_real_escape_string($conn, $prayer) . "'";
$qOld = mysqli_query($conn, "SELECT photo_path FROM tbl_7kih_jurnal WHERE no_induk='$nisEsc' AND tanggal='$today' AND habit_key='" . mysqli_real_escape_string($conn, $habit) . "' AND $oldPrayerSql LIMIT 1");
if ($qOld && ($old = mysqli_fetch_assoc($qOld)) && !empty($old['photo_path'])) {
    $oldPath = __DIR__ . '/../' . $old['photo_path'];
    if (is_file($oldPath) && basename($oldPath) !== basename($photo['absolute'])) {
        @unlink($oldPath);
    }
}

[$timeliness, $score] = kih_score($today, $nowTime, $window['start'], $window['end']);
$nama = (string)($siswa['nama_siswa'] ?? ($_SESSION['nama_siswa'] ?? ''));
$kelas = (string)($siswa['kelas'] ?? ($_SESSION['kelas'] ?? ''));

$habitLabel = $habits[$habit]['label'];
if ($habit === 'beribadah' && $prayer !== '') {
    $habitLabel .= ' - Sholat ' . $prayers[$prayer]['label'];
}

$values = [
    'no_induk' => $nis,
    'nama_siswa' => $nama,
    'kelas' => $kelas,
    'tanggal' => $today,
    'habit_key' => $habit,
    'habit_label' => $habitLabel,
    'prayer_key' => $prayer,
    'submitted_at' => $submittedAt,
    'window_start' => $window['start'],
    'window_end' => $window['end'],
    'timeliness_status' => $timeliness,
    'score' => $score,
    'photo_path' => $photo['relative'],
    'photo_size' => (int)$photo['size'],
    'photo_hash' => $photo['hash'],
    'user_agent' => substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
];

$esc = [];
foreach ($values as $key => $value) {
    $esc[$key] = $value === null ? 'NULL' : "'" . mysqli_real_escape_string($conn, (string)$value) . "'";
}

$sql = "
    INSERT INTO tbl_7kih_jurnal
        (no_induk, nama_siswa, kelas, tanggal, habit_key, habit_label, prayer_key, submitted_at, window_start, window_end, timeliness_status, score, photo_path, photo_size, photo_hash, is_photo_stored, user_agent)
    VALUES
        ({$esc['no_induk']}, {$esc['nama_siswa']}, {$esc['kelas']}, {$esc['tanggal']}, {$esc['habit_key']}, {$esc['habit_label']}, {$esc['prayer_key']}, {$esc['submitted_at']}, {$esc['window_start']}, {$esc['window_end']}, {$esc['timeliness_status']}, {$score}, {$esc['photo_path']}, " . (int)$photo['size'] . ", {$esc['photo_hash']}, 1, {$esc['user_agent']})
    ON DUPLICATE KEY UPDATE
        nama_siswa=VALUES(nama_siswa),
        kelas=VALUES(kelas),
        habit_label=VALUES(habit_label),
        submitted_at=VALUES(submitted_at),
        window_start=VALUES(window_start),
        window_end=VALUES(window_end),
        timeliness_status=VALUES(timeliness_status),
        score=VALUES(score),
        photo_path=VALUES(photo_path),
        photo_size=VALUES(photo_size),
        photo_hash=VALUES(photo_hash),
        is_photo_stored=1,
        user_agent=VALUES(user_agent),
        updated_at=CURRENT_TIMESTAMP
";

$ok = mysqli_query($conn, $sql);
if (!$ok) {
    @unlink($photo['absolute']);
    kih_json(['success' => false, 'message' => 'Gagal menyimpan jurnal: ' . mysqli_error($conn)], 500);
}

kih_json([
    'success' => true,
    'message' => 'Jurnal 7KIH berhasil dikirim.',
    'habit' => $habitLabel,
    'score' => $score,
    'timeliness' => $timeliness,
]);
