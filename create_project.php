<?php
require_once 'classes/database.php';
require_once 'includes/functions.php';

$db = new Database();
requireLogin();

$userId = $_SESSION['user_id'];
$isAdmin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $userId = $_SESSION['user_id'];
    $selectedMembers = $_POST['members'] ?? [];

    if (empty($title)) {
        setFlash("error", "Project title is required.");
    } else {
        $db->beginTransaction();
        try {
            $projectId = $db->insert('projects', [
                'title' => $title,
                'description' => $description,
                'created_by' => $userId
            ]);

            $db->insert('project_members', [
                'project_id' => $projectId,
                'user_id' => $userId,
                'role_id' => 1
            ]);

            if (!empty($selectedMembers) && is_array($selectedMembers)) {
                foreach ($selectedMembers as $memberId) {
                    if (filter_var($memberId, FILTER_VALIDATE_INT)) {
                        $db->insert('project_members', [
                            'project_id' => $projectId,
                            'user_id' => $memberId,
                            'role_id' => 2
                        ]);
                    }
                }
            }
            
            $db->commit();
            setFlash("success", "Project created successfully.");
            header("Location: project_detail.php?id=" . $projectId);
            exit;

        } catch (Exception $e) {
            $db->rollBack();
            setFlash("error", "Database error: " . $e->getMessage());
        }
    }
}

$pageTitle = "Create New Project";

include 'components/head.php';
?>

<div class="dashboard-wrapper">
    <div class="dashboard-container">
        <?php include 'components/sidebar.php'; ?>

        <main class="main-content">
            <?php include 'components/header.php'; ?>

            <div class="content">
                <div class="content-header">
                    <h1>Create New Project</h1>
                    <div class="content-meta">
                        <span>Start a new collaboration</span>
                    </div>
                </div>

                <div class="form-container" style="max-width: 800px;">
                    <div class="card-section" style="margin-bottom: 2rem; background: white; padding: 1.5rem; border-radius: 12px; border: 1px solid var(--border-color);">
                        <form action="" method="post">
                            <div class="form-group" style="margin-bottom: 1.5rem;">
                                <label for="title" style="display: block; margin-bottom: 0.5rem; font-size: 0.9rem; font-weight: 500;">Project Title</label>
                                <input type="text" name="title" id="title" required class="panel-input">
                            </div>

                            <div class="form-group" style="margin-bottom: 1.5rem;">
                                <label for="description" style="display: block; margin-bottom: 0.5rem; font-size: 0.9rem; font-weight: 500;">Description (Optional)</label>
                                <textarea name="description" id="description" rows="4" class="panel-input" style="resize: vertical; min-height: 100px;"></textarea>
                            </div>

                            <div class="form-group" style="position: relative; margin-bottom: 1.5rem;">
                                <label for="user-search" style="display: block; margin-bottom: 0.5rem; font-size: 0.9rem; font-weight: 500;">Add Team Members</label>
                                <input type="text" id="user-search" placeholder="Search by username or email..." autocomplete="off" class="panel-input">
                                <div id="search-results" style="display: none; position: absolute; width: 100%; background: white; border: 1px solid var(--border-color); border-top: none; z-index: 1000; max-height: 200px; overflow-y: auto; box-shadow: 0 4px 6px rgba(0,0,0,0.1); border-radius: 0 0 8px 8px;"></div>
                            </div>

                            <div id="selected-members-container" style="display: flex; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 1.5rem;">
                            </div>
                            
                            <div class="form-actions" style="display: flex; gap: 1rem;">
                                <button type="submit" class="btn-new-task" >Create Project</button>
                                <a href="index.php" class="btn-new-task" style="background: white; color: var(--text-main); border: 1px solid var(--border-color);">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
    const searchInput = document.getElementById('user-search');
    const resultsContainer = document.getElementById('search-results');
    const selectedContainer = document.getElementById('selected-members-container');
    
    const selectedUserIds = new Set();

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
                })
                .catch(err => console.error(err));
        }, 300);
    });

    function displayResults(users) {
        resultsContainer.innerHTML = '';
        let hasResults = false;

        users.forEach(user => {
            if (selectedUserIds.has(user.id)) return;

            hasResults = true;
            const div = document.createElement('div');
            div.className = 'search-item';
            div.style.padding = '0.75rem 1rem';
            div.style.cursor = 'pointer';
            div.style.borderBottom = '1px solid var(--border-color)';
            div.innerHTML = `<strong>${escapeHtml(user.username)}</strong> <small style="color: var(--text-muted);">(${escapeHtml(user.email)})</small>`;
            
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

        resultsContainer.style.display = hasResults ? 'block' : 'none';
    }

    function addUser(user) {
        selectedUserIds.add(user.id);

        const badge = document.createElement('div');
        badge.className = 'member-badge';
        badge.style.display = 'inline-flex';
        badge.style.alignItems = 'center';
        badge.style.gap = '0.5rem';
        badge.style.backgroundColor = '#e0f2fe';
        badge.style.color = '#0369a1';
        badge.style.padding = '0.25rem 0.75rem';
        badge.style.borderRadius = '99px';
        badge.style.fontSize = '0.85rem';
        badge.style.fontWeight = '500';
        
        badge.innerHTML = `
            <span>${escapeHtml(user.username)}</span>
            <span class="remove-btn" data-id="${user.id}" style="cursor: pointer; font-weight: bold;">&times;</span>
            <input type="hidden" name="members[]" value="${user.id}">
        `;

        badge.querySelector('.remove-btn').addEventListener('click', (e) => {
            const userId = parseInt(e.target.getAttribute('data-id'));
            selectedUserIds.delete(userId);
            badge.remove();
        });

        selectedContainer.appendChild(badge);
    }

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
