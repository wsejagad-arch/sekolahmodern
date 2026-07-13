import re

with open('pages/guru/setting-jadwal.php', 'r', encoding='utf-8') as f:
    content = f.read()

new_func = """function sj_has_conflict(mysqli $conn, string $nip, string $hari, string $mulai, string $selesai, int $excludeId = 0)
{
    $nipEsc = mysqli_real_escape_string($conn, $nip);
    $hariEsc = mysqli_real_escape_string($conn, $hari);
    $mulaiEsc = mysqli_real_escape_string($conn, $mulai);
    $selesaiEsc = mysqli_real_escape_string($conn, $selesai);
    $excludeSql = $excludeId > 0 ? "AND id_mapel <> $excludeId" : '';
    $q = @mysqli_query($conn, "
        SELECT id_mapel, kelas
        FROM tbl_mapel_ampu
        WHERE no_induk='$nipEsc'
          AND hari='$hariEsc'
          $excludeSql
          AND jam_mulai < '$selesaiEsc'
          AND jam_selesai > '$mulaiEsc'
        LIMIT 1
    ");
    if ($q && mysqli_num_rows($q) > 0) {
        return mysqli_fetch_assoc($q);
    }
    return false;
}

function sj_class_has_conflict(mysqli $conn, string $kelas, string $hari, string $mulai, string $selesai, int $excludeId = 0)
{
    $kelasEsc = mysqli_real_escape_string($conn, $kelas);
    $hariEsc = mysqli_real_escape_string($conn, $hari);
    $mulaiEsc = mysqli_real_escape_string($conn, $mulai);
    $selesaiEsc = mysqli_real_escape_string($conn, $selesai);
    $excludeSql = $excludeId > 0 ? "AND m.id_mapel <> $excludeId" : '';
    
    $q = @mysqli_query($conn, "
        SELECT m.id_mapel, m.kelas, g.nama_guru
        FROM tbl_mapel_ampu m
        LEFT JOIN tbl_guru g ON m.no_induk = g.no_induk
        WHERE m.kelas='$kelasEsc'
          AND m.hari='$hariEsc'
          $excludeSql
          AND m.jam_mulai < '$selesaiEsc'
          AND m.jam_selesai > '$mulaiEsc'
        LIMIT 1
    ");
    if ($q && mysqli_num_rows($q) > 0) {
        return mysqli_fetch_assoc($q);
    }
    return false;
}"""

content = re.sub(r"function sj_has_conflict\(.*?return false;\n}", new_func, content, flags=re.DOTALL)

old_check = """        } elseif ($conflict = sj_has_conflict($conn, $nip, $hari, $jamMulai, $jamSelesai, $action === 'update' ? $id : 0)) {
            $flash = 'Jadwal bentrok dengan jadwal Anda sendiri di kelas ' . $conflict['kelas'] . '.';
            $flashType = 'danger';
        } else {"""

new_check = """        } elseif ($conflict = sj_has_conflict($conn, $nip, $hari, $jamMulai, $jamSelesai, $action === 'update' ? $id : 0)) {
            $flash = 'Jadwal bentrok dengan jadwal Anda sendiri di kelas ' . $conflict['kelas'] . '.';
            $flashType = 'danger';
        } elseif ($classConflict = sj_class_has_conflict($conn, $kelas, $hari, $jamMulai, $jamSelesai, $action === 'update' ? $id : 0)) {
            $flash = 'Jadwal bentrok! Kelas ' . $kelas . ' sudah digunakan oleh guru ' . ($classConflict['nama_guru'] ?? 'lain') . ' pada waktu tersebut.';
            $flashType = 'danger';
        } else {"""

content = content.replace(old_check, new_check)

with open('pages/guru/setting-jadwal.php', 'w', encoding='utf-8') as f:
    f.write(content)

print("Done")
