<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include "koneksi.php";

if (!isset($_SESSION["username"])) {
    header("location: index.php?haruslogin");
    exit;
}

// Cek apakah ada file yang diupload
if (!isset($_FILES['file_guru']) || $_FILES['file_guru']['error'] !== UPLOAD_ERR_OK) {
    $_SESSION['import_error'] = "File tidak dapat diupload. Silakan coba lagi.";
    header("location: home.php?page=import-guru");
    exit;
}

$file = $_FILES['file_guru'];
$fileName = $file['name'];
$fileTmpName = $file['tmp_name'];
$fileSize = $file['size'];
$fileError = $file['error'];

// Validasi ekstensi file
$allowedExtensions = ['xlsx', 'xls', 'csv'];
$fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

if (!in_array($fileExtension, $allowedExtensions)) {
    $_SESSION['import_error'] = "Format file tidak didukung. Gunakan file .xlsx, .xls, atau .csv";
    header("location: home.php?page=import-guru");
    exit;
}

// Validasi ukuran file (5MB)
if ($fileSize > 5 * 1024 * 1024) {
    $_SESSION['import_error'] = "Ukuran file terlalu besar. Maksimal 5MB.";
    header("location: home.php?page=import-guru");
    exit;
}

// Buat direktori upload jika belum ada
$uploadDir = 'uploads/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Pindahkan file ke direktori upload
$uploadPath = $uploadDir . uniqid() . '.' . $fileExtension;
if (!move_uploaded_file($fileTmpName, $uploadPath)) {
    $_SESSION['import_error'] = "Gagal mengupload file.";
    header("location: home.php?page=import-guru");
    exit;
}

