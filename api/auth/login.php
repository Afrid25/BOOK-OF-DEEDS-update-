<?php
// Start the session at the very beginning
session_start();

// The 'require_once' will now get the $pdo object from your connection file
require_once '../../includes/db_connect.php';
global $pdo;

if (!$pdo) {
    header("location: ../../index.php?error=db_connection_failed");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';

    if (empty($email) || empty($password)) {
        header("location: ../../index.php?error=missing_fields");
        exit();
    }

    try {
        // Prepare the SQL statement using PDO
        $sql = "SELECT id, user_name, password_hash, role FROM users WHERE email = ?";
        $stmt = $pdo->prepare($sql);

        // Execute the statement with the email parameter
        $stmt->execute([$email]);

        // Fetch the user as an associative array. No more bind_result!
        // The fetch() method returns the row, or false if no row is found.
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Check if a user was found AND if the password is correct
        if ($user && password_verify($password, $user['password_hash'])) {
            
            // SUCCESS! Password is correct.
            session_regenerate_id(true);

            // Store data in session variables from the $user array
            $_SESSION["user_id"] = $user['id'];
            $_SESSION["user_name"] = $user['user_name'];
            $_SESSION["role"] = $user['role'];
            $_SESSION["loggedin"] = true;

            // Redirect based on role
            if ($user['role'] === 'admin') {
                header("location: ../../admin/dashboard.php");
            } else {
                header("location: ../../feed.php");
            }
            exit();

        } else {
            // Either the user was not found or the password was incorrect.
            header("location: ../../index.php?error=invalid_credentials");
            exit();
        }

    } catch (PDOException $e) {
        // If something goes wrong with the query, log it and show a generic error.
        error_log("Login PDOException: " . $e->getMessage());
        header("location: ../../index.php?error=server_error");
        exit();
    }
} else {
    header("location: ../../index.php");
    exit();
}
?>