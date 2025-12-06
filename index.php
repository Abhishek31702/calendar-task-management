<?php
require_once 'autoload.php';

$task = new Task();
$category = new Category();

// Get current month and year
$currentMonth = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
$currentYear = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

// Get tasks for the month
$tasks = $task->getByMonth($currentYear, $currentMonth);
$taskCounts = $task->getTaskCountsByMonth($currentYear, $currentMonth);
$categories = $category->getAll();

// Calculate calendar
$firstDay = mktime(0, 0, 0, $currentMonth, 1, $currentYear);
$daysInMonth = date('t', $firstDay);
$dayOfWeek = date('w', $firstDay);
$monthName = date('F Y', $firstDay);

// Group tasks by date
$tasksByDate = [];
foreach ($tasks as $t) {
    $date = $t['due_date'];
    if (!isset($tasksByDate[$date])) {
        $tasksByDate[$date] = [];
    }
    $tasksByDate[$date][] = $t;
}

// Previous and next month calculations
$prevMonth = $currentMonth - 1;
$prevYear = $currentYear;
if ($prevMonth < 1) {
    $prevMonth = 12;
    $prevYear--;
}

$nextMonth = $currentMonth + 1;
$nextYear = $currentYear;
if ($nextMonth > 12) {
    $nextMonth = 1;
    $nextYear++;
}

