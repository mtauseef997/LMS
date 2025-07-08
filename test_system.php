<?php
// System Test File - Check all components
require_once 'config/db.php';

echo "<h1>LMS System Test</h1>";

// Test 1: Database Connection
echo "<h2>1. Database Connection Test</h2>";
if ($conn->connect_error) {
    echo "<p style='color: red;'>❌ Database connection failed: " . $conn->connect_error . "</p>";
} else {
    echo "<p style='color: green;'>✅ Database connection successful</p>";
}

// Test 2: Check if all required tables exist
echo "<h2>2. Database Tables Test</h2>";
$required_tables = [
    'users', 'classes', 'subjects', 'teacher_subject_class', 
    'student_class', 'quizzes', 'quiz_questions', 'quiz_submissions',
    'assignments', 'assignment_submissions'
];

foreach ($required_tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows > 0) {
        echo "<p style='color: green;'>✅ Table '$table' exists</p>";
    } else {
        echo "<p style='color: red;'>❌ Table '$table' missing</p>";
    }
}

// Test 3: Check if key files exist
echo "<h2>3. File Structure Test</h2>";
$required_files = [
    'index.php',
    'login.php',
    'register.php',
    'logout.php',
    'admin/dashboard.php',
    'admin/manage_user.php',
    'admin/manage_class.php',
    'admin/manage_subject.php',
    'admin/assign_teacher.php',
    'admin/enroll_student.php',
    'teacher/dashboard.php',
    'teacher/manage_quiz.php',
    'teacher/quiz_questions.php',
    'teacher/quiz_results.php',
    'teacher/manage_assignment.php',
    'teacher/assignment_submissions.php',
    'students/dashboard.php',
    'students/quizzes.php',
    'students/take_quiz.php',
    'students/view_result.php',
    'students/assignments.php',
    'students/submit_assignment.php',
    'students/view_assignment.php',
    'students/grades.php',
    'students/classes.php',
    'config/db.php',
    'assets/css/dashboard.css',
    'assets/css/index.css',
    'assets/css/register.css',
    'database/schema.sql'
];

foreach ($required_files as $file) {
    if (file_exists($file)) {
        echo "<p style='color: green;'>✅ File '$file' exists</p>";
    } else {
        echo "<p style='color: red;'>❌ File '$file' missing</p>";
    }
}

// Test 4: Check directory permissions
echo "<h2>4. Directory Permissions Test</h2>";
$upload_dir = 'assets/uploads/assignments/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
    echo "<p style='color: blue;'>📁 Created upload directory: $upload_dir</p>";
}

if (is_writable($upload_dir)) {
    echo "<p style='color: green;'>✅ Upload directory is writable</p>";
} else {
    echo "<p style='color: red;'>❌ Upload directory is not writable</p>";
}

// Test 5: Check for sample data
echo "<h2>5. Sample Data Test</h2>";
$user_count = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$class_count = $conn->query("SELECT COUNT(*) as count FROM classes")->fetch_assoc()['count'];
$subject_count = $conn->query("SELECT COUNT(*) as count FROM subjects")->fetch_assoc()['count'];

echo "<p>Users in database: $user_count</p>";
echo "<p>Classes in database: $class_count</p>";
echo "<p>Subjects in database: $subject_count</p>";

if ($user_count == 0) {
    echo "<p style='color: orange;'>⚠️ No users found. You may need to register some users.</p>";
}

// Test 6: PHP Extensions
echo "<h2>6. PHP Extensions Test</h2>";
$required_extensions = ['mysqli', 'json', 'session', 'filter'];

foreach ($required_extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "<p style='color: green;'>✅ PHP extension '$ext' loaded</p>";
    } else {
        echo "<p style='color: red;'>❌ PHP extension '$ext' not loaded</p>";
    }
}

// Test 7: Session functionality
echo "<h2>7. Session Test</h2>";
if (session_status() === PHP_SESSION_ACTIVE || session_start()) {
    echo "<p style='color: green;'>✅ Sessions working</p>";
    $_SESSION['test'] = 'working';
    if (isset($_SESSION['test'])) {
        echo "<p style='color: green;'>✅ Session data storage working</p>";
        unset($_SESSION['test']);
    }
} else {
    echo "<p style='color: red;'>❌ Sessions not working</p>";
}

// Test 8: Database Schema Validation
echo "<h2>8. Database Schema Validation</h2>";

// Check users table structure
$result = $conn->query("DESCRIBE users");
$user_columns = [];
while ($row = $result->fetch_assoc()) {
    $user_columns[] = $row['Field'];
}

$required_user_columns = ['id', 'name', 'email', 'password', 'role', 'created_at'];
foreach ($required_user_columns as $col) {
    if (in_array($col, $user_columns)) {
        echo "<p style='color: green;'>✅ Users table has '$col' column</p>";
    } else {
        echo "<p style='color: red;'>❌ Users table missing '$col' column</p>";
    }
}

// Check quiz_questions table structure
$result = $conn->query("DESCRIBE quiz_questions");
if ($result) {
    $quiz_columns = [];
    while ($row = $result->fetch_assoc()) {
        $quiz_columns[] = $row['Field'];
    }
    
    $required_quiz_columns = ['id', 'quiz_id', 'question_text', 'question_type', 'correct_answer', 'marks'];
    foreach ($required_quiz_columns as $col) {
        if (in_array($col, $quiz_columns)) {
            echo "<p style='color: green;'>✅ Quiz_questions table has '$col' column</p>";
        } else {
            echo "<p style='color: red;'>❌ Quiz_questions table missing '$col' column</p>";
        }
    }
}

echo "<h2>Test Complete</h2>";
echo "<p>If you see any red ❌ marks above, those issues need to be resolved for the system to work properly.</p>";
echo "<p><strong>Next Steps:</strong></p>";
echo "<ul>";
echo "<li>1. Import the database schema from database/schema.sql</li>";
echo "<li>2. Register some test users through register.php</li>";
echo "<li>3. Create some classes and subjects as admin</li>";
echo "<li>4. Assign teachers and enroll students</li>";
echo "<li>5. Test the quiz and assignment functionality</li>";
echo "</ul>";
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h1 { color: #333; }
h2 { color: #666; border-bottom: 1px solid #ddd; padding-bottom: 5px; }
p { margin: 5px 0; }
ul { margin: 10px 0; }
li { margin: 5px 0; }
</style>
