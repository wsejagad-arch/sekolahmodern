SELECT status, COUNT(*) AS jml
FROM tbl_absen
WHERE no_induk='$nisEsc' AND kelas='$klsEsc' AND DATE_FORMAT(tanggal,'%Y-%m')='$bulanEsc'
GROUP BY status