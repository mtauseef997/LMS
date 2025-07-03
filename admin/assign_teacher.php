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
        case 'assign':
            $teacher_id = intval($_POST['teacher_id'] ?? 0);
            $subject_id = intval($_POST['subject_id'] ?? 0);
            $class_id = intval($_POST['class_id'] ?? 0);

            if ($teacher_id <= 0 || $subject_id <= 0 || $class_id <= 0) {
                echo json_encode(['success' => false, 'message' => 'All fields are required']);
                exit;
            }

            $query = "SELECT id FROM teacher_subject_class WHERE teacher_id = ? AND subject_id = ? AND class_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("iii", $teacher_id, $subject_id, $class_id);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                echo json_encode(['success' => false, 'message' => 'This teacher is already assigned to this subject and class']);
                exit;
            }

            $query = "INSERT INTO teacher_subject_class (teacher_id, subject_id, class_id) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("iii", $teacher_id, $subject_id, $class_id);

            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Teacher assigned successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to assign teacher']);
            }
            exit;

        case 'unassign':
            $id = intval($_POST['id'] ?? 0);

            if ($id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid assignment ID']);
                exit;
            }

            $query = "DELETE FROM teacher_subject_class WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $id);

            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Teacher unassigned successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to unassign teacher']);
            }
            exit;
    }
}

$teachers_query = "SELECT id, name, email FROM users WHERE role = 'teacher' ORDER BY name";
$teachers_result = $conn->query($teachers_query);
$teachers = $teachers_result->fetch_all(MYSQLI_ASSOC);

$subjects_query = "SELECT id, name FROM subjects ORDER BY name";
$subjects_result = $conn->query($subjects_query);
$subjects = $subjects_result->fetch_all(MYSQLI_ASSOC);

$classes_query = "SELECT id, name FROM classes ORDER BY name";
$classes_result = $conn->query($classes_query);
$classes = $classes_result->fetch_all(MYSQLI_ASSOC);
$teacher_filter = $_GET['teacher'] ?? '';
$subject_filter = $_GET['subject'] ?? '';
$class_filter = $_GET['class'] ?? '';

$query = "SELECT tsc.id, 
          u.name as teacher_name, u.email as teacher_email,
          s.name as subject_name,
          c.name as class_name,
          tsc.created_at
          FROM teacher_subject_class tsc
          JOIN users u ON tsc.teacher_id = u.id
          JOIN subjects s ON tsc.subject_id = s.id
          JOIN classes c ON tsc.class_id = c.id
          WHERE 1=1";

$params = [];
$types = "";

if (!empty($teacher_filter)) {
    $query .= " AND tsc.teacher_id = ?";
    $params[] = $teacher_filter;
    $types .= "i";
}

if (!empty($subject_filter)) {
    $query .= " AND tsc.subject_id = ?";
    $params[] = $subject_filter;
    $types .= "i";
}

if (!empty($class_filter)) {
    $query .= " AND tsc.class_id = ?";
    $params[] = $class_filter;
    $types .= "i";
}

