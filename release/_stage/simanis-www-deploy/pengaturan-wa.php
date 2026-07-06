<?php
session_start();
require_once 'koneksi.php';
require_once 'auth_helper.php';
require_once 'notification_helper.php';

if (!isset($_SESSION['hak_akses']) || (int)$_SESSION['hak_akses'] !== 1) {
    header('Location: login.php');
    exit;
}

$flash = ['type' => '', 'msg' => ''];

if (!empty($_GET['msg'])) {
    $flash = ['type' => 'info', 'msg' => htmlspecialchars(urldecode($_GET['msg']))];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] === 'save_config' && $conn instanceof mysqli) {
        $wa_url     = trim($_POST['wa_url'] ?? '');
        $wa_api_key = trim($_POST['wa_api_key'] ?? '');
        $wa_enabled = isset($_POST['wa_enabled']) ? '1' : '0';
        
        $wa_delay       = (int)($_POST['wa_delay'] ?? 5);
        $wa_batch_size  = (int)($_POST['wa_batch_size'] ?? 10);
        $wa_batch_delay = (int)($_POST['wa_batch_delay'] ?? 60);

        notif_save_wa_config($conn, 'wa_url', $wa_url);
        notif_save_wa_config($conn, 'wa_api_key', $wa_api_key);
        notif_save_wa_config($conn, 'wa_enabled', $wa_enabled);
        notif_save_wa_config($conn, 'wa_delay', (string)$wa_delay);
        notif_save_wa_config($conn, 'wa_batch_size', (string)$wa_batch_size);
        notif_save_wa_config($conn, 'wa_batch_delay', (string)$wa_batch_delay);
        $flash = ['type' => 'success', 'msg' => '✅ Konfigurasi berhasil disimpan!'];
    }

    if ($_POST['action'] === 'save_custom_notif' && $conn instanceof mysqli) {
        $items = ['presensi', 'jurnal', 'izin', 'laporan', 'rekap'];
        foreach ($items as $item) {
            $statusKey   = 'wa_notif_' . $item . '_status';
            $titleKey    = 'wa_notif_' . $item . '_title';
            $templateKey = 'wa_notif_' . $item . '_template';
            
            $statusVal   = isset($_POST[$statusKey]) ? '1' : '0';
            $titleVal    = trim($_POST[$titleKey] ?? '');
            $templateVal = trim($_POST[$templateKey] ?? '');
            
            notif_save_wa_config($conn, $statusKey, $statusVal);
            notif_save_wa_config($conn, $titleKey, $titleVal);
            notif_save_wa_config($conn, $templateKey, $templateVal);
        }
        $flash = ['type' => 'success', 'msg' => '✅ Pengaturan notifikasi kustom berhasil disimpan!'];
    }

    if ($_POST['action'] === 'test_send' && $conn instanceof mysqli) {
        $test_phone   = trim($_POST['test_phone'] ?? '');
        $test_message = trim($_POST['test_message'] ?? '');
        if ($test_phone === '') {
            $flash = ['type' => 'danger', 'msg' => '⚠️ Masukkan nomor HP tujuan terlebih dahulu.'];
        } elseif ($test_message === '') {
            $flash = ['type' => 'danger', 'msg' => '⚠️ Masukkan isi pesan test terlebih dahulu.'];
        } else {
            [$ok, $err] = notif_send_whatsapp(
                $test_phone,
                '✅ Test Notifikasi SIMANIS',
                $test_message,
                $conn
            );
            $flash = $ok
                ? ['type' => 'success', 'msg' => "✅ Pesan test berhasil dikirim ke <strong>" . htmlspecialchars($test_phone) . "</strong>!"]
                : ['type' => 'danger',  'msg' => "❌ Gagal kirim: <code>" . htmlspecialchars($err) . "</code>"];
        }
    }

    if ($_POST['action'] === 'retry_failed' && $conn instanceof mysqli) {
        @mysqli_query($conn, "UPDATE tbl_notifikasi_outbox SET status='pending', percobaan=0, error_message=NULL WHERE status='failed'");
        $affected = mysqli_affected_rows($conn);
        $flash = ['type' => 'info', 'msg' => "🔄 $affected pesan gagal di-reset ke pending."];
    }
}

$config = ($conn instanceof mysqli) ? notif_get_wasender_config($conn) : [
    'url' => 'http://127.0.0.1:3002', 'api_key' => '', 'enabled' => true,
];

$recentNotif = [];
$stats = ['sent' => 0, 'pending' => 0, 'failed' => 0];
if ($conn instanceof mysqli) {
    notif_ensure_schema($conn);
    $qn = @mysqli_query($conn, "SELECT * FROM tbl_notifikasi_outbox ORDER BY id DESC LIMIT 25");
    while ($qn && $row = mysqli_fetch_assoc($qn)) { $recentNotif[] = $row; }
    $qs = @mysqli_query($conn, "SELECT status, COUNT(*) c FROM tbl_notifikasi_outbox GROUP BY status");
    while ($qs && $r = mysqli_fetch_assoc($qs)) { $stats[$r['status']] = (int)$r['c']; }
}

