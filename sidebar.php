<?php
$currentPage = basename($_SERVER['PHP_SELF']);

$projectPages = ['projects.php', 'project_detail.php', 'project_edit.php']; 
$adminUserPages = ['admin_users.php', 'admin_edit_user.php'];
?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="logo">
            <span class="logo-text">TaskFlow</span>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section">
            <div class="nav-section-title">ACTIVITY</div>
            <a href="index.php" class="nav-item <?= ($currentPage == 'index.php') ? 'active' : '' ?>" title="Dashboard">
                <i class="nav-icon fa-solid fa-table-columns"></i>
                <span>Dashboard</span>
            </a>
            <a href="tasks.php" class="nav-item <?= ($currentPage == 'tasks.php' || $currentPage == 'task_edit.php') ? 'active' : '' ?>" title="My Tasks">
                <i class="nav-icon fa-solid fa-list-check"></i>
                <span>My Tasks</span>
            </a>
            <a href="projects.php" class="nav-item <?= in_array($currentPage, $projectPages) ? 'active' : '' ?>" title="Projects">
                <i class="nav-icon fa-solid fa-diagram-project"></i>
                <span>Projects</span>
            </a>
        </div>

        <div class="nav-section">
            <div class="nav-section-title">SETUP</div>
            <a href="create_project.php" class="nav-item <?= ($currentPage == 'create_project.php') ? 'active' : '' ?>" title="New Project">
                <i class="nav-icon fa-solid fa-plus"></i>
                <span>New Project</span>
            </a>
            <a href="create_task.php" class="nav-item <?= ($currentPage == 'create_task.php') ? 'active' : '' ?>" title="New Task">
                <i class="nav-icon fa-solid fa-plus-circle"></i>
                <span>New Task</span>
            </a>
            <?php if (is_admin()): ?>
            <a href="admin_users.php" class="nav-item <?= in_array($currentPage, $adminUserPages) ? 'active' : '' ?>" title="Users">
                <i class="nav-icon fa-solid fa-users"></i>
                <span>Users</span>
            </a>
            <?php endif; ?>
            <a href="profile_edit.php" class="nav-item <?= ($currentPage == 'profile_edit.php') ? 'active' : '' ?>" title="Settings">
                <i class="nav-icon fa-solid fa-gear"></i>
                <span>Settings</span>
            </a>
            <a href="logout.php" class="nav-item" title="Logout">
                <i class="nav-icon fa-solid fa-right-from-bracket"></i>
                <span>Logout</span>
            </a>
        </div>
    </nav>

    <div class="sidebar-footer">
        Powered by <span>TaskFlow</span>
    </div>
</aside>
