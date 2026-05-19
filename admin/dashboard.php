<?php
//======================================================================
// Part 1: SETUP, SECURITY, AND ROUTING
//======================================================================

session_start();
// Use the absolute path from the document root for reliability.
// Ensure this path is correct for your server setup.
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/db_connect.php';
// Use $pdo instead of $mysqli throughout this file

// Security check: must be logged in and must be an admin.
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !isset($_SESSION["role"]) || $_SESSION["role"] !== 'admin') {
    header("location: /index.php?error=unauthorized");
    exit;
}

// Define page variables
$admin_name = $_SESSION["user_name"] ?? 'Admin';
$current_view = $_GET['view'] ?? 'dashboard'; // Default view is 'dashboard'
$search_query = trim($_GET['search'] ?? '');

// Expanded router for a more intelligent panel
$page_titles = [
    'dashboard' => 'Admin Dashboard',
    'users' => 'Manage Users',
    'challenges' => 'Manage Challenges',
    'challenge_submissions' => 'Challenge Submissions',
    'resources' => 'Manage Resources',
    'feedback' => 'View Feedback',
    'events' => 'Manage Events',
    'alumni' => 'Alumni Posts',
    'test_results' => 'View Test Results',
    'startup_ideas' => 'Startup Idea Hub',
    'mentorship' => 'Mentorship Requests',
];
$page_title = $page_titles[$current_view] ?? 'Admin Panel';

// Action Feedback (for success/error messages after POST)
$feedback_message = '';
$feedback_type = '';
if (isset($_SESSION['feedback_message'])) {
    $feedback_message = $_SESSION['feedback_message'];
    $feedback_type = $_SESSION['feedback_type'];
    unset($_SESSION['feedback_message']);
    unset($_SESSION['feedback_type']);
}

