<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use Doctrine\DBAL\DriverManager;

class Database {
    private $host = "localhost";
    private $db_name = "";
    private $username = ""; 
    private $password = "";
    private $conn;
    private $dbal;

    public function __construct() {
        $this->connect();
        $this->connectDbal();
        $this->checkAndCreateTables();
    }

    public function connect() {
        $this->conn = new mysqli($this->host, $this->username, $this->password, $this->db_name);
        if ($this->conn->connect_error) {
            die("Błąd połączenia: " . $this->conn->connect_error);
        }
    }

    public function connectDbal() {
        if ($this->dbal !== null) {
            return;
        }

        $this->dbal = DriverManager::getConnection([
            'driver' => 'mysqli',
            'host' => $this->host,
            'dbname' => $this->db_name,
            'user' => $this->username,
            'password' => $this->password,
            'charset' => 'utf8mb4',
        ]);
    }

    public function getConnection() {
        return $this->conn;
    }

    public function getDbalConnection() {
        return $this->dbal;
    }

    private function checkAndCreateTables() {
        $queries = [
            "CREATE TABLE IF NOT EXISTS locations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )",

            "CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL,
                password VARCHAR(255) NOT NULL,
                role VARCHAR(50) NOT NULL DEFAULT 'newuser',
                must_change_password TINYINT(1) NOT NULL DEFAULT 0
            )",

            "CREATE TABLE IF NOT EXISTS maintenance (
                id INT AUTO_INCREMENT PRIMARY KEY,
                location_id INT,
                user_id INT,
                service_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                description TEXT,
                image_path VARCHAR(255),
                FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
            )",
            "CREATE TABLE IF NOT EXISTS location_images (
                id INT AUTO_INCREMENT PRIMARY KEY,
                location_id INT NOT NULL,
                user_id INT,
                note TEXT,
                image_path VARCHAR(255) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
            )",
            "CREATE TABLE IF NOT EXISTS rate_limits (
                id VARCHAR(255) NOT NULL PRIMARY KEY,
                tokens INT NOT NULL,
                reset INT NOT NULL
            )",

        ];

        foreach ($queries as $query) {
            if ($this->conn->query($query) === FALSE) {
                echo "Błąd przy tworzeniu tabeli: " . $this->conn->error;
            }
        }

        $this->ensureUsersColumns();
    }

    private function ensureUsersColumns() {
        $columnsToEnsure = [
            'password' => "ALTER TABLE users ADD COLUMN password VARCHAR(255) NOT NULL DEFAULT ''",
            'role' => "ALTER TABLE users ADD COLUMN role VARCHAR(50) NOT NULL DEFAULT 'newuser'",
            'must_change_password' => "ALTER TABLE users ADD COLUMN must_change_password TINYINT(1) NOT NULL DEFAULT 0",
        ];

        foreach ($columnsToEnsure as $column => $query) {
            $result = $this->conn->query("SHOW COLUMNS FROM users LIKE '{$column}'");
            if ($result && $result->num_rows === 0) {
                $this->conn->query($query);
            }
        }
    }
}
?>
