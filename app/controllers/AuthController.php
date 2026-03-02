<?php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../helpers/Security.php';

class AuthController {
    private $user;

    public function __construct() {
        $this->user = new User();
    }

    public function register() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return [];
        }

        $name = sanitize_input($_POST['name'] ?? '');
        $email = sanitize_input($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($name === '' || $email === '' || $password === '') {
            return ['error' => 'Wszystkie pola są wymagane.'];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['error' => 'Podany adres e-mail jest nieprawidłowy.'];
        }

        if ($this->user->findByEmail($email)) {
            return ['error' => 'Podany adres e-mail już istnieje.'];
        }

        if (!$this->user->create($name, $email, $password, 'newuser')) {
            return ['error' => 'Nie udało się utworzyć konta. Spróbuj ponownie.'];
        }

        return ['redirect' => '/login?registered=1'];
    }

    public function login() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return [];
        }

        $email = sanitize_input($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($email === '' || $password === '') {
            return ['error' => 'Podaj e-mail oraz hasło.'];
        }

        $throttleStatus = $this->getLoginThrottleStatus($email);
        if ($throttleStatus['blocked']) {
            $waitSeconds = $throttleStatus['retry_after'];
            return ['error' => 'Zbyt wiele prób logowania. Spróbuj ponownie za ' . $waitSeconds . ' s.'];
        }

        $user = $this->user->verifyCredentials($email, $password);
        if (!$user) {
            $throttleStatus = $this->recordFailedLogin($email);
            if ($throttleStatus['blocked']) {
                $waitSeconds = $throttleStatus['retry_after'];
                return ['error' => 'Zbyt wiele prób logowania. Spróbuj ponownie za ' . $waitSeconds . ' s.'];
            }
            return ['error' => 'Niepoprawne dane logowania.'];
        }

        $this->clearLoginThrottle($email);

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['must_change_password'] = (int) ($user['must_change_password'] ?? 0);

        if (!empty($user['must_change_password'])) {
            return ['redirect' => '/change-password'];
        }

        if (in_array($user['role'], ['admin', 'user'], true)) {
            return ['redirect' => '/home'];
        }

        return ['redirect' => '/unauthorized'];
    }

    public function logout() {
        session_unset();
        session_destroy();
        return ['redirect' => '/login'];
    }

    public function changePassword() {
        if (empty($_SESSION['user_id'])) {
            return ['redirect' => '/login'];
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return [];
        }

        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if ($password === '' || $confirmPassword === '') {
            return ['error' => 'Wszystkie pola są wymagane.'];
        }

        if ($password !== $confirmPassword) {
            return ['error' => 'Hasła muszą być takie same.'];
        }

        if (!$this->user->updatePassword((int) $_SESSION['user_id'], $password, 0)) {
            return ['error' => 'Nie udało się zmienić hasła. Spróbuj ponownie.'];
        }

        $_SESSION['must_change_password'] = 0;

        if (in_array($_SESSION['role'] ?? '', ['admin', 'user'], true)) {
            return ['redirect' => '/home'];
        }

        return ['redirect' => '/unauthorized'];
    }

    private function getLoginThrottleStatus(string $email): array {
        $key = $this->getLoginThrottleKey($email);
        $record = $this->getRateLimitRecord($key);
        $limitConfig = $this->getLoginThrottleConfig();
        $now = time();

        if (!$record) {
            return ['blocked' => false];
        }

        if ($record['reset'] <= $now) {
            $this->clearRateLimitRecord($key);
            return ['blocked' => false];
        }

        if ($record['tokens'] >= $limitConfig['limit']) {
            return [
                'blocked' => true,
                'retry_after' => max(1, $record['reset'] - $now),
            ];
        }

        return ['blocked' => false];
    }

    private function recordFailedLogin(string $email): array {
        $key = $this->getLoginThrottleKey($email);
        $record = $this->getRateLimitRecord($key);
        $limitConfig = $this->getLoginThrottleConfig();
        $now = time();

        if (!$record || $record['reset'] <= $now) {
            $tokens = 1;
            $reset = $now + $limitConfig['interval'];
        } else {
            $tokens = $record['tokens'] + 1;
            $reset = $record['reset'];
        }

        $this->upsertRateLimitRecord($key, $tokens, $reset);

        if ($tokens >= $limitConfig['limit']) {
            return [
                'blocked' => true,
                'retry_after' => max(1, $reset - $now),
            ];
        }

        return ['blocked' => false];
    }

    private function clearLoginThrottle(string $email): void {
        $key = $this->getLoginThrottleKey($email);
        $this->clearRateLimitRecord($key);
    }

    private function getLoginThrottleKey(string $email): string {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $rawKey = sprintf('login_%s_%s', $ip, strtolower($email));
        return 'login_' . hash('sha256', $rawKey);
    }

    private function getLoginThrottleConfig(): array {
        return [
            'limit' => 10,
            'interval' => 600,
        ];
    }

    private function getRateLimitRecord(string $key): ?array {
        $db = new Database();
        $conn = $db->getConnection();
        $stmt = $conn->prepare('SELECT tokens, reset FROM rate_limits WHERE id = ? LIMIT 1');
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('s', $key);
        $stmt->execute();
        $result = $stmt->get_result();
        $record = $result ? $result->fetch_assoc() : null;
        $stmt->close();

        if (!$record) {
            return null;
        }

        return [
            'tokens' => (int) $record['tokens'],
            'reset' => (int) $record['reset'],
        ];
    }

    private function upsertRateLimitRecord(string $key, int $tokens, int $reset): void {
        $db = new Database();
        $conn = $db->getConnection();
        $stmt = $conn->prepare('INSERT INTO rate_limits (id, tokens, reset) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE tokens = VALUES(tokens), reset = VALUES(reset)');
        if (!$stmt) {
            return;
        }
        $stmt->bind_param('sii', $key, $tokens, $reset);
        $stmt->execute();
        $stmt->close();
    }

    private function clearRateLimitRecord(string $key): void {
        $db = new Database();
        $conn = $db->getConnection();
        $stmt = $conn->prepare('DELETE FROM rate_limits WHERE id = ?');
        if (!$stmt) {
            return;
        }
        $stmt->bind_param('s', $key);
        $stmt->execute();
        $stmt->close();
    }
}
?>
