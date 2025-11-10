<?php
session_start();
set_time_limit(0);
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

// Обработчик ошибок
set_error_handler(function($errno, $errstr, $errfile, $errline) use (&$return) {
    error_log("PHP Error [$errno]: $errstr in $errfile on line $errline");
    if (ob_get_level()) ob_clean();
    $return['err'] = 'Error del servidor. Por favor, intente de nuevo.';
    $return['ok'] = 0;
    header('Content-Type: application/json');
    echo json_encode($return);
    exit;
}, E_ALL);

// Обработчик исключений
set_exception_handler(function($exception) use (&$return) {
    error_log("Uncaught exception: " . $exception->getMessage());
    if (ob_get_level()) ob_clean();
    $return['err'] = 'Error del servidor: ' . $exception->getMessage();
    $return['ok'] = 0;
    header('Content-Type: application/json');
    echo json_encode($return);
    exit;
});

try {
    // Проверяем, что файл был загружен
    if (!isset($_FILES['logo']) || $_FILES['logo']['error'] !== UPLOAD_ERR_OK) {
        $return['err'] = 'No se recibió el archivo o hubo un error en la carga.';
        if (ob_get_level()) ob_clean();
        header('Content-Type: application/json');
        echo json_encode($return);
        exit;
    }
    
    $file = $_FILES['logo'];
    
    // Удаляем старый логотип пользователя (если есть)
    global $link;
    $fileManager = new FileManager();
    
    // Находим старый логотип
    $stmt = $link->prepare("SELECT id FROM files WHERE user_id = ? AND file_type = 'logo' AND product_id IS NULL ORDER BY created_at DESC LIMIT 1");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($oldFile = $result->fetch_assoc()) {
        try {
            $fileManager->delete($oldFile['id'], $userId);
            error_log("Old logo deleted: file_id = " . $oldFile['id']);
        } catch (Exception $e) {
            error_log("Error deleting old logo: " . $e->getMessage());
            // Продолжаем, даже если не удалось удалить старый
        }
    }
    $stmt->close();
    
    // Загружаем новый логотип
    $fileId = $fileManager->upload($file, null, $userId, 'logo');
    
    // Получаем URL загруженного файла
    $fileUrl = $fileManager->getUrl($fileId);
    
    $return['ok'] = 1;
    $return['res'] = 'Logo guardado correctamente';
    $return['url'] = $fileUrl;
    
} catch (Exception $e) {
    error_log("Error uploading logo: " . $e->getMessage());
    $return['err'] = 'Error al guardar el logo: ' . $e->getMessage();
}

if (ob_get_level()) {
    ob_clean();
}
header('Content-Type: application/json');
echo json_encode($return);
?>

