<?php
require_once __DIR__ . '/../config/Database.php';

class Maintenance {
    private $conn;
    private $table = 'maintenance';

    public function __construct($conn = null) {
        if ($conn) {
            $this->conn = $conn;
            return;
        }

        $db = new Database();
        $this->conn = $db->getConnection();
    }

    public function create($location_id, $user_id, $description, $image_path) {
        $stmt = $this->conn->prepare("INSERT INTO " . $this->table . " (location_id, user_id, description, image_path) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiss", $location_id, $user_id, $description, $image_path);
        return $stmt->execute();
    }

    public function addMaintenance($location_id, $description, $imagePath) {
        $stmt = $this->conn->prepare("INSERT INTO maintenance (location_id, service_date, description, image_path) VALUES (?, NOW(), ?, ?)");
        $stmt->bind_param("iss", $location_id, $description, $imagePath);
        return $stmt->execute();
    }

    public function getByLocation($location_id) {
        $stmt = $this->conn->prepare("SELECT * FROM maintenance WHERE location_id = ? ORDER BY service_date DESC");
        $stmt->bind_param("i", $location_id);
        $stmt->execute();
        return $stmt->get_result();
    }

    public function getHistoryByLocation($location_id) {
        $stmt = $this->conn->prepare("
            SELECT 
                m.id,
                m.service_date, 
                m.description, 
                COALESCE(m.image_path, '') AS image_path, 
                COALESCE(u.name, 'Nieznany użytkownik') AS user_name,
                l.name AS location_name
            FROM maintenance m
            LEFT JOIN users u ON m.user_id = u.id
            LEFT JOIN locations l ON m.location_id = l.id
            WHERE m.location_id = ?
            ORDER BY m.service_date DESC
        ");
        $stmt->bind_param("i", $location_id);
        $stmt->execute();
        return $stmt->get_result();
    }
    

    public function getLocationsWithOutdatedService() {
        $query = "
            SELECT l.id, l.name, MAX(m.service_date) AS last_service_date
            FROM locations l
            LEFT JOIN maintenance m ON l.id = m.location_id
            GROUP BY l.id, l.name
            HAVING MAX(DATE(m.service_date)) IS NULL 
               OR MAX(DATE(m.service_date)) < CURDATE() - INTERVAL 3 MONTH
            ORDER BY last_service_date ASC
        ";
        return $this->executeQuery($query)->fetch_all(MYSQLI_ASSOC);
    }

    public function getAllLocationsWithLastServiceDate() {
        $query = "
            SELECT 
                l.id,
                l.name,
                MAX(m.service_date) AS last_service_date
            FROM locations l
            LEFT JOIN maintenance m ON l.id = m.location_id
            GROUP BY l.id, l.name
            ORDER BY last_service_date ASC
        ";
    
        return $this->executeQuery($query)->fetch_all(MYSQLI_ASSOC);
    }

    public function getTotalMaintenance() {
        $result = $this->conn->query("SELECT COUNT(*) AS total FROM " . $this->table);
        return $result->fetch_assoc()['total'] ?? 0;
    }
    
    
    
    
    public function executeQuery($query) {
        return $this->conn->query($query);
    }
}
