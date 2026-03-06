function formatDate(dateString) {
    if (!dateString) return 'N/A';
    const options = { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' };
    return new Date(dateString).toLocaleDateString('en-US', options);
}

function escapeHtml(text) {
    if (text === null || typeof text === 'undefined') return '';
    return text.toString()
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

async function openTaskDetails(taskId) {
    const rightPanel = document.getElementById('right-panel');
    const panelContent = document.getElementById('panel-content');
    const panelIdText = document.getElementById('panel-id-text');

    if (!rightPanel || !panelContent || !panelIdText) return;

    rightPanel.classList.add('open');
    panelContent.innerHTML = '<div class="loading">Loading details...</div>';

    try {
        const response = await fetch(`api/api.php?action=get_task_detail&id=${taskId}`);
        const result = await response.json();

        if (result.status === 'success') {
            const task = result.data;
            panelIdText.textContent = `Task #${task.id}`;
            const isCompleted = task.status_id == 3;
            
            const toggleBtnClass = isCompleted ? 'btn btn-secondary' : 'btn btn-success';

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
                            <span class="status-badge ${task.status_id == 3 ? 'status-paid' : 'status-pending'}">${task.status_id == 3 ? '✓ Completed' : '⏱ Pending'}</span>
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
                            <span class="detail-value">${formatDate(task.deadline)}</span>
                        </div>
                    </div>
                </div>
                <div class="panel-section" style="display: flex; gap: 10px; flex-direction: column;">
                     <button onclick="toggleTaskCompletion(${task.id}, ${!isCompleted})" class="${toggleBtnClass}">
                        ${isCompleted ? 'Mark as Incomplete' : 'Mark as Done'}
                    </button>
                    <a href="task_edit.php?id=${task.id}" class="btn btn-outline" style="width: 100%;">Edit Task</a>
                    <a href="task_delete.php?id=${task.id}" class="btn btn-danger-outline" onclick="return confirm('Are you sure?');" style="width: 100%; justify-content: center;">Delete Task</a>
                </div>
            `;
        } else {
            panelContent.innerHTML = `<div class="empty-state">${escapeHtml(result.message)}</div>`;
        }
    } catch (error) {
        panelContent.innerHTML = '<div class="empty-state">Failed to load details.</div>';
    }
}

async function toggleTaskCompletion(taskId, isCompleted) {
    try {
        await fetch('api/api.php?action=toggle_task_completion', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ task_id: taskId, completed: isCompleted })
        });
        if (typeof window.fetchTasks === 'function') {
            await window.fetchTasks();
        }
        await openTaskDetails(taskId);
    } catch (error) {
        console.error('Error toggling task:', error);
    }
}

function closeRightPanel() {
    const rightPanel = document.getElementById('right-panel');
    if (!rightPanel) return;
    
    rightPanel.classList.remove('open');
    setTimeout(() => {
        const panelContent = document.getElementById('panel-content');
        const panelIdText = document.getElementById('panel-id-text');
        if (panelContent) panelContent.innerHTML = '<div class="empty-state">Select a task to view details.</div>';
        if (panelIdText) panelIdText.textContent = 'Select Item';
    }, 400);
}

function goBack() {
    if (document.referrer.includes(window.location.host)) {
        history.back();
    } else {
        window.location.href = 'index.php';
    }
}

window.openTaskDetails = openTaskDetails;
window.toggleTaskCompletion = toggleTaskCompletion;
window.closeRightPanel = closeRightPanel;
window.goBack = goBack;
