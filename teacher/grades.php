<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'teacher') {
    header('Location: ../login.php');
    exit;
}

$teacher_id = $_SESSION['user_id'];


try {
    $assignment_grades_query = "SELECT u.name as student_name, u.email, a.title as assignment_title,
                               asub.score, a.max_marks, asub.submitted_at, asub.graded_at,
                               c.name as class_name, s.name as subject_name
                               FROM assignment_submissions asub
                               JOIN assignments a ON asub.assignment_id = a.id
                               JOIN users u ON asub.student_id = u.id
                               JOIN classes c ON a.class_id = c.id
                               JOIN subjects s ON a.subject_id = s.id
                               WHERE a.teacher_id = ? AND asub.score IS NOT NULL
                               ORDER BY asub.graded_at DESC";
    $assignment_stmt = $conn->prepare($assignment_grades_query);
    $assignment_stmt->bind_param("i", $teacher_id);
    $assignment_stmt->execute();
    $assignment_grades = $assignment_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    $assignment_grades = [];
}


try {
    $quiz_grades_query = "SELECT u.name as student_name, u.email, q.title as quiz_title,
                         qs.score, q.total_marks, qs.submitted_at,
                         c.name as class_name, s.name as subject_name
                         FROM quiz_submissions qs
                         JOIN quizzes q ON qs.quiz_id = q.id
                         JOIN users u ON qs.student_id = u.id
                         JOIN classes c ON q.class_id = c.id
                         JOIN subjects s ON q.subject_id = s.id
                         WHERE q.teacher_id = ?
                         ORDER BY qs.submitted_at DESC";
    $quiz_stmt = $conn->prepare($quiz_grades_query);
    $quiz_stmt->bind_param("i", $teacher_id);
    $quiz_stmt->execute();
    $quiz_grades = $quiz_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    $quiz_grades = [];
}

$total_quizzes = count($quiz_grades);
$avg_assignment_score = 0;
$avg_quiz_score = 0;

if ($total_assignments > 0) {
    $assignment_sum = 0;
    foreach ($assignment_grades as $grade) {
        if ($grade['max_marks'] > 0) {
            $assignment_sum += ($grade['score'] / $grade['max_marks']) * 100;
        }
    }
    $avg_assignment_score = $assignment_sum / $total_assignments;
}

if ($total_quizzes > 0) {
    $quiz_sum = 0;
    foreach ($quiz_grades as $grade) {
        if ($grade['total_marks'] > 0) {
            $quiz_sum += ($grade['score'] / $grade['total_marks']) * 100;
        }
    }
    $avg_quiz_score = $quiz_sum / $total_quizzes;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grades Overview - Teacher Panel</title>
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
                <a href="grades.php" class="nav-item active">
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
                    <h1>Grades Overview</h1>
                    <p>View and analyze student performance</p>
                </div>
            </header>

            <div class="content-body">

                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-tasks"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo $total_assignments; ?></h3>
                            <p>Graded Assignments</p>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-question-circle"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo $total_quizzes; ?></h3>
                            <p>Quiz Submissions</p>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo number_format($avg_assignment_score, 1); ?>%</h3>
                            <p>Avg Assignment Score</p>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-trophy"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo number_format($avg_quiz_score, 1); ?>%</h3>
                            <p>Avg Quiz Score</p>
                        </div>
                    </div>
                </div>


                <div class="content-card">
                    <div class="card-header">
                        <h3>Recent Assignment Grades</h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($assignment_grades)): ?>
                            <div class="empty-state">
                                <i class="fas fa-tasks"></i>
                                <p>No graded assignments yet.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-container">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Student</th>
                                            <th>Assignment</th>
                                            <th>Class/Subject</th>
                                            <th>Score</th>
                                            <th>Percentage</th>
                                            <th>Graded</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach (array_slice($assignment_grades, 0, 10) as $grade): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($grade['student_name']); ?></td>
                                                <td><?php echo htmlspecialchars($grade['assignment_title']); ?></td>
                                                <td><?php echo htmlspecialchars($grade['class_name'] . ' - ' . $grade['subject_name']); ?>
                                                </td>
                                                <td><?php echo $grade['score'] . '/' . $grade['max_marks']; ?></td>
                                                <td>
                                                    <?php
                                                    $percentage = ($grade['score'] / $grade['max_marks']) * 100;
                                                    $class = $percentage >= 80 ? 'excellent' : ($percentage >= 60 ? 'good' : 'needs-improvement');
                                                    ?>
                                                    <span class="grade-badge <?php echo $class; ?>">
                                                        <?php echo number_format($percentage, 1); ?>%
                                                    </span>
                                                </td>
                                                <td><?php echo date('M j, Y', strtotime($grade['graded_at'])); ?></td>
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
                        <h3>Recent Quiz Results</h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($quiz_grades)): ?>
                            <div class="empty-state">
                                <i class="fas fa-question-circle"></i>
                                <p>No quiz submissions yet.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-container">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Student</th>
                                            <th>Quiz</th>
                                            <th>Class/Subject</th>
                                            <th>Score</th>
                                            <th>Percentage</th>
                                            <th>Submitted</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach (array_slice($quiz_grades, 0, 10) as $grade): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($grade['student_name']); ?></td>
                                                <td><?php echo htmlspecialchars($grade['quiz_title']); ?></td>
                                                <td><?php echo htmlspecialchars($grade['class_name'] . ' - ' . $grade['subject_name']); ?>
                                                </td>
                                                <td><?php echo $grade['score'] . '/' . $grade['total_marks']; ?></td>
                                                <td>
                                                    <?php
                                                    $percentage = ($grade['score'] / $grade['total_marks']) * 100;
                                                    $class = $percentage >= 80 ? 'excellent' : ($percentage >= 60 ? 'good' : 'needs-improvement');
                                                    ?>
                                                    <span class="grade-badge <?php echo $class; ?>">
                                                        <?php echo number_format($percentage, 1); ?>%
                                                    </span>
                                                </td>
                                                <td><?php echo date('M j, Y', strtotime($grade['submitted_at'])); ?></td>
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

    <style>
        .table-container {
            overflow-x: auto;
            border-radius: 8px;
        }

        .grade-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 600;
        }

        .grade-badge.excellent {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            color: #065f46;
        }

        .grade-badge.good {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            color: #92400e;
        }

        .grade-badge.needs-improvement {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            color: #991b1b;
        }
    </style>
</body>

</html>