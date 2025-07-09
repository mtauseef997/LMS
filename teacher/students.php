<?php
copy(__FILE__, dirname(__FILE__) . '/students.php');

session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'teacher') {
    header('Location: ../login.php');
    exit;
}

$teacher_id = $_SESSION['user_id'];


$students_query = "SELECT DISTINCT u.id, u.name, u.email, c.name as class_name, s.name as subject_name
                  FROM users u
                  JOIN student_class sc ON u.id = sc.student_id
                  JOIN classes c ON sc.class_id = c.id
                  JOIN teacher_subject_class tsc ON c.id = tsc.class_id
                  JOIN subjects s ON tsc.subject_id = s.id
                  WHERE tsc.teacher_id = ? AND u.role = 'student'
                  ORDER BY u.name, c.name";
$students_stmt = $conn->prepare($students_query);
$students_stmt->bind_param("i", $teacher_id);
$students_stmt->execute();
$students = $students_stmt->get_result()->fetch_all(MYSQLI_ASSOC);


$grouped_students = [];
foreach ($students as $student) {
    $student_id = $student['id'];
    if (!isset($grouped_students[$student_id])) {
        $grouped_students[$student_id] = [
            'id' => $student['id'],
            'name' => $student['name'],
            'email' => $student['email'],
            'classes' => []
        ];
    }
    $grouped_students[$student_id]['classes'][] = [
        'class_name' => $student['class_name'],
        'subject_name' => $student['subject_name']
    ];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Students - Teacher Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
            <header class="content-header">
                <div class="header-left">
                    <h1>My Students</h1>
                    <p>Students enrolled in your classes</p>
                </div>
            </header>

            <div class="content-body">
                <?php if (empty($grouped_students)): ?>
                    <div class="empty-state">
                        <i class="fas fa-users"></i>
                        <h3>No Students Found</h3>
                        <p>You don't have any students assigned to your classes yet.</p>
                    </div>
                <?php else: ?>
                    <div class="students-grid">
                        <?php foreach ($grouped_students as $student): ?>
                            <div class="student-card">
                                <div class="student-header">
                                    <div class="student-avatar">
                                        <i class="fas fa-user"></i>
                                    </div>
                                    <div class="student-info">
                                        <h3><?php echo htmlspecialchars($student['name']); ?></h3>
                                        <p class="email"><?php echo htmlspecialchars($student['email']); ?></p>
                                    </div>
                                </div>

                                <div class="student-classes">
                                    <h4>Enrolled Classes:</h4>
                                    <div class="classes-list">
                                        <?php foreach ($student['classes'] as $class): ?>
                                            <div class="class-item">
                                                <span
                                                    class="class-name"><?php echo htmlspecialchars($class['class_name']); ?></span>
                                                <span
                                                    class="subject-name"><?php echo htmlspecialchars($class['subject_name']); ?></span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <div class="student-actions">
                                    <a href="quiz_results.php?student_id=<?php echo $student['id']; ?>"
                                        class="btn btn-sm btn-primary">
                                        <i class="fas fa-chart-bar"></i> View Grades
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="students-summary">
                        <p><strong>Total Students:</strong> <?php echo count($grouped_students); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <style>
        .students-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-top: 1rem;
        }

        .student-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .student-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        .student-header {
            padding: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            border-bottom: 1px solid #e5e7eb;
        }

        .student-avatar {
            width: 50px;
            height: 50px;
            background: #f3f4f6;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6b7280;
            font-size: 1.5rem;
        }

        .student-info h3 {
            margin: 0 0 0.25rem 0;
            color: #1f2937;
            font-size: 1.125rem;
        }

        .email {
            margin: 0;
            color: #6b7280;
            font-size: 0.875rem;
        }

        .student-classes {
            padding: 1rem 1.5rem;
        }

        .student-classes h4 {
            margin: 0 0 0.75rem 0;
            color: #374151;
            font-size: 0.875rem;
            font-weight: 600;
        }

        .classes-list {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .class-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem;
            background: #f9fafb;
            border-radius: 4px;
        }

        .class-name {
            font-weight: 500;
            color: #374151;
        }

        .subject-name {
            color: #6b7280;
            font-size: 0.875rem;
        }

        .student-actions {
            padding: 1rem 1.5rem;
            background: #f9fafb;
            display: flex;
            justify-content: flex-end;
        }

        .students-summary {
            margin-top: 2rem;
            padding: 1rem;
            background: #f9fafb;
            border-radius: 8px;
            text-align: center;
            color: #6b7280;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #6b7280;
        }

        .empty-state i {
            font-size: 4rem;
            color: #d1d5db;
            margin-bottom: 1rem;
        }

        .empty-state h3 {
            color: #374151;
            margin-bottom: 0.5rem;
        }
    </style>
</body>

</html>