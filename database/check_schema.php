<?php
require_once '../config/db.php';

echo "<h1>Database Schema Check and Fix</h1>";

// Function to check if column exists
function columnExists($conn, $table, $column)
{
    $result = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
    return $result->num_rows > 0;
}

// Function to add column if it doesn't exist
function addColumnIfNotExists($conn, $table, $column, $definition)
{
    if (!columnExists($conn, $table, $column)) {
        $sql = "ALTER TABLE `$table` ADD COLUMN `$column` $definition";
        if ($conn->query($sql)) {
            echo "<p style='color: green;'>✅ Added column '$column' to table '$table'</p>";
        } else {
            echo "<p style='color: red;'>❌ Failed to add column '$column' to table '$table': " . $conn->error . "</p>";
        }
    } else {
        echo "<p style='color: blue;'>ℹ️ Column '$column' already exists in table '$table'</p>";
    }
}

// Check and fix database schema
echo "<h2>Checking and Fixing Database Schema</h2>";

// Check if tables exist
$required_tables = [
    'users',
    'classes',
    'subjects',
    'teacher_subject_class',
    'student_class',
    'quizzes',
    'quiz_questions',
    'quiz_submissions',
    'assignments',
    'assignment_submissions'
];

echo "<h3>Table Existence Check</h3>";
foreach ($required_tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows > 0) {
        echo "<p style='color: green;'>✅ Table '$table' exists</p>";
    } else {
        echo "<p style='color: red;'>❌ Table '$table' missing - please run schema.sql</p>";
    }
}

// Add missing created_at columns
echo "<h3>Adding Missing created_at Columns</h3>";
addColumnIfNotExists($conn, 'users', 'created_at', 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP');
addColumnIfNotExists($conn, 'classes', 'created_at', 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP');
addColumnIfNotExists($conn, 'subjects', 'created_at', 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP');
addColumnIfNotExists($conn, 'teacher_subject_class', 'created_at', 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP');
addColumnIfNotExists($conn, 'student_class', 'created_at', 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP');
addColumnIfNotExists($conn, 'quizzes', 'created_at', 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP');
addColumnIfNotExists($conn, 'assignments', 'created_at', 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP');

// Add missing percentage column to quiz_submissions
addColumnIfNotExists($conn, 'quiz_submissions', 'percentage', 'DECIMAL(5,2) DEFAULT 0.00');

// Add missing total_marks column to quizzes
addColumnIfNotExists($conn, 'quizzes', 'total_marks', 'INT DEFAULT 0');

// Check specific column types
echo "<h3>Column Type Verification</h3>";

// Check quiz_questions options column
$result = $conn->query("SHOW COLUMNS FROM quiz_questions LIKE 'options'");
if ($result->num_rows > 0) {
    $column_info = $result->fetch_assoc();
    if (strpos(strtolower($column_info['Type']), 'json') !== false) {
        echo "<p style='color: green;'>✅ quiz_questions.options is JSON type</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ quiz_questions.options is not JSON type: " . $column_info['Type'] . "</p>";
        // Try to convert to JSON
        if ($conn->query("ALTER TABLE quiz_questions MODIFY COLUMN options JSON DEFAULT NULL")) {
            echo "<p style='color: green;'>✅ Converted quiz_questions.options to JSON</p>";
        } else {
            echo "<p style='color: red;'>❌ Failed to convert quiz_questions.options to JSON: " . $conn->error . "</p>";
        }
    }
} else {
    echo "<p style='color: red;'>❌ quiz_questions.options column missing</p>";
}

// Check quiz_submissions percentage column
$result = $conn->query("SHOW COLUMNS FROM quiz_submissions LIKE 'percentage'");
if ($result->num_rows > 0) {
    $column_info = $result->fetch_assoc();
    if (strpos(strtolower($column_info['Type']), 'decimal') !== false) {
        echo "<p style='color: green;'>✅ quiz_submissions.percentage is DECIMAL type</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ quiz_submissions.percentage is not DECIMAL type: " . $column_info['Type'] . "</p>";
    }
}

// Check assignment_submissions score column
$result = $conn->query("SHOW COLUMNS FROM assignment_submissions LIKE 'score'");
if ($result->num_rows > 0) {
    $column_info = $result->fetch_assoc();
    if (strpos(strtolower($column_info['Type']), 'decimal') !== false) {
        echo "<p style='color: green;'>✅ assignment_submissions.score is DECIMAL type</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ assignment_submissions.score is not DECIMAL type: " . $column_info['Type'] . "</p>";
    }
}

// Show table record counts
echo "<h3>Table Record Counts</h3>";
foreach ($required_tables as $table) {
    $result = $conn->query("SELECT COUNT(*) as count FROM `$table`");
    if ($result) {
        $count = $result->fetch_assoc()['count'];
        echo "<p>$table: $count records</p>";
    } else {
        echo "<p style='color: red;'>❌ Error counting records in $table: " . $conn->error . "</p>";
    }
}

// Test problematic queries
echo "<h3>Testing Problematic Queries</h3>";

// Test classes query with created_at
$test_query = "SELECT c.*, 
              (SELECT COUNT(*) FROM student_class sc WHERE sc.class_id = c.id) as student_count,
              (SELECT COUNT(*) FROM teacher_subject_class tsc WHERE tsc.class_id = c.id) as teacher_count
              FROM classes c ORDER BY c.name ASC LIMIT 1";

if ($conn->query($test_query)) {
    echo "<p style='color: green;'>✅ Classes query test passed</p>";
} else {
    echo "<p style='color: red;'>❌ Classes query test failed: " . $conn->error . "</p>";
}

// Test users query with created_at
$test_query = "SELECT * FROM users ORDER BY created_at DESC LIMIT 1";
if ($conn->query($test_query)) {
    echo "<p style='color: green;'>✅ Users query test passed</p>";
} else {
    echo "<p style='color: red;'>❌ Users query test failed: " . $conn->error . "</p>";
}

echo "<h2>Schema Check Complete</h2>";
echo "<p><a href='../admin/dashboard.php'>← Back to Admin Dashboard</a></p>";
echo "<p><a href='../test_system.php'>Run Full System Test</a></p>";
?>

<style>
    body {
        font-family: Arial, sans-serif;
        margin: 20px;
    }

    h1 {
        color: #333;
    }

    h2 {
        color: #666;
        border-bottom: 1px solid #ddd;
        padding-bottom: 5px;
    }

    h3 {
        color: #888;
    }

    p {
        margin: 5px 0;
    }

    a {
        color: #007cba;
        text-decoration: none;
    }

    a:hover {
        text-decoration: underline;
    }
</style>