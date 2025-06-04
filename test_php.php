<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>PHP Test</h1>";
echo "PHP is working!<br>";
echo "PHP Version: " . phpversion() . "<br>";

// Test database connection
try {
    require_once 'config/database.php';
    $pdo = getConnection();
    echo "Database connection successful!<br>";
    
    // Test query
    $stmt = $pdo->query("SELECT DATABASE()");
    echo "Current database: " . $stmt->fetchColumn() . "<br>";
    
    // List tables
    $stmt = $pdo->query("SHOW TABLES");
    echo "<br>Tables in database:<br>";
    while ($row = $stmt->fetch()) {
        echo "- " . $row[0] . "<br>";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
}
?> 