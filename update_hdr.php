<?php
$file = 'c:\xampp\htdocs\jurnal\pages\guru\guru_header.php';
$content = file_get_contents($file);

// Add blink animation to CSS
$css_old = "</style>";
$css_new = "  @keyframes pulse-red { 0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7); } 70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(239, 68, 68, 0); } 100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); } } .notif-blink { animation: pulse-red 2s infinite; background-color: #ef4444 !important; }\n</style>";
$content = str_replace($css_old, $css_new, $content);

// Check if there are pending validations to apply blink
$php_logic_old = "                              <?php if (\$totalNotifikasi > 0): ?>\n                                  <span class=\"notif-dot\"></span>";
$php_logic_new = "                              <?php \n                                  \$has_pending_validasi = false;\n                                  foreach (\$notifikasiData as \$nd) {\n                                      if (isset(\$nd['type']) && \$nd['type'] === 'validasi_izin') \$has_pending_validasi = true;\n                                  }\n                              ?>\n                              <?php if (\$totalNotifikasi > 0): ?>\n                                  <span class=\"notif-dot <?= \$has_pending_validasi ? 'notif-blink' : '' ?>\"></span>";
$content = str_replace($php_logic_old, $php_logic_new, $content);

file_put_contents($file, $content);
echo "Updated guru_header.php\n";
?>
