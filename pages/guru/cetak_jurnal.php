<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION["no_induk"])) {
    header("location: ../../index.php?haruslogin");
    exit;
}

include "../../koneksi.php";
include "../../functions.php";
date_default_timezone_set('Asia/Jakarta');
        <th>No</th>
        <th>Tanggal</th>
        <th>Jam</th>
        <th>Kelas</th>
        <th>Mata Pelajaran</th>
        <th>Materi</th>
        <th>Siswa Absen</th>
        <th>Catatan</th>
      </tr>
    </thead>
    <tbody>
    <?php
    $sql = mysqli_query($conn, "SELECT m.*, a.kelas, a.nama_mapel, a.jam_mulai, a.jam_selesai 
      FROM tbl_materi m 
      JOIN tbl_mapel_ampu a ON m.id_mapel = a.id_mapel 
      WHERE $where
      ORDER BY m.tanggal ASC"); // diubah dari DESC ke ASC agar urut dari tanggal muda ke tua

    if (mysqli_num_rows($sql) > 0) {
      $no = 1;
      while ($data = mysqli_fetch_array($sql)) {
        echo "<tr>
          <td>$no</td>
          <td>" . tgl_indo($data['tanggal']) . "</td>
          <td>{$data['jam_mulai']} - {$data['jam_selesai']}</td>
          <td>{$data['kelas']}</td>
          <td>{$data['nama_mapel']}</td>
          <td>{$data['materi']}</td>
          <td>{$data['absen']}</td>
          <td>{$data['keterangan']}</td>
        </tr>";
        $no++;
      }
    } else {
      echo '<tr><td colspan="8" class="text-center text-danger">Data tidak ditemukan.</td></tr>';
    }
    ?>
    </tbody>
  </table>

  <!-- Tanda Tangan -->
  <table class="ttd mt-5">
    <tr>
      <td width="50%">
        Mengetahui,<br>
        Kepala Sekolah<br><br><br><br>
        <strong><u>(........................................)</u></strong><br>
        NIP. ....................................
      </td>
      <td width="50%">
        Sumber, <?= tgl_indo(date("Y-m-d")); ?><br>
        Guru Mata Pelajaran<br><br><br><br>
        <strong><u>(<?= $namaguru; ?>)</u></strong><br>
        NIP. <?= $nipguru; ?>
      </td>
    </tr>
  </table>

</div>
</body>
</html>
