<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <title><?= isset($pageTitle) ? e($pageTitle) : 'TaskFlow' ?></title>
    
    <link rel="apple-touch-icon" sizes="180x180" href="<?= asset('/assets/img/apple-touch-icon.png') ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= asset('/assets/img/favicon-32x32.png') ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= asset('/assets/img/favicon-16x16.png') ?>">
    <link rel="manifest" href="<?= asset('/assets/img/site.webmanifest') ?>">
    <link rel="shortcut icon" href="<?= asset('/assets/img/favicon.ico') ?>">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <link rel="stylesheet" href="<?= asset('/assets/css/dashboard.css') ?>">
    
    <?php
    $currentPage = basename($_SERVER['PHP_SELF']);
    
    if ($currentPage == 'index.php') {
        echo '<link rel="stylesheet" href="' . asset('/assets/css/home_dashboard.css') . '">';
    }
    
    if (in_array($currentPage, ['login.php', 'register.php'])) {
        echo '<link rel="stylesheet" href="' . asset('/assets/css/auth.css') . '">';
    }
    ?>

    <script src="<?= asset('/assets/js/app.js') ?>" defer></script>
    
    <?php if (!in_array($currentPage, ['login.php', 'register.php'])): ?>
        <script src="<?= asset('/assets/js/dashboard.js') ?>" defer></script>
    <?php endif; ?>

</head>
<body>
