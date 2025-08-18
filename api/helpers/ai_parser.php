<?php
// api/helpers/ai_parser.php

require_once __DIR__ . '/../../config.php';

/**
 * Parses a syllabus using a premium AI model and prompt for higher accuracy and more detail.
 *
 * @param string $syllabus The raw text of the course syllabus.
 * @return array An array with success status and either structured data or a detailed error message.
 */
function parseSyllabusWithAI($syllabus) {
    // --- Configuration ---
    $apiKey = OPENAI_API_KEY ?? ''; // Use your Groq or OpenAI key from config.php
    
    // Determine which API and model to use based on the key prefix.
    if (str_starts_with($apiKey, 'gsk_')) {
        // Groq Configuration
        $apiUrl = 'https://api.groq.com/openai/v1/chat/completions';
        // Llama 3 70b is much more capable than 8b for complex parsing.
        $model = 'llama3-70b-8192'; 
    } else {
        // OpenAI Configuration (default)
        $apiUrl = 'https://api.openai.com/v1/chat/completions';
        // GPT-4 is the premium choice for accuracy and instruction following.
        $model = 'gpt-4-turbo-preview';
    }

    if (empty($apiKey)) {
        return ['success' => false, 'message' => 'API key is not configured in config.php.'];
    }

    // --- INTELLIGENT & ROBUST PROMPT ENGINEERING ---
    // This prompt is much more detailed and resilient to messy inputs.
    $prompt = "You are an expert curriculum designer. Your task is to analyze the following course syllabus and transform it into a structured JSON object.

    Rules for parsing:
    1.  The top-level structure MUST be a JSON object with a single key: 'chapters'.
    2.  The 'chapters' key must contain an array of chapter objects.
    3.  Each chapter object MUST have three keys:
        - 'chapter_name': A string.
        - 'estimated_time': A string describing the estimated time to complete the chapter (e.g., '2 hours', '45 minutes', '1 week'). Infer this from the syllabus if possible; otherwise, make a reasonable guess.
        - 'topics': An array of strings, where each string is a specific topic or sub-topic within that chapter.
    4.  Consolidate all related sub-topics under their main chapter. If the syllabus is structured by 'Week' or 'Module', treat each one as a 'chapter'.
    5.  Clean up any extraneous text, numbering, or formatting. The output must be ONLY the raw JSON, with no markdown, comments, or explanations.

    Example of required output format:
    {
      \"chapters\": [
        {
          \"chapter_name\": \"Introduction to Quantum Physics\",
          \"estimated_time\": \"3 hours\",
          \"topics\": [
            \"Historical Overview\",
            \"Wave-Particle Duality\",
            \"The Schrödinger Equation\"
          ]
        },
        {
          \"chapter_name\": \"Week 2: Superposition and Entanglement\",
          \"estimated_time\": \"5 hours\",
          \"topics\": [
            \"Understanding Superposition\",
            \"The EPR Paradox\",
            \"Quantum Entanglement in Practice\"
          ]
        }
      ]
    }
    
    Syllabus to parse:
    ---
    " . $syllabus;

    $postData = [
        'model' => $model,
        'messages' => [['role' => 'user', 'content' => $prompt]],
        'temperature' => 0.1, // Very low temperature for deterministic, structured output
        'response_format' => ['type' => 'json_object'] // Crucial for reliable JSON output
    ];

    // --- cURL Request ---
    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey
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
?>