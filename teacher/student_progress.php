<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'teacher') {
    header('Location: ../login.php');
    exit;
}

$teacher_id = $_SESSION['user_id'];
$student_id = intval($_GET['id'] ?? 0);

if ($student_id <= 0) {
    header('Location: view_students.php');
    exit;
}

// Get student information
$student_query = "SELECT * FROM users WHERE id = ? AND role = 'student'";
$student_stmt = $conn->prepare($student_query);
$student_stmt->bind_param("i", $student_id);
$student_stmt->execute();
$student = $student_stmt->get_result()->fetch_assoc();

if (!$student) {
    header('Location: view_students.php');
    exit;
}

// Get progress data for charts
$progress_query = "SELECT 
    DATE(qs.submitted_at) as date,
    AVG(qs.percentage) as avg_score,
    COUNT(*) as submissions,
    'quiz' as type
    FROM quiz_submissions qs
    JOIN quizzes q ON qs.quiz_id = q.id
    WHERE qs.student_id = ? AND q.teacher_id = ?
    GROUP BY DATE(qs.submitted_at)
    
    UNION ALL
    
    SELECT
    DATE(asub.submitted_at) as date,
    AVG((asub.score / a.max_marks) * 100) as avg_score,
    COUNT(*) as submissions,
    'assignment' as type
    FROM assignment_submissions asub
    JOIN assignments a ON asub.assignment_id = a.id
    WHERE asub.student_id = ? AND a.teacher_id = ?
    GROUP BY DATE(asub.submitted_at)
    
    ORDER BY date DESC
    LIMIT 30";

$progress_stmt = $conn->prepare($progress_query);
$progress_stmt->bind_param("iiii", $student_id, $teacher_id, $student_id, $teacher_id);
$progress_stmt->execute();
$progress_data = $progress_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get subject-wise performance
$subject_performance_query = "SELECT 
    s.name as subject_name,
    AVG(qs.percentage) as quiz_avg,
    COUNT(DISTINCT qs.id) as quiz_count,
    AVG((asub.score / a.max_marks) * 100) as assignment_avg,
    COUNT(DISTINCT asub.id) as assignment_count
    FROM subjects s
    LEFT JOIN quizzes q ON s.id = q.subject_id AND q.teacher_id = ?
    LEFT JOIN quiz_submissions qs ON q.id = qs.quiz_id AND qs.student_id = ?
    LEFT JOIN assignments a ON s.id = a.subject_id AND a.teacher_id = ?
    LEFT JOIN assignment_submissions asub ON a.id = asub.assignment_id AND asub.student_id = ?
    WHERE s.id IN (
        SELECT DISTINCT tsc.subject_id 
        FROM teacher_subject_class tsc 
        JOIN student_class sc ON tsc.class_id = sc.class_id 
        WHERE tsc.teacher_id = ? AND sc.student_id = ?
    )
    GROUP BY s.id, s.name
    ORDER BY s.name";

