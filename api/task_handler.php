<?php
header('Content-Type: application/json');
require_once '../autoload.php';

$task = new Task();
$category = new Category();

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'create':
            $data = [
                'title' => $_POST['title'] ?? '',
                'description' => $_POST['description'] ?? '',
                'due_date' => $_POST['due_date'] ?? '',
                'due_time' => $_POST['due_time'] ?? null,
                'priority' => $_POST['priority'] ?? 'medium',
                'category_id' => $_POST['category_id'] ?? null,
                'status' => 'pending'
            ];
            
            if (empty($data['title']) || empty($data['due_date'])) {
                echo json_encode(['success' => false, 'message' => 'Title and due date are required']);
                exit;
            }
            
            $result = $task->create($data);
            echo json_encode(['success' => $result, 'message' => $result ? 'Task created successfully' : 'Failed to create task']);
            break;
            
        case 'update':
            $id = $_POST['id'] ?? 0;
            $data = [
                'title' => $_POST['title'] ?? '',
                'description' => $_POST['description'] ?? '',
                'due_date' => $_POST['due_date'] ?? '',
                'due_time' => $_POST['due_time'] ?? null,
                'priority' => $_POST['priority'] ?? 'medium',
                'category_id' => $_POST['category_id'] ?? null,
                'status' => $_POST['status'] ?? 'pending'
            ];
            
            if (empty($data['title']) || empty($data['due_date'])) {
                echo json_encode(['success' => false, 'message' => 'Title and due date are required']);
                exit;
            }
            
            $result = $task->update($id, $data);
            echo json_encode(['success' => $result, 'message' => $result ? 'Task updated successfully' : 'Failed to update task']);
            break;
            
        case 'delete':
            $id = $_POST['id'] ?? 0;
            $result = $task->delete($id);
            echo json_encode(['success' => $result, 'message' => $result ? 'Task deleted successfully' : 'Failed to delete task']);
            break;
            
        case 'toggle_status':
            $id = $_POST['id'] ?? 0;
            $result = $task->toggleStatus($id);
            echo json_encode(['success' => $result, 'message' => $result ? 'Task status updated' : 'Failed to update status']);
            break;
            
        case 'get_by_date':
            $date = $_GET['date'] ?? date('Y-m-d');
            $tasks = $task->getByDate($date);
            echo json_encode(['success' => true, 'tasks' => $tasks]);
            break;
            
        case 'get_by_month':
            $year = $_GET['year'] ?? date('Y');
            $month = $_GET['month'] ?? date('n');
            $tasks = $task->getByMonth($year, $month);
            echo json_encode(['success' => true, 'tasks' => $tasks]);
            break;
            
        case 'get_by_id':
            $id = $_GET['id'] ?? 0;
            $taskData = $task->getById($id);
            echo json_encode(['success' => true, 'task' => $taskData]);
            break;
            
        case 'get_filtered':
            $filters = [
                'priority' => $_GET['priority'] ?? '',
                'category_id' => $_GET['category_id'] ?? '',
                'status' => $_GET['status'] ?? '',
                'search' => $_GET['search'] ?? '',
                'date_from' => $_GET['date_from'] ?? '',
                'date_to' => $_GET['date_to'] ?? ''
            ];
            
            $tasks = $task->getFiltered($filters);
            echo json_encode(['success' => true, 'tasks' => $tasks]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
