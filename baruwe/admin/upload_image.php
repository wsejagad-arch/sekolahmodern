<?php
session_start();
header('Content-Type: application/json');

if(!isset($_SESSION['admin_logged_in'])) {
    echo json_encode(['uploaded' => 0, 'error' => ['message' => 'Akses ditolak']]);
    exit;
}

if(isset($_FILES['upload']['name'])){
    $file = $_FILES['upload']['name'];
    $file_tmp = $_FILES['upload']['tmp_name'];
    
    // Validasi ekstensi
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    
    if(in_array($ext, $allowed)) {
        $file_name = time() . '_' . rand(1000, 9999) . '.' . $ext;
        $upload_path = '../uploads/' . $file_name;
        
        if(move_uploaded_file($file_tmp, $upload_path)) {
            // Build absolute URL dynamically
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
            $url = $protocol . "://" . $_SERVER['HTTP_HOST'] . "/sekolah-modern/uploads/" . $file_name;
            
            echo json_encode([
                'uploaded' => 1,
                'fileName' => $file_name,
                'url' => $url
            ]);
        } else {
            echo json_encode(['uploaded' => 0, 'error' => ['message' => 'Gagal mengunggah gambar.']]);
        }
    } else {
        echo json_encode(['uploaded' => 0, 'error' => ['message' => 'Format gambar tidak didukung (Gunakan JPG, PNG, GIF, WEBP).']]);
    }
} else {
    echo json_encode(['uploaded' => 0, 'error' => ['message' => 'Tidak ada file yang diunggah.']]);
}
?>
