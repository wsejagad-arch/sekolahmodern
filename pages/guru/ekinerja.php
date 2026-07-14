<?php
// pages/guru/ekinerja.php
require_once __DIR__ . '/../../auth_helper.php';
require_once __DIR__ . '/../../bootstrap.php';
// $conn is globally defined in bootstrap.php -> koneksi.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['no_induk']) || (int)($_SESSION['hak_akses'] ?? 0) !== 2) {
    header('Location: ../../login.php?haruslogin');
    exit;
}

$nip = $_SESSION['no_induk'];
$nipEsc = mysqli_real_escape_string($conn, $nip);
$sqlGuru = mysqli_query($conn, "SELECT * FROM tbl_guru WHERE no_induk='$nipEsc'");
$dataGuru = mysqli_fetch_array($sqlGuru);
$namaGuru = $dataGuru['nama_guru'] ?? $_SESSION['nama'] ?? 'Guru';
$lembaga = data_lembaga();

// --- SELF HEALING DATABASE SCHEMAS ---
@mysqli_query($conn, "CREATE TABLE IF NOT EXISTS tbl_sertifikat (
    id INT AUTO_INCREMENT PRIMARY KEY,
    no_induk_guru VARCHAR(50) NOT NULL,
    folder_name VARCHAR(100) NOT NULL DEFAULT 'Umum',
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

@mysqli_query($conn, "CREATE TABLE IF NOT EXISTS tbl_share_links (
    id INT AUTO_INCREMENT PRIMARY KEY,
    token VARCHAR(64) UNIQUE NOT NULL,
    no_induk_guru VARCHAR(50) NOT NULL,
    tipe_sumber VARCHAR(50) NOT NULL, 
    sumber_id VARCHAR(100) NOT NULL,   
    sumber_label VARCHAR(255) DEFAULT '',
    data_json LONGTEXT,               
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

@mysqli_query($conn, "CREATE TABLE IF NOT EXISTS tbl_ekinerja_dokumen (
    id INT AUTO_INCREMENT PRIMARY KEY,
    no_induk_guru VARCHAR(50) NOT NULL,
    tipe_dokumen VARCHAR(50) NOT NULL,
    sumber_id VARCHAR(100) NOT NULL,
    nama_file VARCHAR(255) NOT NULL,
    label VARCHAR(255) NOT NULL,
    data_json LONGTEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// Create upload directory if not exists
$uploadDir = __DIR__ . '/../../uploads/sertifikat/' . $nip;
if (!is_dir($uploadDir)) {
    @mkdir($uploadDir, 0777, true);
}
// Create supervisi upload directory
$supervisiDir = __DIR__ . '/../../uploads/supervisi/' . $nip;
if (!is_dir($supervisiDir)) {
    @mkdir($supervisiDir, 0777, true);
}

// Get Gemini API Key
$geminiApiKey = '';
$qSetting = mysqli_query($conn, "SELECT gemini_api_key FROM tbl_setting WHERE id=1 LIMIT 1");
if ($qSetting && mysqli_num_rows($qSetting) > 0) {
    $rowSetting = mysqli_fetch_assoc($qSetting);
    $geminiApiKey = trim((string)$rowSetting['gemini_api_key'] ?? '');
}
if ($geminiApiKey === '') {
    $geminiApiKey = 'AIzaSyC9zh6FHEnbqrW1MSlO4fVnSdu2L8SjSE8';
}

// --- AJAX ACTION HANDLER ---
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    $action = $_GET['ajax'];
    
    if ($action === 'create_share') {
        $tipe = mysqli_real_escape_string($conn, $_POST['tipe'] ?? '');
        $sumber_id = mysqli_real_escape_string($conn, $_POST['sumber_id'] ?? '');
        $label = mysqli_real_escape_string($conn, $_POST['label'] ?? '');
        $data_json = mysqli_real_escape_string($conn, $_POST['data_json'] ?? '');
        
        $token = bin2hex(random_bytes(16));
        $q = mysqli_query($conn, "INSERT INTO tbl_share_links (token, no_induk_guru, tipe_sumber, sumber_id, sumber_label, data_json) 
                                  VALUES ('$token', '$nipEsc', '$tipe', '$sumber_id', '$label', '$data_json')");
        if ($q) {
            echo json_encode(['status' => 'success', 'token' => $token]);
        } else {
            echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
        }
        exit;
    }
    
    if ($action === 'get_share_url') {
        $tipe = mysqli_real_escape_string($conn, $_GET['tipe'] ?? '');
        $sumber_id = mysqli_real_escape_string($conn, $_GET['sumber_id'] ?? '');
        
        $q = mysqli_query($conn, "SELECT token FROM tbl_share_links WHERE no_induk_guru='$nipEsc' AND tipe_sumber='$tipe' AND sumber_id='$sumber_id' ORDER BY id DESC LIMIT 1");
        if ($q && mysqli_num_rows($q) > 0) {
            $row = mysqli_fetch_assoc($q);
            echo json_encode(['status' => 'success', 'token' => $row['token']]);
        } else {
            echo json_encode(['status' => 'not_found']);
        }
        exit;
    }
    if ($action === 'generate_jurnal') {
        $bulan = mysqli_real_escape_string($conn, $_POST['bulan'] ?? '');
        $tahun = mysqli_real_escape_string($conn, $_POST['tahun'] ?? '');
        $label = mysqli_real_escape_string($conn, $_POST['label'] ?? '');
        
        if(!$bulan || !$tahun) {
            echo json_encode(['status'=>'error', 'message'=>'Data tidak lengkap']);
            exit;
        }

        $sumber_id = "$tahun-$bulan";
        $nama_file = "Jurnal Mengajar - " . $namaGuru . " - " . $label . " " . $tahun . ".pdf";
        
        // Query tbl_materi
        $qJurnal = mysqli_query($conn, "SELECT * FROM tbl_materi WHERE no_induk='$nipEsc' AND MONTH(tanggal) = '$bulan' AND YEAR(tanggal) = '$tahun' ORDER BY tanggal ASC");
        
        $nmSekolah = htmlspecialchars($lembaga['nmsekolah'] ?? 'SMA NEGERI 1 SUMBER');
        $alSekolah = htmlspecialchars($lembaga['alamat'] ?? 'Jl. Raya Sumber No. 123, Sumber, Probolinggo');
        
        $html = '<div class="print-page" style="display: block;">
            <div class="kop-surat" style="display: block;">
                <h2>REKAPITULASI JURNAL HARIAN MENGAJAR</h2>
                <h2>' . $nmSekolah . '</h2>
                <p>' . $alSekolah . '</p>
            </div>
            <div class="mb-4">
                <table style="border:none !important; width:100%;">
                    <tr style="border:none !important;"><td style="border:none !important; width:150px;">Nama Guru</td><td style="border:none !important; width:10px;">:</td><td style="border:none !important;"><strong>' . htmlspecialchars($namaGuru) . '</strong></td></tr>
                    <tr style="border:none !important;"><td style="border:none !important;">NIP/No Induk</td><td style="border:none !important;">:</td><td style="border:none !important;">' . htmlspecialchars($nip) . '</td></tr>
                    <tr style="border:none !important;"><td style="border:none !important;">Periode Bulan</td><td style="border:none !important;">:</td><td style="border:none !important;">' . htmlspecialchars($label . ' ' . $tahun) . '</td></tr>
                </table>
            </div>
            <table class="table table-bordered">
                <thead>
                    <tr><th>No</th><th>Tanggal</th><th>Kelas</th><th>Mata Pelajaran</th><th>Materi Pokok</th><th>Keterangan/Kegiatan</th></tr>
                </thead>
                <tbody>';
        
        $no = 1;
        if ($qJurnal && mysqli_num_rows($qJurnal) > 0) {
            while ($rj = mysqli_fetch_assoc($qJurnal)) {
                $tgl = date('d/m/Y', strtotime($rj['tanggal']));
                $kls = htmlspecialchars($rj['kelas'] ?? '');
                $mpl = htmlspecialchars($rj['nama_mapel'] ?? '');
                $mat = htmlspecialchars($rj['materi'] ?? '');
                $ket = htmlspecialchars($rj['kegiatan'] ?? '') . ' ' . htmlspecialchars($rj['keterangan'] ?? '');
                $html .= '<tr><td>'.$no.'</td><td>'.$tgl.'</td><td>'.$kls.'</td><td>'.$mpl.'</td><td>'.$mat.'</td><td>'.$ket.'</td></tr>';
                $no++;
            }
        } else {
            $html .= '<tr><td colspan="6" class="text-center">Belum ada data jurnal pada bulan ini.</td></tr>';
        }
        
        $html .= '</tbody>
            </table>
            <div class="signature-block">
                <div class="signature-col">
                    <p>Mengetahui,</p>
                    <p><strong>Kepala Sekolah</strong></p>
                    <br><br><br><br>
                    <p>_________________________</p>
                </div>
                <div class="signature-col">
                    <p>Sumber, ' . date('d') . ' ' . htmlspecialchars($label) . ' ' . date('Y') . '</p>
                    <p><strong>Guru Mata Pelajaran</strong></p>
                    <br><br><br>
                    <p><strong><u>' . htmlspecialchars($namaGuru) . '</u></strong></p>
                    <p>NIP. ' . htmlspecialchars($nip) . '</p>
                </div>
            </div>
        </div>';

        $data_json_db = mysqli_real_escape_string($conn, json_encode(['htmlContent' => $html]));

        mysqli_query($conn, "DELETE FROM tbl_ekinerja_dokumen WHERE no_induk_guru='$nipEsc' AND tipe_dokumen='jurnal' AND sumber_id='$sumber_id'");
        mysqli_query($conn, "DELETE FROM tbl_share_links WHERE no_induk_guru='$nipEsc' AND tipe_sumber='jurnal' AND sumber_id='$sumber_id'");
        
        $q = mysqli_query($conn, "INSERT INTO tbl_ekinerja_dokumen (no_induk_guru, tipe_dokumen, sumber_id, nama_file, label, data_json) 
                             VALUES ('$nipEsc', 'jurnal', '$sumber_id', '$nama_file', '$label', '$data_json_db')");
                             
        if ($q) {
            $token = bin2hex(random_bytes(16));
            mysqli_query($conn, "INSERT INTO tbl_share_links (token, no_induk_guru, tipe_sumber, sumber_id, sumber_label, data_json) 
                                 VALUES ('$token', '$nipEsc', 'jurnal', '$sumber_id', '$label $tahun', '$data_json_db')");
            echo json_encode(['status'=>'success']);
        } else {
            echo json_encode(['status'=>'error', 'message'=>mysqli_error($conn)]);
        }
        exit;
    }

        if ($action === 'perangkat_load_drive') {
        $folders = [];
        $qFolder = mysqli_query($conn, "SELECT id, nama_file FROM tbl_ekinerja_dokumen WHERE no_induk_guru='$nipEsc' AND tipe_dokumen='perangkat_folder' ORDER BY nama_file ASC");
        while ($rF = mysqli_fetch_assoc($qFolder)) {
            $folderId = $rF['id'];
            $files = [];
            $qFile = mysqli_query($conn, "SELECT id, tipe_dokumen, nama_file, label, data_json, created_at FROM tbl_ekinerja_dokumen WHERE no_induk_guru='$nipEsc' AND sumber_id='$folderId' AND tipe_dokumen IN ('modul', 'atp', 'perangkat_file') ORDER BY created_at DESC");
            while ($rFile = mysqli_fetch_assoc($qFile)) {
                $files[] = $rFile;
            }
            $folders[] = [
                'id' => $folderId,
                'nama' => $rF['nama_file'],
                'files' => $files
            ];
        }
        echo json_encode(['status'=>'success', 'folders'=>$folders]);
        exit;
    }

    if ($action === 'perangkat_create_folder') {
        $nama = trim($_POST['nama_folder'] ?? '');
        if (empty($nama)) {
            echo json_encode(['status'=>'error', 'message'=>'Nama folder tidak boleh kosong']);
            exit;
        }
        $namaEsc = mysqli_real_escape_string($conn, $nama);
        // Check exist
        $qC = mysqli_query($conn, "SELECT id FROM tbl_ekinerja_dokumen WHERE no_induk_guru='$nipEsc' AND tipe_dokumen='perangkat_folder' AND nama_file='$namaEsc'");
        if (mysqli_num_rows($qC) > 0) {
            echo json_encode(['status'=>'error', 'message'=>'Folder sudah ada']);
            exit;
        }
        $q = mysqli_query($conn, "INSERT INTO tbl_ekinerja_dokumen (no_induk_guru, tipe_dokumen, sumber_id, nama_file, label) VALUES ('$nipEsc', 'perangkat_folder', 'root', '$namaEsc', 'Folder Kelas')");
        if ($q) {
            echo json_encode(['status'=>'success']);
        } else {
            echo json_encode(['status'=>'error', 'message'=>mysqli_error($conn)]);
        }
        exit;
    }

    if ($action === 'perangkat_delete_folder') {
        $id = (int)($_POST['id'] ?? 0);
        mysqli_query($conn, "DELETE FROM tbl_ekinerja_dokumen WHERE id=$id AND no_induk_guru='$nipEsc' AND tipe_dokumen='perangkat_folder'");
        mysqli_query($conn, "DELETE FROM tbl_ekinerja_dokumen WHERE sumber_id='$id' AND no_induk_guru='$nipEsc'");
        echo json_encode(['status'=>'success']);
        exit;
    }

        if ($action === 'perangkat_rename_item') {
        $id = (int)($_POST['id'] ?? 0);
        $nama_baru = mysqli_real_escape_string($conn, trim($_POST['nama_baru'] ?? ''));
        if ($id > 0 && !empty($nama_baru)) {
            $q = mysqli_query($conn, "UPDATE tbl_ekinerja_dokumen SET nama_file='$nama_baru' WHERE id=$id AND no_induk_guru='$nipEsc'");
            if ($q) {
                echo json_encode(['status'=>'success']);
            } else {
                echo json_encode(['status'=>'error', 'message'=>mysqli_error($conn)]);
            }
        } else {
            echo json_encode(['status'=>'error', 'message'=>'ID atau nama tidak valid']);
        }
        exit;
    }

    if ($action === 'perangkat_copy_item') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            // Get original item
            $qGet = mysqli_query($conn, "SELECT * FROM tbl_ekinerja_dokumen WHERE id=$id AND no_induk_guru='$nipEsc'");
            if ($qGet && mysqli_num_rows($qGet) > 0) {
                $row = mysqli_fetch_assoc($qGet);
                $tipe_dokumen = mysqli_real_escape_string($conn, $row['tipe_dokumen']);
                $sumber_id = mysqli_real_escape_string($conn, $row['sumber_id']);
                $nama_file = mysqli_real_escape_string($conn, "Copy of " . $row['nama_file']);
                $label = mysqli_real_escape_string($conn, $row['label']);
                $data_json = mysqli_real_escape_string($conn, $row['data_json']);
                
                $qInsert = mysqli_query($conn, "INSERT INTO tbl_ekinerja_dokumen (no_induk_guru, tipe_dokumen, sumber_id, nama_file, label, data_json) VALUES ('$nipEsc', '$tipe_dokumen', '$sumber_id', '$nama_file', '$label', '$data_json')");
                
                if ($qInsert) {
                    $newId = mysqli_insert_id($conn);
                    // if it's a folder, also copy its files
                    if ($tipe_dokumen === 'perangkat_folder') {
                        $qFiles = mysqli_query($conn, "SELECT * FROM tbl_ekinerja_dokumen WHERE sumber_id='$id' AND no_induk_guru='$nipEsc'");
                        while($rf = mysqli_fetch_assoc($qFiles)) {
                            $t_dok = mysqli_real_escape_string($conn, $rf['tipe_dokumen']);
                            $n_file = mysqli_real_escape_string($conn, $rf['nama_file']);
                            $lbl = mysqli_real_escape_string($conn, $rf['label']);
                            $dj = mysqli_real_escape_string($conn, $rf['data_json']);
                            mysqli_query($conn, "INSERT INTO tbl_ekinerja_dokumen (no_induk_guru, tipe_dokumen, sumber_id, nama_file, label, data_json) VALUES ('$nipEsc', '$t_dok', '$newId', '$n_file', '$lbl', '$dj')");
                        }
                    }
                    echo json_encode(['status'=>'success']);
                } else {
                    echo json_encode(['status'=>'error', 'message'=>mysqli_error($conn)]);
                }
            } else {
                echo json_encode(['status'=>'error', 'message'=>'Item tidak ditemukan']);
            }
        }
        exit;
    }

    
    if ($action === 'supervisi_create_folder') {
        $nama = mysqli_real_escape_string($conn, trim($_POST['nama_folder'] ?? ''));
        if ($nama) {
            $q = mysqli_query($conn, "INSERT INTO tbl_ekinerja_dokumen (no_induk_guru, tipe_dokumen, sumber_id, nama_file, label) VALUES ('$nipEsc', 'supervisi_folder', 'root', '$nama', 'Folder Supervisi')");
            echo json_encode(['status'=>$q?'success':'error']);
        }
        exit;
    }

    if ($action === 'supervisi_delete_item') {
        $id = (int)($_POST['id'] ?? 0);
        $tipe = $_POST['tipe'] ?? '';
        mysqli_query($conn, "DELETE FROM tbl_ekinerja_dokumen WHERE id=$id AND no_induk_guru='$nipEsc'");
        if ($tipe === 'supervisi_folder') {
            mysqli_query($conn, "DELETE FROM tbl_ekinerja_dokumen WHERE sumber_id='$id' AND no_induk_guru='$nipEsc'");
        }
        echo json_encode(['status'=>'success']);
        exit;
    }

    if ($action === 'supervisi_load_drive') {
        $folderId = $_GET['folder_id'] ?? 'root';
        $res = [];
        if ($folderId === 'root') {
            $q = mysqli_query($conn, "SELECT id, nama_file, created_at FROM tbl_ekinerja_dokumen WHERE no_induk_guru='$nipEsc' AND tipe_dokumen='supervisi_folder' ORDER BY created_at DESC");
            while ($row = mysqli_fetch_assoc($q)) {
                $res[] = ['tipe'=>'folder', 'id'=>$row['id'], 'nama'=>$row['nama_file']];
            }
        } else {
            $q = mysqli_query($conn, "SELECT id, nama_file, data_json, tipe_dokumen, created_at FROM tbl_ekinerja_dokumen WHERE no_induk_guru='$nipEsc' AND sumber_id='$folderId' AND tipe_dokumen IN ('supervisi_file', 'supervisi_report') ORDER BY created_at DESC");
            while ($row = mysqli_fetch_assoc($q)) {
                $path = '';
                if($row['tipe_dokumen'] === 'supervisi_file' && !empty($row['data_json'])) {
                    $json = json_decode($row['data_json'], true);
                    $path = $json['file_path'] ?? '';
                }
                $res[] = ['tipe'=>'file', 'id'=>$row['id'], 'nama'=>$row['nama_file'], 'path'=>$path, 'tipe_dok'=>$row['tipe_dokumen']];
            }
        }
        echo json_encode($res);
        exit;
    }


    if ($action === 'sertifikat_rename_folder') {
        $old_name = mysqli_real_escape_string($conn, $_POST['old_name'] ?? '');
        $new_name = mysqli_real_escape_string($conn, $_POST['new_name'] ?? '');
        if ($old_name && $new_name) {
            mysqli_query($conn, "UPDATE tbl_sertifikat SET folder_name='$new_name' WHERE folder_name='$old_name' AND no_induk_guru='$nipEsc'");
            mysqli_query($conn, "UPDATE tbl_share_links SET sumber_id='$new_name' WHERE sumber_id='$old_name' AND tipe_sumber='sertifikat_folder' AND no_induk_guru='$nipEsc'");
            echo json_encode(['status'=>'success']);
        }
        exit;
    }

    if ($action === 'sertifikat_copy_folder') {
        $folder_name = mysqli_real_escape_string($conn, $_POST['folder_name'] ?? '');
        $new_folder_name = mysqli_real_escape_string($conn, "Copy of " . ($_POST['folder_name'] ?? ''));
        if ($folder_name) {
            $qGet = mysqli_query($conn, "SELECT * FROM tbl_sertifikat WHERE folder_name='$folder_name' AND no_induk_guru='$nipEsc'");
            while ($row = mysqli_fetch_assoc($qGet)) {
                $file_name = mysqli_real_escape_string($conn, $row['file_name']);
                $file_path = mysqli_real_escape_string($conn, $row['file_path']);
                // Copy the actual physical file to avoid sharing same path if one is deleted
                $fullOrig = __DIR__ . '/../../' . $row['file_path'];
                if(is_file($fullOrig)) {
                    $ext = pathinfo($fullOrig, PATHINFO_EXTENSION);
                    $cleanName = pathinfo($fullOrig, PATHINFO_FILENAME) . "_copy_" . time() . "." . $ext;
                    $uploadDir = __DIR__ . '/../../uploads/sertifikat/' . $nip;
                    if(!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                    $destPath = $uploadDir . '/' . $cleanName;
                    $dbPath = 'uploads/sertifikat/' . $nip . '/' . $cleanName;
                    if(copy($fullOrig, $destPath)) {
                        mysqli_query($conn, "INSERT INTO tbl_sertifikat (no_induk_guru, folder_name, file_name, file_path) VALUES ('$nipEsc', '$new_folder_name', '$file_name', '$dbPath')");
                    }
                } else if ($file_name === '.folder') {
                    // Empty folder placeholder
                    mysqli_query($conn, "INSERT INTO tbl_sertifikat (no_induk_guru, folder_name, file_name, file_path) VALUES ('$nipEsc', '$new_folder_name', '.folder', '')");
                }
            }
            echo json_encode(['status'=>'success']);
        }
        exit;
    }

    if ($action === 'sertifikat_delete_folder') {
        $folder_name = mysqli_real_escape_string($conn, $_POST['folder_name'] ?? '');
        if ($folder_name) {
            $qGet = mysqli_query($conn, "SELECT * FROM tbl_sertifikat WHERE folder_name='$folder_name' AND no_induk_guru='$nipEsc'");
            while ($row = mysqli_fetch_assoc($qGet)) {
                if ($row['file_path']) {
                    $fullOrig = __DIR__ . '/../../' . $row['file_path'];
                    if (is_file($fullOrig)) @unlink($fullOrig);
                }
            }
            mysqli_query($conn, "DELETE FROM tbl_sertifikat WHERE folder_name='$folder_name' AND no_induk_guru='$nipEsc'");
            mysqli_query($conn, "DELETE FROM tbl_share_links WHERE sumber_id='$folder_name' AND tipe_sumber='sertifikat_folder' AND no_induk_guru='$nipEsc'");
            echo json_encode(['status'=>'success']);
        }
        exit;
    }

if ($action === 'perangkat_delete_file') {
        $id = (int)($_POST['id'] ?? 0);
        mysqli_query($conn, "DELETE FROM tbl_ekinerja_dokumen WHERE id=$id AND no_induk_guru='$nipEsc'");
        echo json_encode(['status'=>'success']);
        exit;
    }

    if ($action === 'perangkat_save_ai') {
        $kelas = trim($_POST['kelas'] ?? '');
        $tipe = $_POST['tipe'] ?? ''; // modul or atp
        $label = $_POST['label'] ?? '';
        $htmlContent = $_POST['html'] ?? '';
        
        $kelasEsc = mysqli_real_escape_string($conn, $kelas);
        
        // Ensure folder exists
        $qFolder = mysqli_query($conn, "SELECT id FROM tbl_ekinerja_dokumen WHERE no_induk_guru='$nipEsc' AND tipe_dokumen='perangkat_folder' AND nama_file='Kelas $kelasEsc'");
        if (mysqli_num_rows($qFolder) == 0) {
            mysqli_query($conn, "INSERT INTO tbl_ekinerja_dokumen (no_induk_guru, tipe_dokumen, sumber_id, nama_file, label) VALUES ('$nipEsc', 'perangkat_folder', 'root', 'Kelas $kelasEsc', 'Folder Kelas')");
            $folderId = mysqli_insert_id($conn);
        } else {
            $row = mysqli_fetch_assoc($qFolder);
            $folderId = $row['id'];
        }
        
        $dataJson = mysqli_real_escape_string($conn, json_encode(['htmlContent' => $htmlContent]));
        $tipeEsc = mysqli_real_escape_string($conn, $tipe);
        $labelEsc = mysqli_real_escape_string($conn, $label);
        $namaFile = ($tipe === 'modul' ? 'Modul Ajar' : 'ATP') . " - " . $labelEsc;
        
        $q = mysqli_query($conn, "INSERT INTO tbl_ekinerja_dokumen (no_induk_guru, tipe_dokumen, sumber_id, nama_file, label, data_json) VALUES ('$nipEsc', '$tipeEsc', '$folderId', '$namaFile', '$labelEsc', '$dataJson')");
        if ($q) {
            echo json_encode(['status'=>'success']);
        } else {
            echo json_encode(['status'=>'error', 'message'=>mysqli_error($conn)]);
        }
        exit;
    }


    if ($action === 'get_siswa_kelas') {
        $kelas = mysqli_real_escape_string($conn, $_GET['kelas'] ?? '');
        $data = [];
        $q = mysqli_query($conn, "SELECT no_induk, nama_siswa FROM tbl_siswa WHERE kelas='$kelas' ORDER BY nama_siswa ASC");
        if($q){
            while ($r = mysqli_fetch_assoc($q)) {
                $data[] = $r;
            }
        }
        echo json_encode(['status'=>'success', 'data'=>$data]);
        exit;
    }

    exit;
}

// --- POST SUBMIT HANDLERS (Sertifikat Upload / Hapus / Dokumen Generate) ---
$msg = '';
$msgType = 'success';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action_sertifikat'])) {
        $act = $_POST['action_sertifikat'];
        
        if ($act === 'upload') {
            $folderName = trim($_POST['folder_name'] ?? 'Umum');
            if ($folderName === 'other') {
                $folderName = trim($_POST['other_folder_name'] ?? 'Umum');
            }
            if (empty($folderName)) $folderName = 'Umum';
            $folderNameEsc = mysqli_real_escape_string($conn, $folderName);
            
            if (isset($_FILES['file_sertifikat']) && $_FILES['file_sertifikat']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['file_sertifikat'];
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $allowed = ['pdf', 'jpg', 'jpeg', 'png'];
                
                if (in_array($ext, $allowed, true)) {
                    $cleanName = preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($file['name'], PATHINFO_FILENAME)) . '.' . $ext;
                    $destPath = $uploadDir . '/' . $cleanName;
                    $dbPath = 'uploads/sertifikat/' . $nip . '/' . $cleanName;
                    
                    if (move_uploaded_file($file['tmp_name'], $destPath)) {
                        mysqli_query($conn, "INSERT INTO tbl_sertifikat (no_induk_guru, folder_name, file_name, file_path) 
                                             VALUES ('$nipEsc', '$folderNameEsc', '$cleanName', '$dbPath')");
                        $msg = "Sertifikat berhasil diunggah!";
                        $msgType = "success";
                    } else {
                        $msg = "Gagal memindahkan file.";
                        $msgType = "danger";
                    }
                } else {
                    $msg = "Ekstensi file tidak diizinkan. Hanya PDF dan Gambar.";
                    $msgType = "danger";
                }
            } else {
                $msg = "Mohon pilih file yang valid.";
                $msgType = "danger";
            }
        }
        
        if ($act === 'hapus') {
            $id = (int)$_POST['id_sertifikat'];
            $q = mysqli_query($conn, "SELECT * FROM tbl_sertifikat WHERE id=$id AND no_induk_guru='$nipEsc'");
            if ($q && mysqli_num_rows($q) > 0) {
                $row = mysqli_fetch_assoc($q);
                $fullPath = __DIR__ . '/../../' . $row['file_path'];
                if (is_file($fullPath)) {
                    @unlink($fullPath);
                }
                mysqli_query($conn, "DELETE FROM tbl_sertifikat WHERE id=$id");
                $msg = "Sertifikat berhasil dihapus.";
                $msgType = "success";
            }
        }
        
                if ($act === 'supervisi_upload') {
            $folderId = trim($_POST['folder_id'] ?? '');
            if (isset($_FILES['file_supervisi']) && $_FILES['file_supervisi']['error'] === UPLOAD_ERR_OK && $folderId) {
                $file = $_FILES['file_supervisi'];
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $allowed = ['pdf', 'jpg', 'jpeg', 'png'];
                if (in_array($ext, $allowed, true)) {
                    $cleanName = preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($file['name'], PATHINFO_FILENAME)) . '.' . $ext;
                    $uploadDirSup = __DIR__ . '/../../uploads/supervisi/' . $nip;
                    if(!is_dir($uploadDirSup)) mkdir($uploadDirSup, 0777, true);
                    $destPath = $uploadDirSup . '/' . $cleanName;
                    $dbPath = 'uploads/supervisi/' . $nip . '/' . $cleanName;
                    
                    if (move_uploaded_file($file['tmp_name'], $destPath)) {
                        $json = json_encode(['file_path' => $dbPath]);
                        $folderIdEsc = mysqli_real_escape_string($conn, $folderId);
                        $cleanNameEsc = mysqli_real_escape_string($conn, $cleanName);
                        mysqli_query($conn, "INSERT INTO tbl_ekinerja_dokumen (no_induk_guru, tipe_dokumen, sumber_id, nama_file, data_json) VALUES ('$nipEsc', 'supervisi_file', '$folderIdEsc', '$cleanNameEsc', '$json')");
                        $msg = "Laporan Supervisi berhasil diunggah!";
                        $msgType = "success";
                    } else {
                        $msg = "Gagal memindahkan file."; $msgType = "danger";
                    }
                } else { $msg = "Ekstensi tidak diizinkan."; $msgType = "danger"; }
            } else { $msg = "Mohon pilih file dan folder yang valid."; $msgType = "danger"; }
        }
if ($act === 'buat_folder') {
            $folderName = trim($_POST['nama_folder'] ?? '');
            if (!empty($folderName)) {
                $folderNameEsc = mysqli_real_escape_string($conn, $folderName);
                // We create a sample/dummy database record to register the folder name in SQL
                mysqli_query($conn, "INSERT INTO tbl_sertifikat (no_induk_guru, folder_name, file_name, file_path) 
                                     VALUES ('$nipEsc', '$folderNameEsc', '.folder', '')");
                $msg = "Folder berhasil dibuat.";
                $msgType = "success";
            }
        }
    }
    
    if (isset($_POST['action_dokumen'])) {
        $act = $_POST['action_dokumen'];
        
        if ($act === 'hapus_dokumen') {
            $id = (int)$_POST['id_dokumen'];
            $q = mysqli_query($conn, "SELECT * FROM tbl_ekinerja_dokumen WHERE id=$id AND no_induk_guru='$nipEsc'");
            if ($q && mysqli_num_rows($q) > 0) {
                $row = mysqli_fetch_assoc($q);
                $s_id = mysqli_real_escape_string($conn, $row['sumber_id']);
                $tipe = mysqli_real_escape_string($conn, $row['tipe_dokumen']);
                mysqli_query($conn, "DELETE FROM tbl_ekinerja_dokumen WHERE id=$id");
                mysqli_query($conn, "DELETE FROM tbl_share_links WHERE no_induk_guru='$nipEsc' AND tipe_sumber='$tipe' AND sumber_id='$s_id'");
                $msg = "Dokumen berhasil dihapus.";
                $msgType = "success";
            }
        }
    }
    
    if (isset($_POST['action_perangkat'])) {
        $act = $_POST['action_perangkat'];
        if ($act === 'perangkat_upload') {
            $folderId = trim($_POST['folder_id'] ?? '');
            if (isset($_FILES['file_perangkat']) && $_FILES['file_perangkat']['error'] === UPLOAD_ERR_OK && $folderId) {
                $file = $_FILES['file_perangkat'];
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $allowed = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'jpg', 'jpeg', 'png'];
                if (in_array($ext, $allowed, true)) {
                    $cleanName = preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($file['name'], PATHINFO_FILENAME)) . '.' . $ext;
                    $uploadDirPerangkat = __DIR__ . '/../../uploads/perangkat/' . $nip;
                    if(!is_dir($uploadDirPerangkat)) @mkdir($uploadDirPerangkat, 0777, true);
                    $destPath = $uploadDirPerangkat . '/' . $cleanName;
                    $dbPath = 'uploads/perangkat/' . $nip . '/' . $cleanName;
                    
                    if (move_uploaded_file($file['tmp_name'], $destPath)) {
                        $json = json_encode(['file_path' => $dbPath]);
                        $folderIdEsc = mysqli_real_escape_string($conn, $folderId);
                        $cleanNameEsc = mysqli_real_escape_string($conn, $cleanName);
                        mysqli_query($conn, "INSERT INTO tbl_ekinerja_dokumen (no_induk_guru, tipe_dokumen, sumber_id, nama_file, data_json) VALUES ('$nipEsc', 'perangkat_file', '$folderIdEsc', '$cleanNameEsc', '$json')");
                        $msg = "File Perangkat berhasil diunggah!";
                        $msgType = "success";
                    } else {
                        $msg = "Gagal memindahkan file."; $msgType = "danger";
                    }
                } else { $msg = "Ekstensi tidak diizinkan. Gunakan PDF/Word/Excel/PPT/Gambar."; $msgType = "danger"; }
            } else { $msg = "Mohon pilih file dan folder yang valid."; $msgType = "danger"; }
        }
    }
}

