# EduLearn LMS Installation Guide

## Prerequisites

- **Web Server**: Apache/Nginx with PHP support
- **PHP**: Version 7.4 or higher
- **Database**: MySQL 5.7 or higher
- **Extensions**: mysqli, json, session, filter

## Installation Steps

### 1. Download and Setup Files

1. Clone or download the LMS files to your web server directory
2. Ensure the web server has read/write permissions for the `assets/uploads/` directory

### 2. Database Setup

1. Create a new MySQL database named `lms`
2. Import the database schema:
   ```sql
   mysql -u root -p lms < database/schema.sql
   ```

### 3. Configure Database Connection

1. Open `config/db.php`
2. Update the database credentials:
   ```php
   $host = 'localhost';     // Your database host
   $dbname = 'lms';         // Your database name
   $user = 'root';          // Your database username
   $pass = '';              // Your database password
   ```

### 4. Set Directory Permissions

Ensure the following directories are writable by the web server:
```bash
chmod 755 assets/uploads/
chmod 755 assets/uploads/assignments/
```

### 5. Test the Installation

1. Navigate to `your-domain.com/LMS/test_system.php`
2. Check that all tests pass (green checkmarks)
3. Fix any issues shown in red

### 6. Create Initial Users

1. Go to `your-domain.com/LMS/register.php`
2. Register an admin user first
3. Register some teacher and student accounts for testing

### 7. Setup System Data

As an admin user:
1. Login and go to the admin dashboard
2. Create some classes (e.g., "Grade 10A", "Grade 11B")
3. Create some subjects (e.g., "Mathematics", "English", "Science")
4. Assign teachers to subject-class combinations
5. Enroll students in classes

## Default Login Flow

1. **Registration**: Users register through `register.php`
2. **Login**: Users login through `login.php`
3. **Redirection**: Users are redirected based on their role:
   - Admin → `admin/dashboard.php`
   - Teacher → `teacher/dashboard.php`
   - Student → `students/dashboard.php`

## File Structure

```
LMS/
├── admin/              # Admin management interface
├── teacher/            # Teacher dashboard and tools
├── students/           # Student learning interface
├── config/             # Database configuration
├── database/           # Database schema
├── assets/             # CSS, JS, and upload files
│   ├── css/           # Stylesheets
│   └── uploads/       # File upload directory
├── index.php          # Landing page
├── login.php          # Login page
├── register.php       # Registration page
├── logout.php         # Logout handler
└── test_system.php    # System test utility
```

## Troubleshooting

### Common Issues

1. **Database Connection Failed**
   - Check database credentials in `config/db.php`
   - Ensure MySQL service is running
   - Verify database exists

2. **File Upload Errors**
   - Check directory permissions for `assets/uploads/`
   - Verify PHP upload settings in `php.ini`

3. **Session Issues**
   - Ensure PHP sessions are enabled
   - Check session directory permissions

4. **Redirect Loops**
   - Clear browser cache and cookies
   - Check file permissions

### Testing the System

1. **Admin Functions**:
   - Create users, classes, subjects
   - Assign teachers and enroll students

2. **Teacher Functions**:
   - Create quizzes and assignments
   - Add questions and grade submissions

3. **Student Functions**:
   - Take quizzes and submit assignments
   - View grades and progress

## Security Considerations

1. **Production Setup**:
   - Change default database credentials
   - Use HTTPS for all connections
   - Set proper file permissions
   - Remove `test_system.php` in production

2. **File Uploads**:
   - Validate file types and sizes
   - Store uploads outside web root if possible
   - Scan uploaded files for malware

3. **Database Security**:
   - Use strong passwords
   - Limit database user permissions
   - Regular backups

## Support

If you encounter issues:
1. Check the `test_system.php` output
2. Review web server error logs
3. Verify all file permissions
4. Ensure all required PHP extensions are installed

For additional help, refer to the README.md file or check the system requirements.
