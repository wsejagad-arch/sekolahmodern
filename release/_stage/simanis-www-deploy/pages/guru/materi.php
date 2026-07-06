<?php
require_once __DIR__ . '/../../auth_helper.php';
require_once __DIR__ . '/../../bootstrap.php';

// Safe check: guru access level only (hak_akses = 2)
if (!isset($_SESSION["no_induk"]) || $_SESSION['hak_akses'] != 2) {
    header("Location: ../../index.php?haruslogin");
    exit;
}

$nip = $_SESSION['no_induk'];
$nipEsc = mysqli_real_escape_string($conn, $nip);

// Get teacher data
$sqlGuru = mysqli_query($conn, "SELECT * FROM tbl_guru WHERE no_induk='$nipEsc'");
$dataGuru = mysqli_fetch_array($sqlGuru);
$namaGuru = $dataGuru['nama_guru'] ?? $_SESSION['nama_guru'] ?? 'Guru';

// Directory for uploaded material files
$uploadDir = __DIR__ . '/../../materi/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// ----------------------------------------------------
// PROCESS ACTIONS (INSERT, UPDATE, DELETE)
// ----------------------------------------------------
$alertMessage = "";
$alertType = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];

        // 1. ADD NEW MATERIAL
        if ($action === 'create') {
            $id_mapel = (int)($_POST['id_mapel'] ?? 0);
            $judul = mysqli_real_escape_string($conn, trim($_POST['judul'] ?? ''));
            $deskripsi = mysqli_real_escape_string($conn, trim($_POST['deskripsi'] ?? ''));
            $warna_bg = mysqli_real_escape_string($conn, $_POST['warna_bg'] ?? 'white');

            // Find mapel details from ampu list to prevent manual typing
            $qAmpu = mysqli_query($conn, "SELECT nama_mapel, kelas FROM tbl_mapel_ampu WHERE id_mapel = '$id_mapel' AND no_induk = '$nipEsc' LIMIT 1");
            $mapelInfo = mysqli_fetch_assoc($qAmpu);

            if ($id_mapel <= 0 || !$mapelInfo) {
                $alertMessage = "Mata Pelajaran yang dipilih tidak valid atau Anda tidak berwenang.";
                $alertType = "danger";
            } elseif (empty($judul)) {
                $alertMessage = "Judul materi tidak boleh kosong.";
                $alertType = "danger";
            } else {
                $nama_mapel = mysqli_real_escape_string($conn, $mapelInfo['nama_mapel']);
                $kelas = mysqli_real_escape_string($conn, $mapelInfo['kelas']);
                
                // Handle PDF upload
                $filePdfName = null;
                if (isset($_FILES['file_pdf']) && $_FILES['file_pdf']['error'] === UPLOAD_ERR_OK) {
                    $fileName = $_FILES['file_pdf']['name'];
                    $fileTmp = $_FILES['file_pdf']['tmp_name'];
                    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

                    if ($fileExt !== 'pdf') {
                        $alertMessage = "Format file harus PDF.";
                        $alertType = "danger";
                    } else {
                        // Create a clean, unique name
                        $cleanTitle = preg_replace('/[^a-zA-Z0-9]/', '_', substr($judul, 0, 30));
                        $filePdfName = 'MATERI_' . $cleanTitle . '_' . time() . '.pdf';
                        if (move_uploaded_file($fileTmp, $uploadDir . $filePdfName)) {
                            // File moved successfully
                        } else {
                            $filePdfName = null;
                            $alertMessage = "Gagal mengunggah file PDF.";
                            $alertType = "danger";
                        }
                    }
                }

                if ($alertType !== 'danger') {
                    $namaGuruEsc = mysqli_real_escape_string($conn, $namaGuru);
                    $insertQuery = "INSERT INTO tbl_bahan_ajar 
                        (no_induk, nama_guru, id_mapel, nama_mapel, kelas, judul, deskripsi, file_pdf, warna_bg) 
                        VALUES 
                        ('$nipEsc', '$namaGuruEsc', '$id_mapel', '$nama_mapel', '$kelas', '$judul', '$deskripsi', " . ($filePdfName ? "'$filePdfName'" : "NULL") . ", '$warna_bg')";
                    
                    if (mysqli_query($conn, $insertQuery)) {
                        header("Location: materi?sukses=tambah");
                        exit;
                    } else {
                        $alertMessage = "Gagal menyimpan ke database: " . mysqli_error($conn);
                        $alertType = "danger";
                    }
                }
            }
        }

        // 2. EDIT MATERIAL
        if ($action === 'update') {
            $id_bahan = (int)($_POST['id_bahan'] ?? 0);
            $id_mapel = (int)($_POST['id_mapel'] ?? 0);
            $judul = mysqli_real_escape_string($conn, trim($_POST['judul'] ?? ''));
            $deskripsi = mysqli_real_escape_string($conn, trim($_POST['deskripsi'] ?? ''));
            $warna_bg = mysqli_real_escape_string($conn, $_POST['warna_bg'] ?? 'white');

            // Verify owner
            $qVerify = mysqli_query($conn, "SELECT * FROM tbl_bahan_ajar WHERE id_bahan = '$id_bahan' AND no_induk = '$nipEsc' LIMIT 1");
            $existingBahan = mysqli_fetch_assoc($qVerify);

            // Find mapel details
            $qAmpu = mysqli_query($conn, "SELECT nama_mapel, kelas FROM tbl_mapel_ampu WHERE id_mapel = '$id_mapel' AND no_induk = '$nipEsc' LIMIT 1");
            $mapelInfo = mysqli_fetch_assoc($qAmpu);

            if (!$existingBahan) {
                $alertMessage = "Bahan ajar tidak ditemukan atau Anda tidak berhak mengaksesnya.";
                $alertType = "danger";
            } elseif ($id_mapel <= 0 || !$mapelInfo) {
                $alertMessage = "Mata Pelajaran yang dipilih tidak valid.";
                $alertType = "danger";
            } elseif (empty($judul)) {
                $alertMessage = "Judul materi tidak boleh kosong.";
                $alertType = "danger";
            } else {
                $nama_mapel = mysqli_real_escape_string($conn, $mapelInfo['nama_mapel']);
                $kelas = mysqli_real_escape_string($conn, $mapelInfo['kelas']);
                $filePdfName = $existingBahan['file_pdf'];

                // Handle PDF upload replacement
                if (isset($_FILES['file_pdf']) && $_FILES['file_pdf']['error'] === UPLOAD_ERR_OK) {
                    $fileName = $_FILES['file_pdf']['name'];
                    $fileTmp = $_FILES['file_pdf']['tmp_name'];
                    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

                    if ($fileExt !== 'pdf') {
                        $alertMessage = "Format file harus PDF.";
                        $alertType = "danger";
                    } else {
                        // Delete old PDF if exists
                        if (!empty($existingBahan['file_pdf']) && file_exists($uploadDir . $existingBahan['file_pdf'])) {
                            @unlink($uploadDir . $existingBahan['file_pdf']);
                        }

                        // Save new PDF
                        $cleanTitle = preg_replace('/[^a-zA-Z0-9]/', '_', substr($judul, 0, 30));
                        $filePdfName = 'MATERI_' . $cleanTitle . '_' . time() . '.pdf';
                        if (!move_uploaded_file($fileTmp, $uploadDir . $filePdfName)) {
                            $filePdfName = $existingBahan['file_pdf']; // Keep old filename on error
                            $alertMessage = "Gagal mengunggah PDF baru.";
                            $alertType = "danger";
                        }
                    }
                }

                if ($alertType !== 'danger') {
                    $updateQuery = "UPDATE tbl_bahan_ajar SET 
                        id_mapel = '$id_mapel',
                        nama_mapel = '$nama_mapel',
                        kelas = '$kelas',
                        judul = '$judul',
                        deskripsi = '$deskripsi',
                        file_pdf = " . ($filePdfName ? "'$filePdfName'" : "NULL") . ",
                        warna_bg = '$warna_bg'
                        WHERE id_bahan = '$id_bahan' AND no_induk = '$nipEsc'";

                    if (mysqli_query($conn, $updateQuery)) {
                        header("Location: materi?sukses=edit");
                        exit;
                    } else {
                        $alertMessage = "Gagal mengupdate database: " . mysqli_error($conn);
                        $alertType = "danger";
                    }
                }
            }
        }
    }
}

