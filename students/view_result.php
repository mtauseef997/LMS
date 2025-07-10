<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student') {
    header('Location: ../login.php');
    exit;
}

$student_id = $_SESSION['user_id'];
$quiz_id = intval($_GET['quiz_id'] ?? 0);

if ($quiz_id <= 0) {
    header('Location: quizzes.php');
    exit;
}

$result_query = "SELECT qs.*, q.title, q.description, q.total_marks, q.time_limit,
                 s.name as subject_name, c.name as class_name, u.name as teacher_name
                 FROM quiz_submissions qs
                 JOIN quizzes q ON qs.quiz_id = q.id
                 JOIN subjects s ON q.subject_id = s.id
                 JOIN classes c ON q.class_id = c.id
                 JOIN users u ON q.teacher_id = u.id
                 WHERE qs.quiz_id = ? AND qs.student_id = ?";

$result_stmt = $conn->prepare($result_query);
$result_stmt->bind_param("ii", $quiz_id, $student_id);
$result_stmt->execute();
$result_data = $result_stmt->get_result();
$result = $result_data->fetch_assoc();

if (!$result) {
    header('Location: quizzes.php');
    exit;
}

$columns_check = $conn->query("SHOW COLUMNS FROM quiz_questions LIKE 'question_order'");
$has_question_order = $columns_check && $columns_check->num_rows > 0;


if ($has_question_order) {
    $questions_query = "SELECT * FROM quiz_questions WHERE quiz_id = ? ORDER BY question_order, id";
} else {
    $questions_query = "SELECT * FROM quiz_questions WHERE quiz_id = ? ORDER BY id";
}

$questions_stmt = $conn->prepare($questions_query);
$questions_stmt->bind_param("i", $quiz_id);
$questions_stmt->execute();
$questions_result = $questions_stmt->get_result();
$questions = $questions_result->fetch_all(MYSQLI_ASSOC);

$student_answers = json_decode($result['answers'], true) ?? [];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Result - <?php echo htmlspecialchars($result['title']); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
    .result-container {
        max-width: 900px;
        margin: 0 auto;
        padding: 2rem;
    }

    .result-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 2rem;
        border-radius: 12px;
        margin-bottom: 2rem;
        text-align: center;
    }

    .score-display {
        font-size: 3rem;
        font-weight: bold;
        margin: 1rem 0;
    }

    .score-details {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        margin: 2rem 0;
    }

    .score-card {
        background: rgba(255, 255, 255, 0.1);
        padding: 1rem;
        border-radius: 8px;
        text-align: center;
    }

    .question-review {
        background: white;
        border-radius: 12px;
        padding: 2rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .question-number {
        width: 30px;
        height: 30px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        margin-bottom: 1rem;
        color: white;
    }

    .correct {
        background: #10b981;
    }

    .incorrect {
        background: #ef4444;
    }

    .question-text {
        font-size: 1.1rem;
        font-weight: 500;
        margin-bottom: 1rem;
        color: #333;
    }

    .answer-section {
        margin-top: 1rem;
    }

    .answer-label {
        font-weight: 600;
        margin-bottom: 0.5rem;
        display: block;
    }

    .student-answer {
        color: #ef4444;
        font-weight: 500;
    }

    .correct-answer {
        color: #10b981;
        font-weight: 500;
    }

    .grade-badge {
        display: inline-block;
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-weight: bold;
        margin-top: 1rem;
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

    .back-btn {
        background: #6b7280;
        color: white;
        padding: 0.75rem 1.5rem;
        border: none;
        border-radius: 8px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        margin-top: 2rem;
    }

    .back-btn:hover {
        background: #4b5563;
        color: white;
        text-decoration: none;
    }
    </style>
</head>

<body>
    <div class="result-container">
        <div class="result-header">
            <h1><?php echo htmlspecialchars($result['title']); ?></h1>
            <div class="score-display"><?php echo number_format($result['percentage'], 1); ?>%</div>
            <p>You scored <?php echo $result['score']; ?> out of <?php echo $result['total_marks']; ?> marks</p>

            <?php
            $grade = '';
            $gradeClass = '';
            if ($result['percentage'] >= 90) {
                $grade = 'A';
                $gradeClass = 'grade-a';
            } elseif ($result['percentage'] >= 80) {
                $grade = 'B';
                $gradeClass = 'grade-b';
            } elseif ($result['percentage'] >= 70) {
                $grade = 'C';
                $gradeClass = 'grade-c';
            } elseif ($result['percentage'] >= 60) {
                $grade = 'D';
                $gradeClass = 'grade-d';
            } else {
                $grade = 'F';
                $gradeClass = 'grade-f';
            }
            ?>
            <div class="grade-badge <?php echo $gradeClass; ?>">Grade: <?php echo $grade; ?></div>

            <div class="score-details">
                <div class="score-card">
                    <h3>Subject</h3>
                    <p><?php echo htmlspecialchars($result['subject_name']); ?></p>
                </div>
                <div class="score-card">
                    <h3>Class</h3>
                    <p><?php echo htmlspecialchars($result['class_name']); ?></p>
                </div>
                <div class="score-card">
                    <h3>Teacher</h3>
                    <p><?php echo htmlspecialchars($result['teacher_name']); ?></p>
                </div>
                <div class="score-card">
                    <h3>Submitted</h3>
                    <p><?php echo date('M j, Y g:i A', strtotime($result['submitted_at'])); ?></p>
                </div>
            </div>
        </div>

        <h2 style="margin-bottom: 1.5rem; color: #333;">Question Review</h2>

        <?php foreach ($questions as $index => $question): ?>
        <?php
            $student_answer = $student_answers[$question['id']] ?? '';
            $is_correct = strtolower(trim($student_answer)) === strtolower(trim($question['correct_answer']));
            ?>
        <div class="question-review">
            <div class="question-number <?php echo $is_correct ? 'correct' : 'incorrect'; ?>">
                <?php echo $index + 1; ?>
            </div>
            <div class="question-text">
                <?php echo htmlspecialchars($question['question_text']); ?>
                <span style="color: #667eea; font-weight: bold;">(<?php echo $question['marks']; ?> marks)</span>
            </div>

            <div class="answer-section">
                <div class="answer-label">Your Answer:</div>
                <div class="student-answer">
                    <?php echo $student_answer ? htmlspecialchars($student_answer) : '<em>No answer provided</em>'; ?>
                    <?php if ($is_correct): ?>
                    <i class="fas fa-check-circle" style="color: #10b981; margin-left: 0.5rem;"></i>
                    <?php else: ?>
                    <i class="fas fa-times-circle" style="color: #ef4444; margin-left: 0.5rem;"></i>
                    <?php endif; ?>
                </div>

                <?php if (!$is_correct): ?>
                <div style="margin-top: 0.5rem;">
                    <div class="answer-label">Correct Answer:</div>
                    <div class="correct-answer"><?php echo htmlspecialchars($question['correct_answer']); ?></div>
                </div>
                <?php endif; ?>

                <?php if (!empty($question['explanation'])): ?>
                <div style="margin-top: 1rem; padding: 1rem; background: #f8fafc; border-radius: 8px;">
                    <div class="answer-label">Explanation:</div>
                    <p style="margin: 0; color: #4b5563;"><?php echo htmlspecialchars($question['explanation']); ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>

        <div style="text-align: center;">
            <a href="quizzes.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Quizzes
            </a>
        </div>
    </div>
</body>

</html>