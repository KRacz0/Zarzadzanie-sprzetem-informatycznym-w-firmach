<?php
require_once __DIR__ . '/../app/controllers/MaintenanceController.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');
session_start();

if (empty($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['admin', 'user'], true)) {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "Brak uprawnień."]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    ob_start();
    $controller = new MaintenanceController();
    $controller->createMaintenance();
    $output = ob_get_clean(); 

    if (!$output || empty(trim($output))) {
        error_log("Otrzymano pustą odpowiedź od `createMaintenance()`.");
        echo json_encode(["success" => false, "message" => "Błąd wewnętrzny serwera."]);
    } else {
        echo $output;
    }
    exit;
}
?>
