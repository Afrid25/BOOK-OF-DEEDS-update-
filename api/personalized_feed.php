<?php
// ======================================================================
// ==        PERSONALIZED FEED API - AI POWERED BACKEND              ==
// ==   Handles personalized content delivery and user interactions   ==
// ======================================================================

session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../includes/db_connect.php';

// Security check
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

$user_id = $_SESSION['user_id'] ?? 0;
$response = ['success' => false, 'data' => null, 'message' => ''];

// Helper function to get user level based on points
function getUserLevel($points) {
    if ($points < 100) return 'beginner';
    if ($points < 500) return 'intermediate';
    return 'advanced';
}

// Helper function to calculate engagement score
function calculateEngagementScore($views, $likes, $shares) {
    return ($views * 0.1) + ($likes * 0.5) + ($shares * 1.0);
}

// Helper function to generate AI-powered content suggestions
function generateAIContent($user_id, $post_type, $user_level, $interests) {
    $suggestions = [];
    
    switch ($post_type) {
        case 'motivation':
            $suggestions = [
                'Remember why you started. Every expert was once a beginner. Your journey is unique and valuable! 🌟',
                'Today is a new opportunity to be better than yesterday. Small steps lead to big changes! 💪',
                'You have the power to create the life you want. Believe in yourself and keep moving forward! ✨',
                'Success is not final, failure is not fatal. What matters is the courage to continue! 🚀',
                'Your potential is limitless. Don\'t let fear hold you back from achieving your dreams! 🌈'
            ];
            break;
            
        case 'exam_tips':
            $suggestions = [
                'Create a study schedule and stick to it. Consistency beats cramming every time! 📅',
                'Practice active recall by explaining concepts to yourself or others. Teaching is the best way to learn! 🧠',
                'Take regular breaks during study sessions. Your brain needs rest to process information effectively! ⏰',
                'Use the Pomodoro Technique: 25 minutes of focused study followed by 5-minute breaks! 🍅',
                'Review your notes within 24 hours of learning. This simple habit can improve retention by 40%! 📝'
            ];
            break;
            
        case 'study_hacks':
            $suggestions = [
                'Use color coding in your notes. Your brain associates colors with information! 🎨',
                'Create mind maps for complex topics. Visual connections help memory retention! 🗺️',
                'Study in different environments occasionally. This helps your brain create multiple memory pathways! 🌍',
                'Teach what you learn to someone else. The Feynman Technique is incredibly effective! 👨‍🏫',
                'Use spaced repetition techniques. Review material at increasing intervals! 📚'
            ];
            break;
            
        case 'reminder':
            $suggestions = [
                'Take a moment to stretch and hydrate. Your body and mind work better when well-cared for! 💧',
                'Check in with your goals. Are you on track? What small adjustment can you make today? 🎯',
                'Remember to celebrate your progress, no matter how small. Every step forward counts! 🎉',
                'Take a deep breath and center yourself. Mindfulness improves focus and reduces stress! 🧘‍♀️',
                'Reach out to a friend or mentor if you need support. You don\'t have to go through challenges alone! 🤝'
            ];
            break;
    }
    
    return $suggestions[array_rand($suggestions)] ?? 'Keep pushing forward! You\'re doing great! 💪';
}

