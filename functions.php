<?php

function tgl_indo($tanggal)
{
	if (empty($tanggal) || $tanggal === '0000-00-00') {
		return '-';
	}
	$bulan = array(
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

	if (count($pecahkan) < 3) {
		return $tanggal;
	}

	return $pecahkan[2] . ' ' . $bulan[(int)$pecahkan[1]] . ' ' . $pecahkan[0];
}

function data_lembaga()
{
	static $cached = null;
	if ($cached !== null) {
		return $cached;
	}

	$default = array(
		"nmsekolah" => "SIMANIS",
		"nama_aplikasi" => "SIMANIS",
		"alamatlembaga" => "",
		"alamat" => "",
		"nmpimpinan" => "",
		"nippimpinan" => "",
		"logo" => "logo dash.png",
		"maintenance_mode" => "0"
	);

	global $conn;
	if (!function_exists('mysqli_query') || !isset($conn) || !$conn) {
		$cached = $default;
		return $cached;
	}

	$whereSchool = '';
	if (function_exists('mt_column_exists') && $conn instanceof mysqli && mt_column_exists($conn, 'tbl_setting', 'id_sekolah')) {
		$idSekolah = function_exists('mt_current_school_id') ? mt_current_school_id() : 1;
		$whereSchool = " WHERE id_sekolah=" . (int)$idSekolah;
	}

	$sql = @mysqli_query($conn, "SELECT * FROM tbl_setting$whereSchool ORDER BY id DESC LIMIT 1");
	if (!$sql) {
		$cached = $default;
		return $cached;
	}

	$nm = @mysqli_fetch_array($sql);
	if (!is_array($nm)) {
		$cached = $default;
		return $cached;
	}

	$cached = array(
		"nmsekolah" => $nm['nama_sekolah'] ?? $default['nmsekolah'],
		"nama_aplikasi" => $nm['nama_aplikasi'] ?? $default['nama_aplikasi'],
		"alamatlembaga" => $nm['alamat'] ?? $default['alamatlembaga'],
		// alias untuk kompatibilitas lama
		"alamat" => $nm['alamat'] ?? $default['alamat'],
		"nmpimpinan" => $nm['nama_pimpinan'] ?? $default['nmpimpinan'],
		"nippimpinan" => $nm['nip_pimpinan'] ?? $default['nippimpinan'],
		"logo" => $nm['logo'] ?? $default['logo'],
		"maintenance_mode" => $nm['maintenance_mode'] ?? $default['maintenance_mode']
	);
	return $cached;
}

function hitung_user()
{
	include "koneksi.php";
	$where = function_exists('mt_column_exists') && $conn instanceof mysqli && mt_column_exists($conn, 'tbl_user', 'id_sekolah') ? " WHERE id_sekolah=" . mt_current_school_id() : "";
	$count = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM tbl_user$where"));
	return $count;
}

function hitung_guru()
{
	include "koneksi.php";
	$where = function_exists('mt_column_exists') && $conn instanceof mysqli && mt_column_exists($conn, 'tbl_guru', 'id_sekolah') ? " WHERE id_sekolah=" . mt_current_school_id() : "";
	$count = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM tbl_guru$where"));
	return $count;
}

function hitung_siswa()
{
	include "koneksi.php";
	$where = function_exists('mt_column_exists') && $conn instanceof mysqli && mt_column_exists($conn, 'tbl_siswa', 'id_sekolah') ? " WHERE id_sekolah=" . mt_current_school_id() : "";
	$count = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM tbl_siswa$where"));
	return $count;
}

function hitung_kelas()
{
	include "koneksi.php";
	$where = function_exists('mt_column_exists') && $conn instanceof mysqli && mt_column_exists($conn, 'tbl_kelas', 'id_sekolah') ? " WHERE id_sekolah=" . mt_current_school_id() : "";
	$count = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM tbl_kelas$where"));
	return $count;
}

function hitung_mapel()
{
	include "koneksi.php";
	$where = function_exists('mt_column_exists') && $conn instanceof mysqli && mt_column_exists($conn, 'tbl_mapel', 'id_sekolah') ? " WHERE id_sekolah=" . mt_current_school_id() : "";
	$count = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM tbl_mapel$where"));
	return $count;
}

function cek_user($username)
{
	include "koneksi.php";
	$where = function_exists('mt_column_exists') && $conn instanceof mysqli && mt_column_exists($conn, 'tbl_user', 'id_sekolah') ? " AND id_sekolah=" . mt_current_school_id() : "";
	$cek = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM tbl_user WHERE username='$username'$where"));
	if ($cek > 0) {
		return False;
	} else {
		return True;
	}
}

function cek_guru($nip)
{
	include "koneksi.php";
	$where = function_exists('mt_column_exists') && $conn instanceof mysqli && mt_column_exists($conn, 'tbl_guru', 'id_sekolah') ? " AND id_sekolah=" . mt_current_school_id() : "";
	$cek = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM tbl_guru WHERE no_induk='$nip'$where"));
	if ($cek > 0) {
		return False;
	} else {
		return True;
	}
}

// fungsi untuk mengecek data siswa
function cek_siswa($noinduk)
{
	include "koneksi.php";
	$where = function_exists('mt_column_exists') && $conn instanceof mysqli && mt_column_exists($conn, 'tbl_siswa', 'id_sekolah') ? " AND id_sekolah=" . mt_current_school_id() : "";
	$cek = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM tbl_siswa WHERE no_induk='$noinduk'$where"));
	if ($cek > 0) {
		return False;
	} else {
		return True;
	}
}

// fungsi untuk mengecek mapel ampu (jadwal)
function mapel_ampu($nip, $mapel, $hari, $kelas, $thnajaran)
{
	include "koneksi.php";
	$where = function_exists('mt_column_exists') && $conn instanceof mysqli && mt_column_exists($conn, 'tbl_mapel_ampu', 'id_sekolah') ? " AND id_sekolah=" . mt_current_school_id() : "";
	$cek = mysqli_num_rows(mysqli_query($conn, "SELECT no_induk FROM tbl_mapel_ampu WHERE no_induk='$nip' AND nama_mapel='$mapel' AND hari='$hari' AND kelas='$kelas' AND thn_ajaran='$thnajaran'$where"));
	if ($cek > 0) {
		return False;
	} else {
		return True;
	}
}

// fungsi untuk mengecek hari dan jam
function jadwal_mapel($hari, $mulai, $selesai)
{
	include "koneksi.php";
	$cek = mysqli_num_rows(mysqli_query($conn, "SELECT no_induk FROM tbl_mapel_ampu WHERE no_induk='$nip' AND nama_mapel='$mapel' AND hari='$hari' AND kelas='$kelas' AND thn_ajaran='$thnajaran'"));
	if ($cek > 0) {
		return False;
	} else {
		return True;
	}
}

// fungsi untuk mengecek file upload foto guru
function cek_foto($namafile)
{
	// Jika semua syarat terpenuhi
	$ekstensiFile = explode('.', $namafile);
	$ekstensiFile = strtolower(end($ekstensiFile));
	$namaFileBaru = uniqid();
	$namaFileBaru .= ".";
	$namaFileBaru .= $ekstensiFile;

	return $namaFileBaru;
}

// fungsi untuk mengubah nama hari menjadi indonesia
function ubah_nama_hari($tanggal)
{
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
function cek_jadwal_bentrok($jadwal_baru, $jadwal_lama)
{
	foreach ($jadwal_lama as $jadwal) {
		if ($jadwal_baru['hari'] == $jadwal['hari'] && $jadwal_baru['kelas'] == $jadwal['kelas']) {
			$jam_mulai_baru = strtotime($jadwal_baru['jam_mulai']);
			$jam_selesai_baru = strtotime($jadwal_baru['jam_selesai']);
			$jam_mulai_lama = strtotime($jadwal['jam_mulai']);
			$jam_selesai_lama = strtotime($jadwal['jam_selesai']);

			if ($jam_mulai_baru >= $jam_mulai_lama && $jam_mulai_baru < $jam_selesai_lama) {
				return $jadwal;
			}
			if ($jam_selesai_baru > $jam_mulai_lama && $jam_selesai_baru <= $jam_selesai_lama) {
				return $jadwal;
			}
			if ($jam_mulai_baru <= $jam_mulai_lama && $jam_selesai_baru >= $jam_selesai_lama) {
				return $jadwal;
			}
		}
	}
	return True;
}

// fungsi untuk mengecek jika ada jam mengajar yang bentrok bagi ybs
function cek_jadwal_ybs($jadwal_baru, $jadwal_ybs)
{
	foreach ($jadwal_ybs as $jadwal) {
		if ($jadwal_baru['hari'] == $jadwal['hari']) {
			$jam_mulai_baru = strtotime($jadwal_baru['jam_mulai']);
			$jam_selesai_baru = strtotime($jadwal_baru['jam_selesai']);
			$jam_mulai_lama = strtotime($jadwal['jam_mulai']);
			$jam_selesai_lama = strtotime($jadwal['jam_selesai']);

			if ($jam_mulai_baru >= $jam_mulai_lama && $jam_mulai_baru < $jam_selesai_lama) {
				return $jadwal;
			}
			if ($jam_selesai_baru > $jam_mulai_lama && $jam_selesai_baru <= $jam_selesai_lama) {
				return $jadwal;
			}
			if ($jam_mulai_baru <= $jam_mulai_lama && $jam_selesai_baru >= $jam_selesai_lama) {
				return $jadwal;
			}
		}
	}
	return True;
}

// fungsi untuk mengecek daftar kehadiran
function cek_kehadiran($tglskr, $kls, $nip)
{
	include "koneksi.php";
	$sql = mysqli_query($conn, "SELECT * FROM tbl_kehadiran WHERE tanggal='$tglskr' AND kelas='$kls' AND no_induk='$nip' LIMIT 1");
	$jum = mysqli_num_rows($sql);
	if ($jum > 0) {
		return true;
	} else {
		return false;
	}
}

// fungsi untuk format ukuran file
function formatBytes($size, $precision = 2)
{
	$base = log($size, 1024);
	$suffixes = array('B', 'KB', 'MB', 'GB', 'TB');
	return round(pow(1024, $base - floor($base)), $precision) . ' ' . $suffixes[floor($base)];
}

function route_url(string $path, array $queryParams = []): string
{
	$url = rtrim($path, '/');
	if (!empty($queryParams)) {
		$url .= '?' . http_build_query($queryParams);
	}
	return $url;
}

function route_home(string $page = ''): string
{
	if ($page === '') {
		return 'home';
	}
	return 'home/' . rawurlencode($page);
}

function route_profile_siswa(string $nis): string
{
	return 'detail-profil-siswa/' . rawurlencode($nis);
}

function route_detail_guru(string $id): string
{
	return 'detail-guru/' . rawurlencode($id);
}

function route_delete_siswa(string $nis): string
{
	return 'hapus-siswa/' . rawurlencode($nis);
}
