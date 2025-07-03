<?php
require_once 'config/db.php';
session_start();

$start = microtime(true); // Start profiling

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? '';

    $errors = [];

    // Server-side validation
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

    // Check if email already exists using COUNT()
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();

        if ($count > 0) {
            $errors[] = 'Email address is already registered';
        }
    }

    // Insert if no errors
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
        if (!$stmt) {
            $errors[] = 'Prepare failed: ' . $conn->error;
        } else {
            $stmt->bind_param("ssss", $first_name, $email, $hashed_password, $role);
            if ($stmt->execute()) {
                $message = 'Account created successfully! You can now log in.';
                $messageType = 'success';

                if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
                    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
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

    if (!empty($errors)) {
        $message = implode('<br>', $errors);
        $messageType = 'error';

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $message]);
            exit;
        }
    }
}

$executionTime = round(microtime(true) - $start, 3);
error_log("register.php executed in {$executionTime} seconds");
?>


<!-- HTML BELOW -->

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Register - Learning Management System</title>
    <link rel="stylesheet" href="assets/css/register.css">

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

    <script>
    document.getElementById('registerForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const form = e.target;
        const formData = new FormData(form);
        const submitButton = form.querySelector('button[type="submit"]');
        submitButton.disabled = true;

        fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(res => res.json())
            .then(data => {
                submitButton.disabled = false;
                const alertBox = document.querySelector('.alert');
                if (alertBox) alertBox.remove();

                const div = document.createElement('div');
                div.className = 'alert alert-' + (data.success ? 'success' : 'error');
                div.innerHTML = data.message;
                form.parentNode.insertBefore(div, form);

                if (data.success) form.reset();
            })
            .catch(err => {
                submitButton.disabled = false;
                alert("An error occurred: " + err.message);
            });
    });
    </script>

    </script>
</body>

</html>