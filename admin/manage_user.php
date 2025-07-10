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


    error_log("Admin action: " . $action . " by user: " . $_SESSION['user_id']);

    switch ($action) {
        case 'create':
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $role = $_POST['role'] ?? '';


            if (empty($name) || empty($email) || empty($password) || empty($role)) {
                echo json_encode(['success' => false, 'message' => 'All fields are required']);
                exit;
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                echo json_encode(['success' => false, 'message' => 'Invalid email format']);
                exit;
            }

            if (!in_array($role, ['admin', 'teacher', 'student'])) {
                echo json_encode(['success' => false, 'message' => 'Invalid role']);
                exit;
            }


            $existing_user = getUserByEmail($conn, $email);
            if ($existing_user) {
                echo json_encode(['success' => false, 'message' => 'Email already exists']);
                exit;
            }

            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $query = "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ssss", $name, $email, $hashed_password, $role);

            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'User created successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to create user']);
            }
            exit;

        case 'update':
            $id = intval($_POST['id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $role = $_POST['role'] ?? '';

            if ($id <= 0 || empty($name) || empty($email) || empty($role)) {
                echo json_encode(['success' => false, 'message' => 'All fields are required']);
                exit;
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                echo json_encode(['success' => false, 'message' => 'Invalid email format']);
                exit;
            }

            $query = "SELECT id FROM users WHERE email = ? AND id != ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("si", $email, $id);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                echo json_encode(['success' => false, 'message' => 'Email already exists']);
                exit;
            }
            $query = "UPDATE users SET name = ?, email = ?, role = ? WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("sssi", $name, $email, $role, $id);

            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'User updated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update user']);
            }
            exit;

        case 'delete':
            $id = intval($_POST['id'] ?? 0);

            if ($id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
                exit;
            }

            if ($id == $_SESSION['user_id']) {
                echo json_encode(['success' => false, 'message' => 'Cannot delete your own account']);
                exit;
            }

            $query = "DELETE FROM users WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $id);

            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete user']);
            }
            exit;

        case 'get':
            $id = intval($_POST['id'] ?? 0);

            if ($id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
                exit;
            }

            $user = getUserById($conn, $id);
            if ($user) {
                unset($user['password']);
                echo json_encode(['success' => true, 'user' => $user]);
            } else {
                echo json_encode(['success' => false, 'message' => 'User not found']);
            }
            exit;

        case 'reset_password':
            $id = intval($_POST['id'] ?? 0);
            $new_password = $_POST['new_password'] ?? '';

            if ($id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
                exit;
            }

            if (empty($new_password) || strlen($new_password) < 6) {
                echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters long']);
                exit;
            }

            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $query = "UPDATE users SET password = ? WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("si", $hashed_password, $id);

            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Password reset successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to reset password']);
            }
            exit;

        default:
            echo json_encode(['success' => false, 'message' => 'Unknown action: ' . $action]);
            exit;
    }
}


$search = $_GET['search'] ?? '';
$role_filter = $_GET['role'] ?? '';

$query = "SELECT id, name, email, role, created_at FROM users WHERE 1=1";
$params = [];
$types = "";

