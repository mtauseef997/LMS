<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student') {
    header('Location: ../login.php');
    exit;
}

$student_id = $_SESSION['user_id'];

$subject_filter = $_GET['subject'] ?? '';

$subjects_query = "SELECT DISTINCT s.id, s.name 
                  FROM subjects s
                  JOIN teacher_subject_class tsc ON s.id = tsc.subject_id
                  JOIN student_class sc ON tsc.class_id = sc.class_id
                  WHERE sc.student_id = ?
                  ORDER BY s.name";
$subjects_stmt = $conn->prepare($subjects_query);
$subjects_stmt->bind_param("i", $student_id);
$subjects_stmt->execute();
$subjects_result = $subjects_stmt->get_result();
$subjects = $subjects_result->fetch_all(MYSQLI_ASSOC);

$quiz_grades_query = "SELECT qs.*, q.title, q.total_marks as max_marks, s.name as subject_name, c.name as class_name
                     FROM quiz_submissions qs
                     JOIN quizzes q ON qs.quiz_id = q.id
                     JOIN subjects s ON q.subject_id = s.id
                     JOIN classes c ON q.class_id = c.id
                     JOIN student_class sc ON q.class_id = sc.class_id
                     WHERE qs.student_id = ? AND sc.student_id = ?";

$params = [$student_id, $student_id];
$types = "ii";

if (!empty($subject_filter)) {
    $quiz_grades_query .= " AND q.subject_id = ?";
    $params[] = $subject_filter;
    $types .= "i";
}

$quiz_grades_query .= " ORDER BY qs.submitted_at DESC";

$quiz_stmt = $conn->prepare($quiz_grades_query);
$quiz_stmt->bind_param($types, ...$params);
$quiz_stmt->execute();
$quiz_grades = $quiz_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$assignment_grades_query = "SELECT asub.*, a.title, a.max_marks, s.name as subject_name, c.name as class_name
                           FROM assignment_submissions asub
                           JOIN assignments a ON asub.assignment_id = a.id
                           JOIN subjects s ON a.subject_id = s.id
                           JOIN classes c ON a.class_id = c.id
                           JOIN student_class sc ON a.class_id = sc.class_id
                           WHERE asub.student_id = ? AND sc.student_id = ? AND asub.score IS NOT NULL";

$assignment_params = [$student_id, $student_id];
$assignment_types = "ii";

if (!empty($subject_filter)) {
    $assignment_grades_query .= " AND a.subject_id = ?";
    $assignment_params[] = $subject_filter;
    $assignment_types .= "i";
}

$assignment_grades_query .= " ORDER BY asub.submitted_at DESC";

$assignment_stmt = $conn->prepare($assignment_grades_query);
$assignment_stmt->bind_param($assignment_types, ...$assignment_params);
$assignment_stmt->execute();
$assignment_grades = $assignment_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$overall_stats_query = "SELECT 
    COUNT(DISTINCT qs.quiz_id) as total_quizzes,
    AVG(qs.percentage) as avg_quiz_percentage,
    COUNT(DISTINCT asub.assignment_id) as total_assignments,
    AVG((asub.score / a.max_marks) * 100) as avg_assignment_percentage
    FROM student_class sc
    LEFT JOIN quizzes q ON sc.class_id = q.class_id
    LEFT JOIN quiz_submissions qs ON q.id = qs.quiz_id AND qs.student_id = ?
    LEFT JOIN assignments a ON sc.class_id = a.class_id
    LEFT JOIN assignment_submissions asub ON a.id = asub.assignment_id AND asub.student_id = ? AND asub.score IS NOT NULL
    WHERE sc.student_id = ?";

