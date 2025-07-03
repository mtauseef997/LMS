<?php
session_start();
require_once __DIR__ . '/../config/db.php';


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
    <link rel="stylesheet" href="assets/css/register.css" <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - EduLearn LMS</title>


    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">


    <link rel="stylesheet" href="assets/css/index.css">
</head>
<style>
body {
    font-family: 'Inter', sans-serif;
    background: #f9f9fb;
    color: #333;
    margin: 0;
    padding: 0;
}

.register-container {
    max-width: 400px;
    margin: 5rem auto;
    background: white;
    padding: 2rem;
    border-radius: 10px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
}

.register-header h2 {
    margin-bottom: 0.5rem;
    font-size: 1.8rem;
    color: #333;
}

.register-header p {
    color: #777;
    margin-bottom: 1.5rem;
}

.form-group {
    margin-bottom: 1.5rem;
}

label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
}

input[type="email"],
input[type="password"] {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #ccc;
    border-radius: 6px;
    font-size: 1rem;
}

.submit-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    padding: 0.75rem;
    border: none;
    border-radius: 6px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.3s ease;
    width: 100%;
}

.submit-btn:hover {
    background: linear-gradient(135deg, #5a67d8, #6b46c1);
}

.submit-btn i {
    margin-left: 0.5rem;
}

.alert {
    padding: 1rem;
    border-radius: 6px;
    margin-bottom: 1rem;
}

.alert-error {
    background: #ffe5e5;
    color: #b10000;
}

.alert-success {
    background: #e6ffed;
    color: #007d2c;
}

.toggle-password {
    background: none;
    border: none;
    position: absolute;
    right: 1rem;
    top: 50%;
    transform: translateY(-50%);
    cursor: pointer;
    color: #999;
}

.password-input {
    position: relative;
}

.login-link {
    text-align: center;
    font-size: 0.9rem;
    color: #666;
}

.login-link a {
    color: #667eea;
    text-decoration: none;
}

.login-link a:hover {
    text-decoration: underline;
}
</style>


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