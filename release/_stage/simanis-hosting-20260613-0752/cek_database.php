<?php
include "koneksi.php";

// Cek data kelas sebelum dan sesudah update
echo "<h2>Data Kelas Saat Ini</h2>";

$query = mysqli_query($conn, "SELECT id_kelas, kelas, wali_kelas, nip_wali FROM tbl_kelas ORDER BY id_kelas");

echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>ID Kelas</th><th>Nama Kelas</th><th>Wali Kelas</th><th>NIP Wali</th></tr>";

while($data = mysqli_fetch_array($query)) {
    echo "<tr>";
    echo "<td>" . $data['id_kelas'] . "</td>";
    echo "<td>" . $data['kelas'] . "</td>";
    echo "<td>" . ($data['wali_kelas'] == '0' ? '<em style="color:red;">Tidak Ada</em>' : $data['wali_kelas']) . "</td>";
    echo "<td>" . ($data['nip_wali'] == '0' ? '<em style="color:red;">Tidak Ada</em>' : $data['nip_wali']) . "</td>";
    echo "</tr>";
}

echo "</table>";
?>

<br><br>
<form method="POST">
    <button type="submit" name="reset_wali_58">Reset Wali Kelas ID 58 untuk Testing</button>
</form>

<?php
if(isset($_POST['reset_wali_58'])) {
    // Reset wali kelas untuk testing
    $reset_query = "UPDATE tbl_kelas SET wali_kelas='Dedik Setiawan, S. Pd.', nip_wali='199305062020121010' WHERE id_kelas='58'";
    if(mysqli_query($conn, $reset_query)) {
        echo "<script>alert('Wali kelas ID 58 telah direset!'); location.reload();</script>";
    }
}
?>