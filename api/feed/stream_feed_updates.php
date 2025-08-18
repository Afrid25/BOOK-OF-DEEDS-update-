<?php
// api/feed/stream_feed_updates.php
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');

require_once '../../includes/db_connect.php';

// Keep track of the last ID sent for each event type
$last_ids = [
    'deed_added' => 0,
    'chapter_completed' => 0,
    'post_created' => 0
];

// Main loop runs until client disconnects
while (true) {
    if (connection_aborted()) break;

    $events = [];

    // Query for new posts
    $stmt_posts = $mysqli->prepare("SELECT u.user_name, p.content AS title, '' AS content, p.id, p.created_at FROM posts p JOIN users u ON p.user_id = u.id WHERE p.id > ? ORDER BY p.id ASC");
    $stmt_posts->bind_param("i", $last_ids['post_created']);
    $stmt_posts->execute();
    $res_posts = $stmt_posts->get_result();
    while ($row = $res_posts->fetch_assoc()) {
        $events[] = ['type' => 'post_created', 'data' => $row];
        $last_ids['post_created'] = $row['id'];
    }
    $stmt_posts->close();

    // Query for new deeds (add other queries here for other event types)
    // ...

    // If new events were found, send them to the client
    if (!empty($events)) {
        foreach($events as $event) {
            echo "event: " . $event['type'] . "\n";
            echo "data: " . json_encode($event['data']) . "\n\n";
        }
    }
    
    ob_flush();
    flush();
    sleep(2); // Wait 2 seconds before checking again
}

$mysqli->close();
?>