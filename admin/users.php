<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$users = [];
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';

if ($searchTerm !== '') {
    $stmt = $conn->prepare("SELECT id, name, email, role, created_at FROM users 
                            WHERE name LIKE CONCAT('%', ?, '%') OR email LIKE CONCAT('%', ?, '%')
                            ORDER BY created_at DESC");
    $stmt->bind_param("ss", $searchTerm, $searchTerm);
} else {
    $stmt = $conn->prepare("SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC");
}

$stmt->execute();
$result = $stmt->get_result();
$users = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Manage Users - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/teacher.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>
    <div class="dashboard-container">

        <aside class="sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-graduation-cap"></i> EduLearn</h2>
                <p>Admin Panel</p>
            </div>

            <div class="sidebar-nav">
                <a href="dashboard.php" class="nav-item active">
                    <i class="fas fa-arrow-left"></i>
                    <span>Back to Dashboard</span>
                </a>
            </div>

            <div class="sidebar-footer">
                <a href="../logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
                </a>
            </div>
        </aside>


        <main class="main-content">
            <header class="content-header">
                <div class="header-left">
                    <h1><i class="fas fa-users"></i> Manage Users</h1>
                    <p>View, search, and manage all platform users.</p>
                </div>
                <div class="header-right">
                    <form method="GET" class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" name="search" id="searchInput" value="<?= htmlspecialchars($searchTerm) ?>"
                            placeholder="Search users...">
                    </form>
                    <div class="user-info">
                        <i class="fas fa-user-circle"></i>
                        <span><?= htmlspecialchars($_SESSION['user_name']) ?></span>
                    </div>
                </div>
            </header>

            <div class="content-card" style="margin-top: 2rem;">
                <div class="card-header">
                    <h3>User List</h3>
                    <a href="manage_user.php?action=add" class="btn btn-primary">
                        <i class="fas fa-user-plus"></i> Add New User
                    </a>
                </div>
                <div class="card-content">
                    <?php if (empty($users)): ?>
                    <p>No users found.</p>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Joined</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?= htmlspecialchars($user['name']) ?></td>
                                    <td><?= htmlspecialchars($user['email']) ?></td>
                                    <td>
                                        <span class="badge badge-<?= $user['role'] ?>">
                                            <?= ucfirst($user['role']) ?>
                                        </span>
                                    </td>
                                    <td><?= date('M j, Y', strtotime($user['created_at'])) ?></td>
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

    <style>
    .badge-admin {
        background-color: #4f46e5;
        color: white;
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 0.75rem;
    }

    .badge-teacher {
        background-color: #2563eb;
        color: white;
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 0.75rem;
    }

    .badge-student {
        background-color: #10b981;
        color: white;
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 0.75rem;
    }

    .search-box {
        position: relative;
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
        width: 250px;
        padding-top: 0.6rem;
        padding-bottom: 0.6rem;
    }

    .search-box input:focus {
        border-color: #667eea;
        outline: none;
    }

    .card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    </style>
</body>

</html>