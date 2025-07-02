<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'teacher') {
    header('Location: ../login.php');
    exit;
}

$teacher_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    header('Content-Type: application/json');

    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'create':
            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $subject_id = intval($_POST['subject_id'] ?? 0);
            $class_id = intval($_POST['class_id'] ?? 0);
            $time_limit = intval($_POST['time_limit'] ?? 0);
            $total_marks = intval($_POST['total_marks'] ?? 0);

            if (empty($title) || $subject_id <= 0 || $class_id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Title, subject, and class are required']);
                exit;
            }

            $verify_query = "SELECT id FROM teacher_subject_class WHERE teacher_id = ? AND subject_id = ? AND class_id = ?";
            $verify_stmt = $conn->prepare($verify_query);
            $verify_stmt->bind_param("iii", $teacher_id, $subject_id, $class_id);
            $verify_stmt->execute();
            if ($verify_stmt->get_result()->num_rows === 0) {
                echo json_encode(['success' => false, 'message' => 'You are not assigned to this subject-class combination']);
                exit;
            }

            $query = "INSERT INTO quizzes (title, description, subject_id, class_id, teacher_id, time_limit, total_marks) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ssiiiiii", $title, $description, $subject_id, $class_id, $teacher_id, $time_limit, $total_marks);

            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Quiz created successfully', 'quiz_id' => $conn->insert_id]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to create quiz']);
            }
            exit;

        case 'delete':
            $quiz_id = intval($_POST['quiz_id'] ?? 0);

            if ($quiz_id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid quiz ID']);
                exit;
            }

            $verify_query = "SELECT id FROM quizzes WHERE id = ? AND teacher_id = ?";
            $verify_stmt = $conn->prepare($verify_query);
            $verify_stmt->bind_param("ii", $quiz_id, $teacher_id);
            $verify_stmt->execute();
            if ($verify_stmt->get_result()->num_rows === 0) {
                echo json_encode(['success' => false, 'message' => 'Quiz not found or access denied']);
                exit;
            }

            $query = "DELETE FROM quizzes WHERE id = ? AND teacher_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ii", $quiz_id, $teacher_id);

            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Quiz deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete quiz']);
            }
            exit;
    }
}

$assignments_query = "SELECT tsc.subject_id, tsc.class_id, s.name as subject_name, c.name as class_name
                     FROM teacher_subject_class tsc
                     JOIN subjects s ON tsc.subject_id = s.id
                     JOIN classes c ON tsc.class_id = c.id
                     WHERE tsc.teacher_id = ?
                     ORDER BY s.name, c.name";
$assignments_stmt = $conn->prepare($assignments_query);
$assignments_stmt->bind_param("i", $teacher_id);
$assignments_stmt->execute();
$assignments = $assignments_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$subject_filter = $_GET['subject'] ?? '';
$class_filter = $_GET['class'] ?? '';

$query = "SELECT q.*, s.name as subject_name, c.name as class_name,
          (SELECT COUNT(*) FROM quiz_questions qq WHERE qq.quiz_id = q.id) as question_count,
          (SELECT COUNT(DISTINCT qs.student_id) FROM quiz_submissions qs WHERE qs.quiz_id = q.id) as submission_count
          FROM quizzes q
          JOIN subjects s ON q.subject_id = s.id
          JOIN classes c ON q.class_id = c.id
          WHERE q.teacher_id = ?";

$params = [$teacher_id];
$types = "i";

if (!empty($subject_filter)) {
    $query .= " AND q.subject_id = ?";
    $params[] = $subject_filter;
    $types .= "i";
}

if (!empty($class_filter)) {
    $query .= " AND q.class_id = ?";
    $params[] = $class_filter;
    $types .= "i";
}

