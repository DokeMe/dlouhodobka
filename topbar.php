<?php
$currentScript = basename($_SERVER['PHP_SELF']);
$breadcrumbs = [];

$breadcrumbs[] = ['label' => 'Dashboard', 'url' => 'index.php'];

switch ($currentScript) {
    case 'index.php':
        break;
    case 'tasks.php':
        $breadcrumbs[] = ['label' => 'My Tasks', 'url' => ''];
        break;
    case 'projects.php':
        $breadcrumbs[] = ['label' => 'Projects', 'url' => ''];
        break;
    case 'project_detail.php':
        $breadcrumbs[] = ['label' => 'Projects', 'url' => 'projects.php'];
        if (isset($project['title'])) {
            $breadcrumbs[] = ['label' => $project['title'], 'url' => ''];
        } else {
            $breadcrumbs[] = ['label' => 'Detail', 'url' => ''];
        }
        break;
    case 'create_project.php':
        $breadcrumbs[] = ['label' => 'Projects', 'url' => 'projects.php'];
        $breadcrumbs[] = ['label' => 'New Project', 'url' => ''];
        break;
    case 'project_edit.php':
        $breadcrumbs[] = ['label' => 'Projects', 'url' => 'projects.php'];
        if (isset($project['title'])) {
            $breadcrumbs[] = ['label' => $project['title'], 'url' => 'project_detail.php?id=' . $project['id']];
        }
        $breadcrumbs[] = ['label' => 'Edit', 'url' => ''];
        break;
    case 'create_task.php':
        $breadcrumbs[] = ['label' => 'Tasks', 'url' => 'tasks.php'];
        $breadcrumbs[] = ['label' => 'New Task', 'url' => ''];
        break;
    case 'task_edit.php':
        if (isset($task['project_id'])) {
             $breadcrumbs[] = ['label' => 'Project', 'url' => 'project_detail.php?id=' . $task['project_id']];
        } else {
             $breadcrumbs[] = ['label' => 'Tasks', 'url' => 'tasks.php'];
        }
        $breadcrumbs[] = ['label' => 'Edit Task', 'url' => ''];
        break;
    case 'profile_edit.php':
        $breadcrumbs[] = ['label' => 'Settings', 'url' => ''];
        break;
    case 'admin_users.php':
        $breadcrumbs[] = ['label' => 'Admin', 'url' => ''];
        $breadcrumbs[] = ['label' => 'Users', 'url' => ''];
        break;
    case 'admin_edit_user.php':
        $breadcrumbs[] = ['label' => 'Admin', 'url' => ''];
        $breadcrumbs[] = ['label' => 'Users', 'url' => 'admin_users.php'];
        $breadcrumbs[] = ['label' => 'Edit User', 'url' => ''];
        break;
    default:
        if (isset($pageTitle) && $pageTitle !== 'Dashboard') {
            $breadcrumbs[] = ['label' => $pageTitle, 'url' => ''];
        }
}
?>

<header class="header">
    <div class="header-breadcrumbs">
        <?php foreach ($breadcrumbs as $index => $crumb): ?>
            <?php if ($index > 0): ?>
                <span class="breadcrumb-separator">/</span>
            <?php endif; ?>

            <?php if ($index === count($breadcrumbs) - 1 || empty($crumb['url'])): ?>
                <span class="breadcrumb-current"><?= htmlspecialchars($crumb['label']) ?></span>
            <?php else: ?>
                <a href="<?= $crumb['url'] ?>" class="breadcrumb-item"><?= htmlspecialchars($crumb['label']) ?></a>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>

    <div class="header-actions">
        <a href="profile_edit.php" class="user-profile-display">
            <div class="user-avatar">
                <?= strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1)) ?>
            </div>
            <span class="user-name"><?= htmlspecialchars($_SESSION['username'] ?? 'User') ?></span>
        </a>
    </div>
</header>
