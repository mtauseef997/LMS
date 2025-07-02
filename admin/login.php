<?php
session_start();
require_once 'config/db.php';

if (isset($_SESSION['user_id'])) {
    switch ($_SESSION['user_role']) {
        case 'admin':
            header('Location: admin/dashboard.php');
            exit;
        case 'teacher':
            header('Location: teacher/dashboard.php');
            exit;
        case 'student':
            header('Location: student/dashboard.php');
            exit;
    }
}

$errors = [];
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';


    if (empty($email)) {
        $errors[] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address';
    }

    if (empty($password)) {
        $errors[] = 'Password is required';
    }


    if (empty($errors)) {
        $user = getUserByEmail($conn, $email);

        if ($user && password_verify($password, $user['password'])) {

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];


            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                header('Content-Type: application/json');

                $redirect_url = '';
                switch ($user['role']) {
                    case 'admin':
                        $redirect_url = 'admin/dashboard.php';
                        break;
                    case 'teacher':
                        $redirect_url = 'teacher/dashboard.php';
                        break;
                    case 'student':
                        $redirect_url = 'student/dashboard.php';
                        break;
                }

                echo json_encode(['success' => true, 'message' => 'Login successful!', 'redirect' => $redirect_url]);
                exit;
            }


            switch ($user['role']) {
                case 'admin':
                    header('Location: admin/dashboard.php');
                    exit;
                case 'teacher':
                    header('Location: teacher/dashboard.php');
                    exit;
                case 'student':
                    header('Location: student/dashboard.php');
                    exit;
            }
        } else {
            $errors[] = 'Invalid email or password';
        }
    }


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
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - EduLearn LMS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/register.css">
    <style>
    body {
        font-family: 'Inter', sans-serif;
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
            <h2>Welcome Back</h2>
            <p>Sign in to your EduLearn account</p>
        </div>

        <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo $messageType; ?>">
            <?php echo $message; ?>
        </div>
        <?php endif; ?>


        <div
            style="background: rgba(255, 255, 255, 0.1); border-radius: 10px; padding: 1.5rem; margin-bottom: 2rem; backdrop-filter: blur(10px);">
            <h4 style="color: white; margin-bottom: 1rem; font-size: 1rem;"><i class="fas fa-info-circle"></i> Demo
                Accounts</h4>
            <div
                style="display: flex; justify-content: space-between; align-items: center; padding: 0.5rem 0; border-bottom: 1px solid rgba(255, 255, 255, 0.1);">
                <span style="color: rgba(255, 255, 255, 0.8); font-size: 0.9rem;">admin@lms.com</span>
                <span
                    style="background: rgba(255, 255, 255, 0.2); padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.8rem; color: white;">Admin</span>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.5rem 0;">
                <span style="color: rgba(255, 255, 255, 0.8); font-size: 0.9rem;">Password: admin123</span>
                <span style="opacity: 0.6; color: rgba(255, 255, 255, 0.8); font-size: 0.9rem;">for all accounts</span>
            </div>
        </div>

        <form id="loginForm" method="POST" action="">
            <div class="form-group">
                <label for="email">
                    <i class="fas fa-envelope"></i>
                    Email Address
                </label>
                <input type="email" id="email" name="email" required
                    value="<?php echo htmlspecialchars($email ?? ''); ?>">
                <div class="error-message"></div>
            </div>

            <div class="form-group">
                <label for="password">
                    <i class="fas fa-lock"></i>
                    Password
                </label>
                <div class="password-input">
                    <input type="password" id="password" name="password" required>
                    <button type="button" class="toggle-password">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <div class="error-message"></div>
            </div>

            <button type="submit" class="submit-btn">
                <span>Sign In</span>
                <i class="fas fa-arrow-right"></i>
            </button>
        </form>

        <div class="login-link"
            style="text-align: center; margin-top: 2rem; padding-top: 2rem; border-top: 1px solid rgba(255, 255, 255, 0.1);">
            <p>Don't have an account? <a href="register.php"
                    style="color: rgba(255, 255, 255, 0.8); text-decoration: none;">Create Account</a></p>
            <p><a href="index.php" style="color: rgba(255, 255, 255, 0.8); text-decoration: none;">← Back to Home</a>
            </p>
        </div>
    </div>

    <script src="assets/js/login.js"></script>
</body>

</html>