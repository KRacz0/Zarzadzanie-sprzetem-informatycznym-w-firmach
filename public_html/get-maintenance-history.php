<?php
require_once __DIR__ . '../app/controllers/MaintenanceController.php';

header('Content-Type: application/json');
ob_start(); 

if (!isset($_GET['location_id']) || empty($_GET['location_id'])) {
    echo json_encode(["success" => false, "message" => "Błąd: Brak `location_id`."]);
    exit;
}

$locationId = (int) $_GET['location_id'];
error_log("Pobieram historię dla locationId: " . $locationId);

$maintenanceController = new MaintenanceController();
$historyResult = $maintenanceController->getMaintenanceByLocation($locationId);

$historyGrouped = [];

while ($row = $historyResult->fetch_assoc()) {
    if (!is_array($row)) {
        error_log("Błąd: `fetch_assoc()` zwróciło niepoprawne dane.");
        continue;
    }

    $date = new DateTime($row['service_date']);
    $monthKey = $date->format('Y-m'); // np. 2025-04

    if (!isset($historyGrouped[$monthKey])) {
        $historyGrouped[$monthKey] = [];
    }

    $row['image_path'] = (!empty($row['image_path'])) ? $row['image_path'] : null;
    $historyGrouped[$monthKey][] = $row;
}


$jsonOutput = json_encode([
    "success" => true,
    "history" => $historyGrouped
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);


if (json_last_error() !== JSON_ERROR_NONE) {
    error_log("Błąd JSON: " . json_last_error_msg());
    echo json_encode(["success" => false, "message" => "Błąd generowania JSON: " . json_last_error_msg()]);
} else {
    error_log("JSON wysłany: " . $jsonOutput);
    echo $jsonOutput;
}

ob_end_flush();
exit;
?>
