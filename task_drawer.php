<div id="task-drawer-backdrop" class="drawer-backdrop"></div>
<aside id="task-drawer" class="task-drawer">
    <div class="drawer-header">
        <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
            <div style="display: flex; gap: 10px;">
                <button type="button" id="drawer-close-btn" class="btn-icon-close" style="font-size: 1.5rem; cursor: pointer; background: none; border: none;">&times;</button>
                <a id="drawer-fullscreen-link" class="btn-icon-expand" title="Open Full Page" style="font-size: 1.2rem; cursor: pointer; text-decoration: none;">↗</a>
            </div>
            <span id="drawer-status-badge" class="tag" style="font-size: 0.8rem; padding: 4px 12px; border-radius: 99px;"></span>
        </div>

        <div class="drawer-meta" style="margin-top: 1rem; display: flex; justify-content: space-between; font-size: 0.85rem;">
            <span id="drawer-project" class="drawer-project-badge" style="background: #f3f4f6; padding: 2px 8px; border-radius: 4px; font-weight: 600;"></span>
            <span id="drawer-id" class="text-muted" style="color: #6b7280;"></span>
        </div>
    </div>
    
    <div id="drawer-content-loader" class="drawer-loader" style="padding: 2rem; text-align: center; color: #6b7280;">
        <p>Loading Task...</p>
    </div>

    <div id="drawer-content-main" class="drawer-content" style="display: none; padding: 2rem; overflow-y: auto; flex-grow: 1;">
        
        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; margin-bottom: 1.5rem;">
            <h2 id="drawer-title" style="margin: 0; font-size: 1.5rem; line-height: 1.2; color: var(--text-main);"></h2>
            
            <button type="button" id="drawer-toggle-status-btn" class="btn btn-sm btn-white">
                Mark as Done
            </button>
        </div>
        
        <div class="drawer-section" style="margin-bottom: 2rem;">
            <label style="display: block; font-size: 0.75rem; font-weight: 700; color: #6b7280; margin-bottom: 0.5rem; text-transform: uppercase;">Priority</label>
            <span id="drawer-priority" class="tag" style="padding: 4px 10px; border-radius: 99px; font-size: 0.75rem; font-weight: 600; background: #e5e7eb;"></span>
        </div>

        <div class="drawer-section" style="margin-bottom: 2rem;">
            <label style="display: block; font-size: 0.75rem; font-weight: 700; color: #6b7280; margin-bottom: 0.5rem; text-transform: uppercase;">Description</label>
            <p id="drawer-description" class="text-desc" style="line-height: 1.6; color: var(--text-main);"></p>
        </div>

        <div class="drawer-section">
            <label style="display: block; font-size: 0.75rem; font-weight: 700; color: #6b7280; margin-bottom: 0.5rem; text-transform: uppercase;">Details</label>
            <ul class="detail-list" style="list-style: none; padding: 0;">
                <li style="display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid #f3f4f6;">
                    <strong>Assignee:</strong> <span id="drawer-assignee"></span>
                </li>
                <li style="display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid #f3f4f6;">
                    <strong>Deadline:</strong> <span id="drawer-deadline"></span>
                </li>
            </ul>
        </div>
    </div>

    <div id="drawer-footer" class="drawer-footer" style="display: none; padding: 1.5rem 2rem; border-top: 1px solid #e5e7eb; background: #f9fafb; justify-content: space-between;">
        <button type="button" id="drawer-delete-btn" class="btn btn-danger-outline">Delete Task</button>
        <button type="button" id="drawer-edit-btn" class="btn btn-primary">Edit Task</button>
    </div>
</aside>

