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
    $input = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($input) || empty($password)) {
        $error = "Username and password are required.";
    } else {
        $sql = "SELECT id, username, password_hash, is_admin FROM users WHERE username = :name OR email = :email";
        $user = $db->single($sql, ['name' => $input, 'email' => $input]);

        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['loggedin'] = true;
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['is_admin'] = $user['is_admin'];
            header("Location: index.php");
            exit;
        } else {
            $error = "Invalid username/email or password.";
        }
    }
}

$pageTitle = "Login";
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
                <h2>Welcome Back</h2>
                <p>Enter your email and password to access your account</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="msg error" style="margin-bottom: 24px;"><?= e($error) ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="auth-form-group">
                    <label>Username or Email</label>
                    <div class="auth-input-wrapper">
                        <input type="text" name="username" placeholder="Enter your username or email" required value="<?= e($_POST['username'] ?? '') ?>">
                    </div>
                </div>

                <div class="auth-form-group">
                    <label>Password</label>
                    <div class="auth-input-wrapper">
                        <input type="password" id="passwordInput" name="password" placeholder="Enter your password" required>
                        <button type="button" class="auth-toggle-password" id="togglePassword">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="auth-btn-primary">Sign In</button>
            </form>
        </div>

        <div class="auth-signup-link">
            Don't have an account? <a href="register.php">Sign Up</a>
        </div>
    </div>
</div>

<script>
    const togglePassword = document.querySelector('#togglePassword');
    const passwordInput = document.querySelector('#passwordInput');

    if (togglePassword && passwordInput) {
        togglePassword.addEventListener('click', function () {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.innerHTML = type === 'password' ? '<i class="fa-solid fa-eye"></i>' : '<i class="fa-solid fa-eye-slash"></i>';
        });
    }
</script>

</body>
</html>
