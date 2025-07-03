<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name']);
    $email    = trim($_POST['email']);
    $password = $_POST['password'];
    $role     = $_POST['role'];

    if (empty($name) || empty($email) || empty($password) || empty($role)) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } elseif (!in_array($role, ['admin', 'teacher', 'student'])) {
        $error = "Invalid user role.";
    } else {

        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $error = "Email is already registered.";
        } else {

            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $name, $email, $hashed_password, $role);
            if ($stmt->execute()) {
                $success = "User created successfully.";
            } else {
                $error = "Failed to create user.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Create User - EduLearn LMS</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>

<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-graduation-cap"></i> EduLearn</h2>
                <p>Admin Panel</p>
            </div>

            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-item">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
                <a href="users.php" class="nav-item">
                    <i class="fas fa-users"></i>
                    <span>Users</span>
                </a>
                <a href="classes.php" class="nav-item">
                    <i class="fas fa-school"></i>
                    <span>Classes</span>
                </a>
                <a href="subjects.php" class="nav-item">
                    <i class="fas fa-book"></i>
                    <span>Subjects</span>
                </a>
                <a href="assignments.php" class="nav-item">
                    <i class="fas fa-tasks"></i>
                    <span>Assignments</span>
                </a>
                <a href="quizzes.php" class="nav-item">
                    <i class="fas fa-question-circle"></i>
                    <span>Quizzes</span>
                </a>
                <a href="reports.php" class="nav-item">
                    <i class="fas fa-chart-bar"></i>
                    <span>Reports</span>
                </a>
                <a href="assign.php" class="nav-item active">
                    <i class="fas fa-link"></i>
                    <span>Assign</span>
                </a>
            </nav>

            <div class="sidebar-footer">
                <a href="../logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </aside>
        <main class="main-content">

            <header class="content-header">
                <div class="header-left">
                    <h1>Create User</h1>
                    <p>Add new admin, teacher, or student</p>
                </div>
            </header>

            <div class="content-card" style="margin-top: 2rem; max-width: 700px;">
                <div class="card-header">
                    <h3>New User Form</h3>
                </div>
                <div class="card-content">
                    <?php if ($success): ?>
                    <p style="color:green;"><?php echo $success; ?></p>
                    <?php elseif ($error): ?>
                    <p style="color:red;"><?php echo $error; ?></p>
                    <?php endif; ?>

                    <form method="POST">
                        <label for="name"><strong>Name:</strong></label><br>
                        <input type="text" name="name" id="name" required placeholder="Full Name"
                            style="width: 100%; padding: 0.5rem;"><br><br>

                        <label for="email"><strong>Email:</strong></label><br>
                        <input type="email" name="email" id="email" required placeholder="example@email.com"
                            style="width: 100%; padding: 0.5rem;"><br><br>

                        <label for="password"><strong>Password:</strong></label><br>
                        <input type="password" name="password" id="password" required placeholder="Enter password"
                            style="width: 100%; padding: 0.5rem;"><br><br>

                        <label for="role"><strong>Role:</strong></label><br>
                        <select name="role" id="role" required style="width: 100%; padding: 0.5rem;">
                            <option value="">-- Select Role --</option>
                            <option value="admin">Admin</option>
                            <option value="teacher">Teacher</option>
                            <option value="student">Student</option>
                        </select><br><br>

                        <button type="submit" class="btn btn-primary">Create User</button>
                    </form>
                </div>
            </div>
        </main>
    </div>
</body>

</html>