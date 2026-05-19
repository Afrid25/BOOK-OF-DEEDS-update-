-- Book of Deeds Database Schema

CREATE DATABASE IF NOT EXISTS book_of_deeds;
USE book_of_deeds;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    profile_picture_path VARCHAR(255),
    gender VARCHAR(10),
    blood_group VARCHAR(10),
    institution VARCHAR(100),
    department VARCHAR(100),
    bio TEXT,
    student_id VARCHAR(50),
    academic_session VARCHAR(50),
    year VARCHAR(10),
    points INT DEFAULT 0,
    xp INT DEFAULT 0,
    current_streak INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Pending users for OTP verification
CREATE TABLE IF NOT EXISTS pending_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    gender VARCHAR(10),
    blood_group VARCHAR(10),
    institution VARCHAR(100),
    department VARCHAR(100),
    profile_picture_path VARCHAR(255),
    otp_code VARCHAR(10) NOT NULL,
    otp_expires_at DATETIME NOT NULL,
    bio TEXT,
    student_id VARCHAR(50),
    academic_session VARCHAR(50),
    year VARCHAR(10),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Password reset OTPs
CREATE TABLE IF NOT EXISTS password_reset_otps (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    otp_code VARCHAR(10) NOT NULL,
    otp_expires_at DATETIME NOT NULL,
    used TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Daily Activities / Deeds
CREATE TABLE IF NOT EXISTS daily_activities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    activity_type VARCHAR(100) NOT NULL,
    details TEXT,
    date DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Courses
CREATE TABLE IF NOT EXISTS courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    course_name VARCHAR(255) NOT NULL,
    syllabus TEXT,
    is_deleted TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Course Chapters
CREATE TABLE IF NOT EXISTS course_chapters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    chapter_name VARCHAR(255) NOT NULL,
    chapter_order INT DEFAULT 0,
    estimated_time VARCHAR(100),
    is_completed TINYINT(1) DEFAULT 0,
    completed_at DATETIME,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
);

-- Course Topics
CREATE TABLE IF NOT EXISTS course_topics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    chapter_id INT NOT NULL,
    topic_name VARCHAR(255) NOT NULL,
    topic_order INT DEFAULT 0,
    is_completed TINYINT(1) DEFAULT 0,
    completed_at DATETIME,
    proof_file_path VARCHAR(255),
    timer_seconds INT DEFAULT 0,
    FOREIGN KEY (chapter_id) REFERENCES course_chapters(id) ON DELETE CASCADE
);

-- Challenges
CREATE TABLE IF NOT EXISTS challenges (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    points_reward INT DEFAULT 0,
    start_date DATETIME,
    end_date DATETIME,
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Challenge Submissions
CREATE TABLE IF NOT EXISTS challenge_submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    challenge_id INT NOT NULL,
    user_id INT NOT NULL,
    submission_link VARCHAR(255),
    submission_text TEXT,
    is_winner TINYINT(1) DEFAULT 0,
    submitted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (challenge_id) REFERENCES challenges(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Resources
CREATE TABLE IF NOT EXISTS resources (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category VARCHAR(100),
    title VARCHAR(255) NOT NULL,
    description TEXT,
    link VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Feedback
CREATE TABLE IF NOT EXISTS feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    subject VARCHAR(255),
    message TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- AI Study Plans
CREATE TABLE IF NOT EXISTS ai_study_plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    plan_type VARCHAR(50) DEFAULT 'daily',
    title VARCHAR(255),
    description TEXT,
    start_date DATE,
    end_date DATE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- AI Study Tasks
CREATE TABLE IF NOT EXISTS ai_study_tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    plan_id INT NOT NULL,
    user_id INT NOT NULL,
    task_type VARCHAR(50),
    title VARCHAR(255),
    description TEXT,
    subject VARCHAR(100),
    topic VARCHAR(100),
    estimated_duration INT,
    difficulty_level VARCHAR(50),
    due_date DATETIME,
    completed TINYINT(1) DEFAULT 0,
    completed_at DATETIME,
    score INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (plan_id) REFERENCES ai_study_plans(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- AI Weakness Analysis
CREATE TABLE IF NOT EXISTS ai_weakness_analysis (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    subject VARCHAR(100),
    topic VARCHAR(100),
    weakness_type VARCHAR(100),
    severity VARCHAR(50),
    suggested_actions TEXT,
    last_analyzed DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- AI Q&A Interactions
CREATE TABLE IF NOT EXISTS ai_qa_interactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    question TEXT,
    answer TEXT,
    subject VARCHAR(100),
    topic VARCHAR(100),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Chat Logs
CREATE TABLE IF NOT EXISTS chat_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(50) NOT NULL,
    role VARCHAR(20) NOT NULL,
    message TEXT,
    persona VARCHAR(50),
    lang VARCHAR(10),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Moods
CREATE TABLE IF NOT EXISTS moods (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(50) NOT NULL,
    mood VARCHAR(50),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Assignments (from Chatbot)
CREATE TABLE IF NOT EXISTS assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(50) NOT NULL,
    title VARCHAR(255),
    due_date DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Notifications
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    message TEXT,
    is_read TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Personalized Posts
CREATE TABLE IF NOT EXISTS personalized_posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    post_type VARCHAR(50) NOT NULL,
    category VARCHAR(100),
    target_audience JSON,
    difficulty_level VARCHAR(50),
    is_ai_generated TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    view_count INT DEFAULT 0,
    like_count INT DEFAULT 0,
    share_count INT DEFAULT 0,
    engagement_score DECIMAL(10, 2) DEFAULT 0.00,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- User Post Interactions
CREATE TABLE IF NOT EXISTS user_post_interactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    post_id INT NOT NULL,
    interaction_type VARCHAR(50) NOT NULL,
    interaction_value INT DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY (user_id, post_id, interaction_type),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (post_id) REFERENCES personalized_posts(id) ON DELETE CASCADE
);

-- User Interests
CREATE TABLE IF NOT EXISTS user_interests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    interest_category VARCHAR(100) NOT NULL,
    interest_level DECIMAL(5, 2) DEFAULT 0.00,
    last_updated DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY (user_id, interest_category),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Personalized Notifications
CREATE TABLE IF NOT EXISTS personalized_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    notification_type VARCHAR(50),
    title VARCHAR(255),
    message TEXT,
    is_read TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- AI Mentorship Profiles
CREATE TABLE IF NOT EXISTS ai_mentorship_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    learning_style VARCHAR(50),
    preferred_subjects TEXT,
    learning_pace VARCHAR(50),
    study_hours_per_day INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY (user_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- AI Performance Tracking
CREATE TABLE IF NOT EXISTS ai_performance_tracking (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    subject VARCHAR(100),
    topic VARCHAR(100),
    score INT,
    max_score INT,
    completed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- AI Motivational Messages
CREATE TABLE IF NOT EXISTS ai_motivational_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    message_type VARCHAR(50),
    message_text TEXT,
    context JSON,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- AI Daily Challenges
CREATE TABLE IF NOT EXISTS ai_daily_challenges (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    challenge_date DATE,
    challenge_type VARCHAR(50),
    title VARCHAR(255),
    description TEXT,
    subject VARCHAR(100),
    difficulty_level VARCHAR(50),
    completed TINYINT(1) DEFAULT 0,
    completed_at DATETIME,
    score INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- AI Exam Prep
CREATE TABLE IF NOT EXISTS ai_exam_prep (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    exam_title VARCHAR(255),
    subject VARCHAR(100),
    exam_date DATETIME,
    study_notes TEXT,
    predicted_questions JSON,
    study_schedule JSON,
    key_topics JSON,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
