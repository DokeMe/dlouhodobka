<?php
require_once 'classes/database.php';
require_once 'includes/functions.php';

$db = new Database();
requireLogin();

if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: index.php");
    exit;
}

$userId = $_SESSION['user_id'];
$isAdmin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;

$users = $db->all("SELECT id, username, email, is_admin, created_at FROM users ORDER BY created_at DESC");

$pageTitle = "Admin: Manage Users";

include 'components/head.php';
?>

<div class="dashboard-wrapper">
    <div class="dashboard-container">
        <?php include 'components/sidebar.php'; ?>

        <main class="main-content">
            <?php include 'components/header.php'; ?>

            <div class="content">
                <div class="content-header">
                    <h1>User Management</h1>
                    <div class="content-meta">
                        <span>Manage system users</span>
                    </div>
                </div>

                <div class="card-section" style="background: white; border: 1px solid var(--border-color); border-radius: 12px; overflow: hidden;">
                    <table class="tasks-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Registered</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($users) > 0): ?>
                                <?php foreach ($users as $user): ?>
                                    <tr style="cursor: default;">
                                        <td><?= e($user['id']) ?></td>
                                        <td><strong><?= e($user['username']) ?></strong></td>
                                        <td><?= e($user['email']) ?></td>
                                        <td>
                                            <?php if ($user['is_admin']): ?>
                                                <span class="tag tag-high">Admin</span>
                                            <?php else: ?>
                                                <span class="tag tag-normal">User</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= date("M d, Y", strtotime($user['created_at'])) ?></td>
                                        <td class="actions" style="display: flex; gap: 0.5rem;">
                                            <a href="admin_edit_user.php?id=<?= $user['id'] ?>" class="btn-new-task" style="padding: 0.3rem 0.8rem; font-size: 0.8rem; border-color: var(--border-color); background-color: #f0fdf4; color: var(--primary-green); margin: 0; box-shadow: none;"><i class="fa-solid fa-pen-to-square"></i> Edit</a>
                                            <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                <a href="admin_delete_user.php?id=<?= $user['id'] ?>" class="btn-new-task" style="padding: 0.3rem 0.8rem; font-size: 0.8rem; border-color: #fecaca; background-color: #fef2f2; color: #ef4444; margin: 0; box-shadow: none;" onclick="return confirm('Are you sure you want to delete this user?');"><i class="fa-solid fa-trash"></i> Delete</a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="empty-state">No users found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</div>
</body>
</html>
