<?php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Location.php';
require_once __DIR__ . '/../models/Calendar.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Maintenance.php';


$db = new Database();
$conn = $db->getConnection();
$calendar = new Calendar($conn);
$location = new Location();
$user = new User($conn);
$maintenance = new Maintenance();

$locationsWithNextService = $maintenance->getAllLocationsWithLastServiceDate();
$totalLocations = $location->getTotalLocations();
$totalAssets = $maintenance->getTotalMaintenance();
$events = $calendar->getEvents();
$locations = $location->getAll();
$users = $user->getAllUsers();
?>

<!DOCTYPE html>
<html lang="pl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="/static/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.7/index.global.min.css">
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.7/index.global.min.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/js-cookie@3.0.1/dist/js.cookie.min.js"></script>
</head>

<body>

<div class="d-flex">
    <!-- Sidebar -->
    <div class="bg-dark text-white sidebar">
        <div class="p-3 text-center">
            <img src="/static/images/logo.png" alt="Logo" class="img-fluid mb-4">
            <h5 class="text-white">Panel Zarządzania</h5>
            <ul class="nav flex-column mt-4">
                <li class="nav-item">
                    <a class="nav-link text-white" href="/home">
                        <i class="fas fa-home"></i> Strona główna
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white" href="/locations">
                        <i class="fas fa-map-marker-alt"></i> Lista lokalizacji
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white" href="/maintenance">
                        <i class="fas fa-tools"></i> Historia serwisowania
                    </a>
                </li>
                <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="/users">
                            <i class="fas fa-users"></i> Użytkownicy
                        </a>
                    </li>
                <?php endif; ?>
                <li class="nav-item">
                    <a class="nav-link text-white" href="/logout">
                        <i class="fas fa-sign-out-alt"></i> Wyloguj
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <!-- Main content -->
<div class="main-content p-4 flex-grow-1">
    <div id="eventAlertContainer"></div>

    <!-- Statystyki + Najbliższe Serwisy + Kalendarz razem -->
<div class="row mt-4">
    <!-- Kafelki statystyk -->
    <div class="col-md-8">
        <div class="row">
            <div class="col-md-6">
                <div class="card shadow-sm mb-3">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <p class="card-text display-6 text-dark"><?= e($totalLocations ?? 0); ?></p>
                            <h5 class="card-title text-dark">Lokalizacje</h5>
                        </div>
                        <div class="icon-square">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card shadow-sm mb-3">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <p class="card-text display-6 text-dark"><?= e($totalAssets ?? 0); ?></p>
                            <h5 class="card-title text-dark">Serwisy</h5>
                        </div>
                        <div class="icon-square">
                            <i class="fas fa-tools"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Kalendarz -->
            <div class="col-12">
                <div class="card shadow-sm h-100">
                    <div class="card-body text-start">
                        <h4 class="mb-3 text-dark">Kalendarz Wydarzeń</h4>
                        <div id="calendar-container" style="height: 600px;">
                            <div id="calendar"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Najbliższe Serwisy obok -->
    <div class="col-md-4">
    <div class="card shadow-sm h-100" style="height: 100%; max-height: 825px; overflow-y: auto;">
            <div class="card-body text-start">
                <h5 class="card-title text-dark">Najbliższe Serwisy</h5>
                <ul class="list-group list-group-flush">
                    <?php foreach ($locationsWithNextService as $loc): ?>
                        <?php
                            $lastService = $loc['last_service_date'] ?? null;
                            $locationName = $loc['name'];
                            $nextDueDate = $lastService 
                                ? (new DateTime($lastService))->modify('+3 months') 
                                : new DateTime('2025-04-21');
                            $today = new DateTime();
                            $interval = $today->diff($nextDueDate);
                            $days = (int)$interval->format('%r%a');
                            $badgeColor = $days < 0 ? 'danger' : ($days <= 7 ? 'warning' : 'success');
                            $prefix = $days < 0 ? '-' : '';
                        ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <?= e($locationName); ?>
                            <span class="badge bg-<?= e($badgeColor); ?>">
                                <?= e($prefix . abs($days)); ?> dni
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
</div>


