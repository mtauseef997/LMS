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
                <a href="quizzes.php" class="nav-item">
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
                <a href="students.php" class="nav-item active">
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
                    <div class="grid grid-3">
                        <?php foreach ($grouped_students as $student): ?>
                            <div class="card">
                                <div class="card-header">
                                    <div class="student-header">
                                        <div class="student-avatar">
                                            <i class="fas fa-user"></i>
                                        </div>
                                        <div class="student-info">
                                            <h3><?php echo htmlspecialchars($student['name']); ?></h3>
                                            <p class="email"><?php echo htmlspecialchars($student['email']); ?></p>
                                        </div>
                                    </div>
                                </div>

                                <div class="card-body">
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

                                <div class="card-footer">
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
        .student-header {
            display: flex;
            align-items: center;
            gap: 1rem;
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
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .student-info h3 {
            margin: 0 0 0.25rem 0;
            color: #1f2937;
            font-size: 1.125rem;
            font-weight: 600;
        }

        .email {
            margin: 0;
            color: #6b7280;
            font-size: 0.875rem;
        }

        .card-body h4 {
            margin: 0 0 1rem 0;
            color: #374151;
            font-size: 0.875rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .classes-list {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .class-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem;
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
        }

        .class-item:hover {
            background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
            transform: translateX(5px);
        }

        .class-name {
            font-weight: 600;
            color: #374151;
        }

        .subject-name {
            color: #667eea;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .students-summary {
            margin-top: 2rem;
            padding: 1.5rem;
            background: white;
            border-radius: 16px;
            text-align: center;
            color: #6b7280;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        .students-summary strong {
            color: #667eea;
            font-weight: 700;
        }
    </style>
</body>

</html>