// 3. DELETE MATERIAL (GET action)
if (isset($_GET['delete']) && (int)$_GET['delete'] > 0) {
    $id_del = (int)$_GET['delete'];
    
    // Check ownership and get file name
    $qCheck = mysqli_query($conn, "SELECT file_pdf FROM tbl_bahan_ajar WHERE id_bahan = '$id_del' AND no_induk = '$nipEsc' LIMIT 1");
    $delData = mysqli_fetch_assoc($qCheck);

    if ($delData) {
        // Delete PDF file from server
        if (!empty($delData['file_pdf']) && file_exists($uploadDir . $delData['file_pdf'])) {
            @unlink($uploadDir . $delData['file_pdf']);
        }

        // Delete row
        mysqli_query($conn, "DELETE FROM tbl_bahan_ajar WHERE id_bahan = '$id_del' AND no_induk = '$nipEsc'");
        header("Location: materi?sukses=hapus");
        exit;
    } else {
        $alertMessage = "Materi tidak ditemukan atau Anda tidak berwenang menghapusnya.";
        $alertType = "danger";
    }
}

// Success Alert Handling
if (isset($_GET['sukses'])) {
    $suksesAction = $_GET['sukses'];
    if ($suksesAction === 'tambah') {
        $alertMessage = "Materi pembelajaran berhasil ditambahkan ke Board!";
        $alertType = "success";
    } elseif ($suksesAction === 'edit') {
        $alertMessage = "Materi pembelajaran berhasil diperbarui!";
        $alertType = "success";
    } elseif ($suksesAction === 'hapus') {
        $alertMessage = "Materi berhasil dihapus dari Board.";
        $alertType = "success";
    }
}

