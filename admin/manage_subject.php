<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

function logAdminAction($conn, $user_id, $action, $details)
{
    try {
        $query = "INSERT INTO admin_logs (user_id, action, details, created_at) VALUES (?, ?, ?, NOW())";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("iss", $user_id, $action, $details);
        $stmt->execute();
    } catch (Exception $e) {

        try {
            $create_table = "CREATE TABLE IF NOT EXISTS admin_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                action VARCHAR(100) NOT NULL,
                details TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_user_id (user_id),
                INDEX idx_created_at (created_at)
            )";
            $conn->query($create_table);


            $stmt = $conn->prepare($query);
            $stmt->bind_param("iss", $user_id, $action, $details);
            $stmt->execute();
        } catch (Exception $e2) {
        }
    }
}

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

            if (strlen($name) < 2) {
                echo json_encode(['success' => false, 'message' => 'Subject name must be at least 2 characters long']);
                exit;
            }

            if (strlen($name) > 100) {
                echo json_encode(['success' => false, 'message' => 'Subject name cannot exceed 100 characters']);
                exit;
            }

            if (!preg_match('/^[a-zA-Z0-9\s\-&()]+$/', $name)) {
                echo json_encode(['success' => false, 'message' => 'Subject name contains invalid characters']);
                exit;
            }

            if (!empty($description) && strlen($description) > 500) {
                echo json_encode(['success' => false, 'message' => 'Description cannot exceed 500 characters']);
                exit;
            }


            $query = "SELECT id FROM subjects WHERE LOWER(name) = LOWER(?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("s", $name);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                echo json_encode(['success' => false, 'message' => 'A subject with this name already exists']);
                exit;
            }

            $query = "INSERT INTO subjects (name, description) VALUES (?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ss", $name, $description);

            if ($stmt->execute()) {
                $subject_id = $conn->insert_id;
                logAdminAction($conn, $_SESSION['user_id'], 'CREATE_SUBJECT', "Created subject: $name (ID: $subject_id)");
                echo json_encode(['success' => true, 'message' => 'Subject created successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to create subject: ' . $conn->error]);
            }
            exit;

        case 'update':
            $id = intval($_POST['id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');

            if ($id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid subject ID']);
                exit;
            }

            if (empty($name)) {
                echo json_encode(['success' => false, 'message' => 'Subject name is required']);
                exit;
            }

            if (strlen($name) < 2) {
                echo json_encode(['success' => false, 'message' => 'Subject name must be at least 2 characters long']);
                exit;
            }

            if (strlen($name) > 100) {
                echo json_encode(['success' => false, 'message' => 'Subject name cannot exceed 100 characters']);
                exit;
            }

            if (!preg_match('/^[a-zA-Z0-9\s\-&()]+$/', $name)) {
                echo json_encode(['success' => false, 'message' => 'Subject name contains invalid characters']);
                exit;
            }

            if (!empty($description) && strlen($description) > 500) {
                echo json_encode(['success' => false, 'message' => 'Description cannot exceed 500 characters']);
                exit;
            }

            $query = "SELECT id FROM subjects WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            if ($stmt->get_result()->num_rows === 0) {
                echo json_encode(['success' => false, 'message' => 'Subject not found']);
                exit;
            }


            $query = "SELECT id FROM subjects WHERE LOWER(name) = LOWER(?) AND id != ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("si", $name, $id);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                echo json_encode(['success' => false, 'message' => 'A subject with this name already exists']);
                exit;
            }

            $query = "UPDATE subjects SET name = ?, description = ? WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ssi", $name, $description, $id);

            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    logAdminAction($conn, $_SESSION['user_id'], 'UPDATE_SUBJECT', "Updated subject: $name (ID: $id)");
                    echo json_encode(['success' => true, 'message' => 'Subject updated successfully']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'No changes were made']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update subject: ' . $conn->error]);
            }
            exit;

        case 'delete':
            $id = intval($_POST['id'] ?? 0);
            if ($id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid subject ID']);
                exit;
            }


            $query = "SELECT name FROM subjects WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows === 0) {
                echo json_encode(['success' => false, 'message' => 'Subject not found']);
                exit;
            }
            $subject_name = $result->fetch_assoc()['name'];


            $query = "SELECT COUNT(*) as count FROM teacher_subject_class WHERE subject_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $teacher_count = $stmt->get_result()->fetch_assoc()['count'];


            $quiz_count = 0;
            try {
                $query = "SELECT COUNT(*) as count FROM quizzes WHERE subject_id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $quiz_count = $stmt->get_result()->fetch_assoc()['count'];
            } catch (Exception $e) {
            }

            if ($teacher_count > 0 || $quiz_count > 0) {
                $dependencies = [];
                if ($teacher_count > 0) $dependencies[] = "$teacher_count teacher assignment(s)";
                if ($quiz_count > 0) $dependencies[] = "$quiz_count quiz(es)";

                echo json_encode([
                    'success' => false,
                    'message' => "Cannot delete subject '$subject_name'. It has " . implode(', ', $dependencies) . ". Please remove these dependencies first."
                ]);
                exit;
            }

            $query = "DELETE FROM subjects WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $id);

            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    logAdminAction($conn, $_SESSION['user_id'], 'DELETE_SUBJECT', "Deleted subject: $subject_name (ID: $id)");
                    echo json_encode(['success' => true, 'message' => "Subject '$subject_name' deleted successfully"]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Subject not found']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete subject: ' . $conn->error]);
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

            echo json_encode([
                'success' => (bool)$subject,
                'subject' => $subject,
                'message' => $subject ? '' : 'Subject not found'
            ]);
            exit;
    }
}

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

