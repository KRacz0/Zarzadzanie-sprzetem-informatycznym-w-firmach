<?php
require_once __DIR__ . '/../config/Database.php';

class User {
    private $conn;
    private $table = 'users';

    public function __construct($conn = null) {
        if ($conn) {
            $this->conn = $conn;
            return;
        }

        $db = new Database();
        $this->conn = $db->getConnection();
    }

    public function getAllUsers() {
        $stmt = $this->conn->prepare("SELECT id, name, email, role, must_change_password FROM " . $this->table . " ORDER BY name ASC");
        $stmt->execute();
        return $stmt->get_result();
    }

    public function create($name, $email, $password, $role = 'newuser', $mustChangePassword = 0) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->conn->prepare("INSERT INTO " . $this->table . " (name, email, password, role, must_change_password) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssi", $name, $email, $hashedPassword, $role, $mustChangePassword);
        return $stmt->execute();
    }

    public function findByEmail($email) {
        $stmt = $this->conn->prepare("SELECT id, name, email, password, role, must_change_password FROM " . $this->table . " WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function getById($id) {
        $stmt = $this->conn->prepare("SELECT id, name, email, role, must_change_password FROM " . $this->table . " WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function updateRole($id, $role) {
        $stmt = $this->conn->prepare("UPDATE " . $this->table . " SET role = ? WHERE id = ?");
        $stmt->bind_param("si", $role, $id);
        return $stmt->execute();
    }

    public function updatePassword($id, $password, $mustChangePassword = 0) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->conn->prepare("UPDATE " . $this->table . " SET password = ?, must_change_password = ? WHERE id = ?");
        $stmt->bind_param("sii", $hashedPassword, $mustChangePassword, $id);
        return $stmt->execute();
    }

    public function setMustChangePassword($id, $mustChangePassword = 1) {
        $stmt = $this->conn->prepare("UPDATE " . $this->table . " SET must_change_password = ? WHERE id = ?");
        $stmt->bind_param("ii", $mustChangePassword, $id);
        return $stmt->execute();
    }

    public function deleteUser($id) {
        $stmt = $this->conn->prepare("DELETE FROM " . $this->table . " WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    public function verifyCredentials($email, $password) {
        $user = $this->findByEmail($email);
        if (!$user) {
            return null;
        }

        if (!password_verify($password, $user['password'])) {
            return null;
        }

        return $user;
    }
}
?>
