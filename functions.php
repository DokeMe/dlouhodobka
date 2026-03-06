<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function requireLogin() {
    if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
        header("Location: login.php");
        exit;
    }
}

function e(?string $string): string {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

function asset(string $path): string {
    $filePath = ltrim($path, '/');
    $realPath = __DIR__ . '/../' . $filePath;
    
    if (file_exists($realPath)) {
        $version = filemtime($realPath);
        return $path . '?v=' . $version;
    }
    return $path;
}

function setFlash($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type,
        'text' => $message
    ];
}

function displayFlash() {
    if (isset($_SESSION['flash_message'])) {
        $msg = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        
        $icon = $msg['type'] === 'success' ? '<i class="fa-solid fa-circle-check"></i>' : '<i class="fa-solid fa-circle-exclamation"></i>';
        $class = $msg['type'] === 'success' ? 'toast-success' : 'toast-error';
        
        echo '
        <div class="toast-container">
            <div class="toast ' . $class . '">
                <div class="toast-icon">' . $icon . '</div>
                <div class="toast-content">' . e($msg['text']) . '</div>
                <button class="toast-close" onclick="this.parentElement.remove()">&times;</button>
            </div>
        </div>
        <script>
            setTimeout(() => {
                const toast = document.querySelector(".toast");
                if(toast) {
                    toast.style.opacity = "0";
                    toast.style.transform = "translateX(100%)";
                    setTimeout(() => toast.parentElement.remove(), 300);
                }
            }, 4000);
        </script>
        ';
    }
}

function is_admin(): bool {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
}

function is_project_member(Database $db, int $projectId, int $userId): bool {
    if (is_admin()) return true;
    $membership = $db->single("SELECT 1 FROM project_members WHERE project_id = ? AND user_id = ?", [$projectId, $userId]);
    return (bool)$membership;
}

function is_project_manager(Database $db, int $projectId, int $userId): bool {
    if (is_admin()) return true;
    $membership = $db->single("SELECT role_id FROM project_members WHERE project_id = ? AND user_id = ?", [$projectId, $userId]);
    return $membership && $membership['role_id'] == 1;
}

function can_edit_task(Database $db, int $taskId, int $userId): bool {
    if (is_admin()) return true;
    $task = $db->single("SELECT project_id, assigned_to FROM tasks WHERE id = ?", [$taskId]);
    if (!$task) return false;
    if ($task['assigned_to'] == $userId) return true;
    return is_project_manager($db, $task['project_id'], $userId);
}

function can_delete_task(Database $db, int $taskId, int $userId): bool {
    if (is_admin()) return true;
    $task = $db->single("SELECT project_id, assigned_to FROM tasks WHERE id = ?", [$taskId]);
    if (!$task) return false;
    if ($task['assigned_to'] == $userId) return true;
    return is_project_manager($db, $task['project_id'], $userId);
}
?>
