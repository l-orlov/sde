<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

ob_start();

include "functions.php";
require_once __DIR__ . '/FileManager.php';

DBconnect();

$return = ['res' => '', 'ok' => 0, 'err' => '', 'url' => ''];

// Проверка авторизации
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
    
    // Ищем последний загруженный логотип пользователя
    $stmt = $link->prepare("
        SELECT id FROM files 
        WHERE user_id = ? AND file_type = 'logo' AND product_id IS NULL 
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $fileManager = new FileManager();
        $fileUrl = $fileManager->getUrl($row['id']);
        
        if ($fileUrl) {
            $return['ok'] = 1;
            $return['url'] = $fileUrl;
            $return['res'] = 'Logo encontrado';
        } else {
            $return['err'] = 'No se pudo obtener la URL del logo';
        }
    } else {
        // Нет логотипа - это нормально
        $return['ok'] = 1;
        $return['res'] = 'No hay logo guardado';
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    error_log("Error getting logo: " . $e->getMessage());
    $return['err'] = 'Error al obtener el logo: ' . $e->getMessage();
}

if (ob_get_level()) {
    ob_clean();
}
header('Content-Type: application/json');
echo json_encode($return);
?>

