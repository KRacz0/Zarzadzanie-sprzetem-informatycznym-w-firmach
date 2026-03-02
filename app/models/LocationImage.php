<?php
require_once __DIR__ . '/../config/Database.php';

class LocationImage {
    private $conn;
    private $table = 'location_images';

    public function __construct($conn = null) {
        if ($conn) {
            $this->conn = $conn;
            return;
        }

        $db = new Database();
        $this->conn = $db->getConnection();
    }

    public function create($location_id, $user_id, $note, $image_path) {
        $stmt = $this->conn->prepare(
            "INSERT INTO " . $this->table . " (location_id, user_id, note, image_path) VALUES (?, ?, ?, ?)"
        );
        $stmt->bind_param("iiss", $location_id, $user_id, $note, $image_path);
        return $stmt->execute();
    }

    public function getByLocation($location_id) {
        $stmt = $this->conn->prepare(
            "SELECT li.*, u.name AS user_name
            FROM " . $this->table . " li
            LEFT JOIN users u ON li.user_id = u.id
            WHERE li.location_id = ?
            ORDER BY li.created_at DESC"
        );
        $stmt->bind_param("i", $location_id);
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
}
?>
