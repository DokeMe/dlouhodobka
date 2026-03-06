<aside class="sidebar">
    <div class="sidebar-header">
        <div class="logo">
            <span class="logo-icon">⚡</span>
            <span class="logo-text">TaskFlow</span>
        </div>
    </div>

    <nav class="sidebar-nav">
        <ul>
            <li>
                <div class="nav-section-title">Overview</div>
            </li>
            <li>
                <a href="index.php" class="<?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-chart-line"></i> Dashboard
                </a>
            </li>
            <li>
                <a href="tasks.php" class="<?= basename($_SERVER['PHP_SELF']) == 'tasks.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-list-check"></i> My Tasks
                </a>
            </li>
            <li>
                <a href="projects.php" class="<?= basename($_SERVER['PHP_SELF']) == 'projects.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-folder-open"></i> Projects
                </a>
            </li>
            
            <li>
                <div class="nav-section-title">Workspace</div>
            </li>
            <li>
                <a href="create_project.php" class="<?= basename($_SERVER['PHP_SELF']) == 'create_project.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-plus"></i> New Project
                </a>
            </li>
            
            <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1): ?>
                <li>
                    <div class="nav-section-title">Admin</div>
                </li>
                <li>
                    <a href="admin_users.php" class="<?= basename($_SERVER['PHP_SELF']) == 'admin_users.php' ? 'active' : '' ?>">
                        <i class="fa-solid fa-users"></i> Users
                    </a>
                </li>
            <?php endif; ?>

            <li>
                <div class="nav-section-title">Account</div>
            </li>
            <li>
                <a href="profile_edit.php" class="<?= basename($_SERVER['PHP_SELF']) == 'profile_edit.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-user-gear"></i> Settings
                </a>
            </li>
            <li>
                <a href="logout.php" class="text-danger">
                    <i class="fa-solid fa-right-from-bracket"></i> Logout
                </a>
            </li>
        </ul>
    </nav>
</aside>
