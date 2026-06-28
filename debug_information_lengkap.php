<?php
/**
 * DEBUG INFORMATION LENGKAP - CETAK JURNAL
 * File ini berisi semua informasi debug yang tersedia untuk troubleshooting
 */

// Simulasi parameter yang sama dengan cetak-jurnal.php
$_GET['guru'] = '0029';
$_GET['tglAwal'] = '2025-07-01';
$_GET['tglAkhir'] = '2025-09-16';

// Simulasi session
session_start();
$_SESSION['username'] = '0029';
$_SESSION['hak_akses'] = 1;

echo "<!DOCTYPE html><html><head><title>DEBUG INFORMATION LENGKAP</title></head><body>";
echo "<h1>🔍 DEBUG INFORMATION LENGKAP - CETAK JURNAL</h1>";
echo "<h2>📋 DAFTAR DEBUG INFORMATION YANG TERSEDIA:</h2>";

echo "<div style='background: #f0f0f0; padding: 20px; margin: 20px 0; border-radius: 10px;'>";

// 1. PARAMETER INPUT
echo "<h3>1. 📥 PARAMETER INPUT</h3>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background: #e8f4f8;'><th>Parameter</th><th>Nilai</th><th>Keterangan</th></tr>";
echo "<tr><td><code>\$_GET['guru']</code></td><td>" . ($_GET['guru'] ?? 'NULL') . "</td><td>NIP Guru yang dipilih</td></tr>";
echo "<tr><td><code>\$_GET['tglAwal']</code></td><td>" . ($_GET['tglAwal'] ?? 'NULL') . "</td><td>Tanggal mulai periode</td></tr>";
echo "<tr><td><code>\$_GET['tglAkhir']</code></td><td>" . ($_GET['tglAkhir'] ?? 'NULL') . "</td><td>Tanggal akhir periode</td></tr>";
echo "<tr><td><code>\$_GET['kelas']</code></td><td>" . ($_GET['kelas'] ?? 'NULL') . "</td><td>Filter kelas (opsional)</td></tr>";
echo "<tr><td><code>\$_SESSION['username']</code></td><td>" . ($_SESSION['username'] ?? 'NULL') . "</td><td>Username dari session</td></tr>";
echo "<tr><td><code>\$_SESSION['hak_akses']</code></td><td>" . ($_SESSION['hak_akses'] ?? 'NULL') . "</td><td>Level akses user</td></tr>";
echo "</table>";

// 2. DEFAULT PERIODE
echo "<h3>2. 📅 DEFAULT PERIODE TAHUN AJARAN</h3>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background: #e8f4f8;'><th>Kondisi</th><th>Nilai Default</th><th>Keterangan</th></tr>";
echo "<tr><td>Jika parameter kosong</td><td>1 Juli " . date('Y') . " - 30 Juni " . (date('Y') + 1) . "</td><td>Periode tahun ajaran otomatis</td></tr>";
echo "<tr><td>Jika guru kosong</td><td>\$_SESSION['username']</td><td>Guru dari session login</td></tr>";
echo "</table>";

// 3. DATABASE QUERIES
echo "<h3>3. 🗄️ DATABASE QUERIES</h3>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background: #e8f4f8;'><th>Query Type</th><th>SQL Statement</th><th>Expected Result</th></tr>";
echo "<tr><td>Jadwal Mengajar</td><td><code>SELECT * FROM tbl_mapel_ampu WHERE no_induk = '$guru'</code></td><td>Array jadwal per hari</td></tr>";
echo "<tr><td>Jurnal Terisi</td><td><code>SELECT * FROM tbl_materi WHERE no_induk = '$guru' AND tanggal BETWEEN '$tglAwal' AND '$tglAkhir'</code></td><td>Data jurnal yang sudah diisi</td></tr>";
echo "<tr><td>Data Guru</td><td><code>SELECT * FROM tbl_guru WHERE no_induk = '$guru'</code></td><td>Info guru untuk header</td></tr>";
echo "<tr><td>Data Sekolah</td><td><code>SELECT * FROM tbl_setting WHERE 1</code></td><td>Info sekolah untuk kop surat</td></tr>";
echo "</table>";

