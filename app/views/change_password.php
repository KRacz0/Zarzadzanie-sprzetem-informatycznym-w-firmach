<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zmień hasło</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="/static/css/style.css">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <h2 class="mb-3 text-center">Zmień hasło</h2>

                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger">
                                <?= e($error); ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="/change-password">
                            <div class="mb-3">
                                <label class="form-label">Nowe hasło</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Potwierdź nowe hasło</label>
                                <input type="password" name="confirm_password" class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-dark w-100">Zapisz hasło</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
