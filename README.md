# Book of Deeds - Complete Student Learning Platform

A comprehensive AI-powered educational platform designed to help students track their daily deeds, manage courses, get personalized mentorship, and engage with an intelligent chatbot.

## 🎯 Features

### 📚 Course Management
- Create and manage courses with AI-powered curriculum parsing
- Organize courses into chapters and topics
- Track progress with visual indicators
- Timer functionality for focused study sessions
- Proof file uploads for completed tasks

### 🤖 AI Mentorship System
- Personalized study plan generation
- Q&A assistance for academic questions
- Performance analysis and weakness identification
- Daily learning challenges
- Exam preparation materials
- Motivational messages based on user context

### 💬 AI Chatbot
- Multi-lingual support
- Persona-based responses (mentor, motivator, etc.)
- Mood tracking
- XP reward system
- Assignment management
- Auto-summary tool with:
  - Audio transcription (Whisper API)
  - Text extraction from multiple formats
  - AI-powered summarization
  - Slide deck generation

### 📊 Personalized Feed
- AI-generated educational content
- Motivation and study tips
- Success stories and achievements
- User interaction tracking
- Personalized recommendations

### 👥 User Management
- Secure registration and login
- OTP-based email verification
- Password recovery system
- User profiles with academic information
- Leaderboard system with points and streaks

### 📈 Progress Tracking
- Daily activities/deeds logging
- Performance analytics
- Weakness analysis
- Study streak tracking
- Achievement system

### 🏆 Gamification
- Points system
- XP rewards
- Streaks and achievements
- Leaderboards
- Daily challenges

## 🛠️ Technology Stack

- **Backend**: PHP 8.1+
- **Database**: MySQL 8.0+
- **Frontend**: HTML5, CSS3, Bootstrap 5, JavaScript
- **APIs**: OpenAI (GPT-3.5-turbo, Whisper)
- **Authentication**: Session-based with OTP

## 📋 Prerequisites

- PHP 8.1 or higher
- MySQL 8.0 or higher
- OpenAI API key (for AI features)
- Composer (optional, for dependency management)

## 🚀 Installation

### 1. Clone the Repository
```bash
git clone https://github.com/Afrid25/BOOK-OF-DEEDS-update-.git
cd book-of-deeds
```

### 2. Set Up Database
```bash
# Create database
mysql -u root -p < schema.sql

# Or manually:
mysql -u root -p
CREATE DATABASE book_of_deeds;
USE book_of_deeds;
source schema.sql;
```

### 3. Configure Environment
Create a `.env` file in the root directory:
```env
OPENAI_API_KEY=your_openai_api_key_here
GEMINI_API_KEY=your_gemini_api_key_here
DB_HOST=localhost
DB_USER=root
DB_PASS=your_password
DB_NAME=book_of_deeds
```

### 4. Set Up PHP Server
```bash
# Using PHP built-in server (development only)
php -S localhost:8000

# Or configure Apache/Nginx with proper document root
```

### 5. Access the Application
Open your browser and navigate to:
```
http://localhost:8000
```

## 📁 Project Structure

```
book-of-deeds/
├── api/
│   ├── auth/                 # Authentication endpoints
│   ├── courses/              # Course management
│   ├── deeds/                # Daily activities
│   ├── helpers/              # AI services
│   ├── ai_mentorship/        # Mentorship endpoints
│   ├── personalized_feed.php # Feed API
│   └── ...
├── includes/
│   ├── db_connect.php        # Database connection
│   └── config.php            # Configuration
├── assets/
│   ├── css/                  # Stylesheets
│   ├── js/                   # JavaScript files
│   └── images/               # Images
├── components/               # Reusable components
├── admin/                    # Admin dashboard
├── Ai chatbot.php/           # Chatbot module
│   ├── Aichatbot.php         # Main chatbot
│   └── Autosummarytool/      # Summary tool
├── index.php                 # Login page
├── feed.php                  # Main feed
├── signup.php                # Registration
├── forgot_password.php       # Password recovery
├── reset_password.php        # Password reset
├── schema.sql                # Database schema
└── README.md                 # This file
```

## 🔐 Security Features

- **Password Hashing**: Using PHP's `password_hash()` with bcrypt
- **SQL Injection Prevention**: Prepared statements for all queries
- **Session Management**: Secure session-based authentication
- **Input Validation**: Server-side validation for all inputs
- **CORS Support**: Configurable cross-origin requests

## 📚 API Documentation

### Authentication Endpoints
- `POST /api/auth/login.php` - User login
- `POST /api/auth/signup.php` - User registration
- `POST /api/auth/verify_otp.php` - OTP verification
- `POST /api/auth/forgot_password.php` - Password recovery
- `POST /api/auth/reset_password.php` - Password reset
- `GET /api/auth/logout.php` - Logout

### Course Endpoints
- `POST /api/courses/handler.php?action=add_course` - Create course
- `GET /api/courses/handler.php?action=get_progress_data` - Get progress
- `GET /api/courses/handler.php?action=get_course_details&id=X` - Get course details

### AI Mentorship Endpoints
- `POST /api/ai_mentorship/study_plans.php` - Generate study plan
- `POST /api/ai_mentorship/qa.php` - Ask questions
- `POST /api/ai_mentorship/analysis.php` - Get performance analysis

### Feed Endpoints
- `GET /api/personalized_feed.php?action=get_posts` - Get feed posts
- `POST /api/personalized_feed.php?action=interact` - Record interaction

## 🧪 Testing

### Database Connection Test
```bash
php test_db.php
```

### PHP Syntax Validation
```bash
php -l api/auth/login.php
```

## 🐛 Bug Fixes Implemented

See `BUG_FIXES_AND_FEATURES.md` for a comprehensive list of all bugs fixed and features completed.

## 📝 Configuration

### Database Connection
Edit `includes/db_connect.php`:
```php
$host = 'localhost';
$db   = 'book_of_deeds';
$user = 'root';
$pass = '';
```

### API Keys
Set environment variables or edit `includes/config.php`:
```php
define('OPENAI_API_KEY', 'your_key_here');
define('GEMINI_API_KEY', 'your_key_here');
```

## 🚀 Deployment

### Production Checklist
- [ ] Set strong database passwords
- [ ] Configure SSL/HTTPS
- [ ] Set up proper error logging
- [ ] Configure email service for OTP
- [ ] Set up backup system
- [ ] Configure rate limiting
- [ ] Enable security headers
- [ ] Set up monitoring and alerts

### Environment Variables
```bash
export OPENAI_API_KEY="sk-..."
export DB_HOST="your_db_host"
export DB_USER="db_user"
export DB_PASS="db_password"
```

## 📞 Support

For issues and feature requests, please visit:
- GitHub Issues: https://github.com/Afrid25/BOOK-OF-DEEDS-update-/issues
- Documentation: See `BUG_FIXES_AND_FEATURES.md`

## 📄 License

This project is part of the Book of Deeds initiative for student learning and development.

## 🤝 Contributing

Contributions are welcome! Please follow these steps:
1. Fork the repository
2. Create a feature branch
3. Commit your changes
4. Push to the branch
5. Create a Pull Request

## 🎓 Educational Purpose

This platform is designed to support student learning through:
- Personalized mentorship
- Progress tracking
- Peer motivation
- Daily habit building
- Academic excellence

---

**Last Updated**: May 19, 2026
**Version**: 1.0.0 (Complete & Production Ready)
