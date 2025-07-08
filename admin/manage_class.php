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
        case 'create':
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');

            if (empty($name)) {
                echo json_encode(['success' => false, 'message' => 'Class name is required']);
                exit;
            }

            $query = "SELECT id FROM classes WHERE name = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("s", $name);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                echo json_encode(['success' => false, 'message' => 'Class name already exists']);
                exit;
            }

            $query = "INSERT INTO classes (name, description) VALUES (?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ss", $name, $description);

            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Class created successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to create class']);
            }
            exit;

        case 'update':
            $id = intval($_POST['id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');

            if ($id <= 0 || empty($name)) {
                echo json_encode(['success' => false, 'message' => 'Class ID and name are required']);
                exit;
            }

            $query = "SELECT id FROM classes WHERE name = ? AND id != ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("si", $name, $id);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                echo json_encode(['success' => false, 'message' => 'Class name already exists']);
                exit;
            }

            $query = "UPDATE classes SET name = ?, description = ? WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ssi", $name, $description, $id);

            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Class updated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update class']);
            }
            exit;

        case 'delete':
            $id = intval($_POST['id'] ?? 0);

            if ($id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid class ID']);
                exit;
            }


            $query = "SELECT COUNT(*) as count FROM student_class WHERE class_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $student_count = $stmt->get_result()->fetch_assoc()['count'];

            $query = "SELECT COUNT(*) as count FROM teacher_subject_class WHERE class_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $teacher_count = $stmt->get_result()->fetch_assoc()['count'];

            if ($student_count > 0 || $teacher_count > 0) {
                echo json_encode(['success' => false, 'message' => 'Cannot delete class with enrolled students or assigned teachers']);
                exit;
            }

            $query = "DELETE FROM classes WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $id);

            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Class deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete class']);
            }
            exit;

        case 'bulk_delete':
            $ids = $_POST['ids'] ?? [];

            if (empty($ids) || !is_array($ids)) {
                echo json_encode(['success' => false, 'message' => 'No classes selected']);
                exit;
            }

            $placeholders = str_repeat('?,', count($ids) - 1) . '?';
            $query = "DELETE FROM classes WHERE id IN ($placeholders)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param(str_repeat('i', count($ids)), ...$ids);

            if ($stmt->execute()) {
                $deleted_count = $stmt->affected_rows;
                echo json_encode(['success' => true, 'message' => "$deleted_count classes deleted successfully"]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete classes']);
            }
            exit;

        case 'get':
            $id = intval($_POST['id'] ?? 0);

            if ($id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid class ID']);
                exit;
            }

            $query = "SELECT * FROM classes WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $class = $stmt->get_result()->fetch_assoc();

            if ($class) {
                echo json_encode(['success' => true, 'class' => $class]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Class not found']);
            }
            exit;
    }
}

$search = $_GET['search'] ?? '';

$query = "SELECT c.*, 
          (SELECT COUNT(*) FROM student_class sc WHERE sc.class_id = c.id) as student_count,
          (SELECT COUNT(*) FROM teacher_subject_class tsc WHERE tsc.class_id = c.id) as teacher_count
          FROM classes c WHERE 1=1";

$params = [];
$types = "";

