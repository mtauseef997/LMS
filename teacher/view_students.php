<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'teacher') {
    header('Location: ../login.php');
    exit;
}

$teacher_id = $_SESSION['user_id'];


$search = $_GET['search'] ?? '';
$class_filter = $_GET['class'] ?? '';
$subject_filter = $_GET['subject'] ?? '';


$where_conditions = ["tsc.teacher_id = ?", "u.role = 'student'"];
$params = [$teacher_id];
$param_types = "i";

if (!empty($search)) {
    $where_conditions[] = "(u.name LIKE ? OR u.email LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $param_types .= "ss";
}

if (!empty($class_filter)) {
    $where_conditions[] = "c.id = ?";
    $params[] = $class_filter;
    $param_types .= "i";
}

if (!empty($subject_filter)) {
    $where_conditions[] = "s.id = ?";
    $params[] = $subject_filter;
    $param_types .= "i";
}

$students_query = "SELECT DISTINCT u.id, u.name, u.email, u.created_at, c.id as class_id, c.name as class_name, s.id as subject_id, s.name as subject_name
                  FROM users u
                  JOIN student_class sc ON u.id = sc.student_id
                  JOIN classes c ON sc.class_id = c.id
                  JOIN teacher_subject_class tsc ON c.id = tsc.class_id
                  JOIN subjects s ON tsc.subject_id = s.id
                  WHERE " . implode(" AND ", $where_conditions) . "
                  ORDER BY u.name, c.name";

