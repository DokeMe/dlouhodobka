<?php
require_once 'classes/database.php';
require_once 'includes/functions.php';

if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    header("Location: index.php");
    exit;
}

$error = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = new Database();
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($username) || empty($email) || empty($password)) {
        $error = "Please fill in all fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } elseif (strlen($password) < 8 || !preg_match('/[0-9]/', $password)) {
        $error = "Password must be at least 8 characters and include one number.";
    } else {
        $existingUser = $db->single(
            "SELECT id FROM users WHERE username = :username OR email = :email",
            ['username' => $username, 'email' => $email]
        );

        if ($existingUser) {
            $error = "This username or email is already registered.";
        } else {
            $newUserId = $db->insert('users', [
                'username' => $username,
                'email' => $email,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'is_admin' => 0
            ]);

            if ($newUserId) {
                $_SESSION['loggedin'] = true;
                $_SESSION['user_id'] = $newUserId;
                $_SESSION['username'] = $username;
                $_SESSION['is_admin'] = 0;
                header("Location: index.php");
                exit;
            } else {
                $error = "An error occurred during registration.";
            }
        }
    }
}

$pageTitle = "Register";
include 'components/head.php';
?>
<body class="auth-page">

<div class="auth-container">
    <div class="auth-left-panel">
        <div class="auth-image-bg">
        </div>
    </div>

    <div class="auth-right-panel">
        <a href="index.php" class="auth-logo">
            TaskFlow
        </a>

        <div class="auth-form-wrapper">
            <div class="auth-header">
                <h2>Create Account</h2>
                <p>Join us and start managing your tasks efficiently.</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="msg error" style="margin-bottom: 24px;"><?= e($error) ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="auth-form-group">
                    <label>Username</label>
                    <div class="auth-input-wrapper">
                        <input type="text" name="username" placeholder="Choose a username" required value="<?= e($_POST['username'] ?? '') ?>">
                    </div>
                </div>
                
                <div class="auth-form-group">
                    <label>Email</label>
                    <div class="auth-input-wrapper">
                        <input type="email" name="email" placeholder="Enter your email" required value="<?= e($_POST['email'] ?? '') ?>">
                    </div>
                </div>

                <div class="auth-form-group">
                    <label>Password</label>
                    <div class="auth-input-wrapper">
                        <input type="password" id="password" name="password" placeholder="Create a password" required>
                        <button type="button" class="auth-toggle-password" id="togglePassword">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </div>
                    <ul class="requirements">
                        <li id="req-length">At least 8 characters</li>
                        <li id="req-number">Contains at least one number</li>
                    </ul>
                </div>

                <button type="submit" class="auth-btn-primary">Sign Up</button>
            </form>
        </div>

        <div class="auth-signup-link">
            Already have an account? <a href="login.php">Sign In</a>
        </div>
    </div>
</div>

<script src="<?= asset('/assets/js/password_handler.js') ?>"></script>

</body>
</html>
