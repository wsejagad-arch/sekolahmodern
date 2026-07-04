<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['no_induk']) || (int)($_SESSION['hak_akses'] ?? 0) !== 2) {
    header('Location: ../../login.php?haruslogin');
    exit;
}

require_once __DIR__ . '/../../koneksi.php';

$nipGuru = (string)$_SESSION['no_induk'];
$nipEsc = mysqli_real_escape_string($conn, $nipGuru);
$id = (int)($_GET['id'] ?? 0);
$tenantId = function_exists('mt_current_school_id') ? mt_current_school_id() : 1;
$tenantTugas = function_exists('mt_column_exists') && $conn instanceof mysqli && mt_column_exists($conn, 'tbl_tugas', 'id_sekolah') ? "id_sekolah={$tenantId}" : "1=1";

function td_h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$task = null;
if ($id > 0) {
    $q = mysqli_query($conn, "SELECT * FROM tbl_tugas WHERE {$tenantTugas} AND id={$id} AND no_induk_guru='{$nipEsc}' LIMIT 1");
    $task = $q ? mysqli_fetch_assoc($q) : null;
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Detail Tugas - SIMANIS</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        body { min-height:100vh; margin:0; background:linear-gradient(135deg,#ecfdf5,#eef2ff); color:#0f172a; padding-bottom:120px; font-family:"Segoe UI", Arial, sans-serif; }
        .shell { max-width:820px; margin:0 auto; padding:22px; }
        .panel { background:#fff; border:1px solid #dbeafe; border-radius:22px; box-shadow:0 16px 40px rgba(15,23,42,.1); overflow:hidden; }
        .head { background:linear-gradient(135deg,#0f766e,#2563eb); color:#fff; padding:24px; }
        .body { padding:22px; }
        .meta { display:grid; grid-template-columns:repeat(2,1fr); gap:12px; }
        .meta-box { border:1px solid #e2e8f0; border-radius:14px; padding:12px; background:#f8fafc; }
        .meta-box small { color:#64748b; font-weight:800; text-transform:uppercase; letter-spacing:.04em; }
        .meta-box strong { display:block; margin-top:4px; }
        @media (max-width:640px) { .shell { padding:14px; } .meta { grid-template-columns:1fr; } }
    </style>
</head>
<body>
<main class="shell">
    <a href="history-tugas" class="btn btn-sm btn-outline-secondary mb-3"><i class="bi bi-arrow-left"></i> Kembali</a>
    <?php if (!$task): ?>
        <div class="panel body text-center text-muted">
            <i class="bi bi-clipboard-x fs-1 d-block mb-2"></i>
            Tugas tidak ditemukan.
        </div>
    <?php else: ?>
        <article class="panel">
            <header class="head">
                <span class="badge text-bg-light mb-2"><?= td_h($task['status']); ?></span>
                <h1 class="h3 mb-1"><?= td_h($task['judul_tugas']); ?></h1>
                <p class="mb-0 text-white-50"><?= td_h($task['mapel']); ?> - Kelas <?= td_h($task['kelas']); ?></p>
            </header>
            <div class="body">
                <div class="meta mb-3">
                    <div class="meta-box"><small>Tanggal Dibuat</small><strong><?= td_h(date('d/m/Y', strtotime((string)$task['tanggal']))); ?></strong></div>
                    <div class="meta-box"><small>Deadline</small><strong><?= !empty($task['tanggal_pengumpulan']) ? td_h(date('d/m/Y', strtotime((string)$task['tanggal_pengumpulan']))) : '-'; ?></strong></div>
                </div>
                <h2 class="h6 fw-bold">Instruksi</h2>
                <div class="border rounded-4 p-3 bg-light mb-3"><?= nl2br(td_h($task['deskripsi'])); ?></div>
                <div class="d-flex gap-2 flex-wrap">
                    <?php if (!empty($task['link_tugas'])): ?>
                        <a class="btn btn-outline-primary" target="_blank" href="<?= td_h($task['link_tugas']); ?>"><i class="bi bi-link-45deg"></i> Buka Link</a>
                    <?php endif; ?>
                    <?php if (!empty($task['file_tugas'])): ?>
                        <a class="btn btn-outline-success" target="_blank" href="<?= td_h($task['file_tugas']); ?>"><i class="bi bi-paperclip"></i> Unduh File</a>
                    <?php endif; ?>
                </div>
            </div>
        </article>
    <?php endif; ?>
</main>
<?php include __DIR__ . '/guru_common_footer.php'; ?>
</body>
</html>
