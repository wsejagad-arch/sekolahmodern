<?php

/**
 * e-Raport helper.
 * Menyediakan utilitas request HTTP ke server e-Raport.
 */

function eraport_get_config(): array
{
    $baseUrl = getenv('ERAPORT_BASE_URL') ?: '';
    $token = getenv('ERAPORT_TOKEN') ?: '';
    $username = getenv('ERAPORT_ADMIN_USERNAME') ?: '';
    $password = getenv('ERAPORT_ADMIN_PASSWORD') ?: '';
    $sekolahId = getenv('ERAPORT_SEKOLAH_ID') ?: '';
    $semester = getenv('ERAPORT_SEMESTER') ?: '';

    $localConfigPath = __DIR__ . '/config.local.php';
    if (is_file($localConfigPath)) {
        // Isolated scope to avoid leaking variables globally.
        $localVars = (static function (string $path): array {
            $eraport_base_url = null;
            $eraport_token = null;
            $eraport_admin_username = null;
            $eraport_admin_password = null;
            $eraport_sekolah_id = null;
            $eraport_semester = null;
            include $path;
            return [
                'eraport_base_url' => $eraport_base_url,
                'eraport_token' => $eraport_token,
                'eraport_admin_username' => $eraport_admin_username,
                'eraport_admin_password' => $eraport_admin_password,
                'eraport_sekolah_id' => $eraport_sekolah_id,
                'eraport_semester' => $eraport_semester,
            ];
        })($localConfigPath);

        if (!empty($localVars['eraport_base_url'])) {
            $baseUrl = (string)$localVars['eraport_base_url'];
        }
        if (!empty($localVars['eraport_token'])) {
            $token = (string)$localVars['eraport_token'];
        }
        if (!empty($localVars['eraport_admin_username'])) {
            $username = (string)$localVars['eraport_admin_username'];
        }
        if (!empty($localVars['eraport_admin_password'])) {
            $password = (string)$localVars['eraport_admin_password'];
        }
        if (!empty($localVars['eraport_sekolah_id'])) {
            $sekolahId = (string)$localVars['eraport_sekolah_id'];
        }
        if (!empty($localVars['eraport_semester'])) {
            $semester = (string)$localVars['eraport_semester'];
        }
    }

    if ($baseUrl === '') {
        $baseUrl = 'http://103.131.217.1:8239/';
    }

    return [
        'base_url' => rtrim($baseUrl, '/') . '/',
        'token' => $token,
        'admin_username' => $username,
        'admin_password' => $password,
        'sekolah_id' => $sekolahId,
        'semester' => $semester,
    ];
}

function eraport_sha512_hex(string $plain): string
{
    return hash('sha512', $plain);
}

function eraport_parse_csrf_token(string $html): string
{
    if (preg_match('/name=["\']csrf_test_name["\'][^>]*value=["\']([^"\']+)["\']/i', $html, $m)) {
        return (string)$m[1];
    }
    return '';
}

function eraport_parse_login_defaults(string $html): array
{
    $result = [
        'sekolah_id' => '',
        'semester' => '',
        'nm_sek' => '',
    ];

    if (preg_match('/name=["\']sekolahid["\'][\s\S]*?<option\s+value=["\']([^"\']+)["\'][^>]*selected[^>]*>(.*?)<\/option>/i', $html, $m)) {
        $result['sekolah_id'] = trim((string)$m[1]);
        $result['nm_sek'] = trim(strip_tags(html_entity_decode((string)$m[2], ENT_QUOTES | ENT_HTML5, 'UTF-8')));
    } elseif (preg_match('/name=["\']sekolahid["\'][\s\S]*?<option\s+value=["\']([^"\']+)["\'][^>]*>(.*?)<\/option>/i', $html, $m)) {
        $result['sekolah_id'] = trim((string)$m[1]);
        $result['nm_sek'] = trim(strip_tags(html_entity_decode((string)$m[2], ENT_QUOTES | ENT_HTML5, 'UTF-8')));
    }

    if (preg_match('/name=["\']semester["\'][\s\S]*?<option\s+value=["\']([^"\']+)["\'][^>]*selected[^>]*>/i', $html, $m)) {
        $result['semester'] = trim((string)$m[1]);
    } elseif (preg_match('/name=["\']semester["\'][\s\S]*?<option\s+value=["\']([^"\']+)["\'][^>]*>/i', $html, $m)) {
        $result['semester'] = trim((string)$m[1]);
    }

    if ($result['semester'] === '') {
        if (preg_match_all('/name=["\']semester["\'][\s\S]*?<option\s+value=["\']([^"\']*)["\'][^>]*>/i', $html, $allSem)) {
            foreach ($allSem[1] as $semVal) {
                $semVal = trim((string)$semVal);
                if ($semVal !== '') {
                    $result['semester'] = $semVal;
                    break;
                }
            }
        }
    }

    return $result;
}

function eraport_parse_data_ekskul_rows(string $html): array
{
    $rows = [];
    if (preg_match_all('/<tr>\s*<td>(\d+)<\/td>\s*<td>([^<]*)<\/td>\s*<td>([^<]*)<\/td>\s*<td>([^<]*)<\/td>\s*<\/tr>/is', $html, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $rows[] = [
                'no' => (int)$match[1],
                'nama_kelas_ekskul' => trim(html_entity_decode((string)$match[2], ENT_QUOTES | ENT_HTML5, 'UTF-8')),
                'jenis_ekskul' => trim(html_entity_decode((string)$match[3], ENT_QUOTES | ENT_HTML5, 'UTF-8')),
                'nama_ekskul' => trim(html_entity_decode((string)$match[4], ENT_QUOTES | ENT_HTML5, 'UTF-8')),
            ];
        }
    }
    return $rows;
}

function eraport_parse_data_siswa_rows(string $html): array
{
    $rows = [];
    if (preg_match_all('/<tr>\s*<td>(\d+)<\/td>\s*<td>([^<]*)<\/td>\s*<td>([^<]*)<\/td>\s*<td>([^<]*)<\/td>\s*<td>([^<]*)<\/td>\s*<td>([^<]*)<\/td>\s*<td>([^<]*)<\/td>\s*<td>([^<]*)<\/td>\s*<td>([^<]*)<\/td>[\s\S]*?detail_siswa\([\"\']([a-z0-9\-]{6,})[\"\']\)/is', $html, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $rows[] = [
                'no' => (int)$match[1],
                'nama_siswa' => trim(html_entity_decode((string)$match[2], ENT_QUOTES | ENT_HTML5, 'UTF-8')),
                'nis' => trim(html_entity_decode((string)$match[3], ENT_QUOTES | ENT_HTML5, 'UTF-8')),
                'nisn' => trim(html_entity_decode((string)$match[4], ENT_QUOTES | ENT_HTML5, 'UTF-8')),
                'jenis_kelamin' => trim(html_entity_decode((string)$match[5], ENT_QUOTES | ENT_HTML5, 'UTF-8')),
                'ttl' => trim(html_entity_decode((string)$match[6], ENT_QUOTES | ENT_HTML5, 'UTF-8')),
                'agama' => trim(html_entity_decode((string)$match[7], ENT_QUOTES | ENT_HTML5, 'UTF-8')),
                'tingkat' => trim(html_entity_decode((string)$match[8], ENT_QUOTES | ENT_HTML5, 'UTF-8')),
                'kelas' => trim(html_entity_decode((string)$match[9], ENT_QUOTES | ENT_HTML5, 'UTF-8')),
                'peserta_didik_id' => trim((string)$match[10]),
            ];
        }
    }

    return $rows;
}

