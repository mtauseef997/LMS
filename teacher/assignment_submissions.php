<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'teacher') {
    header('Location: ../login.php');
    exit;
}

$teacher_id = $_SESSION['user_id'];
$assignment_id = intval($_GET['assignment_id'] ?? 0);

if ($assignment_id <= 0) {
    header('Location: manage_assignment.php');
    exit;
}

$assignment_query = "SELECT a.*, s.name as subject_name, c.name as class_name
                    FROM assignments a
                    JOIN subjects s ON a.subject_id = s.id
                    JOIN classes c ON a.class_id = c.id
                    WHERE a.id = ? AND a.teacher_id = ?";
$assignment_stmt = $conn->prepare($assignment_query);
$assignment_stmt->bind_param("ii", $assignment_id, $teacher_id);
$assignment_stmt->execute();
$assignment_result = $assignment_stmt->get_result();
$assignment = $assignment_result->fetch_assoc();

if (!$assignment) {
    header('Location: manage_assignment.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    header('Content-Type: application/json');

    $action = $_POST['action'] ?? '';

    if ($action === 'grade') {
        $submission_id = intval($_POST['submission_id'] ?? 0);
        $score = floatval($_POST['score'] ?? 0);
        $feedback = trim($_POST['feedback'] ?? '');

        if ($submission_id <= 0 || $score < 0 || $score > $assignment['max_marks']) {
            echo json_encode(['success' => false, 'message' => 'Invalid submission ID or score']);
            exit;
        }

        $update_query = "UPDATE assignment_submissions SET score = ?, feedback = ?, graded_at = NOW() WHERE id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("dsi", $score, $feedback, $submission_id);

        if ($update_stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Assignment graded successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to grade assignment']);
        }
        exit;
    }
}

$submissions_query = "SELECT asub.*, u.name as student_name, u.email as student_email
                     FROM assignment_submissions asub
                     JOIN users u ON asub.student_id = u.id
                     WHERE asub.assignment_id = ?
                     ORDER BY asub.submitted_at DESC";
$submissions_stmt = $conn->prepare($submissions_query);
$submissions_stmt->bind_param("i", $assignment_id);
$submissions_stmt->execute();
$submissions_result = $submissions_stmt->get_result();
$submissions = $submissions_result->fetch_all(MYSQLI_ASSOC);

$total_students_query = "SELECT COUNT(*) as total FROM student_class WHERE class_id = ?";
$total_stmt = $conn->prepare($total_students_query);
$total_stmt->bind_param("i", $assignment['class_id']);
$total_stmt->execute();
$total_students = $total_stmt->get_result()->fetch_assoc()['total'];

$graded_count = 0;
$total_score = 0;
foreach ($submissions as $submission) {
    if ($submission['score'] !== null) {
        $graded_count++;
        $total_score += $submission['score'];
    }
}

$stats = [
    'total_submissions' => count($submissions),
    'total_students' => $total_students,
    'graded_count' => $graded_count,
    'pending_count' => count($submissions) - $graded_count,
    'average_score' => $graded_count > 0 ? $total_score / $graded_count : 0
];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assignment Submissions - <?php echo htmlspecialchars($assignment['title']); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
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
        font-size: 1.8rem;
        font-weight: bold;
        margin-bottom: 0.5rem;
    }

    .stat-label {
        font-size: 0.85rem;
        opacity: 0.9;
    }

    .submission-content {
        background: #f8fafc;
        padding: 1rem;
        border-radius: 6px;
        margin: 0.5rem 0;
        max-height: 150px;
        overflow-y: auto;
    }

    .grade-form {
        display: inline-flex;
        gap: 0.5rem;
        align-items: center;
    }

    .grade-input {
        width: 60px;
        padding: 0.25rem;
        border: 1px solid #ddd;
        border-radius: 4px;
    }

    .feedback-input {
        width: 200px;
        padding: 0.25rem;
        border: 1px solid #ddd;
        border-radius: 4px;
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
                <a href="manage_assignment.php" class="nav-item active">
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
                    <h1>Assignment Submissions</h1>
                    <p><?php echo htmlspecialchars($assignment['title']); ?> -
                        <?php echo htmlspecialchars($assignment['subject_name']); ?>
                        (<?php echo htmlspecialchars($assignment['class_name']); ?>)</p>
                </div>
                <div class="header-right">
                    <a href="manage_assignment.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Assignments
                    </a>
                </div>
            </header>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number">
                        <?php echo $stats['total_submissions']; ?>/<?php echo $stats['total_students']; ?></div>
                    <div class="stat-label">Submissions</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['graded_count']; ?></div>
                    <div class="stat-label">Graded</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['pending_count']; ?></div>
                    <div class="stat-label">Pending</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format($stats['average_score'], 1); ?></div>
                    <div class="stat-label">Average Score</div>
                </div>
            </div>

            <div class="content-card">
                <div class="card-header">
                    <h3>Student Submissions (<?php echo count($submissions); ?>)</h3>
                </div>
                <div class="card-content">
                    <?php if (empty($submissions)): ?>
                    <p style="text-align: center; color: #666; padding: 2rem;">
                        No submissions yet for this assignment.
                    </p>
                    <?php else: ?>
                    <div class="submissions-list">
                        <?php foreach ($submissions as $submission): ?>
                        <div class="submission-item"
                            style="background: white; border-radius: 8px; padding: 1.5rem; margin-bottom: 1rem; border-left: 4px solid <?php echo $submission['score'] !== null ? '#10b981' : '#f59e0b'; ?>;">
                            <div
                                style="display: flex; justify-content: between; align-items: start; margin-bottom: 1rem;">
                                <div style="flex: 1;">
                                    <h4 style="margin: 0 0 0.5rem 0;">
                                        <?php echo htmlspecialchars($submission['student_name']); ?></h4>
                                    <p style="margin: 0; color: #666; font-size: 0.9rem;">
                                        <?php echo htmlspecialchars($submission['student_email']); ?> •
                                        Submitted:
                                        <?php echo date('M j, Y g:i A', strtotime($submission['submitted_at'])); ?>
                                    </p>
                                </div>
                                <div>
                                    <?php if ($submission['score'] !== null): ?>
                                    <span class="badge badge-success">Graded:
                                        <?php echo $submission['score']; ?>/<?php echo $assignment['max_marks']; ?></span>
                                    <?php else: ?>
                                    <span class="badge badge-warning">Pending Grade</span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <?php if (!empty($submission['submission_text'])): ?>
                            <div style="margin-bottom: 1rem;">
                                <strong>Text Submission:</strong>
                                <div class="submission-content">
                                    <?php echo nl2br(htmlspecialchars($submission['submission_text'])); ?>
                                </div>
                            </div>
                            <?php endif; ?>

                            <?php if (!empty($submission['file_path']) && file_exists($submission['file_path'])): ?>
                            <div style="margin-bottom: 1rem;">
                                <strong>File Submission:</strong>
                                <a href="<?php echo htmlspecialchars($submission['file_path']); ?>" target="_blank"
                                    class="btn btn-sm btn-outline">
                                    <i class="fas fa-download"></i> Download File
                                </a>
                            </div>
                            <?php endif; ?>

                            <div style="border-top: 1px solid #e5e7eb; padding-top: 1rem;">
                                <form class="grade-form"
                                    onsubmit="gradeSubmission(event, <?php echo $submission['id']; ?>)">
                                    <label>Score:</label>
                                    <input type="number" class="grade-input" min="0"
                                        max="<?php echo $assignment['max_marks']; ?>" step="0.1"
                                        value="<?php echo $submission['score'] ?? ''; ?>" required>
                                    <span>/<?php echo $assignment['max_marks']; ?></span>

                                    <label style="margin-left: 1rem;">Feedback:</label>
                                    <input type="text" class="feedback-input" placeholder="Optional feedback..."
                                        value="<?php echo htmlspecialchars($submission['feedback'] ?? ''); ?>">

                                    <button type="submit" class="btn btn-sm btn-primary">
                                        <?php echo $submission['score'] !== null ? 'Update' : 'Grade'; ?>
                                    </button>
                                </form>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
    function gradeSubmission(event, submissionId) {
        event.preventDefault();

        const form = event.target;
        const score = form.querySelector('.grade-input').value;
        const feedback = form.querySelector('.feedback-input').value;
        const button = form.querySelector('button');
        const originalText = button.textContent;

        button.textContent = 'Grading...';
        button.disabled = true;

        const formData = new FormData();
        formData.append('action', 'grade');
        formData.append('submission_id', submissionId);
        formData.append('score', score);
        formData.append('feedback', feedback);

        fetch('assignment_submissions.php?assignment_id=<?php echo $assignment_id; ?>', {
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
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while grading the assignment');
            })
            .finally(() => {
                button.textContent = originalText;
                button.disabled = false;
            });
    }
    </script>
</body>

</html>