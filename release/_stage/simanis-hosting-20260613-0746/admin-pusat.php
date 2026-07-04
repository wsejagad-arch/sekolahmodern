<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/admin_pusat_helper.php';

require_admin_pusat();

if (!$conn instanceof mysqli) {
    http_response_code(500);
    echo 'Database tidak tersambung.';
    exit;
}

pusat_admin_ensure_schema($conn);
mt_ensure_schema($conn);

$message = '';
$error = '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $token = (string)($_POST['csrf_token'] ?? '');
    $action = (string)($_POST['action'] ?? '');

    if (!verify_csrf_token($token)) {
        $error = 'Sesi formulir tidak valid.';
    } elseif ($action === 'status_sekolah') {
        $idSekolah = (int)($_POST['id_sekolah'] ?? 0);
        $status = (string)($_POST['status'] ?? '');
        if ($idSekolah <= 0 || !in_array($status, ['Aktif', 'Non-Aktif'], true)) {
            $error = 'Data sekolah tidak valid.';
        } elseif (pusat_update_school_status($conn, $idSekolah, $status)) {
            $message = 'Status sekolah berhasil diperbarui.';
        } else {
            $error = 'Gagal memperbarui status sekolah.';
        }
    } elseif ($action === 'delete_sekolah') {
        $idSekolah = (int)($_POST['id_sekolah'] ?? 0);
        if ($idSekolah <= 0) {
            $error = 'Data sekolah tidak valid.';
        } elseif (pusat_delete_school($conn, $idSekolah)) {
            $message = 'Sekolah berhasil dihapus.';
        } else {
            $error = 'Gagal menghapus sekolah.';
        }
    }
}

$search = trim((string)($_GET['q'] ?? ''));
$statusFilter = (string)($_GET['status'] ?? '');
$schools = pusat_school_rows($conn, $search, $statusFilter);
$stats = pusat_dashboard_stats($conn);
$oauth = pusat_google_oauth_status($conn);

if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="sekolah-terdaftar-simanis.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['ID Sekolah', 'Client ID/NPSN', 'Nama Sekolah', 'Email', 'HP', 'Status', 'Admin', 'Guru', 'Siswa', 'Kelas', 'Online', 'Dibuat']);
    foreach ($schools as $row) {
        fputcsv($out, [
            $row['id_sekolah'],
            $row['npsn'] ?: $row['kode_sekolah'],
            $row['nama_sekolah'],
            $row['email_kontak'],
            $row['hp_kontak'],
            $row['status'],
            $row['total_admin'],
            $row['total_guru'],
            $row['total_siswa'],
            $row['total_kelas'],
            $row['total_online'],
            $row['created_at'],
        ]);
    }
    fclose($out);
    exit;
}

function pusat_h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Pusat - SIMANIS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <style>
        body {
            margin: 0;
            background-color: #f8fafc !important;
            background-image: 
                radial-gradient(circle at 0% 0%, rgba(20, 184, 166, 0.04) 0%, transparent 50%),
                radial-gradient(circle at 100% 0%, rgba(37, 87, 167, 0.04) 0%, transparent 40%),
                radial-gradient(circle at 50% 100%, rgba(240, 180, 41, 0.02) 0%, transparent 60%),
                radial-gradient(rgba(148, 163, 184, 0.08) 1.5px, transparent 1.5px) !important;
            background-size: auto, auto, auto, 24px 24px !important;
            background-attachment: fixed !important;
            color: #111827;
            font-family: "Poppins", "Segoe UI", sans-serif;
        }

        .topbar {
            background: #111827;
            color: #ffffff;
            border-bottom: 4px solid #14b8a6;
        }

        .page {
            width: min(100%, 1260px);
            margin: 0 auto;
            padding: 24px 16px 42px;
        }

        .metric {
            min-height: 118px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background: #ffffff;
            padding: 18px;
        }

        .metric-label {
            color: #6b7280;
            font-size: .82rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .04em;
        }

        .metric-value {
            margin-top: 8px;
            font-size: 2rem;
            font-weight: 900;
        }

        .panel {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background: #ffffff;
        }

        .panel-head {
            padding: 18px 20px;
            border-bottom: 1px solid #e5e7eb;
        }

        .table> :not(caption)>*>* {
            padding: .85rem .8rem;
            vertical-align: middle;
        }

        .badge-soft {
            border-radius: 999px;
            padding: .42rem .72rem;
            font-weight: 800;
        }

        .badge-ok {
            background: #dcfce7;
            color: #166534;
        }

        .badge-off {
            background: #fee2e2;
            color: #991b1b;
        }

        .client-id {
            font-family: Consolas, "Courier New", monospace;
            font-weight: 800;
            color: #0f766e;
        }

        .small-muted {
            color: #6b7280;
            font-size: .82rem;
        }

        .btn-tight {
            border-radius: 8px;
            font-weight: 800;
        }

        @media (max-width: 900px) {
            .table-wrap {
                overflow-x: auto;
            }

            table {
                min-width: 1050px;
            }
        }
    </style>