$query .= " ORDER BY q.created_at DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$quizzes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Quizzes - EduLearn LMS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
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

        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <header class="content-header">
                <div class="header-left">
                    <h1>Manage Quizzes</h1>
                    <p>Create and manage your quizzes</p>
                </div>
                <div class="header-right">
                    <button class="btn btn-primary" onclick="openCreateModal()">
                        <i class="fas fa-plus"></i> Create New Quiz
                    </button>
                </div>
            </header>

            <!-- Filters -->
            <div class="filters-section" style="margin-bottom: 2rem;">
                <form method="GET" class="filters-form" style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
                    <div class="filter-group">
                        <select name="subject" style="padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
                            <option value="">All Subjects</option>
                            <?php
                            $unique_subjects = [];
                            foreach ($assignments as $assignment) {
                                if (!isset($unique_subjects[$assignment['subject_id']])) {
                                    $unique_subjects[$assignment['subject_id']] = $assignment['subject_name'];
                                    echo "<option value='{$assignment['subject_id']}'" . ($subject_filter == $assignment['subject_id'] ? ' selected' : '') . ">{$assignment['subject_name']}</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <select name="class" style="padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
                            <option value="">All Classes</option>
                            <?php
                            $unique_classes = [];
                            foreach ($assignments as $assignment) {
                                if (!isset($unique_classes[$assignment['class_id']])) {
                                    $unique_classes[$assignment['class_id']] = $assignment['class_name'];
                                    echo "<option value='{$assignment['class_id']}'" . ($class_filter == $assignment['class_id'] ? ' selected' : '') . ">{$assignment['class_name']}</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-secondary">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                    <a href="manage_quiz.php" class="btn btn-outline">
                        <i class="fas fa-times"></i> Clear
                    </a>
                </form>
            </div>

            <!-- Quizzes Table -->
            <div class="content-card">
                <div class="card-header">
                    <h3>Your Quizzes (<?php echo count($quizzes); ?>)</h3>
                </div>
                <div class="card-content">
                    <?php if (empty($quizzes)): ?>
                        <p style="text-align: center; color: #666; padding: 2rem;">
                            No quizzes found. Create your first quiz to get started!
                        </p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Quiz Title</th>
                                        <th>Subject</th>
                                        <th>Class</th>
                                        <th>Questions</th>
                                        <th>Submissions</th>
                                        <th>Total Marks</th>
                                        <th>Time Limit</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($quizzes as $quiz): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($quiz['title']); ?></td>
                                            <td><?php echo htmlspecialchars($quiz['subject_name']); ?></td>
                                            <td><?php echo htmlspecialchars($quiz['class_name']); ?></td>
                                            <td><?php echo $quiz['question_count']; ?></td>
                                            <td><?php echo $quiz['submission_count']; ?></td>
                                            <td><?php echo $quiz['total_marks']; ?></td>
                                            <td><?php echo $quiz['time_limit'] ? $quiz['time_limit'] . ' min' : 'No limit'; ?></td>
                                            <td><?php echo date('M j, Y', strtotime($quiz['created_at'])); ?></td>
                                            <td>
                                                <div class="action-buttons">
                                                    <a href="quiz_questions.php?quiz_id=<?php echo $quiz['id']; ?>" class="btn-icon btn-primary" title="Manage Questions">
                                                        <i class="fas fa-list"></i>
                                                    </a>
                                                    <a href="quiz_results.php?quiz_id=<?php echo $quiz['id']; ?>" class="btn-icon btn-info" title="View Results">
                                                        <i class="fas fa-chart-bar"></i>
                                                    </a>
                                                    <button class="btn-icon btn-delete" onclick="deleteQuiz(<?php echo $quiz['id']; ?>)" title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
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

    <!-- Create Quiz Modal -->
    <div id="createModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Create New Quiz</h3>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <form id="createForm">
                <input type="hidden" name="action" value="create">

                <div class="form-group">
                    <label for="quizTitle">Quiz Title *</label>
                    <input type="text" id="quizTitle" name="title" required maxlength="255">
                </div>

                <div class="form-group">
                    <label for="quizDescription">Description</label>
                    <textarea id="quizDescription" name="description" rows="3" placeholder="Optional description for the quiz"></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="subjectSelect">Subject *</label>
                        <select id="subjectSelect" name="subject_id" required>
                            <option value="">Select Subject</option>
                            <?php
                            $unique_subjects = [];
                            foreach ($assignments as $assignment) {
                                if (!isset($unique_subjects[$assignment['subject_id']])) {
                                    $unique_subjects[$assignment['subject_id']] = $assignment['subject_name'];
                                    echo "<option value='{$assignment['subject_id']}'>{$assignment['subject_name']}</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="classSelect">Class *</label>
                        <select id="classSelect" name="class_id" required>
                            <option value="">Select Class</option>
                            <?php foreach ($assignments as $assignment): ?>
                                <option value="<?php echo $assignment['class_id']; ?>" data-subject="<?php echo $assignment['subject_id']; ?>">
                                    <?php echo htmlspecialchars($assignment['class_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="timeLimit">Time Limit (minutes)</label>
                        <input type="number" id="timeLimit" name="time_limit" min="0" placeholder="0 = No limit">
                    </div>

                    <div class="form-group">
                        <label for="totalMarks">Total Marks</label>
                        <input type="number" id="totalMarks" name="total_marks" min="0" value="0">
                    </div>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="submitBtn">Create Quiz</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openCreateModal() {
            document.getElementById('createForm').reset();
            document.getElementById('createModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('createModal').style.display = 'none';
        }

        function deleteQuiz(quizId) {
            if (confirm('Are you sure you want to delete this quiz? This will also delete all questions and submissions. This action cannot be undone.')) {
                fetch('manage_quiz.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: 'action=delete&quiz_id=' + quizId
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
                        alert('An error occurred while deleting the quiz');
                    });
            }
        }

        document.getElementById('subjectSelect').addEventListener('change', function() {
            const selectedSubject = this.value;
            const classSelect = document.getElementById('classSelect');
            const classOptions = classSelect.querySelectorAll('option');

            classSelect.value = '';

            classOptions.forEach(option => {
                if (option.value === '') {
                    option.style.display = 'block';
                } else {
                    const optionSubject = option.getAttribute('data-subject');
                    option.style.display = (selectedSubject === '' || optionSubject === selectedSubject) ? 'block' : 'none';
                }
            });
        });

        document.getElementById('createForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const submitBtn = document.getElementById('submitBtn');
            const originalText = submitBtn.textContent;

            submitBtn.textContent = 'Creating...';
            submitBtn.disabled = true;

            fetch('manage_quiz.php', {
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
                        if (data.quiz_id) {
                            window.location.href = 'quiz_questions.php?quiz_id=' + data.quiz_id;
                        } else {
                            location.reload();
                        }
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while creating the quiz');
                })
                .finally(() => {
                    submitBtn.textContent = originalText;
                    submitBtn.disabled = false;
                });
        });

        window.onclick = function(event) {
            const modal = document.getElementById('createModal');
            if (event.target === modal) {
                closeModal();
            }
        }
    </script>
</body>

</html>