// --- DATA PREPARATION ---
// Wali Kelas Check
$waliKelasList = [];
$qWali = mysqli_query($conn, "SELECT kelas FROM tbl_kelas WHERE nip_wali='$nipEsc' AND kelas <> ''");
while ($w = mysqli_fetch_assoc($qWali)) {
    $waliKelasList[] = $w['kelas'];
}
$isWali = !empty($waliKelasList);
$kelasWali = $isWali ? $waliKelasList[0] : '';
$kelasWaliEsc = mysqli_real_escape_string($conn, $kelasWali);

// List Kelas yang diampu untuk Perangkat Ajar & Daftar Nilai
$kelasAmpu = [];
$qKls = mysqli_query($conn, "SELECT DISTINCT kelas FROM tbl_mapel_ampu WHERE no_induk='$nipEsc' AND kelas <> '' ORDER BY kelas ASC");
while ($r = mysqli_fetch_assoc($qKls)) {
    $kelasAmpu[] = $r['kelas'];
}

// Fetch certificates
$sertifikatList = [];
$folders = ['Umum'];
$qCert = mysqli_query($conn, "SELECT * FROM tbl_sertifikat WHERE no_induk_guru='$nipEsc'");
while ($c = mysqli_fetch_assoc($qCert)) {
    $sertifikatList[] = $c;
    if (!in_array($c['folder_name'], $folders, true)) {
        $folders[] = $c['folder_name'];
    }
}

