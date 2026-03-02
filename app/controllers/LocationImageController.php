<?php
require_once __DIR__ . '/../models/LocationImage.php';
require_once __DIR__ . '/../helpers/Security.php';

class LocationImageController {
    private $locationImage;

    public function __construct() {
        $this->locationImage = new LocationImage();
    }

    public function addImage($location_id, $user_id, $note) {
        if (empty($_FILES['image']['name'])) {
            header("Location: /location?id={$location_id}&message=Brak+pliku+do+za%C5%82%C4%85czenia");
            exit;
        }

        $note = sanitize_input($note);

        $upload_dir = __DIR__ . '/../../public_html/images/location_images/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $extension = strtolower($file_extension);
        if (!in_array($extension, $allowed_extensions, true)) {
            header("Location: /location?id={$location_id}&message=Nieobs%C5%82ugiwany+format+pliku");
            exit;
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($_FILES['image']['tmp_name']);
        $allowed_mime = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($mime, $allowed_mime, true)) {
            header("Location: /location?id={$location_id}&message=Nieprawid%C5%82owy+typ+pliku");
            exit;
        }

        $timestamp = date("Y-m-d_H-i-s");
        $random = bin2hex(random_bytes(4));
        $new_filename = "location_{$location_id}_{$timestamp}_{$random}.{$file_extension}";

        $image_path = "/images/location_images/" . $new_filename;
        $full_path = $upload_dir . $new_filename;

        if (!move_uploaded_file($_FILES['image']['tmp_name'], $full_path)) {
            header("Location: /location?id={$location_id}&message=B%C5%82%C4%85d+zapisu+pliku");
            exit;
        }

        $success = $this->locationImage->create($location_id, $user_id, $note, $image_path);

        $message = $success ? "Zdjęcie dodane pomyślnie" : "Nie udało się dodać zdjęcia";
        header("Location: /location?id={$location_id}&message=" . urlencode($message));
        exit;
    }

    public function deleteImage($image_id, $location_id) {
        $image = $this->locationImage->getById($image_id);
        if (!$image) {
            header("Location: /location?id={$location_id}&message=Nie+znaleziono+zdj%C4%99cia");
            exit;
        }

        if (!empty($image['image_path'])) {
            $full_path = __DIR__ . '/../../public_html' . $image['image_path'];
            if (file_exists($full_path)) {
                unlink($full_path);
            }
        }

        $this->locationImage->delete($image_id);
        header("Location: /location?id={$location_id}&message=Zdjęcie+usunięte");
        exit;
    }

    public function getImagesByLocation($location_id) {
        return $this->locationImage->getByLocation($location_id);
    }
}
?>
