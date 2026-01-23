<?php
/**
 * Script to temporarily disable .htaccess for testing
 */

$htaccessFile = __DIR__ . '/.htaccess';
$backupFile = __DIR__ . '/.htaccess.backup';

if (file_exists($htaccessFile)) {
    if (rename($htaccessFile, $backupFile)) {
        echo "<h1>Success!</h1>";
        echo "<p>.htaccess has been renamed to .htaccess.backup</p>";
        echo "<p>Try accessing <a href='index.php'>index.php</a> now.</p>";
        echo "<p>If it works, the issue is with .htaccess configuration.</p>";
        echo "<p><a href='restore_htaccess.php'>Click here to restore .htaccess</a></p>";
    } else {
        echo "<h1>Error</h1>";
        echo "<p>Could not rename .htaccess. Please rename it manually.</p>";
    }
} else {
    echo "<p>.htaccess file not found or already disabled.</p>";
}

