<?php
include "koneksi.php";

$bulan   = $_GET['bulan'] ?? date("Y-m");
$noInduk = $_GET['no_induk'] ?? "";
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Rekap Absensi Siswa</title>

  <!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Select2 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

  <!-- jQuery -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

  <!-- Select2 JS -->
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
</head>
<body class="container py-4">

<h3>Rekap Absensi Siswa</h3>

<form method="get" class="mb-4">
  <input type="hidden" name="page" value="rekap_absen_siswa">

  <label>Pilih Siswa:</label>
  <select name="no_induk" id="pilihSiswa" style="width:300px" required></select>

  <label class="ms-3">Bulan:</label>
  <input type="month" name="bulan" value="<?= $bulan ?>">

  <button type="submit" class="btn btn-primary btn-sm ms-2">Tampilkan</button>
</form>

<script>
$(document).ready(function() {
    $('#pilihSiswa').select2({
        placeholder: "Ketik nama siswa...",
        allowClear: true,
        ajax: {
            url: "/cari_siswa.php",  // absolut path
            type: "GET",
            dataType: "json",
            delay: 250,
            data: function (params) {
                return { q: params.term };
            },
            processResults: function (data) {
                return data;
            }
        }
    });

    // Jika sudah ada siswa terpilih (saat reload halaman)
    <?php if ($noInduk): ?>
    $.ajax({
        url: "/cari_siswa.php?q=<?= urlencode($noInduk) ?>",
        dataType: 'json'
    }).done(function(data) {
        if (data.results.length > 0) {
            var option = new Option(data.results[0].text, data.results[0].id, true, true);
            $('#pilihSiswa').append(option).trigger('change');
        }
    });
    <?php endif; ?>
});
</script>

<?php
if ($noInduk) {
    // ambil nama siswa
    $qSiswa = mysqli_query($conn, "SELECT nama_siswa, kelas FROM tbl_siswa WHERE no_induk='$noInduk' LIMIT 1");
    $siswa  = mysqli_fetch_assoc($qSiswa);

    echo "<h4>Rekap Absensi {$siswa['nama_siswa']} - {$siswa['kelas']} (".date("F Y", strtotime($bulan."-01")).")</h4>";

    // detail absensi
    $detail = mysqli_query($conn, "
        SELECT a.tanggal, ma.nama_mapel, g.nama_guru, a.status
        FROM tbl_absen a
        LEFT JOIN tbl_mapel_ampu ma ON a.id_mapel = ma.id_mapel
        LEFT JOIN tbl_guru g ON ma.no_induk = g.no_induk
        WHERE a.no_induk='$noInduk'
          AND DATE_FORMAT(a.tanggal, '%Y-%m')='$bulan'
        ORDER BY a.tanggal, ma.jam_mulai
    ");

    echo "<table class='table table-bordered'>
            <thead class='table-light'>
                <tr>
                    <th>Tanggal</th>
                    <th>Mapel</th>
                    <th>Guru</th>
                    <th>Status</th>
                </tr>
            </thead><tbody>";
    while($d = mysqli_fetch_assoc($detail)) {
        echo "<tr>
                <td>{$d['tanggal']}</td>
                <td>{$d['nama_mapel']}</td>
                <td>{$d['nama_guru']}</td>
                <td>{$d['status']}</td>
              </tr>";
    }
    echo "</tbody></table>";

    // rekap total
    $total = mysqli_query($conn, "
        SELECT 
          SUM(a.status='Hadir')  AS hadir,
          SUM(a.status='Ijin')   AS ijin,
          SUM(a.status='Sakit')  AS sakit,
          SUM(a.status='Dispen') AS dispen,
          SUM(a.status='Alpha')  AS alpha
        FROM tbl_absen a
        WHERE a.no_induk='$noInduk'
          AND DATE_FORMAT(a.tanggal, '%Y-%m')='$bulan'
    ");
    $rekap = mysqli_fetch_assoc($total);

    echo "<h5>Ringkasan:</h5>
          <ul>
            <li>Hadir: {$rekap['hadir']}</li>
            <li>Ijin: {$rekap['ijin']}</li>
            <li>Sakit: {$rekap['sakit']}</li>
            <li>Dispen: {$rekap['dispen']}</li>
            <li>Alpha: {$rekap['alpha']}</li>
          </ul>";
}
?>

</body>
</html>

