<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'teacher') {
    header('Location: ../login.php');
    exit;
}

$teacher_id = $_SESSION['user_id'];


try {
    $quizzes_query = "SELECT q.*, s.name as subject_name, c.name as class_name,
                     COUNT(qs.id) as submission_count,
                     COUNT(DISTINCT qs.student_id) as student_count
                     FROM quizzes q
                     JOIN subjects s ON q.subject_id = s.id
                     JOIN classes c ON q.class_id = c.id
                     LEFT JOIN quiz_submissions qs ON q.id = qs.quiz_id
                     WHERE q.teacher_id = ?
                     GROUP BY q.id
                     ORDER BY q.created_at DESC";
    $quizzes_stmt = $conn->prepare($quizzes_query);
    $quizzes_stmt->bind_param("i", $teacher_id);
    $quizzes_stmt->execute();
    $quizzes = $quizzes_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {

    $quizzes_query = "SELECT q.*, s.name as subject_name, c.name as class_name
                     FROM quizzes q
                     JOIN subjects s ON q.subject_id = s.id
                     JOIN classes c ON q.class_id = c.id
                     WHERE q.teacher_id = ?
                     ORDER BY q.created_at DESC";
    $quizzes_stmt = $conn->prepare($quizzes_query);
    $quizzes_stmt->bind_param("i", $teacher_id);
    $quizzes_stmt->execute();
    $quizzes = $quizzes_stmt->get_result()->fetch_all(MYSQLI_ASSOC);


    foreach ($quizzes as &$quiz) {
        $quiz['submission_count'] = 0;
        $quiz['student_count'] = 0;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Quizzes - Teacher Panel</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/teacher.css">
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
                    <span>Grades</span>
                </a>
                <a href="students.php" class="nav-item">
                    <i class="fas fa-users"></i>
                    <span>Students</span>
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
                    <h1>My Quizzes</h1>
                    <p>Manage and track your quizzes</p>
                </div>
                <div class="header-right">
                    <a href="create_quiz.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Create New Quiz
                    </a>
                </div>
            </header>

            <div class="content-body">
                <?php if (empty($quizzes)): ?>
                    <div class="empty-state">
                        <i class="fas fa-question-circle"></i>
                        <h3>No Quizzes Yet</h3>
                        <p>You haven't created any quizzes yet. Create your first quiz to get started.</p>
                        <a href="create_quiz.php" class="btn btn-primary">Create Quiz</a>
                    </div>
                <?php else: ?>
                    <div class="grid grid-2">
                        <?php foreach ($quizzes as $quiz): ?>
                            <div class="card">
                                <div class="card-header">
                                    <h3><?php echo htmlspecialchars($quiz['title']); ?></h3>
                                    <div class="quiz-meta">
                                        <span class="subject"><?php echo htmlspecialchars($quiz['subject_name']); ?></span>
                                        <span class="class"><?php echo htmlspecialchars($quiz['class_name']); ?></span>
                                    </div>
                                </div>

                                <div class="card-body">
                                    <?php if (isset($quiz['description']) && $quiz['description']): ?>
                                        <p class="description">
                                            <?php echo htmlspecialchars(substr($quiz['description'], 0, 100)) . (strlen($quiz['description']) > 100 ? '...' : ''); ?>
                                        </p>
                                    <?php endif; ?>

                                    <div class="quiz-stats">
                                        <?php if (isset($quiz['total_marks'])): ?>
                                            <div class="stat">
                                                <i class="fas fa-star"></i>
                                                <span>Total Marks: <?php echo $quiz['total_marks']; ?></span>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (isset($quiz['time_limit'])): ?>
                                            <div class="stat">
                                                <i class="fas fa-clock"></i>
                                                <span>Time: <?php echo $quiz['time_limit']; ?> minutes</span>
                                            </div>
                                        <?php endif; ?>
                                        <div class="stat">
                                            <i class="fas fa-users"></i>
                                            <span><?php echo $quiz['submission_count']; ?> submissions</span>
                                        </div>
                                        <div class="stat">
                                            <i class="fas fa-calendar"></i>
                                            <span>Created: <?php echo date('M j, Y', strtotime($quiz['created_at'])); ?></span>
                                        </div>
                                    </div>
                                </div>

                                <div class="card-footer">
                                    <a href="quiz_questions.php?quiz_id=<?php echo $quiz['id']; ?>"
                                        class="btn btn-sm btn-primary">
                                        <i class="fas fa-edit"></i> Edit Questions
                                    </a>
                                    <a href="quiz_results.php?quiz_id=<?php echo $quiz['id']; ?>"
                                        class="btn btn-sm btn-secondary">
                                        <i class="fas fa-chart-bar"></i> View Results
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <style>
        .quiz-meta {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .quiz-meta span {
            background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
            color: #6b7280;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .quiz-stats {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            margin-top: 1rem;
        }

        .stat {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: #6b7280;
            font-size: 0.875rem;
            padding: 0.5rem;
            background: #f8fafc;
            border-radius: 8px;
        }

        .stat i {
            width: 18px;
            color: #667eea;
            font-size: 1rem;
        }

        .description {
            color: #6b7280;
            margin-bottom: 1rem;
            line-height: 1.6;
            font-style: italic;
        }
    </style>
</body>

</html>