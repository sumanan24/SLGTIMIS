<?php
/**
 * Hikvision Device Configuration
 * 
 * Update these settings to match your Hikvision fingerprint device
 */

return [
    // Device IP address
    'host' => '172.16.0.230',
    
    // Device port (default: 80 for HTTP, 443 for HTTPS)
    // Common ports: 80 (HTTP), 443 (HTTPS), 8000, 8001
    // For ZKTeco devices, port 4370 is used (requires TCP socket, not HTTP)
    'port' => 443,
    
    // Device username
    'username' => 'admin',
    
    // Device password
    'password' => 'TCI@itgls2025#@',
    
    // Connection timeout in seconds
    'timeout' => 10,
    
    // Enable SSL/HTTPS (set to true if device uses HTTPS)
    'ssl' => true,
    
    // Auto-sync settings
    'auto_sync' => [
        'enabled' => false,
        'interval' => 3600, // Sync every hour (in seconds)
        'last_sync' => null
    ],
    
    // Staff mapping (optional - maps employee_no from device to staff_id in system)
    // Leave empty to auto-detect based on staff_id or staff_nic
    'staff_mapping' => [
        // Example:
        // '001' => 'STF001',
        // '002' => 'STF002',
    ]
];

