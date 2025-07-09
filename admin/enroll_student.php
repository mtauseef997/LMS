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

// Get enrollment statistics
$stats = [];
$result = $conn->query("SELECT COUNT(*) as total_enrollments FROM student_class");
$stats['total_enrollments'] = $result->fetch_assoc()['total_enrollments'];

$result = $conn->query("SELECT COUNT(DISTINCT student_id) as enrolled_students FROM student_class");
$stats['enrolled_students'] = $result->fetch_assoc()['enrolled_students'];

$result = $conn->query("SELECT COUNT(DISTINCT class_id) as classes_with_students FROM student_class");
$stats['classes_with_students'] = $result->fetch_assoc()['classes_with_students'];
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'enroll';

    if ($action === 'remove') {
        // Handle enrollment removal
        $enrollment_id = intval($_POST['enrollment_id'] ?? 0);

        if ($enrollment_id <= 0) {
            $error = 'Invalid enrollment ID.';
        } else {
            $stmt = $conn->prepare("DELETE FROM student_class WHERE id = ?");
            $stmt->bind_param("i", $enrollment_id);
            if ($stmt->execute()) {
                $success = 'Student enrollment removed successfully.';
            } else {
                $error = 'Failed to remove enrollment. Please try again.';
            }
        }
    } else {
        // Handle new enrollment
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
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enroll Student - EduLearn LMS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
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
                <a href="assign_teacher.php" class="nav-item">
                    <i class="fas fa-chalkboard-teacher"></i>
                    <span>Assign Teachers</span>
                </a>
                <a href="enroll_student.php" class="nav-item active">
                    <i class="fas fa-user-graduate"></i>
                    <span>Enroll Students</span>
                </a>
                <a href="reports.php" class="nav-item">
                    <i class="fas fa-chart-bar"></i>
                    <span>Reports</span>
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
                    <h1><i class="fas fa-user-graduate"></i> Enroll Students</h1>
                    <p>Assign students to classes and manage enrollments efficiently</p>
                </div>
                <div class="header-right">
                    <button onclick="showBulkEnrollModal()" class="btn btn-secondary">
                        <i class="fas fa-users"></i> Bulk Enroll
                    </button>
                    <button onclick="exportEnrollments()" class="btn btn-info">
                        <i class="fas fa-download"></i> Export Data
                    </button>
                </div>
            </header>

            <div class="content-body">
                <!-- Enhanced Statistics Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                            <i class="fas fa-graduation-cap"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo $stats['total_enrollments']; ?></h3>
                            <p>Total Enrollments</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo $stats['enrolled_students']; ?></h3>
                            <p>Enrolled Students</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                            <i class="fas fa-school"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo $stats['classes_with_students']; ?></h3>
                            <p>Active Classes</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo count($students); ?></h3>
                            <p>Total Students</p>
                        </div>
                    </div>
                </div>

                <!-- Enhanced Enrollment Form -->
                <div class="content-card">
                    <div class="card-header">
                        <h3><i class="fas fa-user-plus"></i> Enroll New Student</h3>
                        <p>Select a student and class to create a new enrollment</p>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle"></i>
                                <?php echo htmlspecialchars($error); ?>
                            </div>
                        <?php endif; ?>
                        <?php if ($success): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle"></i>
                                <?php echo htmlspecialchars($success); ?>
                            </div>
                        <?php endif; ?>

                        <div class="form-container">
                            <form method="POST" class="enrollment-form">
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="student_id">
                                            <i class="fas fa-user"></i> Select Student
                                        </label>
                                        <div class="select-wrapper">
                                            <select name="student_id" id="student_id" required>
                                                <option value="">-- Choose a student --</option>
                                                <?php foreach ($students as $student): ?>
                                                    <option value="<?php echo $student['id']; ?>">
                                                        <?php echo htmlspecialchars($student['name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <i class="fas fa-chevron-down select-arrow"></i>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label for="class_id">
                                            <i class="fas fa-school"></i> Select Class
                                        </label>
                                        <div class="select-wrapper">
                                            <select name="class_id" id="class_id" required>
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

                                <div class="form-actions">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-user-plus"></i> Enroll Student
                                    </button>
                                    <button type="button" onclick="resetForm()" class="btn btn-secondary">
                                        <i class="fas fa-undo"></i> Reset Form
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Enhanced Current Enrollments -->
                <div class="content-card">
                    <div class="card-header">
                        <h3><i class="fas fa-list"></i> Current Enrollments</h3>
                        <p>Manage existing student enrollments and class assignments</p>
                    </div>
                    <div class="card-body">
                        <?php
                        $enrollments_query = "SELECT sc.id, u.name as student_name, u.email as student_email,
                                         c.name as class_name, c.description as class_description
                                         FROM student_class sc
                                         JOIN users u ON sc.student_id = u.id
                                         JOIN classes c ON sc.class_id = c.id
                                         ORDER BY u.name, c.name";
                        $enrollments_result = $conn->query($enrollments_query);
                        ?>

                        <?php if ($enrollments_result && $enrollments_result->num_rows > 0): ?>
                            <div class="table-container">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th><i class="fas fa-user"></i> Student</th>
                                            <th><i class="fas fa-envelope"></i> Email</th>
                                            <th><i class="fas fa-school"></i> Class</th>
                                            <th><i class="fas fa-info-circle"></i> Description</th>
                                            <th><i class="fas fa-cogs"></i> Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($enrollment = $enrollments_result->fetch_assoc()): ?>
                                            <tr class="enrollment-row">
                                                <td>
                                                    <div class="student-info">
                                                        <div class="student-avatar">
                                                            <i class="fas fa-user"></i>
                                                        </div>
                                                        <span class="student-name"><?php echo htmlspecialchars($enrollment['student_name']); ?></span>
                                                    </div>
                                                </td>
                                                <td class="student-email"><?php echo htmlspecialchars($enrollment['student_email']); ?></td>
                                                <td>
                                                    <span class="class-badge"><?php echo htmlspecialchars($enrollment['class_name']); ?></span>
                                                </td>
                                                <td class="class-description"><?php echo htmlspecialchars($enrollment['class_description'] ?? 'No description'); ?></td>
                                                <td>
                                                    <div class="action-buttons">
                                                        <button type="button" onclick="removeEnrollment(<?php echo $enrollment['id']; ?>)"
                                                            class="btn btn-sm btn-danger" title="Remove Enrollment">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-user-graduate"></i>
                                <h3>No Enrollments Found</h3>
                                <p>No student enrollments found. Start by enrolling students in classes above.</p>
                                <button onclick="document.getElementById('student_id').focus()" class="btn btn-primary">
                                    <i class="fas fa-plus"></i> Create First Enrollment
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Enhanced CSS Styles -->
    <style>
        /* Enhanced Select Wrapper */
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

        /* Enhanced Student Info */
        .student-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .student-avatar {
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

        .student-name {
            font-weight: 600;
            color: #374151;
        }

        /* Enhanced Class Badge */
        .class-badge {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 600;
            display: inline-block;
        }

        /* Enhanced Action Buttons */
        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        /* Enhanced Table Styles */
        .enrollment-row:hover {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        }

        .table-container {
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        /* Enhanced Header Actions */
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

        /* Enhanced Form Styles */
        .enrollment-form {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            padding: 2rem;
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

        /* Enhanced Card Header */
        .card-header p {
            margin: 0.5rem 0 0 0;
            color: #6b7280;
            font-size: 0.875rem;
        }

        /* Responsive Design */
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

            .student-info {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }
        }

        /* Loading Animation */
        .loading {
            opacity: 0.6;
            pointer-events: none;
        }

        .loading::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 20px;
            height: 20px;
            margin: -10px 0 0 -10px;
            border: 2px solid #667eea;
            border-radius: 50%;
            border-top-color: transparent;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }
    </style>

    <!-- Enhanced JavaScript -->
    <script>
        // Reset form function
        function resetForm() {
            document.querySelector('.enrollment-form').reset();
            document.getElementById('student_id').focus();
        }

        // Remove enrollment function
        function removeEnrollment(enrollmentId) {
            if (confirm('Are you sure you want to remove this student from the class?')) {
                // Create a form and submit it
                const form = document.createElement('form');
                form.method = 'POST';
                form.style.display = 'none';

                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'remove';

                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'enrollment_id';
                idInput.value = enrollmentId;

                form.appendChild(actionInput);
                form.appendChild(idInput);
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Filter enrollments function
        function filterEnrollments() {
            const searchTerm = document.getElementById('searchEnrollments').value.toLowerCase();
            const classFilter = document.getElementById('classFilter').value.toLowerCase();
            const rows = document.querySelectorAll('.enrollment-row');

            rows.forEach(row => {
                const studentName = row.querySelector('.student-name').textContent.toLowerCase();
                const studentEmail = row.querySelector('.student-email').textContent.toLowerCase();
                const className = row.querySelector('.class-badge').textContent.toLowerCase();

                const matchesSearch = studentName.includes(searchTerm) ||
                    studentEmail.includes(searchTerm) ||
                    className.includes(searchTerm);
                const matchesClass = !classFilter || className.includes(classFilter);

                if (matchesSearch && matchesClass) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        // Bulk enroll modal function
        function showBulkEnrollModal() {
            alert('Bulk enrollment feature coming soon!');
        }

        // Export enrollments function
        function exportEnrollments() {
            alert('Export feature coming soon!');
        }

        // Enhanced form validation
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('.enrollment-form');
            const studentSelect = document.getElementById('student_id');
            const classSelect = document.getElementById('class_id');

            form.addEventListener('submit', function(e) {
                if (!studentSelect.value || !classSelect.value) {
                    e.preventDefault();
                    alert('Please select both a student and a class.');
                    return false;
                }

                // Add loading state
                const submitBtn = form.querySelector('button[type="submit"]');
                submitBtn.classList.add('loading');
                submitBtn.disabled = true;
            });

            // Auto-focus first field
            studentSelect.focus();
        });
    </script>
</body>

</html>