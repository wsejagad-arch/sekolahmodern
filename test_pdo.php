<?php $pdo = new PDO('mysql:host=127.0.0.1;dbname=smasumb1_simanis;port=3306', 'smasumb1_simanis1', 'W@hyu1234!'); $stmt = $pdo->query('SELECT COUNT(*) FROM tbl_absen'); print_r($stmt->fetch());