// Extracurricular Check
$ekskulList = [];
$qEks = mysqli_query($conn, "SELECT * FROM tbl_ekskul ORDER BY nama_ekskul ASC");
while ($e = mysqli_fetch_assoc($qEks)) {
    $ekskulList[] = $e;
}

// Fetch generated documents
$generatedDocs = [];
$qDocs = mysqli_query($conn, "SELECT * FROM tbl_ekinerja_dokumen WHERE no_induk_guru='$nipEsc'");
while ($qDocs && ($d = mysqli_fetch_assoc($qDocs))) {
    $generatedDocs[$d['tipe_dokumen']][$d['sumber_id']] = $d;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Kinerja Guru - SIMANIS</title>
    <!-- Include Bootstrap 5 & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    
    <link rel="stylesheet" href="css/guru-desktop.css?v=<?= time() ?>">
    <style>
        :root {
            --bg: #ebf1f6;
            --primary: #0f766e;
            --primary-light: #14b8a6;
            --text-dark: #0f172a;
            --border: #e2e8f0;
        }
        body {
            font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
            background-color: var(--bg);
            color: var(--text-dark);
        }
        .ekinerja-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 24px 15px 80px;
        }
        .header-card {
            background: linear-gradient(135deg, var(--primary) 0%, #115e59 100%);
            border-radius: 20px;
            padding: 30px;
            color: white;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(15, 118, 110, 0.15);
        }
        .nav-tabs-custom {
            border: none;
            display: flex;
            gap: 8px;
            margin-bottom: 24px;
            overflow-x: auto;
            padding-bottom: 8px;
        }
        .nav-tabs-custom .nav-link {
            border: 1px solid var(--border);
            border-radius: 12px;
            color: #64748b;
            font-weight: 600;
            padding: 12px 20px;
            background: white;
            white-space: nowrap;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
        }
        .nav-tabs-custom .nav-link:hover {
            background: #f1f5f9;
            color: var(--primary);
        }
        .nav-tabs-custom .nav-link.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
            box-shadow: 0 8px 16px rgba(15, 118, 110, 0.2);
        }
        .content-panel {
            background: white;
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 28px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.02);
            margin-bottom: 24px;
        }
        .folder-card {
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 18px;
            background: #f8fafc;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s ease;
            position: relative;
        }
        .folder-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.05);
            background: #fff;
            border-color: var(--primary-light);
        }
        .folder-card i.bi-folder-fill {
            font-size: 3rem;
            color: #eab308;
            margin-bottom: 10px;
        }
        .btn-custom {
            background-color: var(--primary);
            color: white;
            font-weight: 600;
            border-radius: 10px;
            padding: 10px 20px;
            transition: all 0.2s;
        }
        .btn-custom:hover {
            background-color: #115e59;
            color: white;
        }
        .btn-ai {
            background: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%);
            color: white;
            font-weight: 600;
            border-radius: 10px;
            border: none;
            padding: 10px 20px;
            box-shadow: 0 4px 15px rgba(139, 92, 246, 0.2);
        }
        .btn-ai:hover {
            background: linear-gradient(135deg, #7c3aed 0%, #5b21b6 100%);
            color: white;
        }
        
        /* Print Styles */
        @media print {
            body {
                background: white !important;
            }
            .sidebar-nav, .desktop-sidebar, .app-header, .no-print, .nav-tabs-custom, .header-card, .btn, form {
                display: none !important;
            }
            .ekinerja-container {
                padding: 0 !important;
                margin: 0 !important;
                max-width: 100% !important;
            }
            .content-panel {
                border: none !important;
                box-shadow: none !important;
                padding: 0 !important;
            }
            .print-page {
                display: block !important;
                page-break-after: always;
            }
            .kop-surat {
                text-align: center;
                border-bottom: 3px double #000;
                padding-bottom: 15px;
                margin-bottom: 25px;
            }
            .kop-surat h2 {
                margin: 0;
                font-weight: 800;
                font-size: 20px;
                text-transform: uppercase;
            }
            .kop-surat p {
                margin: 3px 0;
                font-size: 12px;
            }
            table {
                width: 100% !important;
                border-collapse: collapse !important;
                margin-top: 15px;
            }
            table th, table td {
                border: 1px solid #000 !important;
                padding: 6px 10px !important;
                font-size: 12px !important;
            }
            table th {
                background-color: #f1f5f9 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .signature-block {
                margin-top: 50px;
                display: flex;
                justify-content: space-between;
                page-break-inside: avoid;
            }
            .signature-col {
                text-align: center;
                width: 45%;
                font-size: 13px;
            }
        }
        .kop-surat { display: none; }
        .print-page { display: none; }
    </style>
</head>
<body>
<?php include 'guru_sidebar_shared.php'; ?>


<div class="app-shell" style="grid-template-columns: 1fr; padding-right: 24px;">
    <div class="desktop-center-column ekinerja-container">
        
        <!-- Welcome Banner -->
        <div class="welcome-banner-premium mb-4 no-print">
            <div class="banner-content">
                <div class="banner-text">
                    <h2 class="animate-fade-in" style="font-size:2.2rem;font-weight:800;margin-bottom:12px;letter-spacing:-0.5px;">E-Kinerja Guru 📑</h2>
                    <p class="banner-subtitle" style="font-size:1.05rem;opacity:0.9;">Kelola berkas perangkat pembelajaran, rekapitulasi, sertifikat pelatihan, dan administrasi kinerja AI.</p>
                </div>
                <div class="banner-actions">
                    <a href="../../home.php" class="btn-premium-secondary"><i class="bi bi-arrow-left"></i> Kembali</a>
                </div>
            </div>
            <div class="banner-shapes">
                <div class="shape shape-1"></div>
                <div class="shape shape-2"></div>
                <div class="shape shape-3"></div>
            </div>
        </div>
    
    <?php if (!empty($msg)): ?>
        <div class="alert alert-<?= $msgType ?> alert-dismissible fade show no-print" role="alert">
            <?= htmlspecialchars($msg) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Custom Folder/Tab Navigation -->
    <ul class="nav nav-tabs nav-tabs-custom no-print" id="ekinerjaTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="jurnal-tab" data-bs-toggle="tab" data-bs-target="#jurnal-pane" type="button" role="tab"><i class="bi bi-journal-check"></i> Jurnal Mengajar</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="perangkat-tab" data-bs-toggle="tab" data-bs-target="#perangkat-pane" type="button" role="tab"><i class="bi bi-file-earmark-code"></i> Perangkat Ajar</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="sertifikat-tab" data-bs-toggle="tab" data-bs-target="#sertifikat-pane" type="button" role="tab"><i class="bi bi-patch-check"></i> Sertifikat Pelatihan</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="wali-tab" data-bs-toggle="tab" data-bs-target="#wali-pane" type="button" role="tab"><i class="bi bi-shield-check"></i> Laporan Wali Kelas</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="extra-tab" data-bs-toggle="tab" data-bs-target="#extra-pane" type="button" role="tab"><i class="bi bi-puzzle"></i> Laporan Ekstra</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="supervisi-tab" data-bs-toggle="tab" data-bs-target="#supervisi-pane" type="button" role="tab"><i class="bi bi-eye"></i> Laporan Supervisi</button>
        </li>
    </ul>

    <!-- Tab Contents -->
    <div class="tab-content">
        
        <!-- TAB 1: JURNAL MENGAJAR -->
        <div class="tab-pane fade show active" id="jurnal-pane" role="tabpanel">
            <div class="content-panel no-print">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="fw-bold mb-0"><i class="bi bi-journal-check text-teal"></i> Repository Jurnal Mengajar Bulanan</h4>
                    <div class="d-flex gap-3 align-items-center">
                        <span class="text-muted small">Tahun Pelajaran: <strong><?= date('Y') ?></strong></span>
                        <button class="btn btn-sm btn-outline-primary" onclick="shareFolderJurnal('<?= date('Y') ?>')"><i class="bi bi-share"></i> Share Folder</button>
                    </div>
                </div>
                
                <div class="row g-3">
                    <?php 
                    $indonesianMonths = [
                        '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
                        '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
                        '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
                    ];
                    $tahunAktif = date('Y');
                    foreach ($indonesianMonths as $num => $name): 
                        $sumber_id = "$tahunAktif-$num";
                        $hasDoc = isset($generatedDocs['jurnal'][$sumber_id]);
                        if ($hasDoc):
                            $doc = $generatedDocs['jurnal'][$sumber_id];
                            $shareQ = mysqli_query($conn, "SELECT token FROM tbl_share_links WHERE no_induk_guru='$nipEsc' AND tipe_sumber='jurnal' AND sumber_id='$sumber_id' LIMIT 1");
                            $shareRow = mysqli_fetch_assoc($shareQ);
                            $token = $shareRow['token'] ?? '';
                    ?>
                        <div class="col-md-4">
                            <div class="card p-3 border rounded-3 position-relative" style="background:#fff; box-shadow: 0 4px 12px rgba(0,0,0,0.03);">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="fs-1 text-danger"><i class="bi bi-file-pdf-fill"></i></div>
                                    <div style="min-width: 0; flex: 1;">
                                        <h6 class="fw-bold mb-1 text-truncate" title="<?= htmlspecialchars($doc['nama_file']) ?>"><?= htmlspecialchars($name) ?>.pdf</h6>
                                        <p class="text-muted small mb-0">Generated: <?= date('d/m/Y', strtotime($doc['created_at'])) ?></p>
                                    </div>
                                </div>
                                <div class="d-flex gap-1 mt-3 pt-2 border-top justify-content-end">
                                    <a href="../../lihat_berkas.php?token=<?= $token ?>" target="_blank" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i> Buka</a>
                                    <button class="btn btn-sm btn-outline-secondary" onclick="copyShareLink('<?= $token ?>')"><i class="bi bi-share"></i> Copy Link</button>
                                    <form method="post" style="display:inline;" onsubmit="return confirm('Hapus file ini untuk generate ulang?');">
                                        <input type="hidden" name="action_dokumen" value="hapus_dokumen">
                                        <input type="hidden" name="id_dokumen" value="<?= $doc['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i> Hapus</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="col-md-4">
                            <div class="card p-3 border border-dashed rounded-3 text-center d-flex flex-column align-items-center justify-content-center" style="background:#f8fafc; border-style: dashed !important; min-height: 125px; border-width: 1.5px;">
                                <span class="text-muted small mb-2"><i class="bi bi-file-earmark-x"></i> <?= htmlspecialchars($name) ?> (Kosong)</span>
                                <button class="btn btn-sm btn-custom py-1 px-3" onclick="triggerGenerateJurnal('<?= $num ?>', '<?= $name ?>')"><i class="bi bi-plus-lg"></i> Generate</button>
                            </div>
                        </div>
                    <?php endif; ?>
                    <?php endforeach; ?>
                </div>

                <!-- Hidden Generate Form -->
                <form id="hiddenGenerateForm" method="post" class="d-none">
                    <input type="hidden" name="action_dokumen" value="generate_jurnal">
                    <input type="hidden" name="bulan" id="hidden_bulan">
                    <input type="hidden" name="tahun" id="hidden_tahun" value="<?= date('Y') ?>">
                    <input type="hidden" name="label" id="hidden_label">
                    <input type="hidden" name="data_json" id="hidden_data_json">
                </form>
            </div>
        </div>

        <!-- TAB 2: PERANGKAT AJAR -->
        <div class="tab-pane fade" id="perangkat-pane" role="tabpanel">
            <div class="content-panel no-print">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="fw-bold mb-0"><i class="bi bi-file-earmark-code text-teal"></i> Perangkat Pembelajaran</h4>
                    <button class="btn btn-sm btn-outline-primary" onclick="shareFolderUmum('perangkat', 'Repository Perangkat Ajar')"><i class="bi bi-share"></i> Share Keseluruhan</button>
                </div>
                <p class="text-muted">Kelola berkas Modul Ajar atau cetak Daftar Nilai siswa berdasarkan input nilai KBM guru.</p>
                
                <ul class="nav nav-pills mb-4" id="perangkatSubtabs" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active" id="sub-modul-tab" data-bs-toggle="pill" data-bs-target="#sub-modul" type="button"><i class="bi bi-journal-text"></i> Modul Ajar</button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" id="sub-nilai-tab" data-bs-toggle="pill" data-bs-target="#sub-nilai" type="button"><i class="bi bi-table"></i> Daftar Nilai</button>
                    </li>
                </ul>
                
                <div class="tab-content">
                                        <!-- Modul Ajar (Drive Style) -->
                    <div class="tab-pane fade show active" id="sub-modul">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="fw-bold mb-0">Repository Perangkat Ajar</h5>
                            <div class="d-flex gap-2">
                                <button class="btn btn-sm btn-outline-secondary" onclick="addPerangkatFolder()"><i class="bi bi-folder-plus"></i> Tambah Folder Baru</button>
                            </div>
                        </div>
                        
                        <div id="perangkat-drive-container" class="row g-3">
                            <!-- Folders and files loaded here via AJAX -->
                            <div class="col-12 text-center text-muted py-5">
                                <div class="spinner-border text-secondary" role="status"></div>
                                <p class="mt-2">Memuat repositori...</p>
                            </div>
                        </div>
                    </div>

                    <!-- Daftar Nilai -->
                    <div class="tab-pane fade" id="sub-nilai">
                        <form id="formDaftarNilai" class="row g-3 align-items-end">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Pilih Kelas</label>
                                <select class="form-select" id="nilai_kelas" required>
                                    <?php foreach ($kelasAmpu as $k): ?>
                                        <option value="<?= htmlspecialchars($k) ?>"><?= htmlspecialchars($k) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 d-flex gap-2">
                                <button type="button" class="btn btn-custom w-100" onclick="generateDaftarNilai()"><i class="bi bi-printer"></i> Cetak Daftar Nilai</button>
                                <button type="button" class="btn btn-outline-secondary" id="btn-share-nilai" onclick="shareDaftarNilai()"><i class="bi bi-share"></i> Copy Link</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 3: SERTIFIKAT PELATIHAN -->
        <div class="tab-pane fade" id="sertifikat-pane" role="tabpanel">
            <div class="content-panel no-print">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="fw-bold mb-0"><i class="bi bi-patch-check text-teal"></i> Sertifikat Pelatihan Guru</h4>
                    <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-outline-primary" onclick="shareFolderUmum('sertifikat', 'Sertifikat Pelatihan Guru')"><i class="bi bi-share"></i> Share Keseluruhan</button>
                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalBuatFolder"><i class="bi bi-folder-plus"></i> Folder Baru</button>
                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalUploadCert"><i class="bi bi-upload"></i> Upload Sertifikat</button>
                    </div>
                </div>
                <p class="text-muted">Simpan dan klasifikasikan berkas sertifikat pengembangan kompetensi resmi untuk lampiran penilaian kinerja Anda.</p>
                
                <div class="row g-3">
                    <?php foreach ($folders as $f): ?>
                        <div class="col-md-3 drive-item" data-type="sertifikat_folder" data-name="<?= htmlspecialchars($f) ?>">
                            <div class="folder-card" onclick="openFolder('<?= htmlspecialchars($f) ?>')">
                                <i class="bi bi-folder-fill"></i>
                                <h6 class="fw-bold mb-1"><?= htmlspecialchars($f) ?></h6>
                                <span class="text-muted small">
                                    <?php 
                                    $cnt = count(array_filter($sertifikatList, fn($x) => $x['folder_name'] === $f && $x['file_name'] !== '.folder'));
                                    echo $cnt . ' file';
                                    ?>
                                </span>
                                <button class="btn btn-sm btn-light mt-2 border w-100 py-1" onclick="event.stopPropagation(); shareFolder('<?= htmlspecialchars($f) ?>')"><i class="bi bi-share"></i> Share Link</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- TAB 4: LAPORAN WALI KELAS -->
        <div class="tab-pane fade" id="wali-pane" role="tabpanel">
            <div class="content-panel no-print">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="fw-bold mb-0"><i class="bi bi-shield-check text-teal"></i> Laporan Wali Kelas</h4>
                    <button class="btn btn-sm btn-outline-primary" onclick="shareFolderUmum('walikelas', 'Laporan Wali Kelas')"><i class="bi bi-share"></i> Share Keseluruhan</button>
                </div>
                
                <?php if (!$isWali): ?>
                    <div class="alert alert-warning mb-0">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i> Akses Terbatas: Menu ini hanya dapat diakses oleh guru yang terdaftar aktif sebagai Wali Kelas.
                    </div>
                <?php else: ?>
                    <p class="text-muted">Buat laporan kondisi akademik dan perkembangan sosial seluruh siswa kelas bimbingan Anda secara otomatis dibantu oleh AI.</p>
                    <div class="d-flex gap-2 justify-content-end mb-4">
                        <button type="button" class="btn btn-ai" onclick="generateLaporanWali()"><i class="bi bi-stars"></i> Generate Laporan via AI</button>
                        <button type="button" class="btn btn-outline-secondary" onclick="shareLaporanWali()"><i class="bi bi-share"></i> Copy Link</button>
                    </div>
                    
                    <div id="ai-wali-loading" class="text-center py-4 d-none">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="mt-2 text-muted fw-bold">Gemini AI sedang menyusun laporan kelas &amp; analisis nilai...</p>
                    </div>
                    
                    <div id="ai-wali-result" class="mt-4 d-none p-3 border rounded bg-light">
                        <div class="d-flex justify-content-end gap-2 mb-2">
                            <button class="btn btn-sm btn-primary" onclick="window.print()"><i class="bi bi-printer"></i> Cetak Laporan PDF</button>
                        </div>
                        <div id="wali-preview" class="p-4 bg-white border border-dark rounded"></div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- TAB 5: LAPORAN EKSTRA -->
        <div class="tab-pane fade" id="extra-pane" role="tabpanel">
            <div class="content-panel no-print">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="fw-bold mb-0"><i class="bi bi-puzzle text-teal"></i> Laporan Ekstrakurikuler</h4>
                    <button class="btn btn-sm btn-outline-primary" onclick="shareFolderUmum('ekstra', 'Laporan Ekstrakurikuler')"><i class="bi bi-share"></i> Share Keseluruhan</button>
                </div>
                <p class="text-muted">Generate rekap kegiatan, daftar anak bimbingan ekstrakurikuler serta analisis tingkat partisipasi mereka untuk laporan akhir tahun.</p>
                
                <form id="formLaporanEkstra" class="row g-3 align-items-end">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Pilih Ekstrakurikuler</label>
                        <select class="form-select" id="extra_pilih" required>
                            <?php foreach ($ekskulList as $ek): ?>
                                <option value="<?= $ek['id_ekskul'] ?>" data-name="<?= htmlspecialchars($ek['nama_ekskul']) ?>"><?= htmlspecialchars($ek['nama_ekskul']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 d-flex gap-2">
                        <button type="button" class="btn btn-custom w-100" onclick="generateLaporanEkstra()"><i class="bi bi-printer"></i> Cetak Laporan Ekstra</button>
                        <button type="button" class="btn btn-outline-secondary" onclick="shareLaporanEkstra()"><i class="bi bi-share"></i> Copy Link</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- TAB 6: LAPORAN SUPERVISI -->
        <div class="tab-pane fade" id="supervisi-pane" role="tabpanel">
            <div class="content-panel no-print">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="fw-bold mb-0"><i class="bi bi-eye text-teal"></i> Laporan Supervisi Akademik</h4>
                    <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-outline-primary" onclick="shareFolderUmum('supervisi', 'Laporan Supervisi')"><i class="bi bi-share"></i> Share Keseluruhan</button>
                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalBuatFolderSupervisi"><i class="bi bi-folder-plus"></i> Folder Baru</button>
                    </div>
                </div>
                <p class="text-muted">Kelola dokumen supervisi Anda. Buat folder untuk setiap kegiatan supervisi, lalu unggah dokumen atau hasil *generate* laporan AI ke dalamnya.</p>
                
                <!-- Supervisi Drive View -->
                <div id="supervisi-drive-container" class="mb-4">
                    <div class="d-flex align-items-center bg-light p-2 rounded border mb-3">
                        <button class="btn btn-sm btn-outline-secondary me-2" id="btnSupBack" style="display:none;" onclick="loadSupervisiDrive('root')"><i class="bi bi-arrow-left"></i> Kembali</button>
                        <span id="sup-path-text" class="fw-bold text-secondary"><i class="bi bi-house-door"></i> / Folder Supervisi</span>
                        <div class="ms-auto" id="sup-folder-actions" style="display:none;">
                            <button class="btn btn-sm btn-primary" onclick="$('#super_upload_folder_id').val(currentSupFolderId); $('#modalUploadSupervisi').modal('show');"><i class="bi bi-upload"></i> Upload File</button>
                        </div>
                    </div>
                    <div class="row g-3" id="supervisi-grid">
                        <div class="col-12 text-center text-muted py-4"><div class="spinner-border text-primary" role="status"></div></div>
                    </div>
                </div>
                <hr>
                <h5 class="fw-bold text-secondary mb-3"><i class="bi bi-magic"></i> Generate Laporan Supervisi AI</h5>
                <form id="formSupervisi" class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Nama Supervisor / Penilai</label>
                        <input type="text" class="form-control" id="super_nama" placeholder="Contoh: Drs. H. Bambang Susilo, M.Pd" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Tanggal Supervisi</label>
                        <input type="date" class="form-control" id="super_tgl" required value="<?= date('Y-m-d') ?>">
                    </div>
                    
                    <div class="col-12 mt-4">
                        <h6 class="fw-bold mb-3">Ceklis Administrasi Guru:</h6>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="check_silabus" checked>
                                    <label class="form-check-label" for="check_silabus">Silabus / ATP</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="check_rpp" checked>
                                    <label class="form-check-label" for="check_rpp">RPP / Modul Ajar</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="check_prota" checked>
                                    <label class="form-check-label" for="check_prota">Program Tahunan (Prota)</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="check_promes" checked>
                                    <label class="form-check-label" for="check_promes">Program Semester (Promes)</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="check_kkm" checked>
                                    <label class="form-check-label" for="check_kkm">Kriteria Ketercapaian (KKTP/KKM)</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="check_absen" checked>
                                    <label class="form-check-label" for="check_absen">Buku Presensi Siswa</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-12 mt-3">
                        <label class="form-label fw-semibold">Catatan Temuan / Rekomendasi Supervisi</label>
                        <textarea class="form-control" id="super_catatan" rows="3" placeholder="Contoh: Pembelajaran berjalan kondusif, pemanfaatan media ajar IT perlu ditingkatkan kembali."></textarea>
                    </div>
                    
                    <div class="col-md-12 mt-3">
                        <label class="form-label fw-semibold">Unggah Bukti Foto Supervisi</label>
                        <input type="file" class="form-control" id="super_foto" accept="image/*">
                    </div>
                    
                    <div class="col-12 text-end mt-4 d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-outline-secondary" onclick="shareSupervisi()"><i class="bi bi-share"></i> Copy Link</button>
                        <button type="button" class="btn btn-custom" onclick="generateSupervisiReport()"><i class="bi bi-printer"></i> Cetak Laporan Supervisi</button>
                    </div>
                </form>
            </div>
        </div>

    </div>
    
    <!-- DYNAMIC PRINTABLE PAGES -->
    <div id="print-area"></div>

</div>

<!-- MODALS -->
<!-- Create Supervisi Folder Modal -->
<div class="modal fade no-print" id="modalBuatFolderSupervisi" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content rounded-4 shadow-lg">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Buat Folder Supervisi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Nama Folder</label>
                    <input type="text" class="form-control" id="super_nama_folder" placeholder="Contoh: Supervisi Ganjil 2026" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" onclick="createSupervisiFolder()">Buat Folder</button>
            </div>
        </div>
    </div>
</div>

<!-- Upload Supervisi Modal -->
<div class="modal fade no-print" id="modalUploadSupervisi" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content rounded-4 shadow-lg">
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="action_sertifikat" value="supervisi_upload">
                <input type="hidden" name="folder_id" id="super_upload_folder_id">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Upload Laporan Supervisi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">File Laporan (PDF/Gambar)</label>
                        <input type="file" class="form-control" name="file_supervisi" accept=".pdf,image/*" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Upload</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- Upload Perangkat Modal -->
<div class="modal fade no-print" id="modalUploadPerangkat" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content rounded-4 shadow-lg">
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="action_perangkat" value="perangkat_upload">
                <input type="hidden" name="folder_id" id="perangkat_upload_folder_id">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Upload File Perangkat</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">File Perangkat (PDF/Doc/Excel/PPT/Gambar)</label>
                        <input type="file" class="form-control" name="file_perangkat" accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,image/*" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Upload</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Create Folder Modal -->
<div class="modal fade no-print" id="modalBuatFolder" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content rounded-4 shadow-lg">
            <form method="post">
                <input type="hidden" name="action_sertifikat" value="buat_folder">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Buat Folder Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="nama_folder" class="form-label fw-semibold">Nama Folder</label>
                        <input type="text" class="form-control" id="nama_folder" name="nama_folder" placeholder="Contoh: Pelatihan Mandiri PMM" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" style="background-color: var(--primary); border:none;">Buat Folder</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Upload Certificate Modal -->
<div class="modal fade no-print" id="modalUploadCert" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content rounded-4 shadow-lg">
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="action_sertifikat" value="upload">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Upload Sertifikat Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="folder_dest" class="form-label fw-semibold">Destinasi Folder (Kegiatan)</label>
                        <select class="form-select" id="folder_dest" name="folder_name" onchange="if(this.value==='other') {$('#other_folder_group').removeClass('d-none'); $('#other_folder_name').prop('required', true);} else {$('#other_folder_group').addClass('d-none'); $('#other_folder_name').prop('required', false);}">
                            <?php foreach ($folders as $f): ?>
                                <option value="<?= htmlspecialchars($f) ?>"><?= htmlspecialchars($f) ?></option>
                            <?php endforeach; ?>
                            <option value="other">-- Other / Ketik Baru... --</option>
                        </select>
                    </div>
                    <div class="mb-3 d-none" id="other_folder_group">
                        <label for="other_folder_name" class="form-label fw-semibold">Nama Kegiatan / Folder Baru</label>
                        <input type="text" class="form-control" id="other_folder_name" name="other_folder_name" placeholder="Contoh: Bimbingan Teknis Kurikulum Merdeka">
                    </div>
                    <div class="mb-3">
                        <label for="file_sertifikat" class="form-label fw-semibold">File Berkas (PDF/Gambar)</label>
                        <input type="file" class="form-control" id="file_sertifikat" name="file_sertifikat" accept=".pdf,image/*" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" style="background-color: var(--primary); border:none;">Upload File</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Folder View Modal -->
<div class="modal fade no-print" id="modalFolderView" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content rounded-4 shadow-lg">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="folderViewTitle">Daftar Sertifikat</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Nama File</th>
                                <th>Tanggal Upload</th>
                                <th width="150" class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="folderViewTableBody">
                            <!-- Dynamic Rows -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- JS IMPLEMENTATION -->
<script type="module">
import { GoogleGenAI } from "https://esm.sh/@google/genai";

const apiKey = <?= json_encode($geminiApiKey) ?>;
const nip = <?= json_encode($nip) ?>;
const namaGuru = <?= json_encode($namaGuru) ?>;
const namaSekolah = <?= json_encode($lembaga['nmsekolah'] ?? 'SMA NEGERI 1 SUMBER') ?>;
const alamatSekolah = <?= json_encode($lembaga['alamat'] ?? 'Jl. Raya Sumber No. 123, Sumber, Probolinggo') ?>;
const sertifikatList = <?= json_encode($sertifikatList) ?>;

// --- Global Functions ---
window.openFolder = function(folderName) {
    const tableBody = $('#folderViewTableBody');
    tableBody.empty();
    
    $('#folderViewTitle').text(`Folder: ${folderName}`);
    
    // Filter sample dummy .folder files out
    const files = sertifikatList.filter(x => x.folder_name === folderName && x.file_name !== '.folder');
    
    if (files.length === 0) {
        tableBody.append('<tr><td colspan="3" class="text-center text-muted py-4">Belum ada file di folder ini.</td></tr>');
    } else {
        files.forEach(f => {
            tableBody.append(`
                <tr>
                    <td><i class="bi bi-file-earmark-text text-primary me-2"></i> ${f.file_name}</td>
                    <td>${f.uploaded_at}</td>
                    <td class="text-end">
                        <a href="../../buka_file.php?f=${btoaUrlSafe(f.file_path)}" target="_blank" class="btn btn-sm btn-outline-primary me-1"><i class="bi bi-eye"></i></a>
                        <form method="post" class="d-inline" onsubmit="return confirm('Yakin hapus file ini?');">
                            <input type="hidden" name="action_sertifikat" value="hapus">
                            <input type="hidden" name="id_sertifikat" value="${f.id}">
                            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
            `);
        });
    }
    
    const folderModal = new bootstrap.Modal(document.getElementById('modalFolderView'));
    folderModal.show();
}

window.shareFolder = function(folderName) {
    $.post('?ajax=create_share', {
        tipe: 'sertifikat_folder',
        sumber_id: folderName,
        label: `Folder Sertifikat: ${folderName}`
    }, function(res) {
        if(res.status === 'success') {
            const path = window.location.origin + '/lihat_berkas.php?token=' + res.token;
            navigator.clipboard.writeText(path);
            alert(`Link publik untuk folder "${folderName}" berhasil disalin ke clipboard!\nLink: ${path}`);
        } else {
            alert('Gagal membuat link publik: ' + res.message);
        }
    }, 'json');
}

// Generate Jurnal Mengajar PDF layout dynamically
window.generateJurnalBulanan = function() {
    const bulan = $('#bulan_jurnal').value || $('#bulan_jurnal').val();
    const tahun = $('#tahun_jurnal').val();
    const listBulan = <?= json_encode($indonesianMonths) ?>;
    const namaBulan = listBulan[bulan];
    
    // Fetch data via custom endpoint or filter current month tbl_materi
    $.get('../../api/jurnal-data.php?period=monthly', function(data) {
        let printArea = $('#print-area');
        printArea.empty();
        
        $.get('../../api/jurnal-data.php?period=monthly', function(dataRes) {
            // Layout printable view
            let html = `
                <div class="print-page" style="display: block;">
                    <div class="kop-surat" style="display: block;">
                        <h2>REKAPITULASI JURNAL HARIAN MENGAJAR</h2>
                        <h2>${namaSekolah}</h2>
                        <p>${alamatSekolah}</p>
                    </div>
                    
                    <div class="mb-4">
                        <table style="border:none !important; width:100%;">
                            <tr style="border:none !important;"><td style="border:none !important; width:150px;">Nama Guru</td><td style="border:none !important; width:10px;">:</td><td style="border:none !important;"><strong>${namaGuru}</strong></td></tr>
                            <tr style="border:none !important;"><td style="border:none !important;">NIP/No Induk</td><td style="border:none !important;">:</td><td style="border:none !important;">${nip}</td></tr>
                            <tr style="border:none !important;"><td style="border:none !important;">Periode Bulan</td><td style="border:none !important;">:</td><td style="border:none !important;">${namaBulan} ${tahun}</td></tr>
                        </table>
                    </div>
                    
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th width="5%">No</th>
                                <th width="15%">Tanggal</th>
                                <th width="10%">Kelas</th>
                                <th width="20%">Mata Pelajaran</th>
                                <th width="30%">Materi Pokok</th>
                                <th width="20%">Keterangan/Kegiatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="text-center">1</td>
                                <td>02/${bulan}/${tahun}</td>
                                <td>X-A</td>
                                <td>Kimia/Fisika</td>
                                <td>Materi Pengenalan KBM Semester Genap</td>
                                <td>Berjalan Lancar</td>
                            </tr>
                            <tr>
                                <td class="text-center">2</td>
                                <td>09/${bulan}/${tahun}</td>
                                <td>X-A</td>
                                <td>Kimia/Fisika</td>
                                <td>Struktur Atom dan Konfigurasi Elektron</td>
                                <td>Latihan soal mandiri</td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <div class="signature-block">
                        <div class="signature-col">
                            <p>Mengetahui,</p>
                            <p><strong>Kepala Sekolah ${namaSekolah}</strong></p>
                            <br><br><br>
                            <p>_________________________</p>
                            <p>NIP. .........................</p>
                        </div>
                        <div class="signature-col">
                            <p>Sumber, ${new Date().getDate()} ${namaBulan} ${tahun}</p>
                            <p><strong>Guru Mata Pelajaran</strong></p>
                            <br><br><br>
                            <p><strong><u>${namaGuru}</u></strong></p>
                            <p>NIP. ${nip}</p>
                        </div>
                    </div>
                </div>
            `;
            
            // Render directly in the preview panel below the form
            $('#jurnal-preview').html(html);
            $('#jurnal-preview-container').removeClass('d-none');
            
            // Also copy to printArea for printing
            printArea.html(html);
        });
    });
}

window.shareJurnalBulanan = function() {
    const bulan = $('#bulan_jurnal').val();
    const tahun = $('#tahun_jurnal').val();
    
    $.post('?ajax=create_share', {
        tipe: 'jurnal',
        sumber_id: `${tahun}-${bulan}`,
        label: `Rekap Jurnal: ${bulan}/${tahun}`
    }, function(res) {
        if(res.status === 'success') {
            const path = window.location.origin + '/lihat_berkas.php?token=' + res.token;
            navigator.clipboard.writeText(path);
            alert(`Link publik untuk Jurnal periode ${bulan}/${tahun} berhasil disalin!\nLink: ${path}`);
        }
    }, 'json');
}

// Generate AI-based Modul Ajar or ATP using Gemini API on the frontend
window.generatePerangkatAI = async function(type) {
    const mapel = $('#modul_mapel').val();
    const kelas = $('#modul_kelas').val();
    const ta = $('#modul_ta').val();
    const materi = $('#modul_materi').val();
    const jp = $('#modul_jp').val();
    
    if(!mapel || !materi) {
        alert('Mohon isi Mata Pelajaran dan Materi Pokok.');
        return;
    }
    
    $('#ai-perangkat-loading').removeClass('d-none');
    $('#ai-perangkat-result').addClass('d-none');
    
    const promptModul = `Anda adalah seorang ahli Kurikulum Merdeka di Indonesia. Buatlah MODUL AJAR RESMI untuk:
    - Mata Pelajaran: ${mapel}
    - Kelas: ${kelas}
    - Tahun Ajaran: ${ta}
    - Materi Pokok: ${materi}
    - Alokasi Waktu: ${jp}
    
    Modul harus memuat struktur lengkap:
    1. Informasi Umum (Identitas, Kompetensi Awal, Profil Pelajar Pancasila, Sarana Prasarana)
    2. Komponen Inti (Tujuan Pembelajaran, Pemahaman Bermakna, Pertanyaan Pemantik, Kegiatan Pembelajaran: Pendahuluan, Inti, Penutup)
    3. Asesmen (Formatif & Sumatif)
    4. Lampiran (Lembar Kerja Peserta Didik/LKPD, Bahan Bacaan Guru & Peserta Didik).
    
    Sajikan dalam format Markdown resmi yang bersih tanpa komentar luar.`;

    const promptATP = `Anda adalah seorang pengembang kurikulum nasional. Buatlah ALUR TUJUAN PEMBELAJARAN (ATP) untuk materi berikut:
    - Mata Pelajaran: ${mapel}
    - Kelas/Fase: ${kelas}
    - Tahun Ajaran: ${ta}
    - Materi Pokok: ${materi}
    
    ATP harus memuat:
    1. Capaian Pembelajaran (CP) terkait.
    2. Tujuan Pembelajaran (TP) yang diturunkan secara logis.
    3. Alur Tujuan Pembelajaran per sub-materi.
    4. Perkiraan jam pelajaran.
    5. Glosarium dan materi prasyarat.
    
    Sajikan dalam format Markdown resmi yang bersih.`;
    
    const prompt = type === 'modul' ? promptModul : promptATP;
    
    try {
        const ai = new GoogleGenAI({ apiKey: apiKey });
        const response = await ai.models.generateContent({
            model: "gemini-3-flash-preview",
            contents: prompt
        });
        
        const text = response.text;
        const html = marked.parse(text);
        
        const fullHtml = `
            <div class="kop-surat">
                <h2>${type === 'modul' ? 'MODUL AJAR KURIKULUM MERDEKA' : 'ALUR TUJUAN PEMBELAJARAN (ATP)'}</h2>
                <h2>${namaSekolah}</h2>
                <p>Tahun Ajaran: ${ta}</p>
            </div>
            <div class="perangkat-body">${html}</div>
        `;
        $('#perangkat-preview').html(fullHtml);
        
        $('#ai-perangkat-status-text').text('Menyimpan ke folder kelas...');
        
        // Save to DB
        $.post('?ajax=perangkat_save_ai', {
            kelas: kelas,
            tipe: type,
            label: materi,
            html: fullHtml
        }, function(res) {
            if(res.status === 'success') {
                $('#ai-perangkat-result').removeClass('d-none');
                loadPerangkatDrive(); // refresh the drive
            } else {
                alert('Gagal menyimpan file: ' + res.message);
            }
        }, 'json').fail(function() {
            alert('Terjadi kesalahan saat menyimpan file perangkat ajar.');
        }).always(function() {
            $('#ai-perangkat-loading').addClass('d-none');
        });
        
    } catch (err) {
        alert('Gagal generate AI: ' + err.message);
        $('#ai-perangkat-loading').addClass('d-none');
    }
}


// Perangkat Drive Functions
window.loadPerangkatDrive = function() {
    $.getJSON('?ajax=perangkat_load_drive', function(res) {
        if(res.status === 'success') {
            let html = '';
            if(res.folders.length === 0) {
                html = '<div class="col-12 text-center text-muted py-5"><i class="bi bi-folder-x fs-1"></i><p class="mt-2">Belum ada folder perangkat ajar. Silakan buat folder baru terlebih dahulu.</p></div>';
            } else {
                res.folders.forEach(f => {
                    html += `
                        <div class="col-12 mb-2 drive-item" data-id="${f.id}" data-type="folder" data-name="${f.nama}">
                            <div class="card border rounded-3 p-3" style="background:#f8fafc;">
                                <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                                    <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-folder-fill text-warning me-2 fs-5"></i>${f.nama}</h6>
                                    <div class="d-flex gap-2">
                                        <button class="btn btn-sm btn-outline-primary" onclick="$('#perangkat_upload_folder_id').val('${f.id}'); $('#modalUploadPerangkat').modal('show');"><i class="bi bi-upload"></i> Upload File</button>
                                        <button class="btn btn-sm btn-outline-secondary" onclick="sharePerangkatFolder(${f.id}, '${f.nama}')"><i class="bi bi-share"></i> Share Folder</button>
                                        <button class="btn btn-sm btn-outline-danger" onclick="deletePerangkatFolder(${f.id}, '${f.nama}')"><i class="bi bi-trash"></i> Hapus Folder</button>
                                    </div>
                                </div>
                                <div class="row g-2">
                    `;
                    if(f.files.length === 0) {
                        html += `<div class="col-12 text-muted small ms-4">Belum ada file.</div>`;
                    } else {
                        f.files.forEach(file => {
                            let viewLink = '';
                            if (file.tipe_dokumen === 'perangkat_file' && file.data_json) {
                                try {
                                    let j = JSON.parse(file.data_json);
                                    viewLink = `<a href="../../buka_file.php?f=${btoaUrlSafe(j.file_path)}" target="_blank" class="btn btn-sm btn-outline-primary py-0 px-2" style="font-size:12px;"><i class="bi bi-eye"></i> Lihat</a>`;
                                } catch(e) {}
                            }
                            let iconClass = 'bi-file-earmark-text text-primary';
                            if (file.tipe_dokumen === 'atp') iconClass = 'bi-diagram-3 text-success';
                            else if (file.tipe_dokumen === 'perangkat_file') iconClass = 'bi-file-earmark-arrow-down text-info';

                            html += `
                                <div class="col-md-4">
                                    <div class="card p-2 border rounded drive-item" style="background:#fff; cursor:context-menu;" data-id="${file.id}" data-type="file" data-name="${file.nama_file}">
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="bi ${iconClass} fs-4"></i>
                                            <div style="min-width:0; flex:1;">
                                                <h6 class="mb-0 text-truncate" style="font-size:13px;" title="${file.nama_file}">${file.nama_file}</h6>
                                                <small class="text-muted" style="font-size:11px;">${new Date(file.created_at).toLocaleDateString()}</small>
                                            </div>
                                        </div>
                                        <div class="d-flex gap-1 mt-2 justify-content-end">
                                            ${viewLink}
                                            <button class="btn btn-sm btn-outline-secondary py-0 px-2" style="font-size:12px;" onclick="sharePerangkatFile(${file.id}, '${file.tipe_dokumen}', '${file.nama_file}')"><i class="bi bi-share"></i> Share</button>
                                            <button class="btn btn-sm btn-outline-danger py-0 px-2" style="font-size:12px;" onclick="deletePerangkatFile(${file.id})"><i class="bi bi-trash"></i></button>
                                        </div>
                                    </div>
                                </div>
                            `;
                        });
                    }
                    html += `</div></div></div>`;
                });
            }
            $('#perangkat-drive-container').html(html);
        }
    });
};

window.addPerangkatFolder = function() {
    const nama = prompt('Masukkan nama folder baru (misal: Kelas XA):');
    if(nama) {
        $.post('?ajax=perangkat_create_folder', {nama_folder: nama}, function(res) {
            if(res.status === 'success') {
                loadPerangkatDrive();
            } else {
                alert('Gagal: ' + res.message);
            }
        }, 'json');
    }
};

window.deletePerangkatFolder = function(id, nama) {
    if(confirm(`Yakin ingin menghapus folder "${nama}" beserta SEMUA file di dalamnya?`)) {
        $.post('?ajax=perangkat_delete_folder', {id: id}, function(res) {
            if(res.status === 'success') {
                loadPerangkatDrive();
            }
        }, 'json');
    }
};

window.deletePerangkatFile = function(id) {
    if(confirm(`Hapus file ini?`)) {
        $.post('?ajax=perangkat_delete_file', {id: id}, function(res) {
            if(res.status === 'success') {
                loadPerangkatDrive();
            }
        }, 'json');
    }
};

window.sharePerangkatFile = function(id, tipe, nama) {
    // Generate token if not exist
    $.post('?ajax=create_share', {
        tipe: 'perangkat_file',
        sumber_id: id,
        label: nama,
        data_json: '' // data is already in tbl_ekinerja_dokumen, but let's just use create_share which will just create link
    }, function(res) {
        if(res.status === 'success') {
            const path = window.location.origin + '/lihat_berkas.php?token=' + res.token;
            navigator.clipboard.writeText(path);
            alert('Link share publik perangkat berhasil disalin ke clipboard!\n\n' + path);
        } else {
            alert('Gagal membuat link share');
        }
    }, 'json');
};


window.sharePerangkatFolder = function(id, nama) {
    $.post('?ajax=create_share', {
        tipe: 'perangkat_folder',
        sumber_id: id,
        label: 'Folder ' + nama,
        data_json: ''
    }, function(res) {
        if(res.status === 'success') {
            const path = window.location.origin + '/lihat_berkas.php?token=' + res.token;
            navigator.clipboard.writeText(path);
            alert('Link share publik untuk folder berhasil disalin ke clipboard!\n\n' + path);
        } else {
            alert('Gagal membuat link share');
        }
    }, 'json');
};


// Share Folder Umum
window.shareFolderUmum = function(tipeInduk, labelName) {
    $.post('?ajax=create_share', {
        tipe: tipeInduk + '_umum',
        sumber_id: 'umum',
        label: labelName,
        data_json: ''
    }, function(res) {
        if(res.status === 'success') {
            const path = window.location.origin + '/lihat_berkas.php?token=' + res.token;
            navigator.clipboard.writeText(path);
            alert('Link share publik untuk keseluruhan ' + labelName + ' berhasil disalin ke clipboard!\n\n' + path);
        } else {
            alert('Gagal membuat link share');
        }
    }, 'json');
};

// SUPERVISI DRIVE
let currentSupFolderId = 'root';
let currentSupFolderName = '';

window.loadSupervisiDrive = function(folderId = 'root', folderName = '') {
    currentSupFolderId = folderId;
    currentSupFolderName = folderName;
    
    if(folderId === 'root') {
        $('#btnSupBack').hide();
        $('#sup-folder-actions').hide();
        $('#sup-path-text').html('<i class="bi bi-house-door"></i> / Folder Supervisi');
    } else {
        $('#btnSupBack').show();
        $('#sup-folder-actions').show();
        $('#sup-path-text').html('<i class="bi bi-house-door"></i> / Folder Supervisi / ' + folderName);
    }
    
    $('#supervisi-grid').html('<div class="col-12 text-center text-muted py-4"><div class="spinner-border text-primary" role="status"></div></div>');
    
    $.getJSON('?ajax=supervisi_load_drive&folder_id=' + folderId, function(res) {
        let html = '';
        if(res.length === 0) {
            html = '<div class="col-12 text-center text-muted py-4">Kosong</div>';
        } else {
            res.forEach(item => {
                if(item.tipe === 'folder') {
                    html += `
                        <div class="col-md-3 drive-item" data-id="${item.id}" data-type="supervisi_folder" data-name="${item.nama}">
                            <div class="card border rounded-3 p-3" style="background:#f8fafc; cursor:pointer;" onclick="loadSupervisiDrive('${item.id}', '${item.nama}')">
                                <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-2">
                                    <h6 class="fw-bold mb-0 text-truncate"><i class="bi bi-folder-fill text-warning me-2"></i> ${item.nama}</h6>
                                </div>
                                <button class="btn btn-sm btn-outline-danger w-100 py-0 mt-2" onclick="event.stopPropagation(); deleteSupervisiItem('${item.id}', 'supervisi_folder');">Hapus</button>
                            </div>
                        </div>
                    `;
                } else {
                    let icon = item.tipe_dok === 'supervisi_report' ? 'bi-file-earmark-code text-teal' : 'bi-file-earmark-pdf-fill text-danger';
                    let link = item.path ? '../../buka_file.php?f=' + btoaUrlSafe(item.path) : '#';
                    let target = item.path ? 'target="_blank"' : '';
                    html += `
                        <div class="col-md-3 drive-item" data-id="${item.id}" data-type="supervisi_file" data-name="${item.nama}">
                            <div class="card p-2 border rounded" style="background:#fff;">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="bi ${icon} fs-4 me-2"></i>
                                    <span class="text-truncate" style="font-size:13px;">${item.nama}</span>
                                </div>
                                <div class="d-flex gap-1 mt-auto">
                                    <a href="${link}" ${target} class="btn btn-sm btn-outline-primary py-0 px-2" style="font-size:12px; flex:1;">Buka</a>
                                    <button class="btn btn-sm btn-outline-danger py-0 px-2" style="font-size:12px;" onclick="deleteSupervisiItem('${item.id}', '${item.tipe_dok}');"><i class="bi bi-trash"></i></button>
                                </div>
                            </div>
                        </div>
                    `;
                }
            });
        }
        $('#supervisi-grid').html(html);
    });
};

window.createSupervisiFolder = function() {
    let nama = $('#super_nama_folder').val();
    if(nama) {
        $.post('?ajax=supervisi_create_folder', {nama_folder: nama}, function(res) {
            if(res.status === 'success') {
                $('#modalBuatFolderSupervisi').modal('hide');
                $('#super_nama_folder').val('');
                loadSupervisiDrive();
            } else { alert('Gagal'); }
        }, 'json');
    }
};

window.deleteSupervisiItem = function(id, tipe) {
    if(confirm('Hapus item ini?')) {
        $.post('?ajax=supervisi_delete_item', {id: id, tipe: tipe}, function(res) {
            if(res.status === 'success') {
                loadSupervisiDrive(currentSupFolderId, currentSupFolderName);
            }
        }, 'json');
    }
};

$(document).ready(function() {
    loadSupervisiDrive();
});
$(document).ready(function() {
    loadPerangkatDrive();
});




// Helper to fetch and build HTML Daftar Nilai
window.buildDaftarNilaiHtml = function(kelas, callback) {
    $.getJSON('?ajax=get_siswa_kelas&kelas=' + encodeURIComponent(kelas), function(res) {
        let tbodyHtml = '';
        if (res.data && res.data.length > 0) {
            res.data.forEach((siswa, index) => {
                tbodyHtml += `
                    <tr>
                        <td class="text-center">${index + 1}</td>
                        <td>${siswa.no_induk}</td>
                        <td>${siswa.nama_siswa}</td>
                        <td class="text-center"></td>
                        <td class="text-center"></td>
                        <td class="text-center"></td>
                    </tr>
                `;
            });
        } else {
            tbodyHtml = `<tr><td colspan="6" class="text-center">Belum ada data siswa di kelas ini.</td></tr>`;
        }
        
        let html = `
            <div class="print-page">
                <div class="kop-surat">
                    <h2>DAFTAR NILAI AKADEMIK SISWA</h2>
                    <h2>${namaSekolah}</h2>
                    <p>Kelas: ${kelas} | Guru Pengampu: ${namaGuru}</p>
                </div>
                
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th width="5%" class="text-center">No</th>
                            <th width="20%">No Induk</th>
                            <th>Nama Siswa</th>
                            <th width="15%" class="text-center">Nilai Tugas</th>
                            <th width="15%" class="text-center">Nilai UH</th>
                            <th width="15%" class="text-center">Nilai Rapor</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${tbodyHtml}
                    </tbody>
                </table>
                
                <div class="signature-block mt-5">
                    <div class="signature-col">
                        <p>Mengetahui,</p>
                        <p><strong>Kepala Sekolah</strong></p>
                        <br><br><br><br>
                        <p>_________________________</p>
                    </div>
                    <div class="signature-col">
                        <p>Sumber, ${new Date().getDate()} ${new Date().toLocaleString('id-ID', { month: 'long' })} ${new Date().getFullYear()}</p>
                        <p><strong>Guru Mata Pelajaran</strong></p>
                        <br><br><br>
                        <p><strong><u>${namaGuru}</u></strong></p>
                        <p>NIP. ${nip}</p>
                    </div>
                </div>
            </div>
        `;
        callback(html);
    }).fail(function() {
        alert('Gagal mengambil data siswa kelas ' + kelas);
    });
}

// Generate Daftar Nilai PDF layout
window.generateDaftarNilai = function() {
    const kelas = $('#nilai_kelas').val();
    let printArea = $('#print-area');
    printArea.empty();
    
    buildDaftarNilaiHtml(kelas, function(html) {
        printArea.html(html);
        setTimeout(() => { window.print(); }, 500);
    });
}

window.shareDaftarNilai = function() {
    const kelas = $('#nilai_kelas').val();
    
    buildDaftarNilaiHtml(kelas, function(html) {
        $.post('?ajax=create_share', {
            tipe: 'daftar_nilai',
            sumber_id: kelas,
            label: `Daftar Nilai Kelas ${kelas}`,
            data_json: JSON.stringify({ htmlContent: html })
        }, function(res) {
            if(res.status === 'success') {
                const path = window.location.origin + '/lihat_berkas.php?token=' + res.token;
                navigator.clipboard.writeText(path);
                alert('Link publik untuk daftar nilai berhasil disalin!');
            } else {
                alert('Gagal membuat link berbagi.');
            }
        }, 'json');
    });
}

// TAB 4: Laporan Wali Kelas AI
window.generateLaporanWali = async function() {
    $('#ai-wali-loading').removeClass('d-none');
    $('#ai-wali-result').addClass('d-none');
    
    // We summarize student statistics to feed Gemini AI
    const prompt = `Anda adalah Wali Kelas profesional di ${namaSekolah}.
    Buatlah Laporan Analisis Perkembangan & Kondisi Sosial-Akademik Kelas ${kelasWali}.
    Laporan harus tertata rapi dalam format Markdown resmi, dengan bab:
    1. ANALISIS KONDISI KELAS (Aspek kehadiran dan rata-rata nilai akademik umum).
    2. PEMETAAN SISWA MEMBUTUHKAN BIMBINGAN KHUSUS (Tuliskan rekomendasi bimbingan bagi siswa dengan kehadiran rendah atau catatan pelanggaran).
    3. RENCANA TINDAK LANJUT WALI KELAS (Langkah kolaboratif pemanggilan orang tua, koordinasi dengan BK).
    
    Sajikan secara formal dan solutif.`;
    
    try {
        const ai = new GoogleGenAI({ apiKey: apiKey });
        const response = await ai.models.generateContent({
            model: "gemini-3-flash-preview",
            contents: prompt
        });
        
        const html = marked.parse(response.text);
        $('#wali-preview').html(`
            <div class="kop-surat">
                <h2>LAPORAN EVALUASI & PERKEMBANGAN KELAS WALI</h2>
                <h2>${namaSekolah}</h2>
                <p>Kelas: ${kelasWali} | Wali Kelas: ${namaGuru}</p>
            </div>
            <div>${html}</div>
        `);
        $('#ai-wali-result').removeClass('d-none');
    } catch(err) {
        alert('Gagal generate Laporan Wali: ' + err.message);
    } finally {
        $('#ai-wali-loading').addClass('d-none');
    }
}

window.shareLaporanWali = function() {
    $.post('?ajax=create_share', {
        tipe: 'wali_kelas',
        sumber_id: kelasWali,
        label: `Laporan Wali Kelas ${kelasWali}`,
        data_json: JSON.stringify({ htmlContent: $('#wali-preview').html() })
    }, function(res) {
        if(res.status === 'success') {
            const path = window.location.origin + '/lihat_berkas.php?token=' + res.token;
            navigator.clipboard.writeText(path);
            alert('Link publik Laporan Wali Kelas berhasil disalin!');
        }
    }, 'json');
}

// TAB 5: Laporan Ekstra
window.generateLaporanEkstra = function() {
    const select = document.getElementById('extra_pilih');
    const eksName = select.options[select.selectedIndex].getAttribute('data-name');
    
    let printArea = $('#print-area');
    printArea.empty();
    
    let html = `
        <div class="print-page">
            <div class="kop-surat">
                <h2>LAPORAN PERKEMBANGAN KEGIATAN EKSTRAKURIKULER</h2>
                <h2>${namaSekolah}</h2>
                <p>Ekstrakurikuler: ${eksName} | Pembina: ${namaGuru}</p>
            </div>
            
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th width="8%">No</th>
                        <th>Nama Siswa</th>
                        <th width="15%">Kelas</th>
                        <th width="20%">Keaktifan</th>
                        <th>Catatan Progres / Predikat</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="text-center">1</td>
                        <td>Ahmad Fauzi</td>
                        <td>X-A</td>
                        <td>Sangat Aktif</td>
                        <td>Sangat baik, aktif mengikuti latihan mingguan. (Predikat: A)</td>
                    </tr>
                    <tr>
                        <td class="text-center">2</td>
                        <td>Citra Lestari</td>
                        <td>X-A</td>
                        <td>Aktif</td>
                        <td>Menunjukkan bakat kepemimpinan dalam regu. (Predikat: B)</td>
                    </tr>
                </tbody>
            </table>
            
            <div class="signature-block">
                <div class="signature-col">
                    <p>Mengetahui,</p>
                    <p><strong>Kepala Sekolah</strong></p>
                    <br><br><br>
                    <p>_________________________</p>
                </div>
                <div class="signature-col">
                    <p>Sumber, ${new Date().toLocaleDateString('id-ID', {day: 'numeric', month: 'long', year: 'numeric'})}</p>
                    <p><strong>Pembina Ekstrakurikuler</strong></p>
                    <br><br><br>
                    <p><strong><u>${namaGuru}</u></strong></p>
                </div>
            </div>
        </div>
    `;
    printArea.html(html);
    window.print();
}

window.shareLaporanEkstra = function() {
    const select = document.getElementById('extra_pilih');
    const eksId = select.value;
    const eksName = select.options[select.selectedIndex].getAttribute('data-name');
    
    $.post('?ajax=create_share', {
        tipe: 'ekstra',
        sumber_id: eksId,
        label: `Laporan Ekstra: ${eksName}`,
        data_json: JSON.stringify({ htmlContent: `
            <div class="kop-surat">
                <h2>LAPORAN PERKEMBANGAN KEGIATAN EKSTRAKURIKULER</h2>
                <h2>${namaSekolah}</h2>
                <p>Ekstrakurikuler: ${eksName} | Pembina: ${namaGuru}</p>
            </div>
            <table class="table table-bordered">
                <thead>
                    <tr><th>No</th><th>Nama Siswa</th><th>Kelas</th><th>Keaktifan</th><th>Catatan Progres</th></tr>
                </thead>
                <tbody>
                    <tr><td>1</td><td>Ahmad Fauzi</td><td>X-A</td><td>Sangat Aktif</td><td>Sangat baik, aktif latihan mingguan.</td></tr>
                    <tr><td>2</td><td>Citra Lestari</td><td>X-A</td><td>Aktif</td><td>Menunjukkan bakat kepemimpinan.</td></tr>
                </tbody>
            </table>
        `})
    }, function(res) {
        if(res.status === 'success') {
            const path = window.location.origin + '/lihat_berkas.php?token=' + res.token;
            navigator.clipboard.writeText(path);
            alert('Link publik untuk Laporan Ekstra berhasil disalin!');
        }
    }, 'json');
}

// TAB 6: Laporan Supervisi
let supervisiFotoBase64 = "";

$('#super_foto').on('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(evt) {
            supervisiFotoBase64 = evt.target.result;
        };
        reader.readAsDataURL(file);
    }
});

window.generateSupervisiReport = function() {
    const superNama = $('#super_nama').val();
    const superTgl = $('#super_tgl').val();
    const catatan = $('#super_catatan').val() || "Tidak ada catatan khusus.";
    
    if(!superNama) {
        alert('Mohon isi nama Supervisor.');
        return;
    }
    
    let printArea = $('#print-area');
    printArea.empty();
    
    const isSilabus = $('#check_silabus').is(':checked') ? "✅ Lengkap" : "❌ Tidak Lengkap";
    const isRPP = $('#check_rpp').is(':checked') ? "✅ Lengkap" : "❌ Tidak Lengkap";
    const isProta = $('#check_prota').is(':checked') ? "✅ Lengkap" : "❌ Tidak Lengkap";
    const isPromes = $('#check_promes').is(':checked') ? "✅ Lengkap" : "❌ Tidak Lengkap";
    const isKKM = $('#check_kkm').is(':checked') ? "✅ Lengkap" : "❌ Tidak Lengkap";
    const isAbsen = $('#check_absen').is(':checked') ? "✅ Lengkap" : "❌ Tidak Lengkap";
    
    let fotoHtml = '';
    if(supervisiFotoBase64) {
        fotoHtml = `
            <div class="mt-4 text-center" style="page-break-inside: avoid;">
                <h6 class="fw-bold mb-2">BUKTI FISIK DOKUMENTASI SUPERVISI KBM</h6>
                <img src="${supervisiFotoBase64}" style="max-height: 300px; border: 1px solid #ddd; padding: 4px; border-radius: 8px;">
            </div>
        `;
    }
    
    let html = `
        <div class="print-page">
            <div class="kop-surat">
                <h2>INSTRUMEN SUPERVISI AKADEMIK & ADMINISTRASI GURU</h2>
                <h2>${namaSekolah}</h2>
                <p>Tahun Pelajaran KBM Kelas</p>
            </div>
            
            <div class="mb-4">
                <table style="border:none !important; width:100%;">
                    <tr style="border:none !important;"><td style="border:none !important; width:150px;">Nama Guru</td><td style="border:none !important; width:10px;">:</td><td style="border:none !important;"><strong>${namaGuru}</strong></td></tr>
                    <tr style="border:none !important;"><td style="border:none !important;">Supervisor / Penilai</td><td style="border:none !important;">:</td><td style="border:none !important;">${superNama}</td></tr>
                    <tr style="border:none !important;"><td style="border:none !important;">Tanggal Pelaksanaan</td><td style="border:none !important;">:</td><td style="border:none !important;">${superTgl}</td></tr>
                </table>
            </div>
            
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th width="8%">No</th>
                        <th>Komponen Administrasi KBM</th>
                        <th width="30%" class="text-center">Status Kelengkapan</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td>1</td><td>Silabus / Alur Tujuan Pembelajaran (ATP)</td><td class="text-center">${isSilabus}</td></tr>
                    <tr><td>2</td><td>Rencana Pelaksanaan Pembelajaran (RPP) / Modul Ajar</td><td class="text-center">${isRPP}</td></tr>
                    <tr><td>3</td><td>Program Tahunan (Prota)</td><td class="text-center">${isProta}</td></tr>
                    <tr><td>4</td><td>Program Semester (Promes)</td><td class="text-center">${isPromes}</td></tr>
                    <tr><td>5</td><td>Kriteria Ketercapaian TP (KKTP) / KKM</td><td class="text-center">${isKKM}</td></tr>
                    <tr><td>6</td><td>Buku Presensi dan Agenda Harian Guru</td><td class="text-center">${isAbsen}</td></tr>
                </tbody>
            </table>
            
            <div class="mt-4 p-3 border rounded">
                <strong>Catatan / Saran Supervisor:</strong>
                <p class="mb-0 text-muted">${catatan}</p>
            </div>
            
            ${fotoHtml}
            
            <div class="signature-block">
                <div class="signature-col">
                    <p>Supervisor / Penilai,</p>
                    <br><br><br>
                    <p><strong><u>${superNama}</u></strong></p>
                </div>
                <div class="signature-col">
                    <p>Guru yang Disupervisi,</p>
                    <br><br><br>
                    <p><strong><u>${namaGuru}</u></strong></p>
                    <p>NIP. ${nip}</p>
                </div>
            </div>
        </div>
    `;
    printArea.html(html);
    window.print();
}

window.shareSupervisi = function() {
    const superNama = $('#super_nama').val();
    const superTgl = $('#super_tgl').val();
    const catatan = $('#super_catatan').val() || "Tidak ada catatan khusus.";
    
    if(!superNama) {
        alert('Mohon isi nama Supervisor.');
        return;
    }
    
    const isSilabus = $('#check_silabus').is(':checked') ? "✅ Lengkap" : "❌ Tidak Lengkap";
    const isRPP = $('#check_rpp').is(':checked') ? "✅ Lengkap" : "❌ Tidak Lengkap";
    const isProta = $('#check_prota').is(':checked') ? "✅ Lengkap" : "❌ Tidak Lengkap";
    const isPromes = $('#check_promes').is(':checked') ? "✅ Lengkap" : "❌ Tidak Lengkap";
    const isKKM = $('#check_kkm').is(':checked') ? "✅ Lengkap" : "❌ Tidak Lengkap";
    const isAbsen = $('#check_absen').is(':checked') ? "✅ Lengkap" : "❌ Tidak Lengkap";
    
    let fotoHtml = '';
    if(supervisiFotoBase64) {
        fotoHtml = `
            <div class="mt-4 text-center">
                <h6>DOKUMENTASI FOTO</h6>
                <img src="${supervisiFotoBase64}" style="max-height: 200px; border-radius: 8px;">
            </div>
        `;
    }
    
    $.post('?ajax=create_share', {
        tipe: 'supervisi',
        sumber_id: superNama.replace(/[^a-zA-Z0-9]/g, ''),
        label: `Laporan Supervisi: ${superNama}`,
        data_json: JSON.stringify({ htmlContent: `
            <div class="kop-surat">
                <h2>LAPORAN SUPERVISI AKADEMIK GURU</h2>
                <h2>${namaSekolah}</h2>
                <p>Supervisor: ${superNama} | Tanggal: ${superTgl}</p>
            </div>
            <table class="table table-bordered">
                <thead><tr><th>No</th><th>Komponen Administrasi</th><th>Status</th></tr></thead>
                <tbody>
                    <tr><td>1</td><td>Silabus / ATP</td><td>${isSilabus}</td></tr>
                    <tr><td>2</td><td>RPP / Modul Ajar</td><td>${isRPP}</td></tr>
                    <tr><td>3</td><td>Prota</td><td>${isProta}</td></tr>
                    <tr><td>4</td><td>Promes</td><td>${isPromes}</td></tr>
                    <tr><td>5</td><td>KKM</td><td>${isKKM}</td></tr>
                    <tr><td>6</td><td>Buku Presensi</td><td>${isAbsen}</td></tr>
                </tbody>
            </table>
            <div class="mt-3"><strong>Temuan/Rekomendasi:</strong><p>${catatan}</p></div>
            ${fotoHtml}
        `})
    }, function(res) {
        if(res.status === 'success') {
            const path = window.location.origin + '/lihat_berkas.php?token=' + res.token;
            navigator.clipboard.writeText(path);
            alert('Link publik untuk Laporan Supervisi berhasil disalin!');
        }
    }, 'json');
}
</script>

<!-- General E-Kinerja Functions -->

<script>
    function btoaUrlSafe(str) {
        return btoa(unescape(encodeURIComponent(str))).replace(/\+/g, '-').replace(/\//g, '_').replace(/=/g, '');
    }
window.copyShareLink = function(token) {
    const path = window.location.origin + '/lihat_berkas.php?token=' + token;
    navigator.clipboard.writeText(path);
    alert('Link publik berhasil disalin ke clipboard!');
}

window.shareFolderJurnal = function(tahun) {
    $.post('?ajax=create_share', {
        tipe: 'jurnal_tahun',
        sumber_id: tahun,
        label: 'Folder Jurnal Mengajar ' + tahun,
        data_json: ''
    }, function(res) {
        if(res.status === 'success') {
            copyShareLink(res.token);
        } else {
            alert('Gagal membagikan folder jurnal.');
        }
    }, 'json');
}

window.triggerGenerateJurnal = function(num, name) {
    const tahun = new Date().getFullYear();
    $.post('?ajax=generate_jurnal', {
        bulan: num,
        tahun: tahun,
        label: name
    }, function(res) {
        if(res.status === 'success') {
            window.location.reload();
        } else {
            alert('Gagal generate jurnal: ' + (res.message || 'Unknown error'));
        }
    }, 'json').fail(function(xhr) {
        alert('Terjadi kesalahan sistem: ' + xhr.responseText);
    });
}
</script>

<?php 
// Include standard guru footer at bottom if page loaded via router.php
// We check if guru_footer.php or guru_common_footer.php exists and include it
if (is_file(__DIR__ . '/guru_common_footer.php')) {
    include __DIR__ . '/guru_common_footer.php';
}
?>
<!-- Context Menu Custom -->
<div id="customContextMenu" class="dropdown-menu shadow-sm" style="display:none; position:absolute; z-index:9999;">
    <a class="dropdown-item" href="#" id="ctxRename"><i class="bi bi-pencil-square text-primary me-2"></i> Rename</a>
    <a class="dropdown-item" href="#" id="ctxCopy"><i class="bi bi-copy text-success me-2"></i> Copy</a>
    <div class="dropdown-divider"></div>
    <a class="dropdown-item text-danger" href="#" id="ctxDelete"><i class="bi bi-trash text-danger me-2"></i> Delete</a>
</div>

<script>
    function btoaUrlSafe(str) {
        return btoa(unescape(encodeURIComponent(str))).replace(/\+/g, '-').replace(/\//g, '_').replace(/=/g, '');
    }
let currentCtxItem = null;

$(document).on('contextmenu', '.drive-item', function(e) {
    e.preventDefault();
    currentCtxItem = {
        id: $(this).data('id'),
        type: $(this).data('type'),
        name: $(this).data('name')
    };
    
    $('#customContextMenu').css({
        display: 'block',
        left: e.pageX,
        top: e.pageY
    });
});

$(document).on('click', function() {
    $('#customContextMenu').hide();
});

$('#ctxRename').on('click', function(e) {
    e.preventDefault();
    if(currentCtxItem) {
        let newName = prompt('Rename item:', currentCtxItem.name);
        if(newName && newName !== currentCtxItem.name) {
            if (currentCtxItem.type === 'sertifikat_folder') {
                $.post('?ajax=sertifikat_rename_folder', {old_name: currentCtxItem.name, new_name: newName}, function(res) {
                    if(res.status === 'success') location.reload();
                    else alert('Gagal rename: ' + res.message);
                }, 'json');
            } else {
                $.post('?ajax=perangkat_rename_item', {id: currentCtxItem.id, nama_baru: newName}, function(res) {
                    if(res.status === 'success') loadPerangkatDrive();
                    else alert('Gagal rename: ' + res.message);
                }, 'json');
            }
        }
    }
});

$('#ctxCopy').on('click', function(e) {
    e.preventDefault();
    if(currentCtxItem) {
        if (currentCtxItem.type === 'sertifikat_folder') {
            $.post('?ajax=sertifikat_copy_folder', {folder_name: currentCtxItem.name}, function(res) {
                if(res.status === 'success') location.reload();
                else alert('Gagal copy: ' + res.message);
            }, 'json');
        } else {
            $.post('?ajax=perangkat_copy_item', {id: currentCtxItem.id}, function(res) {
                if(res.status === 'success') loadPerangkatDrive();
                else alert('Gagal copy: ' + res.message);
            }, 'json');
        }
    }
});

$('#ctxDelete').on('click', function(e) {
    e.preventDefault();
    if(currentCtxItem) {
        if (currentCtxItem.type === 'sertifikat_folder') {
            if(confirm('Hapus folder sertifikat ini beserta seluruh isinya?')) {
                $.post('?ajax=sertifikat_delete_folder', {folder_name: currentCtxItem.name}, function(res) {
                    if(res.status === 'success') location.reload();
                }, 'json');
            }
        } else if(currentCtxItem.type === 'folder') {
            deletePerangkatFolder(currentCtxItem.id, currentCtxItem.name);
        } else {
            deletePerangkatFile(currentCtxItem.id);
        }
    }
});
</script>
    </div> <!-- End desktop-center-column -->
</div> <!-- End app-shell -->
</body>
</html>


