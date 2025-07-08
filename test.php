<?php
echo "<h1>LMS Test Page</h1>";
echo "<p>If you can see this, Apache and PHP are working correctly!</p>";
echo "<p>Current time: " . date('Y-m-d H:i:s') . "</p>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>Server: " . $_SERVER['SERVER_SOFTWARE'] . "</p>";

// Test database connection
try {
    require_once 'config/db.php';
    echo "<p style='color: green;'>✅ Database connection successful!</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database connection failed: " . $e->getMessage() . "</p>";
}

echo "<h2>Available Pages:</h2>";
echo "<ul>";
echo "<li><a href='login.php'>Login Page</a></li>";
echo "<li><a href='register.php'>Register Page</a></li>";
echo "<li><a href='admin/dashboard.php'>Admin Dashboard</a></li>";
echo "<li><a href='students/dashboard.php'>Student Dashboard</a></li>";
echo "<li><a href='teacher/dashboard.php'>Teacher Dashboard</a></li>";
echo "</ul>";

echo "<h2>Test Pages:</h2>";
echo "<ul>";
echo "<li><a href='students/test_all_pages.php'>Test All Student Pages</a></li>";
echo "<li><a href='students/test_dashboard.php'>Test Student Dashboard</a></li>";
echo "<li><a href='database/fix_all_columns.php'>Fix Database Columns</a></li>";
echo "</ul>";
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h1 { color: #333; }
h2 { color: #666; }
ul { margin: 10px 0; }
li { margin: 5px 0; }
a { color: #007cba; text-decoration: none; }
a:hover { text-decoration: underline; }
</style>
