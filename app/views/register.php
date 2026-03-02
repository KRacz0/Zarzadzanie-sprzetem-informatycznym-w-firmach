<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rejestracja</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="/static/css/style.css">
</head>
<body class="auth-page">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card shadow-sm auth-card">
                    <div class="bg-dark card-header text-center py-4">
                        <img src="/static/images/logo.png" alt="Logo" class="auth-logo img-fluid mb-2">
                        <h2 class="h4 mb-1">Załóż konto</h2>
                        <p class="auth-subtitle mb-0">Dołącz do panelu serwisowego</p>
                    </div>
                    <div class="card-body p-4">

                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger">
                                <?= e($error); ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="/register">
                            <div class="mb-3">
                                <label class="form-label">Imię i nazwisko</label>
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
                            <button type="submit" class="btn bg-red w-100">Utwórz konto</button>
                        </form>

                        <div class="mt-4 text-center auth-link">
                            <span>Masz już konto?</span>
                            <a href="/login">Zaloguj się</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
