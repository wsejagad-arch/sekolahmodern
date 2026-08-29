<?php
/**
 * index.php - Entry point for SIMANIS application
 * Redirect all root requests to login.php for fast authentication flow
 */
header("Location: v2/public/", true, 301);
exit;