<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student') {
    header('Location: ../login.php');
    exit;
}

$student_id = $_SESSION['user_id'];

$class_filter = $_GET['class'] ?? '';
$subject_filter = $_GET['subject'] ?? '';
$status_filter = $_GET['status'] ?? '';

$classes_query = "SELECT c.id, c.name 
                 FROM classes c
                 JOIN student_class sc ON c.id = sc.class_id
                 WHERE sc.student_id = ?
                 ORDER BY c.name";
$classes_stmt = $conn->prepare($classes_query);
$classes_stmt->bind_param("i", $student_id);
$classes_stmt->execute();
$classes_result = $classes_stmt->get_result();
$classes = $classes_result->fetch_all(MYSQLI_ASSOC);

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

$assignments_query = "SELECT a.id, a.title, a.description, a.due_date,
                     s.name as subject_name, c.name as class_name, u.name as teacher_name,
                     CASE WHEN asub.id IS NOT NULL THEN 'Submitted' ELSE 'Pending' END as status,
                     asub.submitted_at, asub.feedback
                     FROM assignments a
                     JOIN subjects s ON a.subject_id = s.id
                     JOIN classes c ON a.class_id = c.id
                     JOIN users u ON a.teacher_id = u.id
                     JOIN student_class sc ON a.class_id = sc.class_id
                     LEFT JOIN assignment_submissions asub ON a.id = asub.assignment_id AND asub.student_id = ?
                     WHERE sc.student_id = ?";

$params = [$student_id, $student_id];
$types = "ii";

if (!empty($class_filter)) {
    $assignments_query .= " AND a.class_id = ?";
    $params[] = $class_filter;
    $types .= "i";
}

if (!empty($subject_filter)) {
    $assignments_query .= " AND a.subject_id = ?";
    $params[] = $subject_filter;
    $types .= "i";
}

if (!empty($status_filter)) {
    if ($status_filter === 'Submitted') {
        $assignments_query .= " AND asub.id IS NOT NULL";
    } else if ($status_filter === 'Pending') {
        $assignments_query .= " AND asub.id IS NULL";
    }
}

$assignments_query .= " ORDER BY a.due_date ASC";

