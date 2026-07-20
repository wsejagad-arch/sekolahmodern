<?php
session_start();
include "koneksi.php";
include "SimpleXLSX.php";
include "SimpleXLSXGen.php";

if (!isset($_SESSION['no_induk'])) {
    header("Location: login.php");
    exit();
}

// Baca daftar guru dari database
$qGuru = mysqli_query($conn, "SELECT no_induk, nama_guru FROM tbl_guru");
$gurus = [];
while ($r = mysqli_fetch_assoc($qGuru)) {
    $gurus[] = $r;
}

// Baca daftar kelas baku dari database
$qKelas = mysqli_query($conn, "SELECT nama_kelas FROM tbl_kelas");
$kelas_baku = [];
if ($qKelas) {
    while ($r = mysqli_fetch_assoc($qKelas)) {
        $kelas_baku[] = $r['nama_kelas'];
    }
}

function find_no_induk($nama_pdf, $gurus) {
    // Bersihkan nama
    $name_clean = explode(',', $nama_pdf)[0];
    $name_clean = str_ireplace(['Drs.', 'Dra.', 'H.', 'Hj.', 'Dr.', 'M.Pd', 'S.Pd'], '', $name_clean);
    $name_clean = trim($name_clean);
    
    $words = explode(' ', $name_clean);
    $search_name = strtolower($words[0]);
    if (isset($words[1]) && strlen($words[1]) > 2) {
        $search_name .= ' ' . strtolower($words[1]);
    }
    
    foreach ($gurus as $g) {
        if (strpos(strtolower($g['nama_guru']), $search_name) !== false) {
            return $g['no_induk'];
        }
    }
    return '';
}

function find_kelas_baku($kelas_pdf, $kelas_baku) {
    if (empty($kelas_baku)) return $kelas_pdf;
    
    // Coba exact match dulu
    foreach ($kelas_baku as $kb) {
        if (strtolower(trim($kb)) == strtolower(trim($kelas_pdf))) return $kb;
    }
    
    // Pembersihan umum misal "XII F-6" -> "XII Fase F 6" atau sebaliknya
    // Ini heuristik sederhana
    $clean_pdf = preg_replace('/[^A-Za-z0-9]/', '', $kelas_pdf);
    foreach ($kelas_baku as $kb) {
        $clean_kb = preg_replace('/[^A-Za-z0-9]/', '', $kb);
        if (strtolower($clean_pdf) == strtolower($clean_kb)) {
            return $kb;
        }
    }
    
    return $kelas_pdf; // Kembalikan aslinya jika tidak nemu
}

if (isset($_POST['fix_excel'])) {
    $filename = $_FILES['file']['tmp_name'];
    $ext = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
    
    if ($ext != 'xlsx') {
        echo "<script>alert('Harap upload file Excel (.xlsx)'); window.location.href='/home.php?page=import-jadwal';</script>";
        exit();
    }

    $xlsx = SimpleXLSX::parse($filename);
    if ($xlsx) {
        $rows = $xlsx->rows();
        $new_rows = [];
        
        foreach ($rows as $index => $row) {
            if ($index == 0) {
                $new_rows[] = $row; // Header
                continue;
            }
            
            if (empty(array_filter($row))) continue;
            
            $no_induk   = $row[0] ?? '';
            $nama_guru  = $row[1] ?? '';
            $nama_mapel = $row[2] ?? '';
            $kelas      = $row[3] ?? '';
            
            if (empty($no_induk) && !empty($nama_guru)) {
                $row[0] = find_no_induk($nama_guru, $gurus);
            }
            
            if (!empty($kelas)) {
                $row[3] = find_kelas_baku($kelas, $kelas_baku);
            }
            
            $new_rows[] = $row;
        }
        
        $new_xlsx = SimpleXLSXGen::fromArray($new_rows);
        // Simpan file yang telah diperbaiki di server
        $fixedPath = __DIR__ . '/jadwal_fixed.xlsx';
        $new_xlsx->writeToFile($fixedPath);
        // Set flag in session for notification
        $_SESSION['fix_success'] = true;
        // Redirect kembali ke halaman import jadwal
        header('Location: /home.php?page=import-jadwal');
        exit();
    }
}
?>
