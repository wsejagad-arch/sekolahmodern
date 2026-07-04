<?php
// Konfigurasi untuk development lokal
$host = '127.0.0.1';
$user = 'root';
$password = '';
$db = 'sijurnal';
$port = 3306; // XAMPP MySQL default

// Konfigurasi konektor e-Raport (lokal)
$eraport_base_url = 'http://103.131.217.1:8239/';
$eraport_token = 'KA3ozSPb0MESniC';

// Kredensial login sesi e-Raport (untuk fitur sync data ekskul)
$eraport_admin_username = 'administrator';
$eraport_admin_password = 'Sman1$umber';
$eraport_sekolah_id = ''; // boleh dikosongkan (auto-detect)
$eraport_semester = '';   // boleh dikosongkan (auto-detect)

// Opsional: Login dengan Gmail/Google.
// Redirect URI lokal: http://127.0.0.1:8000/google-callback.php
$google_client_id = '';
$google_client_secret = '';
$google_redirect_uri = 'http://127.0.0.1:8000/google-callback.php';
