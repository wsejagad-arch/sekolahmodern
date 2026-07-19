<?php
include 'koneksi.php';

echo "Checking tbl_guru columns...\n";
$q = mysqli_query($conn, "SHOW COLUMNS FROM tbl_guru");
$has_wa = false;
if ($q) {
    while($r = mysqli_fetch_assoc($q)) {
        if ($r['Field'] == 'no_wa') {
            $has_wa = true;
        }
    }
} else {
    echo "Error checking columns: " . mysqli_error($conn) . "\n";
}

if ($has_wa) {
    echo "Column no_wa EXISTS.\n";
} else {
    echo "Column no_wa is MISSING. Attempting to add...\n";
    $add = mysqli_query($conn, "ALTER TABLE tbl_guru ADD COLUMN no_wa VARCHAR(20) DEFAULT NULL AFTER nama_guru");
    if ($add) {
        echo "Successfully added no_wa column.\n";
    } else {
        echo "Failed to add column: " . mysqli_error($conn) . "\n";
    }
}

// Let's also test updating a guru
$guru = mysqli_query($conn, "SELECT id_guru, nama_guru FROM tbl_guru LIMIT 1");
if ($guru && mysqli_num_rows($guru) > 0) {
    $row = mysqli_fetch_assoc($guru);
    $id = $row['id_guru'];
    $nama = $row['nama_guru'];
    echo "\nTesting update on guru ID $id ($nama)...\n";
    
    $upd = mysqli_query($conn, "UPDATE tbl_guru SET no_wa='08123456789' WHERE id_guru='$id'");
    if ($upd) {
        echo "Update query ran successfully.\n";
    } else {
        echo "Update failed: " . mysqli_error($conn) . "\n";
    }
}
?>
