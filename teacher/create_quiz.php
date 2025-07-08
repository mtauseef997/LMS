<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'teacher') {
    header('Location: ../login.php');
    exit;
}

$teacher_id = $_SESSION['user_id'];


$subjects_query = "SELECT DISTINCT s.id, s.name
                  FROM subjects s
                  JOIN teacher_subject_class tsc ON s.id = tsc.subject_id
                  WHERE tsc.teacher_id = ?";
$subjects_stmt = $conn->prepare($subjects_query);
$subjects_stmt->bind_param("i", $teacher_id);
$subjects_stmt->execute();
$subjects = $subjects_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$classes_query = "SELECT DISTINCT c.id, c.name
                 FROM classes c
                 JOIN teacher_subject_class tsc ON c.id = tsc.class_id
                 WHERE tsc.teacher_id = ?";
$classes_stmt = $conn->prepare($classes_query);
$classes_stmt->bind_param("i", $teacher_id);
$classes_stmt->execute();
$classes = $classes_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $subject_id = intval($_POST['subject_id'] ?? 0);
    $class_id = intval($_POST['class_id'] ?? 0);
    $time_limit = intval($_POST['time_limit'] ?? 30);
    $total_marks = intval($_POST['total_marks'] ?? 0);

    if (empty($title) || $subject_id <= 0 || $class_id <= 0 || $total_marks <= 0) {
        $error = 'Please fill in all required fields.';
    } else {

        $verify_query = "SELECT id FROM teacher_subject_class WHERE teacher_id = ? AND subject_id = ? AND class_id = ?";
        $verify_stmt = $conn->prepare($verify_query);
        $verify_stmt->bind_param("iii", $teacher_id, $subject_id, $class_id);
        $verify_stmt->execute();

        if ($verify_stmt->get_result()->num_rows === 0) {
            $error = 'You are not assigned to this subject-class combination.';
        } else {
            try {

                $insert_query = "INSERT INTO quizzes (title, description, subject_id, class_id, teacher_id, time_limit, total_marks)
                               VALUES (?, ?, ?, ?, ?, ?, ?)";
                $insert_stmt = $conn->prepare($insert_query);
                $insert_stmt->bind_param("ssiiiiii", $title, $description, $subject_id, $class_id, $teacher_id, $time_limit, $total_marks);

                if ($insert_stmt->execute()) {
                    $quiz_id = $conn->insert_id;
                    $message = 'Quiz created successfully! You can now add questions.';
                    header("Location: quiz_questions.php?quiz_id=$quiz_id");
                    exit;
                } else {
                    $error = 'Failed to create quiz. Please try again.';
                }
            } catch (Exception $e) {

                try {
                    $insert_query = "INSERT INTO quizzes (title, subject_id, class_id, teacher_id, total_marks)
                                   VALUES (?, ?, ?, ?, ?)";
                    $insert_stmt = $conn->prepare($insert_query);
                    $insert_stmt->bind_param("siiii", $title, $subject_id, $class_id, $teacher_id, $total_marks);

                    if ($insert_stmt->execute()) {
                        $quiz_id = $conn->insert_id;
                        $message = 'Quiz created successfully! You can now add questions.';
                        header("Location: quiz_questions.php?quiz_id=$quiz_id");
                        exit;
                    } else {
                        $error = 'Failed to create quiz. Please try again.';
                    }
                } catch (Exception $e2) {
                    $error = 'Database error: ' . $e2->getMessage();
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Quiz - Teacher Panel</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/teacher.css">
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
                <a href="create_quiz.php" class="nav-item active">
                    <i class="fas fa-plus"></i>
                    <span>Create Quiz</span>
                </a>
                <a href="view_students.php" class="nav-item">
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
                    <h1>Create New Quiz</h1>
                    <p>Create a quiz for your students</p>
                </div>
            </header>

            <div class="content-body">
                <?php if ($message): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <div class="form-container">
                    <form method="POST" class="quiz-form">
                        <div class="form-group">
                            <label for="title">Quiz Title *</label>
                            <input type="text" id="title" name="title" required
                                value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>"
                                placeholder="Enter quiz title">
                        </div>

                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" rows="3"
                                placeholder="Enter quiz description (optional)"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="subject_id">Subject *</label>
                                <select id="subject_id" name="subject_id" required>
                                    <option value="">Select Subject</option>
                                    <?php foreach ($subjects as $subject): ?>
                                        <option value="<?php echo $subject['id']; ?>"
                                            <?php echo (($_POST['subject_id'] ?? '') == $subject['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($subject['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="class_id">Class *</label>
                                <select id="class_id" name="class_id" required>
                                    <option value="">Select Class</option>
                                    <?php foreach ($classes as $class): ?>
                                        <option value="<?php echo $class['id']; ?>"
                                            <?php echo (($_POST['class_id'] ?? '') == $class['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($class['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="time_limit">Time Limit (minutes)</label>
                                <input type="number" id="time_limit" name="time_limit" min="1" max="300"
                                    value="<?php echo $_POST['time_limit'] ?? '30'; ?>"
                                    placeholder="30">
                            </div>

                            <div class="form-group">
                                <label for="total_marks">Total Marks *</label>
                                <input type="number" id="total_marks" name="total_marks" min="1" required
                                    value="<?php echo $_POST['total_marks'] ?? ''; ?>"
                                    placeholder="Enter total marks">
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Create Quiz
                            </button>
                            <a href="manage_quiz.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</body>

</html>