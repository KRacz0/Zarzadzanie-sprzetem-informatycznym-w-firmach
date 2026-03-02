<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/../controllers/LocationController.php';
require_once __DIR__ . '/../controllers/MaintenanceController.php';

$locationController = new LocationController();
$locations = $locationController->showLocations();

$locationId = isset($_GET['location_id']) ? (int) $_GET['location_id'] : null;
$maintenanceController = new MaintenanceController();

$maintenanceRecords = $locationId
    ? $maintenanceController->getMaintenanceByLocation($locationId)
    : $maintenanceController->getAllMaintenance();
$currentUserId = $_SESSION['user_id'] ?? null;
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Historia Serwisowania</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/static/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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

    <!-- Main Content -->
    <div class="main-content p-4 flex-grow-1">
        <div id="eventAlertContainer"></div>

        <div class="container-fluid">
            <h2 class="mb-4">Historia Serwisowania</h2>

            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <form method="GET" action="/maintenance" class="mb-3">
                        <label for="location" class="form-label">Wybierz lokalizację</label>
                        <select name="location_id" id="location" class="form-select" onchange="this.form.submit()">
                            <option value="">-- Wszystkie lokalizacje --</option>
                            <?php while ($row = $locations->fetch_assoc()) : ?>
                                <option value="<?= e($row['id']); ?>" <?= $locationId == $row['id'] ? 'selected' : ''; ?>>
                                    <?= e($row['name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </form>
                </div>
            </div>

            <?php if ($locationId): ?>
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <h4 class="mb-3">Dodaj Serwis</h4>
                        <form method="POST" action="/add-maintenance" enctype="multipart/form-data" class="add-maintenance-form">
                            <input type="hidden" name="location_id" value="<?= e($locationId); ?>">
                            <input type="hidden" name="user_id" value="<?= e($currentUserId ? (int) $currentUserId : 0); ?>">
                            <div class="mb-3">
                                <label class="form-label">Opis Serwisu</label>
                                <textarea name="description" class="form-control" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Dodaj Zdjęcie</label>
                                <input type="file" name="image" class="form-control">
                            </div>
                            <button type="submit" class="btn bg-red">Zapisz Serwis</button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>

            <div class="card shadow-sm">
            <div class="card-body">
            <h4 class="mb-3">Lista Serwisów</h4>

        <?php if ($maintenanceRecords && $maintenanceRecords->num_rows > 0): ?>
            <?php
            // Grupowanie po miesiącu
            $groupedRecords = [];

            while ($row = $maintenanceRecords->fetch_assoc()) {
                $date = new DateTime($row['service_date']);
                $monthKey = $date->format('Y-m');
                $groupedRecords[$monthKey][] = $row;
            }

            $monthIndex = 0;
            ?>

            <div class="accordion" id="maintenanceAccordion">
                <?php foreach ($groupedRecords as $month => $records): ?>
                    <?php
                    $formatter = new IntlDateFormatter(
                        'pl_PL',
                        IntlDateFormatter::LONG,
                        IntlDateFormatter::NONE,
                        'Europe/Warsaw',
                        IntlDateFormatter::GREGORIAN,
                        'LLLL yyyy'
                    );
                    
                    $date = new DateTime("$month-01");
                    $formattedMonth = ucfirst($formatter->format($date));
                    
                    $innerAccordionId = "accordion-inner-$monthIndex";
                    ?>

                    <div class="accordion-item">
                        <h2 class="accordion-header" id="heading-month-<?= e($monthIndex); ?>">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#collapse-month-<?= e($monthIndex); ?>" aria-expanded="false">
                                <?= e($formattedMonth); ?> <span class="ms-2 badge bg-secondary"><?= e(count($records)); ?> serwisów</span>
                            </button>
                        </h2>
                        <div id="collapse-month-<?= e($monthIndex); ?>" class="accordion-collapse collapse"
                             data-bs-parent="#maintenanceAccordion">
                            <div class="accordion-body">
                                <div class="accordion" id="<?= e($innerAccordionId); ?>">
                                    <?php foreach ($records as $record): ?>
                                        <div class="accordion-item">
                                            <h2 class="accordion-header" id="heading-<?= e($record['id']); ?>">
                                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                                        data-bs-target="#collapse-<?= e($record['id']); ?>" aria-expanded="false">
                                                    <?= e($record['location_name'] ?? 'Nieznana lokalizacja'); ?> (<?= e($record['service_date'] ?? 'Brak daty'); ?>)
                                                </button>
                                            </h2>
                                            <div id="collapse-<?= e($record['id']); ?>" class="accordion-collapse collapse"
                                                 data-bs-parent="#<?= e($innerAccordionId); ?>">
                                                <div class="accordion-body">
                                                    <p><strong>Opis:</strong> <?= e($record['description'] ?? 'Brak opisu'); ?></p>
                                                    <?php if (!empty($record['image_path'])): ?>
                                                        <p>
                                                            <a href="<?= e($record['image_path']); ?>" target="_blank">
                                                                <img src="<?= e($record['image_path']); ?>" width="150"
                                                                     onerror="this.onerror=null;this.src='/images/maintenance_images/default.jpg';">
                                                            </a>
                                                        </p>
                                                    <?php endif; ?>
                                                    <p><strong>Użytkownik:</strong> <?= e($record['user_name'] ?? 'Nieznany użytkownik'); ?></p>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php $monthIndex++; ?>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="text-warning"> Brak dostępnych serwisów.</p>
        <?php endif; ?>
    </div>
</div>

        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
$(document).ready(function () {
    $(".add-maintenance-form").on("submit", function (event) {
        event.preventDefault();
        let form = $(this);
        let formData = new FormData(this);

        $.ajax({
            url: "/add-maintenance.php",
            type: "POST",
            data: formData,
            processData: false,
            contentType: false,
            success: function (response) {
                try {
                    let json = typeof response === "string" ? JSON.parse(response) : response;
                    if (json.success) {
                        showAlert("success", json.message);
                        setTimeout(function () {
                            location.reload();
                        }, 2000);
                    } else {
                        showAlert("danger", json.message);
                    }
                } catch (e) {
                    showAlert("danger", "Błąd przetwarzania odpowiedzi serwera.");
                }
            },
            error: function () {
                showAlert("danger", "Błąd połączenia z serwerem!");
            }
        });
    });

    function showAlert(type, message) {
        let alertBox = `
            <div class="alert alert-${type} alert-dismissible fade show mt-3" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
        $("#eventAlertContainer").html(alertBox);
        setTimeout(function () {
            $(".alert").fadeOut("slow", function () {
                $(this).remove();
            });
        }, 3000);
    }

    function fetchMaintenanceGrouped(locationId) {
    $.ajax({
        url: `/get-maintenance-history.php?location_id=${locationId}`,
        method: 'GET',
        dataType: 'json',
        success: function (response) {
            if (response.success && response.history) {
                renderGroupedMaintenance(response.history);
            } else {
                $('#groupedMaintenanceAccordion').html('');
                $('#noDataMsg').show();
            }
        },
        error: function () {
            $('#groupedMaintenanceAccordion').html('');
            $('#noDataMsg').text('Błąd ładowania danych.').show();
        }
    });
}

function renderGroupedMaintenance(data) {
    const container = $('#groupedMaintenanceAccordion');
    container.empty();
    $('#noDataMsg').hide();

    let monthIndex = 0;

    for (const [month, records] of Object.entries(data)) {
        const formattedMonth = new Date(`${month}-01`).toLocaleString('pl-PL', { month: 'long', year: 'numeric' });

        const outerId = `month-${monthIndex}`;
        const innerAccordionId = `records-${monthIndex}`;

        let innerItems = '';
        records.forEach((record, i) => {
            const id = record.id ?? `r${monthIndex}-${i}`;
            const imageTag = record.image_path ? `
                <a href="${record.image_path}" target="_blank">
                    <img src="${record.image_path}" width="150"
                        onerror="this.onerror=null;this.src='/images/maintenance_images/default.jpg';">
                </a>` : '';

            innerItems += `
                <div class="accordion-item">
                    <h2 class="accordion-header" id="heading-${id}">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                            data-bs-target="#collapse-${id}" aria-expanded="false">
                            ${record.service_date ?? 'Brak daty'}
                        </button>
                    </h2>
                    <div id="collapse-${id}" class="accordion-collapse collapse" data-bs-parent="#${innerAccordionId}">
                        <div class="accordion-body">
                            <p><strong>Opis:</strong> ${record.description ?? 'Brak opisu'}</p>
                            ${imageTag}
                            <p><strong>Użytkownik:</strong> ${record.user_name ?? 'Nieznany użytkownik'}</p>
                        </div>
                    </div>
                </div>
            `;
        });

        const monthItem = `
            <div class="accordion-item">
                <h2 class="accordion-header" id="month-heading-${monthIndex}">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                        data-bs-target="#collapse-month-${monthIndex}" aria-expanded="false">
                        ${formattedMonth} <span class="ms-2 badge bg-secondary">${records.length} serwisów</span>
                    </button>
                </h2>
                <div id="collapse-month-${monthIndex}" class="accordion-collapse collapse" data-bs-parent="#groupedMaintenanceAccordion">
                    <div class="accordion-body">
                        <div class="accordion" id="${innerAccordionId}">
                            ${innerItems}
                        </div>
                    </div>
                </div>
            </div>
        `;

        container.append(monthItem);
        monthIndex++;
    }
}

});
</script>
</body>
</html>
