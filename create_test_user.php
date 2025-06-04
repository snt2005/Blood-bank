<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';

echo "<h2>Creating Test User</h2>";

try {
    $pdo = getConnection();
    echo "Database connection successful.<br>";
    
    $pdo->beginTransaction();
    echo "Transaction started.<br>";

    // Create test user
    $username = 'testuser';
    $password = password_hash('testpass123', PASSWORD_DEFAULT);
    $email = 'test@example.com';
    $role = 'donor';

    // Check if user already exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    if ($stmt->fetchColumn() > 0) {
        echo "<div style='color: orange;'>Test user already exists.</div>";
        exit;
    }

    // Insert user
    $stmt = $pdo->prepare("
        INSERT INTO users (username, password, email, role, status)
        VALUES (?, ?, ?, ?, 'active')
    ");
    $stmt->execute([$username, $password, $email, $role]);
    $user_id = $pdo->lastInsertId();
    echo "User created with ID: " . $user_id . "<br>";

    // Insert donor details
    $stmt = $pdo->prepare("
        INSERT INTO donors (user_id, name, blood_group, age, weight, contact, city, address)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $user_id,
        'Test User',
        'O+',
        25,
        70,
        '1234567890',
        'Test City',
        'Test Address'
    ]);
    echo "Donor details added.<br>";

    // Create welcome notification
    $stmt = $pdo->prepare("
        INSERT INTO notifications (user_id, title, message, type)
        VALUES (?, ?, ?, 'info')
    ");
    $stmt->execute([
        $user_id,
        'Welcome to Blood Bank',
        "Welcome to Blood Bank Management System! Your account has been created successfully."
    ]);
    echo "Welcome notification created.<br>";

    $pdo->commit();
    echo "<div style='color: green; font-weight: bold;'>Test user created successfully!</div>";
    echo "<div style='margin-top: 20px; padding: 10px; background-color: #f0f0f0;'>";
    echo "<strong>Test Credentials:</strong><br>";
    echo "Username: testuser<br>";
    echo "Password: testpass123<br>";
    echo "Role: donor<br>";
    echo "</div>";

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "<div style='color: red; font-weight: bold;'>Error creating test user:</div>";
    echo "<div style='color: red;'>" . $e->getMessage() . "</div>";
    echo "<div style='color: red;'>Stack trace:</div>";
    echo "<pre style='color: red;'>" . $e->getTraceAsString() . "</pre>";
}
?> 