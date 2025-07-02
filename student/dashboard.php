<?php
session_start();
require_once '../config/db.php';

// Check if user is logged in and is student
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student') {
    header('Location: ../login.php');
    exit;
}

$student_id = $_SESSION['user_id'];

// Get student's enrolled classes
$enrolled_classes = [];
$query = "SELECT c.name as class_name, c.id as class_id
          FROM student_class sc
          JOIN classes c ON sc.class_id = c.id
          WHERE sc.student_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $enrolled_classes[] = $row;
}

// Get statistics
$stats = [];

// Total enrolled classes
$stats['total_classes'] = count($enrolled_classes);

// Total available quizzes
$query = "SELECT COUNT(DISTINCT q.id) as count
          FROM quizzes q
          JOIN teacher_subject_class tsc ON q.teacher_id = tsc.teacher_id
          JOIN student_class sc ON tsc.class_id = sc.class_id
          WHERE sc.student_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$stats['available_quizzes'] = $result->fetch_assoc()['count'];

// Total completed quizzes
$query = "SELECT COUNT(*) as count FROM quiz_submissions WHERE student_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$stats['completed_quizzes'] = $result->fetch_assoc()['count'];

// Total assignments
$query = "SELECT COUNT(DISTINCT a.id) as count
          FROM assignments a
          JOIN teacher_subject_class tsc ON a.teacher_id = tsc.teacher_id
          JOIN student_class sc ON tsc.class_id = sc.class_id
          WHERE sc.student_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$stats['total_assignments'] = $result->fetch_assoc()['count'];

// Get recent quizzes
$recent_quizzes = [];
$query = "SELECT q.title, q.created_at,
          CASE WHEN qs.id IS NOT NULL THEN 'Completed' ELSE 'Available' END as status
          FROM quizzes q
          JOIN teacher_subject_class tsc ON q.teacher_id = tsc.teacher_id
          JOIN student_class sc ON tsc.class_id = sc.class_id
          LEFT JOIN quiz_submissions qs ON q.id = qs.quiz_id AND qs.student_id = ?
          WHERE sc.student_id = ?
          ORDER BY q.created_at DESC LIMIT 5";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $student_id, $student_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $recent_quizzes[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - EduLearn LMS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>

<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-graduation-cap"></i> EduLearn</h2>
                <p>Student Panel</p>
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
                    <span>My Grades</span>
                </a>
            </nav>

            <div class="sidebar-footer">
                <a href="../logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <header class="content-header">
                <div class="header-left">
                    <h1>Student Dashboard</h1>
                    <p>Welcome back, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</p>
                </div>
                <div class="header-right">
                    <div class="user-info">
                        <i class="fas fa-user-graduate"></i>
                        <span><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                    </div>
                </div>
            </header>

            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-school"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['total_classes']; ?></h3>
                        <p>Enrolled Classes</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-question-circle"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['available_quizzes']; ?></h3>
                        <p>Available Quizzes</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['completed_quizzes']; ?></h3>
                        <p>Completed Quizzes</p>
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

            <!-- Content Grid -->
            <div class="content-grid" style="margin-top: 3rem;">
                <div class="content-card">
                    <div class="card-header">
                        <h3>My Classes</h3>
                        <a href="classes.php" class="btn btn-primary">View All</a>
                    </div>
                    <div class="card-content">
                        <?php if (empty($enrolled_classes)): ?>
                            <p style="color: #666; text-align: center; padding: 2rem;">
                                You are not enrolled in any classes yet. Contact admin for enrollment.
                            </p>
                        <?php else: ?>
                            <div class="class-list">
                                <?php foreach ($enrolled_classes as $class): ?>
                                    <div class="class-item" style="padding: 1rem; border: 1px solid rgba(0,0,0,0.1); border-radius: 8px; margin-bottom: 1rem;">
                                        <h4 style="margin: 0 0 0.5rem 0; color: #333;"><?php echo htmlspecialchars($class['class_name']); ?></h4>
                                        <a href="class_details.php?id=<?php echo $class['class_id']; ?>" class="btn btn-primary" style="font-size: 0.8rem; padding: 0.3rem 0.6rem;">
                                            View Details
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="content-card">
                    <div class="card-header">
                        <h3>Recent Quizzes</h3>
                        <a href="quizzes.php" class="btn btn-primary">View All</a>
                    </div>
                    <div class="card-content">
                        <?php if (empty($recent_quizzes)): ?>
                            <p style="color: #666; text-align: center; padding: 2rem;">
                                No quizzes available yet.
                            </p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Quiz Title</th>
                                            <th>Status</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_quizzes as $quiz): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($quiz['title']); ?></td>
                                                <td>
                                                    <span class="badge badge-<?php echo $quiz['status'] === 'Completed' ? 'teacher' : 'student'; ?>">
                                                        <?php echo $quiz['status']; ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('M j, Y', strtotime($quiz['created_at'])); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>

</html>