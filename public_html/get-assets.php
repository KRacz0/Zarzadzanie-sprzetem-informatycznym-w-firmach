<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/html; charset=UTF-8');

require_once __DIR__ . '/../app/controllers/AssetController.php';

if (!isset($_GET['location_id']) || empty($_GET['location_id'])) {
    die("<div class='alert alert-danger'>Błąd: Brak `location_id`</div>");
}

$location_id = (int) $_GET['location_id'];
error_log("Pobieram zasoby dla location_id: " . $location_id);

$assetController = new AssetController();
$assets = $assetController->showAssetsByLocation($location_id);

if ($assets->num_rows === 0) {
    die("<div class='alert alert-warning'> Brak zasobów dla tej lokalizacji.</div>");
}

echo '<div class="accordion" id="assetsAccordion">';
while ($row = $assets->fetch_assoc()) {
    $assetId = $row['id'];
    echo '
        <div class="accordion-item">
            <h2 class="accordion-header" id="heading-'.$assetId.'">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-'.$assetId.'" aria-expanded="false" aria-controls="collapse-'.$assetId.'">
                    '.$row['name'].' ('.$row['type'].')
                </button>
            </h2>
            <div id="collapse-'.$assetId.'" class="accordion-collapse collapse" aria-labelledby="heading-'.$assetId.'" data-bs-parent="#assetsAccordion">
                <div class="accordion-body">
                    <p><strong>Typ:</strong> '.$row['type'].'</p>
                    <p><strong>Lokalizacja:</strong> '.$row['location_name'].'</p>
                    <p><strong>Kod QR:</strong> <br><img src="'.$row['qr_code'].'" alt="QR Code" width="100"></p>
                    <div id="history-'.$assetId.'" class="mt-3">
                        <button class="btn btn-secondary btn-sm" onclick="toggleHistory('.$assetId.')">Pokaż historię</button>
                        <div id="history-content-'.$assetId.'" class="mt-2" style="display: none;"></div>
                    </div>
                </div>
            </div>
        </div>
    ';
}
echo '</div>';
?>
