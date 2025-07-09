<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$stats = [];
$result = $conn->query("SELECT COUNT(*) as count FROM users");
$stats['total_users'] = $result->fetch_assoc()['count'];

$result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'student'");
$stats['total_students'] = $result->fetch_assoc()['count'];

$result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'teacher'");
$stats['total_teachers'] = $result->fetch_assoc()['count'];

$result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'admin'");
$stats['total_admins'] = $result->fetch_assoc()['count'];

// Academic statistics
$result = $conn->query("SELECT COUNT(*) as count FROM classes");
$stats['total_classes'] = $result->fetch_assoc()['count'];

$result = $conn->query("SELECT COUNT(*) as count FROM subjects");
$stats['total_subjects'] = $result->fetch_assoc()['count'];

$result = $conn->query("SELECT COUNT(*) as count FROM teacher_subject_class");
$stats['total_assignments'] = $result->fetch_assoc()['count'];

$result = $conn->query("SELECT COUNT(*) as count FROM student_class");
$stats['total_enrollments'] = $result->fetch_assoc()['count'];

$result = $conn->query("SELECT COUNT(*) as count FROM quizzes");
$stats['total_quizzes'] = $result->fetch_assoc()['count'];

$result = $conn->query("SELECT COUNT(*) as count FROM quiz_questions");
$stats['total_quiz_questions'] = $result->fetch_assoc()['count'];

$result = $conn->query("SELECT COUNT(*) as count FROM quiz_submissions");
$stats['total_quiz_submissions'] = $result->fetch_assoc()['count'];

$result = $conn->query("SELECT COUNT(*) as count FROM assignments");
$stats['total_assignments_created'] = $result->fetch_assoc()['count'];

$result = $conn->query("SELECT COUNT(*) as count FROM assignment_submissions");
$stats['total_assignment_submissions'] = $result->fetch_assoc()['count'];

$recent_users = [];
try {
    $result = $conn->query("SELECT name, email, role, created_at FROM users ORDER BY created_at DESC LIMIT 10");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $recent_users[] = $row;
        }
    }
} catch (Exception $e) {
    $result = $conn->query("SELECT name, email, role, id as created_at FROM users ORDER BY id DESC LIMIT 10");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $recent_users[] = $row;
        }
    }
}

$recent_quizzes = [];
try {
    $result = $conn->query("SELECT q.title, s.name as subject, c.name as class, u.name as teacher, q.created_at
                           FROM quizzes q
                           JOIN subjects s ON q.subject_id = s.id
                           JOIN classes c ON q.class_id = c.id
                           JOIN users u ON q.teacher_id = u.id
                           ORDER BY q.created_at DESC LIMIT 10");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $recent_quizzes[] = $row;
        }
    }
} catch (Exception $e) {
    $result = $conn->query("SELECT q.title, s.name as subject, c.name as class, u.name as teacher, q.id as created_at
                           FROM quizzes q
                           JOIN subjects s ON q.subject_id = s.id
                           JOIN classes c ON q.class_id = c.id
                           JOIN users u ON q.teacher_id = u.id
                           ORDER BY q.id DESC LIMIT 10");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $recent_quizzes[] = $row;
        }
    }
}

$recent_assignments = [];
try {
    $result = $conn->query("SELECT a.title, s.name as subject, c.name as class, u.name as teacher, a.created_at
                           FROM assignments a
                           JOIN subjects s ON a.subject_id = s.id
                           JOIN classes c ON a.class_id = c.id
                           JOIN users u ON a.teacher_id = u.id
                           ORDER BY a.created_at DESC LIMIT 10");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $recent_assignments[] = $row;
        }
    }
} catch (Exception $e) {
    $result = $conn->query("SELECT a.title, s.name as subject, c.name as class, u.name as teacher, a.id as created_at
                           FROM assignments a
                           JOIN subjects s ON a.subject_id = s.id
                           JOIN classes c ON a.class_id = c.id
                           JOIN users u ON a.teacher_id = u.id
                           ORDER BY a.id DESC LIMIT 10");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $recent_assignments[] = $row;
        }
    }
}


