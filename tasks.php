<?php
require_once 'classes/database.php';
require_once 'includes/functions.php';

$db = new Database();
requireLogin();

$userId = $_SESSION['user_id'];
$isAdmin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
$pageTitle = "My Tasks";

$statuses = $db->all("SELECT * FROM statuses");
$priorities = $db->all("SELECT * FROM priorities");

include 'components/head.php';
?>

<div class="dashboard-wrapper">
    <div class="dashboard-container">
        <?php include 'components/sidebar.php'; ?>

        <main class="main-content">
            <?php include 'components/header.php'; ?>

            <div class="content">
                <div class="content-header">
                    <h1>My Tasks</h1>
                    <div class="content-meta">
                        <span id="current-date"></span>
                    </div>
                </div>

                <div class="stats-grid" id="stats-container">
                </div>

                <div id="tasks-view" class="tasks-list">
                    <?php 
                        $fetchUrl = 'api/api.php?action=get_all_tasks'; 
                        include 'components/task_view.php'; 
                    ?>
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
                <div class="empty-state">Select a task to view details.</div>
            </div>
        </aside>
    </div>
</div>

<script>
    const dateOptions = { year: 'numeric', month: 'short', day: 'numeric' };
    document.getElementById('current-date').textContent = 'Today: ' + new Date().toLocaleDateString('en-US', dateOptions);

    window.calculateAndRenderStats = function(tasks) {
        const total = tasks.length;
        const completed = tasks.filter(t => t.status_id == 3).length;
        const overdue = tasks.filter(t => {
            if (!t.deadline || t.status_id == 3) return false;
            return new Date(t.deadline) < new Date().setHours(0,0,0,0);
        }).length;
        
        const container = document.getElementById('stats-container');
        container.innerHTML = `
            <div class="stat-card">
                <div class="stat-icon blue"><i class="fa-solid fa-list-check"></i></div>
                <div class="stat-info">
                    <h3>Total Tasks</h3>
                    <span class="stat-value">${total}</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green"><i class="fa-solid fa-check"></i></div>
                <div class="stat-info">
                    <h3>Completed</h3>
                    <span class="stat-value">${completed}</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon red"><i class="fa-solid fa-triangle-exclamation"></i></div>
                <div class="stat-info">
                    <h3>Overdue</h3>
                    <span class="stat-value">${overdue}</span>
                </div>
            </div>
        `;
    };

</script>
</body>
</html>
