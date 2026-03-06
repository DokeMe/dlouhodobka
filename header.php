<header class="header">
    <div class="header-actions">
        <a href="profile_edit.php" class="user-profile-display">
            <div class="user-avatar">
                <?= strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1)) ?>
            </div>
            <span class="user-name"><?= htmlspecialchars($_SESSION['username'] ?? 'User') ?></span>
        </a>
    </div>
</header>

<div id="sidebar-backdrop" class="sidebar-backdrop"></div>

<?php if (function_exists('displayFlash')) displayFlash(); ?>
