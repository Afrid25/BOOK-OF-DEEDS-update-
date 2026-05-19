// ======================================================================
// ==        PERSONALIZED FEED - FRONTEND JAVASCRIPT                  ==
// ==   Advanced AI-powered dashboard with dynamic interactions        ==
// ======================================================================

class PersonalizedFeed {
    constructor() {
        this.currentFilter = 'all';
        this.currentPosts = [];
        this.isLoading = false;
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.loadPosts();
        this.setupMoodTracking();
    }

    setupEventListeners() {
        // Filter buttons
        document.querySelectorAll('.feed-filter-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                this.filterPosts(e.target.dataset.filter);
            });
        });

        // AI suggestions button
        const aiSuggestionsBtn = document.getElementById('ai-suggestions-btn');
        if (aiSuggestionsBtn) {
            aiSuggestionsBtn.addEventListener('click', () => this.loadAISuggestions());
        }

        // Analytics button
        const analyticsBtn = document.getElementById('analytics-btn');
        if (analyticsBtn) {
            analyticsBtn.addEventListener('click', () => this.showAnalytics());
        }
    }

    async loadPosts(filter = 'all') {
        if (this.isLoading) return;
        
        this.isLoading = true;
        this.showLoadingState();

        try {
            const response = await fetch(`api/personalized_feed.php?action=get_posts&type=${filter === 'all' ? '' : filter}&limit=10`);
            const data = await response.json();

            if (data.success) {
                this.currentPosts = data.data.posts;
                this.renderPosts();
                this.updateFilterButtons(filter);
                this.showUserLevel(data.data.user_level);
            } else {
                this.showError('Failed to load posts: ' + data.message);
            }
        } catch (error) {
            this.showError('Network error: ' + error.message);
        } finally {
            this.isLoading = false;
            this.hideLoadingState();
        }
    }

    renderPosts() {
        const feedContainer = document.getElementById('personalized-feed');
        if (!feedContainer) return;

        if (this.currentPosts.length === 0) {
            feedContainer.innerHTML = `
                <div class="empty-state">
                    <div class="empty-state-icon">📚</div>
                    <h3>No posts available</h3>
                    <p>We're working on creating personalized content for you!</p>
                    <button class="btn btn-primary" onclick="personalizedFeed.loadAISuggestions()">
                        Get AI Suggestions
                    </button>
                </div>
            `;
            return;
        }

        feedContainer.innerHTML = this.currentPosts.map(post => this.createPostHTML(post)).join('');
        this.setupPostInteractions();
    }

    createPostHTML(post) {
        const postTypeIcon = this.getPostTypeIcon(post.post_type);
        const postTypeClass = this.getPostTypeClass(post.post_type);
        const isAIGenerated = post.is_ai_generated || false;
        
        return `
            <div class="feed-post ${postTypeClass} ${isAIGenerated ? 'ai-generated' : ''}" data-post-id="${post.id}" data-post-type="${post.post_type}">
                <div class="post-header">
                    <div class="post-type-badge">
                        <i class="${postTypeIcon}"></i>
                        ${this.capitalizeFirst(post.post_type.replace('_', ' '))}
                        ${isAIGenerated ? '<span class="ai-badge">🤖 AI</span>' : ''}
                    </div>
                    <div class="post-actions">
                        <button class="action-btn bookmark-btn ${post.is_bookmarked ? 'active' : ''}" 
                                onclick="personalizedFeed.handleInteraction('${post.id}', 'bookmark', '${post.post_type}')">
                            <i class="bi bi-bookmark${post.is_bookmarked ? '-fill' : ''}"></i>
                        </button>
                        <button class="action-btn share-btn" 
                                onclick="personalizedFeed.handleInteraction('${post.id}', 'share', '${post.post_type}')">
                            <i class="bi bi-share"></i>
                        </button>
                    </div>
                </div>
                
                <div class="post-content">
                    <h3 class="post-title">${post.title}</h3>
                    <p class="post-text">${post.content}</p>
                </div>
                
                <div class="post-footer">
                    <div class="post-stats">
                        <span class="stat-item">
                            <i class="bi bi-eye"></i> ${post.total_views || 0}
                        </span>
                        <span class="stat-item">
                            <i class="bi bi-heart"></i> ${post.total_likes || 0}
                        </span>
                        <span class="stat-item">
                            <i class="bi bi-share"></i> ${post.total_shares || 0}
                        </span>
                    </div>
                    
                    <div class="post-actions-bottom">
                        <button class="action-btn like-btn ${post.is_liked ? 'active' : ''}" 
                                onclick="personalizedFeed.handleInteraction('${post.id}', 'like', '${post.post_type}')">
                            <i class="bi bi-heart${post.is_liked ? '-fill' : ''}"></i>
                            ${post.is_liked ? 'Liked' : 'Like'}
                        </button>
                        
                        <button class="action-btn hide-btn" 
                                onclick="personalizedFeed.handleInteraction('${post.id}', 'hide', '${post.post_type}')">
                            <i class="bi bi-eye-slash"></i>
                            Hide
                        </button>
                    </div>
                </div>
                
                ${isAIGenerated ? `
                    <div class="ai-feedback">
                        <p>How was this AI-generated content?</p>
                        <div class="feedback-buttons">
                            <button class="btn btn-sm btn-outline-success" onclick="personalizedFeed.handleAIFeedback('${post.id}', 'positive')">
                                👍 Helpful
                            </button>
                            <button class="btn btn-sm btn-outline-secondary" onclick="personalizedFeed.handleAIFeedback('${post.id}', 'neutral')">
                                🤔 Okay
                            </button>
                            <button class="btn btn-sm btn-outline-danger" onclick="personalizedFeed.handleAIFeedback('${post.id}', 'negative')">
                                👎 Not Helpful
                            </button>
                        </div>
                    </div>
                ` : ''}
            </div>
        `;
    }

    getPostTypeIcon(postType) {
        const icons = {
            'motivation': 'bi bi-lightning-charge',
            'exam_tips': 'bi bi-journal-text',
            'study_hacks': 'bi bi-lightbulb',
            'success_story': 'bi bi-trophy',
            'quote': 'bi bi-quote',
            'challenge': 'bi bi-flag',
            'reminder': 'bi bi-bell',
            'achievement': 'bi bi-star'
        };
        return icons[postType] || 'bi bi-file-text';
    }

    getPostTypeClass(postType) {
        return `post-type-${postType.replace('_', '-')}`;
    }

    capitalizeFirst(str) {
        return str.charAt(0).toUpperCase() + str.slice(1);
    }

    async handleInteraction(postId, interactionType, postType) {
        try {
            const response = await fetch('api/personalized_feed.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=interact&post_id=${postId}&interaction_type=${interactionType}&post_type=${postType}`
            });

            const data = await response.json();

            if (data.success) {
                this.updatePostInteraction(postId, interactionType);
                this.showSuccess(`Interaction recorded: ${interactionType}`);
            } else {
                this.showError('Failed to record interaction: ' + data.message);
            }
        } catch (error) {
            this.showError('Network error: ' + error.message);
        }
    }

    updatePostInteraction(postId, interactionType) {
        const postElement = document.querySelector(`[data-post-id="${postId}"]`);
        if (!postElement) return;

        switch (interactionType) {
            case 'like':
                const likeBtn = postElement.querySelector('.like-btn');
                const likeIcon = likeBtn.querySelector('i');
                
                if (likeBtn.classList.contains('active')) {
                    likeBtn.classList.remove('active');
                    likeIcon.className = 'bi bi-heart';
                    likeBtn.innerHTML = '<i class="bi bi-heart"></i> Like';
                } else {
                    likeBtn.classList.add('active');
                    likeIcon.className = 'bi bi-heart-fill';
                    likeBtn.innerHTML = '<i class="bi bi-heart-fill"></i> Liked';
                }
                break;

            case 'bookmark':
                const bookmarkBtn = postElement.querySelector('.bookmark-btn');
                const bookmarkIcon = bookmarkBtn.querySelector('i');
                
                if (bookmarkBtn.classList.contains('active')) {
                    bookmarkBtn.classList.remove('active');
                    bookmarkIcon.className = 'bi bi-bookmark';
                } else {
                    bookmarkBtn.classList.add('active');
                    bookmarkIcon.className = 'bi bi-bookmark-fill';
                }
                break;

            case 'hide':
                postElement.style.display = 'none';
                this.showSuccess('Post hidden from your feed');
                break;
        }
    }

    async handleAIFeedback(postId, feedback) {
        try {
            localStorage.setItem(`ai_feedback_${postId}`, feedback);
            
            const feedbackElement = document.querySelector(`[data-post-id="${postId}"] .ai-feedback`);
            if (feedbackElement) {
                feedbackElement.innerHTML = `
                    <div class="feedback-thanks">
                        <i class="bi bi-check-circle text-success"></i>
                        <p>Thank you for your feedback! We'll use it to improve future suggestions.</p>
                    </div>
                `;
            }
            
            this.showSuccess('Thank you for your feedback!');
        } catch (error) {
            this.showError('Failed to submit feedback: ' + error.message);
        }
    }

    filterPosts(filter) {
        this.currentFilter = filter;
        this.loadPosts(filter);
    }

    updateFilterButtons(activeFilter) {
        document.querySelectorAll('.feed-filter-btn').forEach(btn => {
            btn.classList.remove('active');
            if (btn.dataset.filter === activeFilter) {
                btn.classList.add('active');
            }
        });
    }

    async loadAISuggestions() {
        try {
            const response = await fetch('api/personalized_feed.php?action=get_ai_suggestions&type=post&limit=5');
            const data = await response.json();

            if (data.success) {
                this.showAISuggestions(data.data.suggestions);
            } else {
                this.showError('Failed to load AI suggestions: ' + data.message);
            }
        } catch (error) {
            this.showError('Network error: ' + error.message);
        }
    }

    showAISuggestions(suggestions) {
        const modal = document.createElement('div');
        modal.className = 'ai-suggestions-modal';
        modal.innerHTML = `
            <div class="modal-content">
                <div class="modal-header">
                    <h3>🤖 AI-Powered Suggestions</h3>
                    <button class="close-btn" onclick="this.closest('.ai-suggestions-modal').remove()">&times;</button>
                </div>
                <div class="modal-body">
                    <p>Here are some personalized suggestions based on your interests:</p>
                    <div class="suggestions-list">
                        ${suggestions.map(suggestion => `
                            <div class="suggestion-item">
                                <p>${suggestion.content}</p>
                                <div class="suggestion-actions">
                                    <button class="btn btn-sm btn-primary" onclick="personalizedFeed.useSuggestion('${suggestion.content}')">
                                        Use This
                                    </button>
                                    <button class="btn btn-sm btn-outline-secondary" onclick="personalizedFeed.generateNewSuggestion()">
                                        Generate Another
                                    </button>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(modal);
    }

    useSuggestion(content) {
        this.showSuccess('Suggestion saved!');
        document.querySelector('.ai-suggestions-modal').remove();
    }

    generateNewSuggestion() {
        this.loadAISuggestions();
    }

    setupMoodTracking() {
        const moodContainer = document.getElementById('mood-tracking');
        if (!moodContainer) return;

        const moodTypes = [
            { type: 'excited', emoji: '😊', label: 'Excited' },
            { type: 'motivated', emoji: '💪', label: 'Motivated' },
            { type: 'focused', emoji: '🎯', label: 'Focused' },
            { type: 'tired', emoji: '😴', label: 'Tired' },
            { type: 'stressed', emoji: '😰', label: 'Stressed' },
            { type: 'happy', emoji: '😄', label: 'Happy' },
            { type: 'neutral', emoji: '😐', label: 'Neutral' }
        ];

        const moodHTML = `
            <div class="mood-tracking-container">
                <h4>How are you feeling today?</h4>
                <div class="mood-options">
                    ${moodTypes.map(mood => `
                        <button class="mood-option" data-mood="${mood.type}" onclick="personalizedFeed.selectMood('${mood.type}')">
                            <span class="mood-emoji">${mood.emoji}</span>
                            <span class="mood-label">${mood.label}</span>
                        </button>
                    `).join('')}
                </div>
                <div class="mood-details" style="display: none;">
                    <div class="form-group">
                        <label>Mood Score (1-10):</label>
                        <input type="range" id="mood-score" min="1" max="10" value="5" class="form-control">
                        <span id="mood-score-display">5</span>
                    </div>
                    <div class="form-group">
                        <label>Activity Level:</label>
                        <select id="activity-level" class="form-control">
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Notes (optional):</label>
                        <textarea id="mood-notes" class="form-control" placeholder="How are you feeling?"></textarea>
                    </div>
                    <button class="btn btn-primary" onclick="personalizedFeed.submitMood()">Submit Mood</button>
                </div>
            </div>
        `;

        moodContainer.innerHTML = moodHTML;

        // Setup mood score slider
        const moodScoreSlider = document.getElementById('mood-score');
        const moodScoreDisplay = document.getElementById('mood-score-display');
        if (moodScoreSlider && moodScoreDisplay) {
            moodScoreSlider.addEventListener('input', (e) => {
                moodScoreDisplay.textContent = e.target.value;
            });
        }
    }

    selectMood(moodType) {
        document.querySelectorAll('.mood-option').forEach(btn => btn.classList.remove('selected'));
        document.querySelector(`[data-mood="${moodType}"]`).classList.add('selected');
        document.querySelector('.mood-details').style.display = 'block';
        this.selectedMood = moodType;
    }

    async submitMood() {
        const moodScore = document.getElementById('mood-score').value;
        const activityLevel = document.getElementById('activity-level').value;
        const notes = document.getElementById('mood-notes').value;

        try {
            const response = await fetch('api/personalized_feed.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=track_mood&mood_type=${this.selectedMood}&mood_score=${moodScore}&activity_level=${activityLevel}&notes=${encodeURIComponent(notes)}`
            });

            const data = await response.json();

            if (data.success) {
                this.showSuccess('Mood tracked successfully!');
                document.querySelector('.mood-details').style.display = 'none';
                document.querySelectorAll('.mood-option').forEach(btn => btn.classList.remove('selected'));
            } else {
                this.showError('Failed to track mood: ' + data.message);
            }
        } catch (error) {
            this.showError('Network error: ' + error.message);
        }
    }

    showUserLevel(level) {
        const levelElement = document.getElementById('user-level-display');
        if (levelElement) {
            levelElement.innerHTML = `
                <div class="user-level-badge level-${level}">
                    <i class="bi bi-${this.getLevelIcon(level)}"></i>
                    ${this.capitalizeFirst(level)} Level
                </div>
            `;
        }
    }

    getLevelIcon(level) {
        const icons = {
            'beginner': 'seedling',
            'intermediate': 'flower1',
            'advanced': 'star'
        };
        return icons[level] || 'person';
    }

    showLoadingState() {
        const feedContainer = document.getElementById('personalized-feed');
        if (feedContainer) {
            feedContainer.innerHTML = `
                <div class="loading-state">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p>Loading personalized content...</p>
                </div>
            `;
        }
    }

    hideLoadingState() {
        // Loading state will be replaced by posts
    }

    showSuccess(message) {
        this.showNotification(message, 'success');
    }

    showError(message) {
        this.showNotification(message, 'error');
    }

    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <div class="notification-content">
                <i class="bi bi-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
                <span>${message}</span>
            </div>
            <button class="notification-close" onclick="this.parentElement.remove()">
                <i class="bi bi-x"></i>
            </button>
        `;

        document.body.appendChild(notification);

        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (notification.parentElement) {
                notification.remove();
            }
        }, 5000);
    }

    setupPostInteractions() {
        // Add any additional interaction setup here
    }

    showAnalytics() {
        // Analytics functionality is handled in the main HTML file
        const analyticsModal = new bootstrap.Modal(document.getElementById('analyticsModal'));
        analyticsModal.show();
    }
}

// Initialize the personalized feed when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.personalizedFeed = new PersonalizedFeed();
});

// Export for global access
window.PersonalizedFeed = PersonalizedFeed; 