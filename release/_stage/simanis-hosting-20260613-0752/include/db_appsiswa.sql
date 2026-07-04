-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 23, 2023 at 06:41 AM
-- Server version: 10.4.24-MariaDB
-- PHP Version: 8.0.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_appsiswa`
--

-- --------------------------------------------------------

--
-- Table structure for table `tbl_guru`
--

CREATE TABLE `tbl_guru` (
  `id_guru` int(12) NOT NULL,
  `no_induk` varchar(25) NOT NULL,
  `nama_guru` varchar(150) NOT NULL,
  `status_kepegawaian` varchar(10) NOT NULL,
  `foto` varchar(100) NOT NULL,
  `status` varchar(15) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_hari`
--

CREATE TABLE `tbl_hari` (
  `id_hari` int(10) NOT NULL,
  `nama_hari` varchar(15) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `tbl_hari`
--

INSERT INTO `tbl_hari` (`id_hari`, `nama_hari`) VALUES
(1, 'SENIN'),
(2, 'SELASA'),
(3, 'RABU'),
(4, 'KAMIS'),
(5, 'JUMAT'),
(6, 'SABTU'),
(7, 'MINGGU');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_kehadiran`
--

CREATE TABLE `tbl_kehadiran` (
  `id_kehadiran` int(10) NOT NULL,
  `tanggal` date NOT NULL,
  `no_induk` varchar(25) NOT NULL,
  `nama_guru` varchar(150) NOT NULL,
  `nama_mapel` varchar(100) NOT NULL,
  `kelas` varchar(100) NOT NULL,
  `nama_ketua_kelas` varchar(100) NOT NULL,
  `status_kehadiran` tinyint(1) NOT NULL,
  `catatan` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `tbl_kehadiran`
--

INSERT INTO `tbl_kehadiran` (`id_kehadiran`, `tanggal`, `no_induk`, `nama_guru`, `nama_mapel`, `kelas`, `nama_ketua_kelas`, `status_kehadiran`, `catatan`) VALUES
(22, '2023-03-23', '199801012000111002', 'Dendi Nasrulloh', 'EKONOMI', 'XI IPA 1', 'Lionel Coding', 0, 'Belum memberi kabar'),
(23, '2023-03-23', '199409102015111002', 'Ahmad Coding', 'INFORMATIKA', 'XI IPA 1', 'Lionel Coding', 1, '');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_kelas`
--

CREATE TABLE `tbl_kelas` (
  `id_kelas` int(5) NOT NULL,
  `kelas` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `tbl_kelas`
--

INSERT INTO `tbl_kelas` (`id_kelas`, `kelas`) VALUES
(20, 'X 1'),
(21, 'X 2'),
(22, 'X 3'),
(23, 'X 4'),
(24, 'X 5'),
(25, 'X 6'),
(26, 'X 7'),
(27, 'X 8'),
(28, 'X 9'),
(29, 'X 10'),
(30, 'X 11'),
(31, 'X 12'),
(35, 'XI BAHASA 1'),
(36, 'XI IPA 1'),
(37, 'XI IPA 2'),
(38, 'XI IPA 3'),
(39, 'XI IPA 4'),
(40, 'XI IPS 1'),
(41, 'XI IPS 2'),
(42, 'XI IPS 3'),
(43, 'XI IPS 4'),
(44, 'XI IPS 5'),
(45, 'XII IPA 1'),
(46, 'XII IPA 2'),
(47, 'XII IPA 3'),
(48, 'XII IPA 4'),
(49, 'XII IPA 5'),
(50, 'XII IPA 6'),
(51, 'XII IPS 1'),
(52, 'XII IPS 2'),
(53, 'XII IPS 3'),
(54, 'XII IPS 4');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_log`
--

CREATE TABLE `tbl_log` (
  `id_log` int(100) NOT NULL,
  `waktu` datetime NOT NULL,
  `isi_log` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_mapel`
--

CREATE TABLE `tbl_mapel` (
  `id_mapel` int(10) NOT NULL,
  `nama_mapel` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `tbl_mapel`
--

INSERT INTO `tbl_mapel` (`id_mapel`, `nama_mapel`) VALUES
(10, 'BIOLOGI'),
(11, 'BAHASA ARAB'),
(12, 'KIMIA'),
(13, 'GEOGRAFI'),
(14, 'BAHASA INDONESIA'),
(15, 'FISIKA'),
(16, 'EKONOMI'),
(17, 'PKN'),
(18, 'BAHASA INGGRIS'),
(19, 'MATEMATIKA'),
(20, 'PENJASORKES'),
(21, 'AKIDAH AKHLAK'),
(22, 'AL-QURAN HADITS'),
(23, 'INFORMATIKA'),
(24, 'FIQIH'),
(25, 'SEJARAH'),
(26, 'SOSIOLOGI'),
(27, 'ANTROPOLOGI'),
(28, 'BAHASA DAN SASTRA JEPANG'),
(29, 'PRAKARYA DAN KEWIRAUSAHAAN'),
(30, 'SENI BUDAYA'),
(31, 'BAHASA SUNDA'),
(32, 'SEJARAH KEBUDAYAAN ISLAM'),
(33, 'BP/BK'),
(34, 'SEJARAH INDONESIA'),
(35, 'OTOMOTIF'),
(36, 'TATA BOGA'),
(37, 'TATA BUSANA'),
(38, 'TKJ');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_mapel_ampu`
--

CREATE TABLE `tbl_mapel_ampu` (
  `id_mapel` int(10) NOT NULL,
  `id_guru` int(15) NOT NULL,
  `no_induk` varchar(25) NOT NULL,
  `nama_mapel` varchar(100) NOT NULL,
  `hari` varchar(50) NOT NULL,
  `jam_mulai` time NOT NULL,
  `jam_selesai` time NOT NULL,
  `kelas` varchar(100) NOT NULL,
  `thn_ajaran` varchar(15) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_materi`
--

CREATE TABLE `tbl_materi` (
  `id_materi` int(10) NOT NULL,
  `id_mapel` int(10) NOT NULL,
  `no_induk` varchar(25) NOT NULL,
  `nama_mapel` varchar(100) NOT NULL,
  `date` date NOT NULL,
  `kelas` varchar(100) NOT NULL,
  `file_materi` varchar(200) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_pengguna`
--

CREATE TABLE `tbl_pengguna` (
  `no_induk` varchar(25) NOT NULL,
  `password` varchar(225) NOT NULL,
  `hak_akses` enum('1','2','3') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `tbl_pengguna`
--

INSERT INTO `tbl_pengguna` (`no_induk`, `password`, `hak_akses`) VALUES
('10111112', '65ed6399f0924b2da2814015a0628e0f', '3'),
('196612022014111001', 'ad129d55d31f0de00b96b4988e9df653', '2'),
('196708012005011006', '2b254858c8a5d784e23bdcad855fad39', '2'),
('196804081998031001', 'ea323e3e5c36c5c8b6c5b7e5854574b0', '2'),
('196901232005012003', '4b432417f310f17ceba6341088fef58e', '2'),
('196912041997032002', 'a369e3f1d45904a6cc00e21e45c36d03', '2'),
('197008032007011043', 'd86ffb72afa94e2af856cc2d823d788e', '2'),
('197009301999052001', 'bfadf62b297e3fc8b8479991d7555918', '2'),
('197207112005011004', '52939cb1d795fbf612c6931a94e6412b', '2'),
('197209231997032003', '00c64d0b6b0da1916d32003b2e802430', '2'),
('197210061999032002', '911e74e0fab3c832eee17e039bfad79c', '2'),
('197301172005011004', 'ea3b81f69b7a4f5b987cf4b1141ead1c', '2'),
('197305132005012004', '2ab802295af4a58ec8518569ae1e7139', '2'),
('197410162009012004', '0dde9a33854c82ba86cf8648b2cefa0e', '2'),
('197501022006041023', '5180492a032815b7df384caf21a187e1', '2'),
('197601202006042007', '9a090dd8169d4b0469922326edfae156', '2'),
('197907022006041014', '01dd036d6cd6000cffb10c3468e32abb', '2'),
('197911012006042029', '072ab69e5d58e0ad3ce57283a381be83', '2'),
('198003192005012006', '534c8e64ef36aaf8c86663275e94a551', '2'),
('198005262014111002', '84dcd151d069413a3c9a03e61fdb410c', '2'),
('198106102006041015', '7995e3494feb780632a0cfadfe5e6ad6', '2'),
('198108072005012006', '68a589008b01d96f2720c738c8546f16', '2'),
('198112012005012005', 'c17fec6077fb16318305a1518f558864', '2'),
('199409102015111002', '8ba5a924e5f52896de2889b36fdca7df', '2'),
('199801012000111002', '271352131cbfb331f607443de1e34c7e', '2'),
('20210040028', '1580c69165ccfbb827f89606a02c7ef5', '3'),
('20210040029', '6b19e3d09e68e5816b81b32a56264894', '3');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_setting`
--

CREATE TABLE `tbl_setting` (
  `id` int(5) NOT NULL,
  `nama_sekolah` varchar(200) NOT NULL,
  `alamat` varchar(200) NOT NULL,
  `logo` varchar(100) NOT NULL,
  `nama_pimpinan` varchar(100) NOT NULL,
  `nip_pimpinan` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `tbl_setting`
--

INSERT INTO `tbl_setting` (`id`, `nama_sekolah`, `alamat`, `logo`, `nama_pimpinan`, `nip_pimpinan`) VALUES
(1, 'SMA Coding', 'Jl. PHP No. 8 Kota Bootstrap Provinsi Javascript', '641ad4d480474.png', 'Drs. Tailwind, M.Pd', '197210112000111002');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_siswa`
--

CREATE TABLE `tbl_siswa` (
  `no_induk` varchar(25) NOT NULL,
  `nama_siswa` varchar(150) NOT NULL,
  `kelas` varchar(100) NOT NULL,
  `status` enum('Aktif','Non-Aktif','Lulus') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_thn_ajaran`
--

CREATE TABLE `tbl_thn_ajaran` (
  `id_thn` int(10) NOT NULL,
  `tahun` varchar(15) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `tbl_thn_ajaran`
--

INSERT INTO `tbl_thn_ajaran` (`id_thn`, `tahun`) VALUES
(12, '2022/2023');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_user`
--

CREATE TABLE `tbl_user` (
  `id_user` int(3) NOT NULL,
  `username` varchar(25) NOT NULL,
  `password` varchar(100) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `hak_akses` enum('1','2','3') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `tbl_user`
--

INSERT INTO `tbl_user` (`id_user`, `username`, `password`, `nama`, `hak_akses`) VALUES
(1, 'admin', '21232f297a57a5a743894a0e4a801fc3', 'Super Admin', '1');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `tbl_guru`
--
ALTER TABLE `tbl_guru`
  ADD PRIMARY KEY (`id_guru`);

--
-- Indexes for table `tbl_hari`
--
ALTER TABLE `tbl_hari`
  ADD PRIMARY KEY (`id_hari`);

--
-- Indexes for table `tbl_kehadiran`
--
ALTER TABLE `tbl_kehadiran`
  ADD PRIMARY KEY (`id_kehadiran`);

--
-- Indexes for table `tbl_kelas`
--
ALTER TABLE `tbl_kelas`
  ADD PRIMARY KEY (`id_kelas`);

--
-- Indexes for table `tbl_log`
--
ALTER TABLE `tbl_log`
  ADD PRIMARY KEY (`id_log`);

--
-- Indexes for table `tbl_mapel`
--
ALTER TABLE `tbl_mapel`
  ADD PRIMARY KEY (`id_mapel`);

--
-- Indexes for table `tbl_mapel_ampu`
--
ALTER TABLE `tbl_mapel_ampu`
  ADD PRIMARY KEY (`id_mapel`);

--
-- Indexes for table `tbl_materi`
--
ALTER TABLE `tbl_materi`
  ADD PRIMARY KEY (`id_materi`);

--
-- Indexes for table `tbl_pengguna`
--
ALTER TABLE `tbl_pengguna`
  ADD PRIMARY KEY (`no_induk`);

--
-- Indexes for table `tbl_setting`
--
ALTER TABLE `tbl_setting`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbl_siswa`
--
ALTER TABLE `tbl_siswa`
  ADD PRIMARY KEY (`no_induk`);

--
-- Indexes for table `tbl_thn_ajaran`
--
ALTER TABLE `tbl_thn_ajaran`
  ADD PRIMARY KEY (`id_thn`);

--
-- Indexes for table `tbl_user`
--
ALTER TABLE `tbl_user`
  ADD PRIMARY KEY (`id_user`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `tbl_guru`
--
ALTER TABLE `tbl_guru`
  MODIFY `id_guru` int(12) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `tbl_hari`
--
ALTER TABLE `tbl_hari`
  MODIFY `id_hari` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `tbl_kehadiran`
--
ALTER TABLE `tbl_kehadiran`
  MODIFY `id_kehadiran` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `tbl_kelas`
--
ALTER TABLE `tbl_kelas`
  MODIFY `id_kelas` int(5) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT for table `tbl_log`
--
ALTER TABLE `tbl_log`
  MODIFY `id_log` int(100) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_mapel`
--
ALTER TABLE `tbl_mapel`
  MODIFY `id_mapel` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `tbl_mapel_ampu`
--
ALTER TABLE `tbl_mapel_ampu`
  MODIFY `id_mapel` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `tbl_materi`
--
ALTER TABLE `tbl_materi`
  MODIFY `id_materi` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `tbl_thn_ajaran`
--
ALTER TABLE `tbl_thn_ajaran`
  MODIFY `id_thn` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `tbl_user`
--
ALTER TABLE `tbl_user`
  MODIFY `id_user` int(3) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
