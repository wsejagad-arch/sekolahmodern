<?php
// pages/admin/broadcast-wa.php
if (!isset($_SESSION['hak_akses']) || (int)$_SESSION['hak_akses'] !== 1) {
    echo "<h1>Akses Ditolak</h1>";
    exit;
}

require_once __DIR__ . '/../../notification_helper.php';

$flash = ['type' => '', 'msg' => ''];

$kelas_list = [];
if (isset($conn) && $conn instanceof mysqli) {
    $qKelas = @mysqli_query($conn, "SELECT DISTINCT kelas FROM tbl_kelas ORDER BY kelas ASC");
    if ($qKelas) {
        while ($r = mysqli_fetch_assoc($qKelas)) {
            $kelas_list[] = $r['kelas'];
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_broadcast') {
    $target_type = $_POST['target_type'] ?? '';
    $pesan_raw = trim($_POST['pesan'] ?? '');
    
    if ($pesan_raw === '') {
        $flash = ['type' => 'danger', 'msg' => '❌ Pesan tidak boleh kosong.'];
    } else {
        $queued_count = 0;
        $targets = []; // array of ['hp' => '...', 'nama' => '...']
        
        if ($target_type === 'semua_guru') {
            $q = @mysqli_query($conn, "SELECT nama_guru as nama, no_wa FROM tbl_guru WHERE status = 'Aktif' AND no_wa IS NOT NULL AND no_wa != ''");
            while ($q && $r = mysqli_fetch_assoc($q)) {
                $targets[] = ['hp' => $r['no_wa'], 'nama' => $r['nama']];
            }
        } elseif ($target_type === 'wali_kelas') {
            $q = @mysqli_query($conn, "
                SELECT g.nama_guru as nama, g.no_wa 
                FROM tbl_guru g
                WHERE g.status = 'Aktif' 
                  AND g.no_wa IS NOT NULL AND g.no_wa != ''
                  AND g.no_induk IN (SELECT nip_wali FROM tbl_kelas)
            ");
            while ($q && $r = mysqli_fetch_assoc($q)) {
                $targets[] = ['hp' => $r['no_wa'], 'nama' => $r['nama']];
            }
        } elseif ($target_type === 'ortu_semua') {
            $q = @mysqli_query($conn, "SELECT nama_siswa as nama, no_wa FROM tbl_siswa WHERE status = 'Aktif' AND no_wa IS NOT NULL AND no_wa != ''");
            while ($q && $r = mysqli_fetch_assoc($q)) {
                $targets[] = ['hp' => $r['no_wa'], 'nama' => $r['nama']];
            }
        } elseif ($target_type === 'ortu_kelas') {
            $selected_kelas = $_POST['kelas_pilihan'] ?? [];
            if (!empty($selected_kelas) && is_array($selected_kelas)) {
                $kelas_esc = array_map(function($k) use ($conn) {
                    return "'" . mysqli_real_escape_string($conn, $k) . "'";
                }, $selected_kelas);
                $kelas_in = implode(",", $kelas_esc);
                
                $q = @mysqli_query($conn, "SELECT nama_siswa as nama, no_wa FROM tbl_siswa WHERE status = 'Aktif' AND no_wa IS NOT NULL AND no_wa != '' AND kelas IN ($kelas_in)");
                while ($q && $r = mysqli_fetch_assoc($q)) {
                    $targets[] = ['hp' => $r['no_wa'], 'nama' => $r['nama']];
                }
            }
        }
        
        // Buat antrean ke database
        $judul = "📢 Broadcast Admin";
        if (!empty($targets)) {
            notif_ensure_schema($conn); // Make sure table exists
            $stmt = mysqli_prepare($conn, "INSERT INTO tbl_notifikasi_outbox (tujuan, judul, pesan, channel, status) VALUES (?, ?, ?, 'whatsapp', 'pending')");
            
            foreach ($targets as $t) {
                $hp = trim($t['hp']);
                if (empty($hp)) continue;
                
                $pesan_final = str_replace('{nama}', $t['nama'], $pesan_raw);
                mysqli_stmt_bind_param($stmt, "sss", $hp, $judul, $pesan_final);
                mysqli_stmt_execute($stmt);
                $queued_count++;
            }
            mysqli_stmt_close($stmt);
            
            // Panggil background process agar antrean diproses
            notif_trigger_background_process();
            
            $flash = ['type' => 'success', 'msg' => "✅ Broadcast berhasil! <strong>$queued_count</strong> pesan telah dimasukkan ke antrean dan akan dikirim secara bertahap."];
        } else {
            $flash = ['type' => 'warning', 'msg' => '⚠️ Tidak ada target penerima (mungkin nomor WA kosong atau kelas tidak dipilih).'];
        }
    }
}
?>

<div class="container-fluid px-3 px-md-4 pb-4">
    <!-- PAGE HEADING -->
    <div class="d-sm-flex align-items-center justify-content-between mb-3">
        <h1 class="h4 mb-0 text-gray-800">
            <i class="fas fa-bullhorn mr-2" style="color:var(--wa)"></i>
            Broadcast WhatsApp
        </h1>
        <a href="pengaturan-wa.php" class="d-none d-sm-inline-block btn btn-sm btn-outline-secondary shadow-sm">
            <i class="fas fa-cogs fa-sm mr-1"></i> Pengaturan WA
        </a>
    </div>

    <!-- FLASH -->
    <?php if ($flash['msg']): ?>
        <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show shadow-sm" role="alert">
            <?= $flash['msg'] ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- FORM BROADCAST -->
        <div class="col-lg-8 mb-4">
            <div class="card shadow-sm border-0" style="border-radius: 12px;">
                <div class="card-header bg-white border-0 py-3" style="border-radius: 12px 12px 0 0;">
                    <h6 class="m-0 font-weight-bold text-primary">Kirim Pesan Massal</h6>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="send_broadcast">
                        
                        <div class="form-group mb-4">
                            <label class="font-weight-bold text-dark">Target Penerima</label>
                            <select name="target_type" id="targetType" class="form-control" required onchange="toggleKelasSelect()">
                                <option value="">-- Pilih Target --</option>
                                <option value="semua_guru">Semua Guru (Aktif)</option>
                                <option value="wali_kelas">Semua Wali Kelas</option>
                                <option value="ortu_semua">Semua Orang Tua / Wali Siswa</option>
                                <option value="ortu_kelas">Orang Tua / Wali (Pilih Kelas)</option>
                            </select>
                        </div>
                        
                        <div class="form-group mb-4" id="boxKelas" style="display: none; background: #F8F9FA; padding: 15px; border-radius: 8px; border: 1px solid #EBEBEB;">
                            <label class="font-weight-bold text-dark">Pilih Kelas <span class="text-danger">*</span></label>
                            <p class="text-muted small mb-2">Tahan tombol Ctrl (Windows) atau Cmd (Mac) untuk memilih lebih dari 1 kelas.</p>
                            <select name="kelas_pilihan[]" id="kelasPilihan" class="form-control" multiple style="min-height: 150px;">
                                <?php foreach ($kelas_list as $kls): ?>
                                    <option value="<?= htmlspecialchars($kls) ?>"><?= htmlspecialchars($kls) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group mb-4">
                            <label class="font-weight-bold text-dark">Isi Pesan WhatsApp</label>
                            <textarea name="pesan" class="form-control" rows="6" placeholder="Ketik pesan broadcast di sini..." required></textarea>
                            <small class="text-muted mt-2 d-block">
                                💡 <strong>Tips:</strong> Gunakan variabel <code>{nama}</code> untuk menyisipkan nama penerima (Nama Guru atau Nama Siswa) secara otomatis.
                            </small>
                        </div>
                        
                        <hr>
                        <button type="submit" class="btn btn-success font-weight-bold shadow-sm" style="background-color: #25D366; border-color: #25D366; border-radius: 8px;" onclick="return confirm('Pesan akan masuk ke antrean dan dikirim secara bertahap. Lanjutkan?')">
                            <i class="fas fa-paper-plane mr-2"></i> Kirim Broadcast Sekarang
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- INFO PANEL -->
        <div class="col-lg-4 mb-4">
            <div class="card shadow-sm border-0 bg-light" style="border-radius: 12px;">
                <div class="card-body">
                    <h6 class="font-weight-bold text-dark mb-3"><i class="fas fa-info-circle mr-2 text-info"></i> Informasi Penting</h6>
                    <ul class="text-muted small pl-3" style="line-height: 1.6;">
                        <li class="mb-2">Broadcast menggunakan mekanisme <strong>antrean otomatis (queue)</strong>.</li>
                        <li class="mb-2">Terdapat <strong>jeda (delay) anti-blokir</strong> sesuai pengaturan Anda di menu Pengaturan WA.</li>
                        <li class="mb-2">Bila jumlah target ratusan, pengiriman bisa memakan waktu hingga beberapa jam di latar belakang (komputer server tidak boleh mati).</li>
                        <li class="mb-2">Pastikan WASENDER dalam keadaan aktif (online).</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleKelasSelect() {
    const type = document.getElementById('targetType').value;
    const box = document.getElementById('boxKelas');
    const select = document.getElementById('kelasPilihan');
    if (type === 'ortu_kelas') {
        box.style.display = 'block';
        select.setAttribute('required', 'required');
    } else {
        box.style.display = 'none';
        select.removeAttribute('required');
    }
}
// Init on load
document.addEventListener("DOMContentLoaded", toggleKelasSelect);
</script>
