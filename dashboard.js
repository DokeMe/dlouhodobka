document.addEventListener('DOMContentLoaded', function() {
    const mobileToggleBtn = document.getElementById('mobile-sidebar-toggle');
    const sidebar = document.getElementById('sidebar');
    const backdrop = document.getElementById('sidebar-backdrop');

    if (mobileToggleBtn && sidebar && backdrop) {
        mobileToggleBtn.addEventListener('click', function() {
            sidebar.classList.add('open');
            backdrop.classList.add('open');
        });

        backdrop.addEventListener('click', function() {
            sidebar.classList.remove('open');
            backdrop.classList.remove('open');
        });
    }

    const desktopToggleBtn = document.getElementById('sidebar-toggle-btn');
    
    const isCollapsed = localStorage.getItem('sidebar-collapsed') === 'true';
    if (isCollapsed && sidebar) {
        sidebar.classList.add('collapsed');
    }

    if (desktopToggleBtn && sidebar) {
        desktopToggleBtn.addEventListener('click', function() {
            sidebar.classList.toggle('collapsed');
            localStorage.setItem('sidebar-collapsed', sidebar.classList.contains('collapsed'));
        });
    }
});
