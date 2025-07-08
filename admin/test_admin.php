<?php
session_start();
require_once '../config/db.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

echo "<h1>Admin System Test</h1>";
echo "<p>Testing admin functionality...</p>";

// Test 1: Check admin session
echo "<h2>1. Admin Session Test</h2>";
if (isset($_SESSION['user_id']) && $_SESSION['user_role'] === 'admin') {
    echo "<p style='color: green;'>✅ Admin session active</p>";
    echo "<p>User ID: " . $_SESSION['user_id'] . "</p>";
    echo "<p>User Name: " . ($_SESSION['user_name'] ?? 'Not set') . "</p>";
    echo "<p>User Role: " . $_SESSION['user_role'] . "</p>";
} else {
    echo "<p style='color: red;'>❌ Admin session not found</p>";
}

// Test 2: Database connection
echo "<h2>2. Database Connection Test</h2>";
if ($conn->connect_error) {
    echo "<p style='color: red;'>❌ Database connection failed: " . $conn->connect_error . "</p>";
} else {
    echo "<p style='color: green;'>✅ Database connection successful</p>";
}

// Test 3: Check admin pages accessibility
echo "<h2>3. Admin Pages Test</h2>";
$admin_pages = [
    'dashboard.php' => 'Admin Dashboard',
    'manage_user.php' => 'User Management',
    'manage_class.php' => 'Class Management',
    'manage_subject.php' => 'Subject Management',
    'assign_teacher.php' => 'Teacher Assignment',
    'enroll_student.php' => 'Student Enrollment'
];

foreach ($admin_pages as $page => $title) {
    if (file_exists($page)) {
        echo "<p style='color: green;'>✅ $title ($page) exists</p>";
    } else {
        echo "<p style='color: red;'>❌ $title ($page) missing</p>";
    }
}

// Test 4: Database tables and data
echo "<h2>4. Database Data Test</h2>";

// Check users table
$result = $conn->query("SELECT COUNT(*) as count FROM users");
$user_count = $result->fetch_assoc()['count'];
echo "<p>Total users: $user_count</p>";

$result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'admin'");
$admin_count = $result->fetch_assoc()['count'];
echo "<p>Admin users: $admin_count</p>";

$result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'teacher'");
$teacher_count = $result->fetch_assoc()['count'];
echo "<p>Teacher users: $teacher_count</p>";

$result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'student'");
$student_count = $result->fetch_assoc()['count'];
echo "<p>Student users: $student_count</p>";

// Check classes and subjects
$result = $conn->query("SELECT COUNT(*) as count FROM classes");
$class_count = $result->fetch_assoc()['count'];
echo "<p>Total classes: $class_count</p>";

$result = $conn->query("SELECT COUNT(*) as count FROM subjects");
$subject_count = $result->fetch_assoc()['count'];
echo "<p>Total subjects: $subject_count</p>";

// Check assignments
$result = $conn->query("SELECT COUNT(*) as count FROM teacher_subject_class");
$assignment_count = $result->fetch_assoc()['count'];
echo "<p>Teacher assignments: $assignment_count</p>";

$result = $conn->query("SELECT COUNT(*) as count FROM student_class");
$enrollment_count = $result->fetch_assoc()['count'];
echo "<p>Student enrollments: $enrollment_count</p>";

// Test 5: AJAX functionality test
echo "<h2>5. AJAX Functionality Test</h2>";
echo "<p>Testing AJAX endpoints...</p>";

// Test user creation endpoint
echo "<div id='ajax-test'>";
echo "<button onclick='testUserCreation()'>Test User Creation AJAX</button>";
echo "<button onclick='testClassCreation()'>Test Class Creation AJAX</button>";
echo "<button onclick='testSubjectCreation()'>Test Subject Creation AJAX</button>";
echo "<div id='ajax-results'></div>";
echo "</div>";

// Test 6: Navigation links
echo "<h2>6. Navigation Test</h2>";
echo "<p>Testing navigation links...</p>";
echo "<ul>";
foreach ($admin_pages as $page => $title) {
    echo "<li><a href='$page' target='_blank'>$title</a></li>";
}
echo "</ul>";

