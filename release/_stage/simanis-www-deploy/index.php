<?php
/**
 * index.php - Entry point for SIMANIS application
 * Redirect all root requests to login.php for fast authentication flow
 */
header("Location: login.php", true, 301);
exit;