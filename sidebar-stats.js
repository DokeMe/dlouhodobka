document.addEventListener('DOMContentLoaded', function() {
    fetchSidebarStats();
    
    setInterval(fetchSidebarStats, 60000);
});

async function fetchSidebarStats() {
    try {
        const response = await fetch('api/api.php?action=get_dashboard_data');
        const result = await response.json();

        if (result.status === 'success') {
            const stats = result.data.stats;
            
            updateBadge('nav-badge-projects', stats.total_projects);
            updateBadge('nav-badge-tasks', stats.total_tasks);
        }
    } catch (error) {
        console.error('Failed to load sidebar stats:', error);
    }
}

function updateBadge(elementId, count) {
    const badge = document.getElementById(elementId);
    if (!badge) return;

    if (count > 0) {
        badge.textContent = count;
        badge.style.display = 'inline-flex';
    } else {
        badge.style.display = 'none';
    }
}
