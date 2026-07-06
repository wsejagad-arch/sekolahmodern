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

function kih_prayers(string $agama): array
{
    $agama = strtolower(trim($agama));
    if (strpos($agama, 'islam') !== false) {
        $p = [
            'subuh' => ['label' => 'Subuh', 'start' => '04:00:00', 'end' => '06:00:00'],
            'dzuhur' => ['label' => 'Dzuhur', 'start' => '11:30:00', 'end' => '13:30:00'],
            'ashar' => ['label' => 'Ashar', 'start' => '15:00:00', 'end' => '16:30:00'],
            'maghrib' => ['label' => 'Maghrib', 'start' => '17:30:00', 'end' => '18:30:00'],
            'isya' => ['label' => 'Isya', 'start' => '19:00:00', 'end' => '20:30:00'],
        ];
        if (date('N') == 5) {
            $p['jumat'] = ['label' => 'Jumat', 'start' => '11:30:00', 'end' => '13:30:00'];
        }
        return $p;
    }
    if (strpos($agama, 'katolik') !== false) {
        return [
            'pagi' => ['label' => 'Ibadah Pagi (06:00)', 'start' => '05:00:00', 'end' => '08:00:00'],
            'siang' => ['label' => 'Ibadah Siang (12:00)', 'start' => '11:00:00', 'end' => '14:00:00'],
            'sore' => ['label' => 'Ibadah Sore (18:00)', 'start' => '17:00:00', 'end' => '20:00:00'],
            'malaikat_tuhan' => ['label' => 'Malaikat Tuhan', 'start' => '00:00:00', 'end' => '23:59:59'],
            'rosario' => ['label' => 'Doa Rosario', 'start' => '00:00:00', 'end' => '23:59:59'],
            'bapa_kami' => ['label' => 'Bapa Kami', 'start' => '00:00:00', 'end' => '23:59:59'],
            'salam_maria' => ['label' => 'Salam Maria', 'start' => '00:00:00', 'end' => '23:59:59'],
            'doa_umum' => ['label' => 'Doa Umum', 'start' => '00:00:00', 'end' => '23:59:59'],
            'novena' => ['label' => 'Doa Novena', 'start' => '00:00:00', 'end' => '23:59:59'],
        ];
    }
    return [
        'umum' => ['label' => 'Ibadah Umum', 'start' => '00:00:00', 'end' => '23:59:59']
    ];
}

