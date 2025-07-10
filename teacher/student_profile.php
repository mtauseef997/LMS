<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'teacher') {
    header('Location: ../login.php');
    exit;
}

$teacher_id = $_SESSION['user_id'];
$student_id = intval($_GET['id'] ?? 0);

if ($student_id <= 0) {
    header('Location: view_students.php');
    exit;
}

$student_query = "SELECT u.*, 
                  COUNT(DISTINCT sc.class_id) as total_classes,
                  COUNT(DISTINCT qs.id) as total_quiz_submissions,
                  AVG(qs.percentage) as avg_quiz_score,
                  COUNT(DISTINCT asub.id) as total_assignment_submissions,
                  AVG(asub.marks) as avg_assignment_score
                  FROM users u
                  LEFT JOIN student_class sc ON u.id = sc.student_id
                  LEFT JOIN quiz_submissions qs ON u.id = qs.student_id
                  LEFT JOIN assignment_submissions asub ON u.id = asub.student_id
                  WHERE u.id = ? AND u.role = 'student'
                  GROUP BY u.id";

$student_stmt = $conn->prepare($student_query);
$student_stmt->bind_param("i", $student_id);
$student_stmt->execute();
$student = $student_stmt->get_result()->fetch_assoc();

if (!$student) {
    header('Location: view_students.php');
    exit;
}


$access_query = "SELECT COUNT(*) as count FROM student_class sc
                 JOIN teacher_subject_class tsc ON sc.class_id = tsc.class_id
                 WHERE sc.student_id = ? AND tsc.teacher_id = ?";
$access_stmt = $conn->prepare($access_query);
$access_stmt->bind_param("ii", $student_id, $teacher_id);
$access_stmt->execute();
$access = $access_stmt->get_result()->fetch_assoc();

if ($access['count'] == 0) {
    header('Location: view_students.php');
    exit;
}


$classes_query = "SELECT c.name as class_name, s.name as subject_name
                  FROM student_class sc
                  JOIN classes c ON sc.class_id = c.id
                  JOIN teacher_subject_class tsc ON c.id = tsc.class_id
                  JOIN subjects s ON tsc.subject_id = s.id
                  WHERE sc.student_id = ? AND tsc.teacher_id = ?";
$classes_stmt = $conn->prepare($classes_query);
$classes_stmt->bind_param("ii", $student_id, $teacher_id);
$classes_stmt->execute();
$classes = $classes_stmt->get_result()->fetch_all(MYSQLI_ASSOC);


$recent_quizzes_query = "SELECT q.title, qs.percentage, qs.submitted_at, s.name as subject_name
                         FROM quiz_submissions qs
                         JOIN quizzes q ON qs.quiz_id = q.id
                         JOIN subjects s ON q.subject_id = s.id
                         WHERE qs.student_id = ? AND q.teacher_id = ?
                         ORDER BY qs.submitted_at DESC
                         LIMIT 5";
$recent_quizzes_stmt = $conn->prepare($recent_quizzes_query);
$recent_quizzes_stmt->bind_param("ii", $student_id, $teacher_id);
$recent_quizzes_stmt->execute();
$recent_quizzes = $recent_quizzes_stmt->get_result()->fetch_all(MYSQLI_ASSOC);


$recent_assignments_query = "SELECT a.title, asub.score as marks, a.max_marks, asub.submitted_at, s.name as subject_name
                            FROM assignment_submissions asub
                            JOIN assignments a ON asub.assignment_id = a.id
                            JOIN subjects s ON a.subject_id = s.id
                            WHERE asub.student_id = ? AND a.teacher_id = ?
                            ORDER BY asub.submitted_at DESC
                            LIMIT 5";
