<?php
/**
 * Educational News Feed API
 * Fetches top 3 latest educational news from RSS feeds
 * 
 * Features:
 * - RSS feed parsing
 * - Top 3 latest posts extraction
 * - Clean content formatting
 * - Error handling
 * - Caching support
 * - Monetization placeholders
 * 
 * @author Your Name
 * @version 1.0
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

// Configuration
$CACHE_DURATION = 3600; // 1 hour cache
$MAX_POSTS = 3;
$MAX_DESCRIPTION_LENGTH = 120;

// Educational RSS Feeds (can be expanded)
$RSS_FEEDS = [
    [
        'name' => 'StudyBarta',
        'url' => 'https://studybarta.com/feed/',
        'category' => 'Education',
        'emoji' => '📚'
    ],
    [
        'name' => 'EdTech Magazine',
        'url' => 'https://edtechmagazine.com/k12/rss.xml',
        'category' => 'EdTech',
        'emoji' => '💻'
    ],
    [
        'name' => 'Education Week',
        'url' => 'https://www.edweek.org/feed',
        'category' => 'Education News',
        'emoji' => '📰'
    ]
];

/**
 * Fetch and parse RSS feed
 */
function fetchRSSFeed($url) {
    $context = stream_context_create([
        'http' => [
            'timeout' => 10,
            'user_agent' => 'Mozilla/5.0 (compatible; EducationalNewsBot/1.0)'
        ]
    ]);
    
    $content = @file_get_contents($url, false, $context);
    
    if ($content === false) {
        return null;
    }
    
    // Parse XML
    $xml = @simplexml_load_string($content);
    if ($xml === false) {
        return null;
    }
    
    return $xml;
}

/**
 * Extract posts from RSS feed
 */
function extractPosts($xml, $source_name, $emoji) {
    $posts = [];
    
    if (isset($xml->channel->item)) {
        foreach ($xml->channel->item as $item) {
            $title = (string)$item->title;
            $description = (string)$item->description;
            $link = (string)$item->link;
            $pubDate = (string)$item->pubDate;
            
            // Clean and format content
            $clean_description = cleanDescription($description);
            $formatted_title = formatTitle($title, $emoji);
            
            $posts[] = [
                'title' => $formatted_title,
                'description' => $clean_description,
                'link' => $link,
                'source' => $source_name,
                'emoji' => $emoji,
                'pub_date' => $pubDate,
                'timestamp' => strtotime($pubDate)
            ];
        }
    }
    
    return $posts;
}

/**
 * Clean and format description
 */
function cleanDescription($description) {
    // Remove HTML tags
    $clean = strip_tags($description);
    
    // Remove extra whitespace
    $clean = preg_replace('/\s+/', ' ', $clean);
    $clean = trim($clean);
    
    // Truncate to max length
    if (strlen($clean) > $GLOBALS['MAX_DESCRIPTION_LENGTH']) {
        $clean = substr($clean, 0, $GLOBALS['MAX_DESCRIPTION_LENGTH']) . '...';
    }
    
    return $clean;
}

/**
 * Format title with emoji
 */
function formatTitle($title, $emoji) {
    // Remove HTML tags
    $clean_title = strip_tags($title);
    
    // Add emoji prefix
    return $emoji . ' ' . $clean_title;
}

/**
 * Get cached data
 */
function getCachedData($cache_key) {
    $cache_file = sys_get_temp_dir() . '/edu_news_' . md5($cache_key) . '.json';
    
    if (file_exists($cache_file)) {
        $data = json_decode(file_get_contents($cache_file), true);
        if ($data && (time() - $data['timestamp']) < $GLOBALS['CACHE_DURATION']) {
            return $data['posts'];
        }
    }
    
    return null;
}

/**
 * Save data to cache
 */
function saveToCache($cache_key, $posts) {
    $cache_file = sys_get_temp_dir() . '/edu_news_' . md5($cache_key) . '.json';
    $data = [
        'posts' => $posts,
        'timestamp' => time()
    ];
    
    file_put_contents($cache_file, json_encode($data));
}

/**
 * Generate sponsored post placeholder
 */
function generateSponsoredPost() {
    return [
        'title' => '🎓 Premium Study Resources',
        'description' => 'Unlock exclusive study materials, AI-powered summaries, and personalized learning paths. Upgrade to premium for better grades!',
        'link' => '#premium-upgrade',
        'source' => 'Sponsored',
        'emoji' => '⭐',
        'is_sponsored' => true,
        'pub_date' => date('r'),
        'timestamp' => time()
    ];
}

/**
 * Generate AI summary placeholder
 */
function generateAISummaryPost() {
    return [
        'title' => '🤖 AI-Powered Learning Assistant',
        'description' => 'Get instant summaries of complex topics, personalized study plans, and smart revision reminders. Your AI study buddy awaits!',
        'link' => '#ai-assistant',
        'source' => 'AI Features',
        'emoji' => '🤖',
        'is_ai_feature' => true,
        'pub_date' => date('r'),
        'timestamp' => time()
    ];
}

/**
 * Main function to get educational news
 */
function getEducationalNews() {
    global $RSS_FEEDS, $MAX_POSTS;
    
    // Check cache first
    $cache_key = 'educational_news_' . date('Y-m-d-H');
    $cached_posts = getCachedData($cache_key);
    
    if ($cached_posts !== null) {
        return [
            'success' => true,
            'posts' => $cached_posts,
            'cached' => true,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
    
    $all_posts = [];
    
    // Fetch from all RSS feeds
    foreach ($RSS_FEEDS as $feed) {
        try {
            $xml = fetchRSSFeed($feed['url']);
            if ($xml) {
                $posts = extractPosts($xml, $feed['name'], $feed['emoji']);
                $all_posts = array_merge($all_posts, $posts);
            }
        } catch (Exception $e) {
            // Log error silently and continue with other feeds
            error_log("Error fetching RSS feed {$feed['url']}: " . $e->getMessage());
        }
    }
    
    // Sort by timestamp (newest first)
    usort($all_posts, function($a, $b) {
        return $b['timestamp'] - $a['timestamp'];
    });
    
    // Get top posts
    $top_posts = array_slice($all_posts, 0, $MAX_POSTS);
    
    // Add sponsored/AI posts if we have space
    if (count($top_posts) < $MAX_POSTS) {
        $sponsored_post = generateSponsoredPost();
        $ai_post = generateAISummaryPost();
        
        // Add sponsored post (monetization opportunity)
        if (count($top_posts) < $MAX_POSTS - 1) {
            $top_posts[] = $sponsored_post;
        }
        
        // Add AI feature post
        if (count($top_posts) < $MAX_POSTS) {
            $top_posts[] = $ai_post;
        }
    }
    
    // Save to cache
    saveToCache($cache_key, $top_posts);
    
    return [
        'success' => true,
        'posts' => $top_posts,
        'cached' => false,
        'timestamp' => date('Y-m-d H:i:s'),
        'total_feeds_checked' => count($RSS_FEEDS),
        'posts_found' => count($all_posts)
    ];
}

// Handle API requests
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET' || $method === 'POST') {
    try {
        $result = getEducationalNews();
        echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'Failed to fetch educational news',
            'message' => $e->getMessage(),
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Method not allowed',
        'allowed_methods' => ['GET', 'POST']
    ]);
}
?> 