$wasenderStatus = 'unknown';
$wasenderMsg    = '';
if ($conn instanceof mysqli) {
    $cfg = notif_get_wasender_config($conn);
    $url = rtrim($cfg['url'], '/');
    $key = $cfg['api_key'];
    if ($url) {
        $isGeolabs = (strpos($url, 'wa.geolabs.my.id') !== false) || (strpos($url, 'geolabs') !== false);
        if ($isGeolabs) {
            // For Geolabs, check if the base server is reachable
            $pingUrl = strpos($url, '/api/send') !== false ? $url : $url . '/api/send';
            $ch = curl_init($pingUrl);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 5,
                CURLOPT_CONNECTTIMEOUT => 3,
            ]);
            $resp = curl_exec($ch);
            $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            // Geolabs returning any code (like 405 Method Not Allowed) means the server is reachable and online
            if ($resp !== false && $code > 0) {
                $wasenderStatus = 'online';
            } else {
                $wasenderStatus = 'offline';
            }
        } else {
            $ch = curl_init($url . '/api/status');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 5,
                CURLOPT_CONNECTTIMEOUT => 3,
                CURLOPT_HTTPHEADER     => $key ? ["x-api-key: $key"] : [],
            ]);
            $resp = curl_exec($ch);
            $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $cerr = curl_error($ch);
            curl_close($ch);

            if ($resp !== false && $code > 0) {
                $dec = json_decode($resp, true);
                $isAuth = (bool)($dec['isAuthenticated'] ?? false);
                $wasenderStatus = $isAuth ? 'online' : 'not_auth';
            } else {
                $wasenderStatus = 'offline';
            }
        }
    }
}

// Dibutuhkan oleh header.php
$lembaga = function_exists('data_lembaga') ? data_lembaga() : [];
$hakakses = $_SESSION['hak_akses'] ?? 0;

include 'header.php';
?>

<!-- ═══ SIDEBAR ═══ -->
<?php include 'sidebar.php'; ?>

