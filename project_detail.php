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
$isAdmin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_manager_id']) && $isAdmin) {
    $newManagerId = $_POST['new_manager_id'];

    $db->beginTransaction();
    try {
        $db->query("UPDATE project_members SET role_id = 2 WHERE project_id = ? AND role_id = 1", [$projectId]);
        $db->query("DELETE FROM project_members WHERE project_id = ? AND user_id = ?", [$projectId, $newManagerId]);
        $db->insert('project_members', ['project_id' => $projectId, 'user_id' => $newManagerId, 'role_id' => 1]);
        
        $db->commit();
        setFlash("success", "New Project Manager assigned successfully.");
        header("Location: project_detail.php?id=" . $projectId);
        exit;
    } catch (Exception $e) {
        $db->rollBack();
        setFlash("error", "Error assigning manager: " . $e->getMessage());
    }
}

$project = $db->single("SELECT * FROM projects WHERE id = ?", [$projectId]);
if (!$project) {
    setFlash('error', 'Project not found.');
    header("Location: index.php");
    exit;
}

$members = $db->all(
    "SELECT u.id, u.username, u.email, pm.role_id 
     FROM project_members pm 
     JOIN users u ON pm.user_id = u.id 
     WHERE pm.project_id = ?", 
    [$projectId]
);

$manager = null;
$isMember = false;
foreach ($members as $member) {
    if ($member['role_id'] == 1) $manager = $member;
    if ($member['id'] == $userId) $isMember = true;
}

