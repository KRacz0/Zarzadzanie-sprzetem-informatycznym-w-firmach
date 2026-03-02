<?php
require_once __DIR__ . '/../../app/models/Calendar.php'; 
require_once __DIR__ . '/../../app/config/Database.php';
require_once __DIR__ . '/../../app/helpers/Security.php';

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

$response = ["success" => false, "message" => "Nie udało się dodać wydarzenia."];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response["message"] = "Błąd: Nieprawidłowa metoda żądania.";
    echo json_encode($response);
    exit;
}

if (!isset($_POST['title'], $_POST['description'], $_POST['event_date'], $_POST['user_ids'], $_POST['location_id'])) {
    $response["message"] = "Błąd: Brak wymaganych pól.";
    echo json_encode($response);
    exit;
}

$title = sanitize_input($_POST['title']);
$description = sanitize_input($_POST['description']);
$eventDate = sanitize_input($_POST['event_date']);
$locationId = (int) $_POST['location_id'];
$userIds = sanitize_input($_POST['user_ids']);

if (
    empty($title) || empty($description) || empty($eventDate) ||
    !is_array($userIds) || empty($userIds) || empty($locationId)
) {
    $response["message"] = "Błąd: Wszystkie pola muszą być poprawnie wypełnione.";
    echo json_encode($response);
    exit;
}

$userIdCsv = implode(',', array_map('intval', $userIds));

$db = new Database();
$dbal = $db->getDbalConnection();

try {
    $dbal->insert('calendar_events', [
        'title' => $title,
        'description' => $description,
        'event_date' => $eventDate,
        'user_id' => $userIdCsv,
        'location_id' => $locationId,
    ]);
    $response["success"] = true;
    $response["message"] = "Wydarzenie zostało dodane.";
} catch (Throwable $exception) {
    $response["message"] = "Błąd SQL: " . $exception->getMessage();
}

file_put_contents(__DIR__ . '/debug.log', print_r([
    'POST' => $_POST,
    'userIdCsv' => $userIdCsv ?? null,
    'SQL Error' => $response["success"] ? null : $response["message"],
], true), FILE_APPEND);

echo json_encode($response);
exit;