// Proses file Excel/CSV
try {
    if ($fileExtension === 'csv') {
        // Parsing CSV
        $handle = fopen($uploadPath, "r");
        $rows = [];
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            $rows[] = $data;
        }
        fclose($handle);
    } else {
        // Cek apakah library SimpleXLSX tersedia
        if (class_exists('SimpleXLSX') || file_exists('vendor/autoload.php')) {
            // Gunakan Composer autoload jika tersedia
            if (file_exists('vendor/autoload.php')) {
                require_once 'vendor/autoload.php';
                
                // Gunakan PhpSpreadsheet
                $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader(ucfirst($fileExtension === 'xlsx' ? 'Xlsx' : 'Xls'));
                $spreadsheet = $reader->load($uploadPath);
                $worksheet = $spreadsheet->getActiveSheet();
                $rows = $worksheet->toArray();
            } else {
                // Fallback ke parsing manual sederhana untuk CSV
                $_SESSION['import_error'] = "Library Excel tidak tersedia. Silakan install PhpSpreadsheet atau gunakan format CSV.";
                header("location: home.php?page=import-guru");
                exit;
            }
        } else {
            $_SESSION['import_error'] = "Library Excel tidak tersedia. Silakan gunakan format CSV atau install PhpSpreadsheet.";
            header("location: home.php?page=import-guru");
            exit;
        }
    }

    // Hapus file upload setelah diproses
    unlink($uploadPath);

    // Validasi minimal ada header + 1 baris data
    if (count($rows) < 2) {
        $_SESSION['import_error'] = "File Excel kosong atau tidak memiliki data.";
        header("location: home.php?page=import-guru");
        exit;
    }

    // Ambil header (baris pertama)
    $header = $rows[0];
    
    // Validasi header yang diperlukan
    $requiredHeaders = ['NIP/NUPTK', 'Nama Guru', 'Status Kepegawaian', 'Status Keaktifan'];
    $headerMap = [];
    
    foreach ($requiredHeaders as $required) {
        $found = false;
        foreach ($header as $index => $col) {
            if (stripos($col, str_replace('/', '', $required)) !== false || 
                stripos(str_replace('/', '', $col), str_replace('/', '', $required)) !== false) {
                $headerMap[$required] = $index;
                $found = true;
                break;
            }
        }
        if (!$found) {
            $_SESSION['import_error'] = "Kolom '$required' tidak ditemukan dalam file Excel.";
            header("location: home.php?page=import-guru");
            exit;
        }
    }
    
    // Cek apakah user ingin mengganti data yang sudah ada
    $replaceData = isset($_POST['replaceData']);
    
    $successCount = 0;
    $errorCount = 0;
    $errors = [];
    
    // Begin transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Proses setiap baris data (mulai dari baris ke-2)
        for ($i = 1; $i < count($rows); $i++) {
            $row = $rows[$i];
            
            // Skip baris kosong
            if (empty(array_filter($row))) {
                continue;
            }
            
            // Ambil data sesuai mapping header
            $nip = isset($row[$headerMap['NIP/NUPTK']]) ? trim($row[$headerMap['NIP/NUPTK']]) : '';
            $namaGuru = isset($row[$headerMap['Nama Guru']]) ? trim($row[$headerMap['Nama Guru']]) : '';
            $statusKepegawaian = isset($row[$headerMap['Status Kepegawaian']]) ? trim($row[$headerMap['Status Kepegawaian']]) : '';
            $statusKeaktifan = isset($row[$headerMap['Status Keaktifan']]) ? trim($row[$headerMap['Status Keaktifan']]) : '';
            
            // Ambil data tambahan jika ada
            $email = '';
            $telepon = '';
            
            foreach ($header as $index => $col) {
                if (stripos($col, 'email') !== false && isset($row[$index])) {
                    $email = trim($row[$index]);
                }
                if (stripos($col, 'telepon') !== false || stripos($col, 'hp') !== false || stripos($col, 'phone') !== false) {
                    if (isset($row[$index])) {
                        $telepon = trim($row[$index]);
                    }
                }
            }
            
            // Validasi data wajib
            if (empty($namaGuru)) {
                $errors[] = "Baris " . ($i + 1) . ": Nama guru tidak boleh kosong";
                $errorCount++;
                continue;
            }
            
            // Validasi status kepegawaian
            $validStatusKepegawaian = ['PNS', 'CPNS', 'GTT/PTT', 'GTT', 'PTT', 'Honorer'];
            if (!empty($statusKepegawaian) && !in_array($statusKepegawaian, $validStatusKepegawaian)) {
                $statusKepegawaian = 'Honorer'; // Default
            }
            
            // Validasi status keaktifan
            if (empty($statusKeaktifan)) {
                $statusKeaktifan = 'Aktif';
            } else {
                $statusKeaktifan = (stripos($statusKeaktifan, 'aktif') !== false) ? 'Aktif' : 'Tidak Aktif';
            }
            
            // Cek apakah guru sudah ada berdasarkan NIP atau nama
            $checkSql = "SELECT id_guru FROM tbl_guru WHERE ";
            $checkParams = [];
            
            if (!empty($nip)) {
                $checkSql .= "no_induk = ? OR nama_guru = ?";
                $checkParams = [$nip, $namaGuru];
            } else {
                $checkSql .= "nama_guru = ?";
                $checkParams = [$namaGuru];
            }
            
            $checkStmt = mysqli_prepare($conn, $checkSql);
            if (!empty($nip)) {
                mysqli_stmt_bind_param($checkStmt, "ss", $nip, $namaGuru);
            } else {
                mysqli_stmt_bind_param($checkStmt, "s", $namaGuru);
            }
            mysqli_stmt_execute($checkStmt);
            $checkResult = mysqli_stmt_get_result($checkStmt);
            
            if (mysqli_num_rows($checkResult) > 0) {
                // Guru sudah ada
                if ($replaceData) {
                    // Update data guru
                    $updateSql = "UPDATE tbl_guru SET no_induk = ?, nama_guru = ?, status_kepegawaian = ?, status = ?, email = ?, telepon = ? WHERE " . 
                                (!empty($nip) ? "no_induk = ? OR nama_guru = ?" : "nama_guru = ?");
                    
                    $updateStmt = mysqli_prepare($conn, $updateSql);
                    if (!empty($nip)) {
                        mysqli_stmt_bind_param($updateStmt, "ssssssss", $nip, $namaGuru, $statusKepegawaian, $statusKeaktifan, $email, $telepon, $nip, $namaGuru);
                    } else {
                        mysqli_stmt_bind_param($updateStmt, "sssssss", $nip, $namaGuru, $statusKepegawaian, $statusKeaktifan, $email, $telepon, $namaGuru);
                    }
                    
                    if (mysqli_stmt_execute($updateStmt)) {
                        $successCount++;
                    } else {
                        $errors[] = "Baris " . ($i + 1) . ": Gagal mengupdate data - " . mysqli_error($conn);
                        $errorCount++;
                    }
                } else {
                    $errors[] = "Baris " . ($i + 1) . ": Guru '$namaGuru' sudah ada (centang 'Replace data' untuk mengganti)";
                    $errorCount++;
                }
            } else {
                // Insert guru baru
                $insertSql = "INSERT INTO tbl_guru (no_induk, nama_guru, status_kepegawaian, status, email, telepon) VALUES (?, ?, ?, ?, ?, ?)";
                $insertStmt = mysqli_prepare($conn, $insertSql);
                mysqli_stmt_bind_param($insertStmt, "ssssss", $nip, $namaGuru, $statusKepegawaian, $statusKeaktifan, $email, $telepon);
                
                if (mysqli_stmt_execute($insertStmt)) {
                    $successCount++;
                } else {
                    $errors[] = "Baris " . ($i + 1) . ": Gagal menambah data - " . mysqli_error($conn);
                    $errorCount++;
                }
            }
        }
        
        // Commit transaction jika tidak ada error kritis
        if ($errorCount < count($rows) - 1) {
            mysqli_commit($conn);
            
            // Set pesan sukses
            $_SESSION['import_success'] = "Import berhasil! $successCount data guru berhasil diproses.";
            if ($errorCount > 0) {
                $_SESSION['import_warnings'] = $errors;
            }
        } else {
            mysqli_rollback($conn);
            $_SESSION['import_error'] = "Import gagal! Terlalu banyak error.";
            $_SESSION['import_errors'] = $errors;
        }
        
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $_SESSION['import_error'] = "Terjadi kesalahan saat memproses data: " . $e->getMessage();
    }
    
} catch (Exception $e) {
    // Hapus file jika ada error
    if (file_exists($uploadPath)) {
        unlink($uploadPath);
    }
    $_SESSION['import_error'] = "Gagal membaca file Excel: " . $e->getMessage();
}

// Redirect kembali ke halaman import
header("location: home.php?page=import-guru");
exit;
?>