// 4. LOGIKA GENERATE DATA
echo "<h3>4. 🔄 LOGIKA GENERATE DATA ARRAY</h3>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background: #e8f4f8;'><th>Langkah</th><th>Proses</th><th>Output</th></tr>";
echo "<tr><td>1</td><td>Loop dari tglAwal sampai tglAkhir</td><td>Setiap tanggal dalam periode</td></tr>";
echo "<tr><td>2</td><td>Konversi tanggal ke hari (Monday → Senin)</td><td>Nama hari dalam bahasa Indonesia</td></tr>";
echo "<tr><td>3</td><td>Cek jadwal untuk hari tersebut</td><td>Array jadwal yang match</td></tr>";
echo "<tr><td>4</td><td>Cek apakah sudah ada jurnal</td><td>Data aktual atau default</td></tr>";
echo "<tr><td>5</td><td>Generate entry untuk setiap jadwal</td><td>Array data lengkap</td></tr>";
echo "</table>";

// 5. STATUS LOGIC
echo "<h3>5. 📊 LOGIKA STATUS</h3>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background: #e8f4f8;'><th>Tanggal</th><th>Status</th><th>Warna</th><th>Kondisi</th></tr>";
echo "<tr><td>Masa lalu</td><td>Belum Mengisi Jurnal</td><td style='color: red;'>🔴 Merah</td><td>Jika belum diisi guru</td></tr>";
echo "<tr><td>Hari ini</td><td>Hari Ini (Belum Diisi)</td><td style='color: orange;'>🟡 Orange</td><td>Jika hari ini belum diisi</td></tr>";
echo "<tr><td>Masa depan</td><td>Jadwal Akan Datang</td><td style='color: blue;'>🔵 Biru</td><td>Jika belum waktunya</td></tr>";
echo "<tr><td>Semua tanggal</td><td>Sudah Mengisi Jurnal</td><td style='color: green;'>🟢 Hijau</td><td>Jika sudah diisi guru</td></tr>";
echo "</table>";

// 6. DEBUG COMMENTS DI HTML
echo "<h3>6. 💬 DEBUG COMMENTS DALAM HTML</h3>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background: #e8f4f8;'><th>Lokasi</th><th>Debug Info</th><th>Kegunaan</th></tr>";
echo "<tr><td>Baris awal</td><td><code>&lt;!-- DEBUG: Guru NIP = \$guru --&gt;</code></td><td>Verifikasi parameter guru</td></tr>";
echo "<tr><td>Setelah query jadwal</td><td><code>&lt;!-- DEBUG: Found X schedules --&gt;</code></td><td>Jumlah jadwal ditemukan</td></tr>";
echo "<tr><td>Setelah query jurnal</td><td><code>&lt;!-- DEBUG: Found X journal entries --&gt;</code></td><td>Jumlah jurnal terisi</td></tr>";
echo "<tr><td>Setelah generate</td><td><code>&lt;!-- DEBUG: Total entries generated = X --&gt;</code></td><td>Total data yang dihasilkan</td></tr>";
echo "<tr><td>Summary</td><td><code>&lt;!-- SUMMARY: Total=X, Filled=X, Empty=X, Percentage=X% --&gt;</code></td><td>Statistik lengkap</td></tr>";
echo "</table>";

// 7. ERROR HANDLING
echo "<h3>7. ⚠️ ERROR HANDLING</h3>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background: #e8f4f8;'><th>Error Condition</th><th>Message</th><th>Solution</th></tr>";
echo "<tr><td>Database connection failed</td><td>Database connection failed</td><td>Check koneksi.php fallback</td></tr>";
echo "<tr><td>No schedules found</td><td>Tidak ada jadwal dalam periode yang dipilih</td><td>Check tbl_mapel_ampu data</td></tr>";
echo "<tr><td>Empty dataArray</td><td>Tidak ada jadwal dalam periode yang dipilih</td><td>Check date range & schedule matching</td></tr>";
echo "<tr><td>Session expired</td><td>Redirect to index.php</td><td>Login ulang</td></tr>";
echo "<tr><td>Access denied</td><td>Redirect to 404.html</td><td>Check hak_akses level</td></tr>";
echo "</table>";

// 8. FALLBACK CONNECTION
echo "<h3>8. 🔧 FALLBACK CONNECTION</h3>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background: #e8f4f8;'><th>Scenario</th><th>Action</th><th>Credentials</th></tr>";
echo "<tr><td>Main connection fails</td><td>Use local credentials</td><td>root@localhost:3307</td></tr>";
echo "<tr><td>Production detected as local</td><td>Override with config.local.php</td><td>Custom config file</td></tr>";
echo "<tr><td>Port detection fails</td><td>Try multiple ports</td><td>3306, 3307, 3308</td></tr>";
echo "</table>";

