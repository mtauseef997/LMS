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
    $teacher_id = $_POST['teacher_id'];
    $subject_id = $_POST['subject_id'];
    $class_id = $_POST['class_id'];

    $stmt = $conn->prepare("SELECT id FROM teacher_subject_class WHERE teacher_id = ? AND subject_id = ? AND class_id = ?");
    $stmt->bind_param('iii', $teacher_id, $subject_id, $class_id);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $error = "This assignment already exists.";
    } else {
        $stmt = $conn->prepare("INSERT INTO teacher_subject_class (teacher_id, subject_id, class_id) VALUES (?, ?, ?)");
        $stmt->bind_param('iii', $teacher_id, $subject_id, $class_id);
        if ($stmt->execute()) {
            $success = "Assignment created successfully!";
        } else {
            $error = "Error assigning teacher.";
        }
    }
}

$teachers = $conn->query("SELECT id, name FROM users WHERE role = 'teacher'");
$subjects = $conn->query("SELECT id, name FROM subjects");
$classes = $conn->query("SELECT id, name FROM classes");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Assign Teacher - EduLearn</title>
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
                    <h1>Assign Teacher</h1>
                    <p>Link teacher to subject and class.</p>
                </div>
            </header>

            <div class="content-card" style="margin-top: 2rem;">
                <div class="card-header">
                    <h3>Create Assignment</h3>
                </div>
                <div class="card-content">
                    <?php if ($success): ?>
                    <p style="color:green;"><strong><?php echo $success; ?></strong></p>
                    <?php endif; ?>
                    <?php if ($error): ?>
                    <p style="color:red;"><strong><?php echo $error; ?></strong></p>
                    <?php endif; ?>
                    <form method="POST">
                        <label>Teacher:</label><br>
                        <select name="teacher_id" required>
                            <option value="">-- Select Teacher --</option>
                            <?php while ($row = $teachers->fetch_assoc()): ?>
                            <option value="<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['name']); ?>
                            </option>
                            <?php endwhile; ?>
                        </select><br><br>

                        <label>Subject:</label><br>
                        <select name="subject_id" required>
                            <option value="">-- Select Subject --</option>
                            <?php while ($row = $subjects->fetch_assoc()): ?>
                            <option value="<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['name']); ?>
                            </option>
                            <?php endwhile; ?>
                        </select><br><br>

                        <label>Class:</label><br>
                        <select name="class_id" required>
                            <option value="">-- Select Class --</option>
                            <?php while ($row = $classes->fetch_assoc()): ?>
                            <option value="<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['name']); ?>
                            </option>
                            <?php endwhile; ?>
                        </select><br><br>

                        <button type="submit" class="btn btn-primary">Assign</button>
                    </form>
                </div>
            </div>
        </main>
    </div>
</body>

</html>