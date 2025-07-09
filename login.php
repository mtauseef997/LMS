<?php
session_start();
require_once __DIR__ . '/config/db.php';

// Check if already logged in
if (isset($_SESSION['user_id'])) {
    switch ($_SESSION['user_role']) {
        case 'admin':
            header('Location: admin/dashboard.php');
            exit;
        case 'teacher':
            header('Location: teacher/dashboard.php');
            exit;
        case 'student':
            header('Location: students/dashboard.php');
            exit;
    }
}

$errors = [];
$message = '';
$messageType = '';
$debug_info = '';

// Check database connection
if ($conn->connect_error) {
    $errors[] = 'Database connection failed: ' . $conn->connect_error;
    $debug_info .= 'DB Connection Error: ' . $conn->connect_error . '<br>';
}

// Check if users table exists
if (empty($errors)) {
    $table_check = $conn->query("SHOW TABLES LIKE 'users'");
    if (!$table_check || $table_check->num_rows == 0) {
        $errors[] = 'Users table does not exist. Please run the database setup.';
        $debug_info .= 'Users table missing<br>';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Input validation
    if (empty($email)) {
        $errors[] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address';
    }

    if (empty($password)) {
        $errors[] = 'Password is required';
    }

    // Proceed with login if no validation errors
    if (empty($errors)) {
        try {
            // Check if user exists
            $stmt = $conn->prepare("SELECT id, name, email, password, role FROM users WHERE email = ?");
            if (!$stmt) {
                throw new Exception('Database prepare failed: ' . $conn->error);
            }

            $stmt->bind_param("s", $email);
            if (!$stmt->execute()) {
                throw new Exception('Database execute failed: ' . $stmt->error);
            }

            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();

            if ($user) {
                // Verify password
                if (password_verify($password, $user['password'])) {
                    // Set session variables
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_role'] = $user['role'];

                    // Handle AJAX requests
                    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                        header('Content-Type: application/json');
                        $redirect_url = $user['role'] === 'student' ? 'students/dashboard.php' : $user['role'] . '/dashboard.php';
                        echo json_encode(['success' => true, 'message' => 'Login successful!', 'redirect' => $redirect_url]);
                        exit;
                    }

                    // Regular form submission redirect
                    $redirect_path = $user['role'] === 'student' ? 'students/dashboard.php' : $user['role'] . '/dashboard.php';
                    header('Location: ' . $redirect_path);
                    exit;
                } else {
                    $errors[] = 'Invalid email or password';
                    $debug_info .= 'Password verification failed for: ' . $email . '<br>';
                }
            } else {
                $errors[] = 'Invalid email or password';
                $debug_info .= 'User not found: ' . $email . '<br>';
            }
        } catch (Exception $e) {
            $errors[] = 'Login failed: ' . $e->getMessage();
            $debug_info .= 'Exception: ' . $e->getMessage() . '<br>';
        }
    }

    // Handle AJAX error responses
    if (!empty($errors) && !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
        exit;
    }

    // Set message for display
    if (!empty($errors)) {
        $message = implode('<br>', $errors);
        $messageType = 'error';
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Learning Management System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow-x: hidden;
        }

        /* Animated Background Elements */
        .bg-animation {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 0;
        }

        .bg-animation::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="white" opacity="0.1"/><circle cx="75" cy="75" r="1" fill="white" opacity="0.1"/><circle cx="50" cy="10" r="0.5" fill="white" opacity="0.1"/><circle cx="10" cy="60" r="0.5" fill="white" opacity="0.1"/><circle cx="90" cy="40" r="0.5" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            animation: float 20s ease-in-out infinite;
        }

        .floating-shapes {
            position: absolute;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 1;
        }

        .shape {
            position: absolute;
            opacity: 0.1;
            animation: float 8s ease-in-out infinite;
        }

        .shape:nth-child(1) {
            top: 10%;
            left: 10%;
            width: 100px;
            height: 100px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation-delay: 0s;
        }

        .shape:nth-child(2) {
            top: 70%;
            right: 15%;
            width: 150px;
            height: 150px;
            background: rgba(255, 255, 255, 0.08);
            border-radius: 30% 70% 70% 30% / 30% 30% 70% 70%;
            animation-delay: 3s;
        }

        .shape:nth-child(3) {
            bottom: 15%;
            left: 15%;
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.12);
            transform: rotate(45deg);
            animation-delay: 6s;
        }

        .shape:nth-child(4) {
            top: 30%;
            right: 30%;
            width: 60px;
            height: 60px;
            background: rgba(255, 255, 255, 0.06);
            border-radius: 50%;
            animation-delay: 2s;
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(0px) rotate(0deg);
            }

            33% {
                transform: translateY(-20px) rotate(120deg);
            }

            66% {
                transform: translateY(10px) rotate(240deg);
            }
        }

        /* Login Container */
        .login-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 24px;
            padding: 3rem;
            width: 100%;
            max-width: 450px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.1);
            position: relative;
            z-index: 2;
            margin: 2rem;
        }

        .login-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }

        .logo {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }

        .logo i {
            font-size: 2rem;
            color: white;
        }

        .login-header h1 {
            font-size: 2rem;
            font-weight: 700;
            color: #1a202c;
            margin-bottom: 0.5rem;
            letter-spacing: -0.025em;
        }

        .login-header p {
            font-size: 1rem;
            color: #64748b;
            font-weight: 400;
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            font-size: 1.1rem;
            z-index: 1;
        }

        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 1rem 1rem 1rem 3rem;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 400;
            background: #ffffff;
            transition: all 0.3s ease;
            outline: none;
        }

        input[type="email"]:focus,
        input[type="password"]:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            transform: translateY(-1px);
        }

        input[type="email"]:focus+i,
        input[type="password"]:focus+i {
            color: #667eea;
        }

        /* Submit Button */
        .submit-btn {
            width: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            font-weight: 600;
            padding: 1rem 2rem;
            border-radius: 12px;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-top: 1rem;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 35px rgba(102, 126, 234, 0.3);
        }

        .submit-btn:active {
            transform: translateY(0);
        }

        .submit-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }

        .btn-content {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        /* Alert Styles */
        .alert {
            padding: 1rem 1.25rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            font-weight: 500;
            border-left: 4px solid;
        }

        .alert-error {
            background: #fef2f2;
            color: #dc2626;
            border-left-color: #dc2626;
        }

        .alert-success {
            background: #f0fdf4;
            color: #16a34a;
            border-left-color: #16a34a;
        }

        .alert-info {
            background: #eff6ff;
            color: #2563eb;
            border-left-color: #2563eb;
        }

        /* Footer */
        .login-footer {
            text-align: center;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #e5e7eb;
        }

        .login-footer p {
            color: #64748b;
            font-size: 0.875rem;
            line-height: 1.5;
        }

        /* Quick Login Buttons */
        .quick-login {
            margin-top: 1.5rem;
            padding: 1.5rem;
            background: #f8fafc;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
        }

        .quick-login h4 {
            color: #374151;
            font-size: 0.875rem;
            font-weight: 600;
            margin-bottom: 1rem;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .quick-login-buttons {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .quick-btn {
            flex: 1;
            min-width: 80px;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .quick-btn.admin {
            background: #dc2626;
            color: white;
        }

        .quick-btn.teacher {
            background: #2563eb;
            color: white;
        }

        .quick-btn.student {
            background: #16a34a;
            color: white;
        }

        .quick-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        /* Responsive Design */
        @media (max-width: 640px) {
            .login-container {
                margin: 1rem;
                padding: 2rem;
                border-radius: 16px;
            }

            .login-header h1 {
                font-size: 1.75rem;
            }

            .logo {
                width: 60px;
                height: 60px;
            }

            .logo i {
                font-size: 1.5rem;
            }
        }
    </style>
</head>

<body>
    <!-- Background Animation -->
    <div class="bg-animation"></div>

    <!-- Floating Shapes -->
    <div class="floating-shapes">
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
    </div>

    <!-- Login Container -->
    <div class="login-container">
        <!-- Header with Logo -->
        <div class="login-header">
            <div class="logo">
                <i class="fas fa-graduation-cap"></i>
            </div>
            <h1>Welcome Back</h1>
            <p>Sign in to your Learning Management System</p>
        </div>

        <!-- Alert Messages -->
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <i class="fas fa-<?php echo $messageType === 'error' ? 'exclamation-triangle' : 'check-circle'; ?>"></i>
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($debug_info) && (isset($_GET['debug']) || $_SERVER['SERVER_NAME'] === 'localhost')): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                <strong>Debug Info:</strong><br>
                <?php echo $debug_info; ?>
            </div>
        <?php endif; ?>

        <!-- Login Form -->
        <form method="POST" action="login.php" id="loginForm">
            <div class="form-group">
                <label for="email">Email Address</label>
                <div class="input-wrapper">
                    <input type="email" id="email" name="email" placeholder="Enter your email address"
                        value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                    <i class="fas fa-envelope"></i>
                </div>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-wrapper">
                    <input type="password" id="password" name="password" placeholder="Enter your password" required>
                    <i class="fas fa-lock"></i>
                </div>
            </div>

            <button type="submit" class="submit-btn" id="submitBtn">
                <div class="btn-content">
                    <span id="btnText">Sign In</span>
                    <i class="fas fa-arrow-right" id="btnIcon"></i>
                    <i class="fas fa-spinner fa-spin" id="btnSpinner" style="display: none;"></i>
                </div>
            </button>
        </form>

        <!-- Footer -->
        <div class="login-footer">
            <p>Need an account? Contact your system administrator for access.</p>
        </div>

        <!-- Quick Login for Testing (only on localhost) -->
        <?php if ($_SERVER['SERVER_NAME'] === 'localhost' || isset($_GET['test'])): ?>
            <div class="quick-login">
                <h4>Quick Test Login</h4>
                <div class="quick-login-buttons">
                    <button class="quick-btn admin" onclick="fillLogin('admin@lms.com', 'password')">
                        <i class="fas fa-user-shield"></i> Admin
                    </button>
                    <button class="quick-btn teacher" onclick="fillLogin('teacher@lms.com', 'password')">
                        <i class="fas fa-chalkboard-teacher"></i> Teacher
                    </button>
                    <button class="quick-btn student" onclick="fillLogin('student@lms.com', 'password')">
                        <i class="fas fa-user-graduate"></i> Student
                    </button>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    <script>
        // Enhanced form handling
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const submitBtn = document.getElementById('submitBtn');
            const btnText = document.getElementById('btnText');
            const btnIcon = document.getElementById('btnIcon');
            const btnSpinner = document.getElementById('btnSpinner');

            // Show loading state with animation
            submitBtn.disabled = true;
            submitBtn.style.transform = 'scale(0.98)';
            btnText.textContent = 'Signing In...';
            btnIcon.style.display = 'none';
            btnSpinner.style.display = 'inline-block';

            // Get form data
            const formData = new FormData(this);

            // Submit form via AJAX
            fetch('login.php', {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        // Success animation
                        btnText.textContent = 'Success!';
                        btnSpinner.style.display = 'none';
                        btnIcon.className = 'fas fa-check';
                        btnIcon.style.display = 'inline-block';
                        submitBtn.style.background = 'linear-gradient(135deg, #16a34a, #15803d)';
                        submitBtn.style.transform = 'scale(1)';

                        // Show success message
                        showMessage(data.message, 'success');

                        // Redirect after short delay
                        setTimeout(() => {
                            window.location.href = data.redirect;
                        }, 1500);
                    } else {
                        throw new Error(data.message);
                    }
                })
                .catch(error => {
                    console.error('Login error:', error);
                    showMessage(error.message || 'Connection error. Please check your internet connection and try again.', 'error');

                    // Reset button state with animation
                    setTimeout(() => {
                        submitBtn.disabled = false;
                        submitBtn.style.transform = 'scale(1)';
                        submitBtn.style.background = 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)';
                        btnText.textContent = 'Sign In';
                        btnSpinner.style.display = 'none';
                        btnIcon.className = 'fas fa-arrow-right';
                        btnIcon.style.display = 'inline-block';
                    }, 1000);
                });
        });

        // Quick login function for testing
        function fillLogin(email, password) {
            const emailInput = document.getElementById('email');
            const passwordInput = document.getElementById('password');

            emailInput.value = email;
            passwordInput.value = password;

            // Add visual feedback
            emailInput.style.borderColor = '#16a34a';
            passwordInput.style.borderColor = '#16a34a';

            setTimeout(() => {
                emailInput.style.borderColor = '#e5e7eb';
                passwordInput.style.borderColor = '#e5e7eb';
            }, 1000);
        }

        // Enhanced show message function
        function showMessage(message, type) {
            // Remove existing alerts
            const existingAlerts = document.querySelectorAll('.alert');
            existingAlerts.forEach(alert => {
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-10px)';
                setTimeout(() => alert.remove(), 300);
            });

            // Create new alert with icon
            const alert = document.createElement('div');
            alert.className = `alert alert-${type}`;
            alert.innerHTML = `
            <i class="fas fa-${type === 'error' ? 'exclamation-triangle' : 'check-circle'}"></i>
            ${message}
        `;

            // Insert after header
            const header = document.querySelector('.login-header');
            header.insertAdjacentElement('afterend', alert);

            // Animate in
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-10px)';
            setTimeout(() => {
                alert.style.transition = 'all 0.3s ease';
                alert.style.opacity = '1';
                alert.style.transform = 'translateY(0)';
            }, 100);

            // Auto-remove after 5 seconds
            setTimeout(() => {
                if (alert.parentNode) {
                    alert.style.opacity = '0';
                    alert.style.transform = 'translateY(-10px)';
                    setTimeout(() => alert.remove(), 300);
                }
            }, 5000);
        }

        // Input focus animations
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-focus on email field
            document.getElementById('email').focus();

            // Add input animations
            document.querySelectorAll('input').forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.style.transform = 'translateY(-2px)';
                    this.parentElement.style.transition = 'transform 0.2s ease';
                });

                input.addEventListener('blur', function() {
                    this.parentElement.style.transform = 'translateY(0)';
                });
            });

            // Page load animation
            const container = document.querySelector('.login-container');
            container.style.opacity = '0';
            container.style.transform = 'translateY(20px)';

            setTimeout(() => {
                container.style.transition = 'all 0.6s ease';
                container.style.opacity = '1';
                container.style.transform = 'translateY(0)';
            }, 100);
        });
    </script>
</body>

</html>