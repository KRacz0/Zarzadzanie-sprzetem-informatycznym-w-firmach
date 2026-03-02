<?php
require_once __DIR__ . '/../models/Location.php';
require_once __DIR__ . '/../helpers/Security.php';

class LocationController {
    private $location;

    public function __construct() {
        $this->location = new Location();
    }

    public function createLocation() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['name']) && !empty($_POST['name'])) {
            $name = sanitize_input($_POST['name']);
            $this->location->create($name);
            header("Location: /locations?message=Lokalizacja dodana pomyślnie");
            exit;
        }
    }
    
    public function deleteLocation($id) {
        $this->location->delete((int) $id);
        header("Location: /locations?message=Lokalizacja usunięta pomyślnie");
        exit;
    }
    

    public function showLocations() {
        return $this->location->getAll();
    }

    public function getLocation($id) {
        return $this->location->getById($id);
    }

}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $controller = new LocationController();
    $controller->createLocation();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $controller = new LocationController();
    $controller->deleteLocation((int) $_POST['delete_id']);
}

?>