$subject_stmt = $conn->prepare($subject_performance_query);
$subject_stmt->bind_param("iiiiii", $teacher_id, $student_id, $teacher_id, $student_id, $teacher_id, $student_id);
$subject_stmt->execute();
$subject_performance = $subject_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($student['name']); ?> - Progress Report</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .progress-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 16px;
            margin-bottom: 2rem;
        }

        .progress-header h1 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .progress-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .progress-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        .card-header {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            font-size: 1.25rem;
            font-weight: 700;
            color: #1f2937;
        }

        .chart-container {
            position: relative;
            height: 300px;
        }

        .subject-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .subject-item {
            background: #f8fafc;
            padding: 1.5rem;
            border-radius: 12px;
            border-left: 4px solid #667eea;
        }

        .subject-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .subject-name {
            font-weight: 600;
            color: #1f2937;
            font-size: 1.1rem;
        }

        .subject-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .stat-group {
            text-align: center;
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }

        .stat-label {
            color: #6b7280;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .progress-bar {
            width: 100%;
            height: 8px;
            background: #e5e7eb;
            border-radius: 4px;
            overflow: hidden;
            margin-top: 0.5rem;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            transition: width 0.3s ease;
        }

        .insights-card {
            grid-column: 1 / -1;
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        .insight-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: #f8fafc;
            border-radius: 12px;
            margin-bottom: 1rem;
        }

        .insight-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
        }

        .insight-positive {
            background: #16a34a;
        }

        .insight-warning {
            background: #f59e0b;
        }

        .insight-info {
            background: #3b82f6;
        }

        @media (max-width: 768px) {
            .progress-grid {
                grid-template-columns: 1fr;
            }

            .subject-stats {
                grid-template-columns: 1fr;
            }
        }
    </style>
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
                <a href="manage_quiz.php" class="nav-item">
                    <i class="fas fa-question-circle"></i>
                    <span>Manage Quizzes</span>
                </a>
                <a href="manage_assignment.php" class="nav-item">
                    <i class="fas fa-tasks"></i>
                    <span>Manage Assignments</span>
                </a>
                <a href="view_students.php" class="nav-item active">
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

        <!-- Main Content -->
        <main class="main-content">
            <!-- Back Button -->
            <div style="margin-bottom: 1rem;">
                <a href="view_students.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Students
                </a>
                <a href="student_profile.php?id=<?php echo $student_id; ?>" class="btn btn-primary">
                    <i class="fas fa-user"></i> View Profile
                </a>
                <a href="student_grades.php?id=<?php echo $student_id; ?>" class="btn btn-primary">
                    <i class="fas fa-chart-bar"></i> View Grades
                </a>
            </div>

            <!-- Header -->
            <div class="progress-header">
                <h1><i class="fas fa-chart-line"></i> Progress Report for <?php echo htmlspecialchars($student['name']); ?></h1>
                <p>Detailed academic progress analysis and insights</p>
            </div>

            <!-- Progress Grid -->
            <div class="progress-grid">
                <!-- Performance Chart -->
                <div class="progress-card">
                    <div class="card-header">
                        <i class="fas fa-chart-line"></i>
                        Performance Trend
                    </div>
                    <div class="chart-container">
                        <canvas id="performanceChart"></canvas>
                    </div>
                </div>

                <!-- Subject Performance -->
                <div class="progress-card">
                    <div class="card-header">
                        <i class="fas fa-book"></i>
                        Subject Performance
                    </div>
                    <div class="subject-list">
                        <?php if (empty($subject_performance)): ?>
                            <p style="color: #6b7280; text-align: center; padding: 2rem;">No performance data available</p>
                        <?php else: ?>
                            <?php foreach ($subject_performance as $subject): ?>
                                <?php
                                $quiz_avg = $subject['quiz_avg'] ?? 0;
                                $assignment_avg = $subject['assignment_avg'] ?? 0;
                                $overall_avg = ($quiz_avg + $assignment_avg) / 2;
                                ?>
                                <div class="subject-item">
                                    <div class="subject-header">
                                        <div class="subject-name"><?php echo htmlspecialchars($subject['subject_name']); ?></div>
                                    </div>
                                    <div class="subject-stats">
                                        <div class="stat-group">
                                            <div class="stat-value" style="color: <?php echo $quiz_avg >= 80 ? '#16a34a' : ($quiz_avg >= 60 ? '#f59e0b' : '#dc2626'); ?>">
                                                <?php echo number_format($quiz_avg, 1); ?>%
                                            </div>
                                            <div class="stat-label">Quiz Average (<?php echo $subject['quiz_count']; ?>)</div>
                                        </div>
                                        <div class="stat-group">
                                            <div class="stat-value" style="color: <?php echo $assignment_avg >= 80 ? '#16a34a' : ($assignment_avg >= 60 ? '#f59e0b' : '#dc2626'); ?>">
                                                <?php echo number_format($assignment_avg, 1); ?>%
                                            </div>
                                            <div class="stat-label">Assignment Average (<?php echo $subject['assignment_count']; ?>)</div>
                                        </div>
                                    </div>
                                    <div class="progress-bar">
                                        <div class="progress-fill" style="width: <?php echo min($overall_avg, 100); ?>%"></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Insights -->
                <div class="insights-card">
                    <div class="card-header">
                        <i class="fas fa-lightbulb"></i>
                        Performance Insights
                    </div>

                    <?php
                    $insights = [];

                    // Calculate overall performance
                    $total_quiz_avg = 0;
                    $total_assignment_avg = 0;
                    $subject_count = 0;

                    foreach ($subject_performance as $subject) {
                        if ($subject['quiz_avg'] > 0 || $subject['assignment_avg'] > 0) {
                            $total_quiz_avg += $subject['quiz_avg'] ?? 0;
                            $total_assignment_avg += $subject['assignment_avg'] ?? 0;
                            $subject_count++;
                        }
                    }

                    if ($subject_count > 0) {
                        $avg_quiz = $total_quiz_avg / $subject_count;
                        $avg_assignment = $total_assignment_avg / $subject_count;

                        if ($avg_quiz >= 85) {
                            $insights[] = ['type' => 'positive', 'text' => 'Excellent quiz performance! Keep up the great work.'];
                        } elseif ($avg_quiz < 60) {
                            $insights[] = ['type' => 'warning', 'text' => 'Quiz scores need improvement. Consider additional practice.'];
                        }

                        if ($avg_assignment >= 85) {
                            $insights[] = ['type' => 'positive', 'text' => 'Outstanding assignment submissions and quality.'];
                        } elseif ($avg_assignment < 60) {
                            $insights[] = ['type' => 'warning', 'text' => 'Assignment performance could be better. Provide additional support.'];
                        }

                        if (abs($avg_quiz - $avg_assignment) > 20) {
                            $insights[] = ['type' => 'info', 'text' => 'Significant difference between quiz and assignment performance.'];
                        }
                    }

                    if (empty($insights)) {
                        $insights[] = ['type' => 'info', 'text' => 'More data needed for detailed insights. Encourage more participation.'];
                    }
                    ?>

                    <?php foreach ($insights as $insight): ?>
                        <div class="insight-item">
                            <div class="insight-icon insight-<?php echo $insight['type']; ?>">
                                <i class="fas fa-<?php echo $insight['type'] === 'positive' ? 'check' : ($insight['type'] === 'warning' ? 'exclamation' : 'info'); ?>"></i>
                            </div>
                            <div><?php echo $insight['text']; ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Performance Chart
        const ctx = document.getElementById('performanceChart').getContext('2d');

        // Prepare chart data
        const progressData = <?php echo json_encode($progress_data); ?>;
        const dates = [...new Set(progressData.map(item => item.date))].sort().slice(-10);

        const quizData = dates.map(date => {
            const item = progressData.find(p => p.date === date && p.type === 'quiz');
            return item ? parseFloat(item.avg_score) : null;
        });

        const assignmentData = dates.map(date => {
            const item = progressData.find(p => p.date === date && p.type === 'assignment');
            return item ? parseFloat(item.avg_score) : null;
        });

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: dates.map(date => new Date(date).toLocaleDateString()),
                datasets: [{
                    label: 'Quiz Performance',
                    data: quizData,
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    tension: 0.4,
                    fill: false
                }, {
                    label: 'Assignment Performance',
                    data: assignmentData,
                    borderColor: '#764ba2',
                    backgroundColor: 'rgba(118, 75, 162, 0.1)',
                    tension: 0.4,
                    fill: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + context.parsed.y.toFixed(1) + '%';
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>

</html>