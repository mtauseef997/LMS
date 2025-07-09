<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'teacher') {
    header('Location: ../login.php');
    exit;
}

$teacher_id = $_SESSION['user_id'];

// Get comprehensive student data
$students_query = "SELECT DISTINCT 
    u.id, u.name, u.email, u.created_at,
    COUNT(DISTINCT sc.class_id) as total_classes,
    COUNT(DISTINCT qs.id) as quiz_submissions,
    AVG(qs.percentage) as quiz_average,
    COUNT(DISTINCT asub.id) as assignment_submissions,
    AVG((asub.score / a.max_marks) * 100) as assignment_average,
    MAX(GREATEST(COALESCE(qs.submitted_at, '1970-01-01'), COALESCE(asub.submitted_at, '1970-01-01'))) as last_activity
    FROM users u
    JOIN student_class sc ON u.id = sc.student_id
    JOIN teacher_subject_class tsc ON sc.class_id = tsc.class_id
    LEFT JOIN quiz_submissions qs ON u.id = qs.student_id
    LEFT JOIN quizzes q ON qs.quiz_id = q.id AND q.teacher_id = ?
    LEFT JOIN assignment_submissions asub ON u.id = asub.student_id
    LEFT JOIN assignments a ON asub.assignment_id = a.id AND a.teacher_id = ?
    WHERE tsc.teacher_id = ? AND u.role = 'student'
    GROUP BY u.id, u.name, u.email, u.created_at
    ORDER BY u.name";

$students_stmt = $conn->prepare($students_query);
$students_stmt->bind_param("iii", $teacher_id, $teacher_id, $teacher_id);
$students_stmt->execute();
$students = $students_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get teacher's classes and subjects for filters
$classes_query = "SELECT DISTINCT c.id, c.name FROM classes c 
                 JOIN teacher_subject_class tsc ON c.id = tsc.class_id 
                 WHERE tsc.teacher_id = ? ORDER BY c.name";
$classes_stmt = $conn->prepare($classes_query);
$classes_stmt->bind_param("i", $teacher_id);
$classes_stmt->execute();
$classes = $classes_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$subjects_query = "SELECT DISTINCT s.id, s.name FROM subjects s 
                  JOIN teacher_subject_class tsc ON s.id = tsc.subject_id 
                  WHERE tsc.teacher_id = ? ORDER BY s.name";
$subjects_stmt = $conn->prepare($subjects_query);
$subjects_stmt->bind_param("i", $teacher_id);
$subjects_stmt->execute();
$subjects = $subjects_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Calculate statistics
$total_students = count($students);
$active_students = 0;
$high_performers = 0;
$needs_attention = 0;

