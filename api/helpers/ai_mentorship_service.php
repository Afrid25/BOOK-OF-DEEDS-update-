<?php
require_once '../../config.php';
require_once '../../includes/db_connect.php';

class AIMentorshipService {
    private $pdo;
    private $mysqli;
    private $openai_api_key;
    private $gemini_api_key;

    public function __construct() {
        global $pdo, $mysqli;
        $this->pdo = $pdo;
        $this->mysqli = $mysqli;
        $this->openai_api_key = OPENAI_API_KEY;
        $this->gemini_api_key = GEMINI_API_KEY;
    }

    /**
     * Generate personalized study plan using AI
     */
    public function generateStudyPlan($user_id, $plan_type = 'daily') {
        try {
            // Get user profile and current performance
            $profile = $this->getUserProfile($user_id);
            $performance = $this->getUserPerformance($user_id);
            $weaknesses = $this->getUserWeaknesses($user_id);
            
            $prompt = $this->buildStudyPlanPrompt($profile, $performance, $weaknesses, $plan_type);
            $ai_response = $this->callOpenAI($prompt);
            
            if ($ai_response) {
                $plan_data = json_decode($ai_response, true);
                if ($plan_data) {
                    return $this->saveStudyPlan($user_id, $plan_data, $plan_type);
                }
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Error generating study plan: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Answer study-related questions using AI
     */
    public function answerQuestion($user_id, $question, $subject = null, $topic = null) {
        try {
            $user_context = $this->getUserContext($user_id);
            $prompt = $this->buildQAPrompt($question, $subject, $topic, $user_context);
            $answer = $this->callOpenAI($prompt);
            
            if ($answer) {
                $this->saveQAInteraction($user_id, $question, $answer, $subject, $topic);
                return $answer;
            }
            
            return "I'm sorry, I couldn't generate an answer right now. Please try again later.";
        } catch (Exception $e) {
            error_log("Error answering question: " . $e->getMessage());
            return "I'm sorry, I encountered an error. Please try again later.";
        }
    }

    /**
     * Generate motivational message based on user's current state
     */
    public function generateMotivationalMessage($user_id, $message_type = 'encouragement') {
        try {
            $user_context = $this->getUserContext($user_id);
            $prompt = $this->buildMotivationPrompt($user_context, $message_type);
            $message = $this->callOpenAI($prompt);
            
            if ($message) {
                $this->saveMotivationalMessage($user_id, $message, $message_type, $user_context);
                return $message;
            }
            
            return $this->getDefaultMotivationalMessage($message_type);
        } catch (Exception $e) {
            error_log("Error generating motivational message: " . $e->getMessage());
            return $this->getDefaultMotivationalMessage($message_type);
        }
    }

    /**
     * Generate daily challenge
     */
    public function generateDailyChallenge($user_id) {
        try {
            $user_context = $this->getUserContext($user_id);
            $prompt = $this->buildChallengePrompt($user_context);
            $challenge = $this->callOpenAI($prompt);
            
            if ($challenge) {
                $challenge_data = json_decode($challenge, true);
                if ($challenge_data) {
                    return $this->saveDailyChallenge($user_id, $challenge_data);
                }
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Error generating daily challenge: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Analyze user performance and suggest improvements
     */
    public function analyzePerformance($user_id) {
        try {
            $performance = $this->getUserPerformance($user_id);
            $prompt = $this->buildAnalysisPrompt($performance);
            $analysis = $this->callOpenAI($prompt);
            
            if ($analysis) {
                $analysis_data = json_decode($analysis, true);
                if ($analysis_data) {
                    $this->saveWeaknessAnalysis($user_id, $analysis_data);
                    return $analysis_data;
                }
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Error analyzing performance: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Generate exam preparation materials
     */
    public function generateExamPrep($user_id, $exam_title, $subject, $exam_date) {
        try {
            $user_context = $this->getUserContext($user_id);
            $prompt = $this->buildExamPrepPrompt($exam_title, $subject, $exam_date, $user_context);
            $prep_materials = $this->callOpenAI($prompt);
            
            if ($prep_materials) {
                $prep_data = json_decode($prep_materials, true);
                if ($prep_data) {
                    return $this->saveExamPrep($user_id, $exam_title, $subject, $exam_date, $prep_data);
                }
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Error generating exam prep: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Call OpenAI API
     */
    private function callOpenAI($prompt) {
        $url = 'https://api.openai.com/v1/chat/completions';
        
        $data = [
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are an AI mentor helping students with their studies. Provide helpful, accurate, and encouraging responses.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'max_tokens' => 1000,
            'temperature' => 0.7
        ];

        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->openai_api_key
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code === 200) {
            $result = json_decode($response, true);
            return $result['choices'][0]['message']['content'] ?? null;
        }

        return null;
    }

    /**
     * Build study plan prompt
     */
    private function buildStudyPlanPrompt($profile, $performance, $weaknesses, $plan_type) {
        $prompt = "Generate a personalized {$plan_type} study plan for a student with the following profile:\n\n";
        $prompt .= "Profile: " . json_encode($profile) . "\n\n";
        $prompt .= "Recent Performance: " . json_encode($performance) . "\n\n";
        $prompt .= "Identified Weaknesses: " . json_encode($weaknesses) . "\n\n";
        
        $prompt .= "Please generate a JSON response with the following structure:\n";
        $prompt .= "{\n";
        $prompt .= "  \"title\": \"Study Plan Title\",\n";
        $prompt .= "  \"description\": \"Plan description\",\n";
        $prompt .= "  \"tasks\": [\n";
        $prompt .= "    {\n";
        $prompt .= "      \"title\": \"Task title\",\n";
        $prompt .= "      \"description\": \"Task description\",\n";
        $prompt .= "      \"subject\": \"Subject name\",\n";
        $prompt .= "      \"topic\": \"Specific topic\",\n";
        $prompt .= "      \"task_type\": \"study|quiz|practice|review|assignment\",\n";
        $prompt .= "      \"estimated_duration\": 30,\n";
        $prompt .= "      \"difficulty_level\": \"beginner|intermediate|advanced\",\n";
        $prompt .= "      \"due_date\": \"YYYY-MM-DD HH:MM:SS\"\n";
        $prompt .= "    }\n";
        $prompt .= "  ]\n";
        $prompt .= "}\n\n";
        $prompt .= "Focus on addressing weaknesses and building on strengths. Make tasks realistic and achievable.";
        
        return $prompt;
    }

    /**
     * Build Q&A prompt
     */
    private function buildQAPrompt($question, $subject, $topic, $user_context) {
        $prompt = "Answer the following study question in a clear, helpful way:\n\n";
        $prompt .= "Question: {$question}\n";
        if ($subject) $prompt .= "Subject: {$subject}\n";
        if ($topic) $prompt .= "Topic: {$topic}\n";
        $prompt .= "Student Context: " . json_encode($user_context) . "\n\n";
        $prompt .= "Provide a concise explanation with examples if helpful. Keep the response under 300 words.";
        
        return $prompt;
    }

    /**
     * Build motivation prompt
     */
    private function buildMotivationPrompt($user_context, $message_type) {
        $prompt = "Generate a motivational message for a student with the following context:\n\n";
        $prompt .= "Context: " . json_encode($user_context) . "\n";
        $prompt .= "Message Type: {$message_type}\n\n";
        $prompt .= "Create a short, encouraging message (under 100 words) that is relevant to their current situation.";
        
        return $prompt;
    }

    /**
     * Build challenge prompt
     */
    private function buildChallengePrompt($user_context) {
        $prompt = "Generate a daily learning challenge for a student with the following context:\n\n";
        $prompt .= "Context: " . json_encode($user_context) . "\n\n";
        $prompt .= "Please generate a JSON response with the following structure:\n";
        $prompt .= "{\n";
        $prompt .= "  \"title\": \"Challenge Title\",\n";
        $prompt .= "  \"description\": \"Challenge description\",\n";
        $prompt .= "  \"challenge_type\": \"quiz|problem_solving|memory_test|speed_test\",\n";
        $prompt .= "  \"subject\": \"Subject name\",\n";
        $prompt .= "  \"difficulty_level\": \"beginner|intermediate|advanced\"\n";
        $prompt .= "}\n\n";
        $prompt .= "Make it engaging and achievable within 15-30 minutes.";
        
        return $prompt;
    }

    /**
     * Build analysis prompt
     */
    private function buildAnalysisPrompt($performance) {
        $prompt = "Analyze the following student performance data and identify areas for improvement:\n\n";
        $prompt .= "Performance Data: " . json_encode($performance) . "\n\n";
        $prompt .= "Please generate a JSON response with the following structure:\n";
        $prompt .= "{\n";
        $prompt .= "  \"weaknesses\": [\n";
        $prompt .= "    {\n";
        $prompt .= "      \"subject\": \"Subject name\",\n";
        $prompt .= "      \"topic\": \"Specific topic\",\n";
        $prompt .= "      \"weakness_type\": \"conceptual|application|time_management|memory\",\n";
        $prompt .= "      \"severity\": \"low|medium|high\",\n";
        $prompt .= "      \"suggested_actions\": \"Specific actions to improve\"\n";
        $prompt .= "    }\n";
        $prompt .= "  ]\n";
        $prompt .= "}\n\n";
        $prompt .= "Focus on actionable insights and specific improvement strategies.";
        
        return $prompt;
    }

    /**
     * Build exam prep prompt
     */
    private function buildExamPrepPrompt($exam_title, $subject, $exam_date, $user_context) {
        $prompt = "Generate exam preparation materials for:\n\n";
        $prompt .= "Exam: {$exam_title}\n";
        $prompt .= "Subject: {$subject}\n";
        $prompt .= "Date: {$exam_date}\n";
        $prompt .= "Student Context: " . json_encode($user_context) . "\n\n";
        $prompt .= "Please generate a JSON response with the following structure:\n";
        $prompt .= "{\n";
        $prompt .= "  \"study_notes\": \"Comprehensive study notes\",\n";
        $prompt .= "  \"predicted_questions\": [\"Question 1\", \"Question 2\", \"Question 3\"],\n";
        $prompt .= "  \"key_topics\": [\"Topic 1\", \"Topic 2\", \"Topic 3\"],\n";
        $prompt .= "  \"study_schedule\": [\n";
        $prompt .= "    {\n";
        $prompt .= "      \"day\": \"Day description\",\n";
        $prompt .= "      \"focus\": \"What to study\",\n";
        $prompt .= "      \"duration\": \"Recommended time\"\n";
        $prompt .= "    }\n";
        $prompt .= "  ]\n";
        $prompt .= "}\n\n";
        $prompt .= "Provide comprehensive but focused preparation materials.";
        
        return $prompt;
    }

    // Database helper methods
    private function getUserProfile($user_id) {
        $stmt = $this->pdo->prepare("SELECT * FROM ai_mentorship_profiles WHERE user_id = ?");
        $stmt->execute([$user_id]);
        return $stmt->fetch();
    }

    private function getUserPerformance($user_id) {
        $stmt = $this->pdo->prepare("SELECT * FROM ai_performance_tracking WHERE user_id = ? ORDER BY completed_at DESC LIMIT 20");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll();
    }

    private function getUserWeaknesses($user_id) {
        $stmt = $this->pdo->prepare("SELECT * FROM ai_weakness_analysis WHERE user_id = ? ORDER BY last_analyzed DESC");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll();
    }

    private function getUserContext($user_id) {
        $profile = $this->getUserProfile($user_id);
        $performance = $this->getUserPerformance($user_id);
        $weaknesses = $this->getUserWeaknesses($user_id);
        
        return [
            'profile' => $profile,
            'recent_performance' => $performance,
            'weaknesses' => $weaknesses
        ];
    }

    private function saveStudyPlan($user_id, $plan_data, $plan_type) {
        try {
            $this->pdo->beginTransaction();
            
            // Insert study plan
            $stmt = $this->pdo->prepare("INSERT INTO ai_study_plans (user_id, plan_type, title, description, start_date, end_date) VALUES (?, ?, ?, ?, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 7 DAY))");
            $stmt->execute([$user_id, $plan_type, $plan_data['title'], $plan_data['description']]);
            $plan_id = $this->pdo->lastInsertId();
            
            // Insert tasks
            foreach ($plan_data['tasks'] as $task) {
                $stmt = $this->pdo->prepare("INSERT INTO ai_study_tasks (plan_id, user_id, task_type, title, description, subject, topic, estimated_duration, difficulty_level, due_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $plan_id, $user_id, $task['task_type'], $task['title'], $task['description'],
                    $task['subject'], $task['topic'], $task['estimated_duration'],
                    $task['difficulty_level'], $task['due_date']
                ]);
            }
            
            $this->pdo->commit();
            return $plan_id;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    private function saveQAInteraction($user_id, $question, $answer, $subject, $topic) {
        $stmt = $this->pdo->prepare("INSERT INTO ai_qa_interactions (user_id, question, answer, subject, topic) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $question, $answer, $subject, $topic]);
    }

    private function saveMotivationalMessage($user_id, $message, $message_type, $context) {
        $stmt = $this->pdo->prepare("INSERT INTO ai_motivational_messages (user_id, message_type, message_text, context) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user_id, $message_type, $message, json_encode($context)]);
    }

    private function saveDailyChallenge($user_id, $challenge_data) {
        $stmt = $this->pdo->prepare("INSERT INTO ai_daily_challenges (user_id, challenge_date, challenge_type, title, description, subject, difficulty_level) VALUES (?, CURDATE(), ?, ?, ?, ?, ?)");
        $stmt->execute([
            $user_id, $challenge_data['challenge_type'], $challenge_data['title'],
            $challenge_data['description'], $challenge_data['subject'], $challenge_data['difficulty_level']
        ]);
        return $this->pdo->lastInsertId();
    }

    private function saveWeaknessAnalysis($user_id, $analysis_data) {
        foreach ($analysis_data['weaknesses'] as $weakness) {
            $stmt = $this->pdo->prepare("INSERT INTO ai_weakness_analysis (user_id, subject, topic, weakness_type, severity, suggested_actions) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $user_id, $weakness['subject'], $weakness['topic'],
                $weakness['weakness_type'], $weakness['severity'], $weakness['suggested_actions']
            ]);
        }
    }

    private function saveExamPrep($user_id, $exam_title, $subject, $exam_date, $prep_data) {
        $stmt = $this->pdo->prepare("INSERT INTO ai_exam_prep (user_id, exam_title, subject, exam_date, study_notes, predicted_questions) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $user_id, $exam_title, $subject, $exam_date,
            $prep_data['study_notes'], json_encode($prep_data['predicted_questions'])
        ]);
        return $this->pdo->lastInsertId();
    }

    private function getDefaultMotivationalMessage($message_type) {
        $messages = [
            'encouragement' => "You're doing great! Every step forward is progress. Keep pushing through!",
            'reminder' => "Don't forget to take a break and stay hydrated while studying!",
            'achievement' => "Congratulations on your progress! You're building a strong foundation for success.",
            'streak' => "Amazing! You're maintaining a great study streak. Consistency is key!",
            'exam_prep' => "You've got this! Trust in your preparation and stay confident."
        ];
        
        return $messages[$message_type] ?? $messages['encouragement'];
    }
}
?>
