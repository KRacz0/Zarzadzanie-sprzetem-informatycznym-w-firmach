<?php
require_once __DIR__ . '/../config/Database.php';

class Location {
    private $conn;
    private $table = 'locations';

    public function __construct($conn = null) {
        if ($conn) {
            $this->conn = $conn;
            return;
        }

        $db = new Database();
        $this->conn = $db->getConnection();
    }

    public function create($name) {
        $stmt = $this->conn->prepare("INSERT INTO " . $this->table . " (name) VALUES (?)");
        $stmt->bind_param("s", $name);
        return $stmt->execute();
    }

    public function getAll() {
        $stmt = $this->conn->prepare("SELECT * FROM " . $this->table);
        $stmt->execute();
        return $stmt->get_result();
    }

    public function getById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM " . $this->table . " WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function delete($id) {
        $stmt = $this->conn->prepare("DELETE FROM " . $this->table . " WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    public function getTotalLocations() {
        $result = $this->conn->query("SELECT COUNT(*) AS total FROM " . $this->table);
        return $result->fetch_assoc()['total'] ?? 0;
    }
}
?>
