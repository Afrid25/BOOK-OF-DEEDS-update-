<?php
session_start();
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book of Deeds - Feed</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">Book of Deeds</a>
            <div class="navbar-nav ms-auto">
                <span class="nav-link text-white">Welcome, <?php echo htmlspecialchars($_SESSION["user_name"]); ?></span>
                <a class="nav-link" href="api/auth/logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-8">
                <h3>Your Personalized Feed</h3>
                <div id="feed-container">
                    <!-- Feed items will be loaded here via JS -->
                </div>
            </div>
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Educational News</h5>
                        <?php include 'components/educational_news_feed.php'; ?>
                    </div>
                </div>
                <div class="card">
                    <div class="card-body text-center">
                        <h5 class="card-title">AI Chatbot</h5>
                        <p>Need help with your studies?</p>
                        <a href="Ai chatbot.php/Aichatbot.php" class="btn btn-outline-primary">Chat with UniAI</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/personalized_feed.js"></script>
</body>
</html>
