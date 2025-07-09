<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'teacher') {
    header('Location: ../login.php');
    exit;
}

$teacher_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    header('Content-Type: application/json');

    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'create':
            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $subject_id = intval($_POST['subject_id'] ?? 0);
            $class_id = intval($_POST['class_id'] ?? 0);
            $due_date = $_POST['due_date'] ?? '';
            $max_marks = intval($_POST['max_marks'] ?? 0);

            if (empty($title) || $subject_id <= 0 || $class_id <= 0 || empty($due_date) || $max_marks <= 0) {
                echo json_encode(['success' => false, 'message' => 'All fields are required']);
                exit;
            }

            $verify_query = "SELECT id FROM teacher_subject_class WHERE teacher_id = ? AND subject_id = ? AND class_id = ?";
            $verify_stmt = $conn->prepare($verify_query);
            $verify_stmt->bind_param("iii", $teacher_id, $subject_id, $class_id);
            $verify_stmt->execute();
            if ($verify_stmt->get_result()->num_rows === 0) {
                echo json_encode(['success' => false, 'message' => 'You are not assigned to this subject-class combination']);
                exit;
            }

            $query = "INSERT INTO assignments (title, description, subject_id, class_id, teacher_id, due_date, max_marks) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ssiiiisi", $title, $description, $subject_id, $class_id, $teacher_id, $due_date, $max_marks);

            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Assignment created successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to create assignment']);
            }
            exit;

        case 'delete':
            $assignment_id = intval($_POST['assignment_id'] ?? 0);

            if ($assignment_id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid assignment ID']);
                exit;
            }

            $verify_query = "SELECT id FROM assignments WHERE id = ? AND teacher_id = ?";
            $verify_stmt = $conn->prepare($verify_query);
            $verify_stmt->bind_param("ii", $assignment_id, $teacher_id);
            $verify_stmt->execute();
            if ($verify_stmt->get_result()->num_rows === 0) {
                echo json_encode(['success' => false, 'message' => 'Assignment not found or access denied']);
                exit;
            }

            $query = "DELETE FROM assignments WHERE id = ? AND teacher_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ii", $assignment_id, $teacher_id);

            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Assignment deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete assignment']);
            }
            exit;
    }
}

$assignments_query = "SELECT tsc.subject_id, tsc.class_id, s.name as subject_name, c.name as class_name 
                     FROM teacher_subject_class tsc 
                     JOIN subjects s ON tsc.subject_id = s.id 
                     JOIN classes c ON tsc.class_id = c.id 
                     WHERE tsc.teacher_id = ? 
                     ORDER BY s.name, c.name";
$assignments_stmt = $conn->prepare($assignments_query);
$assignments_stmt->bind_param("i", $teacher_id);
$assignments_stmt->execute();
$assignments = $assignments_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$subject_filter = $_GET['subject'] ?? '';
$class_filter = $_GET['class'] ?? '';

$query = "SELECT a.*, s.name as subject_name, c.name as class_name,
          (SELECT COUNT(*) FROM assignment_submissions asub WHERE asub.assignment_id = a.id) as submission_count,
          (SELECT COUNT(*) FROM student_class sc WHERE sc.class_id = a.class_id) as total_students
          FROM assignments a
          JOIN subjects s ON a.subject_id = s.id
          JOIN classes c ON a.class_id = c.id
          WHERE a.teacher_id = ?";

$params = [$teacher_id];
$types = "i";

if (!empty($subject_filter)) {
    $query .= " AND a.subject_id = ?";
    $params[] = $subject_filter;
    $types .= "i";
}

if (!empty($class_filter)) {
    $query .= " AND a.class_id = ?";
    $params[] = $class_filter;
    $types .= "i";
}

