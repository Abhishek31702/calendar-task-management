// Global variables
let selectedDate = new URLSearchParams(window.location.search).get('date') || new Date().toISOString().split('T')[0];
let currentTaskId = null;
let activeFilters = {
    search: '',
    priority: '',
    category: '',
    status: '',
    dateRange: 'all'
};
let allTasks = []; // Store all tasks for filtering

// DOM Ready
document.addEventListener('DOMContentLoaded', function() {
    initializeEventListeners();
    loadAllTasks();
});

// Initialize all event listeners
function initializeEventListeners() {
    // Calendar day clicks
    document.querySelectorAll('.calendar-day:not(.empty)').forEach(day => {
        day.addEventListener('click', function() {
            const date = this.dataset.date;
            selectDate(date);
        });
    });
    
    // Mini calendar day clicks
    document.querySelectorAll('.mini-day').forEach(day => {
        day.addEventListener('click', function() {
            const date = this.dataset.date;
            if (date) {
                selectDate(date);
            }
        });
    });
    
    // Task checkbox toggle
    document.querySelectorAll('.task-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const taskId = this.dataset.taskId;
            toggleTaskStatus(taskId);
        });
    });
    
    // Edit task buttons
    document.querySelectorAll('.edit-task').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            const taskId = this.dataset.taskId;
            openEditTaskModal(taskId);
        });
    });
    
    // Delete task buttons
    document.querySelectorAll('.delete-task').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            const taskId = this.dataset.taskId;
            deleteTask(taskId);
        });
    });
    
    // Save task button
    document.getElementById('saveTaskBtn').addEventListener('click', saveTask);
    
    // Apply filters button
    document.getElementById('applyFilters').addEventListener('click', applyFilters);
    
    // Clear filters button
    document.getElementById('clearFilters').addEventListener('click', clearFilters);
    
    // Quick filter buttons
    document.querySelectorAll('.quick-filter').forEach(btn => {
        btn.addEventListener('click', function() {
            const filter = this.dataset.filter;
            applyQuickFilter(filter);
        });
    });
    
    // Priority filter buttons
    document.querySelectorAll('.priority-filter').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.priority-filter').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            activeFilters.priority = this.dataset.priority;
            applyActiveFilters();
        });
    });
    
    // Status filter buttons
    document.querySelectorAll('.status-filter').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.status-filter').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            activeFilters.status = this.dataset.status;
            applyActiveFilters();
        });
    });
    
    // Date filter buttons
    document.querySelectorAll('.date-filter').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.date-filter').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            activeFilters.dateRange = this.dataset.filter;
            applyActiveFilters();
        });
    });
    
    // Category filter
    document.querySelectorAll('.category-check').forEach(item => {
        item.addEventListener('click', function() {
            document.querySelectorAll('.category-check').forEach(c => c.classList.remove('active'));
            this.classList.add('active');
            const radio = this.querySelector('input[type="radio"]');
            radio.checked = true;
            activeFilters.category = this.dataset.categoryId;
            applyActiveFilters();
        });
    });
    
    // Quick search
    const searchInput = document.getElementById('quick_search');
    if (searchInput) {
        let searchTimeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                activeFilters.search = this.value;
                applyActiveFilters();
            }, 300);
        });
    }
    
    // Clear all filters
    const clearBtn = document.getElementById('clearAllFilters');
    if (clearBtn) {
        clearBtn.addEventListener('click', function() {
            clearAllFilters();
        });
    }
    
    // Import button
    const importBtn = document.getElementById('importBtn');
    if (importBtn) {
        importBtn.addEventListener('click', function() {
            importTasks();
        });
    }
}

// Select a date and load its tasks
function selectDate(date) {
    selectedDate = date;
    
    // Update URL
    const url = new URL(window.location);
    url.searchParams.set('date', date);
    window.history.pushState({}, '', url);
    
    // Update selected state in calendar
    document.querySelectorAll('.calendar-day').forEach(day => {
        day.classList.remove('selected');
        if (day.dataset.date === date) {
            day.classList.add('selected');
        }
    });
    
    // Update mini calendar
    document.querySelectorAll('.mini-day').forEach(day => {
        day.classList.remove('selected');
        if (day.dataset.date === date) {
            day.classList.add('selected');
        }
    });
    
    // Update sidebar date
    const dateObj = new Date(date);
    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    document.getElementById('sidebar-date').textContent = dateObj.toLocaleDateString('en-US', options);
    
    // Load tasks for the date
    loadTasksForDate(date);
}

