<?php
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/teacher.css">
</head>
<style>
    .students-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
        gap: 2rem;
        margin-top: 2rem;
    }

    .student-card {
        background: #ffffff;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        transition: 0.3s ease;
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }

    .student-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 6px 12px rgba(0, 0, 0, 0.12);
    }

    .student-header {
        display: flex;
        align-items: center;
        padding: 1.2rem;
        background-color: #f4f6f9;
        border-bottom: 1px solid #e5e7eb;
    }

    .student-avatar {
        width: 60px;
        height: 60px;
        background-color: #4f46e5;
        border-radius: 50%;
        color: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.8rem;
        margin-right: 1rem;
    }

    .student-info h3 {
        margin: 0 0 0.25rem;
        font-size: 1.2rem;
        color: #1f2937;
    }

    .student-info p {
        margin: 0;
        font-size: 0.9rem;
        color: #6b7280;
    }

    .student-classes {
        padding: 1rem 1.5rem;
        flex-grow: 1;
    }

    .student-classes h4 {
        margin-bottom: 0.6rem;
        font-size: 0.95rem;
        color: #374151;
        font-weight: 600;
    }

    .classes-list {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
    }

    .class-item {
        background-color: #eef2ff;
        padding: 0.4rem 0.7rem;
        border-radius: 20px;
        font-size: 0.85rem;
        color: #4338ca;
        font-weight: 500;
    }

    .student-actions {
        padding: 1rem 1.5rem;
        border-top: 1px solid #f1f5f9;
        display: flex;
        justify-content: flex-end;
        background-color: #f9fafb;
    }

    .student-actions .btn {
        background-color: #4f46e5;
        color: white;
        padding: 0.4rem 0.9rem;
        border-radius: 6px;
        font-size: 0.85rem;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        transition: background 0.2s;
    }

    .student-actions .btn:hover {
        background-color: #4338ca;
    }

    .students-summary {
        text-align: center;
        margin-top: 2rem;
        font-size: 0.95rem;
        color: #6b7280;
    }
</style>

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
                <div class="header-right">
                    <div class="user-info">
                        <i class="fas fa-chalkboard-teacher"></i>
                        <span><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                    </div>
                </div>
            </header>

            <div class="content-body">
                <?php if (empty($grouped_students)): ?>
                    <div class="empty-state" style="text-align:center; padding:2rem;">
                        <i class="fas fa-users" style="font-size: 3rem; color: #ccc;"></i>
                        <h3 style="margin-top: 1rem;">No Students Found</h3>
                        <p>You don’t have any students assigned to your classes yet.</p>
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
</body>

</html>