$selectedDate = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$tasksForDate = $task->getByDate($selectedDate);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendar & Task Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Left Sidebar - Filters & Search -->
            <div class="col-md-2 bg-light p-3 left-sidebar">
                <h5 class="mb-3">Search & Filter</h5>
                
                <!-- Search Box -->
                <div class="mb-4">
                    <input type="text" class="form-control form-control-sm" id="quick_search" placeholder="Search tasks...">
                </div>
                
                <!-- Priority Filter -->
                <div class="mb-3">
                    <label class="form-label fw-bold small">Priority</label>
                    <div class="d-flex gap-1 flex-wrap">
                        <button class="btn btn-sm btn-outline-secondary priority-filter active" data-priority="">All</button>
                        <button class="btn btn-sm btn-outline-secondary priority-filter" data-priority="low">Low</button>
                        <button class="btn btn-sm btn-outline-warning priority-filter" data-priority="medium">Medium</button>
                        <button class="btn btn-sm btn-outline-danger priority-filter" data-priority="high">High</button>
                    </div>
                </div>
                
                <!-- Quick Date Filters -->
                <div class="mb-3">
                    <label class="form-label fw-bold small">Quick Filters</label>
                    <div class="d-grid gap-1">
                        <button class="btn btn-sm btn-outline-primary date-filter active" data-filter="all">All Tasks</button>
                        <button class="btn btn-sm btn-outline-primary date-filter" data-filter="today">Today</button>
                        <button class="btn btn-sm btn-outline-primary date-filter" data-filter="week">This Week</button>
                        <button class="btn btn-sm btn-outline-primary date-filter" data-filter="month">This Month</button>
                        <button class="btn btn-sm btn-outline-primary date-filter" data-filter="custom">Custom Range</button>
                    </div>
                </div>
                
                <!-- Category Filter -->
                <div class="mb-3">
                    <label class="form-label fw-bold small">Category</label>
                    <div class="categories-filter">
                        <div class="form-check category-check active" data-category-id="">
                            <input class="form-check-input" type="radio" name="categoryFilter" id="cat_all" checked>
                            <label class="form-check-label" for="cat_all">All Categories</label>
                        </div>
                        <?php foreach ($categories as $cat): ?>
                            <div class="form-check category-check" data-category-id="<?php echo $cat['id']; ?>">
                                <input class="form-check-input" type="radio" name="categoryFilter" id="cat_<?php echo $cat['id']; ?>">
                                <label class="form-check-label" for="cat_<?php echo $cat['id']; ?>">
                                    <span class="category-dot" style="background-color: <?php echo $cat['color']; ?>"></span>
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Status Filter -->
                <div class="mb-3">
                    <label class="form-label fw-bold small">Status</label>
                    <div class="d-grid gap-1">
                        <button class="btn btn-sm btn-outline-success status-filter active" data-status="">All</button>
                        <button class="btn btn-sm btn-outline-info status-filter" data-status="pending">Pending</button>
                        <button class="btn btn-sm btn-outline-secondary status-filter" data-status="completed">Completed</button>
                    </div>
                </div>
                
                <!-- Mini Calendar -->
                <div class="mini-calendar mb-4 mt-4">
                    <div class="text-center mb-2">
                        <small class="text-muted"><?php echo $monthName; ?></small>
                    </div>
                    <div class="mini-calendar-grid">
                        <div class="mini-day-name">S</div>
                        <div class="mini-day-name">M</div>
                        <div class="mini-day-name">T</div>
                        <div class="mini-day-name">W</div>
                        <div class="mini-day-name">T</div>
                        <div class="mini-day-name">F</div>
                        <div class="mini-day-name">S</div>
                        
                        <?php for ($i = 0; $i < $dayOfWeek; $i++): ?>
                            <div class="mini-day"></div>
                        <?php endfor; ?>
                        
                        <?php for ($day = 1; $day <= $daysInMonth; $day++): ?>
                            <?php 
                            $date = sprintf('%04d-%02d-%02d', $currentYear, $currentMonth, $day);
                            $isToday = $date == date('Y-m-d');
                            $isSelected = $date == $selectedDate;
                            $hasTask = isset($tasksByDate[$date]);
                            ?>
                            <div class="mini-day <?php echo $isToday ? 'today' : ''; ?> <?php echo $isSelected ? 'selected' : ''; ?> <?php echo $hasTask ? 'has-task' : ''; ?>" 
                                 data-date="<?php echo $date; ?>">
                                <?php echo $day; ?>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>
                
                <!-- Clear All Filters Button -->
                <button class="btn btn-sm btn-outline-danger w-100 mt-3" id="clearAllFilters">
                    <i class="bi bi-x-circle"></i> Clear All Filters
                </button>
            </div>
            
            <!-- Main Calendar Area -->
            <div class="col-md-7 p-4">
                <!-- Filter Status Bar -->
                <div id="filterStatus" class="alert alert-info mb-3" style="display: none;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <strong>Active Filters:</strong>
                            <span id="filterBadges"></span>
                        </div>
                        <small id="filterCount"></small>
                    </div>
                </div>
                
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><?php echo $monthName; ?></h2>
                    <div class="btn-group">
                        <a href="?month=<?php echo $prevMonth; ?>&year=<?php echo $prevYear; ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-chevron-left"></i>
                        </a>
                        <a href="?month=<?php echo date('n'); ?>&year=<?php echo date('Y'); ?>" class="btn btn-outline-secondary">
                            Today
                        </a>
                        <a href="?month=<?php echo $nextMonth; ?>&year=<?php echo $nextYear; ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-chevron-right"></i>
                        </a>
                        <button class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#exportModal">
                            <i class="bi bi-download"></i> Export
                        </button>
                        <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#importModal">
                            <i class="bi bi-upload"></i> Import
                        </button>
                    </div>
                </div>
                
                <!-- Calendar Grid -->
                <div class="calendar-grid">
                    <div class="calendar-day-header">Sunday</div>
                    <div class="calendar-day-header">Monday</div>
                    <div class="calendar-day-header">Tuesday</div>
                    <div class="calendar-day-header">Wednesday</div>
                    <div class="calendar-day-header">Thursday</div>
                    <div class="calendar-day-header">Friday</div>
                    <div class="calendar-day-header">Saturday</div>
                    
                    <?php for ($i = 0; $i < $dayOfWeek; $i++): ?>
                        <div class="calendar-day empty"></div>
                    <?php endfor; ?>
                    
                    <?php for ($day = 1; $day <= $daysInMonth; $day++): ?>
                        <?php 
                        $date = sprintf('%04d-%02d-%02d', $currentYear, $currentMonth, $day);
                        $isToday = $date == date('Y-m-d');
                        $isSelected = $date == $selectedDate;
                        $dayTasks = $tasksByDate[$date] ?? [];
                        ?>
                        <div class="calendar-day <?php echo $isToday ? 'today' : ''; ?> <?php echo $isSelected ? 'selected' : ''; ?>" 
                             data-date="<?php echo $date; ?>">
                            <div class="day-number"><?php echo $day; ?></div>
                            <div class="day-tasks">
                                <?php foreach (array_slice($dayTasks, 0, 3) as $t): ?>
                                    <div class="task-pill <?php echo $t['status']; ?>" 
                                         style="border-left: 3px solid <?php echo $t['category_color'] ?? '#6c757d'; ?>"
                                         title="<?php echo htmlspecialchars($t['title']); ?>">
                                        <small><?php echo htmlspecialchars(substr($t['title'], 0, 20)); ?></small>
                                    </div>
                                <?php endforeach; ?>
                                <?php if (count($dayTasks) > 3): ?>
                                    <small class="text-muted">+<?php echo count($dayTasks) - 3; ?> more</small>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endfor; ?>
                </div>
            </div>
            
            <!-- Right Sidebar - Task Details -->
            <div class="col-md-3 bg-light p-3 right-sidebar">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 id="sidebar-date"><?php echo date('l, F j', strtotime($selectedDate)); ?></h5>
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#taskModal" onclick="openTaskModal()">
                        <i class="bi bi-plus-lg"></i>
                    </button>
                </div>
                
                <div id="task-list">
                    <?php if (empty($tasksForDate)): ?>
                        <p class="text-muted text-center mt-4">No tasks for this day</p>
                    <?php else: ?>
                        <?php foreach ($tasksForDate as $t): ?>
                            <div class="task-card <?php echo $t['status']; ?>" data-task-id="<?php echo $t['id']; ?>">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="form-check">
                                        <input class="form-check-input task-checkbox" type="checkbox" 
                                               <?php echo $t['status'] == 'completed' ? 'checked' : ''; ?>
                                               data-task-id="<?php echo $t['id']; ?>">
                                        <label class="form-check-label task-title <?php echo $t['status'] == 'completed' ? 'text-decoration-line-through' : ''; ?>">
                                            <?php echo htmlspecialchars($t['title']); ?>
                                        </label>
                                    </div>
                                    <span class="badge bg-<?php echo $t['priority'] == 'high' ? 'danger' : ($t['priority'] == 'medium' ? 'warning' : 'secondary'); ?>">
                                        <?php echo $t['priority']; ?>
                                    </span>
                                </div>
                                
                                <?php if ($t['description']): ?>
                                    <p class="task-description text-muted small mt-2 mb-2"><?php echo nl2br(htmlspecialchars($t['description'])); ?></p>
                                <?php endif; ?>
                                
                                <div class="d-flex justify-content-between align-items-center mt-2">
                                    <div>
                                        <?php if ($t['due_time']): ?>
                                            <small class="text-muted"><i class="bi bi-clock"></i> <?php echo date('g:i A', strtotime($t['due_time'])); ?></small>
                                        <?php endif; ?>
                                        <?php if ($t['category_name']): ?>
                                            <span class="badge" style="background-color: <?php echo $t['category_color']; ?>">
                                                <?php echo htmlspecialchars($t['category_name']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <button class="btn btn-sm btn-outline-primary edit-task" data-task-id="<?php echo $t['id']; ?>">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger delete-task" data-task-id="<?php echo $t['id']; ?>">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Task Modal -->
    <div class="modal fade" id="taskModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="taskModalTitle">Add Task</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="taskForm">
                        <input type="hidden" id="task_id" name="id">
                        <div class="mb-3">
                            <label for="task_title" class="form-label">Title *</label>
                            <input type="text" class="form-control" id="task_title" name="title" required>
                        </div>
                        <div class="mb-3">
                            <label for="task_description" class="form-label">Description</label>
                            <textarea class="form-control" id="task_description" name="description" rows="3"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="task_due_date" class="form-label">Due Date *</label>
                                <input type="date" class="form-control" id="task_due_date" name="due_date" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="task_due_time" class="form-label">Time</label>
                                <input type="time" class="form-control" id="task_due_time" name="due_time">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="task_priority" class="form-label">Priority</label>
                                <select class="form-select" id="task_priority" name="priority">
                                    <option value="low">Low</option>
                                    <option value="medium" selected>Medium</option>
                                    <option value="high">High</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="task_category" class="form-label">Category</label>
                                <select class="form-select" id="task_category" name="category_id">
                                    <option value="">None</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveTaskBtn">Save Task</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Filter Modal -->
    <div class="modal fade" id="filterModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Filter Tasks</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="filterForm">
                        <div class="mb-3">
                            <label for="filter_search" class="form-label">Search</label>
                            <input type="text" class="form-control" id="filter_search" name="search" placeholder="Search tasks...">
                        </div>
                        <div class="mb-3">
                            <label for="filter_priority" class="form-label">Priority</label>
                            <select class="form-select" id="filter_priority" name="priority">
                                <option value="">All</option>
                                <option value="low">Low</option>
                                <option value="medium">Medium</option>
                                <option value="high">High</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="filter_category" class="form-label">Category</label>
                            <select class="form-select" id="filter_category" name="category_id">
                                <option value="">All</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="filter_status" class="form-label">Status</label>
                            <select class="form-select" id="filter_status" name="status">
                                <option value="">All</option>
                                <option value="pending">Pending</option>
                                <option value="completed">Completed</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Date Range</label>
                            <div class="row">
                                <div class="col-6">
                                    <input type="date" class="form-control" id="filter_date_from" name="date_from" placeholder="From">
                                </div>
                                <div class="col-6">
                                    <input type="date" class="form-control" id="filter_date_to" name="date_to" placeholder="To">
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Quick Filters</label>
                            <div class="btn-group w-100" role="group">
                                <button type="button" class="btn btn-outline-secondary quick-filter" data-filter="today">Today</button>
                                <button type="button" class="btn btn-outline-secondary quick-filter" data-filter="week">This Week</button>
                                <button type="button" class="btn btn-outline-secondary quick-filter" data-filter="month">This Month</button>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="clearFilters">Clear</button>
                    <button type="button" class="btn btn-primary" id="applyFilters">Apply Filters</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Export Modal -->
    <div class="modal fade" id="exportModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-download"></i> Export Tasks to CSV</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Export your tasks to a CSV file that can be opened in Excel, Google Sheets, or any spreadsheet application.</p>
                    
                    <div class="alert alert-info">
                        <strong>Export Options:</strong>
                        <ul class="mb-0">
                            <li>Export all tasks from current year</li>
                            <li>Or apply filters first and export filtered results</li>
                        </ul>
                    </div>
                    
                    <p class="text-muted small mb-0">
                        <i class="bi bi-info-circle"></i> The CSV will include: Title, Description, Due Date, Time, Priority, Category, Status, and timestamps.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <a href="api/export_import_handler.php?action=export_csv" class="btn btn-success" id="exportAllBtn">
                        <i class="bi bi-download"></i> Export All Tasks
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Import Modal -->
    <div class="modal fade" id="importModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-upload"></i> Import Tasks from CSV</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Import tasks from a CSV file. The file must have these columns:</p>
                    
                    <div class="alert alert-warning">
                        <strong>Required CSV Format:</strong>
                        <ul>
                            <li><strong>Title</strong> - Task title (required)</li>
                            <li><strong>Description</strong> - Task description (optional)</li>
                            <li><strong>Due Date</strong> - Format: YYYY-MM-DD (required)</li>
                            <li><strong>Due Time</strong> - Format: HH:MM:SS (optional)</li>
                            <li><strong>Priority</strong> - low, medium, or high (optional)</li>
                            <li><strong>Category</strong> - Work, Personal, Shopping, Health, Other (optional)</li>
                            <li><strong>Status</strong> - pending or completed (optional)</li>
                        </ul>
                    </div>
                    
                    <form id="importForm" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="csv_file" class="form-label">Select CSV File</label>
                            <input type="file" class="form-control" id="csv_file" name="csv_file" accept=".csv" required>
                        </div>
                    </form>
                    
                    <div id="importProgress" class="d-none">
                        <div class="progress">
                            <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 100%"></div>
                        </div>
                        <p class="text-center mt-2 mb-0">Importing tasks...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="importBtn">
                        <i class="bi bi-upload"></i> Import Tasks
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/app.js"></script>
</body>
</html>