// Test 7: Sample data suggestions
echo "<h2>7. Sample Data Suggestions</h2>";
if ($user_count < 5) {
    echo "<p style='color: orange;'>⚠️ Consider adding more test users</p>";
}
if ($class_count < 3) {
    echo "<p style='color: orange;'>⚠️ Consider adding more test classes</p>";
}
if ($subject_count < 3) {
    echo "<p style='color: orange;'>⚠️ Consider adding more test subjects</p>";
}
if ($assignment_count < 1) {
    echo "<p style='color: orange;'>⚠️ Consider assigning teachers to subjects and classes</p>";
}
if ($enrollment_count < 1) {
    echo "<p style='color: orange;'>⚠️ Consider enrolling students in classes</p>";
}

echo "<h2>Test Complete</h2>";
echo "<p><a href='dashboard.php'>← Back to Admin Dashboard</a></p>";
?>

<script>
function testUserCreation() {
    const testData = new FormData();
    testData.append('action', 'create');
    testData.append('name', 'Test User ' + Date.now());
    testData.append('email', 'test' + Date.now() + '@example.com');
    testData.append('password', 'password123');
    testData.append('role', 'student');
    
    fetch('manage_user.php', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: testData
    })
    .then(response => response.json())
    .then(data => {
        const results = document.getElementById('ajax-results');
        if (data.success) {
            results.innerHTML += '<p style="color: green;">✅ User creation AJAX working: ' + data.message + '</p>';
        } else {
            results.innerHTML += '<p style="color: red;">❌ User creation AJAX failed: ' + data.message + '</p>';
        }
    })
    .catch(error => {
        const results = document.getElementById('ajax-results');
        results.innerHTML += '<p style="color: red;">❌ User creation AJAX error: ' + error + '</p>';
    });
}

function testClassCreation() {
    const testData = new FormData();
    testData.append('action', 'create');
    testData.append('name', 'Test Class ' + Date.now());
    testData.append('description', 'Test class description');
    
    fetch('manage_class.php', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: testData
    })
    .then(response => response.json())
    .then(data => {
        const results = document.getElementById('ajax-results');
        if (data.success) {
            results.innerHTML += '<p style="color: green;">✅ Class creation AJAX working: ' + data.message + '</p>';
        } else {
            results.innerHTML += '<p style="color: red;">❌ Class creation AJAX failed: ' + data.message + '</p>';
        }
    })
    .catch(error => {
        const results = document.getElementById('ajax-results');
        results.innerHTML += '<p style="color: red;">❌ Class creation AJAX error: ' + error + '</p>';
    });
}

function testSubjectCreation() {
    const testData = new FormData();
    testData.append('action', 'create');
    testData.append('name', 'Test Subject ' + Date.now());
    testData.append('description', 'Test subject description');
    
    fetch('manage_subject.php', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: testData
    })
    .then(response => response.json())
    .then(data => {
        const results = document.getElementById('ajax-results');
        if (data.success) {
            results.innerHTML += '<p style="color: green;">✅ Subject creation AJAX working: ' + data.message + '</p>';
        } else {
            results.innerHTML += '<p style="color: red;">❌ Subject creation AJAX failed: ' + data.message + '</p>';
        }
    })
    .catch(error => {
        const results = document.getElementById('ajax-results');
        results.innerHTML += '<p style="color: red;">❌ Subject creation AJAX error: ' + error + '</p>';
    });
}
</script>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h1 { color: #333; }
h2 { color: #666; border-bottom: 1px solid #ddd; padding-bottom: 5px; }
p { margin: 5px 0; }
button { margin: 5px; padding: 10px; background: #007cba; color: white; border: none; border-radius: 4px; cursor: pointer; }
button:hover { background: #005a87; }
ul { margin: 10px 0; }
li { margin: 5px 0; }
a { color: #007cba; text-decoration: none; }
a:hover { text-decoration: underline; }
#ajax-results { margin-top: 10px; padding: 10px; background: #f5f5f5; border-radius: 4px; }
</style>
