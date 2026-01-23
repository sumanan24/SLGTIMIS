<?php
/**
 * Script to restore .htaccess
 */

$htaccessFile = __DIR__ . '/.htaccess';
$backupFile = __DIR__ . '/.htaccess.backup';

if (file_exists($backupFile)) {
    if (rename($backupFile, $htaccessFile)) {
        echo "<h1>Success!</h1>";
        echo "<p>.htaccess has been restored.</p>";
    } else {
        echo "<h1>Error</h1>";
        echo "<p>Could not restore .htaccess. Please rename it manually.</p>";
    }
} else {
    echo "<p>.htaccess.backup file not found.</p>";
}

