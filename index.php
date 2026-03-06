<?php
require_once 'classes/database.php';
require_once 'includes/functions.php';

$db = new Database();
requireLogin();

$userId = $_SESSION['user_id'];
$isAdmin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
$pageTitle = "Dashboard";

include 'components/head.php';
?>

<div class="dashboard-wrapper">
    <div class="dashboard-container">
        <?php include 'components/sidebar.php'; ?>

        <main class="main-content">
            <?php include 'components/header.php'; ?>

            <div class="content">
                <div class="content-header">
                    <h1>Dashboard</h1>
                    <div class="content-meta">
                        <span id="current-date"></span>
                    </div>
                </div>

                <div class="dashboard-grid">
                    
                    <div class="dashboard-col col-projects">
                        <div class="dashboard-section">
                            <div class="section-header-simple">
                                <h3>My Projects <span id="project-count-badge" class="count-badge">0</span></h3>
                                <a href="create_project.php" class="icon-link" title="New Project"><i class="fa-solid fa-plus"></i></a>
                            </div>
                            <div id="projects-container" class="project-list-simple">
                                <div class="loading">Loading...</div>
                            </div>
                        </div>
                    </div>

                    <div class="dashboard-col col-tasks">
                        
                        <div class="stats-row-simple" id="stats-container">
                        </div>

                        <div class="tasks-scroll-area">
                            
                            <div id="section-overdue" class="alert-section" style="display: none;">
                                <div class="section-header-simple">
                                    <h3 style="color: #b91c1c;"><i class="fa-solid fa-triangle-exclamation"></i> Overdue Tasks</h3>
                                </div>
                                <div id="overdue-tasks-container" class="tasks-list"></div>
                            </div>

                            <div class="dashboard-section inprogress-section">
                                <div class="section-header-simple">
                                    <h3 style="color: #92400e;"><i class="fa-regular fa-clock"></i> In Progress</h3>
                                    <a href="create_task.php" class="btn-new-task-small"><i class="fa-solid fa-plus"></i> Add Task</a>
                                </div>
                                <div id="upcoming-tasks-container" class="tasks-list">
                                    <div class="loading">Loading tasks...</div>
                                </div>
                            </div>

                            <div class="dashboard-section completed-section">
                                <div class="section-header-simple">
                                    <h3 style="color: #065f46;"><i class="fa-regular fa-circle-check"></i> Completed</h3>
                                </div>
                                <div id="completed-tasks-container" class="tasks-list"></div>
                            </div>
                            
                        </div>

                    </div>

                </div>
            </div>
        </main>

        <aside class="right-panel" id="right-panel">
            <div class="panel-header">
                <div class="panel-id">
                    <span id="panel-id-text">Select Item</span>
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
                <div class="empty-state">Select a task or project to view details.</div>
            </div>
        </aside>
    </div>
</div>

