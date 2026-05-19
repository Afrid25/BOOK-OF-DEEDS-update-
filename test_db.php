<?php
require_once 'includes/db_connect.php';

echo "Testing Database Connection...\n";

if ($mysqli) {
    echo "MySQLi Connection Successful!\n";
}

if ($pdo) {
    echo "PDO Connection Successful!\n";
}

// Test inserting a user
$username = "TestUser";
$email = "test@example.com";
$password = password_hash("password123", PASSWORD_DEFAULT);

try {
    $stmt = $pdo->prepare("INSERT INTO users (user_name, email, password_hash) VALUES (?, ?, ?)");
    $stmt->execute([$username, $email, $password]);
    echo "User insertion successful!\n";
    
    $user_id = $pdo->lastInsertId();
    
    // Test fetching the user
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if ($user && $user['user_name'] === $username) {
        echo "User retrieval successful!\n";
    }
    
    // Clean up
    $pdo->exec("DELETE FROM users WHERE id = $user_id");
    echo "Cleanup successful!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
