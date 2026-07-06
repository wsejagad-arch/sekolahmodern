<?php
$file = 'pages/guru/dashboard_guru.php';
$content = file_get_contents($file);

$replaces = [
    'href="pages/guru/validasi-izin"' => 'href="pages/guru/validasi-izin.php"',
    'href="pages/guru/literasi"' => 'href="pages/guru/literasi.php"',
    'href="pages/guru/rekap-kehadiran"' => 'href="pages/guru/rekap-kehadiran.php"',
    'href="pages/guru/setting-jadwal"' => 'href="pages/guru/setting-jadwal.php"',
    'href="pages/guru/materi"' => 'href="pages/guru/materi.php"',
    'href="pages/guru/nilai"' => 'href="pages/guru/nilai.php"',
    'href="pages/guru/walikelas"' => 'href="pages/guru/walikelas.php"',
    'href="pages/guru/laporan-kelas"' => 'href="pages/guru/laporan-kelas.php"',
    'href="pages/guru/ekskul"' => 'href="pages/guru/ekskul.php"',
    'href="pages/guru/leger"' => 'href="pages/guru/leger.php"',
    'href="pages/guru/ekinerja"' => 'href="pages/guru/ekinerja.php"',
    'href="pages/guru/apresiasi-guru"' => 'href="pages/guru/apresiasi-guru.php"',
    'href="pages/guru/piagam-7kih"' => 'href="pages/guru/piagam-7kih.php"',
    'href="pages/guru/wks"' => 'href="pages/guru/wks.php"',

    'href="validasi-izin"' => 'href="pages/guru/validasi-izin.php"',
    'href="literasi.php"' => 'href="pages/guru/literasi.php"',
    'href="rekap-kehadiran"' => 'href="pages/guru/rekap-kehadiran.php"',
    'href="setting-jadwal"' => 'href="pages/guru/setting-jadwal.php"',
    'href="materi"' => 'href="pages/guru/materi.php"',
    'href="nilai"' => 'href="pages/guru/nilai.php"',
    'href="walikelas"' => 'href="pages/guru/walikelas.php"',
    'href="laporan-kelas"' => 'href="pages/guru/laporan-kelas.php"',
    'href="ekskul"' => 'href="pages/guru/ekskul.php"',
    'href="leger"' => 'href="pages/guru/leger.php"',
    'href="ekinerja"' => 'href="pages/guru/ekinerja.php"',
    'href="apresiasi-guru"' => 'href="pages/guru/apresiasi-guru.php"',
    'href="piagam-7kih"' => 'href="pages/guru/piagam-7kih.php"',
    'href="wks"' => 'href="pages/guru/wks.php"'
];

$content = str_replace(array_keys($replaces), array_values($replaces), $content);
file_put_contents($file, $content);
echo "Links fixed.\n";
