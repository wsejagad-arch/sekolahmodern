<?php
$configs = [
    ['host' => '127.0.0.1', 'user' => 'root', 'pass' => '', 'db' => 'sijurnal', 'port' => 3306],
    ['host' => '127.0.0.1', 'user' => 'root', 'pass' => '', 'db' => 'jurnal', 'port' => 3306],
    ['host' => '127.0.0.1', 'user' => 'root', 'pass' => '', 'db' => 'sijurnal', 'port' => 3307],
    ['host' => '127.0.0.1', 'user' => 'root', 'pass' => '', 'db' => 'jurnal', 'port' => 3307],
];

foreach ($configs as $idx => $conf) {
    echo "Trying Config #$idx: {$conf['db']} on port {$conf['port']}...\n";
    $conn = @mysqli_connect($conf['host'], $conf['user'], $conf['pass'], $conf['db'], $conf['port']);
    if ($conn) {
        echo "SUCCESS! Connected to {$conf['db']} on port {$conf['port']}.\n";
        
        $username = '199303012022211013';
        
        echo "Checking User: $username\n";
        $stmt = $conn->prepare("SELECT * FROM tbl_pengguna WHERE no_induk = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $res = $stmt->get_result();
        $user = $res->fetch_assoc();
        if ($user) {
            echo "User found in tbl_pengguna!\n";
            print_r($user);
        } else {
            echo "User NOT found in tbl_pengguna.\n";
        }
        
        $stmt = $conn->prepare("SELECT * FROM tbl_guru WHERE no_induk = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $res = $stmt->get_result();
        $guru = $res->fetch_assoc();
        if ($guru) {
            echo "User found in tbl_guru!\n";
            print_r($guru);
        } else {
            echo "User NOT found in tbl_guru.\n";
        }
        
        mysqli_close($conn);
        exit;
    } else {
        echo "Failed: " . mysqli_connect_error() . "\n";
    }
}
echo "All connection attempts failed.\n";