// Load tasks for a specific date
function loadTasksForDate(date) {
    fetch(`api/task_handler.php?action=get_by_date&date=${date}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderTasks(data.tasks);
            }
        })
        .catch(error => {
            console.error('Error loading tasks:', error);
            showAlert('Failed to load tasks', 'danger');
        });
}

// Render tasks in the sidebar
function renderTasks(tasks) {
    const taskList = document.getElementById('task-list');
    
    if (tasks.length === 0) {
        taskList.innerHTML = '<p class="text-muted text-center mt-4">No tasks for this day</p>';
        return;
    }
    
    taskList.innerHTML = tasks.map(task => {
        const priorityColor = task.priority === 'high' ? 'danger' : (task.priority === 'medium' ? 'warning' : 'secondary');
        const completedClass = task.status === 'completed' ? 'completed' : '';
        const checkedAttr = task.status === 'completed' ? 'checked' : '';
        const strikethrough = task.status === 'completed' ? 'text-decoration-line-through' : '';
        
        return `
            <div class="task-card ${completedClass}" data-task-id="${task.id}">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="form-check">
                        <input class="form-check-input task-checkbox" type="checkbox" 
                               ${checkedAttr}
                               data-task-id="${task.id}"
                               onchange="toggleTaskStatus(${task.id})">
                        <label class="form-check-label task-title ${strikethrough}">
                            ${escapeHtml(task.title)}
                        </label>
                    </div>
                    <span class="badge bg-${priorityColor}">
                        ${task.priority}
                    </span>
                </div>
                
                ${task.description ? `<p class="task-description text-muted small mt-2 mb-2">${escapeHtml(task.description).replace(/\n/g, '<br>')}</p>` : ''}
                
                <div class="d-flex justify-content-between align-items-center mt-2">
                    <div>
                        ${task.due_time ? `<small class="text-muted"><i class="bi bi-clock"></i> ${formatTime(task.due_time)}</small>` : ''}
                        ${task.category_name ? `<span class="badge" style="background-color: ${task.category_color}">${escapeHtml(task.category_name)}</span>` : ''}
                    </div>
                    <div>
                        <button class="btn btn-sm btn-outline-primary" onclick="openEditTaskModal(${task.id})">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger" onclick="deleteTask(${task.id})">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;
    }).join('');
}

// Open task modal for new task
function openTaskModal() {
    currentTaskId = null;
    document.getElementById('taskModalTitle').textContent = 'Add Task';
    document.getElementById('taskForm').reset();
    document.getElementById('task_id').value = '';
    document.getElementById('task_due_date').value = selectedDate;
}