$query .= " ORDER BY s.id DESC";

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


        <main class="main-content">
            <header class="content-header">
                <div class="header-left">
                    <h1><i class="fas fa-book"></i> Subject Management</h1>
                    <p>Create and manage academic subjects efficiently</p>
                </div>
                <div class="header-right">
                    <button class="btn btn-primary" onclick="openCreateModal()">
                        <i class="fas fa-book-open"></i> Add New Subject
                    </button>
                    <button class="btn btn-secondary" onclick="exportSubjects()">
                        <i class="fas fa-download"></i> Export Data
                    </button>
                    <button class="btn btn-info" onclick="showBulkImportModal()">
                        <i class="fas fa-upload"></i> Bulk Import
                    </button>
                </div>
            </header>

            <div class="content-body">

                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                            <i class="fas fa-book"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo count($subjects); ?></h3>
                            <p>Total Subjects</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                            <i class="fas fa-chalkboard-teacher"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo array_sum(array_column($subjects, 'teacher_count')); ?></h3>
                            <p>Teacher Assignments</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                            <i class="fas fa-graduation-cap"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo count(array_filter($subjects, function ($s) {
                                    return !empty($s['description']);
                                })); ?></h3>
                            <p>With Descriptions</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo count(array_filter($subjects, function ($s) {
                                    return $s['teacher_count'] > 0;
                                })); ?></h3>
                            <p>Active Subjects</p>
                        </div>
                    </div>
                </div>



                <div class="content-card">
                    <div class="card-header">
                        <h3><i class="fas fa-search"></i> Search & Filter</h3>
                        <p>Find subjects by name or description</p>
                    </div>
                    <div class="card-body">
                        <form method="GET" class="enhanced-search-form">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="search_input">
                                        <i class="fas fa-search"></i> Search Subjects
                                    </label>
                                    <div class="search-input-wrapper">
                                        <input type="text" name="search" id="search_input"
                                            placeholder="Search by name or description..."
                                            value="<?php echo htmlspecialchars($search); ?>">
                                        <i class="fas fa-search search-icon"></i>
                                    </div>
                                </div>
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Search
                                </button>
                                <a href="manage_subject.php" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Clear
                                </a>
                                <button type="button" onclick="toggleAdvancedSearch()" class="btn btn-info">
                                    <i class="fas fa-cog"></i> Advanced
                                </button>
                            </div>
                        </form>
                    </div>
                </div>



                <div class="content-card">
                    <div class="card-header">
                        <h3><i class="fas fa-list"></i> Subjects Management</h3>
                        <p>Manage academic subjects and their assignments (<?php echo count($subjects); ?> total)</p>
                        <div class="header-actions">
                            <div class="search-box">
                                <i class="fas fa-search"></i>
                                <input type="text" id="searchSubjects" placeholder="Quick search..."
                                    onkeyup="filterSubjects()">
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (empty($subjects)): ?>
                        <div class="empty-state">
                            <i class="fas fa-book"></i>
                            <h3>No Subjects Found</h3>
                            <p>No subjects found matching your criteria. Create your first subject to get started.</p>
                            <button onclick="openCreateModal()" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Create First Subject
                            </button>
                        </div>
                        <?php else: ?>
                        <div class="table-container">
                            <table class="data-table" id="subjectsTable">
                                <thead>
                                    <tr>
                                        <th><i class="fas fa-hashtag"></i> ID</th>
                                        <th><i class="fas fa-book"></i> Subject Name</th>
                                        <th><i class="fas fa-info-circle"></i> Description</th>
                                        <th><i class="fas fa-chalkboard-teacher"></i> Teachers</th>
                                        <th><i class="fas fa-calendar"></i> Created</th>
                                        <th><i class="fas fa-cogs"></i> Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($subjects as $subject): ?>
                                    <tr class="subject-row">
                                        <td>
                                            <span class="subject-id">#<?php echo $subject['id']; ?></span>
                                        </td>
                                        <td>
                                            <div class="subject-info">
                                                <div class="subject-icon">
                                                    <i class="fas fa-book"></i>
                                                </div>
                                                <span
                                                    class="subject-name"><?php echo htmlspecialchars($subject['name']); ?></span>
                                            </div>
                                        </td>
                                        <td class="subject-description">
                                            <?php if (!empty($subject['description'])): ?>
                                            <span
                                                class="description-text"><?php echo htmlspecialchars($subject['description']); ?></span>
                                            <?php else: ?>
                                            <span class="no-description">No description</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span
                                                class="teacher-count-badge <?php echo $subject['teacher_count'] > 0 ? 'active' : 'inactive'; ?>">
                                                <?php echo $subject['teacher_count']; ?>
                                                teacher<?php echo $subject['teacher_count'] != 1 ? 's' : ''; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span
                                                class="created-date"><?php echo date('M j, Y', strtotime($subject['created_at'])); ?></span>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <button type="button"
                                                    onclick="viewSubjectDetails(<?php echo $subject['id']; ?>)"
                                                    class="btn btn-sm btn-info" title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button type="button"
                                                    onclick="editSubject(<?php echo $subject['id']; ?>)"
                                                    class="btn btn-sm btn-secondary" title="Edit Subject">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button type="button"
                                                    onclick="deleteSubject(<?php echo $subject['id']; ?>)"
                                                    class="btn btn-sm btn-danger" title="Delete Subject">
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


    <div id="subjectModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle"><i class="fas fa-book-open"></i> Add New Subject</h3>
                <p>Create a new academic subject for your institution</p>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="subjectForm" class="enhanced-form">
                    <input type="hidden" id="subjectId" name="id">
                    <input type="hidden" id="formAction" name="action" value="create">

                    <div class="form-row">
                        <div class="form-group">
                            <label for="subjectName">
                                <i class="fas fa-book"></i> Subject Name *
                            </label>
                            <input type="text" id="subjectName" name="name" required
                                placeholder="Enter subject name (e.g., Mathematics, Physics)">
                        </div>

                        <div class="form-group">
                            <label for="subjectDescription">
                                <i class="fas fa-info-circle"></i> Description
                            </label>
                            <textarea id="subjectDescription" name="description" rows="4"
                                placeholder="Optional subject description (e.g., Advanced mathematics covering algebra, calculus, and statistics)"></textarea>
                        </div>
                    </div>

                    <div class="subject-preview" id="subjectPreview" style="display: none;">
                        <h4><i class="fas fa-eye"></i> Subject Preview</h4>
                        <div class="preview-content">
                            <div class="preview-item">
                                <strong>Name:</strong> <span id="previewName">-</span>
                            </div>
                            <div class="preview-item">
                                <strong>Description:</strong> <span id="previewDescription">-</span>
                            </div>
                        </div>
                    </div>

                    <div class="modal-actions">
                        <button type="button" onclick="closeModal()" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                        <button type="submit" id="submitBtn" class="btn btn-primary">
                            <i class="fas fa-book-open"></i> Create Subject
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    <style>
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


    .form-group label {
        margin-bottom: 0.75rem !important;
        font-size: 0.95rem !important;
        font-weight: 600 !important;
        color: #374151 !important;
    }

    .form-group input,
    .form-group select,
    .form-group textarea {
        padding: 1rem !important;
        font-size: 1rem !important;
        border-radius: 10px !important;
        border: 2px solid #e5e7eb !important;
        width: 100% !important;
        box-sizing: border-box !important;
    }

    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
        border-color: #667eea !important;
        box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1) !important;
        outline: none !important;
    }

    .form-group textarea {
        resize: vertical !important;
        min-height: 120px !important;
    }


    .form-actions .btn {
        padding: 1rem 2rem !important;
        font-size: 1rem !important;
        font-weight: 600 !important;
        border-radius: 10px !important;
        min-width: 140px !important;
    }


    .subject-preview {
        margin-top: 2rem !important;
        padding: 1.5rem !important;
        border-radius: 12px !important;
    }


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

        .form-actions .btn {
            padding: 0.875rem 1.5rem !important;
            min-width: 120px !important;
        }

        .subject-preview {
            margin-top: 1.5rem !important;
            padding: 1.25rem !important;
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
            flex-direction: column !important;
            gap: 1rem !important;
        }

        .form-actions .btn {
            width: 100% !important;
            padding: 1rem !important;
            min-width: auto !important;
        }

        .subject-preview {
            margin-top: 1.25rem !important;
            padding: 1rem !important;
        }
    }


    .subject-info {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .subject-icon {
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

    .subject-name {
        font-weight: 600;
        color: #374151;
    }

    .subject-id {
        font-family: 'Courier New', monospace;
        background: #f3f4f6;
        padding: 0.25rem 0.5rem;
        border-radius: 4px;
        font-weight: 600;
        color: #374151;
    }


    .description-text {
        color: #374151;
        line-height: 1.4;
    }

    .no-description {
        color: #9ca3af;
        font-style: italic;
    }


    .teacher-count-badge {
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-size: 0.875rem;
        font-weight: 600;
        display: inline-block;
    }

    .teacher-count-badge.active {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
    }

    .teacher-count-badge.inactive {
        background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
        color: #6b7280;
    }

    .created-date {
        color: #6b7280;
        font-size: 0.875rem;
    }

    .action-buttons {
        display: flex;
        gap: 0.5rem;
    }

    .subject-row:hover {
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

    .enhanced-search-form {
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        padding: 1.5rem;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
    }

    .search-input-wrapper {
        position: relative;
    }

    .search-input-wrapper input {
        padding-left: 2.5rem;
        width: 100%;
    }

    .search-icon {
        position: absolute;
        left: 1rem;
        top: 50%;
        transform: translateY(-50%);
        color: #6b7280;
        pointer-events: none;
    }

    .enhanced-form {
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

    .subject-preview {
        background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%);
        border: 1px solid #a5b4fc;
        border-radius: 8px;
        padding: 1rem;
        margin-top: 1rem;
    }

    .subject-preview h4 {
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

        .subject-info {
            flex-direction: column;
            align-items: flex-start;
            gap: 0.5rem;
        }
    }
    </style>


    <script src="../assets/js/responsive-modal.js"></script>
    <script>
    let subjectModal;

    document.addEventListener('DOMContentLoaded', function() {
        subjectModal = new ResponsiveModal('subjectModal');


        document.getElementById('subjectName').addEventListener('input', updateSubjectPreview);
        document.getElementById('subjectDescription').addEventListener('input', updateSubjectPreview);
    });


    function openCreateModal() {

        const form = document.getElementById('subjectForm');
        if (form) form.reset();

        const modalTitle = document.getElementById('modalTitle');
        if (modalTitle) modalTitle.innerHTML = '<i class="fas fa-book-open"></i> Add New Subject';

        const formAction = document.getElementById('formAction');
        if (formAction) formAction.value = 'create';

        const submitBtn = document.getElementById('submitBtn');
        if (submitBtn) submitBtn.innerHTML = '<i class="fas fa-book-open"></i> Create Subject';


        const subjectId = document.getElementById('subjectId');
        if (subjectId) subjectId.value = '';


        const preview = document.getElementById('subjectPreview');
        if (preview) preview.style.display = 'none';

        if (subjectModal) {
            subjectModal.open();
        }
    }

    function closeModal() {
        if (subjectModal) {
            subjectModal.close();
        }
    }


    function editSubject(subjectId) {
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
                    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit"></i> Edit Subject';
                    document.getElementById('formAction').value = 'update';
                    document.getElementById('submitBtn').innerHTML = '<i class="fas fa-save"></i> Update Subject';

                    document.getElementById('subjectId').value = data.subject.id;
                    document.getElementById('subjectName').value = data.subject.name;
                    document.getElementById('subjectDescription').value = data.subject.description || '';

                    updateSubjectPreview();
                    if (subjectModal) {
                        subjectModal.open();
                    }
                } else {
                    showNotification('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('An error occurred while fetching subject data', 'error');
            });
    }


    function deleteSubject(subjectId) {
        if (confirm('Are you sure you want to delete this subject? This action cannot be undone.')) {
            const button = event.target.closest('button');
            const originalContent = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            button.disabled = true;

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
                        showNotification(data.message, 'success');
                        location.reload();
                    } else {
                        showNotification('Error: ' + data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('An error occurred while deleting the subject', 'error');
                })
                .finally(() => {
                    button.innerHTML = originalContent;
                    button.disabled = false;
                });
        }
    }


    function filterSubjects() {
        const searchTerm = document.getElementById('searchSubjects').value.toLowerCase();
        const rows = document.querySelectorAll('.subject-row');

        rows.forEach(row => {
            const subjectName = row.querySelector('.subject-name').textContent.toLowerCase();
            const description = row.querySelector('.subject-description').textContent.toLowerCase();

            const matches = subjectName.includes(searchTerm) || description.includes(searchTerm);

            if (matches) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    function viewSubjectDetails(subjectId) {
        alert('Subject details view coming soon!');
    }


    function exportSubjects() {
        alert('Export feature coming soon!');
    }


    function showBulkImportModal() {
        alert('Bulk import feature coming soon!');
    }

    function toggleAdvancedSearch() {
        alert('Advanced search coming soon!');
    }

    function showNotification(message, type) {
        if (type === 'success') {
            alert('✓ ' + message);
        } else {
            alert('✗ ' + message);
        }
    }

    function updateSubjectPreview() {
        const nameInput = document.getElementById('subjectName');
        const descriptionInput = document.getElementById('subjectDescription');
        const preview = document.getElementById('subjectPreview');

        if (nameInput.value.trim()) {
            document.getElementById('previewName').textContent = nameInput.value.trim();
            document.getElementById('previewDescription').textContent = descriptionInput.value.trim() ||
                'No description';
            preview.style.display = 'block';
        } else {
            preview.style.display = 'none';
        }
    }

    document.getElementById('subjectForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        const submitBtn = document.getElementById('submitBtn');
        const originalText = submitBtn.innerHTML;

        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
        submitBtn.disabled = true;

        fetch('manage_subject.php', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(response => {
                console.log('Response status:', response.status);
                if (!response.ok) {
                    throw new Error('Network response was not ok: ' + response.status);
                }
                return response.text().then(text => {
                    console.log('Response text:', text);
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('JSON parse error:', e);
                        throw new Error('Invalid JSON response: ' + text);
                    }
                });
            })
            .then(data => {
                if (data.success) {
                    showNotification(data.message, 'success');
                    closeModal();
                    setTimeout(() => location.reload(), 1000);
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