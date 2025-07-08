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
                echo json_encode(['success' => false, 'message' => 'Subject name is required']);
                exit;
            }

            $query = "SELECT id FROM subjects WHERE name = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("s", $name);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                echo json_encode(['success' => false, 'message' => 'Subject name already exists']);
                exit;
            }

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

            $query = "SELECT id FROM subjects WHERE name = ? AND id != ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("si", $name, $id);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                echo json_encode(['success' => false, 'message' => 'Subject name already exists']);
                exit;
            }

            $query = "UPDATE subjects SET name = ?, description = ? WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ssi", $name, $description, $id);

            echo json_encode([
                'success' => $stmt->execute(),
                'message' => $stmt->execute() ? 'Subject updated successfully' : 'Failed to update subject'
            ]);
            exit;

        case 'delete':
            $id = intval($_POST['id'] ?? 0);
            if ($id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid subject ID']);
                exit;
            }

            $query = "SELECT COUNT(*) as count FROM teacher_subject_class WHERE subject_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $teacher_count = $stmt->get_result()->fetch_assoc()['count'];

            if ($teacher_count > 0) {
                echo json_encode(['success' => false, 'message' => 'Cannot delete subject with assigned teachers']);
                exit;
            }

            $query = "DELETE FROM subjects WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $id);

            echo json_encode([
                'success' => $stmt->execute(),
                'message' => $stmt->execute() ? 'Subject deleted successfully' : 'Failed to delete subject'
            ]);
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
                <!-- Enhanced Statistics Cards -->
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


                <!-- Enhanced Search Section -->
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


                <!-- Enhanced Subjects Table -->
                <div class="content-card">
                    <div class="card-header">
                        <h3><i class="fas fa-list"></i> Subjects Management</h3>
                        <p>Manage academic subjects and their assignments (<?php echo count($subjects); ?> total)</p>
                        <div class="header-actions">
                            <div class="search-box">
                                <i class="fas fa-search"></i>
                                <input type="text" id="searchSubjects" placeholder="Quick search..." onkeyup="filterSubjects()">
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
                                                        <span class="subject-name"><?php echo htmlspecialchars($subject['name']); ?></span>
                                                    </div>
                                                </td>
                                                <td class="subject-description">
                                                    <?php if (!empty($subject['description'])): ?>
                                                        <span class="description-text"><?php echo htmlspecialchars($subject['description']); ?></span>
                                                    <?php else: ?>
                                                        <span class="no-description">No description</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="teacher-count-badge <?php echo $subject['teacher_count'] > 0 ? 'active' : 'inactive'; ?>">
                                                        <?php echo $subject['teacher_count']; ?> teacher<?php echo $subject['teacher_count'] != 1 ? 's' : ''; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="created-date"><?php echo date('M j, Y', strtotime($subject['created_at'])); ?></span>
                                                </td>
                                                <td>
                                                    <div class="action-buttons">
                                                        <button type="button" onclick="viewSubjectDetails(<?php echo $subject['id']; ?>)"
                                                            class="btn btn-sm btn-info" title="View Details">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        <button type="button" onclick="editSubject(<?php echo $subject['id']; ?>)"
                                                            class="btn btn-sm btn-secondary" title="Edit Subject">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button type="button" onclick="deleteSubject(<?php echo $subject['id']; ?>)"
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

    <!-- Enhanced Subject Modal -->
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

    <!-- Enhanced CSS Styles -->
    <style>
        /* Enhanced Subject Info */
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

        /* Enhanced Description */
        .description-text {
            color: #374151;
            line-height: 1.4;
        }

        .no-description {
            color: #9ca3af;
            font-style: italic;
        }

        /* Enhanced Teacher Count Badge */
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

        /* Enhanced Action Buttons */
        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        /* Enhanced Table Styles */
        .subject-row:hover {
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

        /* Enhanced Search Form */
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

        /* Enhanced Form Styles */
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

        /* Subject Preview */
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

            .subject-info {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }
        }
    </style>

    <!-- Enhanced JavaScript -->
    <script>
        // Enhanced modal functions
        function openCreateModal() {
            document.getElementById('modalTitle').innerHTML = '<i class="fas fa-book-open"></i> Add New Subject';
            document.getElementById('formAction').value = 'create';
            document.getElementById('submitBtn').innerHTML = '<i class="fas fa-book-open"></i> Create Subject';
            document.getElementById('subjectForm').reset();
            document.getElementById('subjectPreview').style.display = 'none';
            document.getElementById('subjectModal').style.display = 'block';
            document.getElementById('subjectName').focus();
        }

        function closeModal() {
            document.getElementById('subjectModal').style.display = 'none';
        }

        // Enhanced edit function
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
                        document.getElementById('subjectModal').style.display = 'block';
                        document.getElementById('subjectName').focus();
                    } else {
                        showNotification('Error: ' + data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('An error occurred while fetching subject data', 'error');
                });
        }

        // Enhanced delete function
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

        // Filter subjects function
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

        // View subject details function
        function viewSubjectDetails(subjectId) {
            alert('Subject details view coming soon!');
        }

        // Export subjects function
        function exportSubjects() {
            alert('Export feature coming soon!');
        }

        // Bulk import modal function
        function showBulkImportModal() {
            alert('Bulk import feature coming soon!');
        }

        // Toggle advanced search function
        function toggleAdvancedSearch() {
            alert('Advanced search coming soon!');
        }

        // Show notification function
        function showNotification(message, type) {
            if (type === 'success') {
                alert('✓ ' + message);
            } else {
                alert('✗ ' + message);
            }
        }

        // Update subject preview
        function updateSubjectPreview() {
            const nameInput = document.getElementById('subjectName');
            const descriptionInput = document.getElementById('subjectDescription');
            const preview = document.getElementById('subjectPreview');

            if (nameInput.value.trim()) {
                document.getElementById('previewName').textContent = nameInput.value.trim();
                document.getElementById('previewDescription').textContent = descriptionInput.value.trim() || 'No description';
                preview.style.display = 'block';
            } else {
                preview.style.display = 'none';
            }
        }

        // Enhanced form submission
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

        // Event listeners
        document.addEventListener('DOMContentLoaded', function() {
            // Add change listeners for subject preview
            document.getElementById('subjectName').addEventListener('input', updateSubjectPreview);
            document.getElementById('subjectDescription').addEventListener('input', updateSubjectPreview);
        });

        // Modal click outside to close
        window.onclick = function(event) {
            const modal = document.getElementById('subjectModal');
            if (event.target === modal) {
                closeModal();
            }
        }
    </script>
</body>

</html>