// ----------------------------------------------------
// READ AND LOAD COMPONENT DATA
// ----------------------------------------------------
// Load teacher's ampu maps for dropdowns
$mapelAmpuList = [];
$qAmpu = mysqli_query($conn, "SELECT DISTINCT id_mapel, nama_mapel, kelas FROM tbl_mapel_ampu WHERE no_induk = '$nipEsc' ORDER BY kelas ASC, nama_mapel ASC");
while ($row = mysqli_fetch_assoc($qAmpu)) {
    $mapelAmpuList[] = $row;
}

// Load all materials uploaded by this teacher
$materiList = [];
$qMateri = mysqli_query($conn, "SELECT * FROM tbl_bahan_ajar WHERE no_induk = '$nipEsc' ORDER BY id_bahan DESC");
if ($qMateri) {
    while ($row = mysqli_fetch_assoc($qMateri)) {
        $materiList[] = $row;
    }
}

// Extract classes for dynamic filter tags
$classes = array_unique(array_column($materiList, 'kelas'));
sort($classes);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>Materi Pembelajaran - Padlet Board</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/guru-desktop.css?v=<?= time(); ?>">
    <style>
        /* Modern masonry board */
        body { background: #ebf1f6; font-family: 'Plus Jakarta Sans', system-ui, sans-serif; }
        .padlet-board {
            column-count: 2;
            column-gap: 12px;
            width: 100%;
            margin-top: 14px;
        }

        .padlet-card {
            break-inside: avoid;
            margin-bottom: 14px;
            border-radius: 20px;
            padding: 16px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
            transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.25s ease;
            display: flex;
            flex-direction: column;
            border: 1px solid rgba(0, 0, 0, 0.06);
            position: relative;
        }

        .padlet-card:hover {
            transform: translateY(-4px) scale(1.01);
            box-shadow: 0 12px 24px rgba(15, 23, 42, 0.08);
        }

        /* Color classes */
        .card-color-white { background: #ffffff !important; color: #1e293b !important; }
        .card-color-yellow { background: #fef3c7 !important; border-color: #fde68a !important; color: #78350f !important; }
        .card-color-green { background: #dcfce7 !important; border-color: #bbf7d0 !important; color: #14532d !important; }
        .card-color-blue { background: #dbeafe !important; border-color: #bfdbfe !important; color: #1e3a8a !important; }
        .card-color-pink { background: #ffe4e6 !important; border-color: #fecdd3 !important; color: #881337 !important; }
        .card-color-purple { background: #f3e8ff !important; border-color: #e9d5ff !important; color: #581c87 !important; }
        .card-color-orange { background: #ffedd5 !important; border-color: #fed7aa !important; color: #7c2d12 !important; }

        /* Meta style inside card */
        .padlet-badge-row {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            margin-bottom: 10px;
        }

        .padlet-badge {
            font-size: 8.5px;
            font-weight: 700;
            padding: 3px 8px;
            border-radius: 99px;
            text-transform: uppercase;
            letter-spacing: 0.2px;
            background: rgba(0, 0, 0, 0.06);
            color: inherit;
        }

        .padlet-title {
            font-size: 13.5px;
            font-weight: 700;
            margin: 0 0 6px 0;
            line-height: 1.35;
        }

        .padlet-desc {
            font-size: 11px;
            line-height: 1.45;
            margin-bottom: 12px;
            white-space: pre-wrap;
            opacity: 0.95;
        }

        /* PDF Attachment Box */
        .pdf-attachment {
            display: flex;
            align-items: center;
            gap: 10px;
            background: rgba(255, 255, 255, 0.45);
            border: 1px solid rgba(0, 0, 0, 0.08);
            border-radius: 12px;
            padding: 8px 10px;
            margin-bottom: 12px;
            transition: background-color 0.2s ease;
            text-decoration: none;
            color: inherit;
        }

        .pdf-attachment:hover {
            background: rgba(255, 255, 255, 0.7);
        }

        .pdf-icon {
            font-size: 24px;
            color: #ef4444;
            flex-shrink: 0;
            line-height: 1;
        }

        .pdf-meta {
            min-width: 0;
            flex: 1;
            text-align: left;
        }

        .pdf-filename {
            font-size: 10px;
            font-weight: 600;
            display: block;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .pdf-size {
            font-size: 8px;
            opacity: 0.6;
            display: block;
        }

        .pdf-open-btn {
            font-size: 10px;
            color: var(--primary);
            font-weight: 700;
            white-space: nowrap;
        }

        /* Card Footer Actions */
        .padlet-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: auto;
            padding-top: 10px;
            border-top: 1px dashed rgba(0, 0, 0, 0.08);
        }

        .padlet-time {
            font-size: 8.5px;
            opacity: 0.6;
        }

        .padlet-actions {
            display: flex;
            gap: 8px;
        }

        .padlet-action-btn {
            background: none;
            border: none;
            padding: 4px 6px;
            font-size: 12px;
            color: inherit;
            opacity: 0.7;
            transition: opacity 0.15s ease, transform 0.15s ease;
            cursor: pointer;
            line-height: 1;
        }

        .padlet-action-btn:hover {
            opacity: 1;
            transform: scale(1.15);
        }

        .padlet-action-btn.del:hover {
            color: #ef4444;
        }

        /* Horizontal tag scroll for filter */
        .filter-tags-container {
            display: flex;
            gap: 8px;
            overflow-x: auto;
            padding: 2px 4px 10px 4px;
            margin-bottom: 12px;
            scroll-behavior: smooth;
            -webkit-overflow-scrolling: touch;
        }

        .filter-tags-container::-webkit-scrollbar {
            display: none; /* Hide scrollbar for clean UI */
        }

        .filter-tag {
            background: #fff;
            color: var(--text-muted);
            border: 1px solid var(--border);
            padding: 6px 14px;
            border-radius: 99px;
            font-size: 11px;
            font-weight: 500;
            white-space: nowrap;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .filter-tag.active {
            background: var(--primary);
            color: #fff;
            border-color: var(--primary);
            box-shadow: 0 4px 10px rgba(79, 70, 229, 0.2);
        }

        /* Search input bar */
        .search-wrapper {
            position: relative;
            margin-bottom: 14px;
            padding: 0 4px;
        }

        .search-input {
            width: 100%;
            background: #fff;
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            padding: 12px 14px 12px 42px;
            font-size: 12.5px;
            font-family: inherit;
            color: var(--text-main);
            transition: all 0.2s ease;
            box-shadow: var(--shadow-sm);
        }

        .search-input:focus {
            outline: none;
            border-color: var(--primary-light);
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.15);
        }

        .search-icon {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 16px;
        }

        /* Floating action button (FAB) for adding new material on mobile */
        .add-material-fab {
            position: fixed;
            bottom: 100px;
            right: 20px;
            width: 54px;
            height: 54px;
            border-radius: 50%;
            background: linear-gradient(135deg, #6366f1, #4f46e5);
            color: #fff;
            display: grid;
            place-items: center;
            box-shadow: 0 10px 25px rgba(79, 70, 229, 0.45);
            border: 4px solid #fff;
            z-index: 999;
            cursor: pointer;
            transition: transform 0.2s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .add-material-fab:active {
            transform: scale(0.9);
        }

        .add-material-fab i {
            font-size: 22px;
            line-height: 1;
        }

        /* Blob color picker */
        .color-blob {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: inline-grid;
            place-items: center;
            cursor: pointer;
            border: 2px solid transparent;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
            transition: all 0.2s ease;
        }

        .color-blob:hover {
            transform: scale(1.1);
        }

        .color-blob input:checked + .color-indicator {
            display: grid;
        }

        .color-indicator {
            display: none;
            place-items: center;
            font-size: 16px;
            color: inherit;
            line-height: 1;
        }

        .bg-white { background: #ffffff !important; border-color: #cbd5e1 !important; color: #1e293b !important; }
        .bg-yellow { background: #fef3c7 !important; border-color: #fcd34d !important; color: #78350f !important; }
        .bg-green { background: #dcfce7 !important; border-color: #86efac !important; color: #14532d !important; }
        .bg-blue { background: #dbeafe !important; border-color: #93c5fd !important; color: #1e3a8a !important; }
        .bg-pink { background: #ffe4e6 !important; border-color: #fda4af !important; color: #881337 !important; }
        .bg-purple { background: #f3e8ff !important; border-color: #d8b4fe !important; color: #581c87 !important; }
        .bg-orange { background: #ffedd5 !important; border-color: #fdba74 !important; color: #7c2d12 !important; }

        .materi-empty-state {
            text-align: center;
            padding: 40px 20px;
            background: #fff;
            border-radius: var(--radius-lg);
            border: 1px dashed var(--border);
            margin: 20px 4px;
        }

        .materi-empty-state i {
            font-size: 42px;
            color: var(--text-muted);
            opacity: 0.4;
            margin-bottom: 12px;
            display: block;
        }

        .materi-empty-state p {
            font-size: 12.5px;
            color: var(--text-muted);
            margin: 0;
        }

        /* Slide drawer alert */
        .top-toast-alert {
            animation: slideDownIn 0.3s ease forwards;
            border-radius: var(--radius-sm);
            border: none;
            margin: 0 4px 16px 4px;
            font-size: 11.5px;
            font-weight: 500;
        }

        @keyframes slideDownIn {
            from { transform: translateY(-20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
    </style>
</head>
<body>
<?php include 'guru_sidebar_shared.php'; ?>


<div class="app-shell" style="grid-template-columns: 1fr; padding-right: 24px;">
    <div class="desktop-center-column">
        <!-- Welcome Banner -->
        <div class="welcome-banner-premium mb-4">
            <div class="banner-content">
                <div class="banner-text">
                    <h2 class="animate-fade-in" style="font-size:2.2rem;font-weight:800;margin-bottom:12px;letter-spacing:-0.5px;">Materi Pembelajaran 📚</h2>
                    <p class="banner-subtitle" style="font-size:1.05rem;opacity:0.9;">Bahan ajar dan dokumen untuk kelas yang dikelola oleh <?= htmlspecialchars($namaGuru) ?></p>
                </div>
                <div class="banner-actions">
                    <button class="btn-premium-primary btn-open-create-modal"><i class="bi bi-plus-lg"></i> Tambah Materi</button>
                    <a href="../../home.php" class="btn-premium-secondary"><i class="bi bi-arrow-left"></i> Kembali</a>
                </div>
            </div>
            <div class="banner-shapes">
                <div class="shape shape-1"></div>
                <div class="shape shape-2"></div>
                <div class="shape shape-3"></div>
            </div>
        </div>

    <!-- TOAST ALERT -->
    <?php if (!empty($alertMessage)): ?>
        <div class="alert alert-<?= $alertType ?> alert-dismissible fade show top-toast-alert" role="alert">
            <i class="bi <?= $alertType === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill' ?> me-2"></i>
            <?= htmlspecialchars($alertMessage) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close" style="font-size: 8px; padding: 1.25rem 1rem;"></button>
        </div>
    <?php endif; ?>

    <!-- INSTANT SEARCH -->
    <div class="search-wrapper">
        <i class="bi bi-search search-icon"></i>
        <input type="text" id="boardSearch" class="search-input" placeholder="Cari judul materi, mapel, atau deskripsi...">
    </div>

    <!-- HORIZONTAL FILTER TAGS -->
    <div class="filter-tags-container">
        <span class="filter-tag active" data-filter="all">Semua Kelas</span>
        <?php foreach ($classes as $cls): ?>
            <span class="filter-tag" data-filter="<?= htmlspecialchars($cls) ?>">Kelas <?= htmlspecialchars($cls) ?></span>
        <?php endforeach; ?>
    </div>

    <!-- BOARD CARDS COLLAGE (PADLET BOARD) -->
    <?php if (empty($materiList)): ?>
        <div class="materi-empty-state">
            <i class="bi bi-book-half"></i>
            <strong>Belum ada materi pembelajaran</strong>
            <p class="mt-2">Klik tombol "+" di kanan atas atau FAB di bawah untuk mulai menempel materi pembelajaran pertama Anda!</p>
        </div>
    <?php else: ?>
        <div class="padlet-board" id="materiBoard">
            <?php foreach ($materiList as $mat):
                $idBahan = (int)$mat['id_bahan'];
                $idMapel = (int)$mat['id_mapel'];
                $mapelNama = (string)$mat['nama_mapel'];
                $kelasNama = (string)$mat['kelas'];
                $judulMat = (string)$mat['judul'];
                $descMat = (string)$mat['deskripsi'];
                $filePdf = (string)$mat['file_pdf'];
                $warna = (string)($mat['warna_bg'] ?? 'white');
                $timestamp = date('d M Y • H:i', strtotime($mat['created_at']));
            ?>
                <article class="padlet-card card-color-<?= $warna ?>" data-kelas="<?= htmlspecialchars($kelasNama) ?>" data-search-content="<?= htmlspecialchars(strtolower($judulMat . ' ' . $mapelNama . ' ' . $descMat . ' ' . $kelasNama)) ?>">
                    <div class="padlet-badge-row">
                        <span class="padlet-badge"><?= htmlspecialchars($kelasNama) ?></span>
                        <span class="padlet-badge" style="background: rgba(0,0,0,0.04); font-weight: 500; text-transform: none;"><?= htmlspecialchars($mapelNama) ?></span>
                    </div>

                    <h3 class="padlet-title"><?= htmlspecialchars($judulMat) ?></h3>
                    
                    <?php if (!empty($descMat)): ?>
                        <p class="padlet-desc"><?= htmlspecialchars($descMat) ?></p>
                    <?php endif; ?>

                    <?php if (!empty($filePdf)): ?>
                        <a href="../../materi/<?= htmlspecialchars($filePdf) ?>" target="_blank" class="pdf-attachment" title="Klik untuk membuka PDF">
                            <i class="bi bi-file-earmark-pdf-fill pdf-icon"></i>
                            <div class="pdf-meta">
                                <span class="pdf-filename"><?= htmlspecialchars($filePdf) ?></span>
                                <span class="pdf-size">Dokumen PDF</span>
                            </div>
                            <span class="pdf-open-btn">Buka</span>
                        </a>
                    <?php endif; ?>

                    <div class="padlet-footer">
                        <span class="padlet-time"><?= $timestamp ?></span>
                        <div class="padlet-actions">
                            <button class="padlet-action-btn edit btn-edit-materi" 
                                    data-id="<?= $idBahan ?>"
                                    data-idmapel="<?= $idMapel ?>"
                                    data-judul="<?= htmlspecialchars($judulMat, ENT_QUOTES, 'UTF-8') ?>"
                                    data-deskripsi="<?= htmlspecialchars($descMat, ENT_QUOTES, 'UTF-8') ?>"
                                    data-warna="<?= $warna ?>"
                                    data-file="<?= htmlspecialchars($filePdf, ENT_QUOTES, 'UTF-8') ?>"
                                    title="Edit Materi">
                                <i class="bi bi-pencil-square"></i>
                            </button>
                            <a href="materi?delete=<?= $idBahan ?>" class="padlet-action-btn del" onclick="return confirm('Apakah Anda yakin ingin menghapus materi pembelajaran ini dari Board?')" title="Hapus Materi">
                                <i class="bi bi-trash3-fill"></i>
                            </a>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- FLOATING ACTION BUTTON (FAB) -->
    <div class="add-material-fab btn-open-create-modal" title="Tambah Materi Baru">
        <i class="bi bi-plus-lg"></i>
    </div>

    <!-- BOTTOM NAV (INTEGRATED SHELL NAVIGATION) -->
    <div class="bottom-nav-wrap">
        <nav class="bottom-nav">
            <a href="../../home.php" class="nav-link"><i class="bi bi-house-door"></i><span>Beranda</span></a>
            <a href="data-siswa" class="nav-link"><i class="bi bi-journal-bookmark"></i><span>Kelas</span></a>
            <a href="../../home.php?open_jurnal=1" class="nav-center" aria-label="Input jurnal"><i class="bi bi-fingerprint"></i></a>
            <a href="nilai" class="nav-link"><i class="bi bi-clipboard-check"></i><span>Nilai</span></a>
            <a href="profil-guru.php" class="nav-link">
                <div style="width: 20px; height: 20px; border-radius: 50%; overflow: hidden; border: 1.5px solid #cbd5e1; margin-bottom: 2px; position: relative;">
                    <?php if ($dataGuru['foto']): ?>
                        <img src="../../foto/<?= $dataGuru['foto'] ?>" alt="Profile" style="width: 100%; height: 100%; object-fit: cover;">
                    <?php else: ?>
                        <?= get_guru_avatar_svg(get_guru_gender($dataGuru['no_induk'], $dataGuru['nama_guru'])) ?>
                    <?php endif; ?>
                </div>
                <span>Profil</span>
            </a>
        </nav>
    </div>
    </div>
</div>

<!-- ====================================================
     ADD / EDIT MATERIAL SLIDE MODAL
==================================================== -->
<div class="journal-modal-backdrop" id="materiModal" aria-hidden="true">
    <div class="journal-modal journal-modal-sm" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
        <div class="journal-modal-head" style="background: linear-gradient(135deg, #4f46e5 0%, #312e81 100%); color: #fff; padding: 18px 20px;">
            <div>
                <h5 id="modalTitle" style="color: #fff; font-weight: 600; margin: 0;"><i class="bi bi-book-half me-2"></i> Tambah Materi</h5>
                <p id="modalSub" style="color: rgba(255,255,255,0.8); font-size: 11px; margin: 4px 0 0 0;">Lengkapi kolom materi dan dokumen di bawah.</p>
            </div>
            <button class="journal-modal-close" type="button" data-close-modal aria-label="Tutup" style="background: rgba(255, 255, 255, 0.15); color: #fff; border: none; width:32px; height:32px; border-radius:10px;"><i class="bi bi-x-lg"></i></button>
        </div>
        <div class="journal-modal-body" style="padding: 20px; text-align: left;">
            <form id="materiForm" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="id_bahan" id="formIdBahan" value="">

                <!-- Select Mata Pelajaran Ampu -->
                <div class="mb-3">
                    <label for="id_mapel" class="form-label fw-semibold">Pilih Mapel & Kelas <span class="text-danger">*</span></label>
                    <select name="id_mapel" id="formIdMapel" class="form-select shadow-none" style="font-size:12.5px; border-radius:12px; padding:10px;" required>
                        <option value="" disabled selected>-- Pilih Kelas / Mapel --</option>
                        <?php foreach ($mapelAmpuList as $ampu): ?>
                            <option value="<?= (int)$ampu['id_mapel'] ?>">Kelas <?= htmlspecialchars($ampu['kelas']) ?> • <?= htmlspecialchars($ampu['nama_mapel']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Judul Materi -->
                <div class="mb-3">
                    <label for="judul" class="form-label fw-semibold">Judul Materi <span class="text-danger">*</span></label>
                    <input type="text" name="judul" id="formJudul" class="form-control shadow-none" placeholder="Contoh: Integral Aljabar Dasar" style="font-size:12.5px; border-radius:12px; padding:10px;" required>
                </div>

                <!-- Deskripsi / Ringkasan -->
                <div class="mb-3">
                    <label for="deskripsi" class="form-label fw-semibold">Deskripsi / Ringkasan Materi</label>
                    <textarea name="deskripsi" id="formDeskripsi" class="form-control shadow-none" rows="4" placeholder="Tuliskan catatan, ringkasan, atau instruksi pengerjaan di sini..." style="font-size:12.5px; border-radius:12px; padding:10px; resize: none;"></textarea>
                </div>

                <!-- File PDF -->
                <div class="mb-3">
                    <label for="file_pdf" class="form-label fw-semibold">Upload File Materi (PDF)</label>
                    <input type="file" name="file_pdf" id="formFilePdf" class="form-control shadow-none" accept=".pdf" style="font-size:12.5px; border-radius:12px; padding:10px;">
                    <small class="text-muted d-block mt-1" style="font-size:10px;"><i class="bi bi-info-circle me-1"></i> File wajib berformat PDF. Ukuran maks 5MB.</small>
                    <div id="editFileIndicator" class="mt-2 p-2 bg-light rounded text-muted" style="font-size:10.5px; display:none;">
                        <i class="bi bi-file-earmark-check-fill text-success me-1"></i> File saat ini: <span id="currentFileName" class="fw-semibold"></span>
                        <div style="font-size:9.5px; margin-top:2px;">* Unggah file baru untuk mengganti. Biarkan kosong jika tidak ingin mengubah.</div>
                    </div>
                </div>

                <!-- Custom Card Color Picker (Padlet style) -->
                <div class="mb-4">
                    <label class="form-label fw-semibold">Pilih Warna Card Board</label>
                    <div class="d-flex gap-2 justify-content-between mt-1">
                        <label class="color-blob bg-white border" title="Putih">
                            <input type="radio" name="warna_bg" value="white" checked class="d-none">
                            <span class="color-indicator"><i class="bi bi-check"></i></span>
                        </label>
                        <label class="color-blob bg-yellow" title="Kuning">
                            <input type="radio" name="warna_bg" value="yellow" class="d-none">
                            <span class="color-indicator"><i class="bi bi-check"></i></span>
                        </label>
                        <label class="color-blob bg-green" title="Hijau">
                            <input type="radio" name="warna_bg" value="green" class="d-none">
                            <span class="color-indicator"><i class="bi bi-check"></i></span>
                        </label>
                        <label class="color-blob bg-blue" title="Biru">
                            <input type="radio" name="warna_bg" value="blue" class="d-none">
                            <span class="color-indicator"><i class="bi bi-check"></i></span>
                        </label>
                        <label class="color-blob bg-pink" title="Merah Muda">
                            <input type="radio" name="warna_bg" value="pink" class="d-none">
                            <span class="color-indicator"><i class="bi bi-check"></i></span>
                        </label>
                        <label class="color-blob bg-purple" title="Ungu">
                            <input type="radio" name="warna_bg" value="purple" class="d-none">
                            <span class="color-indicator"><i class="bi bi-check"></i></span>
                        </label>
                        <label class="color-blob bg-orange" title="Oranye">
                            <input type="radio" name="warna_bg" value="orange" class="d-none">
                            <span class="color-indicator"><i class="bi bi-check"></i></span>
                        </label>
                    </div>
                </div>

                <!-- Action Button -->
                <button type="submit" class="btn btn-primary w-100 py-3 fw-bold" style="border-radius:14px; background:linear-gradient(135deg, #6366f1, #4f46e5); border:none; box-shadow:0 8px 20px rgba(79, 70, 229, 0.25); font-size:13px;">
                    <i class="bi bi-save me-2"></i> Tempel di Board
                </button>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    const modalEl = document.getElementById("materiModal");
    const formEl = document.getElementById("materiForm");
    const modalTitle = document.getElementById("modalTitle");
    const modalSub = document.getElementById("modalSub");
    const formAction = document.getElementById("formAction");
    const formIdBahan = document.getElementById("formIdBahan");
    const formIdMapel = document.getElementById("formIdMapel");
    const formJudul = document.getElementById("formJudul");
    const formDeskripsi = document.getElementById("formDeskripsi");
    const formFilePdf = document.getElementById("formFilePdf");
    const editFileIndicator = document.getElementById("editFileIndicator");
    const currentFileName = document.getElementById("currentFileName");

    // Close Modal Logic
    document.querySelectorAll("[data-close-modal]").forEach(btn => {
        btn.addEventListener("click", function() {
            modalEl.classList.remove("is-open");
            modalEl.setAttribute("aria-hidden", "true");
            document.body.classList.remove("modal-open-dashboard");
        });
    });

    // 1. OPEN CREATE MODAL
    document.querySelectorAll(".btn-open-create-modal").forEach(btn => {
        btn.addEventListener("click", function() {
            // Reset form fields
            formEl.reset();
            formAction.value = "create";
            formIdBahan.value = "";
            
            modalTitle.innerHTML = '<i class="bi bi-plus-circle-fill me-2"></i> Tambah Materi';
            modalSub.innerText = "Lengkapi kolom materi dan dokumen di bawah.";
            editFileIndicator.style.display = "none";
            formFilePdf.required = false; // Make file upload optional since they might just want text

            // Select default white color blob
            document.querySelectorAll("input[name='warna_bg']").forEach(radio => {
                radio.checked = (radio.value === 'white');
            });

            // Open Modal
            modalEl.classList.add("is-open");
            modalEl.setAttribute("aria-hidden", "false");
            document.body.classList.add("modal-open-dashboard");
        });
    });

    // 2. OPEN EDIT MODAL
    document.querySelectorAll(".btn-edit-materi").forEach(btn => {
        btn.addEventListener("click", function() {
            const id = this.getAttribute("data-id");
            const idmapel = this.getAttribute("data-idmapel");
            const judul = this.getAttribute("data-judul");
            const deskripsi = this.getAttribute("data-deskripsi");
            const warna = this.getAttribute("data-warna");
            const file = this.getAttribute("data-file");

            // Setup edit form action
            formAction.value = "update";
            formIdBahan.value = id;
            formIdMapel.value = idmapel;
            formJudul.value = judul;
            formDeskripsi.value = deskripsi;
            formFilePdf.required = false;

            modalTitle.innerHTML = '<i class="bi bi-pencil-square me-2"></i> Edit Materi';
            modalSub.innerText = "Perbarui materi pembelajaran Anda.";

            // Handle PDF indicator
            if (file && file.trim() !== "") {
                currentFileName.innerText = file;
                editFileIndicator.style.display = "block";
            } else {
                editFileIndicator.style.display = "none";
            }

            // Check the color blob corresponding to active color
            document.querySelectorAll("input[name='warna_bg']").forEach(radio => {
                radio.checked = (radio.value === warna);
            });

            // Open Modal
            modalEl.classList.add("is-open");
            modalEl.setAttribute("aria-hidden", "false");
            document.body.classList.add("modal-open-dashboard");
        });
    });

    // 3. DYNAMIC BOARD FILTER BY TAGS
    const filterTags = document.querySelectorAll(".filter-tag");
    const cards = document.querySelectorAll(".padlet-card");

    filterTags.forEach(tag => {
        tag.addEventListener("click", function() {
            // Toggle active tag layout
            filterTags.forEach(t => t.classList.remove("active"));
            this.classList.add("active");

            const filterVal = this.getAttribute("data-filter");

            cards.forEach(card => {
                const cardKelas = card.getAttribute("data-kelas");
                if (filterVal === 'all' || cardKelas === filterVal) {
                    card.style.display = "flex";
                } else {
                    card.style.display = "none";
                }
            });
        });
    });

    // 4. BOARD LIVE SEARCH FILTER
    const searchInput = document.getElementById("boardSearch");
    if (searchInput) {
        searchInput.addEventListener("input", function() {
            const query = this.value.toLowerCase().trim();
            const activeTag = document.querySelector(".filter-tag.active");
            const activeFilter = activeTag ? activeTag.getAttribute("data-filter") : 'all';

            cards.forEach(card => {
                const content = card.getAttribute("data-search-content");
                const cardKelas = card.getAttribute("data-kelas");

                // Check both class filter and search query matches
                const matchesFilter = (activeFilter === 'all' || cardKelas === activeFilter);
                const matchesSearch = content.includes(query);

                if (matchesFilter && matchesSearch) {
                    card.style.display = "flex";
                } else {
                    card.style.display = "none";
                }
            });
        });
    }
});
</script>
<?php include __DIR__ . '/guru_common_footer.php'; ?>
</body>
</html>
