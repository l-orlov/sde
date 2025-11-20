<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

ob_start();

include "functions.php";
require_once __DIR__ . '/FileManager.php';
require_once __DIR__ . '/storage/StorageFactory.php';

DBconnect();

$return = ['res' => '', 'ok' => 0, 'err' => '', 'files' => []];

if (!isset($_SESSION['uid'])) {
    if (ob_get_level()) ob_clean();
    $return['err'] = 'No autorizado. Por favor, inicie sesión.';
    header('Content-Type: application/json');
    echo json_encode($return);
    exit;
}

$userId = intval($_SESSION['uid']);

try {
    global $link;
    $fileManager = new FileManager();
    
    $query = "SELECT f.id, f.product_id, f.file_type, f.file_name, f.file_path, f.storage_type, p.is_main
              FROM files f
              LEFT JOIN products p ON f.product_id = p.id
              WHERE f.user_id = ? AND f.is_temporary = 0
              ORDER BY f.file_type, f.product_id, f.created_at";
    $stmt = $link->prepare($query);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $filesByType = [];
    
    while ($row = $result->fetch_assoc()) {
        $fileType = $row['file_type'];
        $fileId = $row['id'];
        $productId = $row['product_id'];
        $isMain = $row['is_main'] ?? false;
        
        $storage = StorageFactory::createByType($row['storage_type']);
        $url = $storage->getUrl($row['file_path']);
        
        $fileData = [
            'id' => $fileId,
            'url' => $url,
            'name' => $row['file_name']
        ];
        
        if ($productId !== null) {
            $fileData['product_id'] = $productId;
        }
        
        if ($fileType === 'product_photo') {
            if ($productId !== null && !$isMain) {
                if (!isset($filesByType['product_photo_sec'])) {
                    $filesByType['product_photo_sec'] = [];
                }
                if (!isset($filesByType['product_photo_sec'][$productId])) {
                    $filesByType['product_photo_sec'][$productId] = [];
                }
                $filesByType['product_photo_sec'][$productId][] = $fileData;
            } else {
                if (!isset($filesByType['product_photo'])) {
                    $filesByType['product_photo'] = [];
                }
                $filesByType['product_photo'][] = $fileData;
            }
        } else {
            if (!isset($filesByType[$fileType])) {
                $filesByType[$fileType] = [];
            }
            $filesByType[$fileType][] = $fileData;
        }
    }
    
    $stmt->close();
    
    $return['ok'] = 1;
    $return['files'] = $filesByType;
    $return['res'] = 'Archivos obtenidos correctamente';
    
} catch (Exception $e) {
    error_log("Error getting files: " . $e->getMessage());
    $return['err'] = 'Error al obtener archivos: ' . $e->getMessage();
}

if (ob_get_level()) {
    ob_clean();
}
header('Content-Type: application/json');
echo json_encode($return);
?>

