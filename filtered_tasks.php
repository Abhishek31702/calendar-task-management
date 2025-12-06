<?php
require_once 'classes/Task.php';
require_once 'classes/Category.php';

$task = new Task();
$category = new Category();

// Get filter parameters
$filters = [
    'priority' => $_GET['priority'] ?? '',
    'category_id' => $_GET['category_id'] ?? '',
    'status' => $_GET['status'] ?? '',
    'search' => $_GET['search'] ?? '',
    'date_from' => $_GET['date_from'] ?? '',
    'date_to' => $_GET['date_to'] ?? ''
];

$filteredTasks = $task->getFiltered($filters);
$categories = $category->getAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Filtered Tasks - Calendar & Task Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container-fluid p-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Filtered Tasks</h2>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="bi bi-calendar"></i> Back to Calendar
                    </a>
                </div>
                
                <!-- Active Filters Display -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Active Filters</h5>
                        <div class="d-flex flex-wrap gap-2">
                            <?php if (!empty($filters['search'])): ?>
                                <span class="badge bg-primary">Search: <?php echo htmlspecialchars($filters['search']); ?></span>
                            <?php endif; ?>
                            
                            <?php if (!empty($filters['priority'])): ?>
                                <span class="badge bg-info">Priority: <?php echo ucfirst($filters['priority']); ?></span>
                            <?php endif; ?>
                            
                            <?php if (!empty($filters['category_id'])): ?>
                                <?php 
                                $cat = $category->getById($filters['category_id']);
                                if ($cat):
                                ?>
                                    <span class="badge" style="background-color: <?php echo $cat['color']; ?>">
                                        Category: <?php echo htmlspecialchars($cat['name']); ?>
                                    </span>
                                <?php endif; ?>
                            <?php endif; ?>
                            
                            <?php if (!empty($filters['status'])): ?>
                                <span class="badge bg-success">Status: <?php echo ucfirst($filters['status']); ?></span>
                            <?php endif; ?>
                            
                            <?php if (!empty($filters['date_from']) || !empty($filters['date_to'])): ?>
                                <span class="badge bg-warning text-dark">
                                    Date Range: 
                                    <?php echo $filters['date_from'] ?: '...'; ?> to 
                                    <?php echo $filters['date_to'] ?: '...'; ?>
                                </span>
                            <?php endif; ?>
                            
                            <a href="index.php" class="badge bg-secondary text-decoration-none">
                                <i class="bi bi-x"></i> Clear All
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Results -->
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Results (<?php echo count($filteredTasks); ?> tasks)</h5>
                        
                        <?php if (empty($filteredTasks)): ?>
                            <div class="text-center py-5">
                                <i class="bi bi-inbox" style="font-size: 4rem; color: #6c757d;"></i>
                                <p class="text-muted mt-3">No tasks found matching your filters</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th width="40"></th>
                                            <th>Title</th>
                                            <th>Due Date</th>
                                            <th>Time</th>
                                            <th>Priority</th>
                                            <th>Category</th>
                                            <th>Status</th>
                                            <th width="120">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($filteredTasks as $t): ?>
                                            <tr class="<?php echo $t['status'] == 'completed' ? 'table-secondary' : ''; ?>">
                                                <td>
                                                    <input type="checkbox" class="form-check-input task-checkbox" 
                                                           <?php echo $t['status'] == 'completed' ? 'checked' : ''; ?>
                                                           data-task-id="<?php echo $t['id']; ?>"
                                                           onchange="toggleTaskStatus(<?php echo $t['id']; ?>)">
                                                </td>
                                                <td>
                                                    <strong class="<?php echo $t['status'] == 'completed' ? 'text-decoration-line-through' : ''; ?>">
                                                        <?php echo htmlspecialchars($t['title']); ?>
                                                    </strong>
                                                    <?php if ($t['description']): ?>
                                                        <br><small class="text-muted"><?php echo htmlspecialchars(substr($t['description'], 0, 100)); ?><?php echo strlen($t['description']) > 100 ? '...' : ''; ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo date('M j, Y', strtotime($t['due_date'])); ?></td>
                                                <td>
                                                    <?php if ($t['due_time']): ?>
                                                        <small><?php echo date('g:i A', strtotime($t['due_time'])); ?></small>
                                                    <?php else: ?>
                                                        <small class="text-muted">-</small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php echo $t['priority'] == 'high' ? 'danger' : ($t['priority'] == 'medium' ? 'warning' : 'secondary'); ?>">
                                                        <?php echo ucfirst($t['priority']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($t['category_name']): ?>
                                                        <span class="badge" style="background-color: <?php echo $t['category_color']; ?>">
                                                            <?php echo htmlspecialchars($t['category_name']); ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <small class="text-muted">-</small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php echo $t['status'] == 'completed' ? 'success' : 'warning'; ?>">
                                                        <?php echo ucfirst($t['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary" onclick="viewTaskDate('<?php echo $t['due_date']; ?>')">
                                                        <i class="bi bi-calendar"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteTask(<?php echo $t['id']; ?>)">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/app.js"></script>
    <script>
        function viewTaskDate(date) {
            window.location.href = `index.php?date=${date}`;
        }
    </script>
</body>
</html>