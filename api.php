<?php
header('Content-Type: application/json');

require_once '../classes/database.php';
require_once '../includes/functions.php';

$db = new Database();
requireLogin();

$response = [
    'status' => 'error',
    'message' => 'Invalid API action.',
    'data' => null
];

$action = $_GET['action'] ?? null;
$userId = $_SESSION['user_id'];

switch ($action) {
    case 'get_dashboard_data':
        try {
            if (is_admin()) {
                $sqlProjects = "SELECT * FROM projects ORDER BY created_at DESC";
                $projects = $db->all($sqlProjects);
                foreach ($projects as &$project) $project['is_manager'] = true;
            } else {
                $sqlProjects = "SELECT p.*, pm.role_id 
                                FROM projects p
                                JOIN project_members pm ON p.id = pm.project_id
                                WHERE pm.user_id = ?
                                ORDER BY p.created_at DESC";
                $projects = $db->all($sqlProjects, [$userId]);
                foreach ($projects as &$project) $project['is_manager'] = ($project['role_id'] == 1);
            }

            if (is_admin()) {
                $sqlTasks = "SELECT t.*, p.title as project_title, s.name as status_label, pr.name as priority_label
                             FROM tasks t
                             JOIN projects p ON t.project_id = p.id
                             LEFT JOIN statuses s ON t.status_id = s.id
                             LEFT JOIN priorities pr ON t.priority_id = pr.id
                             ORDER BY t.deadline ASC, t.position ASC";
                $tasks = $db->all($sqlTasks);
            } else {
                $sqlTasks = "SELECT t.*, p.title as project_title, s.name as status_label, pr.name as priority_label
                             FROM tasks t
                             JOIN projects p ON t.project_id = p.id
                             LEFT JOIN statuses s ON t.status_id = s.id
                             LEFT JOIN priorities pr ON t.priority_id = pr.id
                             WHERE t.assigned_to = ?
                             ORDER BY t.deadline ASC, t.position ASC";
                $tasks = $db->all($sqlTasks, [$userId]);
            }

            $stats = [
                'total_tasks' => count($tasks),
                'total_projects' => count($projects),
                'overdue_tasks' => 0
            ];

            $now = new DateTime();
            foreach ($tasks as $t) {
                if (!empty($t['deadline'])) {
                    $deadline = new DateTime($t['deadline']);
                    if ($deadline < $now && $t['status_id'] != 3) {
                        $stats['overdue_tasks']++;
                    }
                }
            }

            $response['status'] = 'success';
            $response['message'] = 'Dashboard data fetched.';
            $response['data'] = [
                'projects' => $projects,
                'tasks' => $tasks,
                'stats' => $stats,
                'isAdmin' => is_admin()
            ];

        } catch (Exception $e) {
            $response['message'] = 'Server error: ' . $e->getMessage();
        }
        break;

    case 'get_task_detail':
        try {
            $taskId = $_GET['id'] ?? null;
            if (!$taskId) {
                $response['message'] = 'Task ID required.';
                break;
            }
            
            $task = $db->single("SELECT project_id FROM tasks WHERE id = ?", [$taskId]);
            if (!$task || !is_project_member($db, $task['project_id'], $userId)) {
                $response['message'] = 'Access denied or task not found.';
                break;
            }

            $sql = "SELECT t.*, p.title as project_title, s.name as status_label, pr.name as priority_label, u.username as assignee_name
                    FROM tasks t
                    JOIN projects p ON t.project_id = p.id
                    LEFT JOIN statuses s ON t.status_id = s.id
                    LEFT JOIN priorities pr ON t.priority_id = pr.id
                    LEFT JOIN users u ON t.assigned_to = u.id
                    WHERE t.id = ?";
            
            $taskData = $db->single($sql, [$taskId]);

            $response['status'] = 'success';
            $response['data'] = $taskData;

        } catch (Exception $e) {
            $response['message'] = 'DB Error.';
        }
        break;

    case 'get_project_members':
        try {
            $projectId = $_GET['project_id'] ?? null;
            if (!$projectId) {
                $response['message'] = 'Project ID required.';
                break;
            }

            if (!is_project_member($db, $projectId, $userId)) {
                $response['message'] = 'Access denied.';
                break;
            }

            $sql = "SELECT u.id, u.username FROM users u JOIN project_members pm ON u.id = pm.user_id WHERE pm.project_id = ?";
            $members = $db->all($sql, [$projectId]);

            $response['status'] = 'success';
            $response['data'] = $members;

        } catch (Exception $e) {
            $response['message'] = 'DB Error: ' . $e->getMessage();
        }
        break;

    case 'search_users':
        try {
            $query = trim($_GET['query'] ?? '');
            if (strlen($query) < 2) {
                $response['message'] = 'Query too short.';
                break;
            }
            $sql = "SELECT id, username, email FROM users 
                    WHERE (username LIKE ? OR email LIKE ?) AND id != ? LIMIT 10";
            $term = "%{$query}%";
            $users = $db->all($sql, [$term, $term, $userId]);
            
            $response['status'] = 'success';
            $response['data'] = $users;
        } catch (Exception $e) {
            $response['message'] = 'DB Error.';
        }
        break;

    case 'remove_project_member':
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $projectId = $data['project_id'];
            $userIdToRemove = $data['user_id'];

            if (!is_project_manager($db, $projectId, $userId)) {
                $response['message'] = 'Access Denied.';
                break;
            }
            
            if ($userIdToRemove == $userId) {
                $response['message'] = 'You cannot remove yourself from a project you manage.';
                break;
            }

            $db->query("DELETE FROM project_members WHERE project_id = ? AND user_id = ?", [$projectId, $userIdToRemove]);
            $response['status'] = 'success';

        } catch (Exception $e) {
            $response['message'] = 'Database Error: ' . $e->getMessage();
        }
        break;

    case 'toggle_task_completion':
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $taskId = $data['task_id'];
            $isCompleted = $data['completed'];

            if (!can_edit_task($db, $taskId, $userId)) {
                $response['message'] = 'Access Denied or task not found.';
                break;
            }

            $newStatusId = $isCompleted ? 3 : 1;
            
            $db->query("UPDATE tasks SET status_id = ? WHERE id = ?", [$newStatusId, $taskId]);
            
            $response['status'] = 'success';
            $response['data'] = ['new_status_id' => $newStatusId];

        } catch (Exception $e) {
            $response['message'] = 'Database Error: ' . $e->getMessage();
        }
        break;

    case 'update_task_status':
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $taskId = $data['task_id'];
            $statusId = $data['status_id'];

            if (!in_array($statusId, [1, 2, 3])) {
                $response['message'] = 'Invalid status ID.';
                break;
            }

            if (!can_edit_task($db, $taskId, $userId)) {
                $response['message'] = 'Access Denied or task not found.';
                break;
            }

            $db->query("UPDATE tasks SET status_id = ? WHERE id = ?", [$statusId, $taskId]);
            
            $response['status'] = 'success';

        } catch (Exception $e) {
            $response['message'] = 'Database Error: ' . $e->getMessage();
        }
        break;

    case 'get_all_tasks':
        try {
            if (is_admin()) {
                $sql = "SELECT t.*, p.title as project_title, s.name as status_label, pr.name as priority_label 
                        FROM tasks t
                        JOIN projects p ON t.project_id = p.id
                        LEFT JOIN statuses s ON t.status_id = s.id
                        LEFT JOIN priorities pr ON t.priority_id = pr.id
                        ORDER BY t.deadline ASC, t.position ASC";
                $tasks = $db->all($sql);
            } else {
                $sql = "SELECT t.*, p.title as project_title, s.name as status_label, pr.name as priority_label 
                        FROM tasks t
                        JOIN projects p ON t.project_id = p.id
                        LEFT JOIN statuses s ON t.status_id = s.id
                        LEFT JOIN priorities pr ON t.priority_id = pr.id
                        WHERE t.assigned_to = ?
                        ORDER BY t.deadline ASC, t.position ASC";
                $tasks = $db->all($sql, [$userId]);
            }
            
            $response['status'] = 'success';
            $response['data'] = $tasks;

        } catch (Exception $e) {
            $response['message'] = 'DB Error: ' . $e->getMessage();
        }
        break;

    case 'get_project_tasks':
        try {
            $projectId = $_GET['project_id'] ?? null;
            if (!$projectId) {
                $response['message'] = 'Project ID required.';
                break;
            }

            if (!is_project_member($db, $projectId, $userId)) {
                $response['message'] = 'Access denied.';
                break;
            }

            $sql = "SELECT t.*, p.title as project_title, s.name as status_label, pr.name as priority_label 
                    FROM tasks t
                    JOIN projects p ON t.project_id = p.id
                    LEFT JOIN statuses s ON t.status_id = s.id
                    LEFT JOIN priorities pr ON t.priority_id = pr.id
                    WHERE t.project_id = ?
                    ORDER BY t.deadline ASC, t.position ASC";
            $tasks = $db->all($sql, [$projectId]);
            
            $response['status'] = 'success';
            $response['data'] = $tasks;

        } catch (Exception $e) {
            $response['message'] = 'DB Error: ' . $e->getMessage();
        }
        break;

    case 'get_all_projects':
        try {
            $params = [];
            $sql = "
                SELECT 
                    p.*,
                    (SELECT COUNT(*) FROM project_members WHERE project_id = p.id) as member_count,
                    (SELECT COUNT(*) FROM tasks WHERE project_id = p.id) as task_total,
                    (SELECT COUNT(*) FROM tasks WHERE project_id = p.id AND status_id = 3) as task_completed,
                    pm.role_id
                FROM 
                    projects p
                LEFT JOIN 
                    project_members pm ON p.id = pm.project_id AND pm.user_id = ?
            ";
            $params[] = $userId;

            if (!is_admin()) {
                $sql .= " WHERE p.id IN (SELECT project_id FROM project_members WHERE user_id = ?)";
                $params[] = $userId;
            }

            $sql .= " GROUP BY p.id ORDER BY p.created_at DESC";

            $projects = $db->all($sql, $params);

            foreach ($projects as &$project) {
                $project['is_manager'] = ($project['role_id'] == 1 || is_admin());
            }

            $response['status'] = 'success';
            $response['data'] = $projects;

        } catch (Exception $e) {
            $response['message'] = 'DB Error: ' . $e->getMessage();
        }
        break;
}

echo json_encode($response);
exit;
