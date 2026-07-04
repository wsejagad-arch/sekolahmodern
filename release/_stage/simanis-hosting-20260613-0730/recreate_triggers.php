<?php
require 'multi_tenant.php';
$conn = new mysqli('127.0.0.1', 'root', '', 'sijurnal');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
// Clear cache files
foreach (glob(sys_get_temp_dir() . '/simanis_schema_*.cache') as $f) {
    unlink($f);
}
// Run bootstrap to recreate tables and triggers
mt_bootstrap($conn);
echo "Recreation script finished.\n";
