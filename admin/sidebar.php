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
        <a href="users.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : '' ?>">
            <i class="fas fa-users"></i>
            <span>Users</span>
        </a>
        <a href="classes.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'classes.php' ? 'active' : '' ?>">
            <i class="fas fa-school"></i>
            <span>Classes</span>
        </a>
        <a href="subjects.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'subjects.php' ? 'active' : '' ?>">
            <i class="fas fa-book"></i>
            <span>Subjects</span>
        </a>
        <a href="assignments.php"
            class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'assignments.php' ? 'active' : '' ?>">
            <i class="fas fa-tasks"></i>
            <span>Assignments</span>
        </a>
        <a href="quizzes.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'quizzes.php' ? 'active' : '' ?>">
            <i class="fas fa-question-circle"></i>
            <span>Quizzes</span>
        </a>
        <a href="reports.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : '' ?>">
            <i class="fas fa-chart-bar"></i>
            <span>Reports</span>
        </a>
    </nav>
    <div class="sidebar-footer">
        <a href="../logout.php">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </div>
</aside>