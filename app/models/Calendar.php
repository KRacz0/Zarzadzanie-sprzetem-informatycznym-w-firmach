<?php

class Calendar {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function addEvent($title, $description, $eventDate, $userId) {
        $stmt = $this->conn->prepare("INSERT INTO calendar_events (title, description, event_date, user_id) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sssi", $title, $description, $eventDate, $userId);
        return $stmt->execute();
    }

    public function getEvents() {
        $sql = "
            SELECT 
                e.*, 
                GROUP_CONCAT(DISTINCT u.name SEPARATOR ', ') AS user_name, 
                GROUP_CONCAT(DISTINCT u.email SEPARATOR ', ') AS user_email,
                l.name AS location_name
            FROM calendar_events e
            LEFT JOIN users u ON FIND_IN_SET(u.id, e.user_id)
            LEFT JOIN locations l ON e.location_id = l.id
            GROUP BY e.id
            ORDER BY e.event_date ASC
        ";
        $result = $this->conn->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    
    

    public function getUpcomingEvents() {
        $result = $this->conn->query("SELECT e.*, u.name, u.email FROM calendar_events e LEFT JOIN users u ON e.user_id = u.id WHERE e.event_date >= CURDATE() ORDER BY event_date ASC");
        return $result->fetch_all(MYSQLI_ASSOC);
    }
}