$stats_stmt = $conn->prepare($overall_stats_query);
$stats_stmt->bind_param("iii", $student_id, $student_id, $student_id);
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Grades - EduLearn LMS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 12px;
            text-align: center;
        }
        .stat-number {
            font-size: 2.5rem;
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
        .grade-a { background: #dcfce7; color: #166534; }
        .grade-b { background: #dbeafe; color: #1e40af; }
        .grade-c { background: #fef3c7; color: #92400e; }
        .grade-d { background: #fee2e2; color: #991b1b; }
        .grade-f { background: #fecaca; color: #7f1d1d; }
    </style>
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
                    <h1>My Grades</h1>
                    <p>Track your academic performance</p>
                </div>
            </header>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['total_quizzes'] ?? 0; ?></div>
                    <div class="stat-label">Quizzes Completed</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format($stats['avg_quiz_percentage'] ?? 0, 1); ?>%</div>
                    <div class="stat-label">Average Quiz Score</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['total_assignments'] ?? 0; ?></div>
                    <div class="stat-label">Assignments Graded</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format($stats['avg_assignment_percentage'] ?? 0, 1); ?>%</div>
                    <div class="stat-label">Average Assignment Score</div>
                </div>
            </div>

            <div class="filters-section">
                <form method="GET" class="filters-form">
                    <div class="filter-group">
                        <label for="subject">Filter by Subject:</label>
                        <select name="subject" id="subject" onchange="this.form.submit()">
                            <option value="">All Subjects</option>
                            <?php foreach ($subjects as $subject): ?>
                            <option value="<?php echo $subject['id']; ?>" <?php echo $subject_filter == $subject['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($subject['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <?php if (!empty($subject_filter)): ?>
                    <a href="grades.php" class="btn btn-outline">
                        <i class="fas fa-times"></i> Clear Filter
                    </a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="content-card">
                <div class="card-header">
                    <h3>Quiz Grades</h3>
                </div>
                <div class="card-content">
                    <?php if (empty($quiz_grades)): ?>
                    <p style="text-align: center; color: #666; padding: 2rem;">
                        No quiz grades found.
                    </p>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Quiz Title</th>
                                    <th>Subject</th>
                                    <th>Class</th>
                                    <th>Score</th>
                                    <th>Percentage</th>
                                    <th>Grade</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($quiz_grades as $grade): ?>
                                <?php
                                $percentage = $grade['percentage'];
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
                                    <td><?php echo htmlspecialchars($grade['title']); ?></td>
                                    <td><?php echo htmlspecialchars($grade['subject_name']); ?></td>
                                    <td><?php echo htmlspecialchars($grade['class_name']); ?></td>
                                    <td><?php echo $grade['score']; ?>/<?php echo $grade['max_marks']; ?></td>
                                    <td><?php echo number_format($percentage, 1); ?>%</td>
                                    <td><span class="grade-badge <?php echo $grade_class; ?>"><?php echo $letter_grade; ?></span></td>
                                    <td><?php echo date('M j, Y', strtotime($grade['submitted_at'])); ?></td>
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
                    <h3>Assignment Grades</h3>
                </div>
                <div class="card-content">
                    <?php if (empty($assignment_grades)): ?>
                    <p style="text-align: center; color: #666; padding: 2rem;">
                        No assignment grades found.
                    </p>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Assignment Title</th>
                                    <th>Subject</th>
                                    <th>Class</th>
                                    <th>Score</th>
                                    <th>Percentage</th>
                                    <th>Grade</th>
                                    <th>Feedback</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($assignment_grades as $grade): ?>
                                <?php
                                $percentage = ($grade['score'] / $grade['max_marks']) * 100;
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
                                    <td><?php echo htmlspecialchars($grade['title']); ?></td>
                                    <td><?php echo htmlspecialchars($grade['subject_name']); ?></td>
                                    <td><?php echo htmlspecialchars($grade['class_name']); ?></td>
                                    <td><?php echo $grade['score']; ?>/<?php echo $grade['max_marks']; ?></td>
                                    <td><?php echo number_format($percentage, 1); ?>%</td>
                                    <td><span class="grade-badge <?php echo $grade_class; ?>"><?php echo $letter_grade; ?></span></td>
                                    <td><?php echo $grade['feedback'] ? htmlspecialchars($grade['feedback']) : '-'; ?></td>
                                    <td><?php echo date('M j, Y', strtotime($grade['submitted_at'])); ?></td>
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
