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
    setFlash('error', 'Access Denied.');
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['title'])) {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $newMembers = $_POST['members'] ?? [];

    if (empty($title)) {
        setFlash("error", "Project title is required.");
    } else {
        $db->beginTransaction();
        try {
            $db->query("UPDATE projects SET title = ?, description = ? WHERE id = ?", [$title, $description, $projectId]);

            if (!empty($newMembers)) {
                foreach ($newMembers as $memberId) {
                    $memberId = (int) $memberId;
                    if ($memberId > 0) {
                        $isAlreadyMember = $db->single("SELECT project_id FROM project_members WHERE project_id = ? AND user_id = ?", [$projectId, $memberId]);
                        if (!$isAlreadyMember) {
                            $db->insert('project_members', ['project_id' => $projectId, 'user_id' => $memberId, 'role_id' => 2]);
                        }
                    }
                }
            }
            
            $db->commit();
            setFlash("success", "Project updated successfully.");
            header("Location: project_detail.php?id=" . $projectId);
            exit;

        } catch (Exception $e) {
            $db->rollBack();
            setFlash("error", "Database error: " . $e->getMessage());
        }
    }
}

$project = $db->single("SELECT * FROM projects WHERE id = ?", [$projectId]);
$currentMembers = $db->all("SELECT u.id, u.username, u.email, pm.role_id FROM users u JOIN project_members pm ON u.id = pm.user_id WHERE pm.project_id = ?", [$projectId]);

$pageTitle = "Edit Project";

include 'components/head.php';
?>