function kih_haversine(float $lat1, float $lon1, float $lat2, float $lon2): float {
    $earthRadius = 6371000;
    $latDelta = deg2rad($lat2 - $lat1);
    $lonDelta = deg2rad($lon2 - $lon1);
    $a = sin($latDelta / 2) * sin($latDelta / 2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($lonDelta / 2) * sin($lonDelta / 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    return $earthRadius * $c;
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
    
    // Handle cross midnight (e.g. Isya 19:00 - 03:59)
    if ($e < $s) {
        // If current time is early morning (e.g. 02:00), we consider the start time was yesterday
        if (date('H', $now) < 12) {
            $s = strtotime('-1 day', $s);
        } else {
            // Current time is evening, end time is tomorrow
            $e = strtotime('+1 day', $e);
        }
    }
    
    if ($now === false || $s === false || $e === false) {
        return ['ditolak', 0];
    }
    
    if ($now >= $s && $now <= $e) {
        $span = max(1, $e - $s);
        $progress = ($now - $s) / $span;
        return $progress <= 0.35 ? ['sangat_tepat', 100] : ['tepat', 90];
    }
    
    return ['ditolak', 0];
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

    // Hanya dijadikan konfirmasi tanpa menyimpan file agar server tidak penuh
    return [
        'relative' => null,
        'absolute' => null,
        'size' => 0,
        'hash' => null,
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
$habit = strtolower(trim((string)($_POST['habit_key'] ?? '')));
$prayer = strtolower(trim((string)($_POST['prayer_key'] ?? '')));
$keterangan = isset($_POST['keterangan']) ? trim((string)$_POST['keterangan']) : null;
$lat = isset($_POST['lat']) ? trim((string)$_POST['lat']) : null;
$lng = isset($_POST['lng']) ? trim((string)$_POST['lng']) : null;
$photoData = (string)($_POST['photo_data'] ?? '');
$today = date('Y-m-d');
$nowTime = date('H:i:s');
$submittedAt = date('Y-m-d H:i:s');

if (!isset($habits[$habit])) {
    kih_json(['success' => false, 'message' => 'Jenis jurnal 7 KAIH tidak valid.'], 400);
}

$nis = (string)$_SESSION['no_induk'];
$nisEsc = mysqli_real_escape_string($conn, $nis);
$agamaSelect = kih_column_exists($conn, 'tbl_siswa', 'agama') ? 'agama' : "'' AS agama";
$qSiswa = mysqli_query($conn, "SELECT no_induk, nama_siswa, kelas, $agamaSelect FROM tbl_siswa WHERE no_induk='$nisEsc' LIMIT 1");
$siswa = $qSiswa ? mysqli_fetch_assoc($qSiswa) : [];
$agama = strtolower(trim((string)($siswa['agama'] ?? '')));
$isIslam = strpos($agama, 'islam') !== false;

$prayers = kih_prayers($agama);

if ($habit === 'beribadah') {
    if (!isset($prayers[$prayer])) {
        kih_json(['success' => false, 'message' => 'Pilih waktu ibadah yang valid.'], 400);
    }
    $window = $prayers[$prayer];
    
    // GPS check for Dzuhur & Jumat
    if ($isIslam && ($prayer === 'dzuhur' || $prayer === 'jumat')) {
        if (!$lat || !$lng) {
            kih_json(['success' => false, 'message' => 'Akses lokasi diperlukan untuk memverifikasi posisi sholat di mushola.'], 400);
        }
        $qMushola = @mysqli_query($conn, "SELECT nilai FROM tbl_app_config WHERE kunci='7kih_mushola_locations'");
        $musholas = [];
        if ($qMushola && ($mRow = mysqli_fetch_assoc($qMushola))) {
            $musholas = json_decode($mRow['nilai'], true) ?: [];
        }
        if (!empty($musholas)) {
            $isValidLocation = false;
            foreach ($musholas as $m) {
                if (!isset($m['lat'], $m['lng'])) continue;
                $dist = kih_haversine((float)$lat, (float)$lng, (float)$m['lat'], (float)$m['lng']);
                if ($dist <= (float)($m['radius'] ?? 50)) {
                    $isValidLocation = true;
                    break;
                }
            }
            if (!$isValidLocation) {
                kih_json(['success' => false, 'message' => 'Gagal dikirim. Posisi Anda berada di luar area mushola/masjid yang diizinkan.'], 400);
            }
        }
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
    if (is_file($oldPath) && basename($oldPath) !== basename($photo['absolute'] ?? '')) {
        @unlink($oldPath);
    }
}

[$timeliness, $score] = kih_score($today, $nowTime, $window['start'], $window['end']);
if ($timeliness === 'ditolak' && $habit === 'beribadah') {
    if ($photo['absolute']) @unlink($photo['absolute']);
    kih_json([
        'success' => false, 
        'message' => "Waktu absen ditolak! Pengisian untuk ibadah ini di luar rentang waktu yang sah ({$window['start']} - {$window['end']})."
    ]);
}
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
    'keterangan' => $keterangan,
    'lat' => $lat,
    'lng' => $lng,
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
        (no_induk, nama_siswa, kelas, tanggal, habit_key, habit_label, prayer_key, keterangan, lat, lng, submitted_at, window_start, window_end, timeliness_status, score, photo_path, photo_size, photo_hash, is_photo_stored, user_agent)
    VALUES
        ({$esc['no_induk']}, {$esc['nama_siswa']}, {$esc['kelas']}, {$esc['tanggal']}, {$esc['habit_key']}, {$esc['habit_label']}, {$esc['prayer_key']}, {$esc['keterangan']}, {$esc['lat']}, {$esc['lng']}, {$esc['submitted_at']}, {$esc['window_start']}, {$esc['window_end']}, {$esc['timeliness_status']}, {$score}, {$esc['photo_path']}, " . (int)$photo['size'] . ", {$esc['photo_hash']}, 1, {$esc['user_agent']})
    ON DUPLICATE KEY UPDATE
        nama_siswa=VALUES(nama_siswa),
        kelas=VALUES(kelas),
        habit_label=VALUES(habit_label),
        keterangan=VALUES(keterangan),
        lat=VALUES(lat),
        lng=VALUES(lng),
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
    if ($photo['absolute']) @unlink($photo['absolute']);
    kih_json(['success' => false, 'message' => 'Gagal menyimpan jurnal: ' . mysqli_error($conn)], 500);
}

kih_json([
    'success' => true,
    'message' => 'Jurnal 7 KAIH (Tujuh Kebiasaan Anak Indonesia Hebat) berhasil dikirim.',
    'habit' => $habitLabel,
    'score' => $score,
    'timeliness' => $timeliness,
]);
