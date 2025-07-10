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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    header('Content-Type: application/json');

    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'add_question':
            $question_text = trim($_POST['question_text'] ?? '');
            $question_type = $_POST['question_type'] ?? '';
            $marks = intval($_POST['marks'] ?? 0);
            $correct_answer = trim($_POST['correct_answer'] ?? '');
            $explanation = trim($_POST['explanation'] ?? '');
            $options = $_POST['options'] ?? [];

            if (empty($question_text) || empty($question_type) || $marks <= 0 || empty($correct_answer)) {
                echo json_encode(['success' => false, 'message' => 'All required fields must be filled']);
                exit;
            }

            $options_json = $question_type === 'multiple_choice' ? json_encode(array_filter($options)) : null;

            $insert_query = "INSERT INTO quiz_questions (quiz_id, question_text, question_type, options, correct_answer, marks, explanation) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_query);
            $insert_stmt->bind_param("issssis", $quiz_id, $question_text, $question_type, $options_json, $correct_answer, $marks, $explanation);

            if ($insert_stmt->execute()) {
                $update_total = "UPDATE quizzes SET total_marks = (SELECT SUM(marks) FROM quiz_questions WHERE quiz_id = ?) WHERE id = ?";
                $update_stmt = $conn->prepare($update_total);
                $update_stmt->bind_param("ii", $quiz_id, $quiz_id);
                $update_stmt->execute();

                echo json_encode(['success' => true, 'message' => 'Question added successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to add question']);
            }
            exit;

        case 'delete_question':
            $question_id = intval($_POST['question_id'] ?? 0);

            if ($question_id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid question ID']);
                exit;
            }

            $verify_query = "SELECT qq.id FROM quiz_questions qq JOIN quizzes q ON qq.quiz_id = q.id WHERE qq.id = ? AND q.teacher_id = ?";
            $verify_stmt = $conn->prepare($verify_query);
            $verify_stmt->bind_param("ii", $question_id, $teacher_id);
            $verify_stmt->execute();
            if ($verify_stmt->get_result()->num_rows === 0) {
                echo json_encode(['success' => false, 'message' => 'Question not found or access denied']);
                exit;
            }

            $delete_query = "DELETE FROM quiz_questions WHERE id = ?";
            $delete_stmt = $conn->prepare($delete_query);
            $delete_stmt->bind_param("i", $question_id);

            if ($delete_stmt->execute()) {
                $update_total = "UPDATE quizzes SET total_marks = (SELECT COALESCE(SUM(marks), 0) FROM quiz_questions WHERE quiz_id = ?) WHERE id = ?";
                $update_stmt = $conn->prepare($update_total);
                $update_stmt->bind_param("ii", $quiz_id, $quiz_id);
                $update_stmt->execute();

                echo json_encode(['success' => true, 'message' => 'Question deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete question']);
            }
            exit;
    }
}


$columns_check = $conn->query("SHOW COLUMNS FROM quiz_questions");
$existing_columns = [];
if ($columns_check) {
    while ($column = $columns_check->fetch_assoc()) {
        $existing_columns[] = $column['Field'];
    }
}

$has_question_order = in_array('question_order', $existing_columns);
$has_marks = in_array('marks', $existing_columns);
$has_explanation = in_array('explanation', $existing_columns);


