<?php
require_once __DIR__ . '/../config/database.php';

class Task {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    // Create a new task
    public function create($data) {
        $sql = "INSERT INTO tasks (title, description, due_date, due_time, priority, category_id, status) 
                VALUES (:title, :description, :due_date, :due_time, :priority, :category_id, :status)";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':title' => $data['title'],
            ':description' => $data['description'] ?? '',
            ':due_date' => $data['due_date'],
            ':due_time' => $data['due_time'] ?? null,
            ':priority' => $data['priority'] ?? 'medium',
            ':category_id' => $data['category_id'] ?? null,
            ':status' => $data['status'] ?? 'pending'
        ]);
    }
    
    // Get task by ID
    public function getById($id) {
        $sql = "SELECT t.*, c.name as category_name, c.color as category_color 
                FROM tasks t 
                LEFT JOIN categories c ON t.category_id = c.id 
                WHERE t.id = :id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }
    
    // Get tasks by date
    public function getByDate($date) {
        $sql = "SELECT t.*, c.name as category_name, c.color as category_color 
                FROM tasks t 
                LEFT JOIN categories c ON t.category_id = c.id 
                WHERE t.due_date = :date 
                ORDER BY t.due_time ASC, t.priority DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':date' => $date]);
        return $stmt->fetchAll();
    }
    
    // Get tasks by month
    public function getByMonth($year, $month) {
        $sql = "SELECT t.*, c.name as category_name, c.color as category_color 
                FROM tasks t 
                LEFT JOIN categories c ON t.category_id = c.id 
                WHERE YEAR(t.due_date) = :year AND MONTH(t.due_date) = :month 
                ORDER BY t.due_date ASC, t.due_time ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':year' => $year, ':month' => $month]);
        return $stmt->fetchAll();
    }
    
    // Get filtered tasks
    public function getFiltered($filters) {
        $sql = "SELECT t.*, c.name as category_name, c.color as category_color 
                FROM tasks t 
                LEFT JOIN categories c ON t.category_id = c.id WHERE 1=1";
        
        $params = [];
        
        // Priority filter
        if (!empty($filters['priority'])) {
            $sql .= " AND t.priority = :priority";
            $params[':priority'] = $filters['priority'];
        }
        
        // Category filter
        if (!empty($filters['category_id'])) {
            $sql .= " AND t.category_id = :category_id";
            $params[':category_id'] = $filters['category_id'];
        }
        
        // Status filter
        if (!empty($filters['status'])) {
            $sql .= " AND t.status = :status";
            $params[':status'] = $filters['status'];
        }
        
        // Search filter
        if (!empty($filters['search'])) {
            $sql .= " AND (t.title LIKE :search OR t.description LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        
        // Date range filter
        if (!empty($filters['date_from'])) {
            $sql .= " AND t.due_date >= :date_from";
            $params[':date_from'] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $sql .= " AND t.due_date <= :date_to";
            $params[':date_to'] = $filters['date_to'];
        }
        
        $sql .= " ORDER BY t.due_date ASC, t.due_time ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    // Update task
    public function update($id, $data) {
        $sql = "UPDATE tasks SET 
                title = :title, 
                description = :description, 
                due_date = :due_date, 
                due_time = :due_time, 
                priority = :priority, 
                category_id = :category_id, 
                status = :status 
                WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id' => $id,
            ':title' => $data['title'],
            ':description' => $data['description'] ?? '',
            ':due_date' => $data['due_date'],
            ':due_time' => $data['due_time'] ?? null,
            ':priority' => $data['priority'] ?? 'medium',
            ':category_id' => $data['category_id'] ?? null,
            ':status' => $data['status'] ?? 'pending'
        ]);
    }
    
    // Toggle task status
    public function toggleStatus($id) {
        $sql = "UPDATE tasks SET status = IF(status = 'pending', 'completed', 'pending') WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }
    
    // Delete task
    public function delete($id) {
        $sql = "DELETE FROM tasks WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }
    
    // Get task count by date
    public function getTaskCountsByMonth($year, $month) {
        $sql = "SELECT DAY(due_date) as day, COUNT(*) as count 
                FROM tasks 
                WHERE YEAR(due_date) = :year AND MONTH(due_date) = :month 
                GROUP BY DAY(due_date)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':year' => $year, ':month' => $month]);
        
        $counts = [];
        while ($row = $stmt->fetch()) {
            $counts[$row['day']] = $row['count'];
        }
        return $counts;
    }
}
?>