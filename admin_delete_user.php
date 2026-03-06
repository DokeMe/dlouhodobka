<?php
require_once 'classes/database.php';
require_once 'includes/functions.php';

$db = new Database();
requireLogin();

if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    setFlash('error', 'Access denied.');
    header("Location: index.php");
    exit;
}

$userIdToDelete = $_GET['id'] ?? null;

if (!$userIdToDelete || !filter_var($userIdToDelete, FILTER_VALIDATE_INT)) {
    setFlash('error', 'Invalid user ID.');
    header("Location: admin_users.php");
    exit;
}

if ($userIdToDelete == $_SESSION['user_id']) {
    setFlash('error', 'You cannot delete your own account.');
    header("Location: admin_users.php");
    exit;
}

$db->beginTransaction();
try {
    $db->query("DELETE FROM project_members WHERE user_id = ?", [$userIdToDelete]);
    $db->query("UPDATE tasks SET assigned_to = NULL WHERE assigned_to = ?", [$userIdToDelete]);
    $db->query("DELETE FROM users WHERE id = ?", [$userIdToDelete]);
    
    $db->commit();
    setFlash('success', 'User successfully deleted.');
} catch (Exception $e) {
    $db->rollBack();
    setFlash('error', 'Error deleting user: ' . $e->getMessage());
}

header("Location: admin_users.php");
exit;
?>
