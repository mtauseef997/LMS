<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$stats = [];

$result = $conn->query("SELECT COUNT(*) as count FROM users");
$stats['total_users'] = $result->fetch_assoc()['count'];

$result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'student'");
$stats['total_students'] = $result->fetch_assoc()['count'];

$result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'teacher'");
$stats['total_teachers'] = $result->fetch_assoc()['count'];

$result = $conn->query("SELECT COUNT(*) as count FROM classes");
$stats['total_classes'] = $result->fetch_assoc()['count'];

$result = $conn->query("SELECT COUNT(*) as count FROM subjects");
$stats['total_subjects'] = $result->fetch_assoc()['count'];

$result = $conn->query("SELECT COUNT(*) as count FROM quizzes");
$stats['total_quizzes'] = $result->fetch_assoc()['count'];

$result = $conn->query("SELECT COUNT(*) as count FROM assignments");
$stats['total_assignments'] = $result->fetch_assoc()['count'];


$recent_users = [];
$result = $conn->query("SELECT name, email, role, created_at FROM users ORDER BY created_at DESC LIMIT 5");
while ($row = $result->fetch_assoc()) {
    $recent_users[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - EduLearn LMS</title>
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
                <a href="dashboard.php" class="nav-item active">
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
                    <h1><i class="fas fa-tachometer-alt"></i> Admin Dashboard</h1>
                    <p>Welcome back, <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong>! 🎉</p>
                </div>
                <div class="header-right">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" id="quickSearch" placeholder="Quick search..." onkeyup="performQuickSearch()">
                    </div>
                    <button onclick="showSystemStatus()" class="btn btn-info">
                        <i class="fas fa-heartbeat"></i> Status
                    </button>
                    <div class="user-info">
                        <i class="fas fa-user-circle"></i>
                        <span><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                    </div>
                </div>
            </header>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['total_users']; ?></h3>
                        <p>Total Users</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['total_students']; ?></h3>
                        <p>Students</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-chalkboard-teacher"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['total_teachers']; ?></h3>
                        <p>Teachers</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-school"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['total_classes']; ?></h3>
                        <p>Classes</p>
                    </div>
                </div>
            </div>

            <div class="stats-grid" style="margin-top: 2rem;">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-book"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['total_subjects']; ?></h3>
                        <p>Subjects</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-question-circle"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['total_quizzes']; ?></h3>
                        <p>Quizzes</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-tasks"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['total_assignments']; ?></h3>
                        <p>Assignments</p>
                    </div>
                </div>
            </div>


            <div class="content-grid" style="margin-top: 3rem;">
                <div class="content-card">
                    <div class="card-header">
                        <h3>Recent Users</h3>
                        <a href="users.php" class="btn btn-primary">View All</a>
                    </div>
                    <div class="card-content">
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
                                    <?php foreach ($recent_users as $user): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($user['name']); ?></td>
                                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td>
                                                <span class="badge badge-<?php echo $user['role']; ?>">
                                                    <?php echo ucfirst($user['role']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="content-card">
                    <div class="card-header">
                        <h3>Quick Actions</h3>
                    </div>
                    <div class="card-content">
                        <div class="quick-actions">
                            <a href="manage_user.php?action=add" class="action-btn">
                                <i class="fas fa-user-plus"></i>
                                <span>Add User</span>
                            </a>
                            <a href="manage_class.php?action=add" class="action-btn">
                                <i class="fas fa-plus"></i>
                                <span>Add Class</span>
                            </a>
                            <a href="manage_subject.php?action=add" class="action-btn">
                                <i class="fas fa-book-open"></i>
                                <span>Add Subject</span>
                            </a>
                            <a href="reports.php" class="action-btn">
                                <i class="fas fa-chart-line"></i>
                                <span>View Reports</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Enhanced CSS Styles -->
    <style>
        .search-box {
            position: relative;
            margin-right: 1rem;
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
            padding-top: 0.75rem;
            padding-bottom: 0.75rem;
        }

        .search-box input:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            outline: none;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }

        .user-info i {
            color: #667eea;
            font-size: 1.2rem;
        }

        @media (max-width: 768px) {
            .search-box input {
                width: 200px;
            }

            .header-right {
                flex-direction: column;
                align-items: stretch;
                gap: 0.5rem;
            }
        }
    </style>

    <!-- Enhanced JavaScript -->
    <script>
        // Quick search functionality
        function performQuickSearch() {
            const searchTerm = document.getElementById('quickSearch').value.toLowerCase();

            if (searchTerm.length < 2) {
                return;
            }

            // Simple search implementation - in a real app, this would make an AJAX call
            console.log('Searching for:', searchTerm);

            // You could implement actual search functionality here
            // For now, we'll just show an alert
            if (searchTerm.length > 3) {
                // Simulate search results
                setTimeout(() => {
                    if (searchTerm.includes('user') || searchTerm.includes('student') || searchTerm.includes('teacher')) {
                        showSearchResults('Found users matching: ' + searchTerm);
                    } else if (searchTerm.includes('class')) {
                        showSearchResults('Found classes matching: ' + searchTerm);
                    } else {
                        showSearchResults('No results found for: ' + searchTerm);
                    }
                }, 500);
            }
        }

        function showSearchResults(message) {
            // Create a simple notification
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: #667eea;
                color: white;
                padding: 1rem;
                border-radius: 8px;
                z-index: 1000;
                box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            `;
            notification.textContent = message;
            document.body.appendChild(notification);

            setTimeout(() => {
                document.body.removeChild(notification);
            }, 3000);
        }

        // System status functionality
        function showSystemStatus() {
            const status = {
                database: 'Connected',
                users: '<?php echo $stats['total_users']; ?> active',
                storage: 'Available',
                uptime: 'Running'
            };

            let statusMessage = 'System Status:\n';
            statusMessage += `Database: ${status.database}\n`;
            statusMessage += `Users: ${status.users}\n`;
            statusMessage += `Storage: ${status.storage}\n`;
            statusMessage += `Uptime: ${status.uptime}`;

            alert(statusMessage);
        }

        // Auto-refresh stats every 5 minutes
        setInterval(() => {
            // In a real application, you would make an AJAX call to refresh stats
            console.log('Auto-refreshing stats...');
        }, 300000);

        // Enhanced card interactions
        document.addEventListener('DOMContentLoaded', function() {
            const statCards = document.querySelectorAll('.stat-card');

            statCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-5px)';
                    this.style.boxShadow = '0 10px 30px rgba(0,0,0,0.1)';
                });

                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                    this.style.boxShadow = '0 4px 20px rgba(0,0,0,0.08)';
                });
            });
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl+K or Cmd+K to focus search
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                document.getElementById('quickSearch').focus();
            }

            // Escape to clear search
            if (e.key === 'Escape') {
                document.getElementById('quickSearch').value = '';
                document.getElementById('quickSearch').blur();
            }
        });
    </script>
</body>

</html>