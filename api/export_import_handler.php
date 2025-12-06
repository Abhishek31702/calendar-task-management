<?php
require_once '../autoload.php';

$task = new Task();
$category = new Category();

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'export_csv':
            exportToCSV($task);
            break;
            
        case 'import_csv':
            importFromCSV($task, $category);
            break;
            
        default:
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function exportToCSV($task) {
    // Get filter parameters if any
    $filters = [
        'priority' => $_GET['priority'] ?? '',
        'category_id' => $_GET['category_id'] ?? '',
        'status' => $_GET['status'] ?? '',
        'search' => $_GET['search'] ?? '',
        'date_from' => $_GET['date_from'] ?? '',
        'date_to' => $_GET['date_to'] ?? ''
    ];
    
    // Get tasks based on filters or all tasks
    $hasFilters = array_filter($filters);
    if ($hasFilters) {
        $tasks = $task->getFiltered($filters);
    } else {
        // Get all tasks from current year
        $year = date('Y');
        $allTasks = [];
        for ($month = 1; $month <= 12; $month++) {
            $monthTasks = $task->getByMonth($year, $month);
            $allTasks = array_merge($allTasks, $monthTasks);
        }
        $tasks = $allTasks;
    }
    
    // Set headers for CSV download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="tasks_export_' . date('Y-m-d_H-i-s') . '.csv"');
    
    // Open output stream
    $output = fopen('php://output', 'w');
    
    // Add BOM for Excel UTF-8 support
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Write CSV header
    fputcsv($output, [
        'ID',
        'Title',
        'Description',
        'Due Date',
        'Due Time',
        'Priority',
        'Category',
        'Status',
        'Created At',
        'Updated At'
    ]);
    
    // Write task data
    foreach ($tasks as $t) {
        fputcsv($output, [
            $t['id'],
            $t['title'],
            $t['description'] ?? '',
            $t['due_date'],
            $t['due_time'] ?? '',
            $t['priority'],
            $t['category_name'] ?? '',
            $t['status'],
            $t['created_at'],
            $t['updated_at']
        ]);
    }
    
    fclose($output);
    exit;
}

function importFromCSV($task, $category) {
    header('Content-Type: application/json');
    
    // Check if file was uploaded
    if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error']);
        exit;
    }
    
    $file = $_FILES['csv_file']['tmp_name'];
    
    // Open and read CSV file
    $handle = fopen($file, 'r');
    if (!$handle) {
        echo json_encode(['success' => false, 'message' => 'Could not open CSV file']);
        exit;
    }
    
    // Skip BOM if present
    $bom = fread($handle, 3);
    if ($bom !== chr(0xEF).chr(0xBB).chr(0xBF)) {
        rewind($handle);
    }
    
    // Read header row
    $header = fgetcsv($handle);
    if (!$header) {
        echo json_encode(['success' => false, 'message' => 'Invalid CSV format - no header found']);
        fclose($handle);
        exit;
    }
    
    // Get all categories for mapping
    $categories = $category->getAll();
    $categoryMap = [];
    foreach ($categories as $cat) {
        $categoryMap[strtolower($cat['name'])] = $cat['id'];
    }
    
    $imported = 0;
    $skipped = 0;
    $errors = [];
    
    // Read and import each row
    while (($row = fgetcsv($handle)) !== false) {
        // Skip empty rows
        if (empty(array_filter($row))) {
            continue;
        }
        
        try {
            // Map CSV columns to array
            $data = array_combine($header, $row);
            
            // Validate required fields
            if (empty($data['Title']) || empty($data['Due Date'])) {
                $skipped++;
                $errors[] = "Row skipped: Missing title or due date";
                continue;
            }
            
            // Map category name to ID
            $categoryId = null;
            if (!empty($data['Category'])) {
                $catName = strtolower(trim($data['Category']));
                $categoryId = $categoryMap[$catName] ?? null;
            }
            
            // Prepare task data
            $taskData = [
                'title' => $data['Title'],
                'description' => $data['Description'] ?? '',
                'due_date' => $data['Due Date'],
                'due_time' => !empty($data['Due Time']) ? $data['Due Time'] : null,
                'priority' => strtolower($data['Priority'] ?? 'medium'),
                'category_id' => $categoryId,
                'status' => strtolower($data['Status'] ?? 'pending')
            ];
            
            // Validate priority
            if (!in_array($taskData['priority'], ['low', 'medium', 'high'])) {
                $taskData['priority'] = 'medium';
            }
            
            // Validate status
            if (!in_array($taskData['status'], ['pending', 'completed'])) {
                $taskData['status'] = 'pending';
            }
            
            // Validate date format
            $date = DateTime::createFromFormat('Y-m-d', $taskData['due_date']);
            if (!$date) {
                $skipped++;
                $errors[] = "Row skipped: Invalid date format for '{$taskData['title']}'";
                continue;
            }
            
            // Create task
            if ($task->create($taskData)) {
                $imported++;
            } else {
                $skipped++;
                $errors[] = "Failed to import: {$taskData['title']}";
            }
            
        } catch (Exception $e) {
            $skipped++;
            $errors[] = "Error: " . $e->getMessage();
        }
    }
    
    fclose($handle);
    
    // Return results
    $message = "Import completed: {$imported} tasks imported";
    if ($skipped > 0) {
        $message .= ", {$skipped} skipped";
    }
    
    echo json_encode([
        'success' => true,
        'message' => $message,
        'imported' => $imported,
        'skipped' => $skipped,
        'errors' => $errors
    ]);
}
?>
