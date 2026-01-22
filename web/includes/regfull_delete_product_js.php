<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

ob_start();

include "functions.php";
require_once __DIR__ . '/FileManager.php';

DBconnect();

$return = ['res' => '', 'ok' => 0, 'err' => ''];

if (!isset($_SESSION['uid'])) {
    if (ob_get_level()) ob_clean();
    $return['err'] = 'No autorizado. Por favor, inicie sesión.';
    header('Content-Type: application/json');
    echo json_encode($return);
    exit;
}

$userId = intval($_SESSION['uid']);

// Получаем данные из POST
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);

if (!isset($input['product_id']) || empty($input['product_id'])) {
    if (ob_get_level()) ob_clean();
    $return['err'] = 'ID de producto/servicio no proporcionado.';
    header('Content-Type: application/json');
    echo json_encode($return);
    exit;
}

$productId = intval($input['product_id']);

try {
    global $link;
    $fileManager = new FileManager();
    
    // Проверяем, что продукт/услуга принадлежит пользователю
    $query = "SELECT id, type, name FROM products WHERE id = ? AND user_id = ? LIMIT 1";
    $stmt = $link->prepare($query);
    if (!$stmt) {
        throw new Exception("Failed to prepare query: " . $link->error);
    }
    $stmt->bind_param("ii", $productId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();
    $stmt->close();
    
    if (!$product) {
        if (ob_get_level()) ob_clean();
        $return['err'] = 'Producto/servicio no encontrado o no tiene permisos para eliminarlo.';
        header('Content-Type: application/json');
        echo json_encode($return);
        exit;
    }
    
    // Удаляем все файлы, связанные с продуктом/услугой
    $deletedFilesCount = $fileManager->deleteProductFiles($productId, $userId);
    
    // Удаляем запись из таблицы products
    $deleteQuery = "DELETE FROM products WHERE id = ? AND user_id = ?";
    $deleteStmt = $link->prepare($deleteQuery);
    if (!$deleteStmt) {
        throw new Exception("Failed to prepare delete query: " . $link->error);
    }
    $deleteStmt->bind_param("ii", $productId, $userId);
    
    if (!$deleteStmt->execute()) {
        throw new Exception("Failed to delete product: " . $deleteStmt->error);
    }
    $deleteStmt->close();
    
    $itemType = ($product['type'] === 'service') ? 'servicio' : 'producto';
    $itemName = htmlspecialchars($product['name'] ?? '');
    
    $return['ok'] = 1;
    $return['res'] = "{$itemType} '{$itemName}' eliminado correctamente. Se eliminaron {$deletedFilesCount} archivo(s) asociado(s).";
    
} catch (Exception $e) {
    error_log("Error deleting product: " . $e->getMessage());
    $return['err'] = 'Error al eliminar producto/servicio: ' . $e->getMessage();
}

if (ob_get_level()) {
    ob_clean();
}
header('Content-Type: application/json');
echo json_encode($return);
?>
