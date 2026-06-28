<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include "koneksi.php";

echo "<h2>Debug Session & Role</h2>";
echo "<h3>Session Data:</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h3>Current User Info:</h3>";
if (isset($_SESSION['username'])) {
    $username = $_SESSION['username'];
    $query = mysqli_query($conn, "SELECT * FROM tbl_user WHERE username='$username'");
    if ($query && mysqli_num_rows($query) > 0) {
        $user = mysqli_fetch_array($query);
        echo "<pre>";
        print_r($user);
        echo "</pre>";
        
        echo "<p>Role: " . $user['hakakses'] . "</p>";
        echo "<p>Is Admin: " . ($user['hakakses'] == 1 ? 'YES' : 'NO') . "</p>";
    } else {
        echo "<p>User not found in database!</p>";
    }
} else {
    echo "<p>No session found</p>";
}

echo "<h3>Auth Helper Functions:</h3>";
if (file_exists('auth_helper.php')) {
    include_once 'auth_helper.php';
    echo "<p>is_admin(): " . (function_exists('is_admin') ? (is_admin() ? 'TRUE' : 'FALSE') : 'Function not found') . "</p>";
    echo "<p>current_role(): " . (function_exists('current_role') ? current_role() : 'Function not found') . "</p>";
}

echo "<h3>Test Links:</h3>";
echo '<a href="home.php">Home</a><br>';
echo '<a href="home.php?page=kelas">Data Kelas</a><br>';
echo '<a href="index.php">Login Page</a><br>';
?>