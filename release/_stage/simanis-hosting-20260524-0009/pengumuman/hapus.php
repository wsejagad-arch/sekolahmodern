<?php
require_once '../config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$id = $_GET['id'] ?? null;

if (!$id) {
    header('Location: index.php');
    exit;
}

try {
    $stmt = $conn->prepare("DELETE FROM pengumuman WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: index.php');
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
