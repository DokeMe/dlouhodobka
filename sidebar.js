document.addEventListener('DOMContentLoaded', function () {
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('mainContent');
    const toggleButton = document.getElementById('sidebarToggle');

    if (sidebar && mainContent && toggleButton) {
        const toggleSidebar = () => {
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('collapsed');
            localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
        };

        if (localStorage.getItem('sidebarCollapsed') === 'true') {
            sidebar.classList.add('collapsed');
            mainContent.classList.add('collapsed');
        }

        toggleButton.addEventListener('click', toggleSidebar);
    }
});