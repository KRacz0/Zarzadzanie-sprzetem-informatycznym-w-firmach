<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../vendor/autoload.php';
require_once '../app/helpers/Security.php';
require_once '../app/controllers/AuthController.php';

session_start();

function redirectTo($path) {
    header("Location: {$path}");
    exit;
}

function requireRoles(array $roles) {
    if (empty($_SESSION['user_id'])) {
        redirectTo('/login');
    }

    if (!empty($_SESSION['must_change_password'])) {
        redirectTo('/change-password');
    }

    if (!in_array($_SESSION['role'] ?? '', $roles, true)) {
        redirectTo('/unauthorized');
    }
}

$request = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

switch ($request) {
    case '/':
    case '/home':
        requireRoles(['admin', 'user']);
        require '../app/views/home.php';
        break;
    
    case '/locations':
        requireRoles(['admin', 'user']);
        require '../app/views/locations.php';
        break;

    case '/location':
        requireRoles(['admin', 'user']);
        require '../app/views/location_detail.php';
        break;
    
    case '/assets':
        requireRoles(['admin', 'user']);
        require '../app/views/assets.php'; 
        break;

    case '/add-asset':
        requireRoles(['admin', 'user']);
        require '../app/views/add_asset.php';
        break;

    case '/maintenance':
        requireRoles(['admin', 'user']);
        require '../app/views/maintenance.php';
        break;

    case '/add-maintenance':
        requireRoles(['admin', 'user']);
        require './add-maintenance.php';
        break;    

    case '/users':
        requireRoles(['admin']);
        require '../app/views/users.php';
        
        break;
    
    case '/login':
        $controller = new AuthController();
        $result = $controller->login();
        if (isset($result['redirect'])) {
            redirectTo($result['redirect']);
        }
        $error = $result['error'] ?? null;
        require '../app/views/login.php';
        break;

    case '/register':
        $controller = new AuthController();
        $result = $controller->register();
        if (isset($result['redirect'])) {
            redirectTo($result['redirect']);
        }
        $error = $result['error'] ?? null;
        require '../app/views/register.php';
        break;

    case '/logout':
        $controller = new AuthController();
        $result = $controller->logout();
        redirectTo($result['redirect'] ?? '/login');
        break;

    case '/change-password':
        if (empty($_SESSION['user_id'])) {
            redirectTo('/login');
        }
        $controller = new AuthController();
        $result = $controller->changePassword();
        if (isset($result['redirect'])) {
            redirectTo($result['redirect']);
        }
        $error = $result['error'] ?? null;
        require '../app/views/change_password.php';
        break;

    case '/unauthorized':
        if (empty($_SESSION['user_id'])) {
            redirectTo('/login');
        }
        require '../app/views/unauthorized.php';
        break;

    default:
        http_response_code(404);
        require '../app/views/404.php';
        break;
}

?>
