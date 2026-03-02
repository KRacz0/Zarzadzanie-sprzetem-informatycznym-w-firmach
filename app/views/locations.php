<?php
require_once __DIR__ . '/../controllers/LocationController.php';

$controller = new LocationController();
$locations = $controller->showLocations();
?>

<!DOCTYPE html>
<html lang="pl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lokalizacje - Panel Zarządzania</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="/static/css/style.css">
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

    <!-- Main content -->
    <div class="main-content p-4 flex-grow-1">
        <div class="container-fluid">
            <h2 class="mb-4">Lista Lokalizacji</h2>

            <?php if (isset($_GET['message'])): ?>
                <div class="alert alert-success"><?= e($_GET['message']); ?></div>
            <?php endif; ?>

            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <table class="table table-striped align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Nazwa</th>
                                <th>Akcje</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $locations->fetch_assoc()) : ?>
                                <tr>
                                    <td><?= e($row['id']); ?></td>
                                    <td>
                                        <a href="/location?id=<?= e($row['id']); ?>" class="text-decoration-none">
                                            <?= e($row['name']); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <a href="/location?id=<?= e($row['id']); ?>" class="btn btn-primary btn-sm">
                                            <i class="fas fa-images"></i> Zdjęcia
                                        </a>
                                        <form method="POST" action="/locations" class="d-inline">
                                            <input type="hidden" name="delete_id" value="<?= e($row['id']); ?>">
                                            <button type="submit" class="btn btn-danger btn-sm">
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

            <div class="card shadow-sm">
                <div class="card-body">
                    <h4 class="mb-3">Dodaj nową lokalizację</h4>
                    <form method="POST" action="/locations">
                        <div class="mb-3">
                            <label for="name" class="form-label">Nazwa lokalizacji</label>
                            <input type="text" id="name" name="name" class="form-control" required>
                        </div>
                        <button type="submit" class="btn bg-red">
                            <i class="fas fa-plus-circle"></i> Dodaj
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
