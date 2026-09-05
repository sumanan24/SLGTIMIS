<?php
/**
 * Hikvision Device Configuration
 * 
 * Update these settings to match your Hikvision fingerprint device
 */

return [
    // Match staff_attendance sync (working Digest path for DS-K1T320MFWX)
    'host' => '172.16.0.230',
    'port' => 80,
    'username' => 'admin',
    'password' => 'TCI@itgls2025#@',
    'timeout' => 15,
    // false = HTTP (same as HIKVISION_USE_HTTPS in staff_attendance/config.php)
    'ssl' => false,
    
    'auto_sync' => [
        'enabled' => false,
        'interval' => 3600,
        'last_sync' => null
    ],
    
    'staff_mapping' => [
    ]
];

