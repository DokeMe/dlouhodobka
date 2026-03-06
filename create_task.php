<?php
require_once 'classes/database.php';
require_once 'includes/functions.php';

$db = new Database();
requireLogin();

$userId = $_SESSION['user_id'];
$pageTitle = "Create New Task";

$projectId = $_GET['project_id'] ?? null;
if ($projectId && !filter_var($projectId, FILTER_VALIDATE_INT)) {
    $projectId = null;
}

if ($projectId && !is_project_member($db, $projectId, $userId)) {
    setFlash("error", "Access Denied. You are not a member of this project.");
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['title'])) {
    $postedProjectId = $_POST['project_id'];
    
    if ($postedProjectId != $projectId) {
        setFlash("error", "An error occurred. Project mismatch.");
        header("Location: index.php");
        exit;
    }

    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $statusId = $_POST['status_id'];
    $priorityId = $_POST['priority_id'];
    $deadline = $_POST['deadline'];
    $assignedTo = $_POST['assigned_to'];

    if (empty($title) || empty($postedProjectId)) {
        setFlash("error", "Project and Title are required.");
    } else {
        try {
            $db->insert('tasks', [
                'project_id' => $postedProjectId,
                'title' => $title,
                'description' => $description,
                'status_id' => $statusId,
                'priority_id' => $priorityId,
                'assigned_to' => $assignedTo,
                'position' => 0,
                'deadline' => !empty($deadline) ? $deadline : null
            ]);
            setFlash("success", "Task created successfully.");
            header("Location: project_detail.php?id=" . $postedProjectId);
            exit;
        } catch (Exception $e) {
            setFlash("error", "Database error: " . $e->getMessage());
        }
    }
}

$myProjects = [];
if (!$projectId) {
    if (is_admin()) {
        $myProjects = $db->all("SELECT id, title FROM projects ORDER BY title");
    } else {
        $myProjects = $db->all(
            "SELECT DISTINCT p.id, p.title FROM projects p JOIN project_members pm ON p.id = pm.project_id WHERE pm.user_id = ? ORDER BY p.title",
            [$userId]
        );
    }
} else {
    $statuses = $db->all("SELECT * FROM statuses");
    $priorities = $db->all("SELECT * FROM priorities");
    $project = $db->single("SELECT title FROM projects WHERE id = ?", [$projectId]);
    $projectMembers = $db->all("SELECT u.id, u.username FROM users u JOIN project_members pm ON u.id = pm.user_id WHERE pm.project_id = ?", [$projectId]);
}

include 'components/head.php';
?>

<div class="dashboard-wrapper">
    <div class="dashboard-container">
        <?php include 'components/sidebar.php'; ?>

        <main class="main-content">
            <?php include 'components/header.php'; ?>

            <div class="content">
                <div class="content-header">
                    <h1>Create New Task</h1>
                    <div class="content-meta">
                        <span>Add a new task to a project</span>
                    </div>
                </div>

                <div class="form-container" style="max-width: 800px;">
                    <div class="card-section" style="margin-bottom: 2rem; background: white; padding: 1.5rem; border-radius: 12px; border: 1px solid var(--border-color);">
                        
                        <?php if (!$projectId): ?>
                            
                            <form action="" method="get">
                                <div class="form-group" style="margin-bottom: 1.5rem;">
                                    <label for="project_id" style="display: block; margin-bottom: 0.5rem; font-size: 0.9rem; font-weight: 500;">First, select a project:</label>
                                    <select name="project_id" id="project_id" required class="panel-input" style="cursor: pointer;">
                                        <option value="" disabled selected>Choose a project first</option>
                                        <?php foreach ($myProjects as $proj): ?>
                                            <option value="<?= $proj['id'] ?>"><?= e($proj['title']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-actions">
                                    <button type="submit" class="btn-new-task">Continue</button>
                                </div>
                            </form>

                        <?php else: ?>
                            
                            <form action="create_task.php?project_id=<?= $projectId ?>" method="post">
                                <input type="hidden" name="project_id" value="<?= $projectId ?>">

                                <div class="form-group" style="margin-bottom: 1.5rem;">
                                    <label for="project_display" style="display: block; margin-bottom: 0.5rem; font-size: 0.9rem; font-weight: 500;">Project</label>
                                    <input type="text" id="project_display" value="<?= e($project['title']) ?>" disabled class="panel-input" style="background: #f9fafb; cursor: not-allowed;">
                                </div>

                                <div class="form-group" style="margin-bottom: 1.5rem;">
                                    <label for="title" style="display: block; margin-bottom: 0.5rem; font-size: 0.9rem; font-weight: 500;">Task Title</label>
                                    <input type="text" name="title" id="title" required class="panel-input">
                                </div>

                                <div class="form-group" style="margin-bottom: 1.5rem;">
                                    <label for="description" style="display: block; margin-bottom: 0.5rem; font-size: 0.9rem; font-weight: 500;">Description</label>
                                    <textarea name="description" id="description" rows="4" class="panel-input" style="resize: vertical; min-height: 100px;"></textarea>
                                </div>

                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                                    <div class="form-group">
                                        <label for="status_id" style="display: block; margin-bottom: 0.5rem; font-size: 0.9rem; font-weight: 500;">Status</label>
                                        <select name="status_id" id="status_id" required class="panel-input" style="cursor: pointer;">
                                            <?php foreach ($statuses as $status): ?>
                                                <option value="<?= $status['id'] ?>"><?= e($status['name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="priority_id" style="display: block; margin-bottom: 0.5rem; font-size: 0.9rem; font-weight: 500;">Priority</label>
                                        <select name="priority_id" id="priority_id" required class="panel-input" style="cursor: pointer;">
                                            <?php foreach ($priorities as $priority): ?>
                                                <option value="<?= $priority['id'] ?>"><?= e($priority['name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                                    <div class="form-group">
                                        <label for="assigned_to" style="display: block; margin-bottom: 0.5rem; font-size: 0.9rem; font-weight: 500;">Assign To</label>
                                        <select name="assigned_to" id="assigned_to" required class="panel-input" style="cursor: pointer;">
                                            <?php foreach ($projectMembers as $member): ?>
                                                <option value="<?= $member['id'] ?>" <?= ($member['id'] == $userId) ? 'selected' : '' ?>>
                                                    <?= e($member['username']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="deadline" style="display: block; margin-bottom: 0.5rem; font-size: 0.9rem; font-weight: 500;">Deadline</label>
                                        <input type="date" name="deadline" id="deadline" class="panel-input">
                                    </div>
                                </div>
                                
                                <div class="form-actions" style="margin-top: 2rem; display: flex; gap: 1rem;">
                                    <button type="submit" class="btn-new-task">Create Task</button>
                                    <a href="index.php" class="btn-new-task" style="background: white; color: var(--text-main); border: 1px solid var(--border-color);">Cancel</a>
                                </div>
                            </form>

                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
</body>
</html>
