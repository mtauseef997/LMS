<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'teacher') {
    header('Location: ../login.php');
    exit;
}

$teacher_id = $_SESSION['user_id'];


try {
    $assignments_query = "SELECT a.*, s.name as subject_name, c.name as class_name,
                         COUNT(asub.id) as submission_count,
                         COUNT(CASE WHEN asub.score IS NOT NULL THEN 1 END) as graded_count
                         FROM assignments a
                         JOIN subjects s ON a.subject_id = s.id
                         JOIN classes c ON a.class_id = c.id
                         LEFT JOIN assignment_submissions asub ON a.id = asub.assignment_id
                         WHERE a.teacher_id = ?
                         GROUP BY a.id
                         ORDER BY a.created_at DESC";
    $assignments_stmt = $conn->prepare($assignments_query);
    $assignments_stmt->bind_param("i", $teacher_id);
    $assignments_stmt->execute();
    $assignments = $assignments_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {

    $assignments_query = "SELECT a.*, s.name as subject_name, c.name as class_name,
                         COUNT(asub.id) as submission_count
                         FROM assignments a
                         JOIN subjects s ON a.subject_id = s.id
                         JOIN classes c ON a.class_id = c.id
                         LEFT JOIN assignment_submissions asub ON a.id = asub.assignment_id
                         WHERE a.teacher_id = ?
                         GROUP BY a.id
                         ORDER BY a.created_at DESC";
    $assignments_stmt = $conn->prepare($assignments_query);
    $assignments_stmt->bind_param("i", $teacher_id);
    $assignments_stmt->execute();
    $assignments = $assignments_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    foreach ($assignments as &$assignment) {
        $assignment['graded_count'] = 0;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Assignments - Teacher Panel</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
                    <h1>My Assignments</h1>
                    <p>Manage and track your assignments</p>
                </div>
                <div class="header-right">
                    <a href="manage_assignment.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Create New Assignment
                    </a>
                </div>
            </header>

            <div class="content-body">
                <?php if (empty($assignments)): ?>
                    <div class="empty-state">
                        <i class="fas fa-tasks"></i>
                        <h3>No Assignments Yet</h3>
                        <p>You haven't created any assignments yet. Create your first assignment to get started.</p>
                        <a href="manage_assignment.php" class="btn btn-primary">Create Assignment</a>
                    </div>
                <?php else: ?>
                    <div class="grid grid-2">
                        <?php foreach ($assignments as $assignment): ?>
                            <div class="card">
                                <div class="card-header">
                                    <h3><?php echo htmlspecialchars($assignment['title']); ?></h3>
                                    <div class="assignment-meta">
                                        <span class="subject"><?php echo htmlspecialchars($assignment['subject_name']); ?></span>
                                        <span class="class"><?php echo htmlspecialchars($assignment['class_name']); ?></span>
                                    </div>
                                </div>

                                <div class="card-body">
                                    <?php if (isset($assignment['description']) && $assignment['description']): ?>
                                        <p class="description"><?php echo htmlspecialchars(substr($assignment['description'], 0, 100)) . (strlen($assignment['description']) > 100 ? '...' : ''); ?></p>
                                    <?php endif; ?>

                                    <div class="assignment-stats">
                                        <div class="stat">
                                            <i class="fas fa-calendar"></i>
                                            <span>Due: <?php echo date('M j, Y', strtotime($assignment['due_date'])); ?></span>
                                        </div>
                                        <?php if (isset($assignment['max_marks'])): ?>
                                            <div class="stat">
                                                <i class="fas fa-star"></i>
                                                <span>Max Marks: <?php echo $assignment['max_marks']; ?></span>
                                            </div>
                                        <?php endif; ?>
                                        <div class="stat">
                                            <i class="fas fa-users"></i>
                                            <span><?php echo $assignment['submission_count']; ?> submissions</span>
                                        </div>
                                        <div class="stat">
                                            <i class="fas fa-check-circle"></i>
                                            <span><?php echo $assignment['graded_count']; ?> graded</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="card-footer">
                                    <a href="assignment_submissions.php?assignment_id=<?php echo $assignment['id']; ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-eye"></i> View Submissions
                                    </a>
                                    <a href="manage_assignment.php?edit=<?php echo $assignment['id']; ?>" class="btn btn-sm btn-secondary">
                                        <i class="fas fa-edit"></i> Edit
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
        .assignment-meta {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .assignment-meta span {
            background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
            color: #6b7280;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .assignment-stats {
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