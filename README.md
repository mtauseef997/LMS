# EduLearn - Learning Management System

A comprehensive Learning Management System built with PHP and MySQL, designed for educational institutions to manage students, teachers, classes, subjects, quizzes, and assignments.

## Features

### Admin Features
- User management (Create, edit, delete users)
- Class and subject management
- Teacher-subject-class assignment
- Student enrollment management
- System overview and statistics

### Teacher Features
- Dashboard with assigned classes and subjects
- Quiz creation and management
- Assignment creation and management
- Student progress tracking
- Grade management

### Student Features
- Personal dashboard
- View enrolled classes and subjects
- Take quizzes
- Submit assignments
- View grades and progress

## Technology Stack

- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Frontend**: HTML5, CSS3, JavaScript
- **Server**: Apache (XAMPP recommended for development)

## Database Schema

The system uses the following main tables:
- `users` - Store user information (admin, teacher, student)
- `classes` - Class information
- `subjects` - Subject information
- `teacher_subject_class` - Teacher assignments
- `student_class` - Student enrollments
- `quizzes` & `quiz_questions` - Quiz management
- `assignments` - Assignment management
- `quiz_submissions` & `assignment_submissions` - Submission tracking

## Installation

1. Clone this repository to your web server directory
2. Import the database schema from `database/schema.sql`
3. Configure database connection in `config/db.php`
4. Ensure proper file permissions for uploads directory
5. Access the system through your web browser

## Default Login Credentials

After setting up the database, you can create admin users through the registration system or directly in the database.

## File Structure

```
LMS/
├── admin/          # Admin panel files
├── teacher/        # Teacher dashboard files
├── student/        # Student dashboard files
├── config/         # Configuration files
├── database/       # Database schema
├── assets/         # CSS, JS, and upload files
├── includes/       # Common includes (header, footer, auth)
└── index.php       # Landing page
```

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## License

This project is open source and available under the [MIT License](LICENSE).

## Support

For support or questions, please open an issue in the GitHub repository.
