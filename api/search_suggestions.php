<?php
header('Content-Type: application/json');
require_once '../includes/db_connect.php';

$query = isset($_GET['q']) ? trim($_GET['q']) : '';
$results = [];

if ($query !== '') {
    // USERS (with profile picture)
    $stmt = $pdo->prepare("SELECT id, user_name, profile_picture_path, institution FROM users WHERE user_name LIKE ? LIMIT 5");
    $stmt->execute(['%' . $query . '%']);
    while ($row = $stmt->fetch()) {
        $results[] = [
            'type' => 'user',
            'id' => $row['id'],
            'name' => $row['user_name'],
            'profile_picture' => $row['profile_picture_path'] ?: 'assets/images/default-profile.jpg',
            'institution' => $row['institution']
        ];
    }

    // MENTORS (users who are mentors in mentorship_requests)
    $stmt = $pdo->prepare("SELECT DISTINCT u.id, u.user_name, u.profile_picture_path FROM users u INNER JOIN mentorship_requests m ON u.id = m.mentor_user_id WHERE u.user_name LIKE ? LIMIT 3");
    $stmt->execute(['%' . $query . '%']);
    while ($row = $stmt->fetch()) {
        $results[] = [
            'type' => 'mentor',
            'id' => $row['id'],
            'name' => $row['user_name'],
            'profile_picture' => $row['profile_picture_path'] ?: 'assets/images/default-profile.jpg'
        ];
    }

    // TOOLS (resources table)
    $stmt = $pdo->prepare("SELECT id, title, category FROM resources WHERE title LIKE ? OR category LIKE ? LIMIT 3");
    $stmt->execute(['%' . $query . '%', '%' . $query . '%']);
    while ($row = $stmt->fetch()) {
        $results[] = [
            'type' => 'tool',
            'id' => $row['id'],
            'name' => $row['title'],
            'category' => $row['category']
        ];
    }

    // UNIVERSITIES (distinct institution from users)
    $stmt = $pdo->prepare("SELECT DISTINCT institution FROM users WHERE institution LIKE ? AND institution IS NOT NULL AND institution != '' LIMIT 3");
    $stmt->execute(['%' . $query . '%']);
    while ($row = $stmt->fetch()) {
        $results[] = [
            'type' => 'university',
            'name' => $row['institution']
        ];
    }
}

echo json_encode(['results' => $results]);
