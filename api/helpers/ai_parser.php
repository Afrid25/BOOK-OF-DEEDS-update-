<?php
/**
 * AI Parser Service - Parses syllabus into structured curriculum
 * Uses OpenAI API to intelligently parse and structure course syllabi
 */

class AICurriculumParser {
    private $openai_api_key;
    private $model = 'gpt-3.5-turbo';

    public function __construct() {
        $this->openai_api_key = getenv('OPENAI_API_KEY') ?: 'YOUR_OPENAI_API_KEY';
    }

    /**
     * Parse syllabus text into structured chapters and topics
     * @param string $syllabus The raw syllabus text
     * @return array Structured curriculum data
     */
    public function parseSyllabus($syllabus) {
        if (empty($syllabus)) {
            return ['success' => false, 'message' => 'Syllabus text is empty'];
        }

        $prompt = "You are an expert curriculum designer. Parse the following syllabus and " .
            "transform it into a structured JSON object.\n" .
            "Rules for parsing:\n" .
            "1. The top-level structure MUST be a JSON object with a single key: 'chapters'.\n" .
            "2. The 'chapters' key must contain an array of chapter objects.\n" .
            "3. Each chapter object MUST have three keys:\n" .
            "   - 'chapter_name': A string.\n" .
            "   - 'estimated_time': A string describing the estimated time to complete the chapter (e.g., '2 hours', '45 minutes', '1 week'). Infer this from the syllabus if possible; otherwise, make a reasonable guess.\n" .
            "   - 'topics': An array of strings, where each string is a specific topic or sub-topic within that chapter.\n" .
            "4. Consolidate all related sub-topics under their main chapter. If the syllabus is structured by 'Week' or 'Module', treat each one as a 'chapter'.\n" .
            "5. Clean up any extraneous text, numbering, or formatting. The output must be ONLY the raw JSON, with no markdown, comments, or explanations.\n" .
            "Example of required output format:\n" .
            "{\n" .
            "  \"chapters\": [\n" .
            "    {\n" .
            "      \"chapter_name\": \"Introduction to Quantum Physics\",\n" .
            "      \"estimated_time\": \"3 hours\",\n" .
            "      \"topics\": [\n" .
            "        \"Historical Overview\",\n" .
            "        \"Wave-Particle Duality\",\n" .
            "        \"The Schrödinger Equation\"\n" .
            "      ]\n" .
            "    },\n" .
            "    {\n" .
            "      \"chapter_name\": \"Week 2: Superposition and Entanglement\",\n" .
            "      \"estimated_time\": \"5 hours\",\n" .
            "      \"topics\": [\n" .
            "        \"Understanding Superposition\",\n" .
            "        \"The EPR Paradox\",\n" .
            "        \"Quantum Entanglement in Practice\"\n" .
            "      ]\n" .
            "    }\n" .
            "  ]\n" .
            "}\n\n" .
            "Syllabus to parse:\n---\n" . $syllabus;

        $postData = [
            'model' => $this->model,
            'messages' => [['role' => 'user', 'content' => $prompt]],
            'temperature' => 0.1, // Very low temperature for deterministic, structured output
            'response_format' => ['type' => 'json_object'] // Crucial for reliable JSON output
        ];

        // --- cURL Request ---
        $ch = curl_init('https://api.openai.com/v1/chat/completions');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->openai_api_key
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 90); // Increased timeout for more powerful models
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        // --- Enhanced Error Handling ---
        if ($curl_error) {
            error_log("cURL Error in ai_parser: " . $curl_error);
            return ['success' => false, 'message' => 'Network error while contacting AI service.'];
        }

        if ($http_code !== 200) {
            // Provide more specific feedback for common errors
            $errorMessage = 'Failed to communicate with AI service. Status: ' . $http_code;
            if ($http_code === 401) $errorMessage = 'Authentication Error: The provided API key is invalid or has been revoked.';
            if ($http_code === 429) $errorMessage = 'Rate Limit Exceeded: You have hit your usage limit. Please check your billing on the AI provider platform.';
            if ($http_code === 500) $errorMessage = 'AI Server Error: The provider is experiencing issues. Please try again later.';
            error_log("AI API Error: HTTP " . $http_code . " - Response: " . $response);
            return ['success' => false, 'message' => $errorMessage];
        }

        $result = json_decode($response, true);
        $jsonContent = $result['choices'][0]['message']['content'] ?? null;

        if (!$jsonContent) {
            return ['success' => false, 'message' => 'AI returned a valid response but with no content.'];
        }

        // --- Deep Validation of AI Output ---
        $structuredData = json_decode($jsonContent, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("AI JSON Parse Error: " . json_last_error_msg() . " - Raw content: " . $jsonContent);
            return ['success' => false, 'message' => 'AI returned malformed data that could not be parsed. Please try again.'];
        }

        // 1. Check for the top-level 'chapters' key
        if (!isset($structuredData['chapters']) || !is_array($structuredData['chapters'])) {
            return ['success' => false, 'message' => "AI Response Error: The required 'chapters' array is missing from the output."];
        }

        // 2. Validate the structure of each chapter object
        foreach ($structuredData['chapters'] as $chapter) {
            if (!isset($chapter['chapter_name'], $chapter['estimated_time'], $chapter['topics']) || !is_array($chapter['topics'])) {
                return ['success' => false, 'message' => "AI Response Error: A chapter in the output is missing required keys ('chapter_name', 'estimated_time', 'topics')."];
            }
        }

        return ['success' => true, 'data' => $structuredData['chapters']]; // Return the inner array directly
    }
}
?>