<script>
    const dateOptions = { year: 'numeric', month: 'short', day: 'numeric' };
    document.getElementById('current-date').textContent = 'Today: ' + new Date().toLocaleDateString('en-US', dateOptions);

    async function fetchDashboardData() {
        try {
            const response = await fetch('api/api.php?action=get_dashboard_data');
            const result = await response.json();

            if (result.status === 'success') {
                renderStats(result.data.stats, result.data.tasks);
                renderTasks(result.data.tasks);
                renderProjects(result.data.projects);
            }
        } catch (error) {
            console.error('Fetch Error:', error);
        }
    }

    function renderStats(stats, tasks) {
        const container = document.getElementById('stats-container');
        const total = stats.total_tasks;
        const completed = tasks ? tasks.filter(t => t.status_id == 3).length : 0;
        const overdue = stats.overdue_tasks;

        container.innerHTML = `
            <div class="stat-item-simple">
                <span class="stat-val">${total}</span>
                <span class="stat-lbl">Total</span>
            </div>
            <div class="stat-item-simple">
                <span class="stat-val">${completed} / ${total}</span>
                <span class="stat-lbl">Done</span>
            </div>
            <div class="stat-item-simple danger">
                <span class="stat-val">${overdue}</span>
                <span class="stat-lbl">Overdue</span>
            </div>
        `;
    }

    function renderTasks(tasks) {
        const overdueContainer = document.getElementById('overdue-tasks-container');
        const upcomingContainer = document.getElementById('upcoming-tasks-container');
        const completedContainer = document.getElementById('completed-tasks-container');
        const overdueSection = document.getElementById('section-overdue');

        overdueContainer.innerHTML = '';
        upcomingContainer.innerHTML = '';
        completedContainer.innerHTML = '';

        if (tasks.length === 0) {
            upcomingContainer.innerHTML = '<div class="empty-state">No tasks assigned.</div>';
            return;
        }

        const now = new Date();
        now.setHours(0,0,0,0);
        let hasOverdue = false;

        tasks.forEach(task => {
            const isCompleted = task.status_id == 3;
            let isOverdue = false;
            
            if (task.deadline && !isCompleted) {
                const deadlineDate = new Date(task.deadline);
                if (deadlineDate < now) isOverdue = true;
            }

            const deadlineHtml = task.deadline ? 
                `<span class="meta-date" style="${isOverdue ? 'color: #ef4444;' : ''}"><i class="fa-regular fa-calendar"></i> ${formatDate(task.deadline)}</span>` : '';
            
            const priorityClass = task.priority_label ? `tag-${task.priority_label.toLowerCase()}` : 'tag-normal';

            const html = `
                <div class="task-item ${isCompleted ? 'task-completed' : ''}" onclick="openTaskDetails(${task.id})">
                    <div class="task-content-wrapper">
                        <div class="task-info">
                            <span class="task-name">${escapeHtml(task.title)}</span>
                            <span class="task-meta">${escapeHtml(task.project_title)}</span>
                        </div>
                        <div class="task-status">
                            <span class="tag ${priorityClass}">${escapeHtml(task.priority_label || 'Normal')}</span>
                            ${deadlineHtml}
                        </div>
                    </div>
                </div>`;

            if (isCompleted) {
                completedContainer.innerHTML += html;
            } else if (isOverdue) {
                overdueContainer.innerHTML += html;
                hasOverdue = true;
            } else {
                upcomingContainer.innerHTML += html;
            }
        });

        overdueSection.style.display = hasOverdue ? 'block' : 'none';
        
        if (upcomingContainer.innerHTML === '') upcomingContainer.innerHTML = '<div class="empty-state">No upcoming tasks.</div>';
        if (completedContainer.innerHTML === '') completedContainer.innerHTML = '<div class="empty-state">No completed tasks.</div>';
    }

    function renderProjects(projects) {
        const container = document.getElementById('projects-container');
        const badge = document.getElementById('project-count-badge');
        
        container.innerHTML = '';
        badge.textContent = projects.length;

        if (projects.length === 0) {
            container.innerHTML = '<div class="empty-state">No projects.</div>';
            return;
        }

        projects.forEach(project => {
            const html = `
                <div class="project-row" onclick="window.location.href='project_detail.php?id=${project.id}'">
                    <div class="project-row-icon">${project.title.charAt(0).toUpperCase()}</div>
                    <span class="project-row-name">${escapeHtml(project.title)}</span>
                </div>`;
            container.innerHTML += html;
        });
    }

    window.openTaskDetails = async function(taskId) {
        const rightPanel = document.getElementById('right-panel');
        const panelContent = document.getElementById('panel-content');
        const panelIdText = document.getElementById('panel-id-text');
        
        rightPanel.classList.add('open');
        panelContent.innerHTML = '<div class="loading">Loading details...</div>';
        
        try {
            const response = await fetch(`api/api.php?action=get_task_detail&id=${taskId}`);
            const result = await response.json();

            if (result.status === 'success') {
                const task = result.data;
                panelIdText.textContent = `Task #${task.id}`;
                const isCompleted = task.status_id == 3;
                const statusClass = isCompleted ? 'status-paid' : 'status-pending';
                const statusText = isCompleted ? '✓ Completed' : '⏱ Pending';
                
                const toggleBtnClass = isCompleted ? 'new-task-btn' : 'btn btn-primary';
                const toggleBtnStyle = isCompleted ? '' : 'width: 100%;';

                panelContent.innerHTML = `
                    <div class="panel-section">
                        <h3>Description</h3>
                        <p style="font-size: 14px; color: #6b7280; line-height: 1.5;">${escapeHtml(task.description || 'No description provided.')}</p>
                    </div>

                    <div class="panel-section">
                        <h3>Details</h3>
                        <div class="detail-list">
                            <div class="detail-row">
                                <span class="detail-label">Project</span>
                                <span class="detail-value">${escapeHtml(task.project_title)}</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Status</span>
                                <span class="status-badge ${statusClass}">${statusText}</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Priority</span>
                                <span class="detail-value">${escapeHtml(task.priority_label || 'Normal')}</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Assignee</span>
                                <div class="detail-value">
                                    <div class="assignee">
                                        <div class="assignee-dot dot-blue"></div>
                                        <span>${escapeHtml(task.assignee_name || 'Unassigned')}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Deadline</span>
                                <span class="detail-value">${task.deadline ? formatDate(task.deadline) : '-'}</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="panel-section" style="display: flex; gap: 10px; flex-direction: column;">
                         <button onclick="toggleTaskCompletion(${task.id}, ${!isCompleted})" class="${toggleBtnClass}" style="${toggleBtnStyle}">
                            ${isCompleted ? 'Mark as Incomplete' : 'Mark as Done'}
                        </button>
                        <a href="task_edit.php?id=${task.id}" class="new-task-btn">Edit Task</a>
                        <a href="task_delete.php?id=${task.id}" class="btn btn-danger-outline" onclick="return confirm('Are you sure you want to delete this task?');" style="width: 100%; justify-content: center;">Delete Task</a>
                    </div>
                `;
            }
        } catch (error) {
            panelContent.innerHTML = '<div class="empty-state">Failed to load details.</div>';
        }
    };
    
    window.toggleTaskCompletion = async function(taskId, isCompleted) {
        try {
            await fetch('api/api.php?action=toggle_task_completion', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ task_id: taskId, completed: isCompleted })
            });
            fetchDashboardData();
            openTaskDetails(taskId);
        } catch (error) {
            console.error('Error toggling task:', error);
        }
    };
    
    function closeRightPanel() {
        const rightPanel = document.getElementById('right-panel');
        rightPanel.classList.remove('open');
        setTimeout(() => {
            document.getElementById('panel-content').innerHTML = '<div class="empty-state">Select a task to view details.</div>';
            document.getElementById('panel-id-text').textContent = 'Select Item';
        }, 400);
    }
    
    function escapeHtml(text) {
        if (!text) return '';
        return text.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
    }

    fetchDashboardData();
</script>
</body>
</html>