// Open task modal for editing
function openEditTaskModal(taskId) {
    currentTaskId = taskId;
    document.getElementById('taskModalTitle').textContent = 'Edit Task';
    
    // Fetch task details
    fetch(`api/task_handler.php?action=get_by_id&id=${taskId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.task) {
                const task = data.task;
                document.getElementById('task_id').value = task.id;
                document.getElementById('task_title').value = task.title;
                document.getElementById('task_description').value = task.description || '';
                document.getElementById('task_due_date').value = task.due_date;
                document.getElementById('task_due_time').value = task.due_time || '';
                document.getElementById('task_priority').value = task.priority;
                document.getElementById('task_category').value = task.category_id || '';
                
                // Show modal
                const modal = new bootstrap.Modal(document.getElementById('taskModal'));
                modal.show();
            }
        })
        .catch(error => {
            console.error('Error loading task:', error);
            showAlert('Failed to load task details', 'danger');
        });
}

// Save task (create or update)
function saveTask() {
    const form = document.getElementById('taskForm');
    const formData = new FormData(form);
    
    const taskId = document.getElementById('task_id').value;
    const action = taskId ? 'update' : 'create';
    formData.append('action', action);
    
    if (taskId) {
        formData.append('id', taskId);
    }
    
    // Add status for new tasks
    if (!taskId) {
        formData.append('status', 'pending');
    }
    
    fetch('api/task_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            
            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('taskModal'));
            modal.hide();
            
            // Dynamically reload tasks WITHOUT page refresh
            loadAllTasks();
            loadTasksForDate(selectedDate);
            
            // Refresh calendar display
            refreshCalendarDisplay();
        } else {
            showAlert(data.message, 'danger');
        }
    })
    .catch(error => {
        console.error('Error saving task:', error);
        showAlert('Failed to save task', 'danger');
    });
}

// Toggle task status
function toggleTaskStatus(taskId) {
    const formData = new FormData();
    formData.append('action', 'toggle_status');
    formData.append('id', taskId);
    
    fetch('api/task_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Dynamically reload tasks WITHOUT page refresh
            loadAllTasks();
            loadTasksForDate(selectedDate);
            refreshCalendarDisplay();
        } else {
            showAlert(data.message, 'danger');
        }
    })
    .catch(error => {
        console.error('Error toggling task status:', error);
        showAlert('Failed to update task status', 'danger');
    });
}

// Delete task
function deleteTask(taskId) {
    if (!confirm('Are you sure you want to delete this task?')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('id', taskId);
    
    fetch('api/task_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            
            // Dynamically reload WITHOUT page refresh
            loadAllTasks();
            loadTasksForDate(selectedDate);
            refreshCalendarDisplay();
        } else {
            showAlert(data.message, 'danger');
        }
    })
    .catch(error => {
        console.error('Error deleting task:', error);
        showAlert('Failed to delete task', 'danger');
    });
}

// Apply filters
function applyFilters() {
    const formData = new FormData(document.getElementById('filterForm'));
    const params = new URLSearchParams();
    
    for (let [key, value] of formData) {
        if (value) {
            params.append(key, value);
        }
    }
    
    // Redirect to filtered view
    window.location.href = `filtered_tasks.php?${params.toString()}`;
}

// Clear filters
function clearFilters() {
    document.getElementById('filterForm').reset();
}

// Apply quick filter
function applyQuickFilter(filter) {
    const today = new Date();
    let dateFrom, dateTo;
    
    switch (filter) {
        case 'today':
            dateFrom = dateTo = today.toISOString().split('T')[0];
            break;
        case 'week':
            dateFrom = today.toISOString().split('T')[0];
            const weekEnd = new Date(today);
            weekEnd.setDate(weekEnd.getDate() + 7);
            dateTo = weekEnd.toISOString().split('T')[0];
            break;
        case 'month':
            dateFrom = new Date(today.getFullYear(), today.getMonth(), 1).toISOString().split('T')[0];
            dateTo = new Date(today.getFullYear(), today.getMonth() + 1, 0).toISOString().split('T')[0];
            break;
    }
    
    document.getElementById('filter_date_from').value = dateFrom;
    document.getElementById('filter_date_to').value = dateTo;
}

// Utility function to escape HTML
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Utility function to format time
function formatTime(timeString) {
    if (!timeString) return '';
    const [hours, minutes] = timeString.split(':');
    const hour = parseInt(hours);
    const ampm = hour >= 12 ? 'PM' : 'AM';
    const displayHour = hour % 12 || 12;
    return `${displayHour}:${minutes} ${ampm}`;
}

// Show alert message
function showAlert(message, type = 'info') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3`;
    alertDiv.style.zIndex = '9999';
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(alertDiv);
    
    setTimeout(() => {
        alertDiv.remove();
    }, 3000);
}

// Load all tasks for filtering
function loadAllTasks() {
    const currentDate = new Date();
    const year = currentDate.getFullYear();
    const month = currentDate.getMonth() + 1;
    
    fetch(`api/task_handler.php?action=get_by_month&year=${year}&month=${month}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.tasks) {
                allTasks = data.tasks;
            }
        })
        .catch(error => {
            console.error('Error loading tasks:', error);
        });
}

// Apply active filters
function applyActiveFilters() {
    let filtered = [...allTasks];
    
    // Apply search filter
    if (activeFilters.search) {
        const searchLower = activeFilters.search.toLowerCase();
        filtered = filtered.filter(task => 
            task.title.toLowerCase().includes(searchLower) || 
            (task.description && task.description.toLowerCase().includes(searchLower))
        );
    }
    
    // Apply priority filter
    if (activeFilters.priority) {
        filtered = filtered.filter(task => task.priority === activeFilters.priority);
    }
    
    // Apply category filter
    if (activeFilters.category) {
        filtered = filtered.filter(task => task.category_id == activeFilters.category);
    }
    
    // Apply status filter
    if (activeFilters.status) {
        filtered = filtered.filter(task => task.status === activeFilters.status);
    }
    
    // Apply date range filter
    if (activeFilters.dateRange !== 'all') {
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        
        filtered = filtered.filter(task => {
            const taskDate = new Date(task.due_date);
            taskDate.setHours(0, 0, 0, 0);
            
            switch (activeFilters.dateRange) {
                case 'today':
                    return taskDate.getTime() === today.getTime();
                case 'week':
                    const weekEnd = new Date(today);
                    weekEnd.setDate(weekEnd.getDate() + 7);
                    return taskDate >= today && taskDate <= weekEnd;
                case 'month':
                    return taskDate.getMonth() === today.getMonth() && 
                           taskDate.getFullYear() === today.getFullYear();
                default:
                    return true;
            }
        });
    }
    
    // Update filter status display
    updateFilterStatus(filtered.length);
    
    // Group by date and update calendar
    updateCalendarWithFilteredTasks(filtered);
}

// Update filter status bar
function updateFilterStatus(count) {
    const statusBar = document.getElementById('filterStatus');
    const badgesContainer = document.getElementById('filterBadges');
    const countContainer = document.getElementById('filterCount');
    
    const hasFilters = activeFilters.search || activeFilters.priority || 
                      activeFilters.category || activeFilters.status || 
                      activeFilters.dateRange !== 'all';
    
    if (hasFilters) {
        statusBar.style.display = 'block';
        
        let badges = [];
        if (activeFilters.search) {
            badges.push(`<span class="filter-badge">Search: "${activeFilters.search}"</span>`);
        }
        if (activeFilters.priority) {
            badges.push(`<span class="filter-badge">Priority: ${activeFilters.priority}</span>`);
        }
        if (activeFilters.category) {
            badges.push(`<span class="filter-badge">Category</span>`);
        }
        if (activeFilters.status) {
            badges.push(`<span class="filter-badge">Status: ${activeFilters.status}</span>`);
        }
        if (activeFilters.dateRange !== 'all') {
            badges.push(`<span class="filter-badge">Range: ${activeFilters.dateRange}</span>`);
        }
        
        badgesContainer.innerHTML = badges.join(' ');
        countContainer.textContent = `${count} task(s) found`;
    } else {
        statusBar.style.display = 'none';
    }
}

// Update calendar with filtered tasks
function updateCalendarWithFilteredTasks(filtered) {
    // Group tasks by date
    const tasksByDate = {};
    filtered.forEach(task => {
        if (!tasksByDate[task.due_date]) {
            tasksByDate[task.due_date] = [];
        }
        tasksByDate[task.due_date].push(task);
    });
    
    // Update calendar day cells
    document.querySelectorAll('.calendar-day:not(.empty)').forEach(day => {
        const date = day.dataset.date;
        const tasksContainer = day.querySelector('.day-tasks');
        
        if (tasksContainer) {
            tasksContainer.innerHTML = '';
            
            const dayTasks = tasksByDate[date] || [];
            dayTasks.slice(0, 3).forEach(task => {
                const pill = document.createElement('div');
                pill.className = `task-pill ${task.status}`;
                pill.style.borderLeft = `3px solid ${task.category_color || '#6c757d'}`;
                pill.title = task.title;
                pill.innerHTML = `<small>${escapeHtml(task.title.substring(0, 20))}</small>`;
                tasksContainer.appendChild(pill);
            });
            
            if (dayTasks.length > 3) {
                const more = document.createElement('small');
                more.className = 'text-muted';
                more.textContent = `+${dayTasks.length - 3} more`;
                tasksContainer.appendChild(more);
            }
        }
    });
}

// Clear all filters
function clearAllFilters() {
    activeFilters = {
        search: '',
        priority: '',
        category: '',
        status: '',
        dateRange: 'all'
    };
    
    // Reset UI
    document.getElementById('quick_search').value = '';
    
    document.querySelectorAll('.priority-filter').forEach(b => b.classList.remove('active'));
    document.querySelector('.priority-filter[data-priority=""]').classList.add('active');
    
    document.querySelectorAll('.status-filter').forEach(b => b.classList.remove('active'));
    document.querySelector('.status-filter[data-status=""]').classList.add('active');
    
    document.querySelectorAll('.date-filter').forEach(b => b.classList.remove('active'));
    document.querySelector('.date-filter[data-filter="all"]').classList.add('active');
    
    document.querySelectorAll('.category-check').forEach(c => c.classList.remove('active'));
    document.querySelector('.category-check[data-category-id=""]').classList.add('active');
    document.getElementById('cat_all').checked = true;
    
    applyActiveFilters();
}

// Refresh calendar display with current month tasks
function refreshCalendarDisplay() {
    const currentDate = new Date();
    const year = currentDate.getFullYear();
    const month = currentDate.getMonth() + 1;
    
    fetch(`api/task_handler.php?action=get_by_month&year=${year}&month=${month}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.tasks) {
                allTasks = data.tasks;
                
                // If filters are active, apply them
                if (hasActiveFilters()) {
                    applyActiveFilters();
                } else {
                    updateCalendarWithFilteredTasks(data.tasks);
                }
                
                // Update task counts on calendar
                updateTaskCounts(data.tasks);
            }
        })
        .catch(error => {
            console.error('Error refreshing calendar:', error);
        });
}