$students_stmt = $conn->prepare($students_query);
$students_stmt->bind_param($param_types, ...$params);
$students_stmt->execute();
$students = $students_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$grouped_students = [];
foreach ($students as $student) {
    $student_id = $student['id'];
    if (!isset($grouped_students[$student_id])) {

        $quiz_stats_query = "SELECT
                            COUNT(*) as total_quizzes,
                            AVG(qs.percentage) as avg_score,
                            COUNT(qs.id) as completed_quizzes
                            FROM quizzes q
                            LEFT JOIN quiz_submissions qs ON q.id = qs.quiz_id AND qs.student_id = ?
                            WHERE q.teacher_id = ?";
        $quiz_stats_stmt = $conn->prepare($quiz_stats_query);
        $quiz_stats_stmt->bind_param("ii", $student_id, $teacher_id);
        $quiz_stats_stmt->execute();
        $quiz_stats = $quiz_stats_stmt->get_result()->fetch_assoc();

        $assignment_stats_query = "SELECT
                                  COUNT(*) as total_assignments,
                                  AVG(asub.score) as avg_marks,
                                  COUNT(asub.id) as completed_assignments
                                  FROM assignments a
                                  LEFT JOIN assignment_submissions asub ON a.id = asub.assignment_id AND asub.student_id = ?
                                  WHERE a.teacher_id = ?";
        $assignment_stats_stmt = $conn->prepare($assignment_stats_query);
        $assignment_stats_stmt->bind_param("ii", $student_id, $teacher_id);
        $assignment_stats_stmt->execute();
        $assignment_stats = $assignment_stats_stmt->get_result()->fetch_assoc();

        $grouped_students[$student_id] = [
            'id' => $student['id'],
            'name' => $student['name'],
            'email' => $student['email'],
            'created_at' => $student['created_at'],
            'classes' => [],
            'quiz_stats' => $quiz_stats,
            'assignment_stats' => $assignment_stats
        ];
    }
    $grouped_students[$student_id]['classes'][] = [
        'class_id' => $student['class_id'],
        'class_name' => $student['class_name'],
        'subject_id' => $student['subject_id'],
        'subject_name' => $student['subject_name']
    ];
}


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
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Students - Teacher Panel</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/teacher.css">
    <style>
        .students-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 16px;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }

        .students-header h1 {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .students-header p {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-bottom: 1.5rem;
        }

        .header-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .header-stat {
            background: rgba(255, 255, 255, 0.1);
            padding: 1rem;
            border-radius: 12px;
            text-align: center;
            backdrop-filter: blur(10px);
        }

        .header-stat-number {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }

        .header-stat-label {
            font-size: 0.9rem;
            opacity: 0.8;
        }


        .search-filter-section {
            background: white;
            padding: 1.5rem;
            border-radius: 16px;
            margin-bottom: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        .search-filter-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr auto;
            gap: 1rem;
            align-items: end;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
        }

        .search-input,
        .filter-select {
            padding: 0.75rem 1rem;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: white;
        }

        .search-input:focus,
        .filter-select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .search-input {
            padding-left: 2.5rem;
        }

        .search-wrapper {
            position: relative;
        }

        .search-icon {
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            font-size: 1.1rem;
        }

        .filter-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .filter-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }


        .students-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .student-card {
            background: white;
            border-radius: 20px;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            border: 1px solid #f1f5f9;
            position: relative;
            overflow: hidden;
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
            transform: translateY(-5px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
        }

        .student-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .student-avatar {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .student-info h3 {
            font-size: 1.25rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 0.25rem;
        }

        .student-info .email {
            color: #6b7280;
            font-size: 0.9rem;
        }

        .student-info .join-date {
            color: #9ca3af;
            font-size: 0.8rem;
            margin-top: 0.25rem;
        }


        .performance-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .stat-item {
            background: #f8fafc;
            padding: 1rem;
            border-radius: 12px;
            text-align: center;
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 0.25rem;
        }

        .stat-label {
            font-size: 0.8rem;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }


        .student-classes {
            margin-bottom: 1.5rem;
        }

        .student-classes h4 {
            font-size: 1rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .classes-list {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .class-badge {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }


        .student-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .action-btn {
            flex: 1;
            min-width: 120px;
            padding: 0.75rem 1rem;
            border: none;
            border-radius: 10px;
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

        .action-btn.primary {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            color: white;
        }

        .action-btn.secondary {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }

        .action-btn.tertiary {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
        }

        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: 20px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        .empty-state i {
            font-size: 4rem;
            color: #d1d5db;
            margin-bottom: 1rem;
        }

        .empty-state h3 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #374151;
            margin-bottom: 0.5rem;
        }

        .empty-state p {
            color: #6b7280;
            font-size: 1rem;
        }


        @media (max-width: 768px) {
            .search-filter-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .students-grid {
                grid-template-columns: 1fr;
            }

            .performance-stats {
                grid-template-columns: 1fr;
            }

            .student-actions {
                flex-direction: column;
            }

            .action-btn {
                min-width: auto;
            }
        }
    </style>
</head>

<body>
    <div class="dashboard-container">

        <aside class="sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-chalkboard-teacher"></i> Teacher Panel</h2>
            </div>

            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-item">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
                <a href="assignments.php" class="nav-item">
                    <i class="fas fa-tasks"></i>
                    <span>My Assignments</span>
                </a>
                <a href="manage_assignment.php" class="nav-item">
                    <i class="fas fa-plus-circle"></i>
                    <span>Create Assignment</span>
                </a>
                <a href="manage_quiz.php" class="nav-item">
                    <i class="fas fa-question-circle"></i>
                    <span>Manage Quizzes</span>
                </a>
                <a href="view_students.php" class="nav-item active">
                    <i class="fas fa-users"></i>
                    <span>My Students</span>
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

            <div class="students-header">
                <h1><i class="fas fa-users"></i> My Students</h1>
                <p>Manage and track your students' academic progress across all your classes</p>

                <div class="header-stats">
                    <div class="header-stat">
                        <div class="header-stat-number"><?php echo count($grouped_students); ?></div>
                        <div class="header-stat-label">Total Students</div>
                    </div>
                    <div class="header-stat">
                        <div class="header-stat-number"><?php echo count($classes); ?></div>
                        <div class="header-stat-label">Classes</div>
                    </div>
                    <div class="header-stat">
                        <div class="header-stat-number"><?php echo count($subjects); ?></div>
                        <div class="header-stat-label">Subjects</div>
                    </div>
                    <div class="header-stat">
                        <div class="header-stat-number">
                            <?php
                            $avg_performance = 0;
                            $total_students = count($grouped_students);
                            if ($total_students > 0) {
                                $total_avg = 0;
                                foreach ($grouped_students as $student) {
                                    $total_avg += $student['quiz_stats']['avg_score'] ?? 0;
                                }
                                $avg_performance = round($total_avg / $total_students, 1);
                            }
                            echo $avg_performance . '%';
                            ?>
                        </div>
                        <div class="header-stat-label">Avg Performance</div>
                    </div>
                </div>
            </div>


            <div class="search-filter-section">
                <form method="GET" action="">
                    <div class="search-filter-grid">
                        <div class="form-group">
                            <label for="search">Search Students</label>
                            <div class="search-wrapper">
                                <input type="text" id="search" name="search" class="search-input"
                                    placeholder="Search by name or email..."
                                    value="<?php echo htmlspecialchars($search); ?>">
                                <i class="fas fa-search search-icon"></i>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="class">Filter by Class</label>
                            <select id="class" name="class" class="filter-select">
                                <option value="">All Classes</option>
                                <?php foreach ($classes as $class): ?>
                                    <option value="<?php echo $class['id']; ?>"
                                        <?php echo $class_filter == $class['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($class['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="subject">Filter by Subject</label>
                            <select id="subject" name="subject" class="filter-select">
                                <option value="">All Subjects</option>
                                <?php foreach ($subjects as $subject): ?>
                                    <option value="<?php echo $subject['id']; ?>"
                                        <?php echo $subject_filter == $subject['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($subject['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <button type="submit" class="filter-btn">
                            <i class="fas fa-filter"></i>
                            Apply Filters
                        </button>
                    </div>
                </form>
            </div>


            <?php if (empty($grouped_students)): ?>
                <div class="empty-state">
                    <i class="fas fa-user-graduate"></i>
                    <h3>No Students Found</h3>
                    <p>
                        <?php if (!empty($search) || !empty($class_filter) || !empty($subject_filter)): ?>
                            No students match your current search criteria. Try adjusting your filters.
                        <?php else: ?>
                            You don't have any students assigned to your classes yet.
                        <?php endif; ?>
                    </p>
                    <?php if (!empty($search) || !empty($class_filter) || !empty($subject_filter)): ?>
                        <a href="view_students.php" class="action-btn primary" style="margin-top: 1rem; display: inline-flex;">
                            <i class="fas fa-refresh"></i> Clear Filters
                        </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="students-grid">
                    <?php foreach ($grouped_students as $student): ?>
                        <div class="student-card">

                            <div class="student-header">
                                <div class="student-avatar">
                                    <?php echo strtoupper(substr($student['name'], 0, 1)); ?>
                                </div>
                                <div class="student-info">
                                    <h3><?php echo htmlspecialchars($student['name']); ?></h3>
                                    <p class="email"><?php echo htmlspecialchars($student['email']); ?></p>
                                    <p class="join-date">
                                        <i class="fas fa-calendar-alt"></i>
                                        Joined <?php echo date('M Y', strtotime($student['created_at'])); ?>
                                    </p>
                                </div>
                            </div>

                            <div class="performance-stats">
                                <div class="stat-item">
                                    <div class="stat-value">
                                        <?php echo number_format($student['quiz_stats']['avg_score'] ?? 0, 1); ?>%
                                    </div>
                                    <div class="stat-label">Quiz Average</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-value">
                                        <?php echo $student['quiz_stats']['completed_quizzes'] ?? 0; ?>/<?php echo $student['quiz_stats']['total_quizzes'] ?? 0; ?>
                                    </div>
                                    <div class="stat-label">Quizzes Done</div>
                                </div>
                            </div>

                            <div class="student-classes">
                                <h4><i class="fas fa-book"></i> Enrolled Classes</h4>
                                <div class="classes-list">
                                    <?php foreach ($student['classes'] as $class): ?>
                                        <div class="class-badge">
                                            <i class="fas fa-graduation-cap"></i>
                                            <span><?php echo htmlspecialchars($class['class_name']); ?></span>
                                            <small>(<?php echo htmlspecialchars($class['subject_name']); ?>)</small>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="student-actions">
                                <a href="student_profile.php?id=<?php echo $student['id']; ?>" class="action-btn primary">
                                    <i class="fas fa-user"></i> Profile
                                </a>
                                <a href="student_grades.php?id=<?php echo $student['id']; ?>" class="action-btn secondary">
                                    <i class="fas fa-chart-bar"></i> Grades
                                </a>
                                <a href="student_progress.php?id=<?php echo $student['id']; ?>" class="action-btn tertiary">
                                    <i class="fas fa-chart-line"></i> Progress
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('search');
            const classFilter = document.getElementById('class');
            const subjectFilter = document.getElementById('subject');

            [classFilter, subjectFilter].forEach(element => {
                element.addEventListener('change', function() {
                    this.form.submit();
                });
            });

            let searchTimeout;
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    this.form.submit();
                }, 500);
            });


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

            const actionBtns = document.querySelectorAll('.action-btn');
            actionBtns.forEach(btn => {
                btn.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-2px) scale(1.02)';
                });

                btn.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0) scale(1)';
                });
            });


            const statValues = document.querySelectorAll('.stat-value');
            statValues.forEach(stat => {
                const value = parseFloat(stat.textContent);
                if (stat.textContent.includes('%')) {
                    if (value >= 80) {
                        stat.style.color = '#16a34a';
                    } else if (value >= 60) {
                        stat.style.color = '#f59e0b';
                    } else {
                        stat.style.color = '#dc2626';
                    }
                }
            });


            function highlightSearchTerm() {
                const searchTerm = searchInput.value.toLowerCase();
                if (searchTerm.length > 0) {
                    const studentNames = document.querySelectorAll('.student-info h3');
                    const studentEmails = document.querySelectorAll('.student-info .email');

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
            }


            if (searchInput.value) {
                highlightSearchTerm();
            }
        });


        document.addEventListener('keydown', function(e) {

            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                document.getElementById('search').focus();
            }

            if (e.key === 'Escape') {
                const searchInput = document.getElementById('search');
                if (searchInput.value) {
                    searchInput.value = '';
                    searchInput.form.submit();
                }
            }
        });
    </script>
</body>

</html>