<?php
$file = 'c:\xampp\htdocs\jurnal\topbar.php';
$content = file_get_contents($file);

// Ensure the navbar stretches and spaces items properly
// Remove mr-auto from the left side just in case, or leave it.
$content = str_replace('class="d-none d-sm-flex align-items-center mr-auto ml-md-3 my-2 my-md-0"', 'class="d-none d-sm-flex align-items-center flex-grow-1 ml-md-3 ms-md-3 my-2 my-md-0"', $content);

// Ensure the right ul is pushed to the right
$content = str_replace('<ul class="navbar-nav ml-auto align-items-center">', '<ul class="navbar-nav ml-auto ms-auto align-items-center justify-content-end" style="flex: 0 0 auto;">', $content);

file_put_contents($file, $content);
echo "Topbar aligned";
?>