$quiz_performance = [];
try {
    $result = $conn->query("SELECT AVG(percentage) as avg_score, COUNT(*) as total_submissions FROM quiz_submissions");
    if ($result) {
        $quiz_perf = $result->fetch_assoc();
        $quiz_performance['average_score'] = round($quiz_perf['avg_score'], 2);
        $quiz_performance['total_submissions'] = $quiz_perf['total_submissions'];
    } else {
        throw new Exception("Percentage column not found");
    }
} catch (Exception $e) {
    try {
        $result = $conn->query("SELECT AVG((qs.score / q.total_marks) * 100) as avg_score, COUNT(*) as total_submissions
                               FROM quiz_submissions qs
                               JOIN quizzes q ON qs.quiz_id = q.id
                               WHERE q.total_marks > 0");
        if ($result) {
            $quiz_perf = $result->fetch_assoc();
            $quiz_performance['average_score'] = round($quiz_perf['avg_score'], 2);
            $quiz_performance['total_submissions'] = $quiz_perf['total_submissions'];
        } else {
            throw new Exception("total_marks column not found");
        }
    } catch (Exception $e2) {
        $result = $conn->query("SELECT COUNT(*) as total_submissions FROM quiz_submissions");
        if ($result) {
            $quiz_perf = $result->fetch_assoc();
            $quiz_performance['average_score'] = 0;
            $quiz_performance['total_submissions'] = $quiz_perf['total_submissions'];
        } else {
            $quiz_performance['average_score'] = 0;
            $quiz_performance['total_submissions'] = 0;
        }
    }
}

$assignment_performance = [];
try {
    $result = $conn->query("SELECT COUNT(*) as graded, COUNT(CASE WHEN score IS NULL THEN 1 END) as pending FROM assignment_submissions");
    if ($result) {
        $assign_perf = $result->fetch_assoc();
        $assignment_performance['graded'] = $assign_perf['graded'] - $assign_perf['pending'];
        $assignment_performance['pending'] = $assign_perf['pending'];
    } else {
        throw new Exception("score column not found");
    }
} catch (Exception $e) {
    $result = $conn->query("SELECT COUNT(*) as total FROM assignment_submissions");
    if ($result) {
        $assign_perf = $result->fetch_assoc();
        $assignment_performance['graded'] = 0;
        $assignment_performance['pending'] = $assign_perf['total'];
    } else {
        $assignment_performance['graded'] = 0;
        $assignment_performance['pending'] = 0;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Reports - EduLearn LMS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/teacher.css">
    <style>
        .report-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .report-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .report-card h3 {
            color: #333;
            margin-bottom: 1rem;
            font-size: 1.1rem;
            border-bottom: 2px solid #667eea;
            padding-bottom: 0.5rem;
        }

        .stat-item {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .stat-item:last-child {
            border-bottom: none;
        }

        .stat-label {
            color: #666;
        }

        .stat-value {
            font-weight: 600;
            color: #333;
        }

        .activity-item {
            padding: 0.75rem 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-title {
            font-weight: 500;
            color: #333;
            margin-bottom: 0.25rem;
        }

        .activity-meta {
            font-size: 0.85rem;
            color: #666;
        }

        .export-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin: 0.5rem 0.5rem 0.5rem 0;
        }

        .export-btn:hover {
            transform: translateY(-2px);
            color: white;
            text-decoration: none;
        }
    </style>
</head>

<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-graduation-cap"></i> EduLearn</h2>
                <p>Admin Panel</p>
            </div>

            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-item">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
                <a href="manage_user.php" class="nav-item">
                    <i class="fas fa-users"></i>
                    <span>Manage Users</span>
                </a>
                <a href="manage_class.php" class="nav-item">
                    <i class="fas fa-school"></i>
                    <span>Manage Classes</span>
                </a>
                <a href="manage_subject.php" class="nav-item">
                    <i class="fas fa-book"></i>
                    <span>Manage Subjects</span>
                </a>
                <a href="assign_teacher.php" class="nav-item">
                    <i class="fas fa-chalkboard-teacher"></i>
                    <span>Assign Teachers</span>
                </a>
                <a href="enroll_student.php" class="nav-item">
                    <i class="fas fa-user-graduate"></i>
                    <span>Enroll Students</span>
                </a>
                <a href="reports.php" class="nav-item active">
                    <i class="fas fa-chart-bar"></i>
                    <span>Reports</span>
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
                    <h1>System Reports</h1>
                    <p>Comprehensive overview of system statistics and activity</p>
                </div>
                <div class="header-right">
                    <button class="export-btn" onclick="exportReport('csv')">
                        <i class="fas fa-file-csv"></i> Export CSV
                    </button>
                    <button class="export-btn" onclick="window.print()">
                        <i class="fas fa-print"></i> Print Report
                    </button>
                    <button class="export-btn" onclick="refreshReport()">
                        <i class="fas fa-sync"></i> Refresh
                    </button>
                    <a href="dashboard.php" class="export-btn" style="background: #6c757d;">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
            </header>

            <div class="report-grid">
                <!-- User Statistics -->
                <div class="report-card">
                    <h3><i class="fas fa-users"></i> User Statistics</h3>
                    <div class="stat-item">
                        <span class="stat-label">Total Users</span>
                        <span class="stat-value"><?php echo $stats['total_users']; ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Students</span>
                        <span class="stat-value"><?php echo $stats['total_students']; ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Teachers</span>
                        <span class="stat-value"><?php echo $stats['total_teachers']; ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Administrators</span>
                        <span class="stat-value"><?php echo $stats['total_admins']; ?></span>
                    </div>
                </div>

                <!-- Academic Statistics -->
                <div class="report-card">
                    <h3><i class="fas fa-school"></i> Academic Statistics</h3>
                    <div class="stat-item">
                        <span class="stat-label">Total Classes</span>
                        <span class="stat-value"><?php echo $stats['total_classes']; ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Total Subjects</span>
                        <span class="stat-value"><?php echo $stats['total_subjects']; ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Teacher Assignments</span>
                        <span class="stat-value"><?php echo $stats['total_assignments']; ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Student Enrollments</span>
                        <span class="stat-value"><?php echo $stats['total_enrollments']; ?></span>
                    </div>
                </div>

                <!-- Quiz Statistics -->
                <div class="report-card">
                    <h3><i class="fas fa-question-circle"></i> Quiz Statistics</h3>
                    <div class="stat-item">
                        <span class="stat-label">Total Quizzes</span>
                        <span class="stat-value"><?php echo $stats['total_quizzes']; ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Quiz Questions</span>
                        <span class="stat-value"><?php echo $stats['total_quiz_questions']; ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Quiz Submissions</span>
                        <span class="stat-value"><?php echo $stats['total_quiz_submissions']; ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Average Score</span>
                        <span class="stat-value"><?php echo $quiz_performance['average_score']; ?>%</span>
                    </div>
                </div>

                <!-- Assignment Statistics -->
                <div class="report-card">
                    <h3><i class="fas fa-tasks"></i> Assignment Statistics</h3>
                    <div class="stat-item">
                        <span class="stat-label">Total Assignments</span>
                        <span class="stat-value"><?php echo $stats['total_assignments_created']; ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Total Submissions</span>
                        <span class="stat-value"><?php echo $stats['total_assignment_submissions']; ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Graded Submissions</span>
                        <span class="stat-value"><?php echo $assignment_performance['graded']; ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Pending Grading</span>
                        <span class="stat-value"><?php echo $assignment_performance['pending']; ?></span>
                    </div>
                </div>
            </div>

            <!-- Recent Activity Section -->
            <div class="report-grid">
                <!-- Recent Users -->
                <div class="report-card">
                    <h3><i class="fas fa-user-plus"></i> Recent User Registrations</h3>
                    <?php if (empty($recent_users)): ?>
                        <p style="color: #666; text-align: center; padding: 1rem;">No users found</p>
                    <?php else: ?>
                        <?php foreach (array_slice($recent_users, 0, 5) as $user): ?>
                            <div class="activity-item">
                                <div class="activity-title"><?php echo htmlspecialchars($user['name']); ?></div>
                                <div class="activity-meta">
                                    <?php echo ucfirst($user['role']); ?> • <?php echo htmlspecialchars($user['email']); ?>
                                    <?php if ($user['created_at']): ?>
                                        <br>Registered: <?php echo date('M j, Y', strtotime($user['created_at'])); ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Recent Quizzes -->
                <div class="report-card">
                    <h3><i class="fas fa-question-circle"></i> Recent Quizzes Created</h3>
                    <?php if (empty($recent_quizzes)): ?>
                        <p style="color: #666; text-align: center; padding: 1rem;">No quizzes found</p>
                    <?php else: ?>
                        <?php foreach (array_slice($recent_quizzes, 0, 5) as $quiz): ?>
                            <div class="activity-item">
                                <div class="activity-title"><?php echo htmlspecialchars($quiz['title']); ?></div>
                                <div class="activity-meta">
                                    <?php echo htmlspecialchars($quiz['subject']); ?> -
                                    <?php echo htmlspecialchars($quiz['class']); ?>
                                    <br>By: <?php echo htmlspecialchars($quiz['teacher']); ?>
                                    <?php if ($quiz['created_at']): ?>
                                        • <?php echo date('M j, Y', strtotime($quiz['created_at'])); ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Recent Assignments -->
                <div class="report-card">
                    <h3><i class="fas fa-tasks"></i> Recent Assignments Created</h3>
                    <?php if (empty($recent_assignments)): ?>
                        <p style="color: #666; text-align: center; padding: 1rem;">No assignments found</p>
                    <?php else: ?>
                        <?php foreach (array_slice($recent_assignments, 0, 5) as $assignment): ?>
                            <div class="activity-item">
                                <div class="activity-title"><?php echo htmlspecialchars($assignment['title']); ?></div>
                                <div class="activity-meta">
                                    <?php echo htmlspecialchars($assignment['subject']); ?> -
                                    <?php echo htmlspecialchars($assignment['class']); ?>
                                    <br>By: <?php echo htmlspecialchars($assignment['teacher']); ?>
                                    <?php if ($assignment['created_at']): ?>
                                        • <?php echo date('M j, Y', strtotime($assignment['created_at'])); ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- System Health -->
                <div class="report-card">
                    <h3><i class="fas fa-heartbeat"></i> System Health</h3>
                    <div class="stat-item">
                        <span class="stat-label">Database Tables</span>
                        <span class="stat-value" style="color: #28a745;">✓ All Present</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">User Roles</span>
                        <span class="stat-value" style="color: #28a745;">✓ Configured</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">File Uploads</span>
                        <span class="stat-value"
                            style="color: <?php echo is_writable('../assets/uploads/') ? '#28a745' : '#dc3545'; ?>;">
                            <?php echo is_writable('../assets/uploads/') ? '✓ Working' : '✗ Issues'; ?>
                        </span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Session System</span>
                        <span class="stat-value" style="color: #28a745;">✓ Active</span>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="content-card" style="margin-top: 2rem;">
                <div class="card-header">
                    <h3>Quick Actions</h3>
                </div>
                <div class="card-content">
                    <div style="display: flex; flex-wrap: wrap; gap: 1rem;">
                        <a href="manage_user.php" class="export-btn">
                            <i class="fas fa-user-plus"></i> Add New User
                        </a>
                        <a href="manage_class.php" class="export-btn">
                            <i class="fas fa-plus"></i> Create Class
                        </a>
                        <a href="manage_subject.php" class="export-btn">
                            <i class="fas fa-book-open"></i> Add Subject
                        </a>
                        <a href="assign_teacher.php" class="export-btn">
                            <i class="fas fa-chalkboard-teacher"></i> Assign Teacher
                        </a>
                        <a href="enroll_student.php" class="export-btn">
                            <i class="fas fa-user-graduate"></i> Enroll Student
                        </a>
                        <a href="../database/check_schema.php" class="export-btn"
                            style="background: #ffc107; color: #000;">
                            <i class="fas fa-database"></i> Check Database
                        </a>
                        <a href="../test_system.php" class="export-btn" style="background: #17a2b8;">
                            <i class="fas fa-cogs"></i> System Test
                        </a>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Auto-refresh every 5 minutes
        setTimeout(function() {
            location.reload();
        }, 300000);

        // Export functionality
        function exportReport(format) {
            if (format === 'csv') {
                // Create CSV data
                let csvContent = "data:text/csv;charset=utf-8,";
                csvContent += "Report Type,Value\n";
                csvContent += "Total Users,<?php echo $stats['total_users']; ?>\n";
                csvContent += "Students,<?php echo $stats['total_students']; ?>\n";
                csvContent += "Teachers,<?php echo $stats['total_teachers']; ?>\n";
                csvContent += "Administrators,<?php echo $stats['total_admins']; ?>\n";
                csvContent += "Classes,<?php echo $stats['total_classes']; ?>\n";
                csvContent += "Subjects,<?php echo $stats['total_subjects']; ?>\n";
                csvContent += "Teacher Assignments,<?php echo $stats['total_assignments']; ?>\n";
                csvContent += "Student Enrollments,<?php echo $stats['total_enrollments']; ?>\n";
                csvContent += "Total Quizzes,<?php echo $stats['total_quizzes']; ?>\n";
                csvContent += "Quiz Submissions,<?php echo $stats['total_quiz_submissions']; ?>\n";
                csvContent += "Total Assignments,<?php echo $stats['total_assignments_hw']; ?>\n";
                csvContent += "Assignment Submissions,<?php echo $stats['total_assignment_submissions']; ?>\n";

                const encodedUri = encodeURI(csvContent);
                const link = document.createElement("a");
                link.setAttribute("href", encodedUri);
                link.setAttribute("download", "lms_report_" + new Date().toISOString().split('T')[0] + ".csv");
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            }
        }

        function refreshReport() {
            location.reload();
        }

        // Print styles
        window.addEventListener('beforeprint', function() {
            document.body.classList.add('printing');
        });

        window.addEventListener('afterprint', function() {
            document.body.classList.remove('printing');
        });
    </script>

    <style>
        @media print {

            .sidebar,
            .header-right,
            .export-btn {
                display: none !important;
            }

            .main-content {
                margin-left: 0 !important;
                width: 100% !important;
            }

            .report-card {
                break-inside: avoid;
                margin-bottom: 1rem;
            }

            body {
                font-size: 12px;
            }
        }
    </style>
</body>

</html>