// Check if any filters are active
function hasActiveFilters() {
    return activeFilters.search || 
           activeFilters.priority || 
           activeFilters.category || 
           activeFilters.status || 
           activeFilters.dateRange !== 'all';
}

// Update task count indicators on calendar
function updateTaskCounts(tasks) {
    // Group tasks by date
    const tasksByDate = {};
    tasks.forEach(task => {
        if (!tasksByDate[task.due_date]) {
            tasksByDate[task.due_date] = 0;
        }
        tasksByDate[task.due_date]++;
    });
    
    // Update mini calendar dots
    document.querySelectorAll('.mini-day').forEach(day => {
        const date = day.dataset.date;
        if (date && tasksByDate[date]) {
            day.classList.add('has-task');
        } else {
            day.classList.remove('has-task');
        }
    });
}

// Import tasks from CSV
function importTasks() {
    const fileInput = document.getElementById('csv_file');
    const file = fileInput.files[0];
    
    if (!file) {
        showAlert('Please select a CSV file', 'warning');
        return;
    }
    
    // Validate file type
    if (!file.name.endsWith('.csv')) {
        showAlert('Please select a valid CSV file', 'danger');
        return;
    }
    
    // Show progress
    document.getElementById('importProgress').classList.remove('d-none');
    document.getElementById('importBtn').disabled = true;
    
    // Prepare form data
    const formData = new FormData();
    formData.append('csv_file', file);
    formData.append('action', 'import_csv');
    
    // Send file
    fetch('api/export_import_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        // Hide progress
        document.getElementById('importProgress').classList.add('d-none');
        document.getElementById('importBtn').disabled = false;
        
        if (data.success) {
            // Show detailed results
            let message = data.message;
            if (data.errors && data.errors.length > 0) {
                message += '<br><small>Errors:<br>' + data.errors.slice(0, 5).join('<br>') + '</small>';
            }
            showAlert(message, 'success');
            
            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('importModal'));
            modal.hide();
            
            // Reset form
            document.getElementById('importForm').reset();
            
            // Reload page to show imported tasks
            setTimeout(() => {
                window.location.reload();
            }, 2000);
        } else {
            showAlert(data.message, 'danger');
        }
    })
    .catch(error => {
        console.error('Import error:', error);
        document.getElementById('importProgress').classList.add('d-none');
        document.getElementById('importBtn').disabled = false;
        showAlert('Failed to import CSV file', 'danger');
    });
}