try {
    $assignments_stmt = $conn->prepare($assignments_query);
    $assignments_stmt->bind_param($types, ...$params);
    $assignments_stmt->execute();
    $assignments_result = $assignments_stmt->get_result();
    $assignments = $assignments_result->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {

    $assignments = [];
    $error_message = "Database error: Some assignment data may not be available.";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assignments - EduLearn LMS</title>
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
                <a href="assignments.php" class="nav-item active">
                    <i class="fas fa-tasks"></i>
                    <span>Assignments</span>
                </a>
                <a href="grades.php" class="nav-item">
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
                    <h1>Assignments</h1>
                    <p>View and submit your assignments</p>
                </div>
            </header>

            <?php if (isset($error_message)): ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i>
                <?php echo $error_message; ?>
            </div>
            <?php endif; ?>

            <div class="filters-section">
                <form method="GET" class="filters-form">
                    <div class="filter-group">
                        <label for="class">Class:</label>
                        <select name="class" id="class" onchange="this.form.submit()">
                            <option value="">All Classes</option>
                            <?php foreach ($classes as $class): ?>
                            <option value="<?php echo $class['id']; ?>"
                                <?php echo $class_filter == $class['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($class['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="subject">Subject:</label>
                        <select name="subject" id="subject" onchange="this.form.submit()">
                            <option value="">All Subjects</option>
                            <?php foreach ($subjects as $subject): ?>
                            <option value="<?php echo $subject['id']; ?>"
                                <?php echo $subject_filter == $subject['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($subject['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="status">Status:</label>
                        <select name="status" id="status" onchange="this.form.submit()">
                            <option value="">All Status</option>
                            <option value="Pending" <?php echo $status_filter == 'Pending' ? 'selected' : ''; ?>>Pending
                            </option>
                            <option value="Submitted" <?php echo $status_filter == 'Submitted' ? 'selected' : ''; ?>>
                                Submitted</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-secondary">
                        <i class="fas fa-filter"></i> Filter
                    </button>

                    <?php if (!empty($class_filter) || !empty($subject_filter) || !empty($status_filter)): ?>
                    <a href="assignments.php" class="btn btn-outline">
                        <i class="fas fa-times"></i> Clear Filters
                    </a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="content-card">
                <div class="card-header">
                    <h3>Your Assignments (<?php echo count($assignments); ?>)</h3>
                </div>
                <div class="card-content">
                    <?php if (empty($assignments)): ?>
                    <p style="text-align: center; color: #666; padding: 2rem;">
                        No assignments found matching your criteria.
                    </p>
                    <?php else: ?>
                    <div class="assignments-grid" style="display: grid; gap: 1.5rem;">
                        <?php foreach ($assignments as $assignment): ?>
                        <?php
                                $due_date = new DateTime($assignment['due_date']);
                                $now = new DateTime();
                                $is_overdue = $now > $due_date && $assignment['status'] === 'Pending';
                                ?>
                        <div class="assignment-card"
                            style="background: white; border-radius: 12px; padding: 1.5rem; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); border-left: 4px solid <?php echo $assignment['status'] === 'Submitted' ? '#10b981' : ($is_overdue ? '#ef4444' : '#f59e0b'); ?>;">
                            <div
                                style="display: flex; justify-content: between; align-items: start; margin-bottom: 1rem;">
                                <div style="flex: 1;">
                                    <h3 style="margin: 0 0 0.5rem 0; color: #333;">
                                        <?php echo htmlspecialchars($assignment['title']); ?></h3>
                                    <p style="margin: 0; color: #666; font-size: 0.9rem;">
                                        <?php echo htmlspecialchars($assignment['subject_name']); ?> •
                                        <?php echo htmlspecialchars($assignment['class_name']); ?>
                                    </p>
                                </div>
                                <span
                                    class="badge badge-<?php echo $assignment['status'] === 'Submitted' ? 'success' : ($is_overdue ? 'danger' : 'warning'); ?>">
                                    <?php echo $assignment['status']; ?>
                                </span>
                            </div>

                            <p style="color: #555; margin-bottom: 1rem;">
                                <?php echo htmlspecialchars($assignment['description']); ?></p>

                            <div
                                style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem; margin-bottom: 1rem; font-size: 0.9rem;">
                                <div>
                                    <strong>Teacher:</strong><br>
                                    <?php echo htmlspecialchars($assignment['teacher_name']); ?>
                                </div>
                                <div>
                                    <strong>Due Date:</strong><br>
                                    <span style="color: <?php echo $is_overdue ? '#ef4444' : '#333'; ?>">
                                        <?php echo $due_date->format('M j, Y g:i A'); ?>
                                    </span>
                                </div>
                                <div>
                                    <strong>Max Marks:</strong><br>
                                    <?php echo $assignment['max_marks']; ?>
                                </div>
                                <?php if ($assignment['status'] === 'Submitted'): ?>
                                <div>
                                    <strong>Score:</strong><br>
                                    <?php
                                                if (isset($assignment['score']) && $assignment['score'] !== null) {
                                                    echo $assignment['score'];
                                                    if (isset($assignment['max_marks'])) {
                                                        echo '/' . $assignment['max_marks'];
                                                    }
                                                } else {
                                                    echo 'Not graded';
                                                }
                                                ?>
                                </div>
                                <?php endif; ?>
                            </div>

                            <?php if ($assignment['status'] === 'Submitted'): ?>
                            <div style="margin-bottom: 1rem;">
                                <strong>Submitted:</strong>
                                <?php echo date('M j, Y g:i A', strtotime($assignment['submitted_at'])); ?>
                                <?php if (!empty($assignment['feedback'])): ?>
                                <div
                                    style="margin-top: 0.5rem; padding: 0.75rem; background: #f8fafc; border-radius: 6px;">
                                    <strong>Feedback:</strong><br>
                                    <?php echo htmlspecialchars($assignment['feedback']); ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            <a href="view_assignment.php?id=<?php echo $assignment['id']; ?>"
                                class="btn btn-sm btn-secondary">
                                View Submission
                            </a>
                            <?php else: ?>
                            <a href="submit_assignment.php?id=<?php echo $assignment['id']; ?>"
                                class="btn btn-sm btn-primary">
                                Submit Assignment
                            </a>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</body>

</html>