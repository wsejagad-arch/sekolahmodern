<?php
/**
 * Simple File-Based Cache System
 * Used to reduce database load during peak hours.
 */
class FileCache {
    private static $cacheDir = __DIR__ . '/../cache/';

    /**
     * Set cache value
     * @param string $key Cache key
     * @param mixed $value Data to store
     * @param int $ttl Time to live in seconds (default 300s = 5m)
     */
    public static function set($key, $value, $ttl = 300) {
        if (!is_dir(self::$cacheDir)) {
            @mkdir(self::$cacheDir, 0777, true);
            // Protect cache directory
            @file_put_contents(self::$cacheDir . '.htaccess', "Deny from all");
        }

        $cacheFile = self::getFilePath($key);
        $data = [
            'expires' => time() + $ttl,
            'content' => $value
        ];

        // Use locking to prevent race conditions during high concurrency
        $tmpFile = $cacheFile . '.tmp.' . uniqid();
        if (@file_put_contents($tmpFile, serialize($data))) {
            @rename($tmpFile, $cacheFile);
        }
    }

    /**
     * Get cache value
     * @param string $key Cache key
     * @return mixed|false Returns false if not found or expired
     */
    public static function get($key) {
        $cacheFile = self::getFilePath($key);
        if (!file_exists($cacheFile)) {
            return false;
        }

        $content = @file_get_contents($cacheFile);
        if (!$content) {
            return false;
        }

        $data = @unserialize($content);
        if ($data === false || !is_array($data) || !isset($data['expires'])) {
            return false;
        }

        if (time() > $data['expires']) {
            @unlink($cacheFile);
            return false;
        }

        return $data['content'];
    }

    /**
     * Invalidate/Delete cache key
     */
    public static function delete($key) {
        $cacheFile = self::getFilePath($key);
        if (file_exists($cacheFile)) {
            @unlink($cacheFile);
        }
    }

    /**
     * Clear all cache files
     */
    public static function clear() {
        if (is_dir(self::$cacheDir)) {
            $files = glob(self::$cacheDir . '*.cache');
            foreach ($files as $file) {
                @unlink($file);
            }
        }
    }

    private static function getFilePath($key) {
        return self::$cacheDir . md5($key) . '.cache';
    }
}
