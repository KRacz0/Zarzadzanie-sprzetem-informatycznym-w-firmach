<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/../controllers/UserController.php';

$userController = new UserController();
$users = $userController->showUsers();
$currentUserId = (int) ($_SESSION['user_id'] ?? 0);
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Użytkownicy - Panel Zarządzania</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="/static/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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
                <h2 class="mb-4">Lista Użytkowników</h2>

                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <div class="accordion accordion-flush" id="usersAccordion">
                            <?php while ($row = $users->fetch_assoc()) : ?>
                                <?php $userId = (int) $row['id']; ?>
                                <?php $accordionId = 'user-' . $userId; ?>
                                <?php $isCurrentUser = $userId === $currentUserId; ?>
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="heading-<?= e($accordionId); ?>">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-<?= e($accordionId); ?>" aria-expanded="false" aria-controls="collapse-<?= e($accordionId); ?>">
                                            <div class="d-flex flex-column flex-md-row gap-2 gap-md-3">
                                                <span class="fw-semibold"><?= e($row['name']); ?></span>
                                                <span class="text-muted"><?= e($row['email']); ?></span>
                                                <span class="badge bg-secondary text-uppercase"><?= e($row['role']); ?></span>
                                                <?php if (!empty($row['must_change_password'])) : ?>
                                                    <span class="badge bg-warning text-dark">Wymuszona zmiana hasła</span>
                                                <?php endif; ?>
                                            </div>
                                        </button>
                                    </h2>
                                    <div id="collapse-<?= e($accordionId); ?>" class="accordion-collapse collapse" aria-labelledby="heading-<?= e($accordionId); ?>" data-bs-parent="#usersAccordion">
                                        <div class="accordion-body">
                                            <div class="row g-3 align-items-start">
                                                <div class="col-12 col-xl-4">
                                                    <p class="mb-1"><strong>ID:</strong> <?= e($row['id']); ?></p>
                                                    <p class="mb-1"><strong>Rola:</strong> <?= e($row['role']); ?></p>
                                                    <p class="mb-0"><strong>Wymuszona zmiana hasła:</strong> <?= !empty($row['must_change_password']) ? 'Tak' : 'Nie'; ?></p>
                                                </div>
                                                <div class="col-12 col-xl-8">
                                                    <form method="POST" action="/users" class="d-flex flex-wrap gap-2 align-items-center mb-3">
                                                        <input type="hidden" name="action" value="update_role">
                                                        <input type="hidden" name="user_id" value="<?= e($row['id']); ?>">
                                                        <select name="role" class="form-select form-select-sm w-auto">
                                                            <option value="newuser" <?= $row['role'] === 'newuser' ? 'selected' : ''; ?>>Newuser</option>
                                                            <option value="user" <?= $row['role'] === 'user' ? 'selected' : ''; ?>>User</option>
                                                            <option value="admin" <?= $row['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                                        </select>
                                                        <button type="submit" class="btn btn-outline-primary btn-sm">Zmień rolę</button>
                                                    </form>
                                                    <form method="POST" action="/users" class="d-flex flex-wrap gap-2 align-items-center mb-3">
                                                        <input type="hidden" name="action" value="update_password">
                                                        <input type="hidden" name="user_id" value="<?= e($row['id']); ?>">
                                                        <input type="password" name="password" class="form-control form-control-sm" placeholder="Nowe hasło" required>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" name="must_change_password" id="must-change-<?= e($row['id']); ?>" value="1">
                                                            <label class="form-check-label" for="must-change-<?= e($row['id']); ?>">
                                                                Wymuś zmianę hasła
                                                            </label>
                                                        </div>
                                                        <button type="submit" class="btn btn-outline-secondary btn-sm">Zmień hasło</button>
                                                    </form>
                                                    <div class="d-flex flex-wrap gap-2">
                                                        <form method="POST" action="/users">
                                                            <input type="hidden" name="action" value="force_password_change">
                                                            <input type="hidden" name="user_id" value="<?= e($row['id']); ?>">
                                                            <button type="submit" class="btn btn-outline-warning btn-sm">
                                                                Wymuś zmianę hasła przy logowaniu
                                                            </button>
                                                        </form>
                                                        <form method="POST" action="/users" onsubmit="return confirm('Czy na pewno chcesz usunąć to konto?');">
                                                            <input type="hidden" name="action" value="delete_user">
                                                            <input type="hidden" name="user_id" value="<?= e($row['id']); ?>">
                                                            <button type="submit" class="btn btn-outline-danger btn-sm" <?= $isCurrentUser ? 'disabled' : ''; ?>>
                                                                Usuń konto
                                                            </button>
                                                        </form>
                                                    </div>
                                                    <?php if ($isCurrentUser) : ?>
                                                        <small class="text-muted d-block mt-2">Nie możesz usunąć aktualnie zalogowanego konta.</small>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm">
                    <div class="card-body">
                        <h4 class="mb-3">Dodaj nowego użytkownika</h4>
                        <form method="POST" action="/users">
                            <input type="hidden" name="action" value="create_user">
                            <div class="mb-3">
                                <label class="form-label">Nazwa</label>
                                <input type="text" name="name" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">E-mail</label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Hasło</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Rola</label>
                                <select name="role" class="form-select">
                                    <option value="newuser">Newuser</option>
                                    <option value="user">User</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </div>
                            <button type="submit" class="btn bg-red">
                                <i class="fas fa-user-plus"></i> Dodaj użytkownika
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
