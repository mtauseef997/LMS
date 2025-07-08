<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student') {
    header('Location: ../login.php');
    exit;
}

$student_id = $_SESSION['user_id'];
$quiz_id = intval($_GET['id'] ?? 0);

if ($quiz_id <= 0) {
    header('Location: quizzes.php');
    exit;
}

$quiz_query = "SELECT q.*, s.name as subject_name, c.name as class_name, u.name as teacher_name
               FROM quizzes q
               JOIN subjects s ON q.subject_id = s.id
               JOIN classes c ON q.class_id = c.id
               JOIN users u ON q.teacher_id = u.id
               JOIN student_class sc ON q.class_id = sc.class_id
               WHERE q.id = ? AND sc.student_id = ? AND q.is_active = 1";

$quiz_stmt = $conn->prepare($quiz_query);
$quiz_stmt->bind_param("ii", $quiz_id, $student_id);
$quiz_stmt->execute();
$quiz_result = $quiz_stmt->get_result();
$quiz = $quiz_result->fetch_assoc();

if (!$quiz) {
    header('Location: quizzes.php');
    exit;
}

$existing_submission_query = "SELECT id FROM quiz_submissions WHERE quiz_id = ? AND student_id = ?";
$existing_stmt = $conn->prepare($existing_submission_query);
$existing_stmt->bind_param("ii", $quiz_id, $student_id);
$existing_stmt->execute();
$existing_result = $existing_stmt->get_result();

if ($existing_result->num_rows > 0) {
    header('Location: view_result.php?quiz_id=' . $quiz_id);
    exit;
}

$questions_query = "SELECT * FROM quiz_questions WHERE quiz_id = ? ORDER BY question_order, id";
$questions_stmt = $conn->prepare($questions_query);
$questions_stmt->bind_param("i", $quiz_id);
$questions_stmt->execute();
$questions_result = $questions_stmt->get_result();
$questions = $questions_result->fetch_all(MYSQLI_ASSOC);

if (empty($questions)) {
    header('Location: quizzes.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $answers = $_POST['answers'] ?? [];
    $score = 0;
    $total_questions = count($questions);
    
    foreach ($questions as $question) {
        $student_answer = $answers[$question['id']] ?? '';
        if (strtolower(trim($student_answer)) === strtolower(trim($question['correct_answer']))) {
            $score += $question['marks'];
        }
    }
    
    $percentage = $quiz['total_marks'] > 0 ? ($score / $quiz['total_marks']) * 100 : 0;
    
    $submission_query = "INSERT INTO quiz_submissions (quiz_id, student_id, score, percentage, answers) VALUES (?, ?, ?, ?, ?)";
    $submission_stmt = $conn->prepare($submission_query);
    $answers_json = json_encode($answers);
    $submission_stmt->bind_param("iidds", $quiz_id, $student_id, $score, $percentage, $answers_json);
    
    if ($submission_stmt->execute()) {
        header('Location: view_result.php?quiz_id=' . $quiz_id);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Take Quiz - <?php echo htmlspecialchars($quiz['title']); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        .quiz-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem;
        }
        .quiz-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
        }
        .question-card {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .question-number {
            background: #667eea;
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-bottom: 1rem;
        }
        .question-text {
            font-size: 1.1rem;
            font-weight: 500;
            margin-bottom: 1rem;
            color: #333;
        }
        .answer-input {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }
        .answer-input:focus {
            outline: none;
            border-color: #667eea;
        }
        .quiz-timer {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #ff6b6b;
            color: white;
            padding: 1rem;
            border-radius: 8px;
            font-weight: bold;
            z-index: 1000;
        }
        .submit-section {
            text-align: center;
            margin-top: 2rem;
        }
        .submit-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s ease;
        }
        .submit-btn:hover {
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <?php if ($quiz['time_limit'] > 0): ?>
    <div class="quiz-timer" id="timer">
        Time Remaining: <span id="time-display"><?php echo $quiz['time_limit']; ?>:00</span>
    </div>
    <?php endif; ?>

    <div class="quiz-container">
        <div class="quiz-header">
            <h1><?php echo htmlspecialchars($quiz['title']); ?></h1>
            <p><strong>Subject:</strong> <?php echo htmlspecialchars($quiz['subject_name']); ?></p>
            <p><strong>Class:</strong> <?php echo htmlspecialchars($quiz['class_name']); ?></p>
            <p><strong>Teacher:</strong> <?php echo htmlspecialchars($quiz['teacher_name']); ?></p>
            <p><strong>Total Marks:</strong> <?php echo $quiz['total_marks']; ?></p>
            <?php if ($quiz['time_limit'] > 0): ?>
            <p><strong>Time Limit:</strong> <?php echo $quiz['time_limit']; ?> minutes</p>
            <?php endif; ?>
        </div>

        <form method="POST" id="quizForm">
            <?php foreach ($questions as $index => $question): ?>
            <div class="question-card">
                <div class="question-number"><?php echo $index + 1; ?></div>
                <div class="question-text">
                    <?php echo htmlspecialchars($question['question_text']); ?>
                    <span style="color: #667eea; font-weight: bold;">(<?php echo $question['marks']; ?> marks)</span>
                </div>
                
                <?php if ($question['question_type'] === 'multiple_choice'): ?>
                    <?php 
                    $options = json_decode($question['options'], true);
                    foreach ($options as $option): 
                    ?>
                    <label style="display: block; margin-bottom: 0.5rem; cursor: pointer;">
                        <input type="radio" name="answers[<?php echo $question['id']; ?>]" 
                               value="<?php echo htmlspecialchars($option); ?>" 
                               style="margin-right: 0.5rem;">
                        <?php echo htmlspecialchars($option); ?>
                    </label>
                    <?php endforeach; ?>
                <?php else: ?>
                    <input type="text" 
                           name="answers[<?php echo $question['id']; ?>]" 
                           class="answer-input" 
                           placeholder="Enter your answer here..."
                           required>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>

            <div class="submit-section">
                <button type="submit" class="submit-btn" onclick="return confirm('Are you sure you want to submit your quiz? You cannot change your answers after submission.')">
                    <i class="fas fa-paper-plane"></i> Submit Quiz
                </button>
            </div>
        </form>
    </div>

    <script>
        <?php if ($quiz['time_limit'] > 0): ?>
        let timeLeft = <?php echo $quiz['time_limit'] * 60; ?>;
        
        function updateTimer() {
            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;
            document.getElementById('time-display').textContent = 
                minutes + ':' + (seconds < 10 ? '0' : '') + seconds;
            
            if (timeLeft <= 0) {
                alert('Time is up! Your quiz will be submitted automatically.');
                document.getElementById('quizForm').submit();
            }
            
            timeLeft--;
        }
        
        setInterval(updateTimer, 1000);
        <?php endif; ?>
        
        window.addEventListener('beforeunload', function(e) {
            e.preventDefault();
            e.returnValue = 'Are you sure you want to leave? Your progress will be lost.';
        });
    </script>
</body>
</html>
