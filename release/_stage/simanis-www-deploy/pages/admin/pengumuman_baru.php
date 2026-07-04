<?php
// Halaman Pengumuman
// File: pages/admin/pengumuman.php

if (!isset($conn)) {
    require_once __DIR__ . '/../../config.php';
}

try {
    // Ambil pengumuman yang aktif
    $stmt = $conn->query("SELECT * FROM pengumuman WHERE status = 'aktif' ORDER BY tanggal_dibuat DESC LIMIT 20");
    $pengumuman = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $pengumuman = [];
    $error = "Error: " . $e->getMessage();
}
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">📢 Pengumuman</h1>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <?php if (count($pengumuman) > 0): ?>
        <div class="row">
            <?php foreach ($pengumuman as $item): ?>
            <div class="col-lg-8 mb-4">
                <div class="card shadow mb-4 border-left-primary">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <?php echo htmlspecialchars($item['judul']); ?>
                        </h6>
                        <small class="text-muted">
                            📅 <?php echo date('d-m-Y H:i', strtotime($item['tanggal_dibuat'])); ?>
                        </small>
                    </div>
                    <div class="card-body">
                        <p class="text-gray-700">
                            <?php echo nl2br(htmlspecialchars($item['isi'])); ?>
                        </p>
                    </div>
                    <div class="card-footer bg-light">
                        <small class="text-muted">
                            Status: <span class="badge badge-success">Aktif</span>
                        </small>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
    <div class="alert alert-info" role="alert">
        <i class="fas fa-info-circle"></i> Tidak ada pengumuman saat ini.
    </div>
    <?php endif; ?>
</div>

<style>
    .border-left-primary {
        border-left: .25rem solid #4e73df !important;
    }
    
    .text-gray-700 {
        color: #717171;
        line-height: 1.6;
    }
    
    .card-header {
        background-color: #f8f9fa;
        border-bottom: 1px solid #e3e6f0;
    }
    
    .badge-success {
        background-color: #1cc88a !important;
    }
</style>
