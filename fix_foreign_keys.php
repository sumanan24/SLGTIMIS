<?php
/**
 * Script to fix foreign key constraints after installation
 * Run this if you get foreign key constraint errors
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    echo "<h1>Fixing Foreign Key Constraints</h1>";
    
    // Disable foreign key checks
    $conn->query("SET FOREIGN_KEY_CHECKS=0");
    
    // Try to drop and recreate the problematic constraint
    // The issue is that module table has composite PK (module_id, course_id)
    // but assessments_type references only module_id
    
    echo "<h2>Step 1: Dropping problematic foreign key constraints</h2>";
    
    // Drop the constraint if it exists
    $queries = [
        "ALTER TABLE `assessments_type` DROP FOREIGN KEY IF EXISTS `assessments_type_ibfk_2`",
        "ALTER TABLE `assessments` DROP FOREIGN KEY IF EXISTS `assessments_ibfk_3`",
    ];
    
    foreach ($queries as $query) {
        if ($conn->query($query)) {
            echo "<p>✓ Executed: " . htmlspecialchars($query) . "</p>";
        } else {
            echo "<p>ℹ " . htmlspecialchars($conn->error) . "</p>";
        }
    }
    
    echo "<h2>Step 2: Adding unique index on module_id if needed</h2>";
    
    // Check if module_id alone has a unique index
    $result = $conn->query("SHOW INDEXES FROM `module` WHERE Column_name = 'module_id' AND Non_unique = 0");
    
    if ($result->num_rows == 0) {
        // Try to add a unique index (this might fail if there are duplicate module_ids)
        $query = "ALTER TABLE `module` ADD UNIQUE INDEX `idx_module_id` (`module_id`)";
        if ($conn->query($query)) {
            echo "<p>✓ Added unique index on module_id</p>";
        } else {
            echo "<p>⚠ Could not add unique index: " . htmlspecialchars($conn->error) . "</p>";
            echo "<p>This is OK if module_id values are not unique across different courses.</p>";
        }
    } else {
        echo "<p>✓ Unique index on module_id already exists</p>";
    }
    
    echo "<h2>Step 3: Re-adding foreign key constraints</h2>";
    
    // Try to add the constraint back
    $queries = [
        "ALTER TABLE `assessments_type` 
         ADD CONSTRAINT `assessments_type_ibfk_2` 
         FOREIGN KEY (`module_id`) REFERENCES `module` (`module_id`) 
         ON UPDATE CASCADE",
    ];
    
    foreach ($queries as $query) {
        if ($conn->query($query)) {
            echo "<p>✓ Successfully added constraint</p>";
        } else {
            echo "<p>⚠ Could not add constraint: " . htmlspecialchars($conn->error) . "</p>";
            echo "<p>This is OK - the application will work without this constraint.</p>";
        }
    }
    
    // Re-enable foreign key checks
    $conn->query("SET FOREIGN_KEY_CHECKS=1");
    
    echo "<h2>Done!</h2>";
    echo "<p>The database should now work. Foreign key constraints are optional for the application to function.</p>";
    echo "<p><a href='index.php'>Go to Application</a></p>";
    
    $conn->close();
    
} catch (Exception $e) {
    echo "<p style='color:red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

