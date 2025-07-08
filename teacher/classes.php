<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'teacher') {
    header('Location: ../login.php');
    exit;
}

$teacher_id = $_SESSION['user_id'];


$classes_query = "SELECT tsc.id, c.name as class_name, s.name as subject_name, 
                         COUNT(DISTINCT sc.student_id) as student_count,
                         COUNT(DISTINCT a.id) as assignment_count,
                         COUNT(DISTINCT q.id) as quiz_count
                  FROM teacher_subject_class tsc
                  JOIN classes c ON tsc.class_id = c.id
                  JOIN subjects s ON tsc.subject_id = s.id
                  LEFT JOIN student_class sc ON c.id = sc.class_id
                  LEFT JOIN assignments a ON tsc.subject_id = a.subject_id AND tsc.class_id = a.class_id AND a.teacher_id = ?
                  LEFT JOIN quizzes q ON tsc.subject_id = q.subject_id AND tsc.class_id = q.class_id AND q.teacher_id = ?
                  WHERE tsc.teacher_id = ?
                  GROUP BY tsc.id, c.name, s.name
                  ORDER BY c.name, s.name";
$classes_stmt = $conn->prepare($classes_query);
$classes_stmt->bind_param("iii", $teacher_id, $teacher_id, $teacher_id);
$classes_stmt->execute();
$classes = $classes_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Classes - Teacher Panel</title>
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
                    <h1>My Classes</h1>
                    <p>Classes and subjects you teach</p>
                </div>
            </header>

            <div class="content-body">
                <?php if (empty($classes)): ?>
                    <div class="empty-state">
                        <i class="fas fa-school"></i>
                        <h3>No Classes Assigned</h3>
                        <p>You haven't been assigned to any classes yet. Contact your administrator.</p>
                    </div>
                <?php else: ?>
                    <div class="grid grid-3">
                        <?php foreach ($classes as $class): ?>
                            <div class="card">
                                <div class="card-header">
                                    <h3><?php echo htmlspecialchars($class['class_name']); ?></h3>
                                    <span class="subject-badge"><?php echo htmlspecialchars($class['subject_name']); ?></span>
                                </div>

                                <div class="card-body">
                                    <div class="class-stats">
                                        <div class="stat-item">
                                            <i class="fas fa-users"></i>
                                            <span><?php echo $class['student_count']; ?> Students</span>
                                        </div>
                                        <div class="stat-item">
                                            <i class="fas fa-tasks"></i>
                                            <span><?php echo $class['assignment_count']; ?> Assignments</span>
                                        </div>
                                        <div class="stat-item">
                                            <i class="fas fa-question-circle"></i>
                                            <span><?php echo $class['quiz_count']; ?> Quizzes</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="card-footer">
                                    <a href="assignments.php?class_id=<?php echo $class['id']; ?>"
                                        class="btn btn-sm btn-primary">
                                        <i class="fas fa-tasks"></i> Assignments
                                    </a>
                                    <a href="quizzes.php?class_id=<?php echo $class['id']; ?>" class="btn btn-sm btn-secondary">
                                        <i class="fas fa-question-circle"></i> Quizzes
                                    </a>
                                    <a href="students.php?class_id=<?php echo $class['id']; ?>" class="btn btn-sm btn-info">
                                        <i class="fas fa-users"></i> Students
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <style>
        .subject-badge {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.9) 0%, rgba(255, 255, 255, 0.7) 100%);
            color: #667eea;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 600;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .class-stats {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .stat-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: #6b7280;
            padding: 0.5rem;
            background: #f8fafc;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .stat-item:hover {
            background: #f1f5f9;
            transform: translateX(5px);
        }

        .stat-item i {
            width: 18px;
            color: #667eea;
            font-size: 1rem;
        }
    </style>
</body>

</html>