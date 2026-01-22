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
        
        // Ограничение до одного изображения на товар/услугу: удаляем все существующие файлы для этого product_id и типа
        if ($productId && ($fileType === 'product_photo' || $fileType === 'service_photo')) {
            global $link;
            $deleteQuery = "SELECT id FROM files WHERE product_id = ? AND user_id = ? AND file_type = ? AND is_temporary = 0";
            $deleteStmt = mysqli_prepare($link, $deleteQuery);
            if ($deleteStmt) {
                mysqli_stmt_bind_param($deleteStmt, 'iis', $productId, $userId, $dbFileType);
                mysqli_stmt_execute($deleteStmt);
                $deleteResult = mysqli_stmt_get_result($deleteStmt);
                while ($oldFile = mysqli_fetch_assoc($deleteResult)) {
                    try {
                        $fileManager->delete($oldFile['id'], $userId);
                    } catch (Exception $e) {
                        error_log("Error deleting old file {$oldFile['id']}: " . $e->getMessage());
                    }
                }
                mysqli_stmt_close($deleteStmt);
            }
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

