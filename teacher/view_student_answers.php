<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'teacher') {
    header('Location: ../login.php');
    exit;
}

$teacher_id = $_SESSION['user_id'];
$quiz_id = intval($_GET['quiz_id'] ?? 0);
$student_id = intval($_GET['student_id'] ?? 0);

if ($quiz_id <= 0 || $student_id <= 0) {
    header('Location: view_submissions.php');
    exit;
}

// Get quiz information
$quiz_query = "SELECT q.*, s.name as subject_name, c.name as class_name
               FROM quizzes q
               JOIN subjects s ON q.subject_id = s.id
               JOIN classes c ON q.class_id = c.id
               WHERE q.id = ? AND q.teacher_id = ?";
$quiz_stmt = $conn->prepare($quiz_query);
$quiz_stmt->bind_param("ii", $quiz_id, $teacher_id);
$quiz_stmt->execute();
$quiz = $quiz_stmt->get_result()->fetch_assoc();

if (!$quiz) {
    header('Location: view_submissions.php');
    exit;
}

// Get student information
$student_query = "SELECT * FROM users WHERE id = ? AND role = 'student'";
$student_stmt = $conn->prepare($student_query);
$student_stmt->bind_param("i", $student_id);
$student_stmt->execute();
$student = $student_stmt->get_result()->fetch_assoc();

if (!$student) {
    header('Location: view_submissions.php');
    exit;
}

// Get student's submission
$submission_query = "SELECT * FROM quiz_submissions WHERE quiz_id = ? AND student_id = ?";
$submission_stmt = $conn->prepare($submission_query);
$submission_stmt->bind_param("ii", $quiz_id, $student_id);
$submission_stmt->execute();
$submission = $submission_stmt->get_result()->fetch_assoc();

if (!$submission) {
    header('Location: view_submissions.php');
    exit;
}

// Get quiz questions with student answers
$questions_query = "SELECT * FROM quiz_questions WHERE quiz_id = ? ORDER BY id";
$questions_stmt = $conn->prepare($questions_query);
$questions_stmt->bind_param("i", $quiz_id);
$questions_stmt->execute();
$questions = $questions_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Parse student answers
$student_answers = json_decode($submission['answers'], true) ?? [];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Answers - <?php echo htmlspecialchars($quiz['title']); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        .answers-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 16px;
            margin-bottom: 2rem;
        }

        .student-info {
            background: white;
            padding: 1.5rem;
            border-radius: 16px;
            margin-bottom: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .info-item {
            text-align: center;
            padding: 1rem;
            background: #f8fafc;
            border-radius: 12px;
        }

        .info-value {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }

        .question-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            border-left: 4px solid #e5e7eb;
        }

        .question-card.correct {
            border-left-color: #10b981;
        }

        .question-card.incorrect {
            border-left-color: #ef4444;
        }

        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background: #f1f5f9;
            color: #475569;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
            margin-bottom: 1rem;
        }

        .back-button:hover {
            background: #e2e8f0;
            transform: translateY(-1px);
        }
    </style>
</head>

<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
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
                <a href="view_submissions.php" class="nav-item active">
                    <i class="fas fa-clipboard-list"></i>
                    <span>View Submissions</span>
                </a>
                <a href="students_dashboard.php" class="nav-item">
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

        <!-- Main Content -->
        <main class="main-content">
            <!-- Back Button -->
            <a href="view_submissions.php" class="back-button">
                <i class="fas fa-arrow-left"></i>
                Back to Submissions
            </a>

            <!-- Header -->
            <div class="answers-header">
                <h1><i class="fas fa-eye"></i> Student Answers Review</h1>
                <p><?php echo htmlspecialchars($quiz['title']); ?> - <?php echo htmlspecialchars($student['name']); ?></p>
            </div>

            <!-- Student Info -->
            <div class="student-info">
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-value"><?php echo htmlspecialchars($student['name']); ?></div>
                        <div class="info-label">Student Name</div>
                    </div>
                    <div class="info-item">
                        <div class="info-value"><?php echo $submission['score']; ?>/<?php echo $quiz['total_marks']; ?></div>
                        <div class="info-label">Score</div>
                    </div>
                    <div class="info-item">
                        <div class="info-value"><?php echo number_format($submission['percentage'], 1); ?>%</div>
                        <div class="info-label">Percentage</div>
                    </div>
                    <div class="info-item">
                        <div class="info-value"><?php echo $submission['time_taken']; ?> min</div>
                        <div class="info-label">Time Taken</div>
                    </div>
                    <div class="info-item">
                        <div class="info-value"><?php echo date('M j, Y g:i A', strtotime($submission['submitted_at'])); ?></div>
                        <div class="info-label">Submitted</div>
                    </div>
                </div>
            </div>

            <!-- Questions and Answers -->
            <div class="questions-section">
                <?php foreach ($questions as $index => $question): ?>
                    <?php
                    $question_number = $index + 1;
                    $student_answer = $student_answers[$question['id']] ?? '';
                    $correct_answer = $question['correct_answer'];
                    $is_correct = false;
                    
                    // Check if answer is correct based on question type
                    if ($question['question_type'] === 'multiple_choice') {
                        $is_correct = trim($student_answer) === trim($correct_answer);
                    } else {
                        // For other types, do a case-insensitive comparison
                        $is_correct = strtolower(trim($student_answer)) === strtolower(trim($correct_answer));
                    }
                    ?>
                    
                    <div class="question-card <?php echo $is_correct ? 'correct' : 'incorrect'; ?>">
                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                            <div style="background: #667eea; color: white; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 0.875rem;">
                                <?php echo $question_number; ?>
                            </div>
                            <div style="display: flex; align-items: center; gap: 1rem;">
                                <span style="padding: 0.25rem 0.75rem; border-radius: 12px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; <?php echo $is_correct ? 'background: #dcfce7; color: #166534;' : 'background: #fee2e2; color: #991b1b;'; ?>">
                                    <?php echo $is_correct ? 'Correct' : 'Incorrect'; ?>
                                </span>
                                <div style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.875rem; color: #6b7280;">
                                    <i class="fas fa-star"></i>
                                    <?php echo $is_correct ? $question['marks'] : 0; ?>/<?php echo $question['marks']; ?> marks
                                </div>
                            </div>
                        </div>

                        <div style="font-size: 1.1rem; font-weight: 600; color: #1f2937; margin-bottom: 1rem; line-height: 1.6;">
                            <?php echo nl2br(htmlspecialchars($question['question_text'])); ?>
                        </div>

                        <div style="margin-bottom: 1rem;">
                            <span style="font-weight: 600; color: #374151; margin-bottom: 0.5rem; display: block;">Student Answer:</span>
                            <div style="padding: 0.75rem; border-radius: 8px; background: #fef3c7; border: 1px solid #f59e0b; color: #92400e;">
                                <?php echo !empty($student_answer) ? nl2br(htmlspecialchars($student_answer)) : '<em>No answer provided</em>'; ?>
                            </div>
                        </div>

                        <div style="margin-bottom: 1rem;">
                            <span style="font-weight: 600; color: #374151; margin-bottom: 0.5rem; display: block;">Correct Answer:</span>
                            <div style="padding: 0.75rem; border-radius: 8px; background: #dcfce7; border: 1px solid #10b981; color: #166534;">
                                <?php echo nl2br(htmlspecialchars($correct_answer)); ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </main>
    </div>
</body>
</html>
