<?php
// api/feed/get_feed_items.php
session_start();
require_once '../../includes/db_connect.php';

// Helper function to format timestamp into "time ago" string.
function time_elapsed_string($datetime, $full = false) {
    if (empty($datetime) || strtotime($datetime) === false) {
        return 'a while ago';
    }

    try {
        $now = new DateTime('now', new DateTimeZone('Asia/Dhaka'));
        $ago = new DateTime($datetime, new DateTimeZone('Asia/Dhaka'));
        $diff = $now->diff($ago);

        $weeks = floor($diff->d / 7);
        $diff->d -= $weeks * 7;

        $labels = [
            'y' => 'year',
            'm' => 'month',
            'week' => 'week',
            'd' => 'day',
            'h' => 'hour',
            'i' => 'minute',
            's' => 'second'
        ];

        $values = [
            'y' => $diff->y,
            'm' => $diff->m,
            'week' => $weeks,
            'd' => $diff->d,
            'h' => $diff->h,
            'i' => $diff->i,
            's' => $diff->s
        ];

        $result = [];
        foreach ($labels as $key => $label) {
            if ($values[$key]) {
                $result[] = $values[$key] . ' ' . $label . ($values[$key] > 1 ? 's' : '');
            }
        }

        if (!$full) $result = array_slice($result, 0, 1);
        return $result ? implode(', ', $result) . ' ago' : 'just now';
    } catch (Exception $e) {
        return 'a while ago';
    }
}


// Helper function to generate HTML for a single item
function generate_feed_item_html($item) {
    $userName = htmlspecialchars($item['user_name']);
    $userInitial = mb_strtoupper(mb_substr($userName, 0, 1, 'UTF-8'));
    $title = htmlspecialchars($item['title']);
    $content = htmlspecialchars($item['content']);
    $timeAgo = time_elapsed_string($item['event_timestamp']);
    $actionText = '';

    switch ($item['event_type']) {
        case 'deed_added':
            $actionText = 'added a new deed:';
            break;
        case 'chapter_completed':
            $actionText = 'completed a chapter:';
            $content = "in the course: " . $content;
            break;
        case 'post_created':
            $actionText = 'shared a post:';
            $title = $content; // For posts, the title is the content itself
            $content = ''; // No sub-content for a simple post
            break;
    }

    $contentHTML = $content ? "<p class=\"content\">$content</p>" : "";

    return <<<HTML
        <div class="feed-item-modern glass-card" data-event-id="{$item['event_id']}" data-event-type="{$item['event_type']}">
            <div class="feed-item-modern-header">
                <div class="avatar-placeholder">$userInitial</div>
                <div class="feed-item-user-info">
                    <span class="user-name">$userName</span>
                    <span class="timestamp">$timeAgo</span>
                </div>
            </div>
            <div class="feed-item-modern-body">
                <p class="action-text">$actionText</p>
                <h4 class="title">$title</h4>
                $contentHTML
            </div>
            <div class="feed-item-modern-actions">
                <span class="action-button"><i class="fas fa-thumbs-up"></i> Like</span>
                <span class="action-button"><i class="fas fa-comment"></i> Comment</span>
            </div>
        </div>
    HTML;
}

// --- Main Logic ---
$sql = "(SELECT u.user_name, 'deed_added' AS event_type, d.deed_title AS title, d.deed_description AS content, d.id AS event_id, d.created_at AS event_timestamp FROM deeds d JOIN users u ON d.user_id = u.id)
        UNION ALL
        (SELECT u.user_name, 'chapter_completed' AS event_type, cc.chapter_name AS title, c.course_name AS content, ct.id AS event_id, ct.completed_at AS event_timestamp FROM course_topics ct JOIN course_chapters cc ON ct.chapter_id = cc.id JOIN courses c ON cc.course_id = c.id JOIN users u ON c.user_id = u.id WHERE ct.is_completed = 1 AND ct.completed_at IS NOT NULL AND ct.topic_order = (SELECT MAX(t2.topic_order) FROM course_topics t2 WHERE t2.chapter_id = cc.id))
        UNION ALL
        (SELECT u.user_name, 'post_created' AS event_type, p.content AS title, '' AS content, p.id AS event_id, p.created_at AS event_timestamp FROM posts p JOIN users u ON p.user_id = u.id)
        ORDER BY event_timestamp DESC
        LIMIT 20";

if ($stmt = $mysqli->prepare($sql)) {
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo '<p id="no-feed-items" class="feed-loader">The feed is quiet... be the first to start an activity!</p>';
        exit;
    }
    
    while ($item = $result->fetch_assoc()) {
        echo generate_feed_item_html($item);
    }
    $stmt->close();
}
$mysqli->close();
?>