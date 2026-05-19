<?php
/**
 * Educational News Feed Component
 * Displays top 3 educational news in a styled card format
 * 
 * Features:
 * - Responsive design
 * - Scrollable cards
 * - Clean styling
 * - Integration ready
 * - Monetization placeholders
 */

// Fetch educational news data
function getEducationalNewsData() {
    $api_url = '../api/educational_news_feed.php';
    
    // Try to fetch from API
    $context = stream_context_create([
        'http' => [
            'timeout' => 5,
            'user_agent' => 'EducationalNewsComponent/1.0'
        ]
    ]);
    
    $response = @file_get_contents($api_url, false, $context);
    
    if ($response === false) {
        // Fallback: return sample data if API is unavailable
        return [
            'success' => true,
            'posts' => [
                [
                    'title' => '📚 New Study Techniques for 2024',
                    'description' => 'Discover the latest research-backed study methods that can improve your learning efficiency by up to 40%.',
                    'link' => '#',
                    'source' => 'StudyBarta',
                    'emoji' => '📚',
                    'pub_date' => date('r'),
                    'timestamp' => time()
                ],
                [
                    'title' => '💻 AI in Education: What Students Need to Know',
                    'description' => 'How artificial intelligence is transforming the way students learn and how to leverage these tools effectively.',
                    'link' => '#',
                    'source' => 'EdTech Magazine',
                    'emoji' => '💻',
                    'pub_date' => date('r'),
                    'timestamp' => time() - 3600
                ],
                [
                    'title' => '⭐ Premium Study Resources',
                    'description' => 'Unlock exclusive study materials, AI-powered summaries, and personalized learning paths. Upgrade to premium!',
                    'link' => '#premium-upgrade',
                    'source' => 'Sponsored',
                    'emoji' => '⭐',
                    'is_sponsored' => true,
                    'pub_date' => date('r'),
                    'timestamp' => time() - 7200
                ]
            ],
            'cached' => false,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
    
    $data = json_decode($response, true);
    return $data ?: ['success' => false, 'posts' => []];
}

$newsData = getEducationalNewsData();
?>

<!-- Educational News Feed Component -->
<div class="educational-news-feed">
    <!-- Header -->
    <div class="news-feed-header">
        <div class="news-feed-title">
            <i class="fas fa-newspaper text-primary me-2"></i>
            <h6 class="mb-0">Top Educational News</h6>
        </div>
        <button class="refresh-news-btn" onclick="refreshEducationalNews()" title="Refresh News">
            <i class="fas fa-sync-alt"></i>
        </button>
    </div>
    
    <!-- News Container -->
    <div class="news-container" id="educational-news-container">
        <?php if ($newsData['success'] && !empty($newsData['posts'])): ?>
            <?php foreach ($newsData['posts'] as $index => $post): ?>
                <div class="news-card <?php echo isset($post['is_sponsored']) ? 'sponsored' : ''; ?>" 
                     data-link="<?php echo htmlspecialchars($post['link']); ?>">
                    
                    <!-- News Header -->
                    <div class="news-header">
                        <div class="news-source">
                            <span class="news-emoji"><?php echo htmlspecialchars($post['emoji']); ?></span>
                            <span class="source-name"><?php echo htmlspecialchars($post['source']); ?></span>
                            <?php if (isset($post['is_sponsored'])): ?>
                                <span class="sponsored-badge">Sponsored</span>
                            <?php endif; ?>
                        </div>
                        <div class="news-time">
                            <?php echo time_ago($post['timestamp']); ?>
                        </div>
                    </div>
                    
                    <!-- News Content -->
                    <div class="news-content">
                        <h6 class="news-title">
                            <a href="<?php echo htmlspecialchars($post['link']); ?>" 
                               target="_blank" 
                               rel="noopener noreferrer"
                               class="news-title-link">
                                <?php echo htmlspecialchars($post['title']); ?>
                            </a>
                        </h6>
                        <p class="news-description">
                            <?php echo htmlspecialchars($post['description']); ?>
                        </p>
                    </div>
                    
                    <!-- News Actions -->
                    <div class="news-actions">
                        <button class="news-action-btn" onclick="shareNews('<?php echo htmlspecialchars($post['title']); ?>', '<?php echo htmlspecialchars($post['link']); ?>')" title="Share">
                            <i class="fas fa-share-alt"></i>
                        </button>
                        <button class="news-action-btn" onclick="bookmarkNews(<?php echo $index; ?>)" title="Bookmark">
                            <i class="far fa-bookmark"></i>
                        </button>
                        <?php if (isset($post['is_sponsored'])): ?>
                            <button class="news-action-btn premium-btn" onclick="showPremiumFeatures()" title="Premium Features">
                                <i class="fas fa-crown"></i>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <!-- Loading/Error State -->
            <div class="news-card loading-state">
                <div class="loading-content">
                    <i class="fas fa-spinner fa-spin text-primary"></i>
                    <p>Loading educational news...</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Footer -->
    <div class="news-feed-footer">
        <small class="text-muted">
            <i class="fas fa-clock me-1"></i>
            Last updated: <?php echo $newsData['timestamp'] ?? 'Unknown'; ?>
        </small>
        <button class="view-all-news-btn" onclick="viewAllNews()">
            View All News <i class="fas fa-arrow-right ms-1"></i>
        </button>
    </div>
</div>

<!-- Educational News Feed Styles -->
<style>
.educational-news-feed {
    background: var(--card-bg-color, #ffffff);
    border-radius: 12px;
    border: 1px solid var(--border-color, #e1e5e9);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    margin-bottom: 1.5rem;
    transition: box-shadow 0.3s ease;
}

.educational-news-feed:hover {
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
}

/* Header Styles */
.news-feed-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.25rem;
    border-bottom: 1px solid var(--border-color, #e1e5e9);
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.news-feed-title {
    display: flex;
    align-items: center;
    font-weight: 600;
}

.news-feed-title h6 {
    margin: 0;
    color: white;
}

.refresh-news-btn {
    background: rgba(255, 255, 255, 0.2);
    border: none;
    color: white;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: background-color 0.2s ease;
}

.refresh-news-btn:hover {
    background: rgba(255, 255, 255, 0.3);
}

/* News Container */
.news-container {
    max-height: 400px;
    overflow-y: auto;
    scrollbar-width: thin;
    scrollbar-color: #c1c1c1 transparent;
}

.news-container::-webkit-scrollbar {
    width: 6px;
}

.news-container::-webkit-scrollbar-track {
    background: transparent;
}

.news-container::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 3px;
}

.news-container::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}

/* News Card */
.news-card {
    padding: 1rem 1.25rem;
    border-bottom: 1px solid var(--border-color, #e1e5e9);
    transition: background-color 0.2s ease;
    cursor: pointer;
}

.news-card:last-child {
    border-bottom: none;
}

.news-card:hover {
    background-color: var(--hover-overlay, #f8f9fa);
}

.news-card.sponsored {
    background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
    border-left: 4px solid #ffc107;
}

/* News Header */
.news-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.75rem;
}

.news-source {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.85rem;
    color: var(--text-light, #6c757d);
}

.news-emoji {
    font-size: 1.1rem;
}

.source-name {
    font-weight: 500;
}

.sponsored-badge {
    background: #ffc107;
    color: #212529;
    padding: 0.2rem 0.5rem;
    border-radius: 12px;
    font-size: 0.7rem;
    font-weight: 600;
    text-transform: uppercase;
}

.news-time {
    font-size: 0.75rem;
    color: var(--text-light, #6c757d);
}

/* News Content */
.news-content {
    margin-bottom: 0.75rem;
}

.news-title {
    margin: 0 0 0.5rem 0;
    font-size: 1rem;
    font-weight: 600;
    line-height: 1.4;
}

.news-title-link {
    color: var(--text-dark, #212529);
    text-decoration: none;
    transition: color 0.2s ease;
}

.news-title-link:hover {
    color: var(--primary-color, #007bff);
}

.news-description {
    margin: 0;
    font-size: 0.9rem;
    color: var(--text-light, #6c757d);
    line-height: 1.5;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* News Actions */
.news-actions {
    display: flex;
    gap: 0.5rem;
    justify-content: flex-end;
}

.news-action-btn {
    background: none;
    border: none;
    color: var(--text-light, #6c757d);
    padding: 0.5rem;
    border-radius: 50%;
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 0.9rem;
}

.news-action-btn:hover {
    background-color: var(--hover-overlay, #f8f9fa);
    color: var(--primary-color, #007bff);
}

.premium-btn {
    color: #ffc107;
}

.premium-btn:hover {
    background-color: #fff3cd;
    color: #856404;
}

/* Footer */
.news-feed-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 1.25rem;
    background-color: var(--bg-color, #f8f9fa);
    border-top: 1px solid var(--border-color, #e1e5e9);
}

.view-all-news-btn {
    background: none;
    border: none;
    color: var(--primary-color, #007bff);
    font-size: 0.85rem;
    cursor: pointer;
    transition: color 0.2s ease;
}

.view-all-news-btn:hover {
    color: var(--primary-dark, #0056b3);
}

/* Loading State */
.loading-state {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 120px;
}

.loading-content {
    text-align: center;
    color: var(--text-light, #6c757d);
}

.loading-content i {
    font-size: 1.5rem;
    margin-bottom: 0.5rem;
}

/* Responsive Design */
@media (max-width: 768px) {
    .news-feed-header {
        padding: 0.75rem 1rem;
    }
    
    .news-card {
        padding: 0.75rem 1rem;
    }
    
    .news-feed-footer {
        padding: 0.5rem 1rem;
        flex-direction: column;
        gap: 0.5rem;
        text-align: center;
    }
    
    .news-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.25rem;
    }
    
    .news-time {
        align-self: flex-end;
    }
}

/* Animation for refresh button */
.refresh-news-btn i {
    transition: transform 0.3s ease;
}

.refresh-news-btn:active i {
    transform: rotate(180deg);
}

/* Premium features highlight */
.news-card.sponsored .news-title-link {
    color: #856404;
}

.news-card.sponsored .news-title-link:hover {
    color: #533f03;
}
</style>

<!-- JavaScript Functions -->
<script>
// Time ago function (if not already defined)
function time_ago(timestamp) {
    const now = Math.floor(Date.now() / 1000);
    const diff = now - timestamp;
    
    if (diff < 60) return 'Just now';
    if (diff < 3600) return Math.floor(diff / 60) + 'm ago';
    if (diff < 86400) return Math.floor(diff / 3600) + 'h ago';
    if (diff < 2592000) return Math.floor(diff / 86400) + 'd ago';
    return Math.floor(diff / 2592000) + 'mo ago';
}

// Refresh educational news
async function refreshEducationalNews() {
    const container = document.getElementById('educational-news-container');
    const refreshBtn = document.querySelector('.refresh-news-btn i');
    
    // Add loading animation
    refreshBtn.classList.add('fa-spin');
    container.innerHTML = `
        <div class="news-card loading-state">
            <div class="loading-content">
                <i class="fas fa-spinner fa-spin text-primary"></i>
                <p>Refreshing news...</p>
            </div>
        </div>
    `;
    
    try {
        const response = await fetch('../api/educational_news_feed.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Reload the component
            location.reload();
        } else {
            throw new Error(data.error || 'Failed to refresh news');
        }
    } catch (error) {
        console.error('Error refreshing news:', error);
        container.innerHTML = `
            <div class="news-card loading-state">
                <div class="loading-content">
                    <i class="fas fa-exclamation-triangle text-warning"></i>
                    <p>Failed to refresh news. Please try again.</p>
                </div>
            </div>
        `;
    } finally {
        refreshBtn.classList.remove('fa-spin');
    }
}

// Share news function
function shareNews(title, link) {
    if (navigator.share) {
        navigator.share({
            title: title,
            url: link
        });
    } else {
        // Fallback: copy to clipboard
        const textToShare = `${title}\n\nRead more: ${link}`;
        navigator.clipboard.writeText(textToShare).then(() => {
            alert('News link copied to clipboard!');
        }).catch(() => {
            // Fallback for older browsers
            const textArea = document.createElement('textarea');
            textArea.value = textToShare;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
            alert('News link copied to clipboard!');
        });
    }
}

// Bookmark news function
function bookmarkNews(index) {
    // Get news data from the card
    const newsCard = document.querySelectorAll('.news-card')[index];
    const title = newsCard.querySelector('.news-title-link').textContent;
    const link = newsCard.querySelector('.news-title-link').href;
    
    // Store in localStorage (simple bookmarking)
    const bookmarks = JSON.parse(localStorage.getItem('edu_news_bookmarks') || '[]');
    const bookmark = { title, link, timestamp: Date.now() };
    
    // Check if already bookmarked
    const existingIndex = bookmarks.findIndex(b => b.link === link);
    if (existingIndex >= 0) {
        bookmarks.splice(existingIndex, 1);
        alert('News removed from bookmarks!');
    } else {
        bookmarks.push(bookmark);
        alert('News added to bookmarks!');
    }
    
    localStorage.setItem('edu_news_bookmarks', JSON.stringify(bookmarks));
    
    // Update bookmark icon
    const bookmarkBtn = newsCard.querySelector('.news-action-btn:nth-child(2) i');
    if (existingIndex >= 0) {
        bookmarkBtn.className = 'far fa-bookmark';
    } else {
        bookmarkBtn.className = 'fas fa-bookmark';
    }
}

// Show premium features
function showPremiumFeatures() {
    // This can be expanded to show a modal with premium features
    alert('🌟 Premium Features Coming Soon!\n\n• AI-powered study summaries\n• Personalized learning paths\n• Advanced analytics\n• Ad-free experience\n• Priority support');
}

// View all news function
function viewAllNews() {
    // This can be expanded to show a full news page
    alert('📰 Full News Feed Coming Soon!\n\nThis will open a dedicated page with all educational news, advanced filtering, and search capabilities.');
}

// Initialize bookmark icons
document.addEventListener('DOMContentLoaded', function() {
    const bookmarks = JSON.parse(localStorage.getItem('edu_news_bookmarks') || '[]');
    const newsCards = document.querySelectorAll('.news-card');
    
    newsCards.forEach((card, index) => {
        const link = card.querySelector('.news-title-link').href;
        const bookmarkBtn = card.querySelector('.news-action-btn:nth-child(2) i');
        
        if (bookmarks.find(b => b.link === link)) {
            bookmarkBtn.className = 'fas fa-bookmark';
        }
    });
});
</script> 