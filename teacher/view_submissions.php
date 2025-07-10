<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'teacher') {
    header('Location: ../login.php');
    exit;
}

$teacher_id = $_SESSION['user_id'];

$filter_type = $_GET['type'] ?? 'all';
$filter_status = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';


$quiz_submissions_query = "SELECT 
    qs.id, qs.quiz_id, qs.student_id, qs.score, qs.percentage, qs.submitted_at, qs.status,
    q.title as item_title, q.total_marks as max_marks,
    u.name as student_name, u.email as student_email,
    s.name as subject_name, c.name as class_name,
    'quiz' as submission_type
    FROM quiz_submissions qs
    JOIN quizzes q ON qs.quiz_id = q.id
    JOIN users u ON qs.student_id = u.id
    JOIN subjects s ON q.subject_id = s.id
    JOIN classes c ON q.class_id = c.id
    WHERE q.teacher_id = ?";

$params = [$teacher_id];
$param_types = "i";

if ($filter_type === 'quiz') {
} elseif ($filter_type === 'assignment') {
    $quiz_submissions_query = "SELECT NULL LIMIT 0";
}

if (!empty($search)) {
    $quiz_submissions_query .= " AND (u.name LIKE ? OR q.title LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $param_types .= "ss";
}

if ($filter_status === 'graded') {
    $quiz_submissions_query .= " AND qs.status = 'graded'";
} elseif ($filter_status === 'ungraded') {
    $quiz_submissions_query .= " AND qs.status != 'graded'";
}

$quiz_submissions_query .= " ORDER BY qs.submitted_at DESC";


$assignment_submissions_query = "SELECT 
    asub.id, asub.assignment_id, asub.student_id, asub.score, asub.submitted_at, asub.status,
    a.title as item_title, a.max_marks,
    u.name as student_name, u.email as student_email,
    s.name as subject_name, c.name as class_name,
    'assignment' as submission_type
    FROM assignment_submissions asub
    JOIN assignments a ON asub.assignment_id = a.id
    JOIN users u ON asub.student_id = u.id
    JOIN subjects s ON a.subject_id = s.id
    JOIN classes c ON a.class_id = c.id
    WHERE a.teacher_id = ?";

$assignment_params = [$teacher_id];
$assignment_param_types = "i";

if ($filter_type === 'assignment') {
} elseif ($filter_type === 'quiz') {
    $assignment_submissions_query = "SELECT NULL LIMIT 0";
}

if (!empty($search)) {
    $assignment_submissions_query .= " AND (u.name LIKE ? OR a.title LIKE ?)";
    $assignment_params[] = $search_param;
    $assignment_params[] = $search_param;
    $assignment_param_types .= "ss";
}

if ($filter_status === 'graded') {
    $assignment_submissions_query .= " AND asub.status = 'graded'";
} elseif ($filter_status === 'ungraded') {
    $assignment_submissions_query .= " AND asub.status != 'graded'";
}

$assignment_submissions_query .= " ORDER BY asub.submitted_at DESC";


$all_submissions = [];

if ($filter_type !== 'assignment') {
    $quiz_stmt = $conn->prepare($quiz_submissions_query);
    if (!empty($params)) {
        $quiz_stmt->bind_param($param_types, ...$params);
    }
    $quiz_stmt->execute();
    $quiz_results = $quiz_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $all_submissions = array_merge($all_submissions, $quiz_results);
}

if ($filter_type !== 'quiz') {
    $assignment_stmt = $conn->prepare($assignment_submissions_query);
    if (!empty($assignment_params)) {
        $assignment_stmt->bind_param($assignment_param_types, ...$assignment_params);
    }
    $assignment_stmt->execute();
    $assignment_results = $assignment_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $all_submissions = array_merge($all_submissions, $assignment_results);
}

usort($all_submissions, function ($a, $b) {
    return strtotime($b['submitted_at']) - strtotime($a['submitted_at']);
});

$total_submissions = count($all_submissions);
$graded_count = 0;
$ungraded_count = 0;
$quiz_count = 0;
$assignment_count = 0;