$recent_assignments_stmt = $conn->prepare($recent_assignments_query);
$recent_assignments_stmt->bind_param("ii", $student_id, $teacher_id);
$recent_assignments_stmt->execute();
$recent_assignments = $recent_assignments_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($student['name']); ?> - Student Profile</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
    .profile-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 2rem;
        border-radius: 16px;
        margin-bottom: 2rem;
        display: flex;
        align-items: center;
        gap: 2rem;
    }

    .profile-avatar {
        width: 120px;
        height: 120px;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 3rem;
        font-weight: 700;
        backdrop-filter: blur(10px);
        border: 4px solid rgba(255, 255, 255, 0.3);
    }

    .profile-info h1 {
        font-size: 2.5rem;
        font-weight: 800;
        margin-bottom: 0.5rem;
    }

    .profile-info .email {
        font-size: 1.2rem;
        opacity: 0.9;
        margin-bottom: 1rem;
    }

    .profile-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .stat-card {
        background: white;
        padding: 1.5rem;
        border-radius: 16px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        text-align: center;
    }

    .stat-icon {
        width: 60px;
        height: 60px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.5rem;
        margin: 0 auto 1rem;
    }

    .stat-value {
        font-size: 2rem;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 0.5rem;
    }

    .stat-label {
        color: #6b7280;
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .content-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 2rem;
    }

    .content-card {
        background: white;
        border-radius: 16px;
        padding: 1.5rem;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    }

    .content-card h3 {
        font-size: 1.25rem;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .class-list,
    .activity-list {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }

    .class-item {
        background: #f8fafc;
        padding: 1rem;
        border-radius: 12px;
        border-left: 4px solid #667eea;
    }

    .activity-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem;
        background: #f8fafc;
        border-radius: 12px;
    }

    .activity-score {
        font-weight: 600;
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.875rem;
    }

    .score-excellent {
        background: #dcfce7;
        color: #166534;
    }

    .score-good {
        background: #dbeafe;
        color: #1e40af;
    }

    .score-average {
        background: #fef3c7;
        color: #92400e;
    }

    .score-poor {
        background: #fee2e2;
        color: #991b1b;
    }

    @media (max-width: 768px) {
        .content-grid {
            grid-template-columns: 1fr;
        }

        .profile-header {
            flex-direction: column;
            text-align: center;
        }
    }
    </style>
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
                <a href="manage_quiz.php" class="nav-item">
                    <i class="fas fa-question-circle"></i>
                    <span>Manage Quizzes</span>
                </a>
                <a href="manage_assignment.php" class="nav-item">
                    <i class="fas fa-tasks"></i>
                    <span>Manage Assignments</span>
                </a>
                <a href="view_students.php" class="nav-item active">
                    <i class="fas fa-users"></i>
                    <span>My Students</span>
                </a>
                <a href="grades.php" class="nav-item">
                    <i class="fas fa-chart-bar"></i>
                    <span>Grades & Reports</span>
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

            <div style="margin-bottom: 1rem;">
                <a href="view_students.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Students
                </a>
            </div>


            <div class="profile-header">
                <div class="profile-avatar">
                    <?php echo strtoupper(substr($student['name'], 0, 1)); ?>
                </div>
                <div class="profile-info">
                    <h1><?php echo htmlspecialchars($student['name']); ?></h1>
                    <p class="email"><?php echo htmlspecialchars($student['email']); ?></p>
                    <p><i class="fas fa-calendar-alt"></i> Joined
                        <?php echo date('F j, Y', strtotime($student['created_at'])); ?></p>
                </div>
            </div>


            <div class="profile-stats">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-book"></i>
                    </div>
                    <div class="stat-value"><?php echo $student['total_classes']; ?></div>
                    <div class="stat-label">Enrolled Classes</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-question-circle"></i>
                    </div>
                    <div class="stat-value"><?php echo $student['total_quiz_submissions']; ?></div>
                    <div class="stat-label">Quizzes Completed</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="stat-value"><?php echo number_format($student['avg_quiz_score'] ?? 0, 1); ?>%</div>
                    <div class="stat-label">Quiz Average</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-tasks"></i>
                    </div>
                    <div class="stat-value"><?php echo $student['total_assignment_submissions']; ?></div>
                    <div class="stat-label">Assignments Submitted</div>
                </div>
            </div>


            <div class="content-grid">

                <div class="content-card">
                    <h3><i class="fas fa-graduation-cap"></i> Enrolled Classes</h3>
                    <div class="class-list">
                        <?php if (empty($classes)): ?>
                        <p style="color: #6b7280; text-align: center; padding: 2rem;">No classes found</p>
                        <?php else: ?>
                        <?php foreach ($classes as $class): ?>
                        <div class="class-item">
                            <div style="font-weight: 600; color: #1f2937;">
                                <?php echo htmlspecialchars($class['class_name']); ?></div>
                            <div style="color: #6b7280; font-size: 0.875rem;">
                                <?php echo htmlspecialchars($class['subject_name']); ?></div>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>


                <div class="content-card">
                    <h3><i class="fas fa-chart-bar"></i> Recent Quiz Results</h3>
                    <div class="activity-list">
                        <?php if (empty($recent_quizzes)): ?>
                        <p style="color: #6b7280; text-align: center; padding: 2rem;">No quiz submissions yet</p>
                        <?php else: ?>
                        <?php foreach ($recent_quizzes as $quiz): ?>
                        <?php
                                $score_class = 'score-poor';
                                if ($quiz['percentage'] >= 90) $score_class = 'score-excellent';
                                elseif ($quiz['percentage'] >= 80) $score_class = 'score-good';
                                elseif ($quiz['percentage'] >= 70) $score_class = 'score-average';
                                ?>
                        <div class="activity-item">
                            <div>
                                <div style="font-weight: 600; color: #1f2937;">
                                    <?php echo htmlspecialchars($quiz['title']); ?></div>
                                <div style="color: #6b7280; font-size: 0.875rem;">
                                    <?php echo htmlspecialchars($quiz['subject_name']); ?> •
                                    <?php echo date('M j, Y', strtotime($quiz['submitted_at'])); ?></div>
                            </div>
                            <div class="activity-score <?php echo $score_class; ?>">
                                <?php echo number_format($quiz['percentage'], 1); ?>%
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>

</html>