if (!$has_marks) {
    echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 1rem; margin: 1rem; border-radius: 5px;'>";
    echo "<h3>⚠️ Database Schema Issue</h3>";
    echo "<p>The quiz_questions table is missing required columns. Please run the database fix script.</p>";
    echo "<p><a href='../fix_all_quiz_tables.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Fix Database Now</a></p>";
    echo "</div>";
    exit;
}


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
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Questions - <?php echo htmlspecialchars($quiz['title']); ?></title>
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
                    <h1>Quiz Questions</h1>
                    <p><?php echo htmlspecialchars($quiz['title']); ?> -
                        <?php echo htmlspecialchars($quiz['subject_name']); ?>
                        (<?php echo htmlspecialchars($quiz['class_name']); ?>)</p>
                </div>
                <div class="header-right">
                    <a href="manage_quiz.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Quizzes
                    </a>
                    <button class="btn btn-primary" onclick="openAddModal()">
                        <i class="fas fa-plus"></i> Add Question
                    </button>
                </div>
            </header>

            <div class="content-card">
                <div class="card-header">
                    <h3>Questions (<?php echo count($questions); ?>) - Total Marks: <?php echo $quiz['total_marks']; ?>
                    </h3>
                </div>
                <div class="card-content">
                    <?php if (empty($questions)): ?>
                    <p style="text-align: center; color: #666; padding: 2rem;">
                        No questions added yet. Click "Add Question" to get started.
                    </p>
                    <?php else: ?>
                    <div class="questions-list">
                        <?php foreach ($questions as $index => $question): ?>
                        <div class="question-item"
                            style="background: #f8fafc; border-radius: 8px; padding: 1.5rem; margin-bottom: 1rem; border-left: 4px solid #667eea;">
                            <div
                                style="display: flex; justify-content: between; align-items: start; margin-bottom: 1rem;">
                                <div style="flex: 1;">
                                    <h4 style="margin: 0 0 0.5rem 0; color: #333;">Question <?php echo $index + 1; ?>
                                    </h4>
                                    <span
                                        class="badge badge-primary"><?php echo ucfirst(str_replace('_', ' ', $question['question_type'])); ?></span>
                                    <span class="badge badge-secondary"><?php echo $question['marks']; ?> marks</span>
                                </div>
                                <button class="btn-icon btn-delete"
                                    onclick="deleteQuestion(<?php echo $question['id']; ?>)" title="Delete Question">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>

                            <p style="font-weight: 500; margin-bottom: 1rem; color: #333;">
                                <?php echo htmlspecialchars($question['question_text']); ?>
                            </p>

                            <?php if ($question['question_type'] === 'multiple_choice' && !empty($question['options'])): ?>
                            <div style="margin-bottom: 1rem;">
                                <strong>Options:</strong>
                                <ul style="margin: 0.5rem 0; padding-left: 1.5rem;">
                                    <?php
                                                $options = json_decode($question['options'], true);
                                                foreach ($options as $option):
                                                ?>
                                    <li style="margin-bottom: 0.25rem;"><?php echo htmlspecialchars($option); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <?php endif; ?>

                            <div style="margin-bottom: 1rem;">
                                <strong>Correct Answer:</strong>
                                <span
                                    style="color: #10b981; font-weight: 500;"><?php echo htmlspecialchars($question['correct_answer']); ?></span>
                            </div>

                            <?php if (!empty($question['explanation'])): ?>
                            <div>
                                <strong>Explanation:</strong>
                                <p style="margin: 0.5rem 0; color: #555;">
                                    <?php echo htmlspecialchars($question['explanation']); ?></p>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <div id="addQuestionModal" class="modal" style="display: none;">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <h3>Add New Question</h3>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <form id="questionForm">
                <input type="hidden" name="action" value="add_question">

                <div class="form-group">
                    <label for="questionText">Question Text *</label>
                    <textarea id="questionText" name="question_text" rows="3" required
                        placeholder="Enter your question here..."></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="questionType">Question Type *</label>
                        <select id="questionType" name="question_type" required onchange="toggleOptions()">
                            <option value="">Select Type</option>
                            <option value="multiple_choice">Multiple Choice</option>
                            <option value="short_answer">Short Answer</option>
                            <option value="essay">Essay</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="marks">Marks *</label>
                        <input type="number" id="marks" name="marks" min="1" required>
                    </div>
                </div>

                <div id="optionsSection" style="display: none;">
                    <label>Options (for Multiple Choice)</label>
                    <div id="optionsList">
                        <input type="text" name="options[]" placeholder="Option 1" style="margin-bottom: 0.5rem;">
                        <input type="text" name="options[]" placeholder="Option 2" style="margin-bottom: 0.5rem;">
                        <input type="text" name="options[]" placeholder="Option 3" style="margin-bottom: 0.5rem;">
                        <input type="text" name="options[]" placeholder="Option 4" style="margin-bottom: 0.5rem;">
                    </div>
                </div>

                <div class="form-group">
                    <label for="correctAnswer">Correct Answer *</label>
                    <input type="text" id="correctAnswer" name="correct_answer" required
                        placeholder="Enter the correct answer">
                </div>

                <div class="form-group">
                    <label for="explanation">Explanation (Optional)</label>
                    <textarea id="explanation" name="explanation" rows="2"
                        placeholder="Provide an explanation for the answer..."></textarea>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="submitBtn">Add Question</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function openAddModal() {
        document.getElementById('questionForm').reset();
        document.getElementById('addQuestionModal').style.display = 'block';
        toggleOptions();
    }

    function closeModal() {
        document.getElementById('addQuestionModal').style.display = 'none';
    }

    function toggleOptions() {
        const questionType = document.getElementById('questionType').value;
        const optionsSection = document.getElementById('optionsSection');
        optionsSection.style.display = questionType === 'multiple_choice' ? 'block' : 'none';
    }

    function deleteQuestion(questionId) {
        if (confirm('Are you sure you want to delete this question? This action cannot be undone.')) {
            fetch('quiz_questions.php?quiz_id=<?php echo $quiz_id; ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: 'action=delete_question&question_id=' + questionId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while deleting the question');
                });
        }
    }

    document.getElementById('questionForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        const submitBtn = document.getElementById('submitBtn');
        const originalText = submitBtn.textContent;

        submitBtn.textContent = 'Adding...';
        submitBtn.disabled = true;

        fetch('quiz_questions.php?quiz_id=<?php echo $quiz_id; ?>', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    closeModal();
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while adding the question');
            })
            .finally(() => {
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
            });
    });

    window.onclick = function(event) {
        const modal = document.getElementById('addQuestionModal');
        if (event.target === modal) {
            closeModal();
        }
    }
    </script>
</body>

</html>