</head>

<body>
    <header class="topbar">
        <div class="page py-3 d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <div class="small text-white-50 fw-semibold">SIMANIS</div>
                <h1 class="h4 fw-bold mb-0">Admin Pusat</h1>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="text-white-50 small">Masuk sebagai <?= pusat_h($_SESSION['nama'] ?? 'Admin Pusat'); ?></span>
                <a class="btn btn-sm btn-light btn-tight" href="login.php"><i class="bi bi-box-arrow-in-right me-1"></i> Login Sekolah</a>
                <a class="btn btn-sm btn-outline-light btn-tight" href="logout.php"><i class="bi bi-box-arrow-right me-1"></i> Keluar</a>
            </div>
        </div>
    </header>

    <main class="page">
        <?php if ($message): ?>
            <div class="alert alert-success"><?= pusat_h($message); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= pusat_h($error); ?></div>
        <?php endif; ?>

        <section class="row g-3 mb-3">
            <div class="col-12 col-md-6 col-xl-3">
                <div class="metric">
                    <div class="metric-label">Sekolah Terdaftar</div>
                    <div class="metric-value"><?= number_format($stats['schools']); ?></div>
                    <div class="small-muted"><?= number_format($stats['active_schools']); ?> aktif, <?= number_format($stats['inactive_schools']); ?> non-aktif</div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <div class="metric">
                    <div class="metric-label">Total Pengguna</div>
                    <div class="metric-value"><?= number_format($stats['admins'] + $stats['teachers'] + $stats['students']); ?></div>
                    <div class="small-muted"><?= number_format($stats['admins']); ?> admin, <?= number_format($stats['teachers']); ?> guru, <?= number_format($stats['students']); ?> siswa</div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <div class="metric">
                    <div class="metric-label">Google OAuth</div>
                    <div class="metric-value h3 mb-1"><?= $oauth['configured'] ? 'Aktif' : 'Kosong'; ?></div>
                    <div class="small-muted">Client ID: <?= pusat_h($oauth['client_id']); ?></div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <div class="metric">
                    <div class="metric-label">Ruang Data</div>
                    <div class="metric-value h3 mb-1">Terpisah</div>
                    <div class="small-muted">Setiap sekolah dipisahkan dengan id_sekolah dan NPSN.</div>
                </div>
            </div>
        </section>

        <section class="panel mb-3">
            <div class="panel-head">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <div>
                        <h2 class="h5 fw-bold mb-1">Sekolah dan Client ID Terdaftar</h2>
                        <div class="small-muted">Client ID sekolah memakai NPSN/kode sekolah. Data akademik tetap berada di ruang masing-masing sekolah.</div>
                    </div>
                    <a class="btn btn-outline-secondary btn-tight" href="admin-pusat.php?<?= http_build_query(['q' => $search, 'status' => $statusFilter, 'export' => 'csv']); ?>">
                        <i class="bi bi-download me-1"></i> Export CSV
                    </a>
                </div>
            </div>
            <div class="p-3 border-bottom">
                <form method="get" class="row g-2 align-items-end">
                    <div class="col-12 col-md-7">
                        <label class="form-label fw-semibold">Cari sekolah, NPSN, email, atau HP</label>
                        <input class="form-control" name="q" value="<?= pusat_h($search); ?>" placeholder="Contoh: 202xxxxx atau nama sekolah">
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label fw-semibold">Status</label>
                        <select class="form-select" name="status">
                            <option value="">Semua status</option>
                            <option value="Aktif" <?= $statusFilter === 'Aktif' ? 'selected' : ''; ?>>Aktif</option>
                            <option value="Non-Aktif" <?= $statusFilter === 'Non-Aktif' ? 'selected' : ''; ?>>Non-Aktif</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-2 d-grid">
                        <button class="btn btn-primary btn-tight" type="submit"><i class="bi bi-search me-1"></i> Filter</button>
                    </div>
                </form>
            </div>
            <div class="table-wrap">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Client ID / NPSN</th>
                            <th>Sekolah</th>
                            <th>Kontak</th>
                            <th>Status</th>
                            <th class="text-center">Admin</th>
                            <th class="text-center">Guru</th>
                            <th class="text-center">Siswa</th>
                            <th class="text-center">Kelas</th>
                            <th class="text-center">Online</th>
                            <th>Aktivitas</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$schools): ?>
                            <tr>
                                <td colspan="11" class="text-center py-5 text-muted">Belum ada sekolah yang sesuai filter.</td>
                            </tr>
                        <?php endif; ?>
                        <?php foreach ($schools as $school): ?>
                            <?php
                            $clientId = $school['npsn'] ?: $school['kode_sekolah'];
                            $isActive = ($school['status'] ?? '') === 'Aktif';
                            $nextStatus = $isActive ? 'Non-Aktif' : 'Aktif';
                            ?>
                            <tr>
                                <td>
                                    <div class="client-id"><?= pusat_h($clientId); ?></div>
                                    <div class="small-muted">Internal #<?= (int)$school['id_sekolah']; ?></div>
                                </td>
                                <td>
                                    <div class="fw-bold"><?= pusat_h($school['nama_sekolah']); ?></div>
                                    <div class="small-muted"><?= pusat_h($school['alamat'] ?: '-'); ?></div>
                                </td>
                                <td>
                                    <div><?= pusat_h($school['email_kontak'] ?: '-'); ?></div>
                                    <div class="small-muted"><?= pusat_h($school['hp_kontak'] ?: '-'); ?></div>
                                </td>
                                <td>
                                    <span class="badge-soft <?= $isActive ? 'badge-ok' : 'badge-off'; ?>"><?= pusat_h($school['status']); ?></span>
                                </td>
                                <td class="text-center fw-bold"><?= number_format((int)$school['total_admin']); ?></td>
                                <td class="text-center fw-bold"><?= number_format((int)$school['total_guru']); ?></td>
                                <td class="text-center fw-bold"><?= number_format((int)$school['total_siswa']); ?></td>
                                <td class="text-center fw-bold"><?= number_format((int)$school['total_kelas']); ?></td>
                                <td class="text-center fw-bold"><?= number_format((int)$school['total_online']); ?></td>
                                <td>
                                    <div class="small-muted">Dibuat: <?= pusat_h($school['created_at'] ? date('d/m/Y H:i', strtotime($school['created_at'])) : '-'); ?></div>
                                    <div class="small-muted">Terakhir online: <?= pusat_h($school['last_activity'] ? date('d/m/Y H:i', strtotime($school['last_activity'])) : '-'); ?></div>
                                </td>
                                <td class="text-end">
                                    <div class="d-flex justify-content-end gap-2">
                                        <a class="btn btn-sm btn-outline-primary btn-tight" href="login.php?kode=<?= urlencode((string)$clientId); ?>" title="Buka halaman login sekolah">
                                            <i class="bi bi-door-open"></i>
                                        </a>
                                        <form method="post" onsubmit="return confirm('Ubah status sekolah ini menjadi <?= pusat_h($nextStatus); ?>?')">
                                            <input type="hidden" name="csrf_token" value="<?= pusat_h(generate_csrf_token()); ?>">
                                            <input type="hidden" name="action" value="status_sekolah">
                                            <input type="hidden" name="id_sekolah" value="<?= (int)$school['id_sekolah']; ?>">
                                            <input type="hidden" name="status" value="<?= pusat_h($nextStatus); ?>">
                                            <button class="btn btn-sm <?= $isActive ? 'btn-outline-danger' : 'btn-outline-success'; ?> btn-tight" type="submit">
                                                <?= $isActive ? 'Nonaktifkan' : 'Aktifkan'; ?>
                                            </button>
                                        </form>
                                        <form method="post" onsubmit="return confirm('PERHATIAN: Sekolah dan semua data (guru, siswa, kelas) akan dihapus. Lanjutkan?')">
                                            <input type="hidden" name="csrf_token" value="<?= pusat_h(generate_csrf_token()); ?>">
                                            <input type="hidden" name="action" value="delete_sekolah">
                                            <input type="hidden" name="id_sekolah" value="<?= (int)$school['id_sekolah']; ?>">
                                            <button class="btn btn-sm btn-outline-danger btn-tight" type="submit" title="Hapus sekolah">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="panel">
            <div class="p-3">
                <div class="fw-bold mb-1">Redirect URI Google OAuth</div>
                <code><?= pusat_h($oauth['redirect_uri']); ?></code>
                <div class="small-muted mt-2">Pengaturan Login Gmail tetap dapat diubah dari menu admin sekolah: Pengaturan > Login Gmail.</div>
            </div>
        </section>

        <?php include_once __DIR__ . '/components/shared_footer.php'; ?>
    </main>
</body>

</html>