<div class="dashboard-wrapper">
    <div class="dashboard-container">
        <?php include 'components/sidebar.php'; ?>

        <main class="main-content">
            <?php include 'components/header.php'; ?>

            <div class="content">
                <div class="content-header">
                    <h1>Edit Project</h1>
                    <div class="content-meta">
                        <span>Update project details</span>
                    </div>
                </div>

                <div class="form-container" style="max-width: 800px;">
                    <form action="" method="post">
                        
                        <div class="card-section" style="margin-bottom: 2rem; background: white; padding: 1.5rem; border-radius: 12px; border: 1px solid var(--border-color);">
                            <div class="form-group" style="margin-bottom: 1.5rem;">
                                <label for="title" style="display: block; margin-bottom: 0.5rem; font-size: 0.9rem; font-weight: 500;">Project Title</label>
                                <input type="text" name="title" id="title" value="<?= e($project['title']) ?>" required class="panel-input">
                            </div>
                            <div class="form-group" style="margin-bottom: 1.5rem;">
                                <label for="description" style="display: block; margin-bottom: 0.5rem; font-size: 0.9rem; font-weight: 500;">Description</label>
                                <textarea name="description" id="description" rows="4" class="panel-input" style="resize: vertical; min-height: 100px;"><?= e($project['description']) ?></textarea>
                            </div>
                        </div>

                        <div class="card-section" style="margin-top: 2rem; background: white; padding: 1.5rem; border-radius: 12px; border: 1px solid var(--border-color);">
                            <h4 style="margin-bottom: 1rem; font-size: 1.1rem;">Manage Members</h4>
                            
                            <div class="member-management-list" style="display: flex; flex-direction: column; gap: 1rem;">
                                <?php foreach ($currentMembers as $member): ?>
                                    <div class="member-item" id="member-<?= $member['id'] ?>" style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 8px;">
                                        <div class="member-info" style="display: flex; align-items: center; gap: 1rem;">
                                            <div class="avatar"><?= strtoupper(substr($member['username'], 0, 1)) ?></div>
                                            <div>
                                                <span class="name" style="font-weight: 600; display: block;"><?= e($member['username']) ?></span>
                                                <span class="email text-secondary" style="font-size: 0.8rem; color: var(--text-muted);"><?= e($member['email']) ?></span>
                                            </div>
                                            <?php if ($member['role_id'] == 1): ?>
                                                <span class="role-badge" style="font-size: 0.75rem; padding: 0.2rem 0.6rem; background: #e0f2fe; color: #0369a1; border-radius: 99px; font-weight: 600;"><i class="fa-solid fa-crown"></i> Manager</span>
                                            <?php endif; ?>
                                        </div>
                                        <?php if ($member['role_id'] != 1): ?>
                                            <button type="button" class="btn-remove-member" data-user-id="<?= $member['id'] ?>" data-project-id="<?= $projectId ?>" style="background: transparent; border: 1px solid #fecaca; color: #ef4444; padding: 0.4rem 0.8rem; border-radius: 6px; cursor: pointer;"><i class="fa-solid fa-trash"></i> Remove</button>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <hr style="margin: 1.5rem 0; border-color: var(--border-color); border-style: solid; border-width: 1px 0 0 0;">

                            <div class="form-group" style="position: relative; margin-bottom: 1.5rem;">
                                <label for="user-search" style="display: block; margin-bottom: 0.5rem; font-size: 0.9rem; font-weight: 500;">Add New Member</label>
                                <input type="text" id="user-search" placeholder="Search by username or email..." autocomplete="off" class="panel-input">
                                <div id="search-results" style="display: none; position: absolute; width: 100%; background: white; border: 1px solid var(--border-color); border-top: none; z-index: 1000; max-height: 200px; overflow-y: auto; box-shadow: 0 4px 6px rgba(0,0,0,0.1); border-radius: 0 0 8px 8px;"></div>
                            </div>
                            
                            <div id="selected-members-container" style="display: flex; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 1.5rem;"></div>
                            
                            <div id="hidden-inputs-container"></div>
                        </div>
                        
                        <div class="form-actions" style="margin-top: 2rem; display: flex; gap: 1rem;">
                            <button type="submit" class="btn-new-task">Save Changes</button>
                            <a href="project_detail.php?id=<?= $projectId ?>" class="btn-new-task" style="background: white; color: var(--text-main); border: 1px solid var(--border-color);">Cancel</a>
                        </div>

                    </form>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
    const searchInput = document.getElementById('user-search');
    const resultsContainer = document.getElementById('search-results');
    const selectedContainer = document.getElementById('selected-members-container');
    const hiddenInputsContainer = document.getElementById('hidden-inputs-container');
    const selectedUserIds = new Set(<?= json_encode(array_column($currentMembers, 'id')) ?>);

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
            if (selectedUserIds.has(user.id)) return;
            
            const div = document.createElement('div');
            div.className = 'search-item';
            div.style.padding = '0.75rem 1rem';
            div.style.cursor = 'pointer';
            div.style.borderBottom = '1px solid var(--border-color)';
            div.innerHTML = `<strong>${escapeHtml(user.username)}</strong> <small class="text-secondary">(${escapeHtml(user.email)})</small>`;
            
            div.addEventListener('mouseenter', () => {
                div.style.backgroundColor = '#f9fafb';
            });
            div.addEventListener('mouseleave', () => {
                div.style.backgroundColor = 'white';
            });
            
            div.addEventListener('click', () => {
                addUser(user);
                searchInput.value = '';
                resultsContainer.style.display = 'none';
            });
            resultsContainer.appendChild(div);
        });
        resultsContainer.style.display = users.length > 0 ? 'block' : 'none';
    }

    function addUser(user) {
        selectedUserIds.add(user.id);

        const badge = document.createElement('div');
        badge.className = 'member-badge';
        badge.id = `badge-for-user-${user.id}`;
        badge.style.display = 'inline-flex';
        badge.style.alignItems = 'center';
        badge.style.gap = '0.5rem';
        badge.style.backgroundColor = '#e0f2fe';
        badge.style.color = '#0369a1';
        badge.style.padding = '0.25rem 0.75rem';
        badge.style.borderRadius = '99px';
        badge.style.fontSize = '0.85rem';
        badge.style.fontWeight = '500';
        
        badge.innerHTML = `<span>${escapeHtml(user.username)}</span><span class="remove-btn" data-id="${user.id}" style="cursor: pointer; font-weight: bold;">&times;</span>`;
        
        const hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = 'members[]';
        hiddenInput.value = user.id;
        hiddenInput.id = `input-for-user-${user.id}`;

        badge.querySelector('.remove-btn').addEventListener('click', () => {
            removeUser(user.id);
        });

        selectedContainer.appendChild(badge);
        hiddenInputsContainer.appendChild(hiddenInput);
    }

    function removeUser(userId) {
        selectedUserIds.delete(userId);
        
        const badge = document.getElementById(`badge-for-user-${userId}`);
        if (badge) badge.remove();

        const hiddenInput = document.getElementById(`input-for-user-${userId}`);
        if (hiddenInput) hiddenInput.remove();
    }

    document.querySelectorAll('.btn-remove-member').forEach(button => {
        button.addEventListener('click', function() {
            const userId = this.dataset.userId;
            const projectId = this.dataset.projectId;
            
            if (confirm('Are you sure you want to remove this member?')) {
                fetch('api/api.php?action=remove_project_member', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ user_id: userId, project_id: projectId })
                })
                .then(response => response.json())
                .then(result => {
                    if (result.status === 'success') {
                        document.getElementById(`member-${userId}`).remove();
                        selectedUserIds.delete(parseInt(userId));
                    } else {
                        alert('Error: ' + result.message);
                    }
                });
            }
        });
    });
    
    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target)) {
            resultsContainer.style.display = 'none';
        }
    });

    function escapeHtml(text) {
        if (!text) return '';
        return text.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
    }
</script>
</body>
</html>
