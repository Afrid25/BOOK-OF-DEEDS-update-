<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book of Deeds - Forgot Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .forgot-container { max-width: 400px; margin: 100px auto; padding: 30px; background: white; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
    </style>
</head>
<body>
    <div class="container">
        <div class="forgot-container">
            <h2 class="text-center mb-4">Forgot Password?</h2>
            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($_GET['error']); ?></div>
            <?php endif; ?>
            <p class="text-muted text-center mb-4">Enter your email address and we'll send you an OTP to reset your password.</p>
            <form action="api/auth/forgot_password.php" method="POST">
                <div class="mb-3">
                    <label for="email" class="form-label">Email address</label>
                    <input type="email" name="email" class="form-control" id="email" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Send OTP</button>
            </form>
            <div class="mt-3 text-center">
                <p><a href="index.php">Back to Login</a></p>
            </div>
        </div>
    </div>
</body>
</html>
