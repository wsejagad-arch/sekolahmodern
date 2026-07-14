<?php
require_once 'bootstrap.php';
require_once 'login_action.php';
$_SERVER['REQUEST_METHOD'] = 'POST';
$conn = new mysqli('localhost', 'root', '', 'sijurnal');
// Manually call get_admin_user
$admin = get_admin_user('admin', 0);
echo "ADMIN: \n";
print_r($admin);
echo "VERIFY: " . (verify_password('admin', $admin['password']) ? 'TRUE' : 'FALSE') . "\n";