$tasks = $db->all("SELECT t.*, s.name as status_label, pr.name as priority_label, p.title as project_title
                   FROM tasks t 
                   JOIN projects p ON t.project_id = p.id
                   LEFT JOIN statuses s ON t.status_id = s.id
                   LEFT JOIN priorities pr ON t.priority_id = pr.id
                   WHERE t.project_id = ? ORDER BY t.position ASC", [$projectId]);

$initialTasksJson = json_encode($tasks);
$statuses = $db->all("SELECT * FROM statuses ORDER BY id");
$priorities = $db->all("SELECT * FROM priorities ORDER BY id");

$pageTitle = "Project: " . $project['title'];

include 'components/head.php';
?>

<div class="dashboard-wrapper">
    <div class="dashboard-container">
        <?php include 'components/sidebar.php'; ?>

        <main class="main-content">
            <?php include 'components/header.php'; ?>

            <div class="content">
                <div class="project-detail-header" style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 2rem;">
                    <div>
                        <h1 class="page-title" style="font-size: 2rem; font-weight: 700; color: var(--text-main); margin-bottom: 0.5rem;"><?= e($project['title']) ?></h1>
                        <p class="text-muted" style="font-size: 1rem; color: var(--text-muted); line-height: 1.6; max-width: 600px;"><?= e($project['description']) ?></p>
                    </div>
                    <div class="header-buttons" style="display: flex; gap: 0.75rem;">
                        <button onclick="goBack()" class="btn btn-outline"><i class="fa-solid fa-arrow-left"></i> Back</button>
                        
                        <?php if ($isAdmin || ($manager && $manager['id'] == $userId)): ?>
                            <a href="project_edit.php?id=<?= $projectId ?>" class="btn btn-new-task"><i class="fa-solid fa-pen-to-square"></i> Edit</a>
                            <a href="project_delete.php?id=<?= $projectId ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this project? This action cannot be undone and will delete all tasks associated with it.');"><i class="fa-solid fa-trash"></i> Delete</a>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="tabs" style="margin-bottom: 1.5rem;">
                    <button class="tab active" onclick="switchProjectTab('tasks')"><i class="fa-solid fa-list-check"></i> Tasks</button>
                    <button class="tab" onclick="switchProjectTab('team')"><i class="fa-solid fa-users"></i> Team</button>
                </div>

                <div class="tab-content-wrapper">
                    
                    <div id="tab-tasks" class="project-tab-content">
                        <?php 
                            $fetchUrl = "api/api.php?action=get_project_tasks&project_id=" . $projectId;
                            include 'components/task_view.php'; 
                        ?>
                    </div>

                    <div id="tab-team" class="project-tab-content" style="display: none;">
                        <div class="team-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 1.5rem;">
                            <?php foreach ($members as $member): ?>
                                <div class="stat-card">
                                    <div class="avatar"><?= strtoupper(substr($member['username'], 0, 1)) ?></div>
                                    <div class="member-info">
                                        <span class="name" style="font-weight: 600; display: block; color: var(--text-main);"><?= e($member['username']) ?></span>
                                        <span class="email text-secondary" style="font-size: 0.85rem; color: var(--text-muted);"><?= e($member['email']) ?></span>
                                        <?php if ($member['role_id'] == 1): ?>
                                            <span class="role-badge" style="margin-top: 0.5rem; display: inline-block; font-size: 0.75rem; padding: 0.2rem 0.6rem; background: #e0f2fe; color: #0369a1; border-radius: 99px; font-weight: 600;"><i class="fa-solid fa-crown"></i> Manager</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <?php if ($isAdmin): ?>
                            <div class="card-section admin-actions-card" style="margin-top: 2rem; max-width: 500px; background: white; padding: 1.5rem; border-radius: 12px; border: 1px solid var(--border-color);">
                                <h4 style="margin-bottom: 1rem;">Admin Actions</h4>
                                <label style="display: block; margin-bottom: 0.5rem; font-size: 0.9rem; font-weight: 500;">Assign New Manager:</label>
                                <div style="position: relative;">
                                    <input type="text" id="manager-search" placeholder="Search user..." class="panel-input">
                                    <div id="search-results" style="display: none; position: absolute; width: 100%; background: white; border: 1px solid var(--border-color); border-top: none; z-index: 1000; max-height: 200px; overflow-y: auto; box-shadow: 0 4px 6px rgba(0,0,0,0.1); border-radius: 0 0 8px 8px;"></div>
                                </div>
                                <form id="assign-manager-form" method="POST" style="display: none;">
                                    <input type="hidden" name="new_manager_id" id="new_manager_id">
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>

                </div>

            </div>
        </main>
        
        <aside class="right-panel" id="right-panel">
            <div class="panel-header">
                <div class="panel-id">
                    <span id="panel-id-text">Task Details</span>
                </div>
                <div class="panel-actions">
                    <button class="panel-btn" onclick="closeRightPanel()">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                </div>
            </div>
            <div class="panel-content" id="panel-content">
                <div class="empty-state">Select a task to view details.</div>
            </div>
        </aside>
    </div>
</div>

<script>
    function switchProjectTab(tabName) {
        document.getElementById('tab-tasks').style.display = 'none';
        document.getElementById('tab-team').style.display = 'none';
        
        document.getElementById(`tab-${tabName}`).style.display = 'block';

        const buttons = document.querySelectorAll('.tabs .tab');
        buttons.forEach(btn => btn.classList.remove('active'));
        
        if (tabName === 'tasks') buttons[0].classList.add('active');
        if (tabName === 'team') buttons[1].classList.add('active');
    }

    const searchInput = document.getElementById('manager-search');
    const resultsContainer = document.getElementById('search-results');
    const form = document.getElementById('assign-manager-form');
    const inputId = document.getElementById('new_manager_id');
    
    const currentManagerId = <?= $manager ? $manager['id'] : 'null' ?>;

    if (searchInput) {
        let debounceTimer;
        searchInput.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            const query = this.value.trim();

            if (query.length < 2) {
                resultsContainer.style.display = 'none';
                return;
            }

            debounceTimer = setTimeout(() => {
                fetch(`api/api.php?action=search_users&query=${encodeURIComponent(query)}`)
                    .then(response => response.json())
                    .then(result => {
                        if (result.status === 'success' && result.data.length > 0) {
                            displayResults(result.data);
                        } else {
                            resultsContainer.style.display = 'none';
                        }
                    });
            }, 300);
        });

        function displayResults(users) {
            resultsContainer.innerHTML = '';
            users.forEach(user => {
                const isCurrentManager = (user.id == currentManagerId);
                const div = document.createElement('div');
                div.className = 'search-item'; 
                div.style.padding = '0.75rem 1rem';
                div.style.cursor = 'pointer';
                div.style.borderBottom = '1px solid var(--border-color)';
                
                if (isCurrentManager) {
                    div.style.opacity = '0.6';
                    div.style.cursor = 'default';
                    div.innerHTML = `<strong>${escapeHtml(user.username)}</strong> <small class="text-secondary">(Current Manager)</small>`;
                } else {
                    div.innerHTML = `<strong>${escapeHtml(user.username)}</strong> <small class="text-secondary">(${escapeHtml(user.email)})</small>`;
                    div.addEventListener('click', () => {
                        if (confirm(`Are you sure you want to assign ${user.username} as the new Project Manager?`)) {
                            inputId.value = user.id;
                            form.submit();
                        }
                    });
                }
                
                div.addEventListener('mouseenter', () => {
                    if (!isCurrentManager) div.style.backgroundColor = '#f9fafb';
                });
                div.addEventListener('mouseleave', () => {
                    div.style.backgroundColor = 'white';
                });

                resultsContainer.appendChild(div);
            });
            resultsContainer.style.display = 'block';
        }
        document.addEventListener('click', function(e) {
            if (!searchInput.contains(e.target) && !resultsContainer.contains(e.target)) {
                resultsContainer.style.display = 'none';
            }
        });
    }
</script>
</body>
</html>
