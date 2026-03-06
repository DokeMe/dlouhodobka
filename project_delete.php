<?php
require_once 'classes/database.php';
require_once 'includes/functions.php';

$db = new Database();
requireLogin();

$projectId = $_GET['id'] ?? null;
if (!$projectId || !filter_var($projectId, FILTER_VALIDATE_INT)) {
    header("Location: index.php");
    exit;
}

$userId = $_SESSION['user_id'];

if (!is_project_manager($db, $projectId, $userId)) {
    setFlash('error', 'Access Denied. You are not authorized to delete this project.');
    header("Location: projects.php");
    exit;
}

$db->beginTransaction();
try {
    $db->query("DELETE FROM tasks WHERE project_id = ?", [$projectId]);
    $db->query("DELETE FROM project_members WHERE project_id = ?", [$projectId]);
    $db->query("DELETE FROM projects WHERE id = ?", [$projectId]);
    
    $db->commit();
    setFlash('success', 'Project and all its tasks have been deleted.');
} catch (Exception $e) {
    $db->rollBack();
    setFlash('error', 'Error deleting project: ' . $e->getMessage());
}

header("Location: projects.php");
exit;
?>
