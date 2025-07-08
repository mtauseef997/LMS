<?php
session_start();
require_once '../config/db.php';


if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'teacher') {
    header('Location: ../login.php');
    exit;
}


$teacher_id = $_SESSION['user_id'];


$teacher_assignments = [];
$query = "SELECT c.name as class_name, s.name as subject_name, tsc.id as assignment_id
          FROM teacher_subject_class tsc
          JOIN classes c ON tsc.class_id = c.id
          JOIN subjects s ON tsc.subject_id = s.id
          WHERE tsc.teacher_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $teacher_assignments[] = $row;
}

$stats = [];

$query = "SELECT COUNT(DISTINCT sc.student_id) as count
          FROM student_class sc
          JOIN teacher_subject_class tsc ON sc.class_id = tsc.class_id
          WHERE tsc.teacher_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$result = $stmt->get_result();
$stats['total_students'] = $result->fetch_assoc()['count'];


$query = "SELECT COUNT(*) as count FROM quizzes WHERE teacher_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$result = $stmt->get_result();
$stats['total_quizzes'] = $result->fetch_assoc()['count'];

$query = "SELECT COUNT(*) as count FROM assignments WHERE teacher_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$result = $stmt->get_result();
$stats['total_assignments'] = $result->fetch_assoc()['count'];

$stats['total_classes'] = count($teacher_assignments);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard - EduLearn LMS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/teacher.css">
</head>

<body>
    <div class="dashboard-container">

        <aside class="sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-graduation-cap"></i> EduLearn</h2>
                <p>Teacher Panel</p>
            </div>

            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-item active">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
                <a href="classes.php" class="nav-item">
                    <i class="fas fa-school"></i>
                    <span>My Classes</span>
                </a>
                <a href="quizzes.php" class="nav-item">
                    <i class="fas fa-question-circle"></i>
                    <span>Quizzes</span>
                </a>
                <a href="assignments.php" class="nav-item">
                    <i class="fas fa-tasks"></i>
                    <span>Assignments</span>
                </a>
                <a href="grades.php" class="nav-item">
                    <i class="fas fa-chart-bar"></i>
                    <span>Grades</span>
                </a>
                <a href="students.php" class="nav-item">
                    <i class="fas fa-users"></i>
                    <span>Students</span>
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
                    <h1>Teacher Dashboard</h1>
                    <p>Welcome back, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</p>
                </div>
                <div class="header-right">
                    <div class="user-info">
                        <i class="fas fa-chalkboard-teacher"></i>
                        <span><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                    </div>
                </div>
            </header>


            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-school"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['total_classes']; ?></h3>
                        <p>My Classes</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['total_students']; ?></h3>
                        <p>Total Students</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-question-circle"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['total_quizzes']; ?></h3>
                        <p>Quizzes Created</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-tasks"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['total_assignments']; ?></h3>
                        <p>Assignments</p>
                    </div>
                </div>
            </div>

            <div class="content-grid" style="margin-top: 3rem;">
                <div class="content-card">
                    <div class="card-header">
                        <h3>My Class Assignments</h3>
                    </div>
                    <div class="card-content">
                        <?php if (empty($teacher_assignments)): ?>
                            <p style="color: #666; text-align: center; padding: 2rem;">
                                No class assignments yet. Contact admin to assign classes and subjects.
                            </p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Class</th>
                                            <th>Subject</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($teacher_assignments as $assignment): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($assignment['class_name']); ?></td>
                                                <td><?php echo htmlspecialchars($assignment['subject_name']); ?></td>
                                                <td>
                                                    <a href="class_details.php?id=<?php echo $assignment['assignment_id']; ?>"
                                                        class="btn btn-primary"
                                                        style="font-size: 0.8rem; padding: 0.3rem 0.6rem;">
                                                        View Details
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="content-card">
                    <div class="card-header">
                        <h3>Quick Actions</h3>
                    </div>
                    <div class="card-content">
                        <div class="quick-actions">
                            <a href="quizzes.php?action=create" class="action-btn">
                                <i class="fas fa-plus-circle"></i>
                                <span>Create Quiz</span>
                            </a>
                            <a href="assignments.php?action=create" class="action-btn">
                                <i class="fas fa-file-plus"></i>
                                <span>New Assignment</span>
                            </a>
                            <a href="grades.php" class="action-btn">
                                <i class="fas fa-chart-line"></i>
                                <span>View Grades</span>
                            </a>
                            <a href="students.php" class="action-btn">
                                <i class="fas fa-users"></i>
                                <span>My Students</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>

</html>