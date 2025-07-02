<?php
session_start();
require_once '../config/db.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    header('Content-Type: application/json');

    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'create':
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');

            if (empty($name)) {
                echo json_encode(['success' => false, 'message' => 'Subject name is required']);
                exit;
            }

            // Check if subject name already exists
            $query = "SELECT id FROM subjects WHERE name = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("s", $name);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                echo json_encode(['success' => false, 'message' => 'Subject name already exists']);
                exit;
            }

            // Create subject
            $query = "INSERT INTO subjects (name, description) VALUES (?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ss", $name, $description);

            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Subject created successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to create subject']);
            }
            exit;

        case 'update':
            $id = intval($_POST['id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');

            if ($id <= 0 || empty($name)) {
                echo json_encode(['success' => false, 'message' => 'Subject ID and name are required']);
                exit;
            }

            // Check if subject name exists for other subjects
            $query = "SELECT id FROM subjects WHERE name = ? AND id != ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("si", $name, $id);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                echo json_encode(['success' => false, 'message' => 'Subject name already exists']);
                exit;
            }

            // Update subject
            $query = "UPDATE subjects SET name = ?, description = ? WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ssi", $name, $description, $id);

            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Subject updated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update subject']);
            }
            exit;

        case 'delete':
            $id = intval($_POST['id'] ?? 0);

            if ($id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid subject ID']);
                exit;
            }

            // Check if subject has teachers assigned
            $query = "SELECT COUNT(*) as count FROM teacher_subject_class WHERE subject_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $teacher_count = $stmt->get_result()->fetch_assoc()['count'];

            if ($teacher_count > 0) {
                echo json_encode(['success' => false, 'message' => 'Cannot delete subject with assigned teachers']);
                exit;
            }

            // Delete subject
            $query = "DELETE FROM subjects WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $id);

            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Subject deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete subject']);
            }
            exit;

        case 'get':
            $id = intval($_POST['id'] ?? 0);

            if ($id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid subject ID']);
                exit;
            }

            $query = "SELECT * FROM subjects WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $subject = $stmt->get_result()->fetch_assoc();

            if ($subject) {
                echo json_encode(['success' => true, 'subject' => $subject]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Subject not found']);
            }
            exit;
    }
}

// Get all subjects with teacher counts
$search = $_GET['search'] ?? '';

$query = "SELECT s.*, 
          (SELECT COUNT(*) FROM teacher_subject_class tsc WHERE tsc.subject_id = s.id) as teacher_count
          FROM subjects s WHERE 1=1";

$params = [];
$types = "";

if (!empty($search)) {
    $query .= " AND (s.name LIKE ? OR s.description LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}

$query .= " ORDER BY s.created_at DESC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$subjects = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subject Management - EduLearn LMS</title>
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
                <a href="manage_subject.php" class="nav-item active">
                    <i class="fas fa-book"></i>
                    <span>Manage Subjects</span>
                </a>
                <a href="assign_teacher.php" class="nav-item">
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

        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <header class="content-header">
                <div class="header-left">
                    <h1>Subject Management</h1>
                    <p>Create and manage subjects</p>
                </div>
                <div class="header-right">
                    <button class="btn btn-primary" onclick="openCreateModal()">
                        <i class="fas fa-plus"></i> Add New Subject
                    </button>
                </div>
            </header>

            <!-- Filters -->
            <div class="filters-section" style="margin-bottom: 2rem;">
                <form method="GET" class="filters-form" style="display: flex; gap: 1rem; align-items: center;">
                    <div class="filter-group">
                        <input type="text" name="search" placeholder="Search subjects..."
                            value="<?php echo htmlspecialchars($search); ?>"
                            style="padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px; width: 250px;">
                    </div>
                    <button type="submit" class="btn btn-secondary">
                        <i class="fas fa-search"></i> Search
                    </button>
                    <a href="manage_subject.php" class="btn btn-outline">
                        <i class="fas fa-times"></i> Clear
                    </a>
                </form>
            </div>

            <!-- Subjects Table -->
            <div class="content-card">
                <div class="card-header">
                    <h3>Subjects (<?php echo count($subjects); ?>)</h3>
                </div>
                <div class="card-content">
                    <?php if (empty($subjects)): ?>
                        <p style="text-align: center; color: #666; padding: 2rem;">
                            No subjects found matching your criteria.
                        </p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Subject Name</th>
                                        <th>Description</th>
                                        <th>Teachers</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($subjects as $subject): ?>
                                        <tr>
                                            <td><?php echo $subject['id']; ?></td>
                                            <td><?php echo htmlspecialchars($subject['name']); ?></td>
                                            <td><?php echo htmlspecialchars($subject['description'] ?: 'No description'); ?></td>
                                            <td><?php echo $subject['teacher_count']; ?></td>
                                            <td><?php echo date('M j, Y', strtotime($subject['created_at'])); ?></td>
                                            <td>
                                                <div class="action-buttons">
                                                    <button class="btn-icon btn-edit" onclick="editSubject(<?php echo $subject['id']; ?>)" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn-icon btn-delete" onclick="deleteSubject(<?php echo $subject['id']; ?>)" title="Delete">
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

    <!-- Create/Edit Subject Modal -->
    <div id="subjectModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Add New Subject</h3>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <form id="subjectForm">
                <input type="hidden" id="subjectId" name="id">
                <input type="hidden" id="formAction" name="action" value="create">

                <div class="form-group">
                    <label for="subjectName">Subject Name *</label>
                    <input type="text" id="subjectName" name="name" required>
                </div>

                <div class="form-group">
                    <label for="subjectDescription">Description</label>
                    <textarea id="subjectDescription" name="description" rows="3" placeholder="Optional subject description"></textarea>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="submitBtn">Create Subject</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Modal functions
        function openCreateModal() {
            document.getElementById('modalTitle').textContent = 'Add New Subject';
            document.getElementById('formAction').value = 'create';
            document.getElementById('submitBtn').textContent = 'Create Subject';
            document.getElementById('subjectForm').reset();
            document.getElementById('subjectModal').style.display = 'block';
        }

        function editSubject(subjectId) {
            // Get subject data
            fetch('manage_subject.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: 'action=get&id=' + subjectId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('modalTitle').textContent = 'Edit Subject';
                        document.getElementById('formAction').value = 'update';
                        document.getElementById('submitBtn').textContent = 'Update Subject';

                        document.getElementById('subjectId').value = data.subject.id;
                        document.getElementById('subjectName').value = data.subject.name;
                        document.getElementById('subjectDescription').value = data.subject.description || '';

                        document.getElementById('subjectModal').style.display = 'block';
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while fetching subject data');
                });
        }

        function deleteSubject(subjectId) {
            if (confirm('Are you sure you want to delete this subject? This action cannot be undone.')) {
                fetch('manage_subject.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: 'action=delete&id=' + subjectId
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
                        alert('An error occurred while deleting the subject');
                    });
            }
        }

        function closeModal() {
            document.getElementById('subjectModal').style.display = 'none';
        }

        // Form submission
        document.getElementById('subjectForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const submitBtn = document.getElementById('submitBtn');
            const originalText = submitBtn.textContent;

            submitBtn.textContent = 'Processing...';
            submitBtn.disabled = true;

            fetch('manage_subject.php', {
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
            const modal = document.getElementById('subjectModal');
            if (event.target === modal) {
                closeModal();
            }
        }
    </script>
</body>

</html>