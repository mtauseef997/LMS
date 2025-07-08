<?php
require_once '../config/db.php';

echo "<h1>Database Column Fix Utility</h1>";
echo "<p>This script will add all missing columns to ensure compatibility with the LMS system.</p>";

// Function to check if column exists
function columnExists($conn, $table, $column)
{
    $result = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
    return $result && $result->num_rows > 0;
}

// Function to add column if it doesn't exist
function addColumnIfNotExists($conn, $table, $column, $definition)
{
    if (!columnExists($conn, $table, $column)) {
        $sql = "ALTER TABLE `$table` ADD COLUMN `$column` $definition";
        if ($conn->query($sql)) {
            echo "<p style='color: green;'>✅ Added column '$column' to table '$table'</p>";
            return true;
        } else {
            echo "<p style='color: red;'>❌ Failed to add column '$column' to table '$table': " . $conn->error . "</p>";
            return false;
        }
    } else {
        echo "<p style='color: blue;'>ℹ️ Column '$column' already exists in table '$table'</p>";
        return true;
    }
}

echo "<h2>Adding Missing Columns</h2>";

// All the columns that should exist based on the schema
$required_columns = [
    // Users table
    ['users', 'created_at', 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP'],

    // Classes table
    ['classes', 'created_at', 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP'],

    // Subjects table
    ['subjects', 'created_at', 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP'],

    // Teacher subject class table
    ['teacher_subject_class', 'created_at', 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP'],

    // Student class table
    ['student_class', 'created_at', 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP'],

    // Quizzes table
    ['quizzes', 'created_at', 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP'],
    ['quizzes', 'total_marks', 'INT DEFAULT 0'],
    ['quizzes', 'time_limit', 'INT DEFAULT 30'],
    ['quizzes', 'is_active', 'BOOLEAN DEFAULT 1'],
    ['quizzes', 'description', 'TEXT DEFAULT NULL'],

    // Quiz questions table
    ['quiz_questions', 'options', 'JSON DEFAULT NULL'],
    ['quiz_questions', 'explanation', 'TEXT DEFAULT NULL'],

    // Quiz submissions table
    ['quiz_submissions', 'percentage', 'DECIMAL(5,2) DEFAULT 0.00'],
    ['quiz_submissions', 'submitted_at', 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP'],

    // Assignments table
    ['assignments', 'created_at', 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP'],
    ['assignments', 'due_date', 'DATETIME NOT NULL'],
    ['assignments', 'max_marks', 'INT DEFAULT 100'],
    ['assignments', 'is_active', 'BOOLEAN DEFAULT 1'],
    ['assignments', 'description', 'TEXT DEFAULT NULL'],

    // Assignment submissions table
    ['assignment_submissions', 'score', 'DECIMAL(5,2) DEFAULT NULL'],
    ['assignment_submissions', 'feedback', 'TEXT DEFAULT NULL'],
    ['assignment_submissions', 'submitted_at', 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP'],
    ['assignment_submissions', 'graded_at', 'TIMESTAMP NULL DEFAULT NULL']
];

$success_count = 0;
$total_count = count($required_columns);

foreach ($required_columns as $col) {
    $table = $col[0];
    $column = $col[1];
    $definition = $col[2];

    if (addColumnIfNotExists($conn, $table, $column, $definition)) {
        $success_count++;
    }
}

echo "<h2>Summary</h2>";
echo "<p>Successfully processed $success_count out of $total_count columns.</p>";

if ($success_count == $total_count) {
    echo "<p style='color: green; font-weight: bold;'>🎉 All columns are now present! Your database should be fully compatible.</p>";
} else {
    echo "<p style='color: orange; font-weight: bold;'>⚠️ Some columns could not be added. Please check the errors above.</p>";
}

// Test critical queries
echo "<h2>Testing Critical Queries</h2>";

$test_queries = [
    'Users with created_at' => "SELECT COUNT(*) as count FROM users",
    'Classes with created_at' => "SELECT COUNT(*) as count FROM classes",
    'Quizzes with total_marks' => "SELECT COUNT(*) as count FROM quizzes",
    'Quiz submissions with percentage' => "SELECT COUNT(*) as count FROM quiz_submissions",
    'Assignment submissions with score' => "SELECT COUNT(*) as count FROM assignment_submissions"
];

foreach ($test_queries as $description => $query) {
    try {
        $result = $conn->query($query);
        if ($result) {
            $row = $result->fetch_assoc();
            echo "<p style='color: green;'>✅ $description: " . $row['count'] . " records</p>";
        } else {
            echo "<p style='color: red;'>❌ $description: Query failed - " . $conn->error . "</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ $description: Exception - " . $e->getMessage() . "</p>";
    }
}

// Test the specific queries that were failing
echo "<h3>Testing Reports Page Queries</h3>";

// Test quiz performance calculation
try {
    $result = $conn->query("SELECT AVG(percentage) as avg_score FROM quiz_submissions WHERE percentage > 0");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "<p style='color: green;'>✅ Quiz percentage calculation: Average = " . round($row['avg_score'], 2) . "%</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ Quiz percentage calculation: No data or column missing</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Quiz percentage calculation failed: " . $e->getMessage() . "</p>";
}

// Test quiz total_marks calculation
try {
    $result = $conn->query("SELECT AVG((qs.score / q.total_marks) * 100) as avg_score 
                           FROM quiz_submissions qs 
                           JOIN quizzes q ON qs.quiz_id = q.id 
                           WHERE q.total_marks > 0");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "<p style='color: green;'>✅ Quiz total_marks calculation: Average = " . round($row['avg_score'], 2) . "%</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ Quiz total_marks calculation: No data</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Quiz total_marks calculation failed: " . $e->getMessage() . "</p>";
}

echo "<h2>Next Steps</h2>";
echo "<ul>";
echo "<li><a href='../admin/reports.php'>Test the Reports Page</a></li>";
echo "<li><a href='../admin/test_reports.php'>Run Reports Test</a></li>";
echo "<li><a href='../admin/dashboard.php'>Go to Admin Dashboard</a></li>";
echo "<li><a href='../test_system.php'>Run Full System Test</a></li>";
echo "</ul>";

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

    ul {
        margin: 10px 0;
    }

    li {
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