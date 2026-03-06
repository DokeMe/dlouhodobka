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

$userIdToEdit = $_GET['id'] ?? null;
if (!$userIdToEdit || !filter_var($userIdToEdit, FILTER_VALIDATE_INT)) {
    header("Location: admin_users.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newUsername = trim($_POST['username']);
    $newIsAdmin = isset($_POST['is_admin']) ? 1 : 0;
    $newPassword = $_POST['password'] ?? '';
    $hasError = false;

    if (empty($newUsername)) {
        setFlash("error", "Username cannot be empty.");
        $hasError = true;
    } else {
        $exists = $db->single(
            "SELECT id FROM users WHERE username = ? AND id != ?",
            [$newUsername, $userIdToEdit]
        );

        if ($exists) {
            setFlash("error", "This username is already taken.");
            $hasError = true;
        } else {
            if ($userIdToEdit == $_SESSION['user_id'] && $newIsAdmin == 0) {
                setFlash("error", "You cannot remove your own admin status.");
                $hasError = true;
            } else {
                $updatePassword = false;
                if (!empty($newPassword)) {
                    if (strlen($newPassword) < 8 || !preg_match('/[0-9]/', $newPassword)) {
                        setFlash("error", "Password must be at least 8 characters and include one number.");
                        $hasError = true;
                    } else {
                        $updatePassword = true;
                    }
                }

                if (!$hasError) {
                    $sql = "UPDATE users SET username = ?, is_admin = ?";
                    $params = [$newUsername, $newIsAdmin];

                    if ($updatePassword) {
                        $sql .= ", password_hash = ?";
                        $params[] = password_hash($newPassword, PASSWORD_DEFAULT);
                    }
                    $sql .= " WHERE id = ?";
                    $params[] = $userIdToEdit;

                    $db->query($sql, $params);
                    setFlash("success", "User updated successfully.");
                }
            }
        }
    }
}

$user = $db->single("SELECT id, username, email, is_admin FROM users WHERE id = ?", [$userIdToEdit]);

if (!$user) {
    header("Location: admin_users.php");
    exit;
}

$pageTitle = "Admin: Edit User";

include 'components/head.php';
?>

<div class="dashboard-wrapper">
    <div class="dashboard-container">
        <?php include 'components/sidebar.php'; ?>

        <main class="main-content">
            <?php include 'components/header.php'; ?>

            <div class="content">
                <div class="content-header">
                    <h1>Edit User: <?= e($user['username']) ?></h1>
                    <div class="content-meta">
                        <span>Update user details</span>
                    </div>
                </div>

                <div class="form-container" style="max-width: 800px;">
                    <div class="card-section" style="margin-bottom: 2rem; background: white; padding: 1.5rem; border-radius: 12px; border: 1px solid var(--border-color);">
                        <form action="" method="POST">
                            <div class="form-group" style="margin-bottom: 1.5rem;">
                                <label for="username" style="display: block; margin-bottom: 0.5rem; font-size: 0.9rem; font-weight: 500;">Username</label>
                                <input type="text" id="username" name="username" value="<?= e($user['username']) ?>" required class="panel-input">
                            </div>

                            <div class="form-group" style="margin-bottom: 1.5rem;">
                                <label for="email" style="display: block; margin-bottom: 0.5rem; font-size: 0.9rem; font-weight: 500;">Email (read-only)</label>
                                <input type="email" id="email" name="email" value="<?= e($user['email']) ?>" readonly disabled class="panel-input" style="background: #f9fafb; cursor: not-allowed;">
                            </div>

                            <div class="form-group" style="margin-bottom: 1.5rem;">
                                <label for="password" style="display: block; margin-bottom: 0.5rem; font-size: 0.9rem; font-weight: 500;">New Password (optional)</label>
                                <div class="password-wrapper" style="position: relative;">
                                    <input type="password" name="password" id="password" placeholder="Leave blank to keep current password" class="panel-input">
                                    <button type="button" class="password-toggle" id="togglePassword" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer;">👁️</button>
                                </div>
                                <ul class="requirements" style="list-style: none; padding: 0; margin-top: 0.5rem; font-size: 0.8rem; color: var(--text-muted);">
                                    <li id="req-length">At least 8 characters</li>
                                    <li id="req-number">Contains at least one number</li>
                                </ul>
                            </div>

                            <div class="form-group" style="margin-bottom: 1.5rem;">
                                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                                    <input type="checkbox" name="is_admin" value="1" <?= $user['is_admin'] ? 'checked' : '' ?> <?= ($user['id'] == $_SESSION['user_id']) ? 'disabled' : '' ?> style="width: 1rem; height: 1rem;">
                                    <span style="font-size: 0.9rem; font-weight: 500;">Is Admin</span>
                                </label>
                                <?php if ($user['id'] == $_SESSION['user_id']): ?>
                                    <p class="text-muted" style="font-size: 0.85rem; margin-top: 0.5rem; color: var(--text-muted);">(You cannot remove your own admin status)</p>
                                <?php endif; ?>
                            </div>

                            <div class="form-actions" style="margin-top: 2rem; display: flex; gap: 1rem;">
                                <button type="submit" class="btn-new-task">Save Changes</button>
                                <a href="admin_users.php" class="btn-new-task">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script src="assets/js/password_handler.js"></script>
</body>
</html>