// Handle different API endpoints
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'get_posts':
            // Get personalized posts based on user preferences
            $post_type = $_GET['type'] ?? null;
            $limit = min((int)($_GET['limit'] ?? 10), 50);
            
            // Get user data
            $user_stmt = $mysqli->prepare("SELECT points FROM users WHERE id = ?");
            $user_stmt->bind_param("i", $user_id);
            $user_stmt->execute();
            $user_data = $user_stmt->get_result()->fetch_assoc();
            $user_level = getUserLevel($user_data['points'] ?? 0);
            
            // Get personalized posts
            $query = "SELECT 
                        pp.*,
                        COALESCE(SUM(CASE WHEN upi.interaction_type = 'view' THEN upi.interaction_value ELSE 0 END), 0) as total_views,
                        COALESCE(SUM(CASE WHEN upi.interaction_type = 'like' THEN upi.interaction_value ELSE 0 END), 0) as total_likes,
                        COALESCE(SUM(CASE WHEN upi.interaction_type = 'share' THEN upi.interaction_value ELSE 0 END), 0) as total_shares,
                        CASE WHEN upi_like.id IS NOT NULL THEN 1 ELSE 0 END as is_liked,
                        CASE WHEN upi_bookmark.id IS NOT NULL THEN 1 ELSE 0 END as is_bookmarked
                    FROM personalized_posts pp
                    LEFT JOIN user_post_interactions upi ON pp.id = upi.post_id
                    LEFT JOIN user_post_interactions upi_like ON pp.id = upi_like.post_id AND upi_like.user_id = ? AND upi_like.interaction_type = 'like'
                    LEFT JOIN user_post_interactions upi_bookmark ON pp.id = upi_bookmark.post_id AND upi_bookmark.user_id = ? AND upi_bookmark.interaction_type = 'bookmark'
                    WHERE pp.is_active = TRUE
                        AND (? IS NULL OR pp.post_type = ?)
                        AND JSON_CONTAINS(pp.target_audience, JSON_OBJECT('user_level', ?))
                    GROUP BY pp.id
                    ORDER BY pp.engagement_score DESC, RAND()
                    LIMIT ?";
            
            $stmt = $mysqli->prepare($query);
            $stmt->bind_param("iissi", $user_id, $user_id, $post_type, $post_type, $user_level, $limit);
            $stmt->execute();
            $posts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
            // Add AI-generated content if needed
            if (count($posts) < $limit) {
                $ai_content = generateAIContent($user_id, $post_type, $user_level, []);
                $posts[] = [
                    'id' => 'ai_' . time(),
                    'title' => 'AI Generated Content',
                    'content' => $ai_content,
                    'post_type' => $post_type ?? 'motivation',
                    'category' => 'ai_generated',
                    'difficulty_level' => $user_level,
                    'is_ai_generated' => true,
                    'total_views' => 0,
                    'total_likes' => 0,
                    'total_shares' => 0,
                    'is_liked' => 0,
                    'is_bookmarked' => 0
                ];
            }
            
            $response = [
                'success' => true,
                'data' => [
                    'posts' => $posts,
                    'user_level' => $user_level,
                    'total_count' => count($posts)
                ],
                'message' => 'Personalized posts retrieved successfully'
            ];
            break;
            
        case 'interact':
            // Handle user interactions (like, share, bookmark, hide)
            $post_id = $_POST['post_id'] ?? null;
            $interaction_type = $_POST['interaction_type'] ?? null;
            $post_type = $_POST['post_type'] ?? null;
            
            if (!$post_id || !$interaction_type) {
                throw new Exception('Missing required parameters');
            }
            
            // Handle AI-generated content (no database interaction)
            if (strpos($post_id, 'ai_') === 0) {
                $response = [
                    'success' => true,
                    'data' => ['interaction_type' => $interaction_type],
                    'message' => 'Interaction recorded for AI content'
                ];
                break;
            }
            
            // Record interaction
            $stmt = $mysqli->prepare("INSERT INTO user_post_interactions (user_id, post_id, interaction_type) 
                                    VALUES (?, ?, ?) 
                                    ON DUPLICATE KEY UPDATE interaction_value = interaction_value + 1");
            $stmt->bind_param("iis", $user_id, $post_id, $interaction_type);
            $stmt->execute();
            
            // Update user interests
            if ($post_type) {
                $interest_stmt = $mysqli->prepare("CALL UpdateUserInterests(?, ?, ?)");
                $interest_stmt->bind_param("iss", $user_id, $post_type, $interaction_type);
                $interest_stmt->execute();
            }
            
            // Update post engagement metrics
            $update_stmt = $mysqli->prepare("UPDATE personalized_posts SET 
                                            view_count = view_count + CASE WHEN ? = 'view' THEN 1 ELSE 0 END,
                                            like_count = like_count + CASE WHEN ? = 'like' THEN 1 ELSE 0 END,
                                            share_count = share_count + CASE WHEN ? = 'share' THEN 1 ELSE 0 END,
                                            engagement_score = (
                                                (view_count + CASE WHEN ? = 'view' THEN 1 ELSE 0 END) * 0.1 +
                                                (like_count + CASE WHEN ? = 'like' THEN 1 ELSE 0 END) * 0.5 +
                                                (share_count + CASE WHEN ? = 'share' THEN 1 ELSE 0 END) * 1.0
                                            )
                                            WHERE id = ?");
            $update_stmt->bind_param("ssssssi", $interaction_type, $interaction_type, $interaction_type, $interaction_type, $interaction_type, $interaction_type, $post_id);
            $update_stmt->execute();
            
            $response = [
                'success' => true,
                'data' => ['interaction_type' => $interaction_type],
                'message' => 'Interaction recorded successfully'
            ];
            break;
            
        case 'get_user_interests':
            // Get user's current interests
            $stmt = $mysqli->prepare("SELECT interest_category, interest_level FROM user_interests WHERE user_id = ? ORDER BY interest_level DESC, last_updated DESC");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $interests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
            $response = [
                'success' => true,
                'data' => ['interests' => $interests],
                'message' => 'User interests retrieved'
            ];
            break;
            
        case 'update_interests':
            // Update user interests
            $interests = $_POST['interests'] ?? [];
            
            if (!empty($interests)) {
                $mysqli->begin_transaction();
                
                try {
                    // Clear existing interests
                    $delete_stmt = $mysqli->prepare("DELETE FROM user_interests WHERE user_id = ?");
                    $delete_stmt->bind_param("i", $user_id);
                    $delete_stmt->execute();
                    
                    // Insert new interests
                    $insert_stmt = $mysqli->prepare("INSERT INTO user_interests (user_id, interest_category, interest_level) VALUES (?, ?, ?)");
                    
                    foreach ($interests as $interest) {
                        $insert_stmt->bind_param("iss", $user_id, $interest['category'], $interest['level']);
                        $insert_stmt->execute();
                    }
                    
                    $mysqli->commit();
                    
                    $response = [
                        'success' => true,
                        'data' => ['interests' => $interests],
                        'message' => 'Interests updated successfully'
                    ];
                } catch (Exception $e) {
                    $mysqli->rollback();
                    throw $e;
                }
            } else {
                throw new Exception('No interests provided');
            }
            break;
            
        case 'get_ai_suggestions':
            // Get AI-generated content suggestions
            $suggestion_type = $_GET['type'] ?? 'post';
            $limit = min((int)($_GET['limit'] ?? 5), 20);
            
            $stmt = $mysqli->prepare("SELECT content, relevance_score FROM ai_content_suggestions 
                                    WHERE user_id = ? AND suggestion_type = ? AND is_used = FALSE 
                                    ORDER BY relevance_score DESC LIMIT ?");
            $stmt->bind_param("isi", $user_id, $suggestion_type, $limit);
            $stmt->execute();
            $suggestions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
            // Generate new suggestions if needed
            if (count($suggestions) < $limit) {
                $user_stmt = $mysqli->prepare("SELECT points FROM users WHERE id = ?");
                $user_stmt->bind_param("i", $user_id);
                $user_stmt->execute();
                $user_data = $user_stmt->get_result()->fetch_assoc();
                $user_level = getUserLevel($user_data['points'] ?? 0);
                
                $new_suggestion = generateAIContent($user_id, $suggestion_type, $user_level, []);
                
                $insert_stmt = $mysqli->prepare("INSERT INTO ai_content_suggestions (user_id, suggestion_type, content, relevance_score) VALUES (?, ?, ?, ?)");
                $relevance_score = 0.8; // Default relevance score
                $insert_stmt->bind_param("issd", $user_id, $suggestion_type, $new_suggestion, $relevance_score);
                $insert_stmt->execute();
                
                $suggestions[] = [
                    'content' => $new_suggestion,
                    'relevance_score' => $relevance_score
                ];
            }
            
            $response = [
                'success' => true,
                'data' => ['suggestions' => $suggestions],
                'message' => 'AI suggestions retrieved'
            ];
            break;
            
        case 'track_mood':
            // Track user mood and activity
            $mood_type = $_POST['mood_type'] ?? null;
            $mood_score = (int)($_POST['mood_score'] ?? 5);
            $activity_level = $_POST['activity_level'] ?? 'medium';
            $notes = $_POST['notes'] ?? '';
            
            if (!$mood_type) {
                throw new Exception('Mood type is required');
            }
            
            $stmt = $mysqli->prepare("INSERT INTO user_mood_tracking (user_id, mood_type, mood_score, activity_level, notes) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("isiss", $user_id, $mood_type, $mood_score, $activity_level, $notes);
            $stmt->execute();
            
            $response = [
                'success' => true,
                'data' => ['mood_tracked' => true],
                'message' => 'Mood tracked successfully'
            ];
            break;
            
        case 'get_mood_history':
            // Get user's mood history
            $days = min((int)($_GET['days'] ?? 7), 30);
            
            $stmt = $mysqli->prepare("SELECT mood_type, mood_score, activity_level, notes, created_at 
                                    FROM user_mood_tracking 
                                    WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                                    ORDER BY created_at DESC");
            $stmt->bind_param("ii", $user_id, $days);
            $stmt->execute();
            $mood_history = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
            $response = [
                'success' => true,
                'data' => ['mood_history' => $mood_history],
                'message' => 'Mood history retrieved'
            ];
            break;
            
        case 'get_notifications':
            // Get personalized notifications
            $stmt = $mysqli->prepare("SELECT id, notification_type, title, message, is_read, created_at 
                                    FROM personalized_notifications 
                                    WHERE user_id = ? AND (scheduled_for IS NULL OR scheduled_for <= NOW())
                                    ORDER BY created_at DESC LIMIT 10");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $notifications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
            $response = [
                'success' => true,
                'data' => ['notifications' => $notifications],
                'message' => 'Notifications retrieved'
            ];
            break;
            
        case 'mark_notification_read':
            // Mark notification as read
            $notification_id = $_POST['notification_id'] ?? null;
            
            if (!$notification_id) {
                throw new Exception('Notification ID is required');
            }
            
            $stmt = $mysqli->prepare("UPDATE personalized_notifications SET is_read = TRUE WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $notification_id, $user_id);
            $stmt->execute();
            
            $response = [
                'success' => true,
                'data' => ['marked_read' => true],
                'message' => 'Notification marked as read'
            ];
            break;
            
        default:
            throw new Exception('Invalid action specified');
    }
    
} catch (Exception $e) {
    $response = [
        'success' => false,
        'data' => null,
        'message' => $e->getMessage()
    ];
    http_response_code(400);
}

echo json_encode($response);
?> 