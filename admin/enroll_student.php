<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    header('Content-Type: application/json');

    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'enroll':
            $student_id = intval($_POST['student_id'] ?? 0);
            $class_id = intval($_POST['class_id'] ?? 0);

            if ($student_id <= 0 || $class_id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Student and class are required']);
                exit;
            }

            // Check if enrollment already exists
            $query = "SELECT id FROM student_class WHERE student_id = ? AND class_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ii", $student_id, $class_id);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                echo json_encode(['success' => false, 'message' => 'Student is already enrolled in this class']);
                exit;
            }

            // Create enrollment
            $query = "INSERT INTO student_class (student_id, class_id) VALUES (?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ii", $student_id, $class_id);

            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Student enrolled successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to enroll student']);
            }
            exit;

        case 'unenroll':
            $id = intval($_POST['id'] ?? 0);

            if ($id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid enrollment ID']);
                exit;
            }

            // Delete enrollment
            $query = "DELETE FROM student_class WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $id);

            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Student unenrolled successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to unenroll student']);
            }
            exit;
    }
}

// Get all students
$students_query = "SELECT id, name, email FROM users WHERE role = 'student' ORDER BY name";
$students_result = $conn->query($students_query);
$students = $students_result->fetch_all(MYSQLI_ASSOC);

// Get all classes
$classes_query = "SELECT id, name FROM classes ORDER BY name";
$classes_result = $conn->query($classes_query);
$classes = $classes_result->fetch_all(MYSQLI_ASSOC);

// Get all enrollments with filters
$student_filter = $_GET['student'] ?? '';
$class_filter = $_GET['class'] ?? '';

$query = "SELECT sc.id, 
          u.name as student_name, u.email as student_email,
          c.name as class_name,
          sc.enrolled_at
          FROM student_class sc
          JOIN users u ON sc.student_id = u.id
          JOIN classes c ON sc.class_id = c.id
          WHERE 1=1";

$params = [];
$types = "";

if (!empty($student_filter)) {
    $query .= " AND sc.student_id = ?";
    $params[] = $student_filter;
    $types .= "i";
}

if (!empty($class_filter)) {
    $query .= " AND sc.class_id = ?";
    $params[] = $class_filter;
    $types .= "i";
}

$query .= " ORDER BY u.name, c.name";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$enrollments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Enrollment - EduLearn LMS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>

<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
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
                <a href="enroll_student.php" class="nav-item active">
                    <i class="fas fa-user-graduate"></i>
                    <span>Enroll Students</span>
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
            <!-- Header -->
            <header class="content-header">
                <div class="header-left">
                    <h1>Student Enrollment</h1>
                    <p>Enroll students in classes</p>
                </div>
                <div class="header-right">
                    <button class="btn btn-primary" onclick="openEnrollModal()">
                        <i class="fas fa-plus"></i> Enroll Student
                    </button>
                </div>
            </header>

            <!-- Filters -->
            <div class="filters-section" style="margin-bottom: 2rem;">
                <form method="GET" class="filters-form" style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
                    <div class="filter-group">
                        <select name="student" style="padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
                            <option value="">All Students</option>
                            <?php foreach ($students as $student): ?>
                                <option value="<?php echo $student['id']; ?>" <?php echo $student_filter == $student['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($student['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <select name="class" style="padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
                            <option value="">All Classes</option>
                            <?php foreach ($classes as $class): ?>
                                <option value="<?php echo $class['id']; ?>" <?php echo $class_filter == $class['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($class['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-secondary">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                    <a href="enroll_student.php" class="btn btn-outline">
                        <i class="fas fa-times"></i> Clear
                    </a>
                </form>
            </div>

            <!-- Enrollments Table -->
            <div class="content-card">
                <div class="card-header">
                    <h3>Student Enrollments (<?php echo count($enrollments); ?>)</h3>
                </div>
                <div class="card-content">
                    <?php if (empty($enrollments)): ?>
                        <p style="text-align: center; color: #666; padding: 2rem;">
                            No student enrollments found matching your criteria.
                        </p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Email</th>
                                        <th>Class</th>
                                        <th>Enrolled Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($enrollments as $enrollment): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($enrollment['student_name']); ?></td>
                                            <td><?php echo htmlspecialchars($enrollment['student_email']); ?></td>
                                            <td><?php echo htmlspecialchars($enrollment['class_name']); ?></td>
                                            <td><?php echo date('M j, Y', strtotime($enrollment['enrolled_at'])); ?></td>
                                            <td>
                                                <div class="action-buttons">
                                                    <button class="btn-icon btn-delete" onclick="unenrollStudent(<?php echo $enrollment['id']; ?>)" title="Unenroll">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <!-- Enroll Student Modal -->
    <div id="enrollModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Enroll Student</h3>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <form id="enrollForm">
                <input type="hidden" name="action" value="enroll">

                <div class="form-group">
                    <label for="studentSelect">Student *</label>
                    <select id="studentSelect" name="student_id" required>
                        <option value="">Select Student</option>
                        <?php foreach ($students as $student): ?>
                            <option value="<?php echo $student['id']; ?>">
                                <?php echo htmlspecialchars($student['name']); ?> (<?php echo htmlspecialchars($student['email']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="classSelect">Class *</label>
                    <select id="classSelect" name="class_id" required>
                        <option value="">Select Class</option>
                        <?php foreach ($classes as $class): ?>
                            <option value="<?php echo $class['id']; ?>">
                                <?php echo htmlspecialchars($class['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="submitBtn">Enroll Student</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Modal functions
        function openEnrollModal() {
            document.getElementById('enrollForm').reset();
            document.getElementById('enrollModal').style.display = 'block';
        }

        function unenrollStudent(enrollmentId) {
            if (confirm('Are you sure you want to unenroll this student? This action cannot be undone.')) {
                fetch('enroll_student.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: 'action=unenroll&id=' + enrollmentId
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert(data.message);
                            location.reload();
                        } else {
                            alert('Error: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while unenrolling the student');
                    });
            }
        }

        function closeModal() {
            document.getElementById('enrollModal').style.display = 'none';
        }

        // Form submission
        document.getElementById('enrollForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const submitBtn = document.getElementById('submitBtn');
            const originalText = submitBtn.textContent;

            submitBtn.textContent = 'Processing...';
            submitBtn.disabled = true;

            fetch('enroll_student.php', {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        closeModal();
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while processing the request');
                })
                .finally(() => {
                    submitBtn.textContent = originalText;
                    submitBtn.disabled = false;
                });
        });

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('enrollModal');
            if (event.target === modal) {
                closeModal();
            }
        }
    </script>
</body>

</html>