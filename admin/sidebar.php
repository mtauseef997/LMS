<aside class="sidebar">
    <div class="sidebar-header">
        <h2>EduLearn</h2>
        <p>Admin Panel</p>
    </div>
    <nav class="sidebar-nav">
        <a href="dashboard.php"
            class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>">
            <i class="fas fa-tachometer-alt"></i>
            <span>Dashboard</span>
        </a>
        <a href="manage_user.php"
            class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'manage_user.php' ? 'active' : '' ?>">
            <i class="fas fa-users"></i>
            <span>Manage Users</span>
        </a>
        <a href="manage_class.php"
            class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'manage_class.php' ? 'active' : '' ?>">
            <i class="fas fa-school"></i>
            <span>Manage Classes</span>
        </a>
        <a href="manage_subject.php"
            class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'manage_subject.php' ? 'active' : '' ?>">
            <i class="fas fa-book"></i>
            <span>Manage Subjects</span>
        </a>
        <a href="assign_teacher.php"
            class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'assign_teacher.php' ? 'active' : '' ?>">
            <i class="fas fa-chalkboard-teacher"></i>
            <span>Assign Teachers</span>
        </a>
        <a href="enroll_student.php"
            class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'enroll_student.php' ? 'active' : '' ?>">
            <i class="fas fa-user-graduate"></i>
            <span>Enroll Students</span>
        </a>
        <a href="reports.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : '' ?>">
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