<!-- Skrypt do wyświetlania kalendarza -->
<script>
    $(document).ready(function() {
        var events = [
            <?php 
            $eventsArray = [];
            foreach ($events as $event) {
                $eventsArray[] = json_encode([
                    'title' => $event['title'],
                    'start' => $event['event_date'],
                    'description' => $event['description']
                ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
            }
            echo implode(',', $eventsArray);
            ?>
        ];

        var calendarEl = document.getElementById('calendar');
        var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            locale: 'pl',
            height: '600px',
            events: events,
            eventClick: function(info) {
                alert('Wydarzenie: ' + info.event.title + '\nOpis: ' + info.event.extendedProps.description);
            }
        });
        calendar.render();
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>

<!-- Modal do dodawania wydarzeń -->
<div class="modal fade" id="addEventModal" tabindex="-1" aria-labelledby="addEventModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="addEventForm" action="/Calendar/add-event.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="addEventModalLabel">Dodaj Wydarzenie</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="event_date" id="eventDateInput">
                    <div class="mb-3">
                        <label for="title" class="form-label">Typ wydarzenia</label>
                        <select class="form-select" name="title" required>
                            <option value="Aktualizacja serwera">Aktualizacja serwera</option>
                            <option value="Serwis">Serwis</option>
                            <option value="Inne">Inne</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Opis</label>
                        <textarea class="form-control" name="description" id="description" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="location" class="form-label">Lokalizacja</label>
                        <select class="form-select" name="location_id" required>
                            <?php while ($row = $locations->fetch_assoc()): ?>
                                <option value="<?= e($row['id']); ?>"><?= e($row['name']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Powiadom następujących użytkowników:</label>
                        <div class="form-check" style="max-height: 200px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: .375rem; padding: 10px;">
                            <?php foreach ($users as $user): ?>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="user_ids[]" value="<?= e($user['id']); ?>" id="user_<?= e($user['id']); ?>">
                                <label class="form-check-label" for="user_<?= e($user['id']); ?>">
                            <?= e($user['name']); ?> (<?= e($user['email']); ?>)
                        </label>
                    </div>
                        <?php endforeach; ?>
                    </div>
                </div>


                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
                    <button type="submit" class="btn btn-primary">Zapisz</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- Modal do wyświetlania szczegółów wydarzenia -->
<div class="modal fade" id="eventDetailsModal" tabindex="-1" aria-labelledby="eventDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="eventDetailsModalLabel">Szczegóły Wydarzenia</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h5 id="eventTitle"></h5>
                <p><strong>Lokalizacja:</strong> <span id="eventLocation"></span></p>
                <p><strong>Opis:</strong> <span id="eventDescription"></span></p>
                <p><strong>Data:</strong> <span id="eventDate"></span></p>
                <p><strong>Użytkownicy:</strong> <span id="eventUsers"></span></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Zamknij</button>
            </div>
        </div>
    </div>
</div>


<script>
    $(document).ready(function () {
        var events = [
            <?php 
            $eventsArray = [];
            foreach ($events as $event) {
                $eventsArray[] = json_encode([
                    'title' => $event['title'],
                    'start' => $event['event_date'],
                    'description' => $event['description'],
                    'location_name' => $event['location_name'],
                    'user_name' => $event['user_name'],
                    'user_email' => $event['user_email']
                ], JSON_UNESCAPED_UNICODE);
            }
            echo implode(',', $eventsArray);
            ?>
        ];

        var calendarEl = document.getElementById('calendar');
        var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            locale: 'pl',
            height: '600px',
            events: events,
            dateClick: function (info) {
                $('#eventDateInput').val(info.dateStr);
                $('#addEventModal').modal('show');
            },
            eventClick: function (info) {
                $('#eventTitle').text(info.event.title);
                $('#eventLocation').text(info.event.extendedProps.location_name || 'Brak lokalizacji');
                $('#eventDescription').text(info.event.extendedProps.description);
                $('#eventDate').text(info.event.start.toLocaleDateString());

                const userName = info.event.extendedProps.user_name || 'Brak danych';
                const userEmail = info.event.extendedProps.user_email || 'Brak danych';
                $('#eventUsers').text(userName);

                $('#eventDetailsModal').modal('show');
            }
        });
        calendar.render();

        $('#addEventForm').on('submit', function (e) {
            e.preventDefault();
            $.ajax({
                url: $(this).attr('action'),
                method: $(this).attr('method'),
                data: $(this).serialize(),
                dataType: 'json',
                success: function (response) {
                    if (response.success) {
                        $('#addEventModal').modal('hide');
                        showEventAlert("success", response.message);

                        setTimeout(function () {
                            location.reload();
                        }, 1500);
                    } else {
                        showEventAlert("danger", response.message);
                    }
                },
                error: function (xhr, status, error) {
                    console.log("AJAX error:", xhr.responseText);
                    showEventAlert("danger", "Wystąpił błąd podczas dodawania wydarzenia.");
            }
            });
        });

        function showEventAlert(type, message) {
            let alertHtml = `
                <div class="alert alert-${type} alert-dismissible fade show mt-3" role="alert">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            `;

            $('#eventAlertContainer').html(alertHtml);


            setTimeout(function () {
                $(".alert").fadeOut("slow", function () {
                    $(this).remove();
                });
            }, 3000);
        }
    });
</script>



</body>
</html>
