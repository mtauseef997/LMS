<?php
session_start();
require_once '../config/db.php';


if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}


$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['class_name'])) {
    $class_name = trim($_POST['class_name']);
    if (!empty($class_name)) {
        $stmt = $conn->prepare("INSERT INTO classes (name) VALUES (?)");
        $stmt->bind_param('s', $class_name);
        if ($stmt->execute()) {
            $success = "Class added successfully.";
        } else {
            $error = "Failed to add class.";
        }
    } else {
        $error = "Class name cannot be empty.";
    }
}


if (isset($_GET['delete'])) {
    $class_id = intval($_GET['delete']);
    $conn->query("DELETE FROM classes WHERE id = $class_id");
    header("Location: classes.php");
    exit;
}


$classes = $conn->query("SELECT * FROM classes ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Manage Classes - EduLearn</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
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
                    <h1>Manage Classes</h1>
                    <p>Create or delete class groups for students</p>
                </div>
            </header>


            <div class="content-card" style="margin-top: 2rem;">
                <div class="card-header">
                    <h3>Add New Class</h3>
                </div>
                <div class="card-content">
                    <?php if ($success): ?>
                    <p style="color: green;"><?php echo $success; ?></p>
                    <?php elseif ($error): ?>
                    <p style="color: red;"><?php echo $error; ?></p>
                    <?php endif; ?>

                    <form method="POST">
                        <label for="class_name"><strong>Class Name:</strong></label><br>
                        <input type="text" name="class_name" id="class_name" required placeholder="e.g. Grade 6A"
                            style="padding: 0.5rem; width: 100%; max-width: 400px; margin-top: 5px;"><br><br>
                        <button type="submit" class="btn btn-primary">Add Class</button>
                    </form>
                </div>
            </div>


            <div class="content-card" style="margin-top: 2rem;">
                <div class="card-header">
                    <h3>All Classes</h3>
                </div>
                <div class="card-content">
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Class Name</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $classes->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $row['id']; ?></td>
                                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                                    <td>
                                        <a href="classes.php?delete=<?php echo $row['id']; ?>" class="btn btn-danger"
                                            onclick="return confirm('Are you sure you want to delete this class?');">
                                            <i class="fas fa-trash"></i> Delete
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>

</html>