$query .= " ORDER BY u.name, s.name, c.name";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$assignments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Assignment - EduLearn LMS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
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
                <a href="assign_teacher.php" class="nav-item active">
                    <i class="fas fa-chalkboard-teacher"></i>
                    <span>Assign Teachers</span>
                </a>
                <a href="enroll_student.php" class="nav-item">
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


        <main class="main-content">

            <header class="content-header">
                <div class="header-left">
                    <h1>Teacher Assignment</h1>
                    <p>Assign teachers to subjects and classes</p>
                </div>
                <div class="header-right">
                    <button class="btn btn-primary" onclick="openAssignModal()">
                        <i class="fas fa-plus"></i> Assign Teacher
                    </button>
                </div>
            </header>

            <div class="filters-section" style="margin-bottom: 2rem;">
                <form method="GET" class="filters-form"
                    style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
                    <div class="filter-group">
                        <select name="teacher" style="padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
                            <option value="">All Teachers</option>
                            <?php foreach ($teachers as $teacher): ?>
                            <option value="<?php echo $teacher['id']; ?>"
                                <?php echo $teacher_filter == $teacher['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($teacher['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <select name="subject" style="padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
                            <option value="">All Subjects</option>
                            <?php foreach ($subjects as $subject): ?>
                            <option value="<?php echo $subject['id']; ?>"
                                <?php echo $subject_filter == $subject['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($subject['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <select name="class" style="padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
                            <option value="">All Classes</option>
                            <?php foreach ($classes as $class): ?>
                            <option value="<?php echo $class['id']; ?>"
                                <?php echo $class_filter == $class['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($class['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-secondary">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                    <a href="assign_teacher.php" class="btn btn-outline">
                        <i class="fas fa-times"></i> Clear
                    </a>
                </form>
            </div>

            <div class="content-card">
                <div class="card-header">
                    <h3>Teacher Assignments (<?php echo count($assignments); ?>)</h3>
                </div>
                <div class="card-content">
                    <?php if (empty($assignments)): ?>
                    <p style="text-align: center; color: #666; padding: 2rem;">
                        No teacher assignments found matching your criteria.
                    </p>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Teacher</th>
                                    <th>Email</th>
                                    <th>Subject</th>
                                    <th>Class</th>
                                    <th>Assigned Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($assignments as $assignment): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($assignment['teacher_name']); ?></td>
                                    <td><?php echo htmlspecialchars($assignment['teacher_email']); ?></td>
                                    <td><?php echo htmlspecialchars($assignment['subject_name']); ?></td>
                                    <td><?php echo htmlspecialchars($assignment['class_name']); ?></td>
                                    <td><?php echo date('M j, Y', strtotime($assignment['created_at'])); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-icon btn-delete"
                                                onclick="unassignTeacher(<?php echo $assignment['id']; ?>)"
                                                title="Unassign">
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

    <div id="assignModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Assign Teacher</h3>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <form id="assignForm">
                <input type="hidden" name="action" value="assign">

                <div class="form-group">
                    <label for="teacherSelect">Teacher *</label>
                    <select id="teacherSelect" name="teacher_id" required>
                        <option value="">Select Teacher</option>
                        <?php foreach ($teachers as $teacher): ?>
                        <option value="<?php echo $teacher['id']; ?>">
                            <?php echo htmlspecialchars($teacher['name']); ?>
                            (<?php echo htmlspecialchars($teacher['email']); ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="subjectSelect">Subject *</label>
                    <select id="subjectSelect" name="subject_id" required>
                        <option value="">Select Subject</option>
                        <?php foreach ($subjects as $subject): ?>
                        <option value="<?php echo $subject['id']; ?>">
                            <?php echo htmlspecialchars($subject['name']); ?>
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
                    <button type="submit" class="btn btn-primary" id="submitBtn">Assign Teacher</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function openAssignModal() {
        document.getElementById('assignForm').reset();
        document.getElementById('assignModal').style.display = 'block';
    }

    function unassignTeacher(assignmentId) {
        if (confirm('Are you sure you want to unassign this teacher? This action cannot be undone.')) {
            fetch('assign_teacher.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: 'action=unassign&id=' + assignmentId
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
                    alert('An error occurred while unassigning the teacher');
                });
        }
    }

    function closeModal() {
        document.getElementById('assignModal').style.display = 'none';
    }

    document.getElementById('assignForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        const submitBtn = document.getElementById('submitBtn');
        const originalText = submitBtn.textContent;

        submitBtn.textContent = 'Processing...';
        submitBtn.disabled = true;

        fetch('assign_teacher.php', {
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

    window.onclick = function(event) {
        const modal = document.getElementById('assignModal');
        if (event.target === modal) {
            closeModal();
        }
    }
    </script>
</body>

</html>