// 9. PERFORMANCE METRICS
echo "<h3>9. 📈 PERFORMANCE METRICS</h3>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background: #e8f4f8;'><th>Metric</th><th>Expected Value</th><th>Monitoring</th></tr>";
echo "<tr><td>Query Execution Time</td><td>&lt; 500ms</td><td>Via debug comments</td></tr>";
echo "<tr><td>Memory Usage</td><td>&lt; 50MB</td><td>PHP memory_get_peak_usage()</td></tr>";
echo "<tr><td>Data Generation Time</td><td>&lt; 2 seconds</td><td>Via debug comments</td></tr>";
echo "<tr><td>Page Load Time</td><td>&lt; 3 seconds</td><td>Browser dev tools</td></tr>";
echo "</table>";

// 10. TESTING URLS
echo "<h3>10. 🧪 TESTING URLS</h3>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background: #e8f4f8;'><th>Test Case</th><th>URL</th><th>Expected Result</th></tr>";
echo "<tr><td>Normal case</td><td><code>http://localhost:8000/cetak-jurnal.php?guru=0029&tglAwal=2025-07-01&tglAkhir=2025-09-16</code></td><td>Full table with data</td></tr>";
echo "<tr><td>No parameters</td><td><code>http://localhost:8000/cetak-jurnal.php</code></td><td>Default period, current user</td></tr>";
echo "<tr><td>Specific class</td><td><code>http://localhost:8000/cetak-jurnal.php?guru=0029&kelas=X-A</code></td><td>Filtered by class</td></tr>";
echo "<tr><td>Debug test</td><td><code>http://localhost:8000/test_browser.php</code></td><td>Debug information page</td></tr>";
echo "</table>";

echo "</div>";

// 11. TROUBLESHOOTING GUIDE
echo "<h2>🔧 TROUBLESHOOTING GUIDE</h2>";
echo "<div style='background: #fff3cd; padding: 20px; margin: 20px 0; border-radius: 10px; border-left: 5px solid #ffc107;'>";

echo "<h3>❌ Jika muncul: 'Tidak ada jadwal dalam periode yang dipilih'</h3>";
echo "<ol>";
echo "<li>Check debug comments di HTML source</li>";
echo "<li>Verify guru NIP exists in tbl_mapel_ampu</li>";
echo "<li>Check date range format (YYYY-MM-DD)</li>";
echo "<li>Verify hari matching (Senin, Selasa, etc.)</li>";
echo "<li>Test with debug URL above</li>";
echo "</ol>";

echo "<h3>❌ Jika blank page atau error</h3>";
echo "<ol>";
echo "<li>Check PHP error logs</li>";
echo "<li>Verify database connection</li>";
echo "<li>Check session variables</li>";
echo "<li>Test fallback connection</li>";
echo "</ol>";

echo "<h3>❌ Jika data tidak sesuai</h3>";
echo "<ol>";
echo "<li>Compare with tbl_materi data</li>";
echo "<li>Check date format consistency</li>";
echo "<li>Verify status logic</li>";
echo "<li>Test with different date ranges</li>";
echo "</ol>";

echo "</div>";

// 12. SYSTEM ARCHITECTURE
echo "<h2>🏗️ SYSTEM ARCHITECTURE</h2>";
echo "<div style='background: #e8f4f8; padding: 20px; margin: 20px 0; border-radius: 10px;'>";

echo "<h3>Data Flow:</h3>";
echo "<ol>";
echo "<li>User → cetak-jurnal.php (with parameters)</li>";
echo "<li>Session validation & access control</li>";
echo "<li>Database connection (main + fallback)</li>";
echo "<li>Parameter processing & defaults</li>";
echo "<li>Query jadwal from tbl_mapel_ampu</li>";
echo "<li>Query existing jurnal from tbl_materi</li>";
echo "<li>Generate data array with status logic</li>";
echo "<li>Render HTML table with styling</li>";
echo "<li>Auto-print trigger</li>";
echo "</ol>";

echo "<h3>Key Components:</h3>";
echo "<ul>";
echo "<li><strong>Authentication:</strong> Session-based with role checking</li>";
echo "<li><strong>Database:</strong> MySQLi with fallback connection</li>";
echo "<li><strong>Date Logic:</strong> PHP strtotime() with Indonesian formatting</li>";
echo "<li><strong>Status System:</strong> Dynamic based on date & completion</li>";
echo "<li><strong>UI:</strong> Bootstrap-compatible HTML table</li>";
echo "</ul>";

echo "</div>";

echo "</body></html>";
?>