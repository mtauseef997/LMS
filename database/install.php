<?php


echo "<h1>🚀 LMS Database Installation</h1>";

$host = 'localhost';
$username = 'root';
$password = '';
$database = 'lms';

try {

    $conn = new mysqli($host, $username, $password);

    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    echo "<p style='color: green;'>✅ Connected to MySQL server</p>";


    $schema_file = __DIR__ . '/schema.sql';
    if (!file_exists($schema_file)) {
        throw new Exception("Schema file not found: $schema_file");
    }

    $schema_sql = file_get_contents($schema_file);
    echo "<p style='color: green;'>✅ Schema file loaded</p>";


    if ($conn->multi_query($schema_sql)) {
        echo "<p style='color: green;'>✅ Database schema executed successfully</p>";


        do {
            if ($result = $conn->store_result()) {
                $result->free();
            }
        } while ($conn->next_result());
    } else {
        throw new Exception("Error executing schema: " . $conn->error);
    }


    $conn->select_db($database);

    $tables = [
        'users',
        'classes',
        'subjects',
        'teacher_subject_class',
        'student_class',
        'quizzes',
        'quiz_questions',
        'quiz_submissions',
        'assignments',
        'assignment_submissions',
        'attendance',
        'grades',
        'announcements'
    ];

    echo "<h2>📋 Verifying Installation</h2>";

    $all_good = true;
    foreach ($tables as $table) {
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        if ($result && $result->num_rows > 0) {
            echo "<p style='color: green;'>✅ Table '$table' created successfully</p>";
        } else {
            echo "<p style='color: red;'>❌ Table '$table' not found</p>";
            $all_good = false;
        }
    }


    $admin_check = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'admin'");
    if ($admin_check) {
        $admin_count = $admin_check->fetch_assoc()['count'];
        if ($admin_count > 0) {
            echo "<p style='color: green;'>✅ Admin user created ($admin_count admin users)</p>";
        } else {
            echo "<p style='color: orange;'>⚠️ No admin users found</p>";
        }
    }

    $classes_check = $conn->query("SELECT COUNT(*) as count FROM classes");
    if ($classes_check) {
        $classes_count = $classes_check->fetch_assoc()['count'];
        echo "<p style='color: green;'>✅ Sample classes created ($classes_count classes)</p>";
    }

    $subjects_check = $conn->query("SELECT COUNT(*) as count FROM subjects");
    if ($subjects_check) {
        $subjects_count = $subjects_check->fetch_assoc()['count'];
        echo "<p style='color: green;'>✅ Sample subjects created ($subjects_count subjects)</p>";
    }

    // Check views
    $views = ['student_dashboard', 'teacher_dashboard'];
    foreach ($views as $view) {
        $result = $conn->query("SHOW TABLES LIKE '$view'");
        if ($result && $result->num_rows > 0) {
            echo "<p style='color: green;'>✅ View '$view' created successfully</p>";
        } else {
            echo "<p style='color: orange;'>⚠️ View '$view' not found</p>";
        }
    }

    if ($all_good) {
        echo "<h2 style='color: green;'>🎉 Installation Completed Successfully!</h2>";
        echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 1rem; border-radius: 5px; margin: 1rem 0;'>";
        echo "<h3>✅ What was installed:</h3>";
        echo "<ul>";
        echo "<li><strong>Database:</strong> lms</li>";
        echo "<li><strong>Tables:</strong> " . count($tables) . " core tables</li>";
        echo "<li><strong>Sample Data:</strong> Admin user, classes, subjects</li>";
        echo "<li><strong>Views:</strong> Dashboard views for students and teachers</li>";
        echo "<li><strong>Triggers:</strong> Automatic quiz total marks calculation</li>";
        echo "<li><strong>Indexes:</strong> Performance optimization indexes</li>";
        echo "</ul>";
        echo "</div>";

        echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 1rem; border-radius: 5px; margin: 1rem 0;'>";
        echo "<h3>🔑 Default Admin Login:</h3>";
        echo "<p><strong>Email:</strong> admin@lms.com</p>";
        echo "<p><strong>Password:</strong> password</p>";
        echo "<p><em>Please change this password after first login!</em></p>";
        echo "</div>";

        echo "<div style='background: #cce5ff; border: 1px solid #99d6ff; padding: 1rem; border-radius: 5px; margin: 1rem 0;'>";
        echo "<h3>🚀 Next Steps:</h3>";
        echo "<ol>";
        echo "<li>Update your database configuration in <code>config/db.php</code></li>";
        echo "<li>Login with the admin credentials above</li>";
        echo "<li>Create teachers and students</li>";
        echo "<li>Assign teachers to subjects and classes</li>";
        echo "<li>Enroll students in classes</li>";
        echo "<li>Start creating quizzes and assignments!</li>";
        echo "</ol>";
        echo "</div>";
    } else {
        echo "<h2 style='color: red;'>❌ Installation had some issues</h2>";
        echo "<p>Please check the errors above and try again.</p>";
    }

    $conn->close();
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Installation failed: " . $e->getMessage() . "</p>";
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 1rem; border-radius: 5px; margin: 1rem 0;'>";
    echo "<h3>🔧 Troubleshooting:</h3>";
    echo "<ul>";
    echo "<li>Make sure MySQL server is running</li>";
    echo "<li>Check database credentials in this script</li>";
    echo "<li>Ensure you have permission to create databases</li>";
    echo "<li>Try running the schema.sql file directly in phpMyAdmin</li>";
    echo "</ul>";
    echo "</div>";
}
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 1000px;
    margin: 2rem auto;
    padding: 1rem;
    line-height: 1.6;
}

h1,
h2,
h3 {
    color: #333;
    border-bottom: 2px solid #eee;
    padding-bottom: 0.5rem;
}

code {
    background: #f8f9fa;
    padding: 2px 4px;
    border-radius: 3px;
    font-family: monospace;
}

ul,
ol {
    padding-left: 2rem;
}

li {
    margin: 0.5rem 0;
}
</style>