<!-- ═══ CONTENT WRAPPER — pola SB Admin 2 ═══ -->
<div id="content-wrapper" class="d-flex flex-column">
  <div id="content">

    <!-- Topbar -->
    <?php include 'topbar.php'; ?>

    <!-- Main Content -->
    <div class="container-fluid px-3 px-md-4 pb-4">

      <style>
        /* ── Tokens ── */
        :root {
          --wa:       #25D366;
          --wa-dark:  #075E54;
          --wa-mid:   #128C7E;
          --wa-light: #DCF8C6;
          --rad-lg: 16px;
          --rad-md: 10px;
          --rad-sm: 7px;
          --shadow: 0 2px 16px rgba(0,0,0,.07);
          --border: 1px solid #EBEBEB;
        }
        /* ── Hero ── */
        .wa-hero {
          background: linear-gradient(135deg, var(--wa-dark) 0%, var(--wa-mid) 55%, var(--wa) 100%);
          border-radius: var(--rad-lg);
          color: #fff;
          padding: 1.5rem 1.75rem;
          margin-bottom: 1.25rem;
          position: relative;
          overflow: hidden;
        }
        .wa-hero::before {
          content: '';
          position: absolute;
          width: 200px; height: 200px;
          border-radius: 50%;
          background: rgba(255,255,255,.07);
          right: -50px; top: -50px;
        }
        .wa-hero-inner { position: relative; z-index: 1; }
        .wa-hero h4    { font-size: 1.3rem; font-weight: 800; margin: .25rem 0; }
        .wa-hero p     { opacity: .8; font-size: .85rem; margin: 0 0 .9rem; }
        /* ── Status pill ── */
        .wa-pill {
          display: inline-flex; align-items: center; gap: 7px;
          padding: 5px 13px; border-radius: 50px;
          font-size: .78rem; font-weight: 700;
          backdrop-filter: blur(6px);
        }
        .pill-on   { background: rgba(212,237,218,.92); color: #155724; }
        .pill-warn { background: rgba(255,243,205,.92); color: #856404; }
        .pill-off  { background: rgba(248,215,218,.92); color: #721c24; }
        .pill-unk  { background: rgba(220,220,220,.6);  color: #444;    }
        .dot-pulse {
          width: 8px; height: 8px; border-radius: 50%; display: inline-block;
          animation: dotPulse 1.4s ease-in-out infinite;
        }
        @keyframes dotPulse { 0%,100%{opacity:1} 50%{opacity:.3} }
        .dot-g { background: #28a745; }
        .dot-y { background: #ffc107; }
        .dot-r { background: #dc3545; }
        .dot-x { background: #aaa;    }
        /* ── Flash ── */
        .wa-flash {
          border-radius: var(--rad-md);
          padding: .8rem 1rem;
          font-size: .875rem; font-weight: 600;
          margin-bottom: 1.1rem;
          display: flex; align-items: flex-start; gap: 10px;
          border-left-width: 4px; border-left-style: solid;
        }
        .f-success { background: #D4EDDA; color: #155724; border-color: #28a745; }
        .f-danger  { background: #F8D7DA; color: #721c24; border-color: #dc3545; }
        .f-info    { background: #D1ECF1; color: #0C5460; border-color: #17a2b8; }
        .f-close   { margin-left: auto; cursor: pointer; background: none; border: none; color: inherit; font-size: 1.1rem; line-height: 1; padding: 0; }
        /* ── Stat chips ── */
        .stat-row { display: flex; gap: .6rem; margin-bottom: 1.1rem; flex-wrap: wrap; }
        .stat-chip {
          flex: 1; min-width: 75px; padding: .7rem .8rem;
          border-radius: var(--rad-md); text-align: center; font-weight: 700;
        }
        .stat-chip .n { font-size: 1.55rem; line-height: 1; display: block; }
        .stat-chip .l { font-size: .7rem; opacity: .75; display: block; margin-top: 2px; }
        .sc-sent    { background: #D4EDDA; color: #155724; }
        .sc-pending { background: #FFF3CD; color: #856404; }
        .sc-failed  { background: #F8D7DA; color: #721c24; }
        /* ── Pending alert ── */
        .pa-box {
          background: linear-gradient(135deg,#FFF8E1,#FFF3CD);
          border: 1.5px solid #FFCA28; border-radius: var(--rad-md);
          padding: .9rem 1.1rem;
          display: flex; align-items: center; gap: 12px; flex-wrap: wrap;
          margin-bottom: 1.1rem;
        }
        .pa-box .pa-icon { font-size: 1.7rem; }
        .pa-box .pa-body { flex: 1; }
        .pa-box .pa-body strong { display: block; font-size: .9rem; }
        .pa-box .pa-body span   { font-size: .78rem; color: #666; }
        /* ── Cards ── */
        .wa-card {
          background: #fff; border-radius: var(--rad-lg);
          box-shadow: var(--shadow); border: var(--border);
          margin-bottom: 1.1rem; overflow: hidden;
        }
        .wa-card-hdr {
          padding: .85rem 1.2rem;
          border-bottom: var(--border);
          font-weight: 700; font-size: .88rem; color: #2d2d2d;
          display: flex; align-items: center; gap: 8px;
          background: #FAFAFA;
        }
        .wa-card-hdr .hdr-right { margin-left: auto; }
        .wa-card-body { padding: 1.2rem; }
        /* ── Form ── */
        .wa-label { font-weight: 600; font-size: .83rem; color: #444; margin-bottom: 5px; display: block; }
        .wa-hint  { font-size: .74rem; color: #888; margin-top: 4px; }
        .wa-field { margin-bottom: 1rem; }
        .wa-input {
          width: 100%; border: 2px solid #E5E5E5; border-radius: var(--rad-sm);
          padding: .58rem .85rem; font-size: .87rem; outline: none;
          transition: border-color .2s, box-shadow .2s; font-family: inherit;
        }
        .wa-input:focus { border-color: var(--wa); box-shadow: 0 0 0 3px rgba(37,211,102,.15); }
        .inp-grp { display: flex; }
        .inp-grp .wa-input { border-radius: var(--rad-sm) 0 0 var(--rad-sm); flex: 1; }
        .inp-grp .ig-btn {
          border: 2px solid #E5E5E5; border-left: none;
          border-radius: 0 var(--rad-sm) var(--rad-sm) 0;
          background: #F8F8F8; padding: 0 .85rem; cursor: pointer;
          transition: background .15s; font-size: .95rem;
        }
        .inp-grp .ig-btn:hover { background: #EDEDED; }
        /* ── Toggle ── */
        .tg-wrap { display: flex; align-items: center; gap: 11px; }
        .tg-cb   { display: none; }
        .tg-track {
          width: 44px; height: 24px; background: #CCC; border-radius: 50px;
          position: relative; cursor: pointer; transition: background .25s; flex-shrink: 0;
        }
        .tg-cb:checked + .tg-track { background: var(--wa); }
        .tg-thumb {
          position: absolute; top: 2px; left: 2px;
          width: 20px; height: 20px; background: #fff; border-radius: 50%;
          box-shadow: 0 1px 4px rgba(0,0,0,.2); transition: transform .25s;
          pointer-events: none;
        }
        .tg-cb:checked + .tg-track .tg-thumb { transform: translateX(20px); }
        /* ── Buttons ── */
        .btn-wa {
          background: linear-gradient(135deg, var(--wa), var(--wa-mid));
          color: #fff !important; border: none; border-radius: var(--rad-sm);
          font-weight: 700; font-size: .87rem; padding: .6rem 1.3rem;
          cursor: pointer; display: inline-flex; align-items: center; gap: 6px;
          transition: all .2s; text-decoration: none;
        }
        .btn-wa:hover { transform: translateY(-1px); box-shadow: 0 5px 15px rgba(37,211,102,.3); }
        .btn-wa-sm { padding: .4rem .9rem; font-size: .78rem; }
        .btn-wa-out {
          background: transparent; border: 2px solid var(--wa);
          color: var(--wa-dark) !important; border-radius: var(--rad-sm);
          font-weight: 700; font-size: .87rem; padding: .55rem 1.1rem;
          cursor: pointer; display: inline-flex; align-items: center; gap: 6px;
          transition: background .2s; text-decoration: none;
        }
        .btn-wa-out:hover { background: var(--wa-light); }
        .btn-wa-out-sm { padding: .35rem .8rem; font-size: .78rem; }
        .w-full { width: 100%; justify-content: center; }
        /* ── Log table ── */
        .log-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; }
        .log-tbl { width: 100%; border-collapse: collapse; font-size: .8rem; }
        .log-tbl th {
          background: #F5F5F5; padding: .55rem .75rem;
          text-align: left; font-weight: 700; color: #555;
          font-size: .72rem; text-transform: uppercase; letter-spacing: .04em;
          white-space: nowrap; border-bottom: 2px solid #E0E0E0;
        }
        .log-tbl td { padding: .6rem .75rem; border-bottom: 1px solid #F0F0F0; vertical-align: top; }
        .log-tbl tr:last-child td { border-bottom: none; }
        .log-tbl tr:hover td { background: #FAFFF8; }
        .bl { display: inline-flex; align-items: center; gap: 3px; padding: 2px 9px; border-radius: 50px; font-size: .7rem; font-weight: 700; white-space: nowrap; }
        .bl-s { background: #D4EDDA; color: #155724; }
        .bl-p { background: #FFF3CD; color: #856404; }
        .bl-f { background: #F8D7DA; color: #721c24; }
        .err-txt { font-size: .7rem; color: #dc3545; margin-top: 2px; word-break: break-word; max-width: 170px; }
        .phone-m { font-family: monospace; font-size: .78rem; font-weight: 700; }
        .ts-s { white-space: nowrap; color: #999; font-size: .73rem; }
        /* ── Guide ── */
        .guide-ul { list-style: none; padding: 0; margin: 0; }
        .guide-ul li {
          display: flex; gap: 11px; align-items: flex-start;
          padding: .65rem 0; border-bottom: 1px solid #F0F0F0; font-size: .83rem;
        }
        .guide-ul li:last-child { border-bottom: none; }
        .gn {
          width: 24px; height: 24px; background: var(--wa); color: #fff;
          border-radius: 50%; display: flex; align-items: center; justify-content: center;
          font-size: .72rem; font-weight: 800; flex-shrink: 0; margin-top: 1px;
        }
        /* ── Trigger tags ── */
        .tr-row { display: flex; gap: .4rem; flex-wrap: wrap; margin-top: .6rem; }
        .tr-tag {
          border: 1.5px solid var(--wa); border-radius: 50px;
          padding: 3px 10px; font-size: .75rem; color: var(--wa-dark);
          font-weight: 600; background: var(--wa-light);
        }
        /* ── Empty ── */
        .empty-box { text-align: center; padding: 2rem 1rem; color: #BBB; }
        .empty-box .ei { font-size: 2.5rem; margin-bottom: .5rem; }
        /* ── Responsive ── */
        @media (min-width: 992px) {
          .wa-2col { display: grid; grid-template-columns: 1fr 1fr; gap: 1.1rem; align-items: start; }
        }
        @media (max-width: 575px) {
          .wa-hero { padding: 1.1rem 1.2rem; }
          .wa-hero h4 { font-size: 1.1rem; }
        }
      </style>

      <!-- PAGE HEADING -->
      <div class="d-sm-flex align-items-center justify-content-between mb-3">
        <h1 class="h4 mb-0 text-gray-800">
          <i class="fas fa-comment-dots mr-2" style="color:#25D366"></i>
          Pengaturan Notifikasi WhatsApp
        </h1>
        <a href="home.php" class="d-none d-sm-inline-block btn btn-sm btn-outline-secondary shadow-sm">
          <i class="fas fa-arrow-left fa-sm mr-1"></i> Kembali
        </a>
      </div>

      <!-- HERO -->
      <div class="wa-hero">
        <div class="wa-hero-inner">
          <div style="font-size:2.2rem;line-height:1;margin-bottom:.4rem">📱</div>
          <h4>Notifikasi WhatsApp</h4>
          <p>Hubungkan SIMANIS ke WASENDER untuk kirim pesan otomatis ke orang tua siswa</p>
          <?php
            $pc = match($wasenderStatus){ 'online'=>'pill-on','not_auth'=>'pill-warn','offline'=>'pill-off',default=>'pill-unk' };
            $dc = match($wasenderStatus){ 'online'=>'dot-g','not_auth'=>'dot-y','offline'=>'dot-r',default=>'dot-x' };
            $pt = match($wasenderStatus){
              'online'   => '✅ WA Aktif &amp; Terhubung',
              'not_auth' => '⚠️ WASENDER aktif — Perlu Scan QR',
              'offline'  => '❌ WASENDER Offline',
              default    => '🔧 Belum Dikonfigurasi'
            };
          ?>
          <span class="wa-pill <?= $pc ?>">
            <span class="dot-pulse <?= $dc ?>"></span>
            <?= $pt ?>
          </span>
          <?php if ($wasenderStatus === 'not_auth'): ?>
            <a href="http://127.0.0.1:3002" target="_blank" class="wa-pill pill-warn ml-2" style="text-decoration:none">
              🔗 Buka Dashboard WASENDER
            </a>
          <?php endif; ?>
        </div>
      </div>

      <!-- FLASH -->
      <?php if ($flash['msg']): ?>
        <div class="wa-flash f-<?= $flash['type'] ?>" id="waFlash">
          <div><?= $flash['msg'] ?></div>
          <button class="f-close" onclick="this.closest('.wa-flash').remove()">✕</button>
        </div>
      <?php endif; ?>

      <!-- STAT CHIPS -->
      <div class="stat-row">
        <div class="stat-chip sc-sent">
          <span class="n"><?= $stats['sent'] ?></span>
          <span class="l">✅ Terkirim</span>
        </div>
        <div class="stat-chip sc-pending">
          <span class="n"><?= $stats['pending'] ?></span>
          <span class="l">⏳ Pending</span>
        </div>
        <div class="stat-chip sc-failed">
          <span class="n"><?= $stats['failed'] ?></span>
          <span class="l">❌ Gagal</span>
        </div>
      </div>

      <!-- PENDING / FAILED ALERT -->
      <?php if ($stats['pending'] > 0 || $stats['failed'] > 0): ?>
      <div class="pa-box">
        <div class="pa-icon"><?= $stats['failed'] > 0 ? '🚨' : '⏳' ?></div>
        <div class="pa-body">
          <strong>
            <?php if ($stats['failed'] > 0): ?>
              <?= $stats['failed'] ?> pesan gagal — butuh perhatian
            <?php else: ?>
              <?= $stats['pending'] ?> pesan menunggu dikirim
            <?php endif; ?>
          </strong>
          <span>Pastikan WASENDER aktif lalu proses antrean</span>
        </div>
        <div style="display:flex;gap:.5rem;flex-wrap:wrap;align-items:center">
          <?php if ($stats['failed'] > 0): ?>
          <form method="POST" style="margin:0">
            <input type="hidden" name="action" value="retry_failed">
            <button type="submit" class="btn-wa-out btn-wa-out-sm">🔁 Reset Gagal</button>
          </form>
          <?php endif; ?>
          <a href="api/proses-wa-queue.php" class="btn-wa btn-wa-sm"
             onclick="return confirm('Proses semua pesan pending sekarang?')">▶ Proses</a>
        </div>
      </div>
      <?php endif; ?>

      <!-- TWO-COLUMN GRID -->
      <div class="wa-2col">

        <!-- ▌ KOLOM KIRI ▌ -->
        <div>

          <!-- Konfigurasi -->
          <div class="wa-card">
            <div class="wa-card-hdr">⚙️ Konfigurasi WASENDER</div>
            <div class="wa-card-body">
              <form method="POST">
                <input type="hidden" name="action" value="save_config">

                <div class="wa-field">
                  <label class="wa-label" for="wa_url">URL Server WASENDER</label>
                  <input class="wa-input" type="url" id="wa_url" name="wa_url"
                         value="<?= htmlspecialchars($config['url']) ?>"
                         placeholder="http://127.0.0.1:3002" required>
                  <div class="wa-hint">Default port 3002 sesuai file <code>.env</code> WASENDER</div>
                </div>

                <div class="wa-field">
                  <label class="wa-label" for="apiKeyInp">API Key</label>
                  <div class="inp-grp">
                    <input class="wa-input" type="password" id="apiKeyInp" name="wa_api_key"
                           value="<?= htmlspecialchars($config['api_key']) ?>"
                           placeholder="WA_API_KEY dari .env WASENDER">
                    <button class="ig-btn" type="button" id="apiToggleBtn" onclick="toggleApiKey()">👁</button>
                  </div>
                  <div class="wa-hint">Nilai <code>WA_API_KEY</code> di <code>C:\Users\sman1\WASENDER\.env</code></div>
                </div>

                <div class="wa-field">
                  <div class="tg-wrap">
                    <input type="checkbox" class="tg-cb" id="waEnabled" name="wa_enabled"
                           <?= $config['enabled'] ? 'checked' : '' ?>>
                    <label class="tg-track" for="waEnabled">
                      <div class="tg-thumb"></div>
                    </label>
                    <label class="wa-label" for="waEnabled" style="margin:0;cursor:pointer">
                      Aktifkan Notifikasi WhatsApp
                    </label>
                  </div>
                </div>

                <hr style="border-color: #EBEBEB; margin: 1.5rem 0;">
                <h6 style="font-weight: 700; font-size: .88rem; color: #444; margin-bottom: 1rem;">Pengaturan Jeda (Anti Blokir)</h6>

                <div class="wa-field">
                  <label class="wa-label" for="waDelay">Jeda Normal Antar Pesan (Detik)</label>
                  <input class="wa-input" type="number" id="waDelay" name="wa_delay"
                         value="<?= htmlspecialchars((string)notif_get_custom_setting($conn, 'wa_delay', '5')) ?>" min="0" required>
                  <div class="wa-hint">Jeda waktu pengiriman untuk setiap pesan satuan. Default: 5</div>
                </div>

                <div class="wa-field">
                  <label class="wa-label" for="waBatchSize">Batas Pesan per Batch</label>
                  <input class="wa-input" type="number" id="waBatchSize" name="wa_batch_size"
                         value="<?= htmlspecialchars((string)notif_get_custom_setting($conn, 'wa_batch_size', '10')) ?>" min="1" required>
                  <div class="wa-hint">Berapa pesan yang dikirim sebelum istirahat panjang. Default: 10</div>
                </div>

                <div class="wa-field">
                  <label class="wa-label" for="waBatchDelay">Jeda Istirahat Panjang (Detik)</label>
                  <input class="wa-input" type="number" id="waBatchDelay" name="wa_batch_delay"
                         value="<?= htmlspecialchars((string)notif_get_custom_setting($conn, 'wa_batch_delay', '60')) ?>" min="0" required>
                  <div class="wa-hint">Lama waktu istirahat (delay) setelah mencapai batas batch di atas. Default: 60</div>
                </div>

                <button type="submit" class="btn-wa w-full mt-3">💾 Simpan Konfigurasi</button>
              </form>
            </div>
          </div>

          <!-- Test -->
          <div class="wa-card">
            <div class="wa-card-hdr">🧪 Uji Pengiriman Pesan</div>
            <div class="wa-card-body">
              <form method="POST">
                <input type="hidden" name="action" value="test_send">
                <div class="wa-field">
                  <label class="wa-label" for="testPhone">Nomor HP Tujuan</label>
                  <input class="wa-input" type="tel" id="testPhone" name="test_phone"
                         placeholder="0812xxxx atau 628xxx" required>
                  <div class="wa-hint">Nomor yang terdaftar di WhatsApp. Awali 08 atau 62.</div>
                </div>
                <div class="wa-field">
                  <label class="wa-label" for="testMessage">Isi Pesan Test</label>
                  <textarea class="wa-input" id="testMessage" name="test_message" rows="3" placeholder="Tulis pesan uji coba di sini..." required>Halo! Ini pesan test dari SIMANIS. Sistem notifikasi WhatsApp berhasil terhubung. 🎉</textarea>
                  <div class="wa-hint">Pesan kustom untuk uji coba pengiriman.</div>
                </div>
                <button type="submit" class="btn-wa w-full">📤 Kirim Pesan Test</button>
              </form>
            </div>
          </div>

          <!-- Panduan -->
          <div class="wa-card">
            <div class="wa-card-hdr">📖 Panduan Koneksi</div>
            <div class="wa-card-body">
              <ul class="guide-ul">
                <li>
                  <span class="gn">1</span>
                  <span>Jalankan WASENDER — buka terminal di <code>C:\Users\sman1\WASENDER</code> lalu ketik <code>node index.js</code></span>
                </li>
                <li>
                  <span class="gn">2</span>
                  <span>WASENDER berjalan di port <strong>3002</strong> sesuai file <code>.env</code></span>
                </li>
                <li>
                  <span class="gn">3</span>
                  <span>Buka <a href="http://127.0.0.1:3002" target="_blank">http://127.0.0.1:3002</a>, lalu <strong>scan QR Code</strong> dengan HP</span>
                </li>
                <li>
                  <span class="gn">4</span>
                  <span>Status hero di atas akan berubah <em>✅ WA Aktif &amp; Terhubung</em></span>
                </li>
                <li>
                  <span class="gn">5</span>
                  <span>Klik <strong>Uji Pengiriman</strong> untuk verifikasi</span>
                </li>
              </ul>
              <div style="margin-top:.85rem;padding:.7rem;background:#F0FFF4;border-radius:var(--rad-sm);border:1.5px solid #B2DFDB;font-size:.8rem;">
                <strong>💡 Trigger Otomatis</strong>
                <div class="tr-row">
                  <span class="tr-tag">📋 Pelanggaran Siswa</span>
                  <span class="tr-tag">📊 Absensi Alpa</span>
                  <span class="tr-tag">📢 Broadcast Admin</span>
                </div>
                <p style="margin:.5rem 0 0;color:#555;">Nomor orang tua dari kolom <code>no_wa</code> data siswa.</p>
              </div>
            </div>
          </div>

        </div><!-- /kiri -->

        <!-- ▌ KOLOM KANAN ▌ -->
        <div>

          <!-- Custom Notifications Settings -->
          <div class="wa-card">
            <div class="wa-card-hdr">⚙️ Kustomisasi Notifikasi Item</div>
            <div class="wa-card-body">
              <form method="POST">
                <input type="hidden" name="action" value="save_custom_notif">
                
                <?php
                $itemsConfig = [
                    'presensi' => [
                        'label' => '1. Kehadiran / Presensi Siswa (Orang Tua)',
                        'placeholders' => '{nama_siswa}, {kelas}, {status}, {waktu}',
                        'def_title' => '🔔 Notifikasi Kehadiran Siswa',
                        'def_tpl' => "Pemberitahuan Kehadiran Siswa:\nNama: {nama_siswa}\nKelas: {kelas}\nStatus: {status}\nTanggal/Waktu: {waktu}\n\nSemoga menjadi perhatian."
                    ],
                    'jurnal' => [
                        'label' => '2. Pengisian Jurnal Mengajar (Guru)',
                        'placeholders' => '{nama_guru}, {hari}, {tanggal}, {kelas}, {mapel}, {materi}, {jam}',
                        'def_title' => '📝 Pengisian Jurnal Mengajar',
                        'def_tpl' => "Halo Bapak/Ibu {nama_guru},\nTerima kasih telah mengisi Jurnal Mengajar pada:\nHari/Tanggal: {hari}, {tanggal}\nKelas: {kelas}\nMata Pelajaran: {mapel}\nMateri: {materi}\nJam ke: {jam}\n\nTetap semangat mendidik anak bangsa!"
                    ],
                    'izin' => [
                        'label' => '3. Pengajuan Izin Siswa (BK & Wali Kelas)',
                        'placeholders' => '{nama_siswa}, {kelas}, {jenis_izin}, {alasan}, {tanggal}, {link_validasi}',
                        'def_title' => '✉️ Pengajuan Izin Siswa',
                        'def_tpl' => "Pengajuan Izin Siswa Baru:\nNama: {nama_siswa}\nKelas: {kelas}\nJenis Izin: {jenis_izin}\nAlasan: {alasan}\nTanggal: {tanggal}\nLink Validasi: {link_validasi}\n\nMohon untuk diperiksa dan ditindaklanjuti."
                    ],
                    'laporan' => [
                        'label' => '4. Input Laporan Pelanggaran (Admin)',
                        'placeholders' => '{nama_pelapor}, {nama_siswa}, {kelas}, {kejadian}, {tanggal}',
                        'def_title' => '🚨 Laporan Kejadian Baru',
                        'def_tpl' => "Laporan Kejadian/Pelanggaran Baru:\nPelapor: {nama_pelapor}\nSiswa Terkait: {nama_siswa}\nKelas: {kelas}\nKejadian: {kejadian}\nTanggal: {tanggal}\n\nMohon lakukan verifikasi."
                    ],
                    'rekap' => [
                        'label' => '5. Rekap Absensi Harian (BK/Admin/Wali)',
                        'placeholders' => '{hari}, {tanggal}, {jumlah_sakit}, {jumlah_izin}, {jumlah_alfa}, {jumlah_dispen}, {total_tidak_hadir}',
                        'def_title' => '📊 Rekap Absensi Harian Siswa',
                        'def_tpl' => "Rekap Absensi Harian Siswa (s.d. 07.45):\nHari/Tanggal: {hari}, {tanggal}\n\nDetail:\nSakit: {jumlah_sakit} siswa\nIzin: {jumlah_izin} siswa\nAlfa: {jumlah_alfa} siswa\nDispen: {jumlah_dispen} siswa\n\nTotal tidak hadir: {total_tidak_hadir} siswa."
                    ]
                ];
                
                foreach ($itemsConfig as $key => $conf):
                    $statusVal = (int)notif_get_custom_setting($conn, 'wa_notif_' . $key . '_status', '1');
                    $titleVal = notif_get_custom_setting($conn, 'wa_notif_' . $key . '_title', $conf['def_title']);
                    $tplVal = notif_get_custom_setting($conn, 'wa_notif_' . $key . '_template', $conf['def_tpl']);
                ?>
                  <div style="border-bottom:1.5px solid #F0F0F0; padding-bottom:1rem; margin-bottom:1rem;">
                    <div class="tg-wrap mb-2">
                      <input type="checkbox" class="tg-cb" id="status_<?= $key ?>" name="wa_notif_<?= $key ?>_status" <?= $statusVal === 1 ? 'checked' : '' ?>>
                      <label class="tg-track" for="status_<?= $key ?>">
                        <div class="tg-thumb"></div>
                      </label>
                      <strong class="wa-label" style="margin:0;cursor:pointer;font-size:.9rem;" for="status_<?= $key ?>">
                        <?= $conf['label'] ?>
                      </strong>
                    </div>
                    
                    <div class="wa-field">
                      <label class="wa-label" style="font-size:.78rem;">Judul Pesan</label>
                      <input class="wa-input" style="padding:.45rem .7rem;font-size:.8rem;" type="text" name="wa_notif_<?= $key ?>_title" value="<?= htmlspecialchars($titleVal) ?>" required>
                    </div>
                    
                    <div class="wa-field">
                      <label class="wa-label" style="font-size:.78rem;">Template Pesan</label>
                      <textarea class="wa-input" style="padding:.45rem .7rem;font-size:.8rem;font-family:monospace;min-height:100px;" name="wa_notif_<?= $key ?>_template" required><?= htmlspecialchars($tplVal) ?></textarea>
                      <div class="wa-hint" style="font-size:.68rem;">Placeholder: <code><?= $conf['placeholders'] ?></code></div>
                    </div>
                  </div>
                <?php endforeach; ?>
                
                <button type="submit" class="btn-wa w-full mb-3">💾 Simpan Pengaturan Kustom</button>
              </form>
            </div>
          </div>

          <!-- Log -->
          <div class="wa-card">
            <div class="wa-card-hdr">
              📋 Log Notifikasi
              <span class="hdr-right">
                <button class="btn-wa-out btn-wa-out-sm" onclick="location.reload()">🔄 Refresh</button>
              </span>
            </div>

            <?php if (empty($recentNotif)): ?>
              <div class="empty-box">
                <div class="ei">📭</div>
                <p style="font-size:.85rem;margin:0">Belum ada riwayat notifikasi</p>
              </div>
            <?php else: ?>
            <div class="log-wrap">
              <table class="log-tbl">
                <thead>
                  <tr>
                    <th>Tujuan</th>
                    <th>Pesan</th>
                    <th>Status</th>
                    <th>Waktu</th>
                  </tr>
                </thead>
                <tbody>
                <?php foreach ($recentNotif as $n):
                  $bc  = match($n['status']){ 'sent'=>'bl-s','pending'=>'bl-p',default=>'bl-f' };
                  $bi  = match($n['status']){ 'sent'=>'✅','pending'=>'⏳',default=>'❌' };
                  $chi = strtolower($n['channel']) === 'whatsapp' ? '📱' : '✉️';
                ?>
                <tr>
                  <td>
                    <div style="font-size:.68rem;color:#888;margin-bottom:1px"><?= $chi ?> <?= strtoupper($n['channel']) ?></div>
                    <span class="phone-m"><?= htmlspecialchars($n['tujuan']) ?></span>
                  </td>
                  <td>
                    <span style="font-size:.78rem;font-weight:600;display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:140px"
                          title="<?= htmlspecialchars($n['judul']) ?>">
                      <?= htmlspecialchars($n['judul']) ?>
                    </span>
                    <?php if ($n['error_message']): ?>
                      <div class="err-txt" title="<?= htmlspecialchars($n['error_message']) ?>">
                        ↳ <?= htmlspecialchars(substr($n['error_message'], 0, 60)) ?><?= strlen($n['error_message']) > 60 ? '…' : '' ?>
                      </div>
                    <?php endif; ?>
                  </td>
                  <td>
                    <span class="bl <?= $bc ?>"><?= $bi ?> <?= ucfirst($n['status']) ?></span>
                    <?php if ((int)$n['percobaan'] > 0): ?>
                      <div style="font-size:.65rem;color:#bbb;margin-top:2px"><?= $n['percobaan'] ?>× coba</div>
                    <?php endif; ?>
                  </td>
                  <td class="ts-s">
                    <?= date('d/m', strtotime($n['created_at'])) ?><br>
                    <?= date('H:i', strtotime($n['created_at'])) ?>
                  </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <?php endif; ?>

          </div><!-- /log card -->

        </div><!-- /kanan -->
      </div><!-- /wa-2col -->

    </div><!-- /container-fluid -->

  </div><!-- /id=content -->

<?php include 'footer.php'; ?>

<script>
function toggleApiKey() {
  const inp = document.getElementById('apiKeyInp');
  const btn = document.getElementById('apiToggleBtn');
  inp.type = inp.type === 'password' ? 'text' : 'password';
  btn.textContent = inp.type === 'password' ? '👁' : '🙈';
}

setTimeout(function() {
  const f = document.getElementById('waFlash');
  if (!f) return;
  f.style.transition = 'opacity .5s';
  f.style.opacity = '0';
  setTimeout(() => f && f.remove(), 500);
}, 6000);
</script>
