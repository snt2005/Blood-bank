<?php
require_once 'config/database.php';

try {
    $pdo = getConnection();
    echo "Database connection successful!\n";

    // Check if tables exist
    $tables = ['users', 'hospitals', 'donors', 'recipients', 'appointments', 'blood_units', 'blood_requests', 'emergency_requests', 'notifications'];
    
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "Table '$table' exists\n";
            
            // Show table structure
            $stmt = $pdo->query("DESCRIBE $table");
            echo "Structure of '$table':\n";
            while ($row = $stmt->fetch()) {
                echo "  - {$row['Field']}: {$row['Type']}\n";
            }
        } else {
            echo "Table '$table' does not exist!\n";
        }
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?> 