function eraport_login_and_fetch_data_siswa(?array $override = null): array
{
    $cfg = eraport_get_config();
    $username = (string)($override['username'] ?? $cfg['admin_username'] ?? '');
    $password = (string)($override['password'] ?? $cfg['admin_password'] ?? '');
    $sekolahId = (string)($override['sekolah_id'] ?? $cfg['sekolah_id'] ?? '');
    $semester = (string)($override['semester'] ?? $cfg['semester'] ?? '');

    if ($username === '' || $password === '') {
        return [
            'success' => false,
            'message' => 'Konfigurasi e-Raport belum lengkap (username/password).',
            'items' => [],
        ];
    }

    if (!function_exists('curl_init')) {
        return [
            'success' => false,
            'message' => 'cURL PHP wajib aktif untuk login sesi e-Raport.',
            'items' => [],
        ];
    }

    $baseUrl = (string)$cfg['base_url'];
    $cookieFile = tempnam(sys_get_temp_dir(), 'eraport_cookie_');
    if ($cookieFile === false) {
        return [
            'success' => false,
            'message' => 'Gagal menyiapkan cookie sementara untuk sesi e-Raport.',
            'items' => [],
        ];
    }

    $request = static function (string $url, string $method = 'GET', array $post = []) use ($cookieFile): array {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
        curl_setopt($ch, CURLOPT_USERAGENT, 'SIMANIS-eRaport-Connector/1.0');

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
        }

        $body = curl_exec($ch);
        $err = curl_error($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [
            'status_code' => $code,
            'error' => $err,
            'body' => $body === false ? '' : (string)$body,
        ];
    };

    try {
        $loginPage = $request(eraport_build_url($baseUrl, 'login'));
        if ($loginPage['error'] !== '' || $loginPage['status_code'] < 200 || $loginPage['status_code'] >= 400) {
            return [
                'success' => false,
                'message' => 'Gagal membuka halaman login e-Raport.',
                'debug' => $loginPage,
                'items' => [],
            ];
        }

        $csrf = eraport_parse_csrf_token((string)$loginPage['body']);
        $defaults = eraport_parse_login_defaults((string)$loginPage['body']);

        if ($sekolahId === '') {
            $sekolahId = (string)$defaults['sekolah_id'];
        }
        if ($semester === '') {
            $semester = (string)$defaults['semester'];
        }
        $namaSekolah = (string)($defaults['nm_sek'] ?: 'SMA NEGERI 1 SUMBER');

        if ($sekolahId === '' || $semester === '') {
            return [
                'success' => false,
                'message' => 'sekolah_id/semester tidak ditemukan dari login e-Raport.',
                'items' => [],
            ];
        }

        $payload = [
            'username' => $username,
            'pass' => $password,
            'password' => eraport_sha512_hex($password),
            'semester' => $semester,
            'sekolahid' => $sekolahId,
            'nm_sek' => $namaSekolah,
            'csrf_test_name' => $csrf,
        ];

        $loginSubmit = $request(eraport_build_url($baseUrl, 'login/cekuser'), 'POST', $payload);
        $loginJson = json_decode((string)$loginSubmit['body'], true);
        if ($loginSubmit['error'] !== '' || !$loginJson || (($loginJson['type'] ?? '') !== 'success')) {
            return [
                'success' => false,
                'message' => 'Login e-Raport gagal. Periksa kredensial admin.',
                'debug' => [
                    'status_code' => $loginSubmit['status_code'],
                    'body' => mb_substr((string)$loginSubmit['body'], 0, 400),
                ],
                'items' => [],
            ];
        }

        $siswaPage = $request(eraport_build_url($baseUrl, 'data_siswa'));
        if ($siswaPage['error'] !== '' || $siswaPage['status_code'] < 200 || $siswaPage['status_code'] >= 400) {
            return [
                'success' => false,
                'message' => 'Gagal mengambil halaman data_siswa.',
                'debug' => $siswaPage,
                'items' => [],
            ];
        }

        $items = eraport_parse_data_siswa_rows((string)$siswaPage['body']);

        return [
            'success' => true,
            'message' => 'Data siswa berhasil diambil dari e-Raport.',
            'items' => $items,
            'raw_count' => count($items),
        ];
    } finally {
        @unlink($cookieFile);
    }
}

function eraport_login_and_fetch_ekskul(?array $override = null): array
{
    $cfg = eraport_get_config();
    $username = (string)($override['username'] ?? $cfg['admin_username'] ?? '');
    $password = (string)($override['password'] ?? $cfg['admin_password'] ?? '');
    $sekolahId = (string)($override['sekolah_id'] ?? $cfg['sekolah_id'] ?? '');
    $semester = (string)($override['semester'] ?? $cfg['semester'] ?? '');

    if ($username === '' || $password === '') {
        return [
            'success' => false,
            'message' => 'Konfigurasi e-Raport belum lengkap (username/password).',
            'items' => [],
        ];
    }

    if (!function_exists('curl_init')) {
        return [
            'success' => false,
            'message' => 'cURL PHP wajib aktif untuk login sesi e-Raport.',
            'items' => [],
        ];
    }

    $baseUrl = (string)$cfg['base_url'];
    $cookieFile = tempnam(sys_get_temp_dir(), 'eraport_cookie_');
    if ($cookieFile === false) {
        return [
            'success' => false,
            'message' => 'Gagal menyiapkan cookie sementara untuk sesi e-Raport.',
            'items' => [],
        ];
    }

    $request = static function (string $url, string $method = 'GET', array $post = []) use ($cookieFile): array {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
        curl_setopt($ch, CURLOPT_USERAGENT, 'SIMANIS-eRaport-Connector/1.0');

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
        }

        $body = curl_exec($ch);
        $err = curl_error($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [
            'status_code' => $code,
            'error' => $err,
            'body' => $body === false ? '' : (string)$body,
        ];
    };

    try {
        $loginPage = $request(eraport_build_url($baseUrl, 'login'));
        if ($loginPage['error'] !== '' || $loginPage['status_code'] < 200 || $loginPage['status_code'] >= 400) {
            return [
                'success' => false,
                'message' => 'Gagal membuka halaman login e-Raport.',
                'debug' => $loginPage,
                'items' => [],
            ];
        }

        $csrf = eraport_parse_csrf_token((string)$loginPage['body']);
        $defaults = eraport_parse_login_defaults((string)$loginPage['body']);

        if ($sekolahId === '') {
            $sekolahId = (string)$defaults['sekolah_id'];
        }
        if ($semester === '') {
            $semester = (string)$defaults['semester'];
        }
        $namaSekolah = (string)($defaults['nm_sek'] ?: 'SMA NEGERI 1 SUMBER');

        if ($sekolahId === '' || $semester === '') {
            return [
                'success' => false,
                'message' => 'sekolah_id/semester tidak ditemukan dari login e-Raport.',
                'items' => [],
            ];
        }

        $payload = [
            'username' => $username,
            'pass' => $password,
            'password' => eraport_sha512_hex($password),
            'semester' => $semester,
            'sekolahid' => $sekolahId,
            'nm_sek' => $namaSekolah,
            'csrf_test_name' => $csrf,
        ];

        $loginSubmit = $request(eraport_build_url($baseUrl, 'login/cekuser'), 'POST', $payload);
        $loginJson = json_decode((string)$loginSubmit['body'], true);
        if ($loginSubmit['error'] !== '' || !$loginJson || (($loginJson['type'] ?? '') !== 'success')) {
            return [
                'success' => false,
                'message' => 'Login e-Raport gagal. Periksa kredensial admin.',
                'debug' => [
                    'status_code' => $loginSubmit['status_code'],
                    'body' => mb_substr((string)$loginSubmit['body'], 0, 400),
                ],
                'items' => [],
            ];
        }

        $ekskulPage = $request(eraport_build_url($baseUrl, 'data_ekskul'));
        if ($ekskulPage['error'] !== '' || $ekskulPage['status_code'] < 200 || $ekskulPage['status_code'] >= 400) {
            return [
                'success' => false,
                'message' => 'Gagal mengambil halaman data_ekskul.',
                'debug' => $ekskulPage,
                'items' => [],
            ];
        }

        $items = eraport_parse_data_ekskul_rows((string)$ekskulPage['body']);

        return [
            'success' => true,
            'message' => 'Data ekskul berhasil diambil dari e-Raport.',
            'items' => $items,
            'raw_count' => count($items),
        ];
    } finally {
        @unlink($cookieFile);
    }
}

function eraport_extract_student_ids(string $html, int $limit = 10): array
{
    $ids = [];

    if (preg_match_all('/tampil_siswa\/([a-z0-9\-]{6,})/i', $html, $m)) {
        foreach ($m[1] as $id) {
            $ids[] = trim((string)$id);
        }
    }

    if (preg_match_all('/tampil_siswa\s*\(\s*[\"\']?([a-z0-9\-]{6,})[\"\']?\s*\)/i', $html, $m2)) {
        foreach ($m2[1] as $id) {
            $ids[] = trim((string)$id);
        }
    }

    if (preg_match_all('/\b([0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12})\b/i', $html, $m3)) {
        foreach ($m3[1] as $id) {
            $ids[] = trim((string)$id);
        }
    }

    if (preg_match_all('/(?:data-id|id_siswa|siswa_id)\s*[=:]\s*[\"\']([a-z0-9\-]{6,})[\"\']/i', $html, $m4)) {
        foreach ($m4[1] as $id) {
            $ids[] = trim((string)$id);
        }
    }

    $ids = array_values(array_unique(array_filter($ids, static function ($v) {
        return (string)$v !== '';
    })));

    if ($limit > 0 && count($ids) > $limit) {
        $ids = array_slice($ids, 0, $limit);
    }

    return $ids;
}