<script>
    let drawerCurrentTaskId = null;
    let drawerCurrentStatusId = null;
    let drawerStatusChanged = false;

    window.openTaskDrawer = async function(taskId) {
        const drawer = document.getElementById('task-drawer');
        const backdrop = document.getElementById('task-drawer-backdrop');
        const loader = document.getElementById('drawer-content-loader');
        const mainContent = document.getElementById('drawer-content-main');
        const footer = document.getElementById('drawer-footer');
        
        const editBtn = document.getElementById('drawer-edit-btn');
        const deleteBtn = document.getElementById('drawer-delete-btn');
        const toggleStatusBtn = document.getElementById('drawer-toggle-status-btn');

        drawerCurrentTaskId = null;
        drawerCurrentStatusId = null;
        drawerStatusChanged = false;

        drawer.classList.add('open');
        backdrop.classList.add('open');
        
        loader.style.display = 'block';
        mainContent.style.display = 'none';
        footer.style.display = 'none';
        
        editBtn.disabled = true;
        deleteBtn.disabled = true;
        toggleStatusBtn.disabled = true;
        toggleStatusBtn.style.opacity = '0.5';

        try {
            const response = await fetch(`api/api.php?action=get_task_detail&id=${taskId}`);
            const result = await response.json();

            if (result.status === 'success') {
                const task = result.data;
                drawerCurrentTaskId = task.id;
                drawerCurrentStatusId = task.status_id;

                document.getElementById('drawer-title').textContent = escapeHtml(task.title);
                document.getElementById('drawer-project').textContent = escapeHtml(task.project_title);
                document.getElementById('drawer-id').textContent = `#${task.id}`;
                document.getElementById('drawer-description').textContent = escapeHtml(task.description) || 'No description provided.';
                document.getElementById('drawer-assignee').textContent = escapeHtml(task.assignee_name) || 'Unassigned';
                document.getElementById('drawer-deadline').textContent = formatDate(task.deadline);
                
                const statusBadge = document.getElementById('drawer-status-badge');
                statusBadge.textContent = escapeHtml(task.status_label) || 'Unknown';
                if (task.status_id == 3) {
                    statusBadge.style.backgroundColor = '#d1fae5';
                    statusBadge.style.color = '#065f46';
                } else {
                    statusBadge.style.backgroundColor = '#e0f2fe';
                    statusBadge.style.color = '#075985';
                }
                
                const priorityEl = document.getElementById('drawer-priority');
                priorityEl.textContent = escapeHtml(task.priority_label) || 'Normal';
                
                document.getElementById('drawer-fullscreen-link').href = `task_detail.php?id=${task.id}`;

                updateDrawerToggleButton(drawerCurrentStatusId == 3);

                loader.style.display = 'none';
                mainContent.style.display = 'block';
                footer.style.display = 'flex';
                
                editBtn.disabled = false;
                deleteBtn.disabled = false;
                toggleStatusBtn.disabled = false;
                toggleStatusBtn.style.opacity = '1';

            } else {
                loader.innerHTML = `<p style="color: red;">Error: ${result.message}</p>`;
            }
        } catch (error) {
            console.error('Drawer Error:', error);
            loader.innerHTML = '<p style="color: red;">Failed to load task details.</p>';
        }
    };

    window.closeTaskDrawer = function() {
        document.getElementById('task-drawer').classList.remove('open');
        document.getElementById('task-drawer-backdrop').classList.remove('open');
        
        if (drawerStatusChanged) {
            location.reload();
        }
    };

    function updateDrawerToggleButton(isDone) {
        const btn = document.getElementById('drawer-toggle-status-btn');
        if (isDone) {
            btn.textContent = 'Mark as Incomplete';
            btn.className = 'btn btn-sm btn-white';
            btn.style = '';
        } else {
            btn.textContent = 'Mark as Done';
            btn.className = 'btn btn-sm btn-primary';
            btn.style = '';
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        const editBtn = document.getElementById('drawer-edit-btn');
        const deleteBtn = document.getElementById('drawer-delete-btn');
        const toggleStatusBtn = document.getElementById('drawer-toggle-status-btn');
        const closeBtn = document.getElementById('drawer-close-btn');
        const backdrop = document.getElementById('task-drawer-backdrop');

        editBtn.addEventListener('click', function() {
            if (drawerCurrentTaskId) {
                window.location.href = `task_edit.php?id=${drawerCurrentTaskId}`;
            }
        });

        deleteBtn.addEventListener('click', function() {
            if (drawerCurrentTaskId && confirm('Are you sure you want to delete this task?')) {
                window.location.href = `task_delete.php?id=${drawerCurrentTaskId}`;
            }
        });

        toggleStatusBtn.addEventListener('click', async function() {
            if (!drawerCurrentTaskId) return;

            const isCurrentlyDone = drawerCurrentStatusId == 3;
            const newIsDone = !isCurrentlyDone;

            updateDrawerToggleButton(newIsDone);

            try {
                const response = await fetch('api/api.php?action=toggle_task_completion', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ task_id: drawerCurrentTaskId, completed: newIsDone })
                });
                const result = await response.json();

                if (result.status === 'success') {
                    drawerCurrentStatusId = result.data.new_status_id;
                    drawerStatusChanged = true;
                    
                    const statusBadge = document.getElementById('drawer-status-badge');
                    if (drawerCurrentStatusId == 3) {
                        statusBadge.textContent = 'Done';
                        statusBadge.style.backgroundColor = '#d1fae5';
                        statusBadge.style.color = '#065f46';
                    } else {
                        statusBadge.textContent = 'To Do';
                        statusBadge.style.backgroundColor = '#e0f2fe';
                        statusBadge.style.color = '#075985';
                    }
                } else {
                    alert('Failed to update status: ' + result.message);
                    updateDrawerToggleButton(isCurrentlyDone);
                }
            } catch (error) {
                console.error('API Error:', error);
                alert('Network error.');
                updateDrawerToggleButton(isCurrentlyDone);
            }
        });

        closeBtn.addEventListener('click', window.closeTaskDrawer);
        backdrop.addEventListener('click', window.closeTaskDrawer);
    });
</script>