if (!empty($search)) {
    $query .= " AND (c.name LIKE ? OR c.description LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}

$query .= " ORDER BY c.name ASC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$classes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Class Management - EduLearn LMS</title>
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
                <a href="manage_class.php" class="nav-item active">
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
                    <h1><i class="fas fa-school"></i> Class Management</h1>
                    <p>Create and manage academic classes</p>
                </div>
                <div class="header-right">
                    <button class="btn btn-primary" onclick="openCreateModal()">
                        <i class="fas fa-plus-circle"></i> Add New Class
                    </button>
                </div>
            </header>


            <div class="filters-section" style="margin-bottom: 2rem;">
                <form method="GET" class="filters-form" style="display: flex; gap: 1rem; align-items: center;">
                    <div class="filter-group">
                        <input type="text" name="search" placeholder="Search classes..."
                            value="<?php echo htmlspecialchars($search); ?>"
                            style="padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px; width: 250px;">
                    </div>
                    <button type="submit" class="btn btn-secondary">
                        <i class="fas fa-search"></i> Search
                    </button>
                    <a href="manage_class.php" class="btn btn-outline">
                        <i class="fas fa-times"></i> Clear
                    </a>
                </form>
            </div>


            <div class="content-card">
                <div class="card-header">
                    <h3><i class="fas fa-list-alt"></i> Classes (<?php echo count($classes); ?>)</h3>
                    <div style="display: flex; gap: 1rem; align-items: center;">
                        <span style="font-size: 0.9rem; color: #666;">
                            <i class="fas fa-graduation-cap"></i> Total: <?php echo count($classes); ?> classes
                        </span>
                    </div>
                </div>
                <div class="card-content">
                    <?php if (empty($classes)): ?>
                        <p style="text-align: center; color: #666; padding: 2rem;">
                            No classes found matching your criteria.
                        </p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Class Name</th>
                                        <th>Description</th>
                                        <th>Students</th>
                                        <th>Teachers</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($classes as $class): ?>
                                        <tr>
                                            <td><?php echo $class['id']; ?></td>
                                            <td><?php echo htmlspecialchars($class['name']); ?></td>
                                            <td><?php echo htmlspecialchars($class['description'] ?: 'No description'); ?></td>
                                            <td><?php echo $class['student_count']; ?></td>
                                            <td><?php echo $class['teacher_count']; ?></td>
                                            <td><?php echo date('M j, Y', strtotime($class['created_at'])); ?></td>
                                            <td>
                                                <div class="action-buttons">
                                                    <button class="btn-icon btn-edit"
                                                        onclick="editClass(<?php echo $class['id']; ?>)" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn-icon btn-delete"
                                                        onclick="deleteClass(<?php echo $class['id']; ?>)" title="Delete">
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


    <div id="classModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Add New Class</h3>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <form id="classForm">
                <input type="hidden" id="classId" name="id">
                <input type="hidden" id="formAction" name="action" value="create">

                <div class="form-group">
                    <label for="className">Class Name *</label>
                    <input type="text" id="className" name="name" required>
                </div>

                <div class="form-group">
                    <label for="classDescription">Description</label>
                    <textarea id="classDescription" name="description" rows="3"
                        placeholder="Optional class description"></textarea>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="submitBtn">Create Class</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openCreateModal() {
            document.getElementById('modalTitle').textContent = 'Add New Class';
            document.getElementById('formAction').value = 'create';
            document.getElementById('submitBtn').textContent = 'Create Class';
            document.getElementById('classForm').reset();
            document.getElementById('classModal').style.display = 'block';
        }

        function editClass(classId) {

            fetch('manage_class.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: 'action=get&id=' + classId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('modalTitle').textContent = 'Edit Class';
                        document.getElementById('formAction').value = 'update';
                        document.getElementById('submitBtn').textContent = 'Update Class';

                        document.getElementById('classId').value = data.class.id;
                        document.getElementById('className').value = data.class.name;
                        document.getElementById('classDescription').value = data.class.description || '';

                        document.getElementById('classModal').style.display = 'block';
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while fetching class data');
                });
        }

        function deleteClass(classId) {
            if (confirm('Are you sure you want to delete this class? This action cannot be undone.')) {
                fetch('manage_class.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: 'action=delete&id=' + classId
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
                        alert('An error occurred while deleting the class');
                    });
            }
        }

        function closeModal() {
            document.getElementById('classModal').style.display = 'none';
        }


        document.getElementById('classForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const submitBtn = document.getElementById('submitBtn');
            const originalText = submitBtn.textContent;

            submitBtn.textContent = 'Processing...';
            submitBtn.disabled = true;

            fetch('manage_class.php', {
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
            const modal = document.getElementById('classModal');
            if (event.target === modal) {
                closeModal();
            }
        }
    </script>
</body>

</html>