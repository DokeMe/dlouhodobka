<?php
require_once 'classes/database.php';
require_once 'includes/functions.php';

$db = new Database();
requireLogin();

$userId = $_SESSION['user_id'];
$isAdmin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
$pageTitle = "Projects";

include 'components/head.php';
?>

<div class="dashboard-wrapper">
    <div class="dashboard-container">
        <?php include 'components/sidebar.php'; ?>

        <main class="main-content">
            <?php include 'components/header.php'; ?>

            <div class="content">
                <div class="content-header">
                    <h1>Projects</h1>
                    <div class="content-meta">
                        <span id="current-date"></span>
                    </div>
                </div>

                <div class="tasks-toolbar" style="justify-content: flex-end;">
                    <div class="toolbar-actions">
                        <a href="create_project.php" class="btn-new-task">
                            <i class="fa-solid fa-plus"></i> New Project
                        </a>
                    </div>
                </div>

                <div id="projects-container" class="project-list">
                    <div class="loading">Loading projects...</div>
                </div>
            </div>
        </main>

        <aside class="right-panel" id="right-panel">
            <div class="panel-header">
                <div class="panel-id">
                    <span id="panel-id-text">Project Details</span>
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
                <div class="empty-state">Select a project to view details.</div>
            </div>
        </aside>
    </div>
</div>

<script>
    const dateOptions = { year: 'numeric', month: 'short', day: 'numeric' };
    document.getElementById('current-date').textContent = 'Today: ' + new Date().toLocaleDateString('en-US', dateOptions);

    let allProjects = [];
    const container = document.getElementById('projects-container');

    async function fetchProjects() {
        try {
            const response = await fetch('api/api.php?action=get_all_projects');
            const result = await response.json();
            
            if (result.status === 'success') {
                allProjects = result.data;
                renderProjects(allProjects);
            } else {
                container.innerHTML = `<div class="empty-state">Error loading projects.</div>`;
            }
        } catch (error) {
            console.error('Fetch Error:', error);
            container.innerHTML = `<div class="empty-state">Network error.</div>`;
        }
    }

    function renderProjects(projects) {
        container.innerHTML = '';

        if (projects.length === 0) {
            container.innerHTML = `
                <div class="empty-state" style="grid-column: 1/-1;">
                    <p>No projects found.</p>
                </div>`;
            return;
        }

        projects.forEach((project, index) => {
            const delay = index * 0.05;
            const roleBadge = project.is_manager 
                ? '<span class="project-role-badge role-manager">Manager</span>' 
                : '<span class="project-role-badge role-member">Member</span>';

            const html = `
                <div class="project-item" style="animation: slideUpFade 0.3s ease-out forwards; animation-delay: ${delay}s" onclick="window.location.href='project_detail.php?id=${project.id}'">
                    ${roleBadge}
                    <div class="project-icon-box">${project.title.charAt(0).toUpperCase()}</div>
                    <div class="project-info">
                        <span class="project-name">${escapeHtml(project.title)}</span>
                        <span class="project-desc">${escapeHtml(project.description || 'No description')}</span>
                    </div>
                    <div class="project-actions">
                        <div class="task-badge badge-cyan" style="font-size: 0.75rem;">
                            <i class="fa-solid fa-users"></i> ${project.member_count || 0}
                        </div>
                        <div class="task-badge badge-green" style="font-size: 0.75rem;">
                            <i class="fa-solid fa-check"></i> ${project.task_completed || 0}/${project.task_total || 0}
                        </div>
                    </div>
                </div>`;
            container.innerHTML += html;
        });
    }
    
    function closeRightPanel() {
        const rightPanel = document.getElementById('right-panel');
        rightPanel.classList.remove('open');
    }

    fetchProjects();
</script>
</body>
</html>
