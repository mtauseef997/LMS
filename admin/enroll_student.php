<?php
session_start();
require_once '../config/db.php';


if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$students = [];
$result = $conn->query("SELECT id, name FROM users WHERE role = 'student'");
while ($row = $result->fetch_assoc()) {
    $students[] = $row;
}

$classes = [];
$result = $conn->query("SELECT id, name FROM classes");
while ($row = $result->fetch_assoc()) {
    $classes[] = $row;
}
$success = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = intval($_POST['student_id'] ?? 0);
    $class_id = intval($_POST['class_id'] ?? 0);

    if ($student_id <= 0 || $class_id <= 0) {
        $error = 'Please select both student and class.';
    } else {
        $stmt = $conn->prepare("SELECT id FROM student_class WHERE student_id = ? AND class_id = ?");
        $stmt->bind_param("ii", $student_id, $class_id);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = 'Student is already enrolled in this class.';
        } else {

            $stmt = $conn->prepare("INSERT INTO student_class (student_id, class_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $student_id, $class_id);
            if ($stmt->execute()) {
                $success = 'Student enrolled successfully.';
            } else {
                $error = 'Failed to enroll student. Please try again.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Enroll Student - EduLearn LMS</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>

<body>
    <div class="dashboard-container">

        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>EduLearn</h2>
                <p>Admin Panel</p>
            </div>
            <nav class="sidebar-nav">
                <a href="dashboard.php">Dashboard</a>
                <a href="manage_user.php">Manage Users</a>
                <a href="manage_class.php">Manage Classes</a>
                <a href="manage_subject.php">Manage Subjects</a>
                <a href="assign_teacher.php">Assign Teachers</a>
                <a href="enroll_student.php" class="active">Enroll Students</a>
            </nav>
            <div class="sidebar-footer">
                <a href="../logout.php">Logout</a>
            </div>
        </aside>


        <main class="main-content">
            <header class="content-header">
                <h1>Enroll Student</h1>
                <p>Assign students to classes</p>
            </header>

            <div class="content-card">
                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>

                <form method="POST" class="form">
                    <div class="form-group">
                        <label for="student_id">Select Student</label>
                        <select name="student_id" required>
                            <option value="">-- Select --</option>
                            <?php foreach ($students as $student): ?>
                                <option value="<?php echo $student['id']; ?>">
                                    <?php echo htmlspecialchars($student['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="class_id">Select Class</label>
                        <select name="class_id" required>
                            <option value="">-- Select --</option>
                            <?php foreach ($classes as $class): ?>
                                <option value="<?php echo $class['id']; ?>">
                                    <?php echo htmlspecialchars($class['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary">Enroll Student</button>
                </form>
            </div>
        </main>
    </div>
</body>

</html>