foreach ($students as $student) {
    $overall_avg = (($student['quiz_average'] ?? 0) + ($student['assignment_average'] ?? 0)) / 2;

    if ($student['last_activity'] && strtotime($student['last_activity']) > strtotime('-7 days')) {
        $active_students++;
    }

    if ($overall_avg >= 85) {
        $high_performers++;
    } elseif ($overall_avg < 60) {
        $needs_attention++;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Students Dashboard - Teacher Panel</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
    /* Modern Students Dashboard Styles */
    .students-hero {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 3rem 2rem;
        border-radius: 24px;
        margin-bottom: 2rem;
        position: relative;
        overflow: hidden;
    }

    .students-hero::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="dots" width="20" height="20" patternUnits="userSpaceOnUse"><circle cx="10" cy="10" r="1" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23dots)"/></svg>');
        pointer-events: none;
    }

    .hero-content {
        position: relative;
        z-index: 1;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 2rem;
    }

    .hero-text h1 {
        font-size: 3rem;
        font-weight: 800;
        margin-bottom: 1rem;
        background: linear-gradient(45deg, #ffffff, #e0e7ff);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .hero-text p {
        font-size: 1.2rem;
        opacity: 0.9;
        line-height: 1.6;
    }

    .hero-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
        gap: 1.5rem;
        min-width: 300px;
    }

    .hero-stat {
        background: rgba(255, 255, 255, 0.15);
        backdrop-filter: blur(10px);
        padding: 1.5rem;
        border-radius: 16px;
        text-align: center;
        border: 1px solid rgba(255, 255, 255, 0.2);
    }

    .hero-stat-number {
        font-size: 2.5rem;
        font-weight: 800;
        margin-bottom: 0.5rem;
        display: block;
    }

    .hero-stat-label {
        font-size: 0.9rem;
        opacity: 0.8;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    /* Control Panel */
    .control-panel {
        background: white;
        padding: 2rem;
        border-radius: 20px;
        margin-bottom: 2rem;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.2);
    }

    .control-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .control-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: #1f2937;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .view-toggles {
        display: flex;
        background: #f1f5f9;
        border-radius: 12px;
        padding: 0.25rem;
        gap: 0.25rem;
    }

    .view-toggle {
        padding: 0.75rem 1.5rem;
        border: none;
        background: transparent;
        border-radius: 10px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        color: #64748b;
    }

    .view-toggle.active {
        background: white;
        color: #667eea;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .filters-row {
        display: grid;
        grid-template-columns: 2fr 1fr 1fr auto auto;
        gap: 1rem;
        align-items: end;
    }

    .search-box {
        position: relative;
    }

    .search-input {
        width: 100%;
        padding: 1rem 1rem 1rem 3rem;
        border: 2px solid #e2e8f0;
        border-radius: 16px;
        font-size: 1rem;
        transition: all 0.3s ease;
        background: #f8fafc;
    }

    .search-input:focus {
        outline: none;
        border-color: #667eea;
        background: white;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    .search-icon {
        position: absolute;
        left: 1rem;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        font-size: 1.1rem;
    }

    .filter-select {
        padding: 1rem;
        border: 2px solid #e2e8f0;
        border-radius: 16px;
        background: #f8fafc;
        font-size: 1rem;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .filter-select:focus {
        outline: none;
        border-color: #667eea;
        background: white;
    }

    .action-btn {
        padding: 1rem 1.5rem;
        border: none;
        border-radius: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        text-decoration: none;
    }

    .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }

    .btn-secondary {
        background: #f1f5f9;
        color: #475569;
        border: 2px solid #e2e8f0;
    }

    .action-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
    }

    /* Students Grid */
    .students-container {
        margin-bottom: 2rem;
    }

    .students-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
        gap: 1.5rem;
    }

    .student-card {
        background: white;
        border-radius: 20px;
        padding: 0;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.2);
        transition: all 0.4s ease;
        overflow: hidden;
        position: relative;
    }

    .student-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    .student-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
    }

    .card-header {
        padding: 2rem 2rem 1rem;
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .student-avatar {
        width: 70px;
        height: 70px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.8rem;
        font-weight: 700;
        box-shadow: 0 8px 24px rgba(102, 126, 234, 0.3);
        position: relative;
    }

    .student-avatar::after {
        content: '';
        position: absolute;
        inset: -3px;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea, #764ba2);
        z-index: -1;
        opacity: 0.3;
    }

    .student-info h3 {
        font-size: 1.3rem;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 0.25rem;
    }

    .student-email {
        color: #64748b;
        font-size: 0.9rem;
        margin-bottom: 0.5rem;
    }

    .student-meta {
        display: flex;
        align-items: center;
        gap: 1rem;
        font-size: 0.8rem;
        color: #94a3b8;
    }

    .meta-item {
        display: flex;
        align-items: center;
        gap: 0.25rem;
    }

    .card-body {
        padding: 0 2rem 1rem;
    }

    .performance-overview {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .perf-metric {
        background: #f8fafc;
        padding: 1rem;
        border-radius: 12px;
        text-align: center;
    }

    .perf-value {
        font-size: 1.5rem;
        font-weight: 700;
        margin-bottom: 0.25rem;
    }

    .perf-label {
        font-size: 0.8rem;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .activity-status {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 1rem;
        padding: 0.75rem;
        background: #f8fafc;
        border-radius: 12px;
    }

    .status-indicator {
        width: 8px;
        height: 8px;
        border-radius: 50%;
    }

    .status-active {
        background: #10b981;
    }

    .status-inactive {
        background: #f59e0b;
    }

    .status-offline {
        background: #ef4444;
    }

    .card-actions {
        padding: 1rem 2rem 2rem;
        display: flex;
        gap: 0.5rem;
    }

    .card-btn {
        flex: 1;
        padding: 0.75rem;
        border: none;
        border-radius: 12px;
        font-size: 0.875rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        text-align: center;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }

    .btn-view {
        background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
        color: white;
    }

    .btn-grades {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
    }

    .btn-progress {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        color: white;
    }

    .card-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    /* List View */
    .students-list {
        background: white;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
    }

    .list-header {
        background: #f8fafc;
        padding: 1.5rem 2rem;
        border-bottom: 1px solid #e2e8f0;
        font-weight: 600;
        color: #374151;
        display: grid;
        grid-template-columns: 2fr 1fr 1fr 1fr 1fr auto;
        gap: 1rem;
        align-items: center;
    }

    .list-item {
        padding: 1.5rem 2rem;
        border-bottom: 1px solid #f1f5f9;
        display: grid;
        grid-template-columns: 2fr 1fr 1fr 1fr 1fr auto;
        gap: 1rem;
        align-items: center;
        transition: all 0.3s ease;
    }

    .list-item:hover {
        background: #f8fafc;
    }

    .list-student {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .list-avatar {
        width: 40px;
        height: 40px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 600;
    }

    .list-actions {
        display: flex;
        gap: 0.5rem;
    }

    .list-btn {
        padding: 0.5rem;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.3s ease;
        color: white;
        text-decoration: none;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 4rem 2rem;
        background: white;
        border-radius: 20px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
    }

    .empty-icon {
        font-size: 4rem;
        color: #d1d5db;
        margin-bottom: 1rem;
    }

    .empty-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: #374151;
        margin-bottom: 0.5rem;
    }

    .empty-text {
        color: #6b7280;
        margin-bottom: 2rem;
    }

    /* Responsive Design */
    @media (max-width: 1024px) {
        .filters-row {
            grid-template-columns: 1fr;
            gap: 1rem;
        }

        .students-grid {
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        }
    }

    @media (max-width: 768px) {
        .hero-content {
            flex-direction: column;
            text-align: center;
        }

        .hero-text h1 {
            font-size: 2rem;
        }

        .students-grid {
            grid-template-columns: 1fr;
        }

        .performance-overview {
            grid-template-columns: 1fr;
        }

        .list-header,
        .list-item {
            grid-template-columns: 1fr;
            gap: 0.5rem;
        }
    }

    /* Animations */
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .student-card {
        animation: fadeInUp 0.6s ease forwards;
    }

    .student-card:nth-child(1) {
        animation-delay: 0.1s;
    }

    .student-card:nth-child(2) {
        animation-delay: 0.2s;
    }

    .student-card:nth-child(3) {
        animation-delay: 0.3s;
    }

    .student-card:nth-child(4) {
        animation-delay: 0.4s;
    }

    .student-card:nth-child(5) {
        animation-delay: 0.5s;
    }

    .student-card:nth-child(6) {
        animation-delay: 0.6s;
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
                <a href="students_dashboard.php" class="nav-item active">
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
            <!-- Hero Section -->
            <div class="students-hero">
                <div class="hero-content">
                    <div class="hero-text">
                        <h1><i class="fas fa-users"></i> My Students</h1>
                        <p>Comprehensive student management and performance tracking dashboard</p>
                    </div>
                    <div class="hero-stats">
                        <div class="hero-stat">
                            <span class="hero-stat-number"><?php echo $total_students; ?></span>
                            <span class="hero-stat-label">Total Students</span>
                        </div>
                        <div class="hero-stat">
                            <span class="hero-stat-number"><?php echo $active_students; ?></span>
                            <span class="hero-stat-label">Active This Week</span>
                        </div>
                        <div class="hero-stat">
                            <span class="hero-stat-number"><?php echo $high_performers; ?></span>
                            <span class="hero-stat-label">High Performers</span>
                        </div>
                        <div class="hero-stat">
                            <span class="hero-stat-number"><?php echo $needs_attention; ?></span>
                            <span class="hero-stat-label">Need Attention</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Control Panel -->
            <div class="control-panel">
                <div class="control-header">
                    <div class="control-title">
                        <i class="fas fa-sliders-h"></i>
                        Student Management
                    </div>
                    <div class="view-toggles">
                        <button class="view-toggle active" onclick="switchView('grid')">
                            <i class="fas fa-th-large"></i> Grid View
                        </button>
                        <button class="view-toggle" onclick="switchView('list')">
                            <i class="fas fa-list"></i> List View
                        </button>
                    </div>
                </div>

                <div class="filters-row">
                    <div class="search-box">
                        <input type="text" class="search-input" placeholder="Search students by name or email..."
                            id="searchInput">
                        <i class="fas fa-search search-icon"></i>
                    </div>

                    <select class="filter-select" id="classFilter">
                        <option value="">All Classes</option>
                        <?php foreach ($classes as $class): ?>
                        <option value="<?php echo $class['id']; ?>"><?php echo htmlspecialchars($class['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>

                    <select class="filter-select" id="subjectFilter">
                        <option value="">All Subjects</option>
                        <?php foreach ($subjects as $subject): ?>
                        <option value="<?php echo $subject['id']; ?>"><?php echo htmlspecialchars($subject['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>

                    <select class="filter-select" id="performanceFilter">
                        <option value="">All Performance</option>
                        <option value="excellent">Excellent (85%+)</option>
                        <option value="good">Good (70-84%)</option>
                        <option value="average">Average (60-69%)</option>
                        <option value="needs-attention">Needs Attention (<60%)< /option>
                    </select>

                    <a href="add_student.php" class="action-btn btn-primary">
                        <i class="fas fa-plus"></i>
                        Add Student
                    </a>
                </div>
            </div>

            <!-- Students Container -->
            <div class="students-container">
                <!-- Grid View -->
                <div id="gridView" class="students-grid">
                    <?php if (empty($students)): ?>
                    <div class="empty-state" style="grid-column: 1 / -1;">
                        <i class="fas fa-user-graduate empty-icon"></i>
                        <h3 class="empty-title">No Students Found</h3>
                        <p class="empty-text">You don't have any students assigned to your classes yet.</p>
                        <a href="add_student.php" class="action-btn btn-primary">
                            <i class="fas fa-plus"></i>
                            Add Your First Student
                        </a>
                    </div>
                    <?php else: ?>
                    <?php foreach ($students as $student): ?>
                    <?php
                            $quiz_avg = $student['quiz_average'] ?? 0;
                            $assignment_avg = $student['assignment_average'] ?? 0;
                            $overall_avg = ($quiz_avg + $assignment_avg) / 2;

                            // Determine activity status
                            $last_activity = $student['last_activity'];
                            $activity_status = 'offline';
                            $activity_text = 'No recent activity';

                            if ($last_activity && strtotime($last_activity) > strtotime('-1 day')) {
                                $activity_status = 'active';
                                $activity_text = 'Active today';
                            } elseif ($last_activity && strtotime($last_activity) > strtotime('-7 days')) {
                                $activity_status = 'inactive';
                                $activity_text = 'Active this week';
                            }

                            // Performance color
                            $perf_color = '#ef4444';
                            if ($overall_avg >= 85) $perf_color = '#10b981';
                            elseif ($overall_avg >= 70) $perf_color = '#3b82f6';
                            elseif ($overall_avg >= 60) $perf_color = '#f59e0b';
                            ?>
                    <div class="student-card" data-student-id="<?php echo $student['id']; ?>"
                        data-performance="<?php echo $overall_avg; ?>">
                        <div class="card-header">
                            <div class="student-avatar">
                                <?php echo strtoupper(substr($student['name'], 0, 1)); ?>
                            </div>
                            <div class="student-info">
                                <h3><?php echo htmlspecialchars($student['name']); ?></h3>
                                <div class="student-email"><?php echo htmlspecialchars($student['email']); ?></div>
                                <div class="student-meta">
                                    <div class="meta-item">
                                        <i class="fas fa-calendar-alt"></i>
                                        Joined <?php echo date('M Y', strtotime($student['created_at'])); ?>
                                    </div>
                                    <div class="meta-item">
                                        <i class="fas fa-book"></i>
                                        <?php echo $student['total_classes']; ?> Classes
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card-body">
                            <div class="performance-overview">
                                <div class="perf-metric">
                                    <div class="perf-value"
                                        style="color: <?php echo $quiz_avg >= 80 ? '#10b981' : ($quiz_avg >= 60 ? '#f59e0b' : '#ef4444'); ?>">
                                        <?php echo number_format($quiz_avg, 1); ?>%
                                    </div>
                                    <div class="perf-label">Quiz Average</div>
                                </div>
                                <div class="perf-metric">
                                    <div class="perf-value"
                                        style="color: <?php echo $assignment_avg >= 80 ? '#10b981' : ($assignment_avg >= 60 ? '#f59e0b' : '#ef4444'); ?>">
                                        <?php echo number_format($assignment_avg, 1); ?>%
                                    </div>
                                    <div class="perf-label">Assignment Average</div>
                                </div>
                            </div>

                            <div class="activity-status">
                                <div class="status-indicator status-<?php echo $activity_status; ?>"></div>
                                <span><?php echo $activity_text; ?></span>
                                <div style="margin-left: auto; font-size: 0.8rem; color: #64748b;">
                                    <?php echo $student['quiz_submissions'] + $student['assignment_submissions']; ?>
                                    submissions
                                </div>
                            </div>
                        </div>

                        <div class="card-actions">
                            <a href="student_profile.php?id=<?php echo $student['id']; ?>" class="card-btn btn-view">
                                <i class="fas fa-user"></i>
                                Profile
                            </a>
                            <a href="student_grades.php?id=<?php echo $student['id']; ?>" class="card-btn btn-grades">
                                <i class="fas fa-chart-bar"></i>
                                Grades
                            </a>
                            <a href="student_progress.php?id=<?php echo $student['id']; ?>"
                                class="card-btn btn-progress">
                                <i class="fas fa-chart-line"></i>
                                Progress
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- List View -->
                <div id="listView" class="students-list" style="display: none;">
                    <div class="list-header">
                        <div>Student</div>
                        <div>Quiz Avg</div>
                        <div>Assignment Avg</div>
                        <div>Overall</div>
                        <div>Activity</div>
                        <div>Actions</div>
                    </div>
                    <?php foreach ($students as $student): ?>
                    <?php
                        $quiz_avg = $student['quiz_average'] ?? 0;
                        $assignment_avg = $student['assignment_average'] ?? 0;
                        $overall_avg = ($quiz_avg + $assignment_avg) / 2;

                        $last_activity = $student['last_activity'];
                        $activity_status = 'offline';
                        $activity_text = 'Offline';

                        if ($last_activity && strtotime($last_activity) > strtotime('-1 day')) {
                            $activity_status = 'active';
                            $activity_text = 'Active';
                        } elseif ($last_activity && strtotime($last_activity) > strtotime('-7 days')) {
                            $activity_status = 'inactive';
                            $activity_text = 'Recent';
                        }
                        ?>
                    <div class="list-item" data-student-id="<?php echo $student['id']; ?>"
                        data-performance="<?php echo $overall_avg; ?>">
                        <div class="list-student">
                            <div class="list-avatar">
                                <?php echo strtoupper(substr($student['name'], 0, 1)); ?>
                            </div>
                            <div>
                                <div style="font-weight: 600; color: #1f2937;">
                                    <?php echo htmlspecialchars($student['name']); ?></div>
                                <div style="font-size: 0.875rem; color: #64748b;">
                                    <?php echo htmlspecialchars($student['email']); ?></div>
                            </div>
                        </div>
                        <div
                            style="color: <?php echo $quiz_avg >= 80 ? '#10b981' : ($quiz_avg >= 60 ? '#f59e0b' : '#ef4444'); ?>; font-weight: 600;">
                            <?php echo number_format($quiz_avg, 1); ?>%
                        </div>
                        <div
                            style="color: <?php echo $assignment_avg >= 80 ? '#10b981' : ($assignment_avg >= 60 ? '#f59e0b' : '#ef4444'); ?>; font-weight: 600;">
                            <?php echo number_format($assignment_avg, 1); ?>%
                        </div>
                        <div
                            style="color: <?php echo $overall_avg >= 80 ? '#10b981' : ($overall_avg >= 60 ? '#f59e0b' : '#ef4444'); ?>; font-weight: 600;">
                            <?php echo number_format($overall_avg, 1); ?>%
                        </div>
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <div class="status-indicator status-<?php echo $activity_status; ?>"></div>
                            <?php echo $activity_text; ?>
                        </div>
                        <div class="list-actions">
                            <a href="student_profile.php?id=<?php echo $student['id']; ?>" class="list-btn"
                                style="background: #3b82f6;" title="View Profile">
                                <i class="fas fa-user"></i>
                            </a>
                            <a href="student_grades.php?id=<?php echo $student['id']; ?>" class="list-btn"
                                style="background: #10b981;" title="View Grades">
                                <i class="fas fa-chart-bar"></i>
                            </a>
                            <a href="student_progress.php?id=<?php echo $student['id']; ?>" class="list-btn"
                                style="background: #f59e0b;" title="View Progress">
                                <i class="fas fa-chart-line"></i>
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
    // View switching functionality
    function switchView(view) {
        const gridView = document.getElementById('gridView');
        const listView = document.getElementById('listView');
        const toggles = document.querySelectorAll('.view-toggle');

        toggles.forEach(toggle => toggle.classList.remove('active'));

        if (view === 'grid') {
            gridView.style.display = 'grid';
            listView.style.display = 'none';
            toggles[0].classList.add('active');
        } else {
            gridView.style.display = 'none';
            listView.style.display = 'block';
            toggles[1].classList.add('active');
        }
    }

    // Search and filter functionality
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('searchInput');
        const performanceFilter = document.getElementById('performanceFilter');

        function filterStudents() {
            const searchTerm = searchInput.value.toLowerCase();
            const performanceValue = performanceFilter.value;

            const studentCards = document.querySelectorAll('.student-card, .list-item');

            studentCards.forEach(card => {
                const studentName = card.querySelector('h3, .list-student div div').textContent
                    .toLowerCase();
                const studentEmail = card.querySelector(
                    '.student-email, .list-student div div:last-child').textContent.toLowerCase();
                const performance = parseFloat(card.dataset.performance);

                let showCard = true;

                // Search filter
                if (searchTerm && !studentName.includes(searchTerm) && !studentEmail.includes(
                        searchTerm)) {
                    showCard = false;
                }

                // Performance filter
                if (performanceValue) {
                    switch (performanceValue) {
                        case 'excellent':
                            if (performance < 85) showCard = false;
                            break;
                        case 'good':
                            if (performance < 70 || performance >= 85) showCard = false;
                            break;
                        case 'average':
                            if (performance < 60 || performance >= 70) showCard = false;
                            break;
                        case 'needs-attention':
                            if (performance >= 60) showCard = false;
                            break;
                    }
                }

                card.style.display = showCard ? '' : 'none';
            });
        }

        // Add event listeners
        searchInput.addEventListener('input', filterStudents);
        performanceFilter.addEventListener('change', filterStudents);

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                searchInput.focus();
            }

            if (e.key === 'Escape') {
                searchInput.value = '';
                filterStudents();
            }
        });

        // Add loading animations
        const cards = document.querySelectorAll('.student-card');
        cards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';

            setTimeout(() => {
                card.style.transition = 'all 0.6s ease';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, index * 100);
        });
    });
    // Add search highlighting
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        if (searchTerm.length > 0) {
            const studentNames = document.querySelectorAll(
                '.student-info h3, .list-student div div:first-child');
            const studentEmails = document.querySelectorAll('.student-email, .list-student div div:last-child');

            [...studentNames, ...studentEmails].forEach(element => {
                const text = element.textContent;
                const highlightedText = text.replace(
                    new RegExp(`(${searchTerm})`, 'gi'),
                    '<mark style="background: #fef3c7; padding: 0.1rem 0.2rem; border-radius: 3px;">$1</mark>'
                );
                if (highlightedText !== text) {
                    element.innerHTML = highlightedText;
                }
            });
        }
    });

    // Add hover effects to cards
    const allCards = document.querySelectorAll('.student-card, .list-item');
    allCards.forEach(card => {
    card.addEventListener('mouseenter', function() {
        this.style.transform = 'translateY(-5px)';
    });

    card.addEventListener('mouseleave', function() {
        this.style.transform = 'translateY(0)';
    });
    });
    });
    </script>
</body>

</html>