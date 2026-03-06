<?php
require_once 'classes/database.php';
require_once 'includes/functions.php';

$db = new Database();
requireLogin();

$taskId = $_GET['id'] ?? null;
if (!$taskId || !filter_var($taskId, FILTER_VALIDATE_INT)) {
    header("Location: index.php");
    exit;
}

$userId = $_SESSION['user_id'];
$redirectUrl = $_SERVER['HTTP_REFERER'] ?? 'index.php';

if (!can_delete_task($db, $taskId, $userId)) {
    setFlash('error', 'Access denied or task not found.');
    header("Location: " . $redirectUrl);
    exit;
}

try {
    $db->query("DELETE FROM tasks WHERE id = ?", [$taskId]);
    setFlash('success', 'Task successfully deleted.');
} catch (Exception $e) {
    setFlash('error', 'Error deleting task: ' . $e->getMessage());
}

header("Location: " . $redirectUrl);
exit;
?>
