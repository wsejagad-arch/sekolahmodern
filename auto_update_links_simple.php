<?php

/**
 * Auto-update links - SIMPLE VERSION (tanpa database dependency)
 * Scan semua file PHP dan ganti referensi path pages/ dengan helper functions
 */

// File-file yang skip
$skipDirs = ['vendor/', 'deploy_prod/', 'home5/', 'backup/', '.git/', 'node_modules/'];
$skipFiles = ['router.php', 'bootstrap.php', 'auto_update_links.php', 'auto_update_links_simple.php', 'config.php', 'koneksi.php', 'koneksi_local.php'];

// Mapping pages path ke helper function
$patterns = [
    // Format: /exact_path_to_find/ => helper_function_replacement

    // GURU pages
    '/pages\/guru\/(\w+)\.php/i' => function ($matches) {
        return "guru_page('{$matches[1]}')";
    },

    // SISWA pages  
    '/pages\/siswa\/(\w+)\.php/i' => function ($matches) {
        return "siswa_page('{$matches[1]}')";
    },

    // ADMIN pages
    '/pages\/admin\/(\w+)\.php/i' => function ($matches) {
        return "admin_page('{$matches[1]}')";
    },
];

function scanDirectory($dir, $skipDirs, $skipFiles)
{
    $files = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));

    foreach ($iterator as $file) {
        if (!$file->isFile() || $file->getExtension() !== 'php') continue;

        $path = $file->getPathname();
        $relPath = str_replace($dir . '/', '', $path);

        // Skip files
        $shouldSkip = false;
        foreach ($skipDirs as $skipDir) {
            if (strpos($relPath, $skipDir) === 0) {
                $shouldSkip = true;
                break;
            }
        }

        $basename = basename($path);
        if (in_array($basename, $skipFiles)) {
            $shouldSkip = true;
        }

        if (!$shouldSkip) {
            $files[] = $path;
        }
    }

    return $files;
}

$baseDir = __DIR__;
$files = scanDirectory($baseDir, $skipDirs, $skipFiles);

echo "=== AUTO UPDATE LINKS (SIMPLE VERSION) ===\n";
echo "Scanning " . count($files) . " files...\n\n";

$stats = [
    'updated' => 0,
    'skipped' => 0,
    'errors' => 0,
];

foreach ($files as $filepath) {
    $content = file_get_contents($filepath);
    $originalContent = $content;
    $replaced = false;

    // Check if file memiliki pattern yang perlu diupdate
    if (preg_match('/pages\/(guru|siswa|admin)\/\w+\.php/i', $content)) {
        // Update guru pages
        $content = preg_replace_callback(
            '/["\']pages\/guru\/(\w+)\.php["\']/i',
            function ($m) {
                return "<?= guru_page('{$m[1]}') ?>";
            },
            $content
        );

        // Update siswa pages
        $content = preg_replace_callback(
            '/["\']pages\/siswa\/(\w+)\.php["\']/i',
            function ($m) {
                return "<?= siswa_page('{$m[1]}') ?>";
            },
            $content
        );

        // Update admin pages
        $content = preg_replace_callback(
            '/["\']pages\/admin\/(\w+)\.php["\']/i',
            function ($m) {
                return "<?= admin_page('{$m[1]}') ?>";
            },
            $content
        );

        if ($content !== $originalContent) {
            if (file_put_contents($filepath, $content) !== false) {
                $relPath = str_replace($baseDir . '/', '', $filepath);
                echo "✓ UPDATED: $relPath\n";
                $stats['updated']++;
                $replaced = true;
            } else {
                $relPath = str_replace($baseDir . '/', '', $filepath);
                echo "✗ ERROR writing: $relPath\n";
                $stats['errors']++;
            }
        }
    }

    if (!$replaced) {
        $stats['skipped']++;
    }
}

echo "\n=== SUMMARY ===\n";
echo "Updated: " . $stats['updated'] . " files\n";
echo "Skipped: " . $stats['skipped'] . " files\n";
echo "Errors:  " . $stats['errors'] . " files\n";
echo "Total:   " . count($files) . " files\n";
