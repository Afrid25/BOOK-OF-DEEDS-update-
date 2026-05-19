<?php
session_start();
require_once '../../includes/db_connect.php';
require_once '../helpers/ai_mentorship_service.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not authenticated']);
    exit;
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data) {
            echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
            exit;
        }
        
        // Validate required fields
        $required_fields = ['age', 'education_level', 'study_goals', 'available_hours_per_day', 'learning_style'];
        foreach ($required_fields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                echo json_encode(['success' => false, 'message' => "Missing required field: {$field}"]);
                exit;
            }
        }
        
        // Check if profile already exists
        $stmt = $pdo->prepare("SELECT id FROM ai_mentorship_profiles WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $existing_profile = $stmt->fetch();
        
        if ($existing_profile) {
            // Update existing profile
            $stmt = $pdo->prepare("
                UPDATE ai_mentorship_profiles 
                SET age = ?, education_level = ?, semester_year = ?, major_subjects = ?, 
                    study_goals = ?, available_hours_per_day = ?, learning_style = ?, 
                    previous_exam_scores = ?, onboarding_completed = TRUE, updated_at = CURRENT_TIMESTAMP
                WHERE user_id = ?
            ");
        } else {
            // Create new profile
            $stmt = $pdo->prepare("
                INSERT INTO ai_mentorship_profiles 
                (user_id, age, education_level, semester_year, major_subjects, study_goals, 
                 available_hours_per_day, learning_style, previous_exam_scores, onboarding_completed)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, TRUE)
            ");
        }
        
        $params = [
            $data['age'],
            $data['education_level'],
            $data['semester_year'] ?? null,
            $data['major_subjects'] ?? null,
            $data['study_goals'],
            $data['available_hours_per_day'],
            $data['learning_style'],
            json_encode($data['previous_exam_scores'] ?? []),
            $user_id
        ];
        
        if ($existing_profile) {
            // For update, remove user_id from params and add it at the end
            array_pop($params);
            $params[] = $user_id;
        }
        
        $stmt->execute($params);
        
        // Initialize user level if not exists
        $stmt = $pdo->prepare("SELECT id FROM ai_user_levels WHERE user_id = ?");
        $stmt->execute([$user_id]);
        if (!$stmt->fetch()) {
            $stmt = $pdo->prepare("INSERT INTO ai_user_levels (user_id) VALUES (?)");
            $stmt->execute([$user_id]);
        }
        
        // Generate initial study plan
        $ai_service = new AIMentorshipService();
        $study_plan_id = $ai_service->generateStudyPlan($user_id, 'daily');
        
        // Generate welcome motivational message
        $welcome_message = $ai_service->generateMotivationalMessage($user_id, 'encouragement');
        
        echo json_encode([
            'success' => true,
            'message' => 'Profile created successfully!',
            'study_plan_id' => $study_plan_id,
            'welcome_message' => $welcome_message
        ]);
        
    } catch (Exception $e) {
        error_log("Onboarding error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'An error occurred while saving your profile']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
