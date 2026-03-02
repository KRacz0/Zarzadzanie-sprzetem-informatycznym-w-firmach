<?php
require_once __DIR__ . '/../models/Maintenance.php';
require_once __DIR__ . '/../helpers/Security.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

class MaintenanceController {
    private $maintenance;

    public function __construct() {
        $this->maintenance = new Maintenance();
    }

    public function createMaintenance() {
        header('Content-Type: application/json');
        ob_start();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(["success" => false, "message" => "Błędna metoda żądania."]);
            exit;
        }

        if (!isset($_POST['location_id'], $_POST['user_id'], $_POST['description'])) {
            echo json_encode(["success" => false, "message" => "Brak wymaganych pól."]);
            exit;
        }

        $location_id = (int) $_POST['location_id'];
        $user_id = (int) $_POST['user_id'];
        $description = sanitize_input($_POST['description']);
        $image_path = null;

        if (!empty($_FILES['image']['name'])) {
            $upload_dir = __DIR__ . '/../../public_html/images/maintenance_images/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $extension = strtolower($file_extension);
            if (!in_array($extension, $allowed_extensions, true)) {
                echo json_encode(["success" => false, "message" => "Nieobsługiwany format pliku."]);
                exit;
            }

            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($_FILES['image']['tmp_name']);
            $allowed_mime = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!in_array($mime, $allowed_mime, true)) {
                echo json_encode(["success" => false, "message" => "Nieprawidłowy typ pliku."]);
                exit;
            }
            $timestamp = date("Y-m-d_H-i-s");
            $new_filename = "maintenance_{$location_id}_{$timestamp}.{$file_extension}";

            $image_path = "/images/maintenance_images/" . $new_filename;
            $full_path = $upload_dir . $new_filename;

            if (!move_uploaded_file($_FILES['image']['tmp_name'], $full_path)) {
                error_log("Błąd przy zapisie pliku: " . $_FILES['image']['tmp_name']);
                echo json_encode(["success" => false, "message" => "Błąd zapisu pliku"]);
                exit;
            }
        }

        $success = $this->maintenance->create($location_id, $user_id, $description, $image_path);

        $historyResult = $this->getMaintenanceByLocation($location_id);
        $historyData = [];

        while ($row = $historyResult->fetch_assoc()) {
            $row['image_path'] = !empty($row['image_path']) ? $row['image_path'] : null;
            $historyData[] = $row;
        }

        if (ob_get_length()) {
            ob_clean();
        }
        ob_end_flush();

        echo json_encode([
            "success" => $success,
            "message" => $success ? "Serwis dodany pomyślnie!" : "Błąd podczas dodawania serwisu.",
            "history" => $historyData
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public function getMaintenanceByLocation($location_id) {
        return $this->maintenance->getHistoryByLocation($location_id);
    }

    public function getAllMaintenance() {
        $query = "
            SELECT m.*, l.name AS location_name, u.name AS user_name,
                   YEAR(m.service_date) AS service_year,
                   MONTH(m.service_date) AS service_month
            FROM maintenance m
            LEFT JOIN locations l ON m.location_id = l.id
            LEFT JOIN users u ON m.user_id = u.id
            ORDER BY m.service_date DESC
        ";
        return $this->maintenance->executeQuery($query);
    }
}
