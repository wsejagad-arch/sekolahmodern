<?php
// Test file untuk mengecek kelasList
require_once 'koneksi.php';

echo "=== TESTING KELAS LIST ===" . PHP_EOL;

$nipguru = '0029';  // Guru test
echo "Testing for guru: $nipguru" . PHP_EOL;

// Simulasi query yang sama seperti di guru.php
$kelasList = [];
$qKelas = mysqli_query($conn, "SELECT DISTINCT kelas FROM tbl_mapel_ampu WHERE no_induk='".$nipguru."' ORDER BY kelas");

echo "Query: SELECT DISTINCT kelas FROM tbl_mapel_ampu WHERE no_induk='".$nipguru."' ORDER BY kelas" . PHP_EOL;

if (!$qKelas) {
    echo "❌ Query failed: " . mysqli_error($conn) . PHP_EOL;
} else {
    while($rK = mysqli_fetch_assoc($qKelas)) { 
        $kelasList[] = $rK['kelas']; 
    }
    
    echo "Found " . count($kelasList) . " classes:" . PHP_EOL;
    foreach($kelasList as $kelas) {
        echo "- " . $kelas . PHP_EOL;
    }
}

// Test juga wali kelas
echo PHP_EOL . "=== TESTING WALI KELAS ===" . PHP_EOL;

$nipEsc = mysqli_real_escape_string($conn, $nipguru);
$resWK  = mysqli_query($conn, "SELECT wk.id_kelas, k.kelas FROM tbl_wali_kelas wk JOIN tbl_kelas k ON wk.id_kelas=k.id_kelas WHERE wk.nip_wali='".$nipEsc."' LIMIT 1");

$waliKelasNama = null;
if ($resWK && mysqli_num_rows($resWK) > 0) {
    $rowWK = mysqli_fetch_assoc($resWK);
    $waliKelasNama = $rowWK['kelas'];
    echo "✅ Found wali kelas: " . $waliKelasNama . PHP_EOL;
} else {
    // Fallback ke kolom legacy tbl_kelas.nip_wali bila tabel tbl_wali_kelas kosong
    $resLegacy = mysqli_query($conn, "SELECT id_kelas, kelas FROM tbl_kelas WHERE nip_wali='".$nipEsc."' LIMIT 1");
    if ($resLegacy && mysqli_num_rows($resLegacy) > 0) {
        $rowL = mysqli_fetch_assoc($resLegacy);
        $waliKelasNama = $rowL['kelas'];
        echo "✅ Found wali kelas (legacy): " . $waliKelasNama . PHP_EOL;
    } else {
        echo "❌ No wali kelas found" . PHP_EOL;
    }
}

$kelasWali = $waliKelasNama;
echo "Default selected class: " . ($kelasWali ?? 'None') . PHP_EOL;

echo PHP_EOL . "=== DROPDOWN HTML SIMULATION ===" . PHP_EOL;
echo '<select class="form-select" id="selectKelasP" name="kelas" required>' . PHP_EOL;
echo '  <option value="">-- Pilih Kelas --</option>' . PHP_EOL;
if (!empty($kelasList)) {
    foreach ($kelasList as $kelas) {
        $selected = ($kelas == $kelasWali) ? ' selected' : '';
        echo '  <option value="' . htmlspecialchars($kelas) . '"' . $selected . '>' . htmlspecialchars($kelas) . '</option>' . PHP_EOL;
    }
} else {
    echo '  <!-- No classes found -->' . PHP_EOL;
}
echo '</select>' . PHP_EOL;
?>