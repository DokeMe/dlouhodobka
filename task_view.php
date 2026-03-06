<script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.js"></script>

<div class="view-switcher-container">
    <div class="view-switcher">
        <button class="view-btn active" data-view="board" title="Board View"><i class="fa-solid fa-table-columns"></i> Board</button>
        <button class="view-btn" data-view="list" title="List View"><i class="fa-solid fa-list"></i> List</button>
        <button class="view-btn" data-view="grid" title="Grid View"><i class="fa-solid fa-border-all"></i> Grid</button>
        <button class="view-btn" data-view="table" title="Table View"><i class="fa-solid fa-table"></i> Table</button>
    </div>
</div>

<div class="tasks-toolbar">
    
    <div class="filters-group">
        <div class="filter-item">
            <label for="status-filter">Status</label>
            <select id="status-filter">
                <option value="all">All</option>
                <option value="not_done">All except Done</option>
                <?php foreach ($statuses as $status): ?>
                    <option value="<?= $status['id'] ?>"><?= e($status['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="filter-item">
            <label for="priority-filter">Priority</label>
            <select id="priority-filter">
                <option value="all">All</option>
                <?php foreach ($priorities as $priority): ?>
                    <option value="<?= $priority['id'] ?>"><?= e($priority['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="filter-item">
            <label for="sort-by">Sort by</label>
            <select id="sort-by">
                <option value="deadline">Due Date</option>
                <option value="priority">Priority</option>
                <option value="created_at">Creation Date</option>
            </select>
        </div>
        <button id="reset-filters" class="btn-new-task" style="background: var(--secondary-gray); border-color: var(--secondary-gray); color: white; padding: 0.5rem 1rem; font-size: 0.85rem;">
            <i class="fa-solid fa-rotate-left"></i> Reset
        </button>
    </div>

    <div class="toolbar-actions">
        <a href="create_task.php<?= isset($projectId) ? '?project_id=' . $projectId : '' ?>" class="btn-new-task">
            <i class="fa-solid fa-plus"></i> New Task
        </a>
    </div>

</div>

<div id="tasks-container" class="tasks-container view-board">
    <div class="loading">Loading tasks...</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let allTasks = [];
    let currentView = 'board';

    const statusFilter = document.getElementById('status-filter');
    const priorityFilter = document.getElementById('priority-filter');
    const sortBy = document.getElementById('sort-by');
    const resetBtn = document.getElementById('reset-filters');
    const tasksContainer = document.getElementById('tasks-container');
    const viewBtns = document.querySelectorAll('.view-btn');

    const initialTasks = <?= isset($initialTasksJson) ? $initialTasksJson : 'null' ?>;
    const fetchUrl = "<?= isset($fetchUrl) ? $fetchUrl : '' ?>";

    function init() {
        if (initialTasks) {
            allTasks = initialTasks;
            if (typeof calculateAndRenderStats === 'function') {
                calculateAndRenderStats(allTasks);
            }
            renderFilteredTasks();
        } else if (fetchUrl) {
            fetchTasks();
        }
    }

    async function fetchTasks() {
        try {
            const response = await fetch(fetchUrl);
            const result = await response.json();
            if (result.status === 'success') {
                if (Array.isArray(result.data)) {
                    allTasks = result.data;
                } else if (result.data && Array.isArray(result.data.tasks)) {
                    allTasks = result.data.tasks;
                } else {
                    allTasks = [];
                }
                
                if (typeof calculateAndRenderStats === 'function') {
                    calculateAndRenderStats(allTasks);
                }
                renderFilteredTasks();
            } else {
                tasksContainer.innerHTML = `<div class="empty-state">Error loading tasks.</div>`;
            }
        } catch (error) {
            console.error('Fetch Error:', error);
            tasksContainer.innerHTML = `<div class="empty-state">Network error.</div>`;
        }
    }
    
    window.fetchTasks = fetchTasks;

    function renderFilteredTasks() {
        if (!Array.isArray(allTasks)) {
            console.error('allTasks is not an array:', allTasks);
            tasksContainer.innerHTML = `<div class="empty-state">Data error.</div>`;
            return;
        }

        const status = statusFilter.value;
        const priority = priorityFilter.value;
        const sort = sortBy.value;

        let filtered = allTasks.filter(task => {
            let matchesStatus = true;
            if (status === 'not_done') {
                matchesStatus = task.status_id != 3;
            } else if (status !== 'all') {
                matchesStatus = task.status_id == status;
            }
            
            const matchesPriority = priority === 'all' || task.priority_id == priority;
            return matchesStatus && matchesPriority;
        });

        filtered.sort((a, b) => {
            if (currentView === 'board') {
                if (a.priority_id !== b.priority_id) {
                    return (b.priority_id || 0) - (a.priority_id || 0);
                }
                if (!a.deadline) return 1;
                if (!b.deadline) return -1;
                return new Date(a.deadline) - new Date(b.deadline);
            }

            if (sort === 'deadline') {
                if (!a.deadline) return 1;
                if (!b.deadline) return -1;
                return new Date(a.deadline) - new Date(b.deadline);
            }
            if (sort === 'priority') {
                return (b.priority_id || 0) - (a.priority_id || 0);
            }
            return new Date(b.created_at) - new Date(a.created_at);
        });

        tasksContainer.innerHTML = '';
        tasksContainer.className = `tasks-container view-${currentView}`;

        if (filtered.length === 0 && currentView !== 'board') {
            tasksContainer.innerHTML = `<div class="empty-state">No tasks match the current filters.</div>`;
            return;
        }

        if (currentView === 'board') {
            renderBoardView(filtered);
        } else if (currentView === 'table') {
            renderTableView(filtered);
        } else {
            renderCardView(filtered);
        }
    }

    function renderBoardView(tasks) {
        const columns = [
            { id: 1, title: 'To Do', class: 'todo' },
            { id: 2, title: 'In Progress', class: 'inprogress' },
            { id: 3, title: 'Done', class: 'done' }
        ];

        columns.forEach(col => {
            const colTasks = tasks.filter(t => t.status_id == col.id);
            
            const colDiv = document.createElement('div');
            colDiv.className = `kanban-column col-${col.class}`;
            colDiv.innerHTML = `<div class="kanban-header ${col.class}">${col.title} <span class="count">${colTasks.length}</span></div>`;
            
            const tasksDiv = document.createElement('div');
            tasksDiv.className = 'kanban-tasks';
            tasksDiv.dataset.statusId = col.id;

            colTasks.forEach(task => {
                const card = document.createElement('div');
                card.className = 'kanban-card';
                card.dataset.taskId = task.id;
                
                let priorityColor = 'var(--border-color)';
                if (task.priority_id == 2) priorityColor = '#fbbf24';
                if (task.priority_id == 3) priorityColor = '#ef4444';

                card.style.borderLeft = `3px solid ${priorityColor}`;

                card.innerHTML = `
                    <div class="kanban-card-content">
                        <div class="card-title">${escapeHtml(task.title)}</div>
                        <div class="card-meta">
                            <span class="project-tag">${escapeHtml(task.project_title || '')}</span>
                        </div>
                        <div class="card-footer" style="margin-top: 8px; display: flex; justify-content: space-between; align-items: center;">
                            <span class="tag tag-${(escapeHtml(task.priority_label) || 'normal').toLowerCase()}">${escapeHtml(task.priority_label || 'Normal')}</span>
                            ${task.deadline ? `<span class="date-tag" style="font-size: 0.75rem; color: var(--text-muted);"><i class="fa-regular fa-clock"></i> ${formatDate(task.deadline)}</span>` : ''}
                        </div>
                    </div>
                    <div class="kanban-drag-handle">
                        <i class="fa-solid fa-grip-vertical"></i>
                    </div>
                `;

                card.querySelector('.kanban-card-content').onclick = () => openTaskDetails(task.id);

                tasksDiv.appendChild(card);
            });

            new Sortable(tasksDiv, {
                group: 'kanban',
                animation: 150,
                handle: '.kanban-drag-handle',
                ghostClass: 'sortable-ghost',
                forceFallback: true,
                fallbackOnBody: true,
                fallbackClass: 'sortable-fallback',
                onEnd: function (evt) {
                    const itemEl = evt.item;
                    const newStatusId = evt.to.dataset.statusId;
                    const taskId = itemEl.dataset.taskId;

                    if (evt.from !== evt.to) {
                        updateTaskStatus(taskId, newStatusId);
                    } else {
                        renderFilteredTasks();
                    }
                }
            });

            colDiv.appendChild(tasksDiv);
            tasksContainer.appendChild(colDiv);
        });
    }

    async function updateTaskStatus(taskId, statusId) {
        try {
            const response = await fetch('api/api.php?action=update_task_status', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ task_id: taskId, status_id: statusId })
            });
            const result = await response.json();
            
            if (result.status === 'success') {
                const task = allTasks.find(t => t.id == taskId);
                if (task) {
                    task.status_id = statusId;
                    if (typeof calculateAndRenderStats === 'function') {
                        calculateAndRenderStats(allTasks);
                    }
                    renderFilteredTasks();
                }
            } else {
                alert('Failed to update status: ' + result.message);
                if (fetchUrl) fetchTasks();
                else location.reload();
            }
        } catch (error) {
            console.error('API Error:', error);
            alert('Network error.');
        }
    }

    function renderCardView(tasks) {
        let html = '';
        tasks.forEach(task => {
            const isCompleted = task.status_id == 3;
            const completedClass = isCompleted ? 'task-completed' : '';
            
            html += `
                <div class="task-item ${completedClass}" onclick="openTaskDetails(${task.id})">
                    <div class="task-content-wrapper">
                        <div class="task-info">
                            <span class="task-name">${escapeHtml(task.title)}</span>
                            <span class="task-meta">${escapeHtml(task.project_title || '')}</span>
                        </div>
                        <div class="task-status">
                            <span class="tag tag-${(escapeHtml(task.priority_label) || 'normal').toLowerCase()}">${escapeHtml(task.priority_label || 'Normal')}</span>
                            ${task.deadline ? `<span class="meta-date"><i class="fa-regular fa-calendar"></i> ${formatDate(task.deadline)}</span>` : ''}
                        </div>
                    </div>
                </div>
            `;
        });
        tasksContainer.innerHTML = html;
    }

    function renderTableView(tasks) {
        let html = `
            <table class="tasks-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Project</th>
                        <th>Status</th>
                        <th>Priority</th>
                        <th>Due Date</th>
                    </tr>
                </thead>
                <tbody>
        `;
        
        tasks.forEach(task => {
            const isCompleted = task.status_id == 3;
            const completedStyle = isCompleted ? 'opacity: 0.6; text-decoration: line-through;' : '';
            
            html += `
                <tr onclick="openTaskDetails(${task.id})" style="${completedStyle}">
                    <td><strong>${escapeHtml(task.title)}</strong></td>
                    <td>${escapeHtml(task.project_title || '')}</td>
                    <td>${escapeHtml(task.status_label || 'Unknown')}</td>
                    <td><span class="tag tag-${(escapeHtml(task.priority_label) || 'normal').toLowerCase()}">${escapeHtml(task.priority_label || 'Normal')}</span></td>
                    <td>${task.deadline ? formatDate(task.deadline) : '-'}</td>
                </tr>
            `;
        });

        html += `</tbody></table>`;
        tasksContainer.innerHTML = html;
    }

    viewBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            viewBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            currentView = btn.dataset.view;
            renderFilteredTasks();
        });
    });

    statusFilter.addEventListener('change', renderFilteredTasks);
    priorityFilter.addEventListener('change', renderFilteredTasks);
    sortBy.addEventListener('change', renderFilteredTasks);
    
    resetBtn.addEventListener('click', () => {
        statusFilter.value = 'all';
        priorityFilter.value = 'all';
        sortBy.value = 'deadline';
        renderFilteredTasks();
    });

    init();
});
</script>
