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
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
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
    <a href="javascript:history.back()" class="back-btn"><i class="bi bi-arrow-left"></i> Kembali / Back</a>
    
    <h1>Privacy Policy <br><span style="font-size: 1.25rem; font-weight: 600; color: #64748b;">(Kebijakan Privasi)</span></h1>
    
    <p style="text-align: center; margin-bottom: 40px; color: #94a3b8;">Terakhir diperbarui / Last updated: <?= date('d M Y') ?></p>
    
    <div style="margin-bottom: 40px;">
        <h2>1. Pengumpulan Data (Data Collection)</h2>
        <p><strong>[ID]</strong> Kami mengumpulkan informasi yang Anda berikan secara langsung kepada kami, seperti saat Anda membuat akun, memperbarui profil, atau berinteraksi dengan layanan kami. Informasi yang kami kumpulkan meliputi nama, nomor identitas (NIP/NIS), email, dan data akademik lainnya yang relevan.</p>
        <p><strong>[EN]</strong> We collect information you provide directly to us, such as when you create an account, update your profile, or interact with our services. The information we collect includes names, identity numbers (NIP/NIS), emails, and other relevant academic data.</p>
    </div>

    <div style="margin-bottom: 40px;">
        <h2>2. Data Lokasi (Location Data)</h2>
        <p><strong>[ID]</strong> Aplikasi kami mengambil data lokasi (GPS) pengguna saat melakukan presensi. Data ini <strong>hanya</strong> digunakan untuk memvalidasi posisi presensi siswa atau guru agar sesuai dengan radius sekolah yang diizinkan.</p>
        <p><strong>[EN]</strong> Our application collects user location data (GPS) during attendance. This data is used <strong>solely</strong> to validate the student's or teacher's attendance position to ensure it is within the permitted school radius.</p>
    </div>

    <div style="margin-bottom: 40px;">
        <h2>3. Kamera (Camera)</h2>
        <p><strong>[ID]</strong> Aplikasi kami membutuhkan akses ke kamera perangkat Anda. Kamera digunakan <strong>hanya untuk verifikasi identitas (foto diri/swafoto)</strong> sebagai bukti kehadiran yang valid saat melakukan presensi. Kami tidak akan mengakses kamera Anda di luar proses tersebut.</p>
        <p><strong>[EN]</strong> Our application requires access to your device's camera. The camera is used <strong>only for identity verification (selfie/photo)</strong> as valid proof of attendance during the check-in process. We will not access your camera outside of this process.</p>
    </div>

    <div style="margin-bottom: 40px;">
        <h2>4. Iklan AdMob (AdMob Advertising)</h2>
        <p><strong>[ID]</strong> Kami menggunakan layanan pihak ketiga, yaitu Google AdMob, untuk menampilkan iklan di dalam aplikasi kami. Google AdMob mungkin mengumpulkan dan menggunakan data teknis (seperti pengenal perangkat / device ID) untuk keperluan identifikasi perangkat dan personalisasi iklan.</p>
        <p><strong>[EN]</strong> We use a third-party service, Google AdMob, to display advertisements within our application. Google AdMob may collect and use technical data (such as device identifiers / device ID) for device identification and personalized advertising purposes.</p>
    </div>

    <div style="margin-bottom: 40px;">
        <h2>5. Keamanan Data (Data Security)</h2>
        <p><strong>[ID]</strong> Kami sangat menjaga keamanan data Anda. Seluruh data presensi dan informasi pribadi Anda dikirim secara aman langsung ke server sekolah. Kami menjamin bahwa data Anda <strong>tidak akan dijual, disewakan, atau dibagikan ke pihak ketiga</strong> untuk tujuan komersial di luar keperluan sistem pendidikan kami.</p>
        <p><strong>[EN]</strong> We take the security of your data very seriously. All attendance data and personal information are transmitted securely directly to the school server. We guarantee that your data <strong>will not be sold, rented, or shared with third parties</strong> for commercial purposes outside of our educational system needs.</p>
    </div>

    <div style="margin-bottom: 40px;">
        <h2>6. Penghapusan Data (Data Deletion)</h2>
        <p><strong>[ID]</strong> Catatan: Google mewajibkan adanya link dimana pengguna bisa mengetahui cara menghapus data mereka. Cara menghapus profil: melalui menu profil, di dalam menu profil terdapat fitur "Hapus Data" untuk mengajukan penghapusan akun dan data Anda.</p>
        <p><strong>[EN]</strong> Note: Google requires a link where users can find out how to delete their data. How to delete your profile: via the profile menu, inside the profile menu there is a "Delete Data" feature to request the deletion of your account and data.</p>
        <p>Masukkan link kebijakan privasi Anda: <a href="https://simanis.sman1sumber.sch.id/privacy-policy.php" style="color: #4F46E5;">https://simanis.sman1sumber.sch.id/privacy-policy.php</a></p>
    </div>

    <div style="margin-bottom: 40px;">
        <h2>7. Kontak Kami (Contact Us)</h2>
        <p><strong>[ID]</strong> Jika Anda memiliki pertanyaan atau kekhawatiran tentang Kebijakan Privasi ini, silakan hubungi administrator sistem melalui kontak di bawah ini:<br>
        <strong>[EN]</strong> If you have any questions or concerns about this Privacy Policy, please contact the system administrator via the contact details below:</p>
        
        <div style="background-color: #f1f5f9; padding: 20px; border-radius: 8px; margin-top: 15px;">
            <ul style="list-style: none; padding-left: 0; margin-bottom: 0; color: #334155;">
                <li style="margin-bottom: 12px; display: flex; align-items: center;"><i class="bi bi-telephone-fill" style="color: #4F46E5; font-size: 1.2rem; margin-right: 12px; width: 24px; text-align: center;"></i> <strong>Owner:</strong> &nbsp;087815873285</li>
                <li style="margin-bottom: 12px; display: flex; align-items: center;"><i class="bi bi-envelope-fill" style="color: #4F46E5; font-size: 1.2rem; margin-right: 12px; width: 24px; text-align: center;"></i> <strong>Email:</strong> &nbsp;<a href="mailto:wsejagad@gmail.com" style="color: #334155; text-decoration: none;">wsejagad@gmail.com</a></li>
                <li style="display: flex; align-items: center;"><i class="bi bi-globe" style="color: #4F46E5; font-size: 1.2rem; margin-right: 12px; width: 24px; text-align: center;"></i> <strong>Website:</strong> &nbsp;<a href="https://pintarhub.com" target="_blank" style="color: #334155; text-decoration: none;">pintarhub.com</a></li>
            </ul>
        </div>
    </div>
</div>

</body>
</html>
