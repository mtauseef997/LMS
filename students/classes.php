<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student') {
    header('Location: ../login.php');
    exit;
}

$student_id = $_SESSION['user_id'];

$classes = [];
$query = "SELECT c.id, c.name, 
          (SELECT COUNT(*) FROM subjects s 
           JOIN teacher_subject_class tsc ON s.id = tsc.subject_id 
           WHERE tsc.class_id = c.id) as subject_count
          FROM classes c
          JOIN student_class sc ON c.id = sc.class_id
          WHERE sc.student_id = ?
          ORDER BY c.name";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $classes[] = $row;
}

$selected_class = null;
$class_subjects = [];
$class_teachers = [];

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $class_id = intval($_GET['id']);

    $check_query = "SELECT COUNT(*) as count FROM student_class WHERE student_id = ? AND class_id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("ii", $student_id, $class_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result()->fetch_assoc();

    if ($check_result['count'] > 0) {

        $class_query = "SELECT * FROM classes WHERE id = ?";
        $class_stmt = $conn->prepare($class_query);
        $class_stmt->bind_param("i", $class_id);
        $class_stmt->execute();
        $selected_class = $class_stmt->get_result()->fetch_assoc();

        $subjects_query = "SELECT DISTINCT s.id, s.name, 
                          (SELECT COUNT(*) FROM quizzes q WHERE q.subject_id = s.id AND q.class_id = ?) as quiz_count,
                          (SELECT COUNT(*) FROM assignments a WHERE a.subject_id = s.id AND a.class_id = ?) as assignment_count
                          FROM subjects s
                          JOIN teacher_subject_class tsc ON s.id = tsc.subject_id
                          WHERE tsc.class_id = ?";
        $subjects_stmt = $conn->prepare($subjects_query);
        $subjects_stmt->bind_param("iii", $class_id, $class_id, $class_id);
        $subjects_stmt->execute();
        $subjects_result = $subjects_stmt->get_result();
        while ($row = $subjects_result->fetch_assoc()) {
            $class_subjects[] = $row;
        }

        $teachers_query = "SELECT DISTINCT u.id, u.name, s.name as subject_name
                          FROM users u
                          JOIN teacher_subject_class tsc ON u.id = tsc.teacher_id
                          JOIN subjects s ON tsc.subject_id = s.id
                          WHERE tsc.class_id = ?
                          ORDER BY s.name";
        $teachers_stmt = $conn->prepare($teachers_query);
        $teachers_stmt->bind_param("i", $class_id);
        $teachers_stmt->execute();
        $teachers_result = $teachers_stmt->get_result();
        while ($row = $teachers_result->fetch_assoc()) {
            $class_teachers[] = $row;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Classes - EduLearn LMS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>

<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-graduation-cap"></i> EduLearn</h2>
                <p>Student Panel</p>
            </div>

            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-item">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
                <a href="classes.php" class="nav-item active">
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

        <main class="main-content">
            <header class="content-header">
                <div class="header-left">
                    <h1>My Classes</h1>
                    <p>View your enrolled classes and subjects</p>
                </div>
            </header>

            <?php if (empty($classes)): ?>
                <div class="alert alert-info">
                    <p>You are not enrolled in any classes yet. Please contact your administrator.</p>
                </div>
            <?php else: ?>

                <?php if ($selected_class): ?>

                    <div class="content-card">
                        <div class="card-header">
                            <div class="header-left">
                                <h3><?php echo htmlspecialchars($selected_class['name']); ?></h3>
                            </div>
                            <div class="header-right">
                                <a href="classes.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Back to Classes
                                </a>
                            </div>
                        </div>
                        <div class="card-content">
                            <div class="class-details">
                                <div class="detail-section">
                                    <h4><i class="fas fa-book"></i> Subjects</h4>
                                    <?php if (empty($class_subjects)): ?>
                                        <p>No subjects assigned to this class yet.</p>
                                    <?php else: ?>
                                        <div class="subjects-grid">
                                            <?php foreach ($class_subjects as $subject): ?>
                                                <div class="subject-card">
                                                    <h5><?php echo htmlspecialchars($subject['name']); ?></h5>
                                                    <div class="subject-stats">
                                                        <div class="stat">
                                                            <i class="fas fa-question-circle"></i>
                                                            <span><?php echo $subject['quiz_count']; ?> Quizzes</span>
                                                        </div>
                                                        <div class="stat">
                                                            <i class="fas fa-tasks"></i>
                                                            <span><?php echo $subject['assignment_count']; ?> Assignments</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="detail-section">
                                    <h4><i class="fas fa-chalkboard-teacher"></i> Teachers</h4>
                                    <?php if (empty($class_teachers)): ?>
                                        <p>No teachers assigned to this class yet.</p>
                                    <?php else: ?>
                                        <div class="teachers-list">
                                            <?php foreach ($class_teachers as $teacher): ?>
                                                <div class="teacher-item">
                                                    <div class="teacher-avatar">
                                                        <i class="fas fa-user"></i>
                                                    </div>
                                                    <div class="teacher-info">
                                                        <h5><?php echo htmlspecialchars($teacher['name']); ?></h5>
                                                        <p><?php echo htmlspecialchars($teacher['subject_name']); ?></p>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php else: ?>

                    <div class="classes-grid">
                        <?php foreach ($classes as $class): ?>
                            <div class="class-card">
                                <div class="class-header">
                                    <h3><?php echo htmlspecialchars($class['name']); ?></h3>
                                </div>
                                <div class="class-content">
                                    <div class="class-stats">
                                        <div class="stat">
                                            <i class="fas fa-book"></i>
                                            <span><?php echo $class['subject_count']; ?> Subjects</span>
                                        </div>
                                    </div>
                                    <a href="classes.php?id=<?php echo $class['id']; ?>" class="btn btn-primary">View Details</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

            <?php endif; ?>
        </main>
    </div>
</body>

</html>