function eraport_extract_candidate_endpoints_from_html(string $html, string $baseUrl): array
{
    $hits = [];

    if (preg_match_all('/(?:url|href|src)\s*[:=]\s*[\"\']([^\"\']+)[\"\']/i', $html, $m1)) {
        foreach ($m1[1] as $url) {
            $hits[] = (string)$url;
        }
    }

    if (preg_match_all('/[\"\'](\/[a-z0-9_\-\/]+)[\"\']/i', $html, $m2)) {
        foreach ($m2[1] as $url) {
            $hits[] = (string)$url;
        }
    }

    $endpoints = [];
    $base = rtrim($baseUrl, '/');
    foreach ($hits as $raw) {
        $u = trim((string)$raw);
        if ($u === '' || stripos($u, 'javascript:') === 0 || stripos($u, 'data:') === 0) {
            continue;
        }

        if (stripos($u, $base) === 0) {
            $u = substr($u, strlen($base));
        }

        if (strpos($u, '?') !== false) {
            $u = (string)strtok($u, '?');
        }

        $u = trim($u, " \t\r\n/");
        if ($u === '' || strpos($u, '.') !== false) {
            continue;
        }

        if (!preg_match('/(siswa|ekskul|ekstra|rapor|nilai|rombel)/i', $u)) {
            continue;
        }

        $endpoints[] = $u;
    }

    return array_values(array_unique($endpoints));
}

