<?php
require_once __DIR__ . '/../../app/models/MailSender.php';
require_once __DIR__ . '/../../app/models/Maintenance.php';
require_once __DIR__ . '/../../app/config/Database.php';

$db = new Database();
$conn = $db->getConnection();

// === Przypomnienia o serwisach ===
$maintenance = new Maintenance($conn);
$locations = $maintenance->getLocationsWithOutdatedService();

$failedLocations = [];

foreach ($locations as $location) {
    $locationName = $location['name'];
    $lastServiceDate = $location['last_service_date'] ?? 'Brak danych';

    if (!MailSender::sendReminderEmail($locationName, $lastServiceDate)) {
        echo "Błąd wysyłki dla lokalizacji: $locationName <br>";
        $failedLocations[] = $locationName;
    } else {
        echo "Wysłano przypomnienie dla lokalizacji: $locationName <br>";
    }
}

if (!empty($failedLocations)) {
    MailSender::sendFailureReport($failedLocations);
    echo "Wysłano raport o błędach do administratora. <br>";
}

// === Przypomnienia o wydarzeniach ===
$tomorrow = (new DateTime('+1 day'))->format('Y-m-d');

$query = "SELECT * FROM calendar_events WHERE event_date = ? AND reminder_sent = 0";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $tomorrow);
$stmt->execute();
$result = $stmt->get_result();

while ($event = $result->fetch_assoc()) {
    $userIds = explode(',', $event['user_id']);
    $title = $event['title'];
    $description = $event['description'];
    $eventDate = $event['event_date'];

    foreach ($userIds as $uid) {
        $uid = (int) trim($uid);
        if ($uid <= 0) continue;

        $userStmt = $conn->prepare("SELECT name, email FROM users WHERE id = ?");
        $userStmt->bind_param("i", $uid);
        $userStmt->execute();
        $userResult = $userStmt->get_result();

        if ($user = $userResult->fetch_assoc()) {
            $success = MailSender::sendEventReminderEmail(
                $user['name'],
                $title,
                $description,
                $eventDate,
                $user['email']
            );

            if ($success) {
                echo "Wysłano do: {$user['email']}<br>";
            } else {
                echo "Błąd wysyłki do: {$user['email']}<br>";
            }
        }
    }

    $update = $conn->prepare("UPDATE calendar_events SET reminder_sent = 1 WHERE id = ?");
    $update->bind_param("i", $event['id']);
    $update->execute();
}

echo "<br> Skrypt został zakończony.";
