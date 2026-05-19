<?php
// Fetch notifications for the logged-in user (for navigationbar.php)
header('Content-Type: application/json');
require_once '../config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$userId = $_SESSION['user_id'] ?? 0;
if (!$userId) {
    echo json_encode(['unread_count' => 0, 'notifications' => []]);
    exit;
}

function timeAgo($datetime) {
    $time = strtotime($datetime);
    $diff = time() - $time;
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff/60) . ' min ago';
    if ($diff < 86400) return floor($diff/3600) . ' hr ago';
    if ($diff < 604800) return floor($diff/86400) . 'd ago';
    return date('M d, Y', $time);
}

$notifications = [];
$unread_count = 0;

// --- Direct Messages ---
$stmt = $pdo->prepare("SELECT dm.id, dm.sender_id AS actor_id, u.user_name AS actor_name, u.profile_picture_path AS actor_avatar, dm.created_at, dm.is_read
    FROM direct_messages dm
    JOIN users u ON dm.sender_id = u.id
    WHERE dm.receiver_id = ? AND dm.is_deleted_by_receiver = 0
    ORDER BY dm.created_at DESC LIMIT 10");
$stmt->execute([$userId]);
foreach ($stmt->fetchAll() as $row) {
    $notifications[] = [
        'id' => 1000000 + $row['id'],
        'type' => 'message',
        'message' => "sent you a message.",
        'actor_name' => $row['actor_name'],
        'actor_avatar' => $row['actor_avatar'] ?: 'assets/images/default-profile.jpg',
        'link' => "dms.php?user_id=" . $row['actor_id'],
        'is_read' => $row['is_read'],
        'time_ago' => timeAgo($row['created_at'])
    ];
    if (!$row['is_read']) $unread_count++;
}

// --- Friend Requests ---
$stmt = $pdo->prepare("SELECT f.id, f.user_one_id, f.user_two_id, f.status, f.action_user_id, f.created_at, u.user_name AS actor_name, u.profile_picture_path AS actor_avatar
    FROM friendships f
    JOIN users u ON u.id = f.user_one_id
    WHERE f.user_two_id = ? AND f.status = 'pending'
    ORDER BY f.created_at DESC LIMIT 10");
$stmt->execute([$userId]);
foreach ($stmt->fetchAll() as $row) {
    $notifications[] = [
        'id' => 2000000 + $row['id'],
        'type' => 'friend_request',
        'message' => "sent you a friend request.",
        'actor_name' => $row['actor_name'],
        'actor_avatar' => $row['actor_avatar'] ?: 'assets/images/default-profile.jpg',
        'link' => "community.php?page=friends",
        'is_read' => 0,
        'time_ago' => timeAgo($row['created_at'])
    ];
    $unread_count++;
}

// --- Feed Likes ---
$stmt = $pdo->prepare("SELECT fl.id, fl.user_id AS actor_id, u.user_name AS actor_name, u.profile_picture_path AS actor_avatar, fl.created_at, p.id AS post_id
    FROM feed_likes fl
    JOIN users u ON fl.user_id = u.id
    JOIN posts p ON fl.item_id = p.id
    WHERE p.user_id = ? AND fl.user_id != ?
    ORDER BY fl.created_at DESC LIMIT 10");
$stmt->execute([$userId, $userId]);
foreach ($stmt->fetchAll() as $row) {
    $notifications[] = [
        'id' => 3000000 + $row['id'],
        'type' => 'like',
        'message' => "liked your post.",
        'actor_name' => $row['actor_name'],
        'actor_avatar' => $row['actor_avatar'] ?: 'assets/images/default-profile.jpg',
        'link' => "feed.php?post_id=" . $row['post_id'],
        'is_read' => 0,
        'time_ago' => timeAgo($row['created_at'])
    ];
    $unread_count++;
}

// --- Feed Comments ---
$stmt = $pdo->prepare("SELECT fc.id, fc.user_id AS actor_id, u.user_name AS actor_name, u.profile_picture_path AS actor_avatar, fc.created_at, p.id AS post_id
    FROM feed_comments fc
    JOIN users u ON fc.user_id = u.id
    JOIN posts p ON fc.item_id = p.id
    WHERE p.user_id = ? AND fc.user_id != ?
    ORDER BY fc.created_at DESC LIMIT 10");
$stmt->execute([$userId, $userId]);
foreach ($stmt->fetchAll() as $row) {
    $notifications[] = [
        'id' => 4000000 + $row['id'],
        'type' => 'comment',
        'message' => "commented on your post.",
        'actor_name' => $row['actor_name'],
        'actor_avatar' => $row['actor_avatar'] ?: 'assets/images/default-profile.jpg',
        'link' => "feed.php?post_id=" . $row['post_id'],
        'is_read' => 0,
        'time_ago' => timeAgo($row['created_at'])
    ];
    $unread_count++;
}

// --- Showcase Likes ---
$stmt = $pdo->prepare("SELECT sl.item_id, sl.user_id AS actor_id, u.user_name AS actor_name, u.profile_picture_path AS actor_avatar, si.user_id AS owner_id
    FROM showcase_likes sl
    JOIN users u ON sl.user_id = u.id
    JOIN showcase_items si ON sl.item_id = si.id
    WHERE si.user_id = ? AND sl.user_id != ?
    ORDER BY sl.item_id DESC LIMIT 10");
$stmt->execute([$userId, $userId]);
foreach ($stmt->fetchAll() as $row) {
    $notifications[] = [
        'id' => 5000000 + $row['item_id'],
        'type' => 'showcase_like',
        'message' => "liked your showcase item.",
        'actor_name' => $row['actor_name'],
        'actor_avatar' => $row['actor_avatar'] ?: 'assets/images/default-profile.jpg',
        'link' => "community.php?page=showcase&item_id=" . $row['item_id'],
        'is_read' => 0,
        'time_ago' => 'just now'
    ];
    $unread_count++;
}

// --- Idea Comments ---
$stmt = $pdo->prepare("SELECT ic.id, ic.user_id AS actor_id, u.user_name AS actor_name, u.profile_picture_path AS actor_avatar, ic.created_at, si.id AS idea_id
    FROM idea_comments ic
    JOIN users u ON ic.user_id = u.id
    JOIN startup_ideas si ON ic.idea_id = si.id
    WHERE si.user_id = ? AND ic.user_id != ?
    ORDER BY ic.created_at DESC LIMIT 10");
$stmt->execute([$userId, $userId]);
foreach ($stmt->fetchAll() as $row) {
    $notifications[] = [
        'id' => 6000000 + $row['id'],
        'type' => 'idea_comment',
        'message' => "commented on your idea.",
        'actor_name' => $row['actor_name'],
        'actor_avatar' => $row['actor_avatar'] ?: 'assets/images/default-profile.jpg',
        'link' => "community.php?page=ideas&idea_id=" . $row['idea_id'],
        'is_read' => 0,
        'time_ago' => timeAgo($row['created_at'])
    ];
    $unread_count++;
}

// Sort notifications by time (descending)
usort($notifications, function($a, $b) {
    return strcmp($b['time_ago'], $a['time_ago']);
});

// Limit to 20 most recent
$notifications = array_slice($notifications, 0, 20);

// Output

echo json_encode([
    'unread_count' => $unread_count,
    'notifications' => $notifications
]);
