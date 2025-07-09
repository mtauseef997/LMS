<?php
session_start();
require_once '../config/db.php';

// Simple test to check if admin functionality works
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Modal Test</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 2rem;
            background: #f5f5f5;
        }
        
        .test-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            margin: 0.5rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            background: #667eea;
            color: white;
        }
        
        .btn:hover {
            background: #5a67d8;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }
        
        .modal-content {
            background-color: white;
            margin: 10% auto;
            padding: 2rem;
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
        }
        
        .close {
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
        }
        
        .form-group input, .form-group select {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
    </style>
</head>
<body>
    <div class="test-container">
        <h1><i class="fas fa-cog"></i> Admin Panel Test</h1>
        <p>This is a test page to verify admin functionality is working properly.</p>
        
        <div class="test-section">
            <h2>Modal Tests</h2>
            <button class="btn" onclick="openTestModal()">
                <i class="fas fa-plus"></i> Test Modal
            </button>
            <button class="btn" onclick="testAjax()">
                <i class="fas fa-sync"></i> Test AJAX
            </button>
            <button class="btn" onclick="testDatabase()">
                <i class="fas fa-database"></i> Test Database
            </button>
        </div>
        
        <div id="test-results" style="margin-top: 2rem; padding: 1rem; background: #f8f9fa; border-radius: 4px;">
            <h3>Test Results:</h3>
            <div id="results-content">Click a test button to see results...</div>
        </div>
        
        <div class="test-section" style="margin-top: 2rem;">
            <h2>Quick Links</h2>
            <a href="manage_user.php" class="btn">User Management</a>
            <a href="manage_subject.php" class="btn">Subject Management</a>
            <a href="manage_class.php" class="btn">Class Management</a>
            <a href="dashboard.php" class="btn">Dashboard</a>
        </div>
    </div>

    <!-- Test Modal -->
    <div id="testModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeTestModal()">&times;</span>
            <h2>Test Modal</h2>
            <form id="testForm">
                <div class="form-group">
                    <label for="testName">Name:</label>
                    <input type="text" id="testName" name="name" required>
                </div>
                <div class="form-group">
                    <label for="testEmail">Email:</label>
                    <input type="email" id="testEmail" name="email" required>
                </div>
                <div class="form-group">
                    <label for="testRole">Role:</label>
                    <select id="testRole" name="role" required>
                        <option value="">Select Role</option>
                        <option value="admin">Admin</option>
                        <option value="teacher">Teacher</option>
                        <option value="student">Student</option>
                    </select>
                </div>
                <button type="submit" class="btn">Submit Test</button>
            </form>
        </div>
    </div>

    <script>
        function openTestModal() {
            document.getElementById('testModal').style.display = 'block';
            updateResults('Modal opened successfully!');
        }
        
        function closeTestModal() {
            document.getElementById('testModal').style.display = 'none';
            updateResults('Modal closed successfully!');
        }
        
        function testAjax() {
            updateResults('Testing AJAX...');
            
            fetch('manage_user.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: 'action=test'
            })
            .then(response => response.text())
            .then(data => {
                updateResults('AJAX Response: ' + data);
            })
            .catch(error => {
                updateResults('AJAX Error: ' + error.message);
            });
        }
        
        function testDatabase() {
            updateResults('Testing database connection...');
            
            fetch('../test.php')
            .then(response => response.text())
            .then(data => {
                if (data.includes('Database connection successful')) {
                    updateResults('Database connection: SUCCESS');
                } else {
                    updateResults('Database connection: FAILED');
                }
            })
            .catch(error => {
                updateResults('Database test error: ' + error.message);
            });
        }
        
        function updateResults(message) {
            const timestamp = new Date().toLocaleTimeString();
            const resultsDiv = document.getElementById('results-content');
            resultsDiv.innerHTML += '<div>[' + timestamp + '] ' + message + '</div>';
        }
        
        // Test form submission
        document.getElementById('testForm').addEventListener('submit', function(e) {
            e.preventDefault();
            updateResults('Form submitted successfully! Data: ' + JSON.stringify({
                name: document.getElementById('testName').value,
                email: document.getElementById('testEmail').value,
                role: document.getElementById('testRole').value
            }));
            closeTestModal();
        });
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('testModal');
            if (event.target === modal) {
                closeTestModal();
            }
        }
        
        // Initial test
        updateResults('Admin test page loaded successfully!');
    </script>
</body>
</html>
