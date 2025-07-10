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

$student_stmt = $conn->prepare("SELECT * FROM users WHERE id = ? AND role = 'student'");
$student_stmt->bind_param("i", $student_id);
$student_stmt->execute();
$student = $student_stmt->get_result()->fetch_assoc();

if (!$student) {
    header('Location: view_students.php');
    exit;
}

$access_stmt = $conn->prepare("SELECT COUNT(*) AS count FROM student_class sc
    JOIN teacher_subject_class tsc ON sc.class_id = tsc.class_id
    WHERE sc.student_id = ? AND tsc.teacher_id = ?");
$access_stmt->bind_param("ii", $student_id, $teacher_id);
$access_stmt->execute();
$access = $access_stmt->get_result()->fetch_assoc();

if ($access['count'] == 0) {
    header('Location: view_students.php');
    exit;
}

$quiz_stmt = $conn->prepare("SELECT q.title, q.total_marks, qs.score, qs.percentage, qs.submitted_at, 
    s.name as subject_name, c.name as class_name
    FROM quiz_submissions qs
    JOIN quizzes q ON qs.quiz_id = q.id
    JOIN subjects s ON q.subject_id = s.id
    JOIN classes c ON q.class_id = c.id
    WHERE qs.student_id = ? AND q.teacher_id = ?
    ORDER BY qs.submitted_at DESC");
$quiz_stmt->bind_param("ii", $student_id, $teacher_id);
$quiz_stmt->execute();
$quiz_grades = $quiz_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$assign_stmt = $conn->prepare("SELECT a.title, a.max_marks, asub.score as marks, asub.submitted_at,
    s.name as subject_name, c.name as class_name
    FROM assignment_submissions asub
    JOIN assignments a ON asub.assignment_id = a.id
    JOIN subjects s ON a.subject_id = s.id
    JOIN classes c ON a.class_id = c.id
    WHERE asub.student_id = ? AND a.teacher_id = ?
    ORDER BY asub.submitted_at DESC");
$assign_stmt->bind_param("ii", $student_id, $teacher_id);
$assign_stmt->execute();
$assignment_grades = $assign_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$quiz_total = $quiz_max = 0;
foreach ($quiz_grades as $q) {
    $quiz_total += $q['score'];
    $quiz_max += $q['total_marks'];
}
$quiz_average = $quiz_max > 0 ? ($quiz_total / $quiz_max) * 100 : 0;

$assign_total = $assign_max = 0;
foreach ($assignment_grades as $a) {
    $assign_total += $a['marks'];
    $assign_max += $a['max_marks'];
}
$assignment_average = $assign_max > 0 ? ($assign_total / $assign_max) * 100 : 0;

$overall_average = ($quiz_average + $assignment_average) / 2;

$total_quizzes = count($quiz_grades);
$total_assignments = count($assignment_grades);
$total_submissions = $total_quizzes + $total_assignments;

$total_quiz_score = 0;
$total_quiz_max = 0;
foreach ($quiz_grades as $quiz) {
    $total_quiz_score += $quiz['score'];
    $total_quiz_max += $quiz['total_marks'];
}

$total_assignment_score = 0;
$total_assignment_max = 0;
foreach ($assignment_grades as $assignment) {
    $total_assignment_score += $assignment['marks'];
    $total_assignment_max += $assignment['max_marks'];
}

$quiz_average = $total_quiz_max > 0 ? ($total_quiz_score / $total_quiz_max) * 100 : 0;
$assignment_average = $total_assignment_max > 0 ? ($total_assignment_score / $total_assignment_max) * 100 : 0;
$overall_average = ($quiz_average + $assignment_average) / 2;


$total_quizzes = count($quiz_grades);
$total_assignments = count($assignment_grades);
$total_submissions = $total_quizzes + $total_assignments;


?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($student['name']); ?> - Grades</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
    .grades-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 2rem;
        border-radius: 16px;
        margin-bottom: 2rem;
    }

    .grades-header h1 {
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
    }

    .summary-cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .summary-card {
        background: white;
        padding: 1.5rem;
        border-radius: 16px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        text-align: center;
    }

    .summary-icon {
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

    .summary-value {
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
    }

    .summary-label {
        color: #6b7280;
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .grades-section {
        background: white;
        border-radius: 16px;
        padding: 1.5rem;
        margin-bottom: 2rem;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    }

    .section-header {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 1.5rem;
        font-size: 1.25rem;
        font-weight: 700;
        color: #1f2937;
    }

    .grades-table {
        width: 100%;
        border-collapse: collapse;
    }

    .grades-table th,
    .grades-table td {
        padding: 1rem;
        text-align: left;
        border-bottom: 1px solid #e5e7eb;
    }

    .grades-table th {
        background: #f8fafc;
        font-weight: 600;
        color: #374151;
        text-transform: uppercase;
        font-size: 0.875rem;
        letter-spacing: 0.05em;
    }

    .grade-badge {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        border-radius: 12px;
        font-weight: 600;
        font-size: 0.875rem;
    }

    .grade-excellent {
        background: #dcfce7;
        color: #166534;
    }

    .grade-good {
        background: #dbeafe;
        color: #1e40af;
    }

    .grade-average {
        background: #fef3c7;
        color: #92400e;
    }

    .grade-poor {
        background: #fee2e2;
        color: #991b1b;
    }

    .empty-state {
        text-align: center;
        padding: 3rem;
        color: #6b7280;
    }

    .empty-state i {
        font-size: 3rem;
        color: #d1d5db;
        margin-bottom: 1rem;
    }

    @media (max-width: 768px) {
        .grades-table {
            font-size: 0.875rem;
        }

        .grades-table th,
        .grades-table td {
            padding: 0.75rem 0.5rem;
        }
    }
    </style>
</head>

<body>

    <?php echo count($quiz_grades) + count($assignment_grades); ?>

    <?php echo $total_submissions; ?>

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
                <a href="student_profile.php?id=<?php echo $student_id; ?>" class="btn btn-primary">
                    <i class="fas fa-user"></i> View Profile
                </a>
            </div>


            <div class="grades-header">
                <h1><i class="fas fa-chart-bar"></i> Grades for <?php echo htmlspecialchars($student['name']); ?></h1>
                <p>Complete academic performance overview</p>
            </div>


            <div class="summary-cards">
                <div class="summary-card">
                    <div class="summary-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="summary-value"
                        style="color: <?php echo $overall_average >= 80 ? '#16a34a' : ($overall_average >= 60 ? '#f59e0b' : '#dc2626'); ?>">
                        <?php echo number_format($overall_average, 1); ?>%
                    </div>
                    <div class="summary-label">Overall Average</div>
                </div>

                <div class="summary-card">
                    <div class="summary-icon">
                        <i class="fas fa-question-circle"></i>
                    </div>
                    <div class="summary-value"
                        style="color: <?php echo $quiz_average >= 80 ? '#16a34a' : ($quiz_average >= 60 ? '#f59e0b' : '#dc2626'); ?>">
                        <?php echo number_format($quiz_average, 1); ?>%
                    </div>
                    <div class="summary-label">Quiz Average</div>
                </div>

                <div class="summary-card">
                    <div class="summary-icon">
                        <i class="fas fa-tasks"></i>
                    </div>
                    <div class="summary-value"
                        style="color: <?php echo $assignment_average >= 80 ? '#16a34a' : ($assignment_average >= 60 ? '#f59e0b' : '#dc2626'); ?>">
                        <?php echo number_format($assignment_average, 1); ?>%
                    </div>
                    <div class="summary-label">Assignment Average</div>
                </div>

                <div class="summary-card">
                    <div class="summary-icon">
                        <i class="fas fa-clipboard-check"></i>
                    </div>
                    <div class="summary-value">
                        <?php echo count($quiz_grades) + count($assignment_grades); ?>
                    </div>
                    <div class="summary-label">Total Submissions</div>
                </div>
            </div>


            <div class="grades-section">
                <div class="section-header">
                    <i class="fas fa-question-circle"></i>
                    Quiz Grades (<?php echo count($quiz_grades); ?>)
                </div>

                <?php if (empty($quiz_grades)): ?>
                <div class="empty-state">
                    <i class="fas fa-question-circle"></i>
                    <h3>No Quiz Submissions</h3>
                    <p>This student hasn't submitted any quizzes yet.</p>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="grades-table">
                        <thead>
                            <tr>
                                <th>Quiz Title</th>
                                <th>Subject</th>
                                <th>Class</th>
                                <th>Score</th>
                                <th>Percentage</th>
                                <th>Grade</th>
                                <th>Submitted</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($quiz_grades as $quiz): ?>
                            <?php
                                    $percentage = $quiz['percentage'];
                                    $grade_class = 'grade-poor';
                                    $letter_grade = 'F';

                                    if ($percentage >= 90) {
                                        $grade_class = 'grade-excellent';
                                        $letter_grade = 'A';
                                    } elseif ($percentage >= 80) {
                                        $grade_class = 'grade-good';
                                        $letter_grade = 'B';
                                    } elseif ($percentage >= 70) {
                                        $grade_class = 'grade-average';
                                        $letter_grade = 'C';
                                    } elseif ($percentage >= 60) {
                                        $grade_class = 'grade-average';
                                        $letter_grade = 'D';
                                    }
                                    ?>
                            <tr>
                                <td><?php echo htmlspecialchars($quiz['title']); ?></td>
                                <td><?php echo htmlspecialchars($quiz['subject_name']); ?></td>
                                <td><?php echo htmlspecialchars($quiz['class_name']); ?></td>
                                <td><?php echo $quiz['score']; ?>/<?php echo $quiz['total_marks']; ?></td>
                                <td><?php echo number_format($percentage, 1); ?>%</td>
                                <td><span
                                        class="grade-badge <?php echo $grade_class; ?>"><?php echo $letter_grade; ?></span>
                                </td>
                                <td><?php echo date('M j, Y g:i A', strtotime($quiz['submitted_at'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>


            <div class="grades-section">
                <div class="section-header">
                    <i class="fas fa-tasks"></i>
                    Assignment Grades (<?php echo count($assignment_grades); ?>)
                </div>

                <?php if (empty($assignment_grades)): ?>
                <div class="empty-state">
                    <i class="fas fa-tasks"></i>
                    <h3>No Assignment Submissions</h3>
                    <p>This student hasn't submitted any assignments yet.</p>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="grades-table">
                        <thead>
                            <tr>
                                <th>Assignment Title</th>
                                <th>Subject</th>
                                <th>Class</th>
                                <th>Score</th>
                                <th>Percentage</th>
                                <th>Grade</th>
                                <th>Submitted</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($assignment_grades as $assignment): ?>
                            <?php
                                    $percentage = ($assignment['marks'] / $assignment['max_marks']) * 100;
                                    $grade_class = 'grade-poor';
                                    $letter_grade = 'F';

                                    if ($percentage >= 90) {
                                        $grade_class = 'grade-excellent';
                                        $letter_grade = 'A';
                                    } elseif ($percentage >= 80) {
                                        $grade_class = 'grade-good';
                                        $letter_grade = 'B';
                                    } elseif ($percentage >= 70) {
                                        $grade_class = 'grade-average';
                                        $letter_grade = 'C';
                                    } elseif ($percentage >= 60) {
                                        $grade_class = 'grade-average';
                                        $letter_grade = 'D';
                                    }
                                    ?>
                            <tr>
                                <td><?php echo htmlspecialchars($assignment['title']); ?></td>
                                <td><?php echo htmlspecialchars($assignment['subject_name']); ?></td>
                                <td><?php echo htmlspecialchars($assignment['class_name']); ?></td>
                                <td><?php echo $assignment['marks']; ?>/<?php echo $assignment['max_marks']; ?></td>
                                <td><?php echo number_format($percentage, 1); ?>%</td>
                                <td><span
                                        class="grade-badge <?php echo $grade_class; ?>"><?php echo $letter_grade; ?></span>
                                </td>
                                <td><?php echo date('M j, Y g:i A', strtotime($assignment['submitted_at'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>

</html>