$query .= " ORDER BY a.due_date DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$teacher_assignments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Assignments - EduLearn LMS</title>
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
                <a href="manage_assignment.php" class="nav-item active">
                    <i class="fas fa-tasks"></i>
                    <span>Manage Assignments</span>
                </a>
                <a href="view_submissions.php" class="nav-item">
                    <i class="fas fa-clipboard-list"></i>
                    <span>View Submissions</span>
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
                    <h1>Manage Assignments</h1>
                    <p>Create and manage your assignments</p>
                </div>
                <div class="header-right">
                    <button class="btn btn-primary" onclick="openCreateModal()">
                        <i class="fas fa-plus"></i> Create New Assignment
                    </button>
                </div>
            </header>

            <div class="filters-section" style="margin-bottom: 2rem;">
                <form method="GET" class="filters-form"
                    style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
                    <div class="filter-group">
                        <select name="subject" style="padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
                            <option value="">All Subjects</option>
                            <?php
                            $unique_subjects = [];
                            foreach ($assignments as $assignment) {
                                if (!isset($unique_subjects[$assignment['subject_id']])) {
                                    $unique_subjects[$assignment['subject_id']] = $assignment['subject_name'];
                                    echo "<option value='{$assignment['subject_id']}'" . ($subject_filter == $assignment['subject_id'] ? ' selected' : '') . ">{$assignment['subject_name']}</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <select name="class" style="padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
                            <option value="">All Classes</option>
                            <?php
                            $unique_classes = [];
                            foreach ($assignments as $assignment) {
                                if (!isset($unique_classes[$assignment['class_id']])) {
                                    $unique_classes[$assignment['class_id']] = $assignment['class_name'];
                                    echo "<option value='{$assignment['class_id']}'" . ($class_filter == $assignment['class_id'] ? ' selected' : '') . ">{$assignment['class_name']}</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-secondary">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                    <a href="manage_assignment.php" class="btn btn-outline">
                        <i class="fas fa-times"></i> Clear
                    </a>
                </form>
            </div>

            <div class="content-card">
                <div class="card-header">
                    <h3>Your Assignments (<?php echo count($teacher_assignments); ?>)</h3>
                </div>
                <div class="card-content">
                    <?php if (empty($teacher_assignments)): ?>
                    <p style="text-align: center; color: #666; padding: 2rem;">
                        No assignments found. Create your first assignment to get started!
                    </p>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Assignment Title</th>
                                    <th>Subject</th>
                                    <th>Class</th>
                                    <th>Due Date</th>
                                    <th>Max Marks</th>
                                    <th>Submissions</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($teacher_assignments as $assignment): ?>
                                <?php
                                        $due_date = new DateTime($assignment['due_date']);
                                        $now = new DateTime();
                                        $is_overdue = $now > $due_date;
                                        ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($assignment['title']); ?></td>
                                    <td><?php echo htmlspecialchars($assignment['subject_name']); ?></td>
                                    <td><?php echo htmlspecialchars($assignment['class_name']); ?></td>
                                    <td style="color: <?php echo $is_overdue ? '#ef4444' : '#333'; ?>">
                                        <?php echo $due_date->format('M j, Y g:i A'); ?>
                                        <?php if ($is_overdue): ?>
                                        <br><small style="color: #ef4444;">Overdue</small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $assignment['max_marks']; ?></td>
                                    <td><?php echo $assignment['submission_count']; ?>/<?php echo $assignment['total_students']; ?>
                                    </td>
                                    <td><?php echo date('M j, Y', strtotime($assignment['created_at'])); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="assignment_submissions.php?assignment_id=<?php echo $assignment['id']; ?>"
                                                class="btn-icon btn-primary" title="View Submissions">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <button class="btn-icon btn-delete"
                                                onclick="deleteAssignment(<?php echo $assignment['id']; ?>)"
                                                title="Delete">
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
        </main>
    </div>

    <div id="createModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Create New Assignment</h3>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <form id="createForm">
                <input type="hidden" name="action" value="create">

                <div class="form-group">
                    <label for="assignmentTitle">Assignment Title *</label>
                    <input type="text" id="assignmentTitle" name="title" required maxlength="255">
                </div>

                <div class="form-group">
                    <label for="assignmentDescription">Description *</label>
                    <textarea id="assignmentDescription" name="description" rows="4" required
                        placeholder="Describe the assignment requirements..."></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="subjectSelect">Subject *</label>
                        <select id="subjectSelect" name="subject_id" required>
                            <option value="">Select Subject</option>
                            <?php
                            $unique_subjects = [];
                            foreach ($assignments as $assignment) {
                                if (!isset($unique_subjects[$assignment['subject_id']])) {
                                    $unique_subjects[$assignment['subject_id']] = $assignment['subject_name'];
                                    echo "<option value='{$assignment['subject_id']}'>{$assignment['subject_name']}</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="classSelect">Class *</label>
                        <select id="classSelect" name="class_id" required>
                            <option value="">Select Class</option>
                            <?php foreach ($assignments as $assignment): ?>
                            <option value="<?php echo $assignment['class_id']; ?>"
                                data-subject="<?php echo $assignment['subject_id']; ?>">
                                <?php echo htmlspecialchars($assignment['class_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="dueDate">Due Date *</label>
                        <input type="datetime-local" id="dueDate" name="due_date" required>
                    </div>

                    <div class="form-group">
                        <label for="maxMarks">Maximum Marks *</label>
                        <input type="number" id="maxMarks" name="max_marks" min="1" required>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="submitBtn">Create Assignment</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Enhanced Responsive Modal CSS -->
    <style>
    /* Modal Base Styles */
    .modal {
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(5px);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1rem;
        box-sizing: border-box;
    }

    .modal-content {
        background-color: #ffffff;
        border-radius: 12px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
        width: 100%;
        max-width: 600px;
        max-height: 90vh;
        overflow-y: auto;
        animation: modalSlideIn 0.3s ease-out;
        position: relative;
    }

    @keyframes modalSlideIn {
        from {
            opacity: 0;
            transform: translateY(-50px) scale(0.95);
        }

        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    .modal-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 1.5rem 2rem;
        border-radius: 12px 12px 0 0;
        position: relative;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .modal-header h3 {
        margin: 0;
        font-size: 1.25rem;
        font-weight: 600;
    }

    .close {
        color: white;
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
        transition: opacity 0.2s;
        background: none;
        border: none;
        padding: 0;
        line-height: 1;
    }

    .close:hover {
        opacity: 0.7;
    }

    /* Form Styles */
    .modal form {
        padding: 2rem;
    }

    .form-group {
        margin-bottom: 1.5rem;
    }

    .form-group label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 600;
        color: #374151;
        font-size: 0.875rem;
    }

    .form-group input,
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 0.75rem;
        border: 2px solid #e5e7eb;
        border-radius: 8px;
        font-size: 1rem;
        transition: border-color 0.2s, box-shadow 0.2s;
        box-sizing: border-box;
    }

    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    .form-group textarea {
        resize: vertical;
        min-height: 100px;
    }

    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
    }

    .form-actions {
        display: flex;
        gap: 1rem;
        justify-content: flex-end;
        margin-top: 2rem;
        padding-top: 1rem;
        border-top: 1px solid #e5e7eb;
    }

    .btn {
        padding: 0.75rem 1.5rem;
        border: none;
        border-radius: 8px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }

    .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 20px rgba(102, 126, 234, 0.3);
    }

    .btn-secondary {
        background: #6b7280;
        color: white;
    }

    .btn-secondary:hover {
        background: #4b5563;
    }

    .btn:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        transform: none !important;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .modal {
            padding: 0.5rem;
            align-items: flex-start;
            padding-top: 2rem;
        }

        .modal-content {
            max-width: 100%;
            max-height: calc(100vh - 2rem);
            margin: 0;
        }

        .modal-header {
            padding: 1rem 1.5rem;
        }

        .modal-header h3 {
            font-size: 1.125rem;
        }

        .modal form {
            padding: 1.5rem;
        }

        .form-row {
            grid-template-columns: 1fr;
            gap: 0;
        }

        .form-actions {
            flex-direction: column-reverse;
            gap: 0.75rem;
        }

        .btn {
            width: 100%;
            justify-content: center;
            padding: 1rem;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            font-size: 16px;
            /* Prevents zoom on iOS */
        }
    }

    @media (max-width: 480px) {
        .modal {
            padding: 0;
            align-items: stretch;
        }

        .modal-content {
            max-width: 100%;
            max-height: 100vh;
            border-radius: 0;
            height: 100vh;
            overflow-y: auto;
        }

        .modal-header {
            border-radius: 0;
            padding: 1rem;
        }

        .modal form {
            padding: 1rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }
    }

    /* Landscape orientation on mobile */
    @media (max-height: 600px) and (orientation: landscape) {
        .modal {
            align-items: flex-start;
            padding-top: 1rem;
        }

        .modal-content {
            max-height: calc(100vh - 1rem);
        }

        .modal-header {
            padding: 0.75rem 1.5rem;
        }

        .modal form {
            padding: 1rem 1.5rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }
    }

    /* High DPI displays */
    @media (-webkit-min-device-pixel-ratio: 2),
    (min-resolution: 192dpi) {
        .modal-content {
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        }
    }

    /* Dark mode support */
    @media (prefers-color-scheme: dark) {
        .modal {
            background-color: rgba(0, 0, 0, 0.7);
        }

        .modal-content {
            background-color: #1f2937;
            color: #f9fafb;
        }

        .form-group label {
            color: #e5e7eb;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            background-color: #374151;
            border-color: #4b5563;
            color: #f9fafb;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #667eea;
            background-color: #374151;
        }

        .form-actions {
            border-top-color: #4b5563;
        }
    }

    /* Accessibility improvements */
    @media (prefers-reduced-motion: reduce) {
        .modal-content {
            animation: none;
        }

        .btn {
            transition: none;
        }
    }

    /* Focus management */
    .modal:focus-within .modal-content {
        outline: 2px solid #667eea;
        outline-offset: 2px;
    }

    /* Touch device improvements */
    @media (hover: none) and (pointer: coarse) {
        .btn {
            min-height: 44px;
            /* Minimum touch target size */
        }

        .close {
            min-width: 44px;
            min-height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    }
    </style>

    <script>
    function openCreateModal() {
        const modal = document.getElementById('createModal');
        const form = document.getElementById('createForm');

        // Reset form
        if (form) form.reset();

        // Show modal
        if (modal) {
            modal.style.display = 'block';

            // Focus management for accessibility
            setTimeout(() => {
                const firstInput = modal.querySelector('input, select, textarea');
                if (firstInput) firstInput.focus();
            }, 100);

            // Prevent body scroll when modal is open
            document.body.style.overflow = 'hidden';
        }
    }

    function closeModal() {
        const modal = document.getElementById('createModal');
        if (modal) {
            modal.style.display = 'none';

            // Restore body scroll
            document.body.style.overflow = '';
        }
    }

    // Enhanced keyboard navigation
    document.addEventListener('keydown', function(e) {
        const modal = document.getElementById('createModal');
        if (modal && modal.style.display === 'block') {
            // Close modal on Escape key
            if (e.key === 'Escape') {
                closeModal();
            }

            // Trap focus within modal
            if (e.key === 'Tab') {
                const focusableElements = modal.querySelectorAll(
                    'input, select, textarea, button, [tabindex]:not([tabindex="-1"])'
                );
                const firstElement = focusableElements[0];
                const lastElement = focusableElements[focusableElements.length - 1];

                if (e.shiftKey && document.activeElement === firstElement) {
                    e.preventDefault();
                    lastElement.focus();
                } else if (!e.shiftKey && document.activeElement === lastElement) {
                    e.preventDefault();
                    firstElement.focus();
                }
            }
        }
    });

    function deleteAssignment(assignmentId) {
        if (confirm(
                'Are you sure you want to delete this assignment? This will also delete all submissions. This action cannot be undone.'
            )) {
            fetch('manage_assignment.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: 'action=delete&assignment_id=' + assignmentId
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
                    alert('An error occurred while deleting the assignment');
                });
        }
    }

    document.getElementById('subjectSelect').addEventListener('change', function() {
        const selectedSubject = this.value;
        const classSelect = document.getElementById('classSelect');
        const classOptions = classSelect.querySelectorAll('option');

        classSelect.value = '';

        classOptions.forEach(option => {
            if (option.value === '') {
                option.style.display = 'block';
            } else {
                const optionSubject = option.getAttribute('data-subject');
                option.style.display = (selectedSubject === '' || optionSubject === selectedSubject) ?
                    'block' : 'none';
            }
        });
    });

    document.getElementById('createForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        const submitBtn = document.getElementById('submitBtn');
        const originalText = submitBtn.textContent;

        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating...';
        submitBtn.disabled = true;

        fetch('manage_assignment.php', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('✓ ' + data.message, 'success');
                    closeModal();
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showNotification('✗ Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('✗ An error occurred while creating the assignment', 'error');
            })
            .finally(() => {
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
            });
    });

    // Enhanced click outside to close
    window.addEventListener('click', function(event) {
        const modal = document.getElementById('createModal');
        if (event.target === modal) {
            closeModal();
        }
    });

    // Touch support for mobile devices
    window.addEventListener('touchstart', function(event) {
        const modal = document.getElementById('createModal');
        if (event.target === modal) {
            closeModal();
        }
    });

    // Responsive viewport handling
    function handleViewportChange() {
        const modal = document.getElementById('createModal');
        if (modal && modal.style.display === 'block') {
            // Adjust modal position on orientation change
            setTimeout(() => {
                const modalContent = modal.querySelector('.modal-content');
                if (modalContent) {
                    modalContent.scrollTop = 0;
                }
            }, 100);
        }
    }

    // Listen for orientation changes
    window.addEventListener('orientationchange', handleViewportChange);
    window.addEventListener('resize', handleViewportChange);

    // Enhanced notification system
    function showNotification(message, type) {
        // Create notification element
        const notification = document.createElement('div');
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            color: white;
            font-weight: 600;
            z-index: 2000;
            max-width: 300px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
            animation: slideInRight 0.3s ease-out;
            ${type === 'success' ? 'background: linear-gradient(135deg, #10b981 0%, #059669 100%);' : 'background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);'}
        `;

        notification.innerHTML = `
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
                <span>${message}</span>
            </div>
        `;

        // Add to page
        document.body.appendChild(notification);

        // Remove after 4 seconds
        setTimeout(() => {
            notification.style.animation = 'slideOutRight 0.3s ease-in';
            setTimeout(() => {
                if (notification.parentNode) {
                    document.body.removeChild(notification);
                }
            }, 300);
        }, 4000);
    }

    // Add CSS for notification animations
    const notificationStyle = document.createElement('style');
    notificationStyle.textContent = `
        @keyframes slideInRight {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        @keyframes slideOutRight {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }

        @media (max-width: 480px) {
            .notification {
                top: 10px !important;
                right: 10px !important;
                left: 10px !important;
                max-width: none !important;
            }
        }
    `;
    document.head.appendChild(notificationStyle);
    </script>
</body>

</html>