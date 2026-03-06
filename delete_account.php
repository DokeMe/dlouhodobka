<?php
require_once 'classes/database.php';
require_once 'includes/functions.php';

$db = new Database();
requireLogin();

$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db->query("DELETE FROM users WHERE id = ?", [$userId]);

        $_SESSION = [];

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        session_destroy();

        header("Location: login.php?message=account_deleted");
        exit;

    } catch (Exception $e) {
        setFlash('error', 'Error deleting account. Please contact support.');
        header("Location: profile_edit.php");
        exit;
    }
} else {
    header("Location: profile_edit.php");
    exit;
}
