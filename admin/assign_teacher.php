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
          tsc.id as assignment_id
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
    <link rel="stylesheet" href="../assets/css/teacher.css">
    <link rel="stylesheet" href="../assets/css/responsive-modal.css">
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
                    <h1><i class="fas fa-chalkboard-teacher"></i> Teacher Assignment</h1>
                    <p>Assign teachers to subjects and classes efficiently</p>
                </div>
                <div class="header-right">
                    <button class="btn btn-primary" onclick="openAssignModal()">
                        <i class="fas fa-user-plus"></i> Assign Teacher
                    </button>
                    <button class="btn btn-secondary" onclick="exportAssignments()">
                        <i class="fas fa-download"></i> Export Data
                    </button>
                    <button class="btn btn-info" onclick="showBulkAssignModal()">
                        <i class="fas fa-users"></i> Bulk Assign
                    </button>
                </div>
            </header>

            <div class="content-body">

                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                            <i class="fas fa-chalkboard-teacher"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo count($assignments); ?></h3>
                            <p>Total Assignments</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo count($teachers); ?></h3>
                            <p>Available Teachers</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                            <i class="fas fa-book"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo count($subjects); ?></h3>
                            <p>Subjects</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                            <i class="fas fa-school"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo count($classes); ?></h3>
                            <p>Classes</p>
                        </div>
                    </div>
                </div>


                <div class="content-card">
                    <div class="card-header">
                        <h3><i class="fas fa-filter"></i> Filter Assignments</h3>
                        <p>Filter teacher assignments by teacher, subject, or class</p>
                    </div>
                    <div class="card-body">
                        <form method="GET" class="enhanced-filters-form">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="teacher_filter">
                                        <i class="fas fa-user"></i> Teacher
                                    </label>
                                    <div class="select-wrapper">
                                        <select name="teacher" id="teacher_filter">
                                            <option value="">All Teachers</option>
                                            <?php foreach ($teachers as $teacher): ?>
                                            <option value="<?php echo $teacher['id']; ?>"
                                                <?php echo $teacher_filter == $teacher['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($teacher['name']); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <i class="fas fa-chevron-down select-arrow"></i>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="subject_filter">
                                        <i class="fas fa-book"></i> Subject
                                    </label>
                                    <div class="select-wrapper">
                                        <select name="subject" id="subject_filter">
                                            <option value="">All Subjects</option>
                                            <?php foreach ($subjects as $subject): ?>
                                            <option value="<?php echo $subject['id']; ?>"
                                                <?php echo $subject_filter == $subject['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($subject['name']); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <i class="fas fa-chevron-down select-arrow"></i>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="class_filter">
                                        <i class="fas fa-school"></i> Class
                                    </label>
                                    <div class="select-wrapper">
                                        <select name="class" id="class_filter">
                                            <option value="">All Classes</option>
                                            <?php foreach ($classes as $class): ?>
                                            <option value="<?php echo $class['id']; ?>"
                                                <?php echo $class_filter == $class['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($class['name']); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <i class="fas fa-chevron-down select-arrow"></i>
                                    </div>
                                </div>
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-filter"></i> Apply Filters
                                </button>
                                <a href="assign_teacher.php" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Clear Filters
                                </a>
                                <button type="button" onclick="toggleAdvancedFilters()" class="btn btn-info">
                                    <i class="fas fa-cog"></i> Advanced
                                </button>
                            </div>
                        </form>
                    </div>
                </div>


                <div class="content-card">
                    <div class="card-header">
                        <h3><i class="fas fa-list"></i> Teacher Assignments</h3>
                        <p>Manage teacher assignments to subjects and classes (<?php echo count($assignments); ?> total)
                        </p>
                        <div class="header-actions">
                            <div class="search-box">
                                <i class="fas fa-search"></i>
                                <input type="text" id="searchAssignments" placeholder="Search assignments..."
                                    onkeyup="filterAssignments()">
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (empty($assignments)): ?>
                        <div class="empty-state">
                            <i class="fas fa-chalkboard-teacher"></i>
                            <h3>No Assignments Found</h3>
                            <p>No teacher assignments found matching your criteria. Create your first assignment to get
                                started.</p>
                            <button onclick="openAssignModal()" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Create First Assignment
                            </button>
                        </div>
                        <?php else: ?>
                        <div class="table-container">
                            <table class="data-table" id="assignmentsTable">
                                <thead>
                                    <tr>
                                        <th><i class="fas fa-user"></i> Teacher</th>
                                        <th><i class="fas fa-envelope"></i> Email</th>
                                        <th><i class="fas fa-book"></i> Subject</th>
                                        <th><i class="fas fa-school"></i> Class</th>
                                        <th><i class="fas fa-id-badge"></i> ID</th>
                                        <th><i class="fas fa-cogs"></i> Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($assignments as $assignment): ?>
                                    <tr class="assignment-row">
                                        <td>
                                            <div class="teacher-info">
                                                <div class="teacher-avatar">
                                                    <i class="fas fa-user"></i>
                                                </div>
                                                <span
                                                    class="teacher-name"><?php echo htmlspecialchars($assignment['teacher_name']); ?></span>
                                            </div>
                                        </td>
                                        <td class="teacher-email">
                                            <?php echo htmlspecialchars($assignment['teacher_email']); ?></td>
                                        <td>
                                            <span
                                                class="subject-badge"><?php echo htmlspecialchars($assignment['subject_name']); ?></span>
                                        </td>
                                        <td>
                                            <span
                                                class="class-badge"><?php echo htmlspecialchars($assignment['class_name']); ?></span>
                                        </td>
                                        <td>
                                            <span
                                                class="assignment-id">#<?php echo $assignment['assignment_id']; ?></span>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <button type="button"
                                                    onclick="viewAssignmentDetails(<?php echo $assignment['assignment_id']; ?>)"
                                                    class="btn btn-sm btn-info" title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button type="button"
                                                    onclick="unassignTeacher(<?php echo $assignment['id']; ?>)"
                                                    class="btn btn-sm btn-danger" title="Unassign Teacher">
                                                    <i class="fas fa-trash"></i>
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
            </div>
        </main>
    </div>


    <div id="assignModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-user-plus"></i> Assign Teacher</h3>
                <p>Create a new teacher assignment to subject and class</p>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="assignForm" class="enhanced-form">
                    <input type="hidden" name="action" value="assign">

                    <div class="form-row">
                        <div class="form-group">
                            <label for="teacherSelect">
                                <i class="fas fa-user"></i> Select Teacher *
                            </label>
                            <div class="select-wrapper">
                                <select id="teacherSelect" name="teacher_id" required>
                                    <option value="">-- Choose a teacher --</option>
                                    <?php foreach ($teachers as $teacher): ?>
                                    <option value="<?php echo $teacher['id']; ?>">
                                        <?php echo htmlspecialchars($teacher['name']); ?>
                                        (<?php echo htmlspecialchars($teacher['email']); ?>)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <i class="fas fa-chevron-down select-arrow"></i>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="subjectSelect">
                                <i class="fas fa-book"></i> Select Subject *
                            </label>
                            <div class="select-wrapper">
                                <select id="subjectSelect" name="subject_id" required>
                                    <option value="">-- Choose a subject --</option>
                                    <?php foreach ($subjects as $subject): ?>
                                    <option value="<?php echo $subject['id']; ?>">
                                        <?php echo htmlspecialchars($subject['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <i class="fas fa-chevron-down select-arrow"></i>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="classSelect">
                                <i class="fas fa-school"></i> Select Class *
                            </label>
                            <div class="select-wrapper">
                                <select id="classSelect" name="class_id" required>
                                    <option value="">-- Choose a class --</option>
                                    <?php foreach ($classes as $class): ?>
                                    <option value="<?php echo $class['id']; ?>">
                                        <?php echo htmlspecialchars($class['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <i class="fas fa-chevron-down select-arrow"></i>
                            </div>
                        </div>
                    </div>

                    <div class="assignment-preview" id="assignmentPreview" style="display: none;">
                        <h4><i class="fas fa-eye"></i> Assignment Preview</h4>
                        <div class="preview-content">
                            <div class="preview-item">
                                <strong>Teacher:</strong> <span id="previewTeacher">-</span>
                            </div>
                            <div class="preview-item">
                                <strong>Subject:</strong> <span id="previewSubject">-</span>
                            </div>
                            <div class="preview-item">
                                <strong>Class:</strong> <span id="previewClass">-</span>
                            </div>
                        </div>
                    </div>

                    <div class="modal-actions">
                        <button type="button" onclick="closeModal()" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                        <button type="submit" id="submitBtn" class="btn btn-primary">
                            <i class="fas fa-user-plus"></i> Assign Teacher
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <style>
    .select-wrapper {
        position: relative;
        display: inline-block;
        width: 100%;
    }

    .select-wrapper select {
        appearance: none;
        -webkit-appearance: none;
        -moz-appearance: none;
        padding-right: 2.5rem;
        background: white;
        cursor: pointer;
    }

    .select-arrow {
        position: absolute;
        right: 1rem;
        top: 50%;
        transform: translateY(-50%);
        color: #6b7280;
        pointer-events: none;
        font-size: 0.875rem;
    }

    .teacher-info {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .teacher-avatar {
        width: 40px;
        height: 40px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1rem;
    }

    .teacher-name {
        font-weight: 600;
        color: #374151;
    }

    /* Enhanced Badges */
    .subject-badge,
    .class-badge {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-size: 0.875rem;
        font-weight: 600;
        display: inline-block;
    }

    .class-badge {
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    }

    .assignment-id {
        font-family: 'Courier New', monospace;
        background: #f3f4f6;
        padding: 0.25rem 0.5rem;
        border-radius: 4px;
        font-weight: 600;
        color: #374151;
    }


    .action-buttons {
        display: flex;
        gap: 0.5rem;
    }

    .assignment-row:hover {
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    }

    .table-container {
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    }

    .header-actions {
        display: flex;
        gap: 1rem;
        align-items: center;
        margin-top: 1rem;
    }

    .search-box {
        position: relative;
        flex: 1;
        max-width: 300px;
    }

    .search-box i {
        position: absolute;
        left: 1rem;
        top: 50%;
        transform: translateY(-50%);
        color: #6b7280;
    }

    .search-box input {
        padding-left: 2.5rem;
        border: 2px solid #e5e7eb;
        border-radius: 8px;
        width: 100%;
        padding-top: 0.75rem;
        padding-bottom: 0.75rem;
    }

    .search-box input:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    .enhanced-form {
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        padding: 2rem;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
    }

    .enhanced-filters-form {
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        padding: 1.5rem;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
    }

    .form-group label {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 0.75rem;
        font-weight: 600;
        color: #374151;
    }

    .form-group label i {
        color: #667eea;
    }


    .assignment-preview {
        background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%);
        border: 1px solid #a5b4fc;
        border-radius: 8px;
        padding: 1rem;
        margin-top: 1rem;
    }

    .assignment-preview h4 {
        margin: 0 0 1rem 0;
        color: #3730a3;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .preview-content {
        display: grid;
        gap: 0.5rem;
    }

    .preview-item {
        color: #374151;
    }

    .preview-item strong {
        color: #1f2937;
    }

    .card-header p {
        margin: 0.5rem 0 0 0;
        color: #6b7280;
        font-size: 0.875rem;
    }

    @media (max-width: 768px) {
        .header-actions {
            flex-direction: column;
            align-items: stretch;
        }

        .search-box {
            max-width: none;
        }

        .action-buttons {
            flex-direction: column;
        }

        .teacher-info {
            flex-direction: column;
            align-items: flex-start;
            gap: 0.5rem;
        }
    }
    </style>

    <script src="../assets/js/responsive-modal.js"></script>
    <script>
    let assignModal;

    document.addEventListener('DOMContentLoaded', function() {
        assignModal = new ResponsiveModal('assignModal');


        document.getElementById('teacherSelect').addEventListener('change', updateAssignmentPreview);
        document.getElementById('subjectSelect').addEventListener('change', updateAssignmentPreview);
        document.getElementById('classSelect').addEventListener('change', updateAssignmentPreview);
    });


    function openAssignModal() {
        const form = document.getElementById('assignForm');
        if (form) form.reset();

        const preview = document.getElementById('assignmentPreview');
        if (preview) preview.style.display = 'none';

        if (assignModal) {
            assignModal.open();
        }
    }

    function closeModal() {
        if (assignModal) {
            assignModal.close();
        }
    }

    function unassignTeacher(assignmentId) {
        if (confirm('Are you sure you want to unassign this teacher? This action cannot be undone.')) {
            const button = event.target.closest('button');
            const originalContent = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            button.disabled = true;

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
                        showNotification(data.message, 'success');
                        location.reload();
                    } else {
                        showNotification('Error: ' + data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('An error occurred while unassigning the teacher', 'error');
                })
                .finally(() => {
                    button.innerHTML = originalContent;
                    button.disabled = false;
                });
        }
    }

    function filterAssignments() {
        const searchTerm = document.getElementById('searchAssignments').value.toLowerCase();
        const rows = document.querySelectorAll('.assignment-row');

        rows.forEach(row => {
            const teacherName = row.querySelector('.teacher-name').textContent.toLowerCase();
            const teacherEmail = row.querySelector('.teacher-email').textContent.toLowerCase();
            const subjectName = row.querySelector('.subject-badge').textContent.toLowerCase();
            const className = row.querySelector('.class-badge').textContent.toLowerCase();

            const matches = teacherName.includes(searchTerm) ||
                teacherEmail.includes(searchTerm) ||
                subjectName.includes(searchTerm) ||
                className.includes(searchTerm);

            if (matches) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }


    function viewAssignmentDetails(assignmentId) {
        alert('Assignment details view coming soon!');
    }

    function showBulkAssignModal() {
        alert('Bulk assignment feature coming soon!');
    }

    function exportAssignments() {
        alert('Export feature coming soon!');
    }


    function toggleAdvancedFilters() {
        alert('Advanced filters coming soon!');
    }


    function showNotification(message, type) {

        if (type === 'success') {
            alert('✓ ' + message);
        } else {
            alert('✗ ' + message);
        }
    }

    function updateAssignmentPreview() {
        const teacherSelect = document.getElementById('teacherSelect');
        const subjectSelect = document.getElementById('subjectSelect');
        const classSelect = document.getElementById('classSelect');
        const preview = document.getElementById('assignmentPreview');

        if (teacherSelect.value && subjectSelect.value && classSelect.value) {
            document.getElementById('previewTeacher').textContent = teacherSelect.options[teacherSelect.selectedIndex]
                .text;
            document.getElementById('previewSubject').textContent = subjectSelect.options[subjectSelect.selectedIndex]
                .text;
            document.getElementById('previewClass').textContent = classSelect.options[classSelect.selectedIndex].text;
            preview.style.display = 'block';
        } else {
            preview.style.display = 'none';
        }
    }


    document.getElementById('assignForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        const submitBtn = document.getElementById('submitBtn');
        const originalText = submitBtn.innerHTML;

        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
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
                    showNotification(data.message, 'success');
                    closeModal();
                    location.reload();
                } else {
                    showNotification('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('An error occurred while processing the request', 'error');
            })
            .finally(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
    });
    </script>
</body>

</html>