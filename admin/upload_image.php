<?php
session_start();
header('Content-Type: application/json');

if(!isset($_SESSION['admin_logged_in'])) {
    echo json_encode(['uploaded' => 0, 'error' => ['message' => 'Akses ditolak']]);
    exit;
}

// Support for both CKEditor (upload) and TinyMCE (file)
$fileParam = isset($_FILES['file']) ? 'file' : (isset($_FILES['upload']) ? 'upload' : null);

if($fileParam && isset($_FILES[$fileParam]['name'])){
    $file = $_FILES[$fileParam]['name'];
    $file_tmp = $_FILES[$fileParam]['tmp_name'];
    
    // Validasi ekstensi
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    
    if(in_array($ext, $allowed)) {
        $file_name = time() . '_' . rand(1000, 9999) . '.' . $ext;
        
        // Gunakan absolute path agar lebih aman dari open_basedir
        $upload_dir = dirname(__DIR__) . '/uploads/';
        if (!is_dir($upload_dir)) {
            @mkdir($upload_dir, 0755, true);
        }
        
        $upload_path = $upload_dir . $file_name;
        
        if(move_uploaded_file($file_tmp, $upload_path)) {
            // Build root-relative URL (biar aman tanpa perlu hardcode nama folder)
            // Mengambil root path secara otomatis
            $base_dir = str_replace($_SERVER['DOCUMENT_ROOT'], '', dirname(__DIR__));
            // Hapus backslash jika ada (Windows)
            $base_dir = str_replace('\\', '/', $base_dir);
            
            $url = $base_dir . "/uploads/" . $file_name;
            
            echo json_encode([
                'uploaded' => 1,
                'fileName' => $file_name,
                'url' => $url,
                'location' => $url // Untuk TinyMCE
            ]);
        } else {
            $err = error_get_last();
            $errMsg = $err ? $err['message'] : 'Permission denied';
            echo json_encode(['uploaded' => 0, 'error' => ['message' => 'Gagal mengunggah gambar. ' . $errMsg]]);
        }
    } else {
        echo json_encode(['uploaded' => 0, 'error' => ['message' => 'Format gambar tidak didukung (Gunakan JPG, PNG, GIF, WEBP).']]);
    }
} else {
    echo json_encode(['uploaded' => 0, 'error' => ['message' => 'Tidak ada file yang diunggah.']]);
}
?>
