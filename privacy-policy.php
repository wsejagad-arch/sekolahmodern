<?php
// privacy-policy.php
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy - SIMANIS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <style>
        body {
            font-family: 'Nunito', 'Plus Jakarta Sans', sans-serif;
            background-color: #f8fafc;
            color: #0f172a;
            line-height: 1.6;
            margin: 0;
            padding: 0;
        }
        .container-pp {
            max-width: 800px;
            margin: 40px auto;
            background: #ffffff;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        h1 {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 20px;
            color: #1e293b;
            text-align: center;
        }
        h2 {
            font-size: 1.25rem;
            font-weight: 700;
            margin-top: 30px;
            margin-bottom: 15px;
            color: #334155;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 8px;
        }
        p {
            margin-bottom: 16px;
            color: #475569;
        }
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 24px;
            padding: 8px 16px;
            background-color: #f1f5f9;
            color: #475569;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.2s ease;
        }
        .back-btn:hover {
            background-color: #e2e8f0;
            color: #0f172a;
        }
        @media (max-width: 768px) {
            .container-pp {
                margin: 20px;
                padding: 24px;
            }
            h1 {
                font-size: 1.5rem;
            }
            h2 {
                font-size: 1.1rem;
            }
        }
    </style>
</head>
<body>

<div class="container-pp">
    <a href="javascript:history.back()" class="back-btn"><i class="bi bi-arrow-left"></i> Kembali</a>
    
    <h1>Privacy Policy (Kebijakan Privasi)</h1>
    
    <p>Terakhir diperbarui: <?= date('d M Y') ?></p>
    
    <p>Selamat datang di sistem manajemen akademik. Kami menghargai privasi Anda dan berkomitmen untuk melindungi informasi pribadi Anda. Kebijakan Privasi ini menjelaskan bagaimana kami mengumpulkan, menggunakan, dan melindungi data Anda saat menggunakan layanan kami.</p>

    <h2>1. Pengumpulan Data</h2>
    <p>Kami mengumpulkan informasi yang Anda berikan secara langsung kepada kami, seperti saat Anda membuat akun, memperbarui profil, atau berinteraksi dengan layanan kami. Informasi yang kami kumpulkan meliputi nama, nomor identitas (NIP/NIS), email, dan data akademik lainnya yang relevan.</p>

    <h2>2. Penggunaan Lokasi dan Kamera untuk Presensi</h2>
    <p>Sistem kami memiliki fitur absensi/presensi digital yang menggunakan fitur geolokasi (Lokasi GPS) dan Kamera perangkat Anda.
    <br><strong>Lokasi (GPS):</strong> Digunakan semata-mata untuk memverifikasi posisi Anda saat melakukan absensi, memastikan presensi dilakukan pada area yang diizinkan (misalnya: area sekolah).
    <br><strong>Kamera:</strong> Digunakan untuk mengambil foto wajah saat proses absensi atau pengajuan izin, sebagai bukti kehadiran yang valid.
    <br>Data lokasi dan foto (swafoto) ini dikirim secara aman, hanya disimpan untuk kebutuhan arsip kehadiran, dan tidak akan dibagikan ke pihak ketiga tanpa persetujuan Anda.</p>

    <h2>3. Keamanan Data</h2>
    <p>Kami menerapkan langkah-langkah keamanan teknis dan organisasional untuk melindungi informasi pribadi Anda dari akses yang tidak sah, kehilangan, atau penyalahgunaan. Meskipun demikian, tidak ada metode transmisi di internet atau penyimpanan elektronik yang 100% aman.</p>

    <h2>4. Penyimpanan dan Retensi Data</h2>
    <p>Data pribadi Anda akan disimpan selama akun Anda aktif atau selama diperlukan untuk menyediakan layanan terkait aktivitas akademik dan administratif di sekolah.</p>

    <h2>5. Perubahan Kebijakan Privasi</h2>
    <p>Kami dapat memperbarui Kebijakan Privasi ini dari waktu ke waktu. Setiap perubahan akan diinformasikan melalui sistem kami. Anda disarankan untuk meninjau halaman ini secara berkala untuk mengetahui perubahan apa pun.</p>

    <h2>6. Kontak Kami</h2>
    <p>Jika Anda memiliki pertanyaan atau kekhawatiran tentang Kebijakan Privasi ini atau pengelolaan data pribadi Anda, silakan hubungi administrator sistem atau pihak tata usaha sekolah.</p>
</div>

</body>
</html>
