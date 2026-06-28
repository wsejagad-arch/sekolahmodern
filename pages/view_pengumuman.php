<?php
// Halaman Lihat Pengumuman
// File: pages/view_pengumuman.php

if (!isset($conn)) {
    require_once __DIR__ . '/../config.php';
}

require_login(); // Pastikan user login

?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">📢 Pengumuman</h1>
    </div>

    <div class="row">
        <div class="col-12">
            <?php
            $pengumuman_query = mysqli_query($conn, "SELECT * FROM tbl_pengumuman WHERE status = 'aktif' ORDER BY created_at DESC");
            if (mysqli_num_rows($pengumuman_query) > 0) {
                while ($p = mysqli_fetch_assoc($pengumuman_query)) {
                    echo '<div class="card shadow mb-4" style="border-radius: 15px;">';
                    echo '<div class="card-header bg-primary text-white" style="border-radius: 15px 15px 0 0;">';
                    echo '<h5 class="mb-0">' . htmlspecialchars($p['judul']) . '</h5>';
                    echo '<small>Dibuat: ' . date('d-m-Y H:i', strtotime($p['created_at'])) . '</small>';
                    echo '</div>';
                    echo '<div class="card-body">';
                    echo '<p>' . nl2br(htmlspecialchars($p['isi'])) . '</p>';
                    if ($p['lampiran']) {
                        echo '<p><a href="' . htmlspecialchars($p['lampiran']) . '" target="_blank" class="btn btn-sm btn-secondary"><i class="fas fa-download"></i> Unduh Lampiran</a></p>';
                    }
                    echo '</div>';
                    echo '</div>';
                }
            } else {
                echo '<div class="alert alert-info">Tidak ada pengumuman aktif saat ini.</div>';
            }
            ?>
        </div>
    </div>
</div>