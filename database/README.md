# LMS Database Schema v2.0

This directory contains the complete database schema for the Learning Management System (LMS).

## 📁 Files

- **`schema.sql`** - Complete database schema with all tables, indexes, views, and triggers
- **`install.php`** - Web-based installation script
- **`README.md`** - This documentation file

## 🚀 Quick Installation

### Option 1: Web Installation (Recommended)
1. Visit: `your-domain.com/LMS/database/install.php`
2. The script will automatically create the database and all tables
3. Check for success messages

### Option 2: Manual Installation
1. Open phpMyAdmin
2. Create a new database named `lms`
3. Import the `schema.sql` file
4. Verify all tables are created

## 📊 Database Structure

### Core Tables

#### **Users Management**
- `users` - All system users (admin, teachers, students)
- `classes` - Class/grade definitions
- `subjects` - Subject definitions
- `teacher_subject_class` - Teacher assignments to subjects and classes
- `student_class` - Student enrollments in classes

#### **Quiz System**
- `quizzes` - Quiz definitions
- `quiz_questions` - Questions for each quiz
- `quiz_submissions` - Student quiz submissions and scores

#### **Assignment System**
- `assignments` - Assignment definitions
- `assignment_submissions` - Student assignment submissions

#### **Additional Features**
- `attendance` - Student attendance tracking
- `grades` - Grade management
- `announcements` - System announcements

### Key Features

#### **🔒 Data Integrity**
- Foreign key constraints ensure referential integrity
- Unique constraints prevent duplicate enrollments
- Proper indexes for performance

#### **📈 Performance Optimized**
- Strategic indexes on frequently queried columns
- Composite indexes for complex queries
- Views for common dashboard queries

#### **🔄 Automatic Updates**
- Triggers automatically update quiz total marks
- Timestamp tracking for all records
- Calculated fields for percentages

#### **🎯 Flexible Design**
- Support for multiple question types
- File upload capabilities for assignments
- Attendance tracking with multiple statuses
- Comprehensive grading system

## 🔑 Default Credentials

After installation, use these credentials to login:

- **Email:** admin@lms.com
- **Password:** password

**⚠️ Important: Change the default password immediately after first login!**

## 📋 Sample Data Included

The schema includes sample data for:
- 1 Admin user
- 4 Sample classes (10A, 10B, 11A, 12A)
- 7 Sample subjects (Math, Physics, Chemistry, Biology, English, History, Computer Science)

## 🔧 Configuration

After installation, update your database configuration in:
```php
// config/db.php
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'lms';
```

## 📊 Database Views

### Student Dashboard View
Provides aggregated data for student dashboards:
- Total quizzes and completed count
- Total assignments and submitted count
- Average quiz and assignment scores

### Teacher Dashboard View
Provides aggregated data for teacher dashboards:
- Total students in classes
- Quiz and assignment counts
- Submission statistics

## 🔄 Triggers

### Quiz Total Marks Auto-Update
Automatically updates the `total_marks` field in the `quizzes` table when:
- Questions are added to a quiz
- Question marks are updated
- Questions are deleted from a quiz

## 🎯 Usage Examples

### Creating a Quiz
```sql
-- 1. Insert quiz
INSERT INTO quizzes (title, description, subject_id, class_id, teacher_id, time_limit) 
VALUES ('Math Quiz 1', 'Basic algebra quiz', 1, 1, 2, 30);

-- 2. Add questions (total_marks will auto-update)
INSERT INTO quiz_questions (quiz_id, question_text, correct_answer, marks) 
VALUES (1, 'What is 2+2?', '4', 5);
```

### Submitting a Quiz
```sql
INSERT INTO quiz_submissions (quiz_id, student_id, answers, score, percentage) 
VALUES (1, 3, '{"1": "4"}', 5, 100.00);
```

### Creating an Assignment
```sql
INSERT INTO assignments (title, description, subject_id, class_id, teacher_id, due_date, max_marks) 
VALUES ('Essay Assignment', 'Write a 500-word essay', 5, 1, 2, '2024-12-31 23:59:59', 100);
```

## 🛠️ Maintenance

### Regular Tasks
1. **Backup Database** - Regular backups recommended
2. **Monitor Performance** - Check slow query log
3. **Update Statistics** - Run `ANALYZE TABLE` periodically
4. **Clean Old Data** - Archive old submissions if needed

### Troubleshooting
- Check MySQL error log for issues
- Verify foreign key constraints
- Ensure proper permissions on database
- Monitor disk space for file uploads

## 🔄 Version History

### v2.0 (Current)
- Complete rewrite with improved structure
- Added attendance tracking
- Enhanced grading system
- Performance optimizations
- Comprehensive views and triggers

### v1.0 (Previous)
- Basic quiz and assignment functionality
- Simple user management
- Limited reporting capabilities

## 📞 Support

For issues with the database schema:
1. Check the installation logs
2. Verify MySQL version compatibility (5.7+ recommended)
3. Ensure proper permissions
4. Review error messages in detail

## 🔐 Security Notes

- All passwords are hashed using PHP's `password_hash()`
- Foreign key constraints prevent orphaned records
- Input validation should be implemented in application layer
- Regular security updates recommended

---

**Ready to use! The schema is designed to be production-ready with proper indexing, constraints, and performance optimizations.**
