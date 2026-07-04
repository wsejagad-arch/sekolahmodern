<?php

function tgl_indo($tanggal) {
	$bulan = array (
		1 =>   'Januari',
		'Februari',
		'Maret',
		'April',
		'Mei',
		'Juni',
		'Juli',
		'Agustus',
		'September',
		'Oktober',
		'November',
		'Desember'
	);
	$pecahkan = explode('-', $tanggal);
	
	// variabel pecahkan 0 = tanggal
	// variabel pecahkan 1 = bulan
	// variabel pecahkan 2 = tahun
 
	return $pecahkan[2] . ' ' . $bulan[ (int)$pecahkan[1] ] . ' ' . $pecahkan[0];
}

function data_lembaga() {
	include "koneksi.php";
	$sql = mysqli_query($conn, "SELECT * FROM tbl_setting");
	$nm = mysqli_fetch_array($sql);
	$datalembaga = array(
		"nmsekolah" => $nm['nama_sekolah'],
		"alamatlembaga" => $nm['alamat'],
		// alias untuk kompatibilitas lama
		"alamat" => $nm['alamat'],
		"nmpimpinan" => $nm['nama_pimpinan'],
		"nippimpinan" => $nm['nip_pimpinan'],
		"logo" => $nm['logo']
	);
	
	return $datalembaga;
}

function hitung_user() {
	include "koneksi.php";
	$count = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM tbl_user"));
	return $count;
}

function hitung_guru() {
	include "koneksi.php";
	$count = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM tbl_guru"));
	return $count;
}

function hitung_siswa() {
	include "koneksi.php";
	$count = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM tbl_siswa"));
	return $count;
}

function hitung_kelas() {
	include "koneksi.php";
	$count = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM tbl_kelas"));
	return $count;
}

function hitung_mapel() {
	include "koneksi.php";
	$count = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM tbl_mapel"));
	return $count;
}

function cek_user($username) {
	include "koneksi.php";
	$cek = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM tbl_user WHERE username='$username'"));
	if($cek > 0) {
		return False;
	} else {
		return True;
	}
}

function cek_guru($nip) {
	include "koneksi.php";
	$cek = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM tbl_guru WHERE no_induk='$nip'"));
	if($cek > 0) {
		return False;
	} else {
		return True;
	}
}

// fungsi untuk mengecek data siswa
function cek_siswa($noinduk) {
	include "koneksi.php";
	$cek = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM tbl_siswa WHERE no_induk='$noinduk'"));
	if($cek > 0) {
		return False;
	} else {
		return True;
	}
}

// fungsi untuk mengecek mapel ampu (jadwal)
function mapel_ampu($nip, $mapel, $hari, $kelas, $thnajaran) {
	include "koneksi.php";
	$cek = mysqli_num_rows(mysqli_query($conn, "SELECT no_induk FROM tbl_mapel_ampu WHERE no_induk='$nip' AND nama_mapel='$mapel' AND hari='$hari' AND kelas='$kelas' AND thn_ajaran='$thnajaran'"));
	if($cek > 0) {
		return False;
	} else {
		return True;
	}
}

// fungsi untuk mengecek hari dan jam
function jadwal_mapel($hari, $mulai, $selesai) {
	include "koneksi.php";
	$cek = mysqli_num_rows(mysqli_query($conn, "SELECT no_induk FROM tbl_mapel_ampu WHERE no_induk='$nip' AND nama_mapel='$mapel' AND hari='$hari' AND kelas='$kelas' AND thn_ajaran='$thnajaran'"));
	if($cek > 0) {
		return False;
	} else {
		return True;
	}
}

// fungsi untuk mengecek file upload foto guru
function cek_foto($namafile) {
    // Jika semua syarat terpenuhi
	$ekstensiFile = explode('.', $namafile);
    $ekstensiFile = strtolower(end($ekstensiFile));
    $namaFileBaru = uniqid();
    $namaFileBaru .= ".";
    $namaFileBaru .= $ekstensiFile;
	
	return $namaFileBaru;
}

// fungsi untuk mengubah nama hari menjadi indonesia
function ubah_nama_hari($tanggal) {
  $nama_hari_inggris = date("l", strtotime($tanggal));
  $daftar_nama_hari = array(
    'Sunday' => 'Minggu',
    'Monday' => 'Senin',
    'Tuesday' => 'Selasa',
    'Wednesday' => 'Rabu',
    'Thursday' => 'Kamis',
    'Friday' => 'Jumat',
    'Saturday' => 'Sabtu'
  );
  $nama_hari_indonesia = $daftar_nama_hari[$nama_hari_inggris];
  return $nama_hari_indonesia;
}

// fungsi untuk mengecek jika ada jadwal mengajar yang bentrok
function cek_jadwal_bentrok($jadwal_baru, $jadwal_lama) {
  foreach ($jadwal_lama as $jadwal) {
    if ($jadwal_baru['hari'] == $jadwal['hari'] && $jadwal_baru['kelas'] == $jadwal['kelas']) {
      $jam_mulai_baru = strtotime($jadwal_baru['jam_mulai']);
      $jam_selesai_baru = strtotime($jadwal_baru['jam_selesai']);
      $jam_mulai_lama = strtotime($jadwal['jam_mulai']);
      $jam_selesai_lama = strtotime($jadwal['jam_selesai']);

      if ($jam_mulai_baru >= $jam_mulai_lama && $jam_mulai_baru < $jam_selesai_lama) {
        return False;
      }
      if ($jam_selesai_baru > $jam_mulai_lama && $jam_selesai_baru <= $jam_selesai_lama) {
        return False;
      }
      if ($jam_mulai_baru <= $jam_mulai_lama && $jam_selesai_baru >= $jam_selesai_lama) {
        return False;
      }
    }
  }
  return True;
}

// fungsi untuk mengecek jika ada jam mengajar yang bentrok bagi ybs
function cek_jadwal_ybs($jadwal_baru, $jadwal_ybs) {
  foreach ($jadwal_ybs as $jadwal) {
    if ($jadwal_baru['hari'] == $jadwal['hari']) {
      $jam_mulai_baru = strtotime($jadwal_baru['jam_mulai']);
      $jam_selesai_baru = strtotime($jadwal_baru['jam_selesai']);
      $jam_mulai_lama = strtotime($jadwal['jam_mulai']);
      $jam_selesai_lama = strtotime($jadwal['jam_selesai']);

      if ($jam_mulai_baru >= $jam_mulai_lama && $jam_mulai_baru < $jam_selesai_lama) {
        return False;
      }
      if ($jam_selesai_baru > $jam_mulai_lama && $jam_selesai_baru <= $jam_selesai_lama) {
        return False;
      }
      if ($jam_mulai_baru <= $jam_mulai_lama && $jam_selesai_baru >= $jam_selesai_lama) {
        return False;
      }
    }
  }
  return True;
}

// fungsi untuk mengecek daftar kehadiran
function cek_kehadiran($tglskr, $kls, $nip) {
	include "koneksi.php";
	$sql = mysqli_query($conn, "SELECT * FROM tbl_kehadiran WHERE tanggal='$tglskr' AND kelas='$kls' AND no_induk='$nip' LIMIT 1");
	$jum = mysqli_num_rows($sql);
	if($jum > 0) {
		return true;
	} else {
		return false;
	}
}

?>
