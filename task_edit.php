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

if (!can_edit_task($db, $taskId, $userId)) {
    setFlash('error', 'Access Denied. You cannot edit this task.');
    header("Location: index.php");
    exit;
}

$task = $db->single("SELECT * FROM tasks WHERE id = ?", [$taskId]);
$projectId = $task['project_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $statusId = $_POST['status_id'];
    $priorityId = $_POST['priority_id'];
    $deadline = $_POST['deadline'];
    $assignedTo = $_POST['assigned_to'];

    if (empty($title)) {
        setFlash("error", "Title is required.");
    } else {
        try {
            $db->query(
                "UPDATE tasks SET title = ?, description = ?, status_id = ?, priority_id = ?, assigned_to = ?, deadline = ? WHERE id = ?",
                [$title, $description, $statusId, $priorityId, $assignedTo, !empty($deadline) ? $deadline : null, $taskId]
            );
            setFlash("success", "Task updated successfully.");
            header("Location: project_detail.php?id=" . $projectId);
            exit;
        } catch (Exception $e) {
            setFlash("error", "Database error: " . $e->getMessage());
        }
    }
}

$statuses = $db->all("SELECT * FROM statuses");
$priorities = $db->all("SELECT * FROM priorities");
$projectMembers = $db->all("SELECT u.id, u.username FROM users u JOIN project_members pm ON u.id = pm.user_id WHERE pm.project_id = ?", [$projectId]);

$pageTitle = "Edit Task";

include 'components/head.php';
?>

<div class="dashboard-wrapper">
    <div class="dashboard-container">
        <?php include 'components/sidebar.php'; ?>

        <main class="main-content">
            <?php include 'components/header.php'; ?>

            <div class="content">
                <div class="content-header">
                    <h1>Edit Task</h1>
                    <div class="content-meta">
                        <span>Update task details</span>
                    </div>
                </div>

                <div class="form-container" style="max-width: 800px;">
                    <div class="card-section" style="margin-bottom: 2rem; background: white; padding: 1.5rem; border-radius: 12px; border: 1px solid var(--border-color);">
                        <form action="" method="post">
                            <div class="form-group" style="margin-bottom: 1.5rem;">
                                <label for="title" style="display: block; margin-bottom: 0.5rem; font-size: 0.9rem; font-weight: 500;">Task Title</label>
                                <input type="text" name="title" id="title" value="<?= e($task['title']) ?>" required class="panel-input">
                            </div>

                            <div class="form-group" style="margin-bottom: 1.5rem;">
                                <label for="description" style="display: block; margin-bottom: 0.5rem; font-size: 0.9rem; font-weight: 500;">Description</label>
                                <textarea name="description" id="description" rows="4" class="panel-input" style="resize: vertical; min-height: 100px;"><?= e($task['description']) ?></textarea>
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                                <div class="form-group">
                                    <label for="status_id" style="display: block; margin-bottom: 0.5rem; font-size: 0.9rem; font-weight: 500;">Status</label>
                                    <select name="status_id" id="status_id" required class="panel-input" style="cursor: pointer;">
                                        <?php foreach ($statuses as $status): ?>
                                            <option value="<?= $status['id'] ?>" <?= ($task['status_id'] == $status['id']) ? 'selected' : '' ?>>
                                                <?= e($status['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="priority_id" style="display: block; margin-bottom: 0.5rem; font-size: 0.9rem; font-weight: 500;">Priority</label>
                                    <select name="priority_id" id="priority_id" required class="panel-input" style="cursor: pointer;">
                                        <?php foreach ($priorities as $priority): ?>
                                            <option value="<?= $priority['id'] ?>" <?= ($task['priority_id'] == $priority['id']) ? 'selected' : '' ?>>
                                                <?= e($priority['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                                <div class="form-group">
                                    <label for="assigned_to" style="display: block; margin-bottom: 0.5rem; font-size: 0.9rem; font-weight: 500;">Assign To</label>
                                    <select name="assigned_to" id="assigned_to" required class="panel-input" style="cursor: pointer;">
                                        <?php foreach ($projectMembers as $member): ?>
                                            <option value="<?= $member['id'] ?>" <?= ($task['assigned_to'] == $member['id']) ? 'selected' : '' ?>>
                                                <?= e($member['username']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="deadline" style="display: block; margin-bottom: 0.5rem; font-size: 0.9rem; font-weight: 500;">Deadline</label>
                                    <input type="date" name="deadline" id="deadline" value="<?= e($task['deadline']) ?>" class="panel-input">
                                </div>
                            </div>
                            
                            <div class="form-actions" style="display: flex; gap: 1rem;">
                                <button type="submit" class="btn-new-task" >Save Changes</button>
                                <a href="project_detail.php?id=<?= $projectId ?>" class="btn-new-task" style="background: white; color: var(--text-main); border: 1px solid var(--border-color);">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
</body>
</html>
