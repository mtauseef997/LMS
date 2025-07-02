<?php
require_once 'config/db.php';
session_start();

$message = '';
$messageType = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? '';

    $errors = [];

    // Validation
    if (empty($first_name) || strlen($first_name) < 2) {
        $errors[] = 'Name must be at least 2 characters long';
    }

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address';
    }

    if (empty($password) || strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters long';
    }

    if ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match';
    }

    if (empty($role) || !in_array($role, ['student', 'teacher', 'admin'])) {
        $errors[] = 'Please select a valid role';
    }

    // Check if email already exists
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $errors[] = 'Email address is already registered';
        }
        $stmt->close();
    }

    // If no errors, create user
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $name = $first_name;

        $stmt = $conn->prepare("INSERT INTO `users`(`name`, `email`, `password`, `role`) VALUES (?, ?, ?, ?)");
        if ($stmt === false) {
            $errors[] = 'Prepare failed: ' . $conn->error;
        } else {
            $stmt->bind_param("ssss", $name, $email, $hashed_password, $role);
            if ($stmt->execute()) {
                $message = 'Account created successfully! You can now log in.';
                $messageType = 'success';

                // AJAX response
                if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'message' => $message]);
                    exit;
                }
            } else {
                $errors[] = 'Execute failed: ' . $stmt->error;
            }
            $stmt->close();
        }
    }

    // Error response (AJAX)
    if (!empty($errors) && !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
        exit;
    }

    if (!empty($errors)) {
        $message = implode('<br>', $errors);
        $messageType = 'error';
    }
}
?>

<!-- HTML BELOW -->

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Register - Learning Management System</title>
    <link rel="stylesheet" href="assets/css/register.css">
    <style>
    body {
        font-family: 'Inter', sans-serif;
    }

    .password-strength {
        font-size: 0.875rem;
        margin-top: 5px;
        padding: 5px 10px;
        border-radius: 6px;
        text-align: center;
        font-weight: 500;
    }

    .password-strength.very-weak {
        background: #ffebee;
        color: #c62828;
    }

    .password-strength.weak {
        background: #fff3e0;
        color: #ef6c00;
    }

    .password-strength.fair {
        background: #fff8e1;
        color: #f57f17;
    }

    .password-strength.good {
        background: #f3e5f5;
        color: #7b1fa2;
    }

    .password-strength.strong {
        background: #e8f5e8;
        color: #2e7d32;
    }

    .floating-shapes {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        pointer-events: none;
        z-index: -1;
    }

    .shape {
        position: absolute;
        opacity: 0.1;
        animation: float 6s ease-in-out infinite;
    }

    .shape:nth-child(1) {
        top: 20%;
        left: 10%;
        width: 80px;
        height: 80px;
        background: linear-gradient(45deg, #667eea, #764ba2);
        border-radius: 50%;
        animation-delay: 0s;
    }

    .shape:nth-child(2) {
        top: 60%;
        right: 10%;
        width: 120px;
        height: 120px;
        background: linear-gradient(45deg, #f093fb, #f5576c);
        border-radius: 30% 70% 70% 30% / 30% 30% 70% 70%;
        animation-delay: 2s;
    }

    .shape:nth-child(3) {
        bottom: 20%;
        left: 20%;
        width: 60px;
        height: 60px;
        background: linear-gradient(45deg, #4facfe, #00f2fe);
        transform: rotate(45deg);
        animation-delay: 4s;
    }

    @keyframes float {

        0%,
        100% {
            transform: translateY(0px) rotate(0deg);
        }

        50% {
            transform: translateY(-20px) rotate(180deg);
        }
    }
    </style>
</head>

<body>
    <div class="floating-shapes">
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
    </div>

    <div class="register-container">
        <div class="register-header">
            <h1>Join Our LMS</h1>
            <p>Create your account to start learning</p>
        </div>

        <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo $messageType; ?>">
            <?php echo $message; ?>
        </div>
        <?php endif; ?>

        <form id="registerForm" method="POST" action="register.php">
            <div class="form-group">
                <label for="first_name">Name</label>
                <input type="text" id="first_name" name="first_name" placeholder="Enter your name"
                    value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" placeholder="Enter your email"
                    value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Create a strong password" required>
                <div id="passwordStrength" class="password-strength" style="display: none;"></div>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm password"
                    required>
            </div>

            <div class="form-group">
                <label>Select Your Role</label>
                <div class="role-selection">
                    <div class="role-option">
                        <input type="radio" id="student" name="role" value="student"
                            <?php echo (($_POST['role'] ?? '') === 'student') ? 'checked' : ''; ?>>
                        <label for="student" class="role-label">
                            <strong>Student</strong><br><small>Access courses and assignments</small>
                        </label>
                    </div>
                    <div class="role-option">
                        <input type="radio" id="teacher" name="role" value="teacher"
                            <?php echo (($_POST['role'] ?? '') === 'teacher') ? 'checked' : ''; ?>>
                        <label for="teacher" class="role-label">
                            <strong>Teacher</strong><br><small>Create and manage courses</small>
                        </label>
                    </div>
                    <div class="role-option">
                        <input type="radio" id="admin" name="role" value="admin"
                            <?php echo (($_POST['role'] ?? '') === 'admin') ? 'checked' : ''; ?>>
                        <label for="admin" class="role-label">
                            <strong>Admin</strong><br><small>System administration</small>
                        </label>
                    </div>
                </div>
            </div>

            <button type="submit" class="register-btn">Create Account</button>
        </form>

        <div class="login-link">
            <p>Already have an account? <a href="login.php">Sign in here</a></p>
        </div>
    </div>

    <script src="assets/js/register.js"></script>
    <script>
    // Show password strength hint
    document.getElementById('password').addEventListener('input', function() {
        const strengthIndicator = document.getElementById('passwordStrength');
        if (this.value.length > 0) {
            strengthIndicator.style.display = 'block';
            // Optional: add JS password strength logic here
        } else {
            strengthIndicator.style.display = 'none';
        }
    });
    </script>
</body>

</html>