<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student') {
    header('Location: ../login.php');
    exit;
}

$student_id = $_SESSION['user_id'];

// Get filter values
$class_filter = $_GET['class'] ?? '';
$subject_filter = $_GET['subject'] ?? '';
$status_filter = $_GET['status'] ?? '';

// Get classes the student is enrolled in
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

// Get subjects available to the student
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

// Build the quizzes query with filters
$quizzes_query = "SELECT q.id, q.title, q.description, q.time_limit, q.total_marks,
                 s.name as subject_name, c.name as class_name, u.name as teacher_name,
                 CASE WHEN qs.id IS NOT NULL THEN 'Completed' ELSE 'Available' END as status,
                 qs.score, qs.percentage, qs.submitted_at
                 FROM quizzes q
                 JOIN subjects s ON q.subject_id = s.id
                 JOIN classes c ON q.class_id = c.id
                 JOIN users u ON q.teacher_id = u.id
                 JOIN student_class sc ON q.class_id = sc.class_id
                 LEFT JOIN quiz_submissions qs ON q.id = qs.quiz_id AND qs.student_id = ?
                 WHERE sc.student_id = ? AND q.is_active = 1";

$params = [$student_id, $student_id];
$types = "ii";

if (!empty($class_filter)) {
    $quizzes_query .= " AND q.class_id = ?";
    $params[] = $class_filter;
    $types .= "i";
}

if (!empty($subject_filter)) {
    $quizzes_query .= " AND q.subject_id = ?";
    $params[] = $subject_filter;
    $types = "i";
}

if (!empty($status_filter)) {
    if ($status_filter === 'Completed') {
        $quizzes_query .= " AND qs.id IS NOT NULL";
    } else if ($status_filter === 'Available') {
        $quizzes_query .= " AND qs.id IS NULL";
    }
}

$quizzes_query .= " ORDER BY q.created_at DESC";

$quizzes_stmt = $conn->prepare($quizzes_query);
$quizzes_stmt->bind_param($types, ...$params);
$quizzes_stmt->execute();
$quizzes_result = $quizzes_stmt->get_result();
$quizzes = $quizzes_result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quizzes - EduLearn LMS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>

<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
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
                <a href="quizzes.php" class="nav-item active">
                    <i class="fas fa-question-circle"></i>
                    <span>Quizzes</span>
                </a>
                <a href="assignments.php" class="nav-item">
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

        <!-- Main Content -->
        <main class="main-content">
            <header class="content-header">
                <div class="header-left">
                    <h1>Quizzes</h1>
                    <p>View and take quizzes assigned to you</p>
                </div>
            </header>

            <!-- Filters -->
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
                            <option value="Available" <?php echo $status_filter == 'Available' ? 'selected' : ''; ?>>
                                Available</option>
                            <option value="Completed" <?php echo $status_filter == 'Completed' ? 'selected' : ''; ?>>
                                Completed</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-secondary">
                        <i class="fas fa-filter"></i> Filter
                    </button>

                    <?php if (!empty($class_filter) || !empty($subject_filter) || !empty($status_filter)): ?>
                    <a href="quizzes.php" class="btn btn-outline">
                        <i class="fas fa-times"></i> Clear Filters
                    </a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Quizzes List -->
            <div class="content-card">
                <div class="card-header">
                    <h3>Available Quizzes (<?php echo count($quizzes); ?>)</h3>
                </div>
                <div class="card-content">
                    <?php if (empty($quizzes)): ?>
                    <p style="text-align: center; color: #666; padding: 2rem;">
                        No quizzes found matching your criteria.
                    </p>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Quiz Title</th>
                                    <th>Subject</th>
                                    <th>Class</th>
                                    <th>Teacher</th>
                                    <th>Time Limit</th>
                                    <th>Total Marks</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($quizzes as $quiz): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($quiz['title']); ?></td>
                                    <td><?php echo htmlspecialchars($quiz['subject_name']); ?></td>
                                    <td><?php echo htmlspecialchars($quiz['class_name']); ?></td>
                                    <td><?php echo htmlspecialchars($quiz['teacher_name']); ?></td>
                                    <td><?php echo $quiz['time_limit'] ? $quiz['time_limit'] . ' min' : 'No limit'; ?>
                                    </td>
                                    <td><?php echo $quiz['total_marks']; ?></td>
                                    <td>
                                        <span
                                            class="badge badge-<?php echo $quiz['status'] === 'Completed' ? 'success' : 'warning'; ?>">
                                            <?php echo $quiz['status']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($quiz['status'] === 'Available'): ?>
                                        <a href="take_quiz.php?id=<?php echo $quiz['id']; ?>"
                                            class="btn btn-sm btn-primary">Take Quiz</a>
                                        <?php else: ?>
                                        <a href="view_result.php?quiz_id=<?php echo $quiz['id']; ?>"
                                            class="btn btn-sm btn-secondary">
                                            View Result
                                        </a>
                                        <?php endif; ?>
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

    <script>
    // Add any JavaScript functionality here
    </script>
</body>

</html>