function eraport_parse_student_ekskul_relations(string $html, string $sourceEndpoint = ''): array
{
    $rows = [];
    $nis = '';
    $namaSiswa = '';

    if (preg_match('/\bNIS\b[^0-9]{0,20}([0-9]{4,20})/i', strip_tags($html), $mNis)) {
        $nis = trim((string)$mNis[1]);
    }

    if (preg_match('/\bNama\s+Siswa\b[^A-Za-z0-9]{0,20}([^\n\r<]+)/i', strip_tags($html), $mNama)) {
        $namaSiswa = trim((string)$mNama[1]);
    }

    if (preg_match_all('/Ekstrakurikuler\s*<\/[^>]+>\s*<[^>]+>\s*([^<]+)\s*</i', $html, $m1)) {
        foreach ($m1[1] as $namaEkskul) {
            $namaEkskul = trim(html_entity_decode((string)$namaEkskul, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            if ($namaEkskul === '' || $namaEkskul === '-') {
                continue;
            }
            $rows[] = [
                'nis' => $nis,
                'nama_siswa' => $namaSiswa,
                'nama_ekskul' => $namaEkskul,
                'sumber_endpoint' => $sourceEndpoint,
            ];
        }
    }

    if (preg_match_all('/<li[^>]*>\s*([^<]*(ekskul|ekstra|ekstrakurikuler)[^<]*)\s*<\/li>/i', $html, $m2, PREG_SET_ORDER)) {
        foreach ($m2 as $match) {
            $text = trim(html_entity_decode(strip_tags((string)$match[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            if ($text === '') {
                continue;
            }
            $text = preg_replace('/^\s*(ekskul|ekstra|ekstrakurikuler)\s*[:\-]?\s*/i', '', $text);
            if ($text === '') {
                continue;
            }
            $rows[] = [
                'nis' => $nis,
                'nama_siswa' => $namaSiswa,
                'nama_ekskul' => trim((string)$text),
                'sumber_endpoint' => $sourceEndpoint,
            ];
        }
    }

    if (preg_match_all('/<tr[^>]*>(.*?)<\/tr>/is', $html, $trMatches)) {
        foreach ($trMatches[1] as $tr) {
            if (!preg_match_all('/<td[^>]*>(.*?)<\/td>/is', (string)$tr, $tdMatches)) {
                continue;
            }
            $cells = array_map(static function ($td) {
                return trim(html_entity_decode(strip_tags((string)$td), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            }, $tdMatches[1]);

            if (count($cells) < 2) {
                continue;
            }

            $rowText = strtolower(implode(' ', $cells));
            if (strpos($rowText, 'ekskul') === false && strpos($rowText, 'ekstra') === false && strpos($rowText, 'ekstrakurikuler') === false) {
                continue;
            }

            $namaEkskul = end($cells);
            if (!is_string($namaEkskul) || $namaEkskul === '' || $namaEkskul === '-') {
                continue;
            }

            $rows[] = [
                'nis' => $nis,
                'nama_siswa' => $namaSiswa,
                'nama_ekskul' => trim($namaEkskul),
                'sumber_endpoint' => $sourceEndpoint,
            ];
        }
    }

    $normalized = [];
    foreach ($rows as $r) {
        $key = strtolower(trim((string)$r['nis'])) . '|' . strtolower(trim((string)$r['nama_siswa'])) . '|' . strtolower(trim((string)$r['nama_ekskul']));
        $normalized[$key] = $r;
    }

    return array_values($normalized);
}

function eraport_discover_and_fetch_student_ekskul(?array $override = null): array
{
    $cfg = eraport_get_config();
    $username = (string)($override['username'] ?? $cfg['admin_username'] ?? '');
    $password = (string)($override['password'] ?? $cfg['admin_password'] ?? '');
    $sekolahId = (string)($override['sekolah_id'] ?? $cfg['sekolah_id'] ?? '');
    $semester = (string)($override['semester'] ?? $cfg['semester'] ?? '');
    $deepProbe = !empty($override['deep_probe']);
    $studentLimit = (int)($override['student_limit'] ?? ($deepProbe ? 25 : 8));
    if ($studentLimit < 1) {
        $studentLimit = 8;
    }

    if ($username === '' || $password === '') {
        return [
            'success' => false,
            'message' => 'Konfigurasi e-Raport belum lengkap (username/password).',
            'relations' => [],
            'candidates' => [],
        ];
    }

    if (!function_exists('curl_init')) {
        return [
            'success' => false,
            'message' => 'cURL PHP wajib aktif untuk discovery sesi e-Raport.',
            'relations' => [],
            'candidates' => [],
        ];
    }

    $baseUrl = (string)$cfg['base_url'];
    $cookieFile = tempnam(sys_get_temp_dir(), 'eraport_cookie_');
    if ($cookieFile === false) {
        return [
            'success' => false,
            'message' => 'Gagal menyiapkan cookie sementara untuk sesi e-Raport.',
            'relations' => [],
            'candidates' => [],
        ];
    }

    $request = static function (string $url, string $method = 'GET', array $post = []) use ($cookieFile): array {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
        curl_setopt($ch, CURLOPT_USERAGENT, 'SIMANIS-eRaport-Connector/1.0');

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
        }

        $body = curl_exec($ch);
        $err = curl_error($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [
            'status_code' => $code,
            'error' => $err,
            'body' => $body === false ? '' : (string)$body,
        ];
    };

    try {
        $loginPage = $request(eraport_build_url($baseUrl, 'login'));
        if ($loginPage['error'] !== '' || $loginPage['status_code'] < 200 || $loginPage['status_code'] >= 400) {
            return [
                'success' => false,
                'message' => 'Gagal membuka halaman login e-Raport.',
                'debug' => $loginPage,
                'relations' => [],
                'candidates' => [],
            ];
        }

        $csrf = eraport_parse_csrf_token((string)$loginPage['body']);
        $defaults = eraport_parse_login_defaults((string)$loginPage['body']);
        if ($sekolahId === '') {
            $sekolahId = (string)$defaults['sekolah_id'];
        }
        if ($semester === '') {
            $semester = (string)$defaults['semester'];
        }
        $namaSekolah = (string)($defaults['nm_sek'] ?: 'SMA NEGERI 1 SUMBER');

        if ($sekolahId === '' || $semester === '') {
            return [
                'success' => false,
                'message' => 'sekolah_id/semester tidak ditemukan dari login e-Raport.',
                'relations' => [],
                'candidates' => [],
            ];
        }

        $payload = [
            'username' => $username,
            'pass' => $password,
            'password' => eraport_sha512_hex($password),
            'semester' => $semester,
            'sekolahid' => $sekolahId,
            'nm_sek' => $namaSekolah,
            'csrf_test_name' => $csrf,
        ];

        $loginSubmit = $request(eraport_build_url($baseUrl, 'login/cekuser'), 'POST', $payload);
        $loginJson = json_decode((string)$loginSubmit['body'], true);
        if ($loginSubmit['error'] !== '' || !$loginJson || (($loginJson['type'] ?? '') !== 'success')) {
            return [
                'success' => false,
                'message' => 'Login e-Raport gagal. Periksa kredensial admin.',
                'debug' => [
                    'status_code' => $loginSubmit['status_code'],
                    'body' => mb_substr((string)$loginSubmit['body'], 0, 400),
                ],
                'relations' => [],
                'candidates' => [],
            ];
        }

        $seedPage = $request(eraport_build_url($baseUrl, 'data_siswa'));
        if ($seedPage['error'] !== '' || $seedPage['status_code'] < 200 || $seedPage['status_code'] >= 400) {
            return [
                'success' => false,
                'message' => 'Gagal mengambil halaman data_siswa untuk discovery.',
                'debug' => $seedPage,
                'relations' => [],
                'candidates' => [],
            ];
        }

        $seedHtml = (string)$seedPage['body'];
        $studentIds = eraport_extract_student_ids($seedHtml, $studentLimit);
        $autoEndpoints = eraport_extract_candidate_endpoints_from_html($seedHtml, $baseUrl);

        $endpoints = [
            'data_siswa',
            'data_ekskul',
            'nilai_rapor',
            'nilai_siswa',
            'ekskul_siswa',
            'ekstra_siswa',
            'data_ekskul_siswa',
            'siswa_ekskul',
        ];

        if ($deepProbe) {
            $endpoints = array_merge($endpoints, [
                'leger_kelas',
                'status_penilaian',
                'riwayat_nilaisiswa',
                'transkrip_nilai',
                'plk_rapor',
                'anggota_ekskul',
                'peserta_ekskul',
                'nilai_ekskul',
                'nilai_rapor_ekskul',
            ]);
        }

        if (!empty($autoEndpoints)) {
            $endpoints = array_merge($endpoints, $autoEndpoints);
        }

        foreach ($studentIds as $sid) {
            $endpoints[] = 'tampil_siswa/' . $sid;
            $endpoints[] = 'detail_siswa/' . $sid;
            $endpoints[] = 'ekskul_siswa/' . $sid;
            $endpoints[] = 'data_ekskul_siswa/' . $sid;
            $endpoints[] = 'tampil_ekskul/' . $sid;
            $endpoints[] = 'tampil_nilai_rapor/' . $sid;
            $endpoints[] = 'nilai_rapor_siswa/' . $sid;
            if ($deepProbe) {
                $endpoints[] = 'plk_rapor/' . $sid;
                $endpoints[] = 'transkrip_nilai/' . $sid;
                $endpoints[] = 'riwayat_nilaisiswa/' . $sid;
            }
        }

        $endpoints = array_values(array_unique($endpoints));

        $candidates = [];
        $relations = [];

        foreach ($endpoints as $ep) {
            $httpMethod = 'GET';
            $resp = $request(eraport_build_url($baseUrl, $ep));
            if (($ep === 'data_siswa' || strpos($ep, 'tampil_siswa/') === 0) && ((int)$resp['status_code'] >= 400 || trim((string)$resp['body']) === '')) {
                $httpMethod = 'POST';
                $resp = $request(eraport_build_url($baseUrl, $ep), 'POST', []);
            }

            if ($resp['error'] !== '') {
                continue;
            }

            $status = (int)$resp['status_code'];
            if ($status < 200 || $status >= 400) {
                continue;
            }

            $body = (string)$resp['body'];
            $bodyLower = strtolower($body);
            $hasKeyword = (strpos($bodyLower, 'ekskul') !== false)
                || (strpos($bodyLower, 'ekstra') !== false)
                || (strpos($bodyLower, 'ekstrakurikuler') !== false);

            $parsed = eraport_parse_student_ekskul_relations($body, $ep);

            if ($hasKeyword || !empty($parsed)) {
                $candidates[] = [
                    'endpoint' => $ep,
                    'method' => $httpMethod,
                    'status_code' => $status,
                    'has_keyword' => $hasKeyword,
                    'relations_found' => count($parsed),
                    'preview' => mb_substr(trim(strip_tags($body)), 0, 220),
                ];
            }

            if (!empty($parsed)) {
                foreach ($parsed as $row) {
                    $relations[] = $row;
                }
            }
        }

        $uniq = [];
        foreach ($relations as $row) {
            $key = strtolower(trim((string)($row['nis'] ?? ''))) . '|'
                . strtolower(trim((string)($row['nama_siswa'] ?? ''))) . '|'
                . strtolower(trim((string)($row['nama_ekskul'] ?? '')));
            $uniq[$key] = $row;
        }

        return [
            'success' => true,
            'message' => 'Discovery endpoint ekskul siswa selesai.',
            'relations' => array_values($uniq),
            'candidates' => $candidates,
            'student_ids_sample' => $studentIds,
            'auto_endpoints_sample' => array_slice($autoEndpoints, 0, 30),
            'endpoint_checked' => count($endpoints),
            'deep_probe' => $deepProbe,
        ];
    } finally {
        @unlink($cookieFile);
    }
}

function eraport_build_url(string $baseUrl, string $endpoint): string
{
    $endpoint = trim($endpoint);
    if ($endpoint === '') {
        return $baseUrl;
    }

    if (stripos($endpoint, 'http://') === 0 || stripos($endpoint, 'https://') === 0) {
        return $endpoint;
    }

    return rtrim($baseUrl, '/') . '/' . ltrim($endpoint, '/');
}

function eraport_request(string $endpoint, string $method = 'GET', array $payload = [], ?string $tokenOverride = null): array
{
    $cfg = eraport_get_config();
    $url = eraport_build_url($cfg['base_url'], $endpoint);
    $token = $tokenOverride !== null && $tokenOverride !== '' ? $tokenOverride : (string)$cfg['token'];

    $method = strtoupper(trim($method));
    if ($method !== 'GET' && $method !== 'POST') {
        $method = 'GET';
    }

    $headers = [
        'Accept: application/json, text/plain, */*',
        'User-Agent: SIMANIS-eRaport-Connector/1.0',
    ];

    if ($token !== '') {
        $headers[] = 'Authorization: Bearer ' . $token;
        $headers[] = 'X-Token: ' . $token;
        $headers[] = 'token: ' . $token;
    }

    $requestBody = '';
    if ($method === 'GET' && !empty($payload)) {
        $query = http_build_query($payload);
        $url .= (strpos($url, '?') === false ? '?' : '&') . $query;
    }

    if ($method === 'POST') {
        $requestBody = http_build_query($payload);
        $headers[] = 'Content-Type: application/x-www-form-urlencoded';
    }

    $statusCode = 0;
    $responseBody = '';
    $error = '';

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 25);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $requestBody);
        }

        $raw = curl_exec($ch);
        if ($raw === false) {
            $error = (string)curl_error($ch);
        } else {
            $responseBody = (string)$raw;
        }

        $statusCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
    } else {
        $ctx = stream_context_create([
            'http' => [
                'method' => $method,
                'header' => implode("\r\n", $headers),
                'content' => $method === 'POST' ? $requestBody : '',
                'timeout' => 25,
                'ignore_errors' => true,
            ],
        ]);
        $raw = @file_get_contents($url, false, $ctx);
        if ($raw === false) {
            $error = 'HTTP request gagal (stream context).';
        } else {
            $responseBody = (string)$raw;
        }

        if (!empty($http_response_header) && preg_match('/\s(\d{3})\s/', (string)$http_response_header[0], $m)) {
            $statusCode = (int)$m[1];
        }
    }

    $json = null;
    if ($responseBody !== '') {
        $decoded = json_decode($responseBody, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $json = $decoded;
        }
    }

    return [
        'success' => $error === '' && $statusCode >= 200 && $statusCode < 500,
        'status_code' => $statusCode,
        'url' => $url,
        'method' => $method,
        'has_token' => $token !== '',
        'error' => $error,
        'body' => $responseBody,
        'json' => $json,
    ];
}

function eraport_col_letters_to_index(string $letters): int
{
    $letters = strtoupper(trim($letters));
    $idx = 0;
    $len = strlen($letters);
    for ($i = 0; $i < $len; $i++) {
        $char = ord($letters[$i]) - 64;
        if ($char < 1 || $char > 26) {
            continue;
        }
        $idx = ($idx * 26) + $char;
    }
    return max(0, $idx - 1);
}

function eraport_zip_entries_from_binary(string $binary): array
{
    $entries = [];
    $eocdPos = strrpos($binary, "PK\x05\x06");
    if ($eocdPos === false || strlen($binary) < $eocdPos + 22) {
        return [];
    }

    $eocd = unpack('vdisk/vcdisk/ventriesDisk/ventries/VcdSize/VcdOffset/vcommentLen', substr($binary, $eocdPos + 4, 18));
    if (!is_array($eocd)) {
        return [];
    }

    $ptr = (int)$eocd['cdOffset'];
    $entriesCount = (int)$eocd['entries'];
    for ($i = 0; $i < $entriesCount; $i++) {
        if (substr($binary, $ptr, 4) !== "PK\x01\x02") {
            break;
        }

        $h = unpack(
            'vverMade/vverNeed/vflags/vmethod/vtime/vdate/Vcrc/VcompSize/VuncompSize/vnameLen/vextraLen/vcommentLen/vdisk/vintAttr/VextAttr/VlocalOffset',
            substr($binary, $ptr + 4, 42)
        );
        if (!is_array($h)) {
            break;
        }

        $nameLen = (int)$h['nameLen'];
        $extraLen = (int)$h['extraLen'];
        $commentLen = (int)$h['commentLen'];
        $name = substr($binary, $ptr + 46, $nameLen);
        $localOffset = (int)$h['localOffset'];

        if (substr($binary, $localOffset, 4) === "PK\x03\x04") {
            $lh = unpack('vver/vflags/vmethod/vtime/vdate/Vcrc/VcompSize/VuncompSize/vnameLen/vextraLen', substr($binary, $localOffset + 4, 26));
            if (is_array($lh)) {
                $dataStart = $localOffset + 30 + (int)$lh['nameLen'] + (int)$lh['extraLen'];
                $compressed = substr($binary, $dataStart, (int)$h['compSize']);
                if ((int)$h['method'] === 0) {
                    $entries[$name] = $compressed;
                } elseif ((int)$h['method'] === 8) {
                    $data = @gzinflate($compressed);
                    if ($data !== false) {
                        $entries[$name] = $data;
                    }
                }
            }
        }

        $ptr += 46 + $nameLen + $extraLen + $commentLen;
    }

    return $entries;
}

function eraport_parse_xlsx_rows_from_binary(string $binary): array
{
    $rows = [];
    try {
        $entries = [];
        $zip = null;
        if (class_exists('ZipArchive')) {
            $tmp = tempnam(sys_get_temp_dir(), 'eraport_xlsx_');
            if ($tmp !== false) {
                file_put_contents($tmp, $binary);
                $zip = new ZipArchive();
                if ($zip->open($tmp) === true) {
                    $entries = null;
                } else {
                    $zip = null;
                    @unlink($tmp);
                }
            }
        }

        if ($zip === null) {
            $entries = eraport_zip_entries_from_binary($binary);
        }

        $getFromName = static function (string $name) use (&$zip, &$entries) {
            if ($zip instanceof ZipArchive) {
                return $zip->getFromName($name);
            }
            return is_array($entries) && array_key_exists($name, $entries) ? (string)$entries[$name] : false;
        };

        $sharedStrings = [];
        $sharedXml = $getFromName('xl/sharedStrings.xml');
        if (is_string($sharedXml) && $sharedXml !== '') {
            $sx = @simplexml_load_string($sharedXml);
            if ($sx && isset($sx->si)) {
                foreach ($sx->si as $si) {
                    if (isset($si->t)) {
                        $sharedStrings[] = (string)$si->t;
                    } else {
                        $parts = [];
                        if (isset($si->r)) {
                            foreach ($si->r as $r) {
                                if (isset($r->t)) {
                                    $parts[] = (string)$r->t;
                                }
                            }
                        }
                        $sharedStrings[] = implode('', $parts);
                    }
                }
            }
        }

        $sheetXml = $getFromName('xl/worksheets/sheet1.xml');
        if (!is_string($sheetXml) || $sheetXml === '') {
            $sheetXml = $getFromName('xl/worksheets/sheet2.xml');
        }

        if (!is_string($sheetXml) || $sheetXml === '') {
            if ($zip instanceof ZipArchive) {
                $zip->close();
            }
            return [];
        }

        $sxSheet = @simplexml_load_string($sheetXml);
        if (!$sxSheet || !isset($sxSheet->sheetData->row)) {
            if ($zip instanceof ZipArchive) {
                $zip->close();
            }
            return [];
        }

        foreach ($sxSheet->sheetData->row as $row) {
            $line = [];
            if (!isset($row->c)) {
                continue;
            }

            foreach ($row->c as $c) {
                $ref = (string)$c['r'];
                $type = (string)$c['t'];
                if (!preg_match('/^([A-Z]+)\d+$/', $ref, $mRef)) {
                    continue;
                }
                $colIdx = eraport_col_letters_to_index((string)$mRef[1]);

                $val = '';
                if (isset($c->v)) {
                    $raw = (string)$c->v;
                    if ($type === 's') {
                        $sIdx = (int)$raw;
                        $val = (string)($sharedStrings[$sIdx] ?? '');
                    } else {
                        $val = $raw;
                    }
                } elseif (isset($c->is->t)) {
                    $val = (string)$c->is->t;
                }

                $line[$colIdx] = trim(html_entity_decode((string)$val, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            }

            if (!empty($line)) {
                ksort($line);
                $rows[] = $line;
            }
        }

        if ($zip instanceof ZipArchive) {
            $zip->close();
        }
        return $rows;
    } finally {
        if (isset($tmp) && is_string($tmp)) {
            @unlink($tmp);
        }
    }
}

function eraport_extract_leger_rows(array $xlsxRows): array
{
    if (empty($xlsxRows)) {
        return [];
    }

    $headerRow = [];
    $headerIndex = -1;
    foreach ($xlsxRows as $idx => $line) {
        $vals = array_map(static function ($v) {
            return strtolower(trim((string)$v));
        }, $line);

        $hasNama = false;
        $hasNis = false;
        foreach ($vals as $v) {
            if (strpos($v, 'nama') !== false) {
                $hasNama = true;
            }
            if ($v === 'nis' || strpos($v, 'nis ') === 0 || strpos($v, 'nisn') === 0) {
                $hasNis = true;
            }
        }

        if ($hasNama && $hasNis) {
            $headerRow = $line;
            $headerIndex = $idx;
            break;
        }
    }

    if ($headerIndex < 0 || empty($headerRow)) {
        return [];
    }

    $headers = [];
    foreach ($headerRow as $k => $v) {
        $headers[$k] = trim((string)$v);
    }

    $result = [];
    for ($i = $headerIndex + 1; $i < count($xlsxRows); $i++) {
        $line = $xlsxRows[$i];
        if (empty($line)) {
            continue;
        }

        $assoc = [];
        foreach ($headers as $col => $name) {
            if ($name === '') {
                continue;
            }
            $assoc[$name] = trim((string)($line[$col] ?? ''));
        }

        if (empty($assoc)) {
            continue;
        }

        $nis = '';
        $nama = '';
        $avg = null;
        $nums = [];

        foreach ($assoc as $key => $val) {
            $k = strtolower(trim((string)$key));
            if ($nis === '' && ($k === 'nis' || strpos($k, ' nis') !== false || strpos($k, 'nis ') === 0)) {
                $nis = trim((string)$val);
            }
            if ($nama === '' && strpos($k, 'nama') !== false) {
                $nama = trim((string)$val);
            }

            $num = str_replace(',', '.', str_replace(' ', '', (string)$val));
            if (is_numeric($num)) {
                $f = (float)$num;
                if (strpos($k, 'rata') !== false || strpos($k, 'rerata') !== false) {
                    $avg = $f;
                }

                if (
                    strpos($k, 'nis') === false &&
                    strpos($k, 'nama') === false &&
                    strpos($k, 'no') !== 0 &&
                    strpos($k, 'kelas') === false &&
                    strpos($k, 'ttl') === false &&
                    strpos($k, 'lahir') === false
                ) {
                    $nums[] = $f;
                }
            }
        }

        if ($avg === null && !empty($nums)) {
            $avg = round(array_sum($nums) / count($nums), 2);
        }

        if ($nama === '' && $nis === '') {
            continue;
        }

        $result[] = [
            'nis' => $nis,
            'nama_siswa' => $nama,
            'nilai_rerata' => $avg,
            'raw' => $assoc,
        ];
    }

    return $result;
}

function eraport_extract_leger_detail_rows(array $xlsxRows): array
{
    $empty = [
        'meta' => ['sekolah' => '', 'kelas' => ''],
        'subjects' => [],
        'students' => [],
        'details' => [],
    ];
    if (empty($xlsxRows)) {
        return $empty;
    }

    $meta = $empty['meta'];
    foreach (array_slice($xlsxRows, 0, 8) as $line) {
        $joined = trim(implode(' ', array_map('strval', $line)));
        if (stripos($joined, 'sekolah') !== false && preg_match('/:\s*(.+)$/', $joined, $m)) {
            $meta['sekolah'] = trim((string)$m[1]);
        }
        if (stripos($joined, 'kelas') !== false && preg_match('/:\s*(.+)$/', $joined, $m)) {
            $meta['kelas'] = trim((string)$m[1]);
        }
    }

    $identityRowIndex = -1;
    $semesterRowIndex = -1;
    foreach ($xlsxRows as $idx => $line) {
        $vals = array_map(static fn($v) => strtolower(trim((string)$v)), $line);
        if ($identityRowIndex < 0 && in_array('nama siswa', $vals, true) && (in_array('nis', $vals, true) || in_array('nisn', $vals, true))) {
            $identityRowIndex = $idx;
        }
        foreach ($vals as $v) {
            if (preg_match('/^smt\s*\d+$/i', $v) || in_array($v, ['rerata', 'rata-rata', 'rata rata'], true)) {
                $semesterRowIndex = $idx;
                break 2;
            }
        }
    }

    if ($identityRowIndex < 0 || $semesterRowIndex < 0) {
        return $empty;
    }

    $identityRow = $xlsxRows[$identityRowIndex];
    $semesterRow = $xlsxRows[$semesterRowIndex];
    $nameCol = null;
    $nisnCol = null;
    $nisCol = null;
    foreach ($identityRow as $col => $label) {
        $l = strtolower(trim((string)$label));
        if ($nameCol === null && strpos($l, 'nama') !== false) {
            $nameCol = (int)$col;
        } elseif ($nisnCol === null && $l === 'nisn') {
            $nisnCol = (int)$col;
        } elseif ($nisCol === null && $l === 'nis') {
            $nisCol = (int)$col;
        }
    }
    if ($nameCol === null) {
        return $empty;
    }
    $firstScoreCol = max(array_filter([$nameCol, $nisnCol ?? 0, $nisCol ?? 0])) + 1;

    $subjectNameByStart = [];
    for ($r = $identityRowIndex + 1; $r < $semesterRowIndex; $r++) {
        foreach (($xlsxRows[$r] ?? []) as $col => $value) {
            $value = trim((string)$value);
            if ($value !== '' && (int)$col >= $firstScoreCol) {
                $subjectNameByStart[(int)$col] = $value;
            }
        }
    }
    ksort($subjectNameByStart);

    $subjects = [];
    $currentSubject = '';
    $subjectNumber = 0;
    foreach ($semesterRow as $col => $semesterLabel) {
        $col = (int)$col;
        if ($col < $firstScoreCol) {
            continue;
        }
        $semesterLabel = trim((string)$semesterLabel);
        if ($semesterLabel === '') {
            continue;
        }
        if (isset($subjectNameByStart[$col])) {
            $currentSubject = $subjectNameByStart[$col];
            $subjectNumber++;
        } elseif ($currentSubject === '') {
            $subjectNumber++;
            $currentSubject = 'Mapel ' . $subjectNumber;
        }
        $subjects[$col] = [
            'mapel' => $currentSubject,
            'komponen' => $semesterLabel,
        ];
    }

    $students = [];
    $details = [];
    for ($r = $semesterRowIndex + 1; $r < count($xlsxRows); $r++) {
        $line = $xlsxRows[$r];
        $nama = trim((string)($line[$nameCol] ?? ''));
        $nis = $nisCol !== null ? trim((string)($line[$nisCol] ?? '')) : '';
        $nisn = $nisnCol !== null ? trim((string)($line[$nisnCol] ?? '')) : '';
        if ($nama === '' && $nis === '' && $nisn === '') {
            continue;
        }
        if (stripos($nama, 'jumlah') !== false || stripos($nama, 'rata') !== false) {
            continue;
        }

        $studentKey = $nis !== '' ? $nis : ($nisn !== '' ? $nisn : md5($nama));
        $students[$studentKey] = [
            'nis' => $nis,
            'nisn' => $nisn,
            'nama_siswa' => $nama,
        ];

        foreach ($subjects as $col => $subject) {
            $raw = trim((string)($line[$col] ?? ''));
            if ($raw === '') {
                continue;
            }
            $num = str_replace(',', '.', str_replace(' ', '', $raw));
            if (!is_numeric($num)) {
                continue;
            }
            $details[] = [
                'nis' => $nis,
                'nisn' => $nisn,
                'nama_siswa' => $nama,
                'mapel' => (string)$subject['mapel'],
                'komponen' => (string)$subject['komponen'],
                'nilai' => round((float)$num, 2),
            ];
        }
    }

    return [
        'meta' => $meta,
        'subjects' => array_values(array_unique(array_map(static fn($s) => (string)$s['mapel'], $subjects))),
        'students' => array_values($students),
        'details' => $details,
    ];
}

function eraport_ensure_leger_tables(mysqli $conn): void
{
    @mysqli_query($conn, "CREATE TABLE IF NOT EXISTS tbl_leger_siswa_eraport (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        run_id VARCHAR(40) NOT NULL,
        synced_at DATETIME NOT NULL,
        semester VARCHAR(30) DEFAULT NULL,
        kelas VARCHAR(80) NOT NULL,
        nis VARCHAR(40) DEFAULT NULL,
        nama_siswa VARCHAR(200) DEFAULT NULL,
        nilai_rerata DECIMAL(6,2) DEFAULT NULL,
        raw_row LONGTEXT,
        PRIMARY KEY (id),
        KEY idx_run (run_id),
        KEY idx_kelas (kelas),
        KEY idx_nis (nis),
        KEY idx_synced_at (synced_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    @mysqli_query($conn, "CREATE TABLE IF NOT EXISTS tbl_leger_nilai_raport_siswa (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        run_id VARCHAR(40) NOT NULL,
        uploaded_at DATETIME NOT NULL,
        semester VARCHAR(30) DEFAULT NULL,
        kelas VARCHAR(80) NOT NULL,
        nis VARCHAR(40) DEFAULT NULL,
        nisn VARCHAR(40) DEFAULT NULL,
        nama_siswa VARCHAR(200) DEFAULT NULL,
        mapel VARCHAR(180) NOT NULL,
        komponen VARCHAR(40) NOT NULL,
        nilai DECIMAL(6,2) NOT NULL,
        uploaded_by VARCHAR(50) DEFAULT NULL,
        source_file VARCHAR(255) DEFAULT NULL,
        PRIMARY KEY (id),
        KEY idx_run (run_id),
        KEY idx_kelas (kelas),
        KEY idx_nis (nis),
        KEY idx_mapel (mapel),
        KEY idx_uploaded_at (uploaded_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $extraColumns = [
        'uploaded_by' => "ALTER TABLE tbl_leger_siswa_eraport ADD COLUMN uploaded_by VARCHAR(50) DEFAULT NULL AFTER raw_row",
        'source_file' => "ALTER TABLE tbl_leger_siswa_eraport ADD COLUMN source_file VARCHAR(255) DEFAULT NULL AFTER uploaded_by",
    ];
    foreach ($extraColumns as $column => $sql) {
        $check = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_leger_siswa_eraport LIKE '" . mysqli_real_escape_string($conn, $column) . "'");
        if (!$check || mysqli_num_rows($check) === 0) {
            @mysqli_query($conn, $sql);
        }
    }

    $detailExtraColumns = [
        'updated_at' => "ALTER TABLE tbl_leger_nilai_raport_siswa ADD COLUMN updated_at DATETIME DEFAULT NULL AFTER source_file",
    ];
    foreach ($detailExtraColumns as $column => $sql) {
        $check = @mysqli_query($conn, "SHOW COLUMNS FROM tbl_leger_nilai_raport_siswa LIKE '" . mysqli_real_escape_string($conn, $column) . "'");
        if (!$check || mysqli_num_rows($check) === 0) {
            @mysqli_query($conn, $sql);
        }
    }
}

function eraport_store_leger_snapshot(mysqli $conn, string $kelas, array $parsed, string $semester = '', string $uploadedBy = '', string $sourceFile = ''): array
{
    eraport_ensure_leger_tables($conn);

    $details = is_array($parsed['details'] ?? null) ? $parsed['details'] : [];
    $students = is_array($parsed['students'] ?? null) ? $parsed['students'] : [];
    if (empty($details) || empty($students)) {
        return ['success' => false, 'message' => 'Data leger tidak ditemukan pada file.', 'summary' => ['inserted' => 0]];
    }

    $runId = date('YmdHis') . '_' . substr(md5(uniqid((string)mt_rand(), true)), 0, 8);
    $runEsc = mysqli_real_escape_string($conn, $runId);
    $kelasEsc = mysqli_real_escape_string($conn, $kelas);
    $semesterEsc = mysqli_real_escape_string($conn, $semester);
    $uploadedByEsc = mysqli_real_escape_string($conn, $uploadedBy);
    $sourceFileEsc = mysqli_real_escape_string($conn, $sourceFile);

    $detailByStudent = [];
    foreach ($details as $detail) {
        $key = trim((string)($detail['nis'] ?? ''));
        if ($key === '') {
            $key = trim((string)($detail['nisn'] ?? '')) ?: md5((string)($detail['nama_siswa'] ?? ''));
        }
        $detailByStudent[$key][] = $detail;
    }

    $insertedSummary = 0;
    foreach ($students as $student) {
        $nis = trim((string)($student['nis'] ?? ''));
        $nisn = trim((string)($student['nisn'] ?? ''));
        $nama = trim((string)($student['nama_siswa'] ?? ''));
        $key = $nis !== '' ? $nis : ($nisn !== '' ? $nisn : md5($nama));
        $studentDetails = $detailByStudent[$key] ?? [];
        if ($nama === '' && $nis === '') {
            continue;
        }

        $rerataValues = [];
        $allValues = [];
        foreach ($studentDetails as $detail) {
            $nilai = (float)($detail['nilai'] ?? 0);
            $allValues[] = $nilai;
            $komponen = strtolower((string)($detail['komponen'] ?? ''));
            if (strpos($komponen, 'rata') !== false || strpos($komponen, 'rerata') !== false) {
                $rerataValues[] = $nilai;
            }
        }
        $avgSource = !empty($rerataValues) ? $rerataValues : $allValues;
        $avgSql = !empty($avgSource) ? (string)round(array_sum($avgSource) / count($avgSource), 2) : 'NULL';
        $rawEsc = mysqli_real_escape_string($conn, json_encode($studentDetails, JSON_UNESCAPED_UNICODE));
        $nisEsc = mysqli_real_escape_string($conn, $nis);
        $namaEsc = mysqli_real_escape_string($conn, $nama);

        $sql = "INSERT INTO tbl_leger_siswa_eraport
            (run_id, synced_at, semester, kelas, nis, nama_siswa, nilai_rerata, raw_row, uploaded_by, source_file)
            VALUES
            ('{$runEsc}', NOW(), '{$semesterEsc}', '{$kelasEsc}', '{$nisEsc}', '{$namaEsc}', {$avgSql}, '{$rawEsc}', '{$uploadedByEsc}', '{$sourceFileEsc}')";
        if (@mysqli_query($conn, $sql)) {
            $insertedSummary++;
        }
    }

    $insertedDetails = 0;
    $updatedDetails = 0;
    foreach ($details as $detail) {
        $nisEsc = mysqli_real_escape_string($conn, trim((string)($detail['nis'] ?? '')));
        $nisnEsc = mysqli_real_escape_string($conn, trim((string)($detail['nisn'] ?? '')));
        $namaEsc = mysqli_real_escape_string($conn, trim((string)($detail['nama_siswa'] ?? '')));
        $mapelEsc = mysqli_real_escape_string($conn, trim((string)($detail['mapel'] ?? '')));
        $komponenEsc = mysqli_real_escape_string($conn, trim((string)($detail['komponen'] ?? '')));
        $nilai = round((float)($detail['nilai'] ?? 0), 2);
        if ($mapelEsc === '' || $komponenEsc === '') {
            continue;
        }
        $identityWhere = $nisEsc !== ''
            ? "nis='{$nisEsc}'"
            : ($nisnEsc !== '' ? "nisn='{$nisnEsc}'" : "nama_siswa='{$namaEsc}'");
        $qExisting = @mysqli_query(
            $conn,
            "SELECT id FROM tbl_leger_nilai_raport_siswa
             WHERE kelas='{$kelasEsc}'
               AND {$identityWhere}
               AND mapel='{$mapelEsc}'
               AND komponen='{$komponenEsc}'
             ORDER BY id DESC
             LIMIT 1"
        );
        if ($qExisting && ($existing = mysqli_fetch_assoc($qExisting))) {
            $id = (int)$existing['id'];
            $sql = "UPDATE tbl_leger_nilai_raport_siswa
                    SET run_id='{$runEsc}',
                        uploaded_at=NOW(),
                        nilai={$nilai},
                        nis='{$nisEsc}',
                        nisn='{$nisnEsc}',
                        nama_siswa='{$namaEsc}',
                        uploaded_by='{$uploadedByEsc}',
                        source_file='{$sourceFileEsc}',
                        updated_at=NOW()
                    WHERE id={$id}";
            if (@mysqli_query($conn, $sql)) {
                $updatedDetails++;
            }
        } else {
            $sql = "INSERT INTO tbl_leger_nilai_raport_siswa
                (run_id, uploaded_at, semester, kelas, nis, nisn, nama_siswa, mapel, komponen, nilai, uploaded_by, source_file, updated_at)
                VALUES
                ('{$runEsc}', NOW(), '{$semesterEsc}', '{$kelasEsc}', '{$nisEsc}', '{$nisnEsc}', '{$namaEsc}', '{$mapelEsc}', '{$komponenEsc}', {$nilai}, '{$uploadedByEsc}', '{$sourceFileEsc}', NOW())";
            if (@mysqli_query($conn, $sql)) {
                $insertedDetails++;
            }
        }
    }

    return [
        'success' => true,
        'message' => 'Leger raport berhasil diunggah.',
        'summary' => [
            'run_id' => $runId,
            'kelas' => $kelas,
            'students' => count($students),
            'inserted' => $insertedSummary,
            'details' => $insertedDetails,
            'updated_details' => $updatedDetails,
        ],
    ];
}

function eraport_parse_leger_class_map_from_html(string $html): array
{
    $classMap = [];
    if (preg_match_all('/<option\s+value=["\']([^"\']+)["\']\s*>\s*([^<]+)\s*<\/option>/i', $html, $opts, PREG_SET_ORDER)) {
        foreach ($opts as $o) {
            $idc = trim((string)$o[1]);
            $nmc = trim(html_entity_decode((string)$o[2], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            if ($idc === '' || $nmc === '' || stripos($nmc, 'pilih') !== false) {
                continue;
            }
            $classMap[$nmc] = $idc;
        }
    }
    return $classMap;
}

function eraport_login_and_get_leger_class_options(?array $override = null): array
{
    $cfg = eraport_get_config();
    $username = (string)($override['username'] ?? $cfg['admin_username'] ?? '');
    $password = (string)($override['password'] ?? $cfg['admin_password'] ?? '');
    $sekolahId = (string)($override['sekolah_id'] ?? $cfg['sekolah_id'] ?? '');
    $semester = (string)($override['semester'] ?? $cfg['semester'] ?? '');

    if ($username === '' || $password === '') {
        return ['success' => false, 'message' => 'Konfigurasi e-Raport belum lengkap (username/password).', 'classes' => []];
    }
    if (!function_exists('curl_init')) {
        return ['success' => false, 'message' => 'cURL PHP wajib aktif untuk sinkron leger.', 'classes' => []];
    }

    $baseUrl = (string)$cfg['base_url'];
    $cookieFile = tempnam(sys_get_temp_dir(), 'eraport_cookie_');
    if ($cookieFile === false) {
        return ['success' => false, 'message' => 'Gagal menyiapkan cookie sementara.', 'classes' => []];
    }

    $request = static function (string $url, string $method = 'GET', array $post = []) use ($cookieFile): array {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
        curl_setopt($ch, CURLOPT_USERAGENT, 'SIMANIS-eRaport-Connector/1.0');
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
        }
        $body = curl_exec($ch);
        $err = curl_error($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return ['status_code' => $code, 'error' => $err, 'body' => $body === false ? '' : (string)$body];
    };

    try {
        $loginPage = $request(eraport_build_url($baseUrl, 'login'));
        if ($loginPage['error'] !== '' || $loginPage['status_code'] < 200 || $loginPage['status_code'] >= 400) {
            return ['success' => false, 'message' => 'Gagal membuka login e-Raport.', 'classes' => []];
        }

        $csrf = eraport_parse_csrf_token((string)$loginPage['body']);
        $defaults = eraport_parse_login_defaults((string)$loginPage['body']);
        if ($sekolahId === '') {
            $sekolahId = (string)$defaults['sekolah_id'];
        }
        if ($semester === '') {
            $semester = (string)$defaults['semester'];
        }
        $namaSekolah = (string)($defaults['nm_sek'] ?: 'SMA NEGERI 1 SUMBER');

        $payload = [
            'username' => $username,
            'pass' => $password,
            'password' => eraport_sha512_hex($password),
            'semester' => $semester,
            'sekolahid' => $sekolahId,
            'nm_sek' => $namaSekolah,
            'csrf_test_name' => $csrf,
        ];
        $loginSubmit = $request(eraport_build_url($baseUrl, 'login/cekuser'), 'POST', $payload);
        $loginJson = json_decode((string)$loginSubmit['body'], true);
        if ($loginSubmit['error'] !== '' || !$loginJson || (($loginJson['type'] ?? '') !== 'success')) {
            return ['success' => false, 'message' => 'Login e-Raport gagal.', 'classes' => []];
        }

        $page = $request(eraport_build_url($baseUrl, 'leger_kelas'));
        if ($page['error'] !== '' || $page['status_code'] < 200 || $page['status_code'] >= 400) {
            return ['success' => false, 'message' => 'Gagal membuka halaman leger_kelas.', 'classes' => []];
        }

        $map = eraport_parse_leger_class_map_from_html((string)$page['body']);
        $classes = [];
        foreach ($map as $name => $id) {
            $classes[] = ['name' => (string)$name, 'id' => (string)$id];
        }

        return ['success' => true, 'message' => 'Daftar kelas e-Raport berhasil dimuat.', 'classes' => $classes];
    } finally {
        @unlink($cookieFile);
    }
}

function eraport_login_and_fetch_leger_kelas(string $kelas, ?array $override = null): array
{
    $cfg = eraport_get_config();
    $username = (string)($override['username'] ?? $cfg['admin_username'] ?? '');
    $password = (string)($override['password'] ?? $cfg['admin_password'] ?? '');
    $sekolahId = (string)($override['sekolah_id'] ?? $cfg['sekolah_id'] ?? '');
    $semester = (string)($override['semester'] ?? $cfg['semester'] ?? '');
    $kelasEraport = trim((string)($override['kelas_eraport'] ?? ''));

    if ($username === '' || $password === '') {
        return [
            'success' => false,
            'message' => 'Konfigurasi e-Raport belum lengkap (username/password).',
            'items' => [],
        ];
    }

    if (!function_exists('curl_init')) {
        return [
            'success' => false,
            'message' => 'cURL PHP wajib aktif untuk sinkron leger.',
            'items' => [],
        ];
    }

    $baseUrl = (string)$cfg['base_url'];
    $cookieFile = tempnam(sys_get_temp_dir(), 'eraport_cookie_');
    if ($cookieFile === false) {
        return [
            'success' => false,
            'message' => 'Gagal menyiapkan cookie sementara.',
            'items' => [],
        ];
    }

    $request = static function (string $url, string $method = 'GET', array $post = []) use ($cookieFile): array {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
        curl_setopt($ch, CURLOPT_USERAGENT, 'SIMANIS-eRaport-Connector/1.0');
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
        }
        $body = curl_exec($ch);
        $err = curl_error($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return [
            'status_code' => $code,
            'error' => $err,
            'body' => $body === false ? '' : (string)$body,
        ];
    };

    try {
        $loginPage = $request(eraport_build_url($baseUrl, 'login'));
        if ($loginPage['error'] !== '' || $loginPage['status_code'] < 200 || $loginPage['status_code'] >= 400) {
            return ['success' => false, 'message' => 'Gagal membuka login e-Raport.', 'items' => []];
        }

        $csrf = eraport_parse_csrf_token((string)$loginPage['body']);
        $defaults = eraport_parse_login_defaults((string)$loginPage['body']);
        if ($sekolahId === '') {
            $sekolahId = (string)$defaults['sekolah_id'];
        }
        if ($semester === '') {
            $semester = (string)$defaults['semester'];
        }
        $namaSekolah = (string)($defaults['nm_sek'] ?: 'SMA NEGERI 1 SUMBER');

        $payload = [
            'username' => $username,
            'pass' => $password,
            'password' => eraport_sha512_hex($password),
            'semester' => $semester,
            'sekolahid' => $sekolahId,
            'nm_sek' => $namaSekolah,
            'csrf_test_name' => $csrf,
        ];
        $loginSubmit = $request(eraport_build_url($baseUrl, 'login/cekuser'), 'POST', $payload);
        $loginJson = json_decode((string)$loginSubmit['body'], true);
        if ($loginSubmit['error'] !== '' || !$loginJson || (($loginJson['type'] ?? '') !== 'success')) {
            return ['success' => false, 'message' => 'Login e-Raport gagal.', 'items' => []];
        }

        $page = $request(eraport_build_url($baseUrl, 'leger_kelas'));
        if ($page['error'] !== '' || $page['status_code'] < 200 || $page['status_code'] >= 400) {
            return ['success' => false, 'message' => 'Gagal membuka halaman leger_kelas.', 'items' => []];
        }

        $classMap = eraport_parse_leger_class_map_from_html((string)$page['body']);

        $pickedId = '';
        $pickedName = '';
        if ($kelasEraport !== '') {
            foreach ($classMap as $nmc => $idc) {
                if (strcasecmp(trim($nmc), $kelasEraport) === 0) {
                    $pickedId = $idc;
                    $pickedName = $nmc;
                    break;
                }
            }
        }

        foreach ($classMap as $nmc => $idc) {
            if ($pickedId !== '') {
                break;
            }
            if (strcasecmp(trim($nmc), trim($kelas)) === 0) {
                $pickedId = $idc;
                $pickedName = $nmc;
                break;
            }
        }
        if ($pickedId === '') {
            foreach ($classMap as $nmc => $idc) {
                if (stripos($nmc, trim($kelas)) !== false || stripos(trim($kelas), $nmc) !== false) {
                    $pickedId = $idc;
                    $pickedName = $nmc;
                    break;
                }
            }
        }

        if ($pickedId === '') {
            return [
                'success' => false,
                'message' => 'Kelas tidak ditemukan di menu leger e-Raport: ' . $kelas,
                'items' => [],
            ];
        }

        $dl = $request(eraport_build_url($baseUrl, 'download_leger'), 'POST', ['idkelas' => $pickedId]);
        $path = trim((string)$dl['body']);
        $path = trim($path, " \"'");
        if ($dl['error'] !== '' || $path === '') {
            return [
                'success' => false,
                'message' => 'Gagal request file leger.',
                'items' => [],
            ];
        }

        $path = str_replace(' ', '%20', $path);
        $file = $request(eraport_build_url($baseUrl, $path));
        if ($file['error'] !== '' || $file['status_code'] < 200 || $file['status_code'] >= 400 || (string)$file['body'] === '') {
            return [
                'success' => false,
                'message' => 'Gagal mengambil file leger hasil download.',
                'items' => [],
            ];
        }

        $xlsxRows = eraport_parse_xlsx_rows_from_binary((string)$file['body']);
        $items = eraport_extract_leger_rows($xlsxRows);

        return [
            'success' => true,
            'message' => 'Leger siswa berhasil diambil dari e-Raport.',
            'items' => $items,
            'kelas' => $kelas,
            'kelas_eraport' => $pickedName,
            'kelas_id' => $pickedId,
            'raw_count' => count($items),
        ];
    } finally {
        @unlink($cookieFile);
    }
}

function eraport_sync_leger_kelas_to_local(mysqli $conn, string $kelas, ?array $override = null): array
{
    @mysqli_query($conn, "CREATE TABLE IF NOT EXISTS tbl_leger_siswa_eraport (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        run_id VARCHAR(40) NOT NULL,
        synced_at DATETIME NOT NULL,
        semester VARCHAR(30) DEFAULT NULL,
        kelas VARCHAR(80) NOT NULL,
        nis VARCHAR(40) DEFAULT NULL,
        nama_siswa VARCHAR(200) DEFAULT NULL,
        nilai_rerata DECIMAL(6,2) DEFAULT NULL,
        raw_row LONGTEXT,
        PRIMARY KEY (id),
        KEY idx_run (run_id),
        KEY idx_kelas (kelas),
        KEY idx_nis (nis),
        KEY idx_synced_at (synced_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $fetch = eraport_login_and_fetch_leger_kelas($kelas, $override);
    if (empty($fetch['success'])) {
        return [
            'success' => false,
            'message' => (string)($fetch['message'] ?? 'Gagal ambil leger kelas.'),
            'summary' => ['inserted' => 0],
        ];
    }

    $items = is_array($fetch['items'] ?? null) ? $fetch['items'] : [];
    $runId = date('YmdHis') . '_' . substr(md5(uniqid((string)mt_rand(), true)), 0, 8);
    $kelasEsc = mysqli_real_escape_string($conn, $kelas);
    $semester = (string)(eraport_get_config()['semester'] ?? '');
    $semesterEsc = mysqli_real_escape_string($conn, $semester);

    // Keep recent snapshots only (last 30 runs per class).
    $oldRunRes = mysqli_query($conn, "SELECT run_id FROM tbl_leger_siswa_eraport WHERE kelas='{$kelasEsc}' GROUP BY run_id ORDER BY MAX(synced_at) DESC LIMIT 30, 200");
    if ($oldRunRes) {
        $oldRuns = [];
        while ($or = mysqli_fetch_assoc($oldRunRes)) {
            $oldRuns[] = "'" . mysqli_real_escape_string($conn, (string)$or['run_id']) . "'";
        }
        if (!empty($oldRuns)) {
            @mysqli_query($conn, "DELETE FROM tbl_leger_siswa_eraport WHERE kelas='{$kelasEsc}' AND run_id IN (" . implode(',', $oldRuns) . ")");
        }
    }

    $inserted = 0;
    foreach ($items as $it) {
        $nis = mysqli_real_escape_string($conn, trim((string)($it['nis'] ?? '')));
        $nama = mysqli_real_escape_string($conn, trim((string)($it['nama_siswa'] ?? '')));
        $raw = mysqli_real_escape_string($conn, json_encode($it['raw'] ?? [], JSON_UNESCAPED_UNICODE));
        $avg = $it['nilai_rerata'];
        $avgSql = $avg === null ? 'NULL' : (string)round((float)$avg, 2);

        if ($nis === '' && $nama === '') {
            continue;
        }

        $sql = "INSERT INTO tbl_leger_siswa_eraport (
            run_id, synced_at, semester, kelas, nis, nama_siswa, nilai_rerata, raw_row
        ) VALUES (
            '" . mysqli_real_escape_string($conn, $runId) . "', NOW(), '{$semesterEsc}', '{$kelasEsc}', '{$nis}', '{$nama}', {$avgSql}, '{$raw}'
        )";
        if (mysqli_query($conn, $sql)) {
            $inserted++;
        }
    }

    return [
        'success' => true,
        'message' => 'Sinkron leger kelas berhasil.',
        'summary' => [
            'run_id' => $runId,
            'fetched' => count($items),
            'inserted' => $inserted,
            'kelas' => $kelas,
            'kelas_eraport' => (string)($fetch['kelas_eraport'] ?? ''),
        ],
    ];
}
