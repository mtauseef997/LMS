<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'teacher') {
    header('Location: ../login.php');
    exit;
}

$teacher_id = $_SESSION['user_id'];
$quiz_id = intval($_GET['quiz_id'] ?? 0);

if ($quiz_id <= 0) {
    header('Location: manage_quiz.php');
    exit;
}

$quiz_query = "SELECT q.*, s.name as subject_name, c.name as class_name
               FROM quizzes q
               JOIN subjects s ON q.subject_id = s.id
               JOIN classes c ON q.class_id = c.id
               WHERE q.id = ? AND q.teacher_id = ?";
$quiz_stmt = $conn->prepare($quiz_query);
$quiz_stmt->bind_param("ii", $quiz_id, $teacher_id);
$quiz_stmt->execute();
$quiz_result = $quiz_stmt->get_result();
$quiz = $quiz_result->fetch_assoc();

if (!$quiz) {
    header('Location: manage_quiz.php');
    exit;
}

$submissions_query = "SELECT qs.*, u.name as student_name, u.email as student_email
                     FROM quiz_submissions qs
                     JOIN users u ON qs.student_id = u.id
                     WHERE qs.quiz_id = ?
                     ORDER BY qs.percentage DESC, qs.submitted_at ASC";
$submissions_stmt = $conn->prepare($submissions_query);
$submissions_stmt->bind_param("i", $quiz_id);
$submissions_stmt->execute();
$submissions_result = $submissions_stmt->get_result();
$submissions = $submissions_result->fetch_all(MYSQLI_ASSOC);

$total_students_query = "SELECT COUNT(*) as total FROM student_class WHERE class_id = ?";
$total_stmt = $conn->prepare($total_students_query);
$total_stmt->bind_param("i", $quiz['class_id']);
$total_stmt->execute();
$total_students = $total_stmt->get_result()->fetch_assoc()['total'];

$stats = [
    'total_submissions' => count($submissions),
    'total_students' => $total_students,
    'completion_rate' => $total_students > 0 ? (count($submissions) / $total_students) * 100 : 0,
    'average_score' => count($submissions) > 0 ? array_sum(array_column($submissions, 'percentage')) / count($submissions) : 0,
    'highest_score' => count($submissions) > 0 ? max(array_column($submissions, 'percentage')) : 0,
    'lowest_score' => count($submissions) > 0 ? min(array_column($submissions, 'percentage')) : 0
];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Results - <?php echo htmlspecialchars($quiz['title']); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/teacher.css">
    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 12px;
            text-align: center;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .grade-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-weight: bold;
            font-size: 0.8rem;
        }

        .grade-a {
            background: #dcfce7;
            color: #166534;
        }

        .grade-b {
            background: #dbeafe;
            color: #1e40af;
        }

        .grade-c {
            background: #fef3c7;
            color: #92400e;
        }

        .grade-d {
            background: #fee2e2;
            color: #991b1b;
        }

        .grade-f {
            background: #fecaca;
            color: #7f1d1d;
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
                <a href="manage_quiz.php" class="nav-item active">
                    <i class="fas fa-question-circle"></i>
                    <span>Manage Quizzes</span>
                </a>
                <a href="manage_assignment.php" class="nav-item">
                    <i class="fas fa-tasks"></i>
                    <span>Manage Assignments</span>
                </a>
                <a href="view_submissions.php" class="nav-item">
                    <i class="fas fa-clipboard-list"></i>
                    <span>View Submissions</span>
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
                    <h1>Quiz Results</h1>
                    <p><?php echo htmlspecialchars($quiz['title']); ?> - <?php echo htmlspecialchars($quiz['subject_name']); ?> (<?php echo htmlspecialchars($quiz['class_name']); ?>)</p>
                </div>
                <div class="header-right">
                    <a href="manage_quiz.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Quizzes
                    </a>
                    <a href="quiz_questions.php?quiz_id=<?php echo $quiz_id; ?>" class="btn btn-primary">
                        <i class="fas fa-list"></i> View Questions
                    </a>
                </div>
            </header>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['total_submissions']; ?>/<?php echo $stats['total_students']; ?></div>
                    <div class="stat-label">Submissions</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format($stats['completion_rate'], 1); ?>%</div>
                    <div class="stat-label">Completion Rate</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format($stats['average_score'], 1); ?>%</div>
                    <div class="stat-label">Average Score</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format($stats['highest_score'], 1); ?>%</div>
                    <div class="stat-label">Highest Score</div>
                </div>
            </div>

            <div class="content-card">
                <div class="card-header">
                    <h3>Student Results (<?php echo count($submissions); ?>)</h3>
                </div>
                <div class="card-content">
                    <?php if (empty($submissions)): ?>
                        <p style="text-align: center; color: #666; padding: 2rem;">
                            No submissions yet for this quiz.
                        </p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Email</th>
                                        <th>Score</th>
                                        <th>Percentage</th>
                                        <th>Grade</th>
                                        <th>Submitted</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($submissions as $submission): ?>
                                        <?php
                                        $percentage = $submission['percentage'];
                                        $letter_grade = '';
                                        $grade_class = '';
                                        if ($percentage >= 90) {
                                            $letter_grade = 'A';
                                            $grade_class = 'grade-a';
                                        } elseif ($percentage >= 80) {
                                            $letter_grade = 'B';
                                            $grade_class = 'grade-b';
                                        } elseif ($percentage >= 70) {
                                            $letter_grade = 'C';
                                            $grade_class = 'grade-c';
                                        } elseif ($percentage >= 60) {
                                            $letter_grade = 'D';
                                            $grade_class = 'grade-d';
                                        } else {
                                            $letter_grade = 'F';
                                            $grade_class = 'grade-f';
                                        }
                                        ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($submission['student_name']); ?></td>
                                            <td><?php echo htmlspecialchars($submission['student_email']); ?></td>
                                            <td><?php echo $submission['score']; ?>/<?php echo $quiz['total_marks']; ?></td>
                                            <td><?php echo number_format($percentage, 1); ?>%</td>
                                            <td><span class="grade-badge <?php echo $grade_class; ?>"><?php echo $letter_grade; ?></span></td>
                                            <td><?php echo date('M j, Y g:i A', strtotime($submission['submitted_at'])); ?></td>
                                            <td>
                                                <a href="view_student_answers.php?quiz_id=<?php echo $quiz_id; ?>&student_id=<?php echo $submission['student_id']; ?>"
                                                    class="btn-icon btn-primary" title="View Answers">
                                                    <i class="fas fa-eye"></i>
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
        </main>
    </div>
</body>

</html>