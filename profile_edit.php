<?php
require_once 'classes/database.php';
require_once 'includes/functions.php';

$db = new Database();
requireLogin();

$userId = $_SESSION['user_id'];
$isAdmin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username'])) {
    $newUsername = trim($_POST['username']);
    $newPassword = $_POST['password'];
    $hasError = false;

    if (empty($newUsername)) {
        setFlash("error", "Username cannot be empty.");
        $hasError = true;
    } else {
        $exists = $db->single("SELECT id FROM users WHERE username = ? AND id != ?", [$newUsername, $userId]);
        if ($exists) {
            setFlash("error", "This username is already taken.");
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
                if ($updatePassword) {
                    $sql = "UPDATE users SET username = ?, password_hash = ? WHERE id = ?";
                    $params = [$newUsername, password_hash($newPassword, PASSWORD_DEFAULT), $userId];
                } else {
                    $sql = "UPDATE users SET username = ? WHERE id = ?";
                    $params = [$newUsername, $userId];
                }
                $db->query($sql, $params);
                $_SESSION['username'] = $newUsername;
                setFlash("success", "Profile updated successfully.");
            }
        }
    }
}

$me = $db->single("SELECT username, email FROM users WHERE id = ?", [$userId]);

$pageTitle = "Settings";

include 'components/head.php';
?>

<div class="dashboard-wrapper">
    <div class="dashboard-container">
        <?php include 'components/sidebar.php'; ?>

        <main class="main-content">
            <?php include 'components/header.php'; ?>

            <div class="content">
                <div class="content-header">
                    <h1>Settings</h1>
                    <div class="content-meta">
                        <span>Manage your account</span>
                    </div>
                </div>

                <div class="form-container" style="max-width: 800px;">
                    <div class="card-section" style="margin-bottom: 2rem; background: white; padding: 1.5rem; border-radius: 12px; border: 1px solid var(--border-color);">
                        <h3 style="margin-bottom: 1rem; font-size: 1.1rem;">Profile</h3>
                        <form action="" method="post">
                            <div class="form-group" style="margin-bottom: 1.5rem;">
                                <label for="username" style="display: block; margin-bottom: 0.5rem; font-size: 0.9rem; font-weight: 500;">Username</label>
                                <input type="text" name="username" id="username" value="<?= e($me['username']) ?>" required class="panel-input">
                            </div>
                            <div class="form-group" style="margin-bottom: 1.5rem;">
                                <label for="email" style="display: block; margin-bottom: 0.5rem; font-size: 0.9rem; font-weight: 500;">Email (cannot be changed)</label>
                                <input type="email" id="email" value="<?= e($me['email']) ?>" disabled class="panel-input" style="background: #f9fafb; cursor: not-allowed;">
                            </div>
                            <div class="form-group" style="margin-bottom: 1.5rem;">
                                <label for="password" style="display: block; margin-bottom: 0.5rem; font-size: 0.9rem; font-weight: 500;">New Password</label>
                                <div class="password-wrapper">
                                    <input type="password" name="password" id="password" placeholder="Leave blank to keep current password" class="panel-input">
                                    <button type="button" class="password-toggle" id="togglePassword">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                </div>
                                <ul class="requirements">
                                    <li id="req-length">At least 8 characters</li>
                                    <li id="req-number">Contains at least one number</li>
                                </ul>
                            </div>
                            <div class="form-actions">
                                <button type="submit" class="btn-new-task" >Save Changes</button>
                            </div>
                        </form>
                    </div>

                    <div class="danger-zone" style="background: #fef2f2; padding: 1.5rem; border-radius: 12px; border: 1px solid #fecaca;">
                        <h3 style="color: #b91c1c; margin-bottom: 0.5rem; font-size: 1.1rem;">Delete Account</h3>
                        <p style="color: #7f1d1d; font-size: 0.9rem; margin-bottom: 1rem;">Once you delete your account, there is no going back. Please be certain.</p>
                        <form action="delete_account.php" method="post" onsubmit="return confirm('Are you absolutely sure you want to delete your account? This action cannot be undone.');">
                            <button type="submit" class="btn btn-danger">Delete My Account</button>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script src="<?= asset('/assets/js/password_handler.js') ?>"></script>
</body>
</html>