if (!empty($search)) {
    $query .= " AND (name LIKE ? OR email LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}

if (!empty($role_filter)) {
    $query .= " AND role = ?";
    $params[] = $role_filter;
    $types .= "s";
}

$query .= " ORDER BY created_at DESC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - EduLearn LMS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/teacher.css">
    <link rel="stylesheet" href="../assets/css/responsive-modal.css">
</head>
<style>
.form-actions.search-input {
    width: 100%;
    max-width: 280px;
    padding: 0.75rem 1rem;
    border: 2px solid #d1d5db;
    border-radius: 8px;
    font-size: 0.95rem;
    transition: border-color 0.3s ease;
    background-color: #fff;
    color: #333;
}

.search-input:focus {
    border-color: #6366f1;
    outline: none;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
}

.filters-form {
    display: flex;
    flex-wrap: wrap;
    gap: 1.5rem;
    align-items: flex-end;
}

.filters-section {
    padding: 1.5rem 2rem;
    background-color: #f9fafb;
    border-bottom: 1px solid #e5e7eb;
}

.filters-form {
    display: flex;
    flex-wrap: wrap;
    gap: 1.5rem;
    align-items: flex-end;
}
</style>

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
                <a href="manage_user.php" class="nav-item active">
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
                    <h1><i class="fas fa-users"></i> User Management</h1>
                    <p>Manage system users - admins, teachers, and students</p>
                </div>
                <div class="header-right">
                    <button class="btn btn-primary" onclick="openCreateModal()">
                        <i class="fas fa-user-plus"></i> Add New User
                    </button>
                </div>
            </header>
            <div class="container-fluid mb-4">
                <form method="GET" class="row align-items-end g-2">
                    <!-- Search Bar -->
                    <div class="col-md-5">
                        <label for="search" class="form-label fw-semibold text-muted small">Search Users</label>
                        <input type="text" class="form-control" id="search" name="search"
                            placeholder="🔍 Search by name or email..."
                            value="<?php echo htmlspecialchars($search); ?>">
                    </div>

                    <!-- Role Filter -->
                    <div class="col-md-3">
                        <label for="role" class="form-label fw-semibold text-muted small">Filter by Role</label>
                        <select class="form-select" id="role" name="role">
                            <option value="">All Roles</option>
                            <option value="admin" <?php echo $role_filter === 'admin' ? 'selected' : ''; ?>>👑 Admin
                            </option>
                            <option value="teacher" <?php echo $role_filter === 'teacher' ? 'selected' : ''; ?>>👨‍🏫
                                Teacher</option>
                            <option value="student" <?php echo $role_filter === 'student' ? 'selected' : ''; ?>>👨‍🎓
                                Student</option>
                        </select>
                    </div>

                    <!-- Buttons -->
                    <div class="col-md-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary w-50">
                            <i class="fas fa-search"></i> Search
                        </button>
                        <a href="manage_user.php" class="btn btn-outline-secondary w-50">
                            <i class="fas fa-times"></i> Clear
                        </a>
                    </div>
                </form>
            </div>

            <div class="content-card">
                <div class="card-header">
                    <h3><i class="fas fa-list"></i> Users (<?php echo count($users); ?>)</h3>
                    <div style="display: flex; gap: 1rem; align-items: center;">
                        <span style="font-size: 0.9rem; color: #666;">
                            <i class="fas fa-info-circle"></i> Total: <?php echo count($users); ?> users
                        </span>
                    </div>
                </div>
                <div class="card-content">
                    <?php if (empty($users)): ?>
                    <p style="text-align: center; color: #666; padding: 2rem;">
                        No users found matching your criteria.
                    </p>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo $user['id']; ?></td>
                                    <td><?php echo htmlspecialchars($user['name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $user['role']; ?>">
                                            <?php echo ucfirst($user['role']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-icon btn-edit"
                                                onclick="editUser(<?php echo $user['id']; ?>)" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn-icon btn-warning"
                                                onclick="resetPassword(<?php echo $user['id']; ?>)"
                                                title="Reset Password">
                                                <i class="fas fa-key"></i>
                                            </button>
                                            <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                            <button class="btn-icon btn-delete"
                                                onclick="deleteUser(<?php echo $user['id']; ?>)" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                            <?php endif; ?>
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
    <div id="userModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle"><i class="fas fa-user-plus"></i> Add New User</h3>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <form id="userForm" class="form">
                <input type="hidden" id="userId" name="id">
                <input type="hidden" id="formAction" name="action" value="create">

                <div class="form-group">
                    <label for="userName"><i class="fas fa-user"></i> Full Name *</label>
                    <input type="text" id="userName" name="name" required placeholder="Enter full name">
                </div>

                <div class="form-group">
                    <label for="userEmail"><i class="fas fa-envelope"></i> Email Address *</label>
                    <input type="email" id="userEmail" name="email" required placeholder="Enter email address">
                </div>

                <div class="form-group" id="passwordGroup">
                    <label for="userPassword"><i class="fas fa-lock"></i> Password *</label>
                    <input type="password" id="userPassword" name="password" required placeholder="Enter password">
                </div>

                <div class="form-group">
                    <label for="userRole"><i class="fas fa-user-tag"></i> Role *</label>
                    <select id="userRole" name="role" required>
                        <option value="">Select Role</option>
                        <option value="admin">👑 Admin</option>
                        <option value="teacher">👨‍🏫 Teacher</option>
                        <option value="student">👨‍🎓 Student</option>
                    </select>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="submitBtn">Create User</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Custom User Management Styles -->
    <style>
    /* Enhanced Modal Padding */
    .modal-content {
        padding: 0 !important;
    }

    .modal-header {
        padding: 2rem 2.5rem 1.5rem 2.5rem !important;
    }

    .modal-body {
        padding: 0 2.5rem 2.5rem 2.5rem !important;
    }

    .form-group {
        margin-bottom: 2rem !important;
    }

    .form-group:last-child {
        margin-bottom: 1.5rem !important;
    }

    .form-actions {
        margin-top: 2.5rem !important;
        padding-top: 2rem !important;
        border-top: 2px solid #e5e7eb !important;
    }

    /* User role badges */
    .user-role-badge {
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
    }

    .user-role-badge.admin {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        color: white;
    }

    .user-role-badge.teacher {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        color: white;
    }

    .user-role-badge.student {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
    }

    /* Enhanced form styling */
    .form-group label {
        margin-bottom: 0.75rem !important;
        font-size: 0.95rem !important;
        font-weight: 600 !important;
    }

    .form-group input,
    .form-group select {
        padding: 1rem !important;
        font-size: 1rem !important;
        border-radius: 10px !important;
        border: 2px solid #e5e7eb !important;
    }

    .form-group input:focus,
    .form-group select:focus {
        border-color: #667eea !important;
        box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1) !important;
    }

    /* Responsive padding adjustments */
    @media (max-width: 768px) {
        .modal-header {
            padding: 1.5rem 2rem 1rem 2rem !important;
        }

        .modal-body {
            padding: 0 2rem 2rem 2rem !important;
        }

        .form-group {
            margin-bottom: 1.5rem !important;
        }

        .form-actions {
            margin-top: 2rem !important;
            padding-top: 1.5rem !important;
        }
    }

    @media (max-width: 480px) {
        .modal-header {
            padding: 1rem 1.5rem 0.75rem 1.5rem !important;
        }

        .modal-body {
            padding: 0 1.5rem 1.5rem 1.5rem !important;
        }

        .form-group {
            margin-bottom: 1.25rem !important;
        }

        .form-actions {
            margin-top: 1.5rem !important;
            padding-top: 1.25rem !important;
        }
    }
    </style>

    <script src="../assets/js/responsive-modal.js"></script>
    <script>
    // Initialize responsive modal
    let userModal;

    document.addEventListener('DOMContentLoaded', function() {
        userModal = new ResponsiveModal('userModal');
    });

    function openCreateModal() {
        // Reset form and set up for creation
        const form = document.getElementById('userForm');
        if (form) form.reset();

        // Set modal title and action
        document.getElementById('modalTitle').innerHTML = '<i class="fas fa-user-plus"></i> Add New User';
        document.getElementById('formAction').value = 'create';
        document.getElementById('submitBtn').textContent = 'Create User';

        // Show password field for new users
        const passwordGroup = document.getElementById('passwordGroup');
        const passwordField = document.getElementById('userPassword');
        if (passwordGroup && passwordField) {
            passwordGroup.style.display = 'block';
            passwordField.required = true;
        }

        // Clear user ID for new user
        document.getElementById('userId').value = '';

        // Open modal using responsive modal system
        if (userModal) {
            userModal.open();
        }
    }

    function editUser(userId) {
        if (!userId) {
            alert('Invalid user ID');
            return;
        }

        fetch('manage_user.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: 'action=get&id=' + encodeURIComponent(userId)
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success && data.user) {
                    // Set modal title and action
                    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit"></i> Edit User';
                    document.getElementById('formAction').value = 'update';
                    document.getElementById('submitBtn').textContent = 'Update User';

                    // Hide password field for editing
                    const passwordGroup = document.getElementById('passwordGroup');
                    const passwordField = document.getElementById('userPassword');
                    if (passwordGroup && passwordField) {
                        passwordGroup.style.display = 'none';
                        passwordField.required = false;
                    }

                    // Populate form fields
                    document.getElementById('userId').value = data.user.id || '';
                    document.getElementById('userName').value = data.user.name || '';
                    document.getElementById('userEmail').value = data.user.email || '';
                    document.getElementById('userRole').value = data.user.role || '';

                    // Show modal using responsive modal system
                    if (userModal) {
                        userModal.open();
                    }
                } else {
                    alert('Error: ' + (data.message || 'Failed to load user data'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while fetching user data. Please try again.');
            });
    }

    function deleteUser(userId) {
        if (confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
            fetch('manage_user.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: 'action=delete&id=' + userId
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
                    alert('An error occurred while deleting the user');
                });
        }
    }

    function resetPassword(userId) {
        const newPassword = prompt('Enter new password (minimum 6 characters):');
        if (newPassword && newPassword.length >= 6) {
            if (confirm('Are you sure you want to reset this user\'s password?')) {
                fetch('manage_user.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: 'action=reset_password&id=' + userId + '&new_password=' + encodeURIComponent(
                            newPassword)
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert(data.message);
                        } else {
                            alert('Error: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while resetting the password');
                    });
            }
        } else if (newPassword !== null) {
            alert('Password must be at least 6 characters long');
        }
    }

    function closeModal() {
        if (userModal) {
            userModal.close();
        }
    }

    // Enhanced form submission
    document.getElementById('userForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        const submitBtn = document.getElementById('submitBtn');
        const originalText = submitBtn.textContent;

        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
        submitBtn.disabled = true;

        fetch('manage_user.php', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    alert('✓ ' + data.message);
                    closeModal();
                    setTimeout(() => location.reload(), 1000);
                } else {
                    alert('✗ Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('✗ An error occurred while processing the request');
            })
            .finally(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
    });
    </script>
</body>

</html>