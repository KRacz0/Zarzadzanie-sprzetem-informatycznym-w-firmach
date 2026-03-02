<?php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../helpers/Security.php';

class UserController {
    private $user;

    public function __construct() {
        $this->user = new User();
    }

    public function showUsers() {
        return $this->user->getAllUsers();
    }

    public function handlePost() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
            $action = $_POST['action'];

            if ($action === 'create_user' && isset($_POST['name'])) {
                $name = sanitize_input($_POST['name']);
                $email = sanitize_input($_POST['email'] ?? '');
                $password = $_POST['password'] ?? '';
                $role = $_POST['role'] ?? 'newuser';
                $allowedRoles = ['newuser', 'user', 'admin'];
                if (!in_array($role, $allowedRoles, true)) {
                    $role = 'newuser';
                }

                if ($name !== '' && $email !== '' && $password !== '') {
                    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $this->user->create($name, $email, $password, $role);
                    }
                }

                header("Location: /users");
                exit;
            }

            if ($action === 'update_role' && isset($_POST['user_id'])) {
                $userId = (int) $_POST['user_id'];
                $role = $_POST['role'] ?? 'newuser';
                $allowedRoles = ['newuser', 'user', 'admin'];
                if (!in_array($role, $allowedRoles, true)) {
                    $role = 'newuser';
                }
                $this->user->updateRole($userId, $role);
                header("Location: /users");
                exit;
            }

            if ($action === 'update_password' && isset($_POST['user_id'])) {
                $userId = (int) $_POST['user_id'];
                $password = $_POST['password'] ?? '';
                $mustChange = isset($_POST['must_change_password']) ? 1 : 0;
                if ($password !== '') {
                    $this->user->updatePassword($userId, $password, $mustChange);
                }
                header("Location: /users");
                exit;
            }

            if ($action === 'force_password_change' && isset($_POST['user_id'])) {
                $userId = (int) $_POST['user_id'];
                $this->user->setMustChangePassword($userId, 1);
                header("Location: /users");
                exit;
            }

            if ($action === 'delete_user' && isset($_POST['user_id'])) {
                $userId = (int) $_POST['user_id'];
                $currentUserId = (int) ($_SESSION['user_id'] ?? 0);
                if ($userId !== 0 && $userId !== $currentUserId) {
                    $this->user->deleteUser($userId);
                }
                header("Location: /users");
                exit;
            }
        }
    }
}

// Obsługa formularza dodawania użytkownika
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_SERVER['REQUEST_URI'] === '/users') {
    $controller = new UserController();
    $controller->handlePost();
}
?>
