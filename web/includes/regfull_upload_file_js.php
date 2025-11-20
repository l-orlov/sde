<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

ob_start();

include "functions.php";
require_once __DIR__ . '/FileManager.php';

DBconnect();

$return = ['res' => '', 'ok' => 0, 'err' => '', 'file_id' => null, 'url' => '', 'name' => ''];

if (!isset($_SESSION['uid'])) {
    if (ob_get_level()) ob_clean();
    $return['err'] = 'No autorizado. Por favor, inicie sesión.';
    header('Content-Type: application/json');
    echo json_encode($return);
    exit;
}

$userId = intval($_SESSION['uid']);
$fileManager = new FileManager();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
    try {
        $fileType = isset($_POST['file_type']) ? htmlspecialchars(trim($_POST['file_type'])) : 'product_photo';
        $productId = isset($_POST['product_id']) && !empty($_POST['product_id']) ? intval($_POST['product_id']) : null;
        
        $dbFileType = $fileType;
        if ($fileType === 'product_photo_sec') {
            $dbFileType = 'product_photo';
        }
        
        $fileId = $fileManager->upload($_FILES['file'], $productId, $userId, $dbFileType, true);
        
        $fileInfo = $fileManager->getFileInfo($fileId);
        
        if ($fileInfo) {
            $return['ok'] = 1;
            $return['file_id'] = $fileInfo['id'];
            $return['url'] = $fileInfo['url'];
            $return['name'] = $fileInfo['file_name'];
            $return['res'] = 'Archivo cargado correctamente';
        } else {
            $return['err'] = 'No se pudo obtener información del archivo';
        }
    } catch (Exception $e) {
        error_log("Error uploading file: " . $e->getMessage());
        $return['err'] = 'Error al cargar el archivo: ' . $e->getMessage();
    }
} else {
    $return['err'] = 'No se recibió el archivo';
}

if (ob_get_level()) {
    ob_clean();
}
header('Content-Type: application/json');
echo json_encode($return);
?>

