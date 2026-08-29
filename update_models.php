<?php
$modelsDir = __DIR__ . '/v2/app/Models/';
$files = glob($modelsDir . '*.php');

foreach ($files as $file) {
    $filename = basename($file);
    $content = file_get_contents($file);
    
    if ($filename === 'User.php') {
        if (strpos($content, "'username'") === false) {
            $content = str_replace("'email',", "'email',\n        'username',\n        'role',", $content);
            file_put_contents($file, $content);
            echo "Updated User.php\n";
        }
    } else {
        if (strpos($content, "\$guarded") === false) {
            $pattern = '/(use HasFactory;)/';
            $replacement = "$1\n\n    protected \$guarded = [];";
            $newContent = preg_replace($pattern, $replacement, $content);
            file_put_contents($file, $newContent);
            echo "Updated $filename\n";
        }
    }
}