//======================================================================
// Part 2: POST ACTION HANDLING (FIXED & STANDARDIZED WITH PDO)
//======================================================================

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? '';

    try {
        // --- User Actions ---
        if ($action == 'create_user' || $action == 'edit_user') {
            $username = trim($_POST['username']);
            $email = trim($_POST['email']);
            $role = $_POST['role'];
            $password = $_POST['password'];

            if (empty($username) || empty($email) || empty($role) || ($action == 'create_user' && empty($password))) {
                 throw new Exception("All fields are required.");
            }

            if ($action == 'create_user') {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (user_name, email, password_hash, role) VALUES (?, ?, ?, ?)");
                $stmt->execute([$username, $email, $password_hash, $role]);
                $_SESSION['feedback_message'] = "User created successfully.";
            } else { // edit_user
                $user_id = $_POST['user_id'];
                if (!empty($password)) {
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET user_name = ?, email = ?, role = ?, password_hash = ? WHERE id = ?");
                    $stmt->execute([$username, $email, $role, $password_hash, $user_id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET user_name = ?, email = ?, role = ? WHERE id = ?");
                    $stmt->execute([$username, $email, $role, $user_id]);
                }
                $_SESSION['feedback_message'] = "User updated successfully.";
            }
            $_SESSION['feedback_type'] = 'success';

        } elseif ($action == 'delete_user') {
            $user_id = $_POST['user_id'];
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $_SESSION['feedback_message'] = "User deleted successfully.";
            $_SESSION['feedback_type'] = 'success';
        }

        // --- Challenge Actions ---
        elseif ($action == 'create_challenge' || $action == 'edit_challenge') {
            $title = $_POST['title'];
            $description = $_POST['description'];
            $points = $_POST['points_reward'];
            $start_date = $_POST['start_date'];
            $end_date = $_POST['end_date'];
            $is_active = isset($_POST['is_active']) ? 1 : 0;

            if ($action == 'create_challenge') {
                $stmt = $pdo->prepare("INSERT INTO challenges (title, description, points_reward, start_date, end_date, is_active) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$title, $description, $points, $start_date, $end_date, $is_active]);
                $_SESSION['feedback_message'] = "Challenge created successfully.";
            } else {
                $challenge_id = $_POST['challenge_id'];
                $stmt = $pdo->prepare("UPDATE challenges SET title=?, description=?, points_reward=?, start_date=?, end_date=?, is_active=? WHERE id=?");
                $stmt->execute([$title, $description, $points, $start_date, $end_date, $is_active, $challenge_id]);
                $_SESSION['feedback_message'] = "Challenge updated successfully.";
            }
            $_SESSION['feedback_type'] = 'success';
        } elseif ($action == 'delete_challenge') {
            $challenge_id = $_POST['challenge_id'];
            $stmt = $pdo->prepare("DELETE FROM challenges WHERE id = ?");
            $stmt->execute([$challenge_id]);
            $_SESSION['feedback_message'] = "Challenge deleted successfully.";
            $_SESSION['feedback_type'] = 'success';
        }

        // --- Resource Actions ---
        elseif ($action == 'create_resource' || $action == 'edit_resource') {
            $category = $_POST['category'];
            $title = $_POST['title'];
            $description = $_POST['description'];
            $link = $_POST['link'];
            if ($action == 'create_resource') {
                $stmt = $pdo->prepare("INSERT INTO resources (category, title, description, link) VALUES (?, ?, ?, ?)");
                $stmt->execute([$category, $title, $description, $link]);
                $_SESSION['feedback_message'] = "Resource added successfully.";
            } else {
                $resource_id = $_POST['resource_id'];
                $stmt = $pdo->prepare("UPDATE resources SET category=?, title=?, description=?, link=? WHERE id=?");
                $stmt->execute([$category, $title, $description, $link, $resource_id]);
                $_SESSION['feedback_message'] = "Resource updated successfully.";
            }
            $_SESSION['feedback_type'] = 'success';
        } elseif ($action == 'delete_resource') {
            $resource_id = $_POST['resource_id'];
            $stmt = $pdo->prepare("DELETE FROM resources WHERE id = ?");
            $stmt->execute([$resource_id]);
            $_SESSION['feedback_message'] = "Resource deleted successfully.";
            $_SESSION['feedback_type'] = 'success';
        }

        // --- Feedback Action ---
        elseif ($action == 'delete_feedback') {
            $feedback_id = $_POST['feedback_id'];
            $stmt = $pdo->prepare("DELETE FROM feedback WHERE id = ?");
            $stmt->execute([$feedback_id]);
            $_SESSION['feedback_message'] = "Feedback deleted successfully.";
            $_SESSION['feedback_type'] = 'success';
        }

        // --- Challenge Submission Action ---
        elseif ($action == 'update_submission_winner') {
            $submission_id = $_POST['submission_id'];
            $stmt = $pdo->prepare("UPDATE challenge_submissions SET is_winner = 1 WHERE id = ?");
            $stmt->execute([$submission_id]);
            $_SESSION['feedback_message'] = "Submission marked as winner.";
            $_SESSION['feedback_type'] = 'success';
        }

    } catch (Exception $e) {
        // Catch both PDOException and general Exception for robust error handling
        $_SESSION['feedback_message'] = "An error occurred: " . $e->getMessage();
        $_SESSION['feedback_type'] = 'error';
    }

    // Redirect to prevent form resubmission
    header("Location: ?view=" . $current_view);
    exit();
}

//======================================================================
// Part 3: DATA FETCHING FOR VIEW (ALL USING PDO)
//======================================================================

switch ($current_view) {
    case 'users':
        $sql = "SELECT id, user_name, email, role, created_at, points, current_streak FROM users";
        if (!empty($search_query)) {
            $sql .= " WHERE user_name LIKE ? OR email LIKE ?";
            $search_param = "%{$search_query}%";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$search_param, $search_param]);
            $result = $stmt;
        } else {
            $sql .= " ORDER BY created_at DESC";
            $result = $pdo->query($sql);
        }
        break;
    case 'challenges':
        $result = $pdo->query("SELECT * FROM challenges ORDER BY start_date DESC");
        break;
    case 'resources':
        $result = $pdo->query("SELECT * FROM resources ORDER BY category, title");
        break;
    case 'feedback':
        $result = $pdo->query("SELECT * FROM feedback ORDER BY created_at DESC");
        break;
    case 'events':
        $result = $pdo->query("SELECT e.*, u.user_name as creator_name FROM events e JOIN users u ON e.creator_user_id = u.id ORDER BY event_date DESC");
        break;
    case 'alumni':
        $result = $pdo->query("SELECT ap.*, u.user_name as alumni_name FROM alumni_posts ap JOIN users u ON ap.alumni_user_id = u.id ORDER BY ap.created_at DESC");
        break;
    case 'challenge_submissions':
        $result = $pdo->query("
            SELECT cs.id, cs.submission_link, cs.submission_text, cs.is_winner, cs.submitted_at, 
                   c.title as challenge_title, u.user_name
            FROM challenge_submissions cs
            JOIN challenges c ON cs.challenge_id = c.id
            JOIN users u ON cs.user_id = u.id
            ORDER BY cs.submitted_at DESC
        ");
        break;
    case 'test_results':
        $result = $pdo->query("
            SELECT tr.id, tr.score, tr.submitted_at, 
                   u.user_name, t.test_name
            FROM test_results tr
            JOIN users u ON tr.user_id = u.id
            JOIN tests t ON tr.test_id = t.id
            ORDER BY tr.submitted_at DESC
        ");
        break;
    case 'startup_ideas':
        $result = $pdo->query("
            SELECT si.id, si.title, si.pitch, si.created_at, u.user_name
            FROM startup_ideas si
            JOIN users u ON si.user_id = u.id
            ORDER BY si.created_at DESC
        ");
        break;
    case 'mentorship':
        $result = $pdo->query("
            SELECT mr.id, mr.subject, mr.status, mr.created_at, 
                   mentee.user_name as mentee_name, 
                   mentor.user_name as mentor_name
            FROM mentorship_requests mr
            JOIN users mentee ON mr.mentee_user_id = mentee.id
            JOIN users mentor ON mr.mentor_user_id = mentor.id
            ORDER BY mr.created_at DESC
        ");
        break;
    case 'dashboard':
    default:
        $total_users = $pdo->query("SELECT COUNT(id) AS count FROM users")->fetchColumn() ?? 0;
        $total_ideas = $pdo->query("SELECT COUNT(id) AS count FROM startup_ideas")->fetchColumn() ?? 0;
        $active_challenges = $pdo->query("SELECT COUNT(id) AS count FROM challenges WHERE is_active = 1 AND end_date >= CURDATE()")->fetchColumn() ?? 0;
        $total_tests_taken = $pdo->query("SELECT COUNT(id) AS count FROM test_results")->fetchColumn() ?? 0;
        $pending_submissions = $pdo->query("SELECT COUNT(id) AS count FROM challenge_submissions WHERE is_winner = 0")->fetchColumn() ?? 0;
        $pending_feedback = $pdo->query("SELECT COUNT(id) AS count FROM feedback WHERE is_viewed = 0")->fetchColumn() ?? 0;
        $chart_data_result = $pdo->query("
            SELECT DATE(created_at) as date, COUNT(id) as count 
            FROM users 
            WHERE created_at >= CURDATE() - INTERVAL 7 DAY 
            GROUP BY DATE(created_at) 
            ORDER BY date ASC
        ");
        $chart_labels = [];
        $chart_values = [];
        while($row = $chart_data_result->fetch(PDO::FETCH_ASSOC)) {
            $chart_labels[] = date("M d", strtotime($row['date']));
            $chart_values[] = $row['count'];
        }
        break;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - Book of Deeds</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!--======================================================================-->
    <!-- Part 4: EMBEDDED CSS (REDESIGNED FOR MODERN LOOK)                  -->
    <!--======================================================================-->
    <style>
        :root {
            --primary-color: #4F46E5; --primary-light: #EEF2FF;
            --secondary-color: #1F2937; --secondary-light: #374151;
            --text-dark: #111827; --text-light: #6B7280; --text-white: #F9FAFB;
            --bg-light: #F9FAFB; --bg-white: #FFFFFF; --border-color: #E5E7EB;
            --green: #10B981; --red: #EF4444; --blue: #3B82F6; --orange: #F97316; --purple: #8B5CF6;
        }
        * { box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; margin: 0; background-color: var(--bg-light); color: var(--text-dark); -webkit-font-smoothing: antialiased; }
        
        .admin-container { display: flex; min-height: 100vh; }
        .sidebar { width: 260px; background: var(--secondary-color); color: var(--text-white); position: fixed; height: 100%; display: flex; flex-direction: column; z-index: 1000; }
        .main-content { margin-left: 260px; width: calc(100% - 260px); display: flex; flex-direction: column; }
        
        .sidebar-header { padding: 1.5rem; text-align: center; border-bottom: 1px solid var(--secondary-light); }
        .sidebar-header .brand-title { font-size: 1.5rem; margin: 0; color: var(--text-white); font-weight: 600; }
        .sidebar-nav { flex-grow: 1; overflow-y: auto; }
        .sidebar-nav ul { list-style: none; padding: 0; margin: 1rem 0; }
        .sidebar-nav .nav-heading { color: #9CA3AF; font-size: 0.75rem; font-weight: 600; padding: 0.5rem 1.5rem; text-transform: uppercase; letter-spacing: 0.05em; }
        .sidebar-nav a { display: flex; align-items: center; padding: 0.75rem 1.5rem; color: #D1D5DB; text-decoration: none; transition: all 0.2s ease; border-left: 4px solid transparent; font-weight: 500; }
        .sidebar-nav a i { margin-right: 1rem; width: 20px; text-align: center; font-size: 1.1rem; }
        .sidebar-nav a:hover { background: var(--secondary-light); color: var(--text-white); }
        .sidebar-nav li.active > a { background: var(--primary-color); color: var(--text-white); border-left-color: var(--blue); }
        
        .top-bar { background: var(--bg-white); padding: 1rem 2rem; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; z-index: 999; }
        .page-title h1 { margin: 0; font-size: 1.5rem; font-weight: 600; }
        
        .content-wrapper { padding: 2rem; flex-grow: 1; }

        .dashboard-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; }
        .stat-card { background: var(--bg-white); border-radius: 12px; padding: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.05), 0 1px 2px rgba(0,0,0,0.05); display: flex; align-items: center; gap: 1.5rem; }
        .stat-card .icon { font-size: 1.75rem; width: 50px; height: 50px; border-radius: 50%; display: grid; place-items: center; color: white; }
        .stat-card .content h3 { margin: 0 0 0.25rem 0; font-size: 0.9rem; color: var(--text-light); font-weight: 500; }
        .stat-card .content .number { margin: 0; font-size: 2rem; font-weight: 700; color: var(--text-dark); }
        .icon.bg-blue { background-color: var(--blue); } .icon.bg-green { background-color: var(--green); }
        .icon.bg-orange { background-color: var(--orange); } .icon.bg-purple { background-color: var(--purple); }
        .icon.bg-red { background-color: var(--red); } .icon.bg-teal { background-color: #14B8A6; }

        .content-box { background: var(--bg-white); padding: 2rem; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.05), 0 1px 2px rgba(0,0,0,0.05); margin-top: 2rem; }
        .content-box-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; border-bottom: 1px solid var(--border-color); padding-bottom: 1rem; }
        .content-box-header h2 { margin: 0; font-size: 1.25rem; }
        
        .search-bar form { display: flex; }
        .search-bar input { padding: 0.6rem 1rem; border: 1px solid var(--border-color); border-radius: 8px; width: 300px; }

        .data-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .data-table th, .data-table td { padding: 0.8rem 1rem; text-align: left; border-bottom: 1px solid var(--border-color); vertical-align: middle; word-wrap: break-word; }
        .data-table th { background: var(--bg-light); font-weight: 600; color: var(--text-light); font-size: 0.85rem; text-transform: uppercase; }
        .data-table .actions a, .data-table .actions button { margin-right: 0.5rem; color: var(--text-light); background: none; border: none; cursor: pointer; font-size: 1.1rem; }
        .data-table .actions a.edit, .data-table .actions button.edit-btn { color: var(--orange); }
        .data-table .actions button.delete-btn { color: var(--red); }
        .data-table .badge { padding: 0.25em 0.75em; border-radius: 999px; font-size: 0.8rem; font-weight: 600; text-transform: capitalize; }
        .badge.role-admin { background-color: #DBEAFE; color: #1E40AF; }
        .badge.role-user { background-color: #D1FAE5; color: #065F46; }
        .badge.role-alumni { background-color: #E0E7FF; color: #3730A3; }
        .badge.status-pending { background-color: #FEF3C7; color: #92400E; }
        .badge.status-accepted, .badge.status-winner { background-color: #D1FAE5; color: #065F46; }
        .badge.status-rejected { background-color: #FEE2E2; color: #991B1B; }
        
        .btn { padding: 0.6rem 1.2rem; border: none; border-radius: 8px; font-size: 0.9rem; font-weight: 500; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem; transition: all 0.2s ease; }
        .btn-primary { background: var(--primary-color); color: white; }
        .btn-primary:hover { background: #4338CA; }
        .btn-success { background: var(--green); color: white; }
        .btn-success:hover { background: #059669; }

        .notification { padding: 1rem; margin: 0 2rem 1rem; border-radius: 8px; color: white; display: none; }
        .notification.show { display: block; }
        .notification.success { background-color: var(--green); }
        .notification.error { background-color: var(--red); }
        
        .modal { display: none; position: fixed; z-index: 1001; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.6); backdrop-filter: blur(4px); }
        .modal-content { background-color: #fefefe; margin: 5% auto; padding: 2rem; border: 1px solid #888; width: 80%; max-width: 600px; border-radius: 12px; position: relative; animation: slide-down 0.4s ease-out; }
        @keyframes slide-down { from { transform: translateY(-50px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        .modal-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); padding-bottom: 1rem; margin-bottom: 1.5rem; }
        .modal-header h2 { margin: 0; }
        .close-btn { color: #aaa; font-size: 28px; font-weight: bold; cursor: pointer; }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 500; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 8px; font-family: 'Inter', sans-serif; }
        .form-group textarea { min-height: 100px; resize: vertical; }
        .form-actions { margin-top: 1.5rem; text-align: right; }
        .checkbox-group { display: flex; align-items: center; gap: 0.5rem; }
        /*======================================================================*/
/* Part 9: RESPONSIVE STYLES                                            */
/*======================================================================*/
#menu-toggle {
    display: none; /* Hidden on desktop */
    background: transparent;
    color: var(--text-dark);
    padding: 0.5rem;
    margin-right: 1rem;
}
.sidebar-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 999;
}

/* --- Tablet & Mobile Styles (below 992px) --- */
@media (max-width: 992px) {
    .sidebar {
        position: fixed;
        left: 0;
        top: 0;
        height: 100%;
        transform: translateX(-100%);
        transition: transform 0.3s ease-in-out;
        z-index: 1001; /* Must be on top of overlay */
    }
    .sidebar.is-open {
        transform: translateX(0);
    }
    .main-content {
        margin-left: 0;
        width: 100%;
    }
    #menu-toggle {
        display: flex; /* Show the hamburger menu */
    }
    .top-bar .user-info {
        display: none; /* Hide welcome message on small screens for space */
    }
}

/* --- Mobile-Specific Table Styles (below 768px) --- */
@media (max-width: 768px) {
    .content-wrapper { padding: 1rem; }
    .top-bar { padding: 0.75rem 1rem; }
    .modal-content { width: 95%; margin: 10% auto; }
    
    .data-table {
        border: 0;
    }
    .data-table thead {
        display: none; /* Hide table headers */
    }
    .data-table tr {
        display: block;
        margin-bottom: 1rem;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    }
    .data-table td {
        display: block;
        text-align: right; /* Value on the right */
        padding-left: 50%; /* Make space for the label */
        position: relative;
        border-bottom: 1px solid var(--border-color);
    }
    .data-table td:last-child {
        border-bottom: 0;
    }
    .data-table td::before {
        content: attr(data-label); /* Use the data-label attribute */
        position: absolute;
        left: 1rem;
        width: calc(50% - 2rem);
        text-align: left;
        font-weight: 600;
        color: var(--text-dark);
    }
    .data-table .actions {
        text-align: right;
    }
    .data-table td.actions::before {
      /* Hide label for actions cell if it looks odd */
      content: "";
    }
}
    </style>
</head>
<body>
    <div class="admin-container">
        <!--======================================================================-->
        <!-- Part 5: SIDEBAR NAVIGATION (REDESIGNED WITH ICONS & GROUPS)          -->
        <!--======================================================================-->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2 class="brand-title"><i class="fas fa-book-reader"></i> Book of Deeds</h2>
            </div>
            <nav class="sidebar-nav">
                <ul>
                    <li class="<?php echo ($current_view == 'dashboard') ? 'active' : ''; ?>">
                        <a href="?view=dashboard"><i class="fas fa-tachometer-alt fa-fw"></i> Dashboard</a>
                    </li>
                    <li class="nav-heading">Content Management</li>
                    <li class="<?php echo ($current_view == 'challenges') ? 'active' : ''; ?>">
                        <a href="?view=challenges"><i class="fas fa-trophy fa-fw"></i> Challenges</a>
                    </li>
                    <li class="<?php echo ($current_view == 'challenge_submissions') ? 'active' : ''; ?>">
                        <a href="?view=challenge_submissions"><i class="fas fa-paper-plane fa-fw"></i> Submissions</a>
                    </li>
                    <li class="<?php echo ($current_view == 'test_results') ? 'active' : ''; ?>">
                        <a href="?view=test_results"><i class="fas fa-file-signature fa-fw"></i> Test Results</a>
                    </li>
                    <li class="<?php echo ($current_view == 'resources') ? 'active' : ''; ?>">
                        <a href="?view=resources"><i class="fas fa-book-open fa-fw"></i> Resources</a>
                    </li>
                     <li class="<?php echo ($current_view == 'events') ? 'active' : ''; ?>">
                        <a href="?view=events"><i class="fas fa-calendar-alt fa-fw"></i> Events</a>
                    </li>

                    <li class="nav-heading">Community Management</li>
                    <li class="<?php echo ($current_view == 'users') ? 'active' : ''; ?>">
                        <a href="?view=users"><i class="fas fa-users-cog fa-fw"></i> Users</a>
                    </li>
                    <li class="<?php echo ($current_view == 'alumni') ? 'active' : ''; ?>">
                        <a href="?view=alumni"><i class="fas fa-user-graduate fa-fw"></i> Alumni Posts</a>
                    </li>
                     <li class="<?php echo ($current_view == 'startup_ideas') ? 'active' : ''; ?>">
                        <a href="?view=startup_ideas"><i class="fas fa-lightbulb fa-fw"></i> Startup Ideas</a>
                    </li>
                    <li class="<?php echo ($current_view == 'mentorship') ? 'active' : ''; ?>">
                        <a href="?view=mentorship"><i class="fas fa-hands-helping fa-fw"></i> Mentorship</a>
                    </li>
                    <li class="<?php echo ($current_view == 'feedback') ? 'active' : ''; ?>">
                        <a href="?view=feedback"><i class="fas fa-comment-dots fa-fw"></i> Feedback</a>
                    </li>
                </ul>
            </nav>
            <div class="sidebar-footer" style="padding: 1.5rem; border-top: 1px solid var(--secondary-light);">
                <a href="/api/auth/logout.php" style="display: block; text-align: center; text-decoration: none; color: #D1D5DB; font-weight: 500;"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </aside>

      <main class="main-content">
    <header class="top-bar">
        <!-- ADD THIS BUTTON -->
        <button id="menu-toggle" class="btn"><i class="fas fa-bars"></i></button>
        
        <div class="page-title">
            <h1><?php echo htmlspecialchars($page_title); ?></h1>
        </div>
                <div class="user-info">
                    <span>Welcome, <strong><?php echo htmlspecialchars($admin_name); ?></strong></span>
                </div>
            </header>
            
            <div class="content-wrapper">
                
                <?php if ($feedback_message): ?>
                    <div class="notification <?php echo $feedback_type; ?> show" style="margin-top: -1rem;">
                        <?php echo htmlspecialchars($feedback_message); ?>
                    </div>
                <?php endif; ?>

                <!--======================================================================-->
                <!-- Part 6: DYNAMIC CONTENT AREA (FIXED DATA FETCHING LOOPS)           -->
                <!--======================================================================-->
                <?php
                switch ($current_view):
                    case 'users': ?>
                        <div class="content-box">
                            <div class="content-box-header">
                                <div class="search-bar">
                                    <form method="GET">
                                        <input type="hidden" name="view" value="users">
                                        <input type="text" name="search" placeholder="Search by name or email..." value="<?php echo htmlspecialchars($search_query); ?>">
                                    </form>
                                </div>
                                <button class="btn btn-primary" onclick="openModal('user-modal')"><i class="fas fa-plus"></i> Add User</button>
                            </div>
                            <div style="overflow-x:auto;">
                            <table class="data-table">
                                <thead>
                                    <tr><th>User</th><th>Role</th><th>Points</th><th>Streak</th><th>Joined On</th><th>Actions</th></tr>
                                </thead>
                               <tbody>
    <?php while($item = $result->fetch(PDO::FETCH_ASSOC)): ?>
    <tr>
        <td data-label="User">
            <strong><?php echo htmlspecialchars($item['user_name']); ?></strong><br>
            <small class="text-light"><?php echo htmlspecialchars($item['email']); ?></small>
        </td>
        <td data-label="Role"><span class="badge role-<?php echo strtolower(htmlspecialchars($item['role'])); ?>"><?php echo htmlspecialchars($item['role']); ?></span></td>
        <td data-label="Points"><?php echo $item['points']; ?></td>
        <td data-label="Streak"><?php echo $item['current_streak']; ?> 🔥</td>
        <td data-label="Joined On"><?php echo date('M d, Y', strtotime($item['created_at'])); ?></td>
        <td data-label="Actions" class="actions">
            <button class="edit-btn" onclick="editUser(<?php echo htmlspecialchars(json_encode($item)); ?>)"><i class="fas fa-edit"></i></button>
            <form action="" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this user? This cannot be undone.');">
                <input type="hidden" name="action" value="delete_user">
                <input type="hidden" name="user_id" value="<?php echo $item['id']; ?>">
                <button type="submit" class="delete-btn"><i class="fas fa-trash-alt"></i></button>
            </form>
        </td>
    </tr>
    <?php endwhile; ?>
</tbody>
                            </table>
                            </div>
                        </div>
                        <?php break; ?>

                    <?php case 'challenges': ?>
                         <div class="content-box">
                            <div class="content-box-header">
                                <h2>All Challenges</h2>
                                <button class="btn btn-primary" onclick="openModal('challenge-modal')"><i class="fas fa-plus"></i> Create Challenge</button>
                            </div>
                            <div style="overflow-x:auto;">
                            <table class="data-table">
                                <thead><tr><th>Title</th><th>Points</th><th>Dates</th><th>Status</th><th>Actions</th></tr></thead>
                                <tbody>
                                    <?php while($item = $result->fetch(PDO::FETCH_ASSOC)): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['title']); ?></td>
                                        <td><?php echo $item['points_reward']; ?></td>
                                        <td><?php echo date('M d, Y', strtotime($item['start_date'])); ?> - <?php echo date('M d, Y', strtotime($item['end_date'])); ?></td>
                                        <td><?php echo $item['is_active'] ? '<span style="color:var(--green)">Active</span>' : '<span style="color:var(--text-light)">Inactive</span>'; ?></td>
                                        <td class="actions">
                                            <button class="edit-btn" onclick="editChallenge(<?php echo htmlspecialchars(json_encode($item)); ?>)"><i class="fas fa-edit"></i></button>
                                            <form action="" method="POST" style="display:inline;" onsubmit="return confirm('Delete this challenge?');">
                                                <input type="hidden" name="action" value="delete_challenge">
                                                <input type="hidden" name="challenge_id" value="<?php echo $item['id']; ?>">
                                                <button type="submit" class="delete-btn"><i class="fas fa-trash-alt"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                            </div>
                        </div>
                        <?php break; ?>
                    
                    <?php case 'resources': ?>
                         <div class="content-box">
                            <div class="content-box-header">
                                <h2>All Resources</h2>
                                <button class="btn btn-primary" onclick="openModal('resource-modal')"><i class="fas fa-plus"></i> Add Resource</button>
                            </div>
                            <div style="overflow-x:auto;">
                            <table class="data-table">
                                <thead><tr><th>Category</th><th>Title</th><th>Link</th><th>Actions</th></tr></thead>
                                <tbody>
                                    <?php while($item = $result->fetch(PDO::FETCH_ASSOC)): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['category']); ?></td>
                                        <td><strong><?php echo htmlspecialchars($item['title']); ?></strong><br><small><?php echo htmlspecialchars($item['description']); ?></small></td>
                                        <td><a href="<?php echo htmlspecialchars($item['link']); ?>" target="_blank">View Resource</a></td>
                                        <td class="actions">
                                            <button class="edit-btn" onclick="editResource(<?php echo htmlspecialchars(json_encode($item)); ?>)"><i class="fas fa-edit"></i></button>
                                            <form action="" method="POST" style="display:inline;" onsubmit="return confirm('Delete this resource?');">
                                                <input type="hidden" name="action" value="delete_resource">
                                                <input type="hidden" name="resource_id" value="<?php echo $item['id']; ?>">
                                                <button type="submit" class="delete-btn"><i class="fas fa-trash-alt"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                            </div>
                        </div>
                        <?php break; ?>

                    <!-- FIX STARTS HERE: Added missing view cases -->
                    <?php case 'events': ?>
                        <div class="content-box">
                            <div class="content-box-header"><h2>Upcoming & Past Events</h2></div>
                             <div style="overflow-x:auto;">
                            <table class="data-table">
                                <thead><tr><th>Title</th><th>Description</th><th>Date</th><th>Location</th><th>Creator</th></tr></thead>
                                <tbody>
                                    <?php while($item = $result->fetch(PDO::FETCH_ASSOC)): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($item['title']); ?></strong></td>
                                        <td style="max-width: 400px;"><?php echo htmlspecialchars($item['description']); ?></td>
                                        <td><?php echo date('M d, Y h:i A', strtotime($item['event_date'])); ?></td>
                                        <td><?php echo htmlspecialchars($item['location']); ?></td>
                                        <td><?php echo htmlspecialchars($item['creator_name']); ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                            </div>
                        </div>
                        <?php break; ?>

                    <?php case 'alumni': ?>
                        <div class="content-box">
                            <div class="content-box-header"><h2>Alumni Posts</h2></div>
                            <div style="overflow-x:auto;">
                            <table class="data-table">
                                <thead><tr><th>Title</th><th>Content</th><th>Alumnus</th><th>Posted On</th></tr></thead>
                                <tbody>
                                    <?php while($item = $result->fetch(PDO::FETCH_ASSOC)): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($item['title']); ?></strong></td>
                                        <td style="max-width: 400px;"><?php echo htmlspecialchars($item['content']); ?></td>
                                        <td><?php echo htmlspecialchars($item['alumni_name']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($item['created_at'])); ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                            </div>
                        </div>
                        <?php break; ?>
                    <!-- FIX ENDS HERE -->

                    <?php case 'challenge_submissions': ?>
                         <div class="content-box">
                            <div class="content-box-header"><h2>Challenge Submissions</h2></div>
                             <div style="overflow-x:auto;">
                            <table class="data-table">
                                <thead><tr><th>User</th><th>Challenge</th><th>Submission</th><th>Status</th><th>Actions</th></tr></thead>
                                <tbody>
                                    <?php while($item = $result->fetch(PDO::FETCH_ASSOC)): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($item['user_name']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($item['challenge_title']); ?></td>
                                        <td>
                                            <?php if($item['submission_link']): ?>
                                                <a href="<?php echo htmlspecialchars($item['submission_link']); ?>" target="_blank">View Link</a>
                                            <?php else: echo nl2br(htmlspecialchars($item['submission_text'])); endif; ?>
                                        </td>
                                        <td>
                                            <?php if($item['is_winner']): ?>
                                                <span class="badge status-winner">Winner</span>
                                            <?php else: ?>
                                                <span class="badge status-pending">Pending</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="actions">
                                            <?php if(!$item['is_winner']): ?>
                                            <form action="" method="POST" style="display:inline;" onsubmit="return confirm('Mark this submission as a winner?');">
                                                <input type="hidden" name="action" value="update_submission_winner">
                                                <input type="hidden" name="submission_id" value="<?php echo $item['id']; ?>">
                                                <button type="submit" class="btn btn-success" style="padding: 0.3rem 0.8rem; font-size: 0.8rem;">Mark as Winner</button>
                                            </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                            </div>
                        </div>
                        <?php break; ?>
                        
                    <?php case 'test_results': ?>
                         <div class="content-box">
                            <div class="content-box-header"><h2>All Test Results</h2></div>
                            <div style="overflow-x:auto;">
                            <table class="data-table">
                                <thead><tr><th>User</th><th>Test Name</th><th>Score</th><th>Date</th></tr></thead>
                                <tbody>
                                    <?php while($item = $result->fetch(PDO::FETCH_ASSOC)): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($item['user_name']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($item['test_name']); ?></td>
                                        <td><strong><?php echo number_format($item['score'], 2); ?>%</strong></td>
                                        <td><?php echo date('M d, Y h:i A', strtotime($item['submitted_at'])); ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                            </div>
                        </div>
                        <?php break; ?>

                    <?php case 'startup_ideas': ?>
                         <div class="content-box">
                            <div class="content-box-header"><h2>User Startup Ideas</h2></div>
                             <div style="overflow-x:auto;">
                            <table class="data-table">
                                <thead><tr><th>Title</th><th>Pitch</th><th>Submitted By</th><th>Date</th></tr></thead>
                                <tbody>
                                    <?php while($item = $result->fetch(PDO::FETCH_ASSOC)): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($item['title']); ?></strong></td>
                                        <td style="max-width: 400px;"><?php echo htmlspecialchars($item['pitch']); ?></td>
                                        <td><?php echo htmlspecialchars($item['user_name']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($item['created_at'])); ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                            </div>
                        </div>
                        <?php break; ?>

                     <?php case 'mentorship': ?>
                         <div class="content-box">
                            <div class="content-box-header"><h2>Mentorship Requests</h2></div>
                            <div style="overflow-x:auto;">
                            <table class="data-table">
                                <thead><tr><th>Mentee</th><th>Mentor</th><th>Subject</th><th>Status</th><th>Date</th></tr></thead>
                                <tbody>
                                    <?php while($item = $result->fetch(PDO::FETCH_ASSOC)): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['mentee_name']); ?></td>
                                        <td><?php echo htmlspecialchars($item['mentor_name']); ?></td>
                                        <td><?php echo htmlspecialchars($item['subject']); ?></td>
                                        <td><span class="badge status-<?php echo strtolower(htmlspecialchars($item['status'])); ?>"><?php echo htmlspecialchars($item['status']); ?></span></td>
                                        <td><?php echo date('M d, Y', strtotime($item['created_at'])); ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                            </div>
                        </div>
                        <?php break; ?>
                    
                     <?php case 'feedback': ?>
                         <div class="content-box">
                            <div class="content-box-header"><h2>User Feedback</h2></div>
                             <div style="overflow-x:auto;">
                            <table class="data-table">
                                <thead><tr><th>To</th><th>Subject</th><th>Message</th><th>Received</th><th>Actions</th></tr></thead>
                                <tbody>
                                     <?php while($item = $result->fetch(PDO::FETCH_ASSOC)): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars(ucfirst($item['feedback_to'])); ?></td>
                                        <td><?php echo htmlspecialchars($item['subject']); ?></td>
                                        <td><?php echo nl2br(htmlspecialchars($item['message'])); ?></td>
                                        <td><?php echo date('M d, Y h:i A', strtotime($item['created_at'])); ?></td>
                                        <td class="actions">
                                             <form action="" method="POST" style="display:inline;" onsubmit="return confirm('Delete this feedback?');">
                                                <input type="hidden" name="action" value="delete_feedback">
                                                <input type="hidden" name="feedback_id" value="<?php echo $item['id']; ?>">
                                                <button type="submit" class="delete-btn"><i class="fas fa-trash-alt"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                     <?php endwhile; ?>
                                </tbody>
                            </table>
                             </div>
                        </div>
                        <?php break; ?>

                    <?php default: // --- REDESIGNED DASHBOARD VIEW --- ?>
                        <section class="dashboard-grid">
                            <div class="stat-card">
                                <div class="icon bg-purple"><i class="fas fa-users"></i></div>
                                <div class="content"><h3>Total Users</h3><p class="number"><?php echo $total_users; ?></p></div>
                            </div>
                            <div class="stat-card">
                                <div class="icon bg-orange"><i class="fas fa-lightbulb"></i></div>
                                <div class="content"><h3>Startup Ideas</h3><p class="number"><?php echo $total_ideas; ?></p></div>
                            </div>
                            <div class="stat-card">
                                <div class="icon bg-teal"><i class="fas fa-trophy"></i></div>
                                <div class="content"><h3>Active Challenges</h3><p class="number"><?php echo $active_challenges; ?></p></div>
                            </div>
                             <div class="stat-card">
                                <div class="icon bg-blue"><i class="fas fa-file-signature"></i></div>
                                <div class="content"><h3>Tests Taken</h3><p class="number"><?php echo $total_tests_taken; ?></p></div>
                            </div>
                             <div class="stat-card">
                                <div class="icon bg-green"><i class="fas fa-paper-plane"></i></div>
                                <div class="content"><h3>Pending Subs</h3><p class="number"><?php echo $pending_submissions; ?></p></div>
                            </div>
                             <div class="stat-card">
                                <div class="icon bg-red"><i class="fas fa-envelope-open-text"></i></div>
                                <div class="content"><h3>Pending Feedback</h3><p class="number"><?php echo $pending_feedback; ?></p></div>
                            </div>
                        </section>
                        <section class="content-box">
                             <div class="content-box-header">
                                <h2>Weekly New User Signups</h2>
                            </div>
                            <canvas id="activityChart" style="max-height: 300px;"></canvas>
                        </section>
                <?php endswitch; ?>
            </div>
        </main>
    </div>

    <!--======================================================================-->
    <!-- Part 7: MODALS FOR CRUD OPERATIONS (FULLY INTEGRATED)                 -->
    <!--======================================================================-->

    <!-- User Modal (for Create/Edit) -->
    <div id="user-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="user-modal-title">Add New User</h2>
                <span class="close-btn" onclick="closeModal('user-modal')">×</span>
            </div>
            <form id="user-form" action="?view=users" method="POST">
                <input type="hidden" name="action" id="user-action" value="create_user">
                <input type="hidden" name="user_id" id="user-id">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>
                 <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password">
                    <small id="password-help" class="text-light"></small>
                </div>
                 <div class="form-group">
                    <label for="role">Role</label>
                    <select id="role" name="role" required>
                        <option value="user">User</option>
                        <option value="admin">Admin</option>
                        <option value="alumni">Alumni</option>
                    </select>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Save User</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Challenge Modal (for Create/Edit) -->
    <div id="challenge-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="challenge-modal-title">Create New Challenge</h2>
                <span class="close-btn" onclick="closeModal('challenge-modal')">×</span>
            </div>
            <form id="challenge-form" action="?view=challenges" method="POST">
                <input type="hidden" name="action" id="challenge-action" value="create_challenge">
                <input type="hidden" name="challenge_id" id="challenge-id">
                <div class="form-group">
                    <label for="challenge-title">Title</label>
                    <input type="text" id="challenge-title" name="title" required>
                </div>
                <div class="form-group">
                    <label for="challenge-description">Description</label>
                    <textarea id="challenge-description" name="description" required></textarea>
                </div>
                <div class="form-group">
                    <label for="challenge-points">Points Reward</label>
                    <input type="number" id="challenge-points" name="points_reward" value="50" required>
                </div>
                <div class="form-group">
                    <label for="challenge-start">Start Date</label>
                    <input type="datetime-local" id="challenge-start" name="start_date" required>
                </div>
                 <div class="form-group">
                    <label for="challenge-end">End Date</label>
                    <input type="datetime-local" id="challenge-end" name="end_date" required>
                </div>
                <div class="form-group checkbox-group">
                    <input type="checkbox" id="challenge-active" name="is_active" value="1" checked>
                    <label for="challenge-active">Is Active?</label>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Save Challenge</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Resource Modal (for Create/Edit) -->
    <div id="resource-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="resource-modal-title">Add New Resource</h2>
                <span class="close-btn" onclick="closeModal('resource-modal')">×</span>
            </div>
            <form id="resource-form" action="?view=resources" method="POST">
                <input type="hidden" name="action" id="resource-action" value="create_resource">
                <input type="hidden" name="resource_id" id="resource-id">
                <div class="form-group">
                    <label for="resource-category">Category</label>
                    <input type="text" id="resource-category" name="category" required placeholder="e.g., Programming, Design, Marketing">
                </div>
                <div class="form-group">
                    <label for="resource-title">Title</label>
                    <input type="text" id="resource-title" name="title" required>
                </div>
                <div class="form-group">
                    <label for="resource-description">Description</label>
                    <textarea id="resource-description" name="description"></textarea>
                </div>
                <div class="form-group">
                    <label for="resource-link">Link (URL)</label>
                    <input type="url" id="resource-link" name="link" required placeholder="https://example.com">
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Save Resource</button>
                </div>
            </form>
        </div>
    </div>


    <!--======================================================================-->
    <!-- Part 8: EMBEDDED JAVASCRIPT (WITH CHART & MODAL LOGIC)               -->
    <!--======================================================================-->
    <script>

        // --- Responsive Sidebar Toggle ---
const menuToggle = document.getElementById('menu-toggle');
const sidebar = document.querySelector('.sidebar');
const navLinks = document.querySelectorAll('.sidebar-nav a');

if (menuToggle && sidebar) {
    // Create an overlay element
    const overlay = document.createElement('div');
    overlay.className = 'sidebar-overlay';
    document.body.appendChild(overlay);

    const closeSidebar = () => {
        sidebar.classList.remove('is-open');
        overlay.style.display = 'none';
    };

    const openSidebar = () => {
        sidebar.classList.add('is-open');
        overlay.style.display = 'block';
    };

    menuToggle.addEventListener('click', (e) => {
        e.stopPropagation();
        if (sidebar.classList.contains('is-open')) {
            closeSidebar();
        } else {
            openSidebar();
        }
    });

    overlay.addEventListener('click', closeSidebar);

    // Close sidebar when a nav link is clicked (good for mobile UX)
    navLinks.forEach(link => {
        link.addEventListener('click', () => {
             // Only close if in mobile view (sidebar is not permanently visible)
            if (window.innerWidth <= 992) {
                closeSidebar();
            }
        });
    });
}

        // --- Modal Handling ---
        function openModal(modalId) { document.getElementById(modalId).style.display = 'block'; }
        function closeModal(modalId) { document.getElementById(modalId).style.display = 'none'; }
        window.onclick = function(event) { if (event.target.classList.contains('modal')) { event.target.style.display = 'none'; } }
        document.addEventListener('keydown', function(event) { if (event.key === "Escape") { document.querySelectorAll('.modal').forEach(modal => modal.style.display = 'none'); } });

        // --- User Edit ---
        function editUser(user) {
            document.getElementById('user-modal-title').innerText = 'Edit User';
            document.getElementById('user-action').value = 'edit_user';
            document.getElementById('user-id').value = user.id;
            document.getElementById('username').value = user.user_name;
            document.getElementById('email').value = user.email;
            document.getElementById('role').value = user.role;
            document.getElementById('password').value = '';
            document.getElementById('password').placeholder = 'Leave blank to keep current password';
            document.getElementById('password').required = false;
            document.getElementById('password-help').innerText = 'Leave blank to keep current password.';
            openModal('user-modal');
        }

        // --- Challenge Edit ---
        function editChallenge(challenge) {
            document.getElementById('challenge-modal-title').innerText = 'Edit Challenge';
            document.getElementById('challenge-action').value = 'edit_challenge';
            document.getElementById('challenge-id').value = challenge.id;
            document.getElementById('challenge-title').value = challenge.title;
            document.getElementById('challenge-description').value = challenge.description;
            document.getElementById('challenge-points').value = challenge.points_reward;
            const formatDT = (dt) => dt ? dt.replace(' ', 'T').substring(0, 16) : '';
            document.getElementById('challenge-start').value = formatDT(challenge.start_date);
            document.getElementById('challenge-end').value = formatDT(challenge.end_date);
            document.getElementById('challenge-active').checked = (challenge.is_active == 1);
            openModal('challenge-modal');
        }

        // --- Resource Edit ---
        function editResource(resource) {
            document.getElementById('resource-modal-title').innerText = 'Edit Resource';
            document.getElementById('resource-action').value = 'edit_resource';
            document.getElementById('resource-id').value = resource.id;
            document.getElementById('resource-category').value = resource.category;
            document.getElementById('resource-title').value = resource.title;
            document.getElementById('resource-description').value = resource.description;
            document.getElementById('resource-link').value = resource.link;
            openModal('resource-modal');
        }
        
        // --- Dashboard Chart ---
        <?php if ($current_view == 'dashboard'): ?>
        const ctx = document.getElementById('activityChart')?.getContext('2d');
        if (ctx) {
            const activityChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode($chart_labels); ?>,
                    datasets: [{
                        label: 'New Users',
                        data: <?php echo json_encode($chart_values); ?>,
                        backgroundColor: 'rgba(79, 70, 229, 0.7)',
                        borderColor: 'rgba(79, 70, 229, 1)',
                        borderWidth: 1,
                        borderRadius: 5
                    }]
                },
                options: {
                    scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
                    plugins: { legend: { display: false } },
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
        }
        <?php endif; ?>

        // --- Reset Modals on Add New ---
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelector('button[onclick="openModal(\'user-modal\')"]')?.addEventListener('click', () => {
                document.getElementById('user-modal-title').innerText = 'Add New User';
                document.getElementById('user-form').reset();
                document.getElementById('user-action').value = 'create_user';
                document.getElementById('user-id').value = '';
                document.getElementById('password').placeholder = 'Required for new user';
                document.getElementById('password').required = true;
                document.getElementById('password-help').innerText = '';
            });
            document.querySelector('button[onclick="openModal(\'challenge-modal\')"]')?.addEventListener('click', () => {
                document.getElementById('challenge-modal-title').innerText = 'Create New Challenge';
                document.getElementById('challenge-form').reset();
                document.getElementById('challenge-action').value = 'create_challenge';
                document.getElementById('challenge-id').value = '';
            });
            document.querySelector('button[onclick="openModal(\'resource-modal\')"]')?.addEventListener('click', () => {
                document.getElementById('resource-modal-title').innerText = 'Add New Resource';
                document.getElementById('resource-form').reset();
                document.getElementById('resource-action').value = 'create_resource';
                document.getElementById('resource-id').value = '';
            });
        });
    </script>
</body>
</html>