foreach ($all_submissions as $submission) {
    if ($submission['status'] === 'graded') {
        $graded_count++;
    } else {
        $ungraded_count++;
    }

    if ($submission['submission_type'] === 'quiz') {
        $quiz_count++;
    } else {
        $assignment_count++;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Submissions - Teacher Panel</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
    .submissions-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 2rem;
        border-radius: 16px;
        margin-bottom: 2rem;
    }

    .submissions-header h1 {
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .stat-card {
        background: white;
        padding: 1.5rem;
        border-radius: 16px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        text-align: center;
    }

    .stat-icon {
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

    .stat-value {
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
        color: #1f2937;
    }

    .stat-label {
        color: #6b7280;
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .filters-section {
        background: white;
        padding: 1.5rem;
        border-radius: 16px;
        margin-bottom: 2rem;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    }

    .filters-grid {
        display: grid;
        grid-template-columns: 2fr 1fr 1fr auto;
        gap: 1rem;
        align-items: end;
    }

    .filter-group label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 600;
        color: #374151;
    }

    .filter-input,
    .filter-select {
        width: 100%;
        padding: 0.75rem;
        border: 2px solid #e5e7eb;
        border-radius: 12px;
        font-size: 1rem;
        transition: all 0.3s ease;
    }

    .filter-input:focus,
    .filter-select:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    .submissions-table {
        background: white;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    }

    .table-header {
        background: #f8fafc;
        padding: 1.5rem;
        border-bottom: 1px solid #e5e7eb;
        font-weight: 600;
        color: #374151;
    }

    .submissions-list {
        max-height: 600px;
        overflow-y: auto;
    }

    .submission-item {
        padding: 1.5rem;
        border-bottom: 1px solid #f1f5f9;
        transition: all 0.3s ease;
    }

    .submission-item:hover {
        background: #f8fafc;
    }

    .submission-header {
        display: flex;
        justify-content: space-between;
        align-items: start;
        margin-bottom: 1rem;
    }

    .submission-info h4 {
        font-size: 1.1rem;
        font-weight: 600;
        color: #1f2937;
        margin-bottom: 0.25rem;
    }

    .submission-meta {
        display: flex;
        gap: 1rem;
        font-size: 0.875rem;
        color: #6b7280;
    }

    .submission-actions {
        display: flex;
        gap: 0.5rem;
    }

    .btn-sm {
        padding: 0.5rem 1rem;
        font-size: 0.875rem;
        border-radius: 8px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.3s ease;
    }

    .btn-primary {
        background: #3b82f6;
        color: white;
    }

    .btn-success {
        background: #10b981;
        color: white;
    }

    .btn-warning {
        background: #f59e0b;
        color: white;
    }

    .btn-sm:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    .status-badge {
        padding: 0.25rem 0.75rem;
        border-radius: 12px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
    }

    .status-graded {
        background: #dcfce7;
        color: #166534;
    }

    .status-submitted {
        background: #fef3c7;
        color: #92400e;
    }

    .type-badge {
        padding: 0.25rem 0.75rem;
        border-radius: 12px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
    }

    .type-quiz {
        background: #dbeafe;
        color: #1e40af;
    }

    .type-assignment {
        background: #f3e8ff;
        color: #7c3aed;
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
        .filters-grid {
            grid-template-columns: 1fr;
        }

        .submission-header {
            flex-direction: column;
            gap: 1rem;
        }

        .submission-meta {
            flex-direction: column;
            gap: 0.5rem;
        }
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
                <a href="manage_assignment.php" class="nav-item">
                    <i class="fas fa-tasks"></i>
                    <span>Manage Assignments</span>
                </a>
                <a href="view_submissions.php" class="nav-item active">
                    <i class="fas fa-clipboard-list"></i>
                    <span>View Submissions</span>
                </a>
                <a href="students.php" class="nav-item">
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

            <div class="submissions-header">
                <h1><i class="fas fa-clipboard-list"></i> All Submissions</h1>
                <p>Comprehensive overview of quiz and assignment submissions</p>
            </div>


            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-clipboard-check"></i>
                    </div>
                    <div class="stat-value"><?php echo $total_submissions; ?></div>
                    <div class="stat-label">Total Submissions</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-value"><?php echo $graded_count; ?></div>
                    <div class="stat-label">Graded</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-value"><?php echo $ungraded_count; ?></div>
                    <div class="stat-label">Pending Review</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="stat-value">
                        <?php echo $total_submissions > 0 ? round(($graded_count / $total_submissions) * 100) : 0; ?>%
                    </div>
                    <div class="stat-label">Completion Rate</div>
                </div>
            </div>

            <div class="filters-section">
                <form method="GET" action="">
                    <div class="filters-grid">
                        <div class="filter-group">
                            <label for="search">Search</label>
                            <input type="text" id="search" name="search" class="filter-input"
                                placeholder="Search by student name or item title..."
                                value="<?php echo htmlspecialchars($search); ?>">
                        </div>

                        <div class="filter-group">
                            <label for="type">Type</label>
                            <select id="type" name="type" class="filter-select">
                                <option value="all" <?php echo $filter_type === 'all' ? 'selected' : ''; ?>>All Types
                                </option>
                                <option value="quiz" <?php echo $filter_type === 'quiz' ? 'selected' : ''; ?>>Quizzes
                                </option>
                                <option value="assignment"
                                    <?php echo $filter_type === 'assignment' ? 'selected' : ''; ?>>Assignments</option>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label for="status">Status</label>
                            <select id="status" name="status" class="filter-select">
                                <option value="all" <?php echo $filter_status === 'all' ? 'selected' : ''; ?>>All Status
                                </option>
                                <option value="graded" <?php echo $filter_status === 'graded' ? 'selected' : ''; ?>>
                                    Graded</option>
                                <option value="ungraded" <?php echo $filter_status === 'ungraded' ? 'selected' : ''; ?>>
                                    Ungraded</option>
                            </select>
                        </div>

                        <button type="submit" class="btn-sm btn-primary">
                            <i class="fas fa-search"></i>
                            Filter
                        </button>
                    </div>
                </form>
            </div>

            <div class="submissions-table">
                <div class="table-header">
                    <h3>Recent Submissions (<?php echo count($all_submissions); ?>)</h3>
                </div>

                <?php if (empty($all_submissions)): ?>
                <div class="empty-state">
                    <i class="fas fa-clipboard"></i>
                    <h3>No Submissions Found</h3>
                    <p>
                        <?php if (!empty($search) || $filter_type !== 'all' || $filter_status !== 'all'): ?>
                        No submissions match your current filters. Try adjusting your search criteria.
                        <?php else: ?>
                        No submissions have been made yet.
                        <?php endif; ?>
                    </p>
                </div>
                <?php else: ?>
                <div class="submissions-list">
                    <?php foreach ($all_submissions as $submission): ?>
                    <div class="submission-item">
                        <div class="submission-header">
                            <div class="submission-info">
                                <h4><?php echo htmlspecialchars($submission['item_title']); ?></h4>
                                <div class="submission-meta">
                                    <span><i class="fas fa-user"></i>
                                        <?php echo htmlspecialchars($submission['student_name']); ?></span>
                                    <span><i class="fas fa-envelope"></i>
                                        <?php echo htmlspecialchars($submission['student_email']); ?></span>
                                    <span><i class="fas fa-book"></i>
                                        <?php echo htmlspecialchars($submission['subject_name']); ?></span>
                                    <span><i class="fas fa-users"></i>
                                        <?php echo htmlspecialchars($submission['class_name']); ?></span>
                                    <span><i class="fas fa-calendar"></i>
                                        <?php echo date('M j, Y g:i A', strtotime($submission['submitted_at'])); ?></span>
                                </div>
                            </div>

                            <div style="display: flex; align-items: center; gap: 1rem;">
                                <span class="type-badge type-<?php echo $submission['submission_type']; ?>">
                                    <?php echo ucfirst($submission['submission_type']); ?>
                                </span>

                                <span class="status-badge status-<?php echo $submission['status']; ?>">
                                    <?php echo ucfirst($submission['status']); ?>
                                </span>

                                <?php if ($submission['submission_type'] === 'quiz'): ?>
                                <div style="text-align: center;">
                                    <div style="font-weight: 600; color: #1f2937;">
                                        <?php echo $submission['score']; ?>/<?php echo $submission['max_marks']; ?>
                                    </div>
                                    <div style="font-size: 0.875rem; color: #6b7280;">
                                        <?php echo number_format($submission['percentage'], 1); ?>%
                                    </div>
                                </div>
                                <?php else: ?>
                                <div style="text-align: center;">
                                    <div style="font-weight: 600; color: #1f2937;">
                                        <?php echo $submission['score'] ? number_format($submission['score'], 1) : 'Not graded'; ?>/<?php echo $submission['max_marks']; ?>
                                    </div>
                                    <?php if ($submission['score']): ?>
                                    <div style="font-size: 0.875rem; color: #6b7280;">
                                        <?php echo number_format(($submission['score'] / $submission['max_marks']) * 100, 1); ?>%
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>

                                <div class="submission-actions">
                                    <?php if ($submission['submission_type'] === 'quiz'): ?>
                                    <a href="quiz_results.php?quiz_id=<?php echo $submission['quiz_id']; ?>"
                                        class="btn-sm btn-primary" title="View Quiz Results">
                                        <i class="fas fa-chart-bar"></i>
                                        Results
                                    </a>
                                    <a href="view_student_answers.php?quiz_id=<?php echo $submission['quiz_id']; ?>&student_id=<?php echo $submission['student_id']; ?>"
                                        class="btn-sm btn-success" title="View Student Answers">
                                        <i class="fas fa-eye"></i>
                                        Answers
                                    </a>
                                    <?php else: ?>
                                    <a href="assignment_submissions.php?assignment_id=<?php echo $submission['assignment_id']; ?>"
                                        class="btn-sm btn-primary" title="View All Submissions">
                                        <i class="fas fa-list"></i>
                                        All
                                    </a>
                                    <?php if ($submission['status'] !== 'graded'): ?>
                                    <a href="assignment_submissions.php?assignment_id=<?php echo $submission['assignment_id']; ?>#submission-<?php echo $submission['id']; ?>"
                                        class="btn-sm btn-warning" title="Grade Submission">
                                        <i class="fas fa-edit"></i>
                                        Grade
                                    </a>
                                    <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const typeSelect = document.getElementById('type');
        const statusSelect = document.getElementById('status');

        typeSelect.addEventListener('change', function() {
            this.form.submit();
        });

        statusSelect.addEventListener('change', function() {
            this.form.submit();
        });

        const searchInput = document.getElementById('search');
        let searchTimeout;

        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                this.form.submit();
            }, 500);
        });
    });
    </script>
</body>

</html>