<?php
require_once __DIR__ . '/../controllers/LocationController.php';
require_once __DIR__ . '/../controllers/LocationImageController.php';
require_once __DIR__ . '/../controllers/MaintenanceController.php';

$locationId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($locationId <= 0) {
    header("Location: /locations?message=Nie+wybrano+lokalizacji");
    exit;
}

$locationController = new LocationController();
$location = $locationController->getLocation($locationId);
if (!$location) {
    header("Location: /locations?message=Nie+znaleziono+lokalizacji");
    exit;
}

$imageController = new LocationImageController();
$maintenanceController = new MaintenanceController();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'upload') {
        $note = trim($_POST['note'] ?? '');
        $imageController->addImage($locationId, (int) $_SESSION['user_id'], $note);
    }

    if ($_POST['action'] === 'delete' && isset($_POST['image_id'])) {
        $imageController->deleteImage((int) $_POST['image_id'], $locationId);
    }
}

$images = $imageController->getImagesByLocation($locationId);
$maintenanceRecords = $maintenanceController->getMaintenanceByLocation($locationId);
?>

<!DOCTYPE html>
<html lang="pl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zdjęcia lokalizacji - Panel Zarządzania</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="/static/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body>
<div class="d-flex">
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

    <div class="main-content p-4 flex-grow-1">
        <div class="container-fluid">
            <div class="d-flex align-items-center justify-content-between mb-4">
                <div>
                    <h2 class="mb-1">Zdjęcia lokalizacji</h2>
                    <p class="text-muted mb-0">Lokalizacja: <?= e($location['name']); ?></p>
                </div>
                <a href="/locations" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> Wróć do listy
                </a>
            </div>

            <?php if (isset($_GET['message'])): ?>
                <div class="alert alert-success"><?= e($_GET['message']); ?></div>
            <?php endif; ?>

            <div class="card shadow-sm">
                <div class="card-body">
                    <h4 class="mb-3">Załączone zdjęcia</h4>
                    <div class="table-responsive">
                        <table class="table table-striped align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th>Zdjęcie</th>
                                    <th>Notatka</th>
                                    <th>Dodane przez</th>
                                    <th>Data</th>
                                    <th>Akcje</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($images->num_rows === 0): ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">Brak zdjęć dla tej lokalizacji.</td>
                                    </tr>
                                <?php endif; ?>
                                <?php while ($image = $images->fetch_assoc()) : ?>
                                    <tr>
                                        <td>
                                            <a href="<?= e($image['image_path']); ?>" target="_blank">
                                                <img src="<?= e($image['image_path']); ?>" width="150"
                                                    onerror="this.onerror=null;this.src='/images/maintenance_images/default.jpg';">
                                            </a>
                                        </td>
                                        <td><?= nl2br(e($image['note'] ?? '')); ?></td>
                                        <td><?= e($image['user_name'] ?? 'Brak danych'); ?></td>
                                        <td><?= e($image['created_at']); ?></td>
                                        <td>
                                            <form method="POST" action="/location?id=<?= e($locationId); ?>" class="d-inline">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="image_id" value="<?= e($image['id']); ?>">
                                                <button type="submit" class="btn btn-danger btn-sm"
                                                    onclick="return confirm('Czy na pewno chcesz usunąć to zdjęcie?');">
                                                    <i class="fas fa-trash-alt"></i> Usuń
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="card shadow-sm my-4">
                <div class="card-body">
                    <h4 class="mb-3">Historia serwisowania</h4>
                    <?php if ($maintenanceRecords && $maintenanceRecords->num_rows > 0): ?>
                        <?php
                        $groupedRecords = [];

                        while ($row = $maintenanceRecords->fetch_assoc()) {
                            $date = new DateTime($row['service_date']);
                            $monthKey = $date->format('Y-m');
                            $groupedRecords[$monthKey][] = $row;
                        }

                        $monthIndex = 0;
                        ?>

                        <div class="accordion" id="locationMaintenanceAccordion">
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

                                $innerAccordionId = "location-accordion-inner-$monthIndex";
                                ?>

                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="location-heading-month-<?= e($monthIndex); ?>">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                                data-bs-target="#location-collapse-month-<?= e($monthIndex); ?>" aria-expanded="false">
                                            <?= e($formattedMonth); ?> <span class="ms-2 badge bg-secondary"><?= e(count($records)); ?> serwisów</span>
                                        </button>
                                    </h2>
                                    <div id="location-collapse-month-<?= e($monthIndex); ?>" class="accordion-collapse collapse"
                                         data-bs-parent="#locationMaintenanceAccordion">
                                        <div class="accordion-body">
                                            <div class="accordion" id="<?= e($innerAccordionId); ?>">
                                                <?php foreach ($records as $record): ?>
                                                    <div class="accordion-item">
                                                        <h2 class="accordion-header" id="location-heading-<?= e($record['id']); ?>">
                                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                                                    data-bs-target="#location-collapse-<?= e($record['id']); ?>" aria-expanded="false">
                                                                <?= e($record['service_date'] ?? 'Brak daty'); ?>
                                                            </button>
                                                        </h2>
                                                        <div id="location-collapse-<?= e($record['id']); ?>" class="accordion-collapse collapse"
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
                        <p class="text-warning">Brak dostępnych serwisów dla tej lokalizacji.</p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h4 class="mb-3">Dodaj zdjęcie</h4>
                    <form method="POST" action="/location?id=<?= e($locationId); ?>" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="upload">
                        <div class="mb-3">
                            <label for="image" class="form-label">Zdjęcie</label>
                            <input type="file" id="image" name="image" class="form-control" accept="image/*" required>
                        </div>
                        <div class="mb-3">
                            <label for="note" class="form-label">Notatka</label>
                            <textarea id="note" name="note" class="form-control" rows="3"
                                placeholder="Dodaj informację o zdjęciu"></textarea>
                        </div>
                        <button type="submit" class="btn bg-red">
                            <i class="fas fa-upload"></i> Załącz zdjęcie
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
