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

set_error_handler(function($errno, $errstr, $errfile, $errline) use (&$return) {
    error_log("PHP Error [$errno]: $errstr in $errfile on line $errline");
    if (ob_get_level()) ob_clean();
    $return['err'] = 'Error del servidor. Por favor, intente de nuevo.';
    $return['ok'] = 0;
    header('Content-Type: application/json');
    echo json_encode($return);
    exit;
}, E_ALL);

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
    global $link;
    $stmt = $link->prepare("
        SELECT id FROM files 
        WHERE user_id = ? AND file_type = 'logo' AND product_id IS NULL 
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    if (!$row) {
        $return['ok'] = 1;
        $return['res'] = 'No hay logo para eliminar';
        if (ob_get_level()) ob_clean();
        header('Content-Type: application/json');
        echo json_encode($return);
        exit;
    }

    $fileManager = new FileManager();
    $fileManager->delete($row['id'], $userId);

    $return['ok'] = 1;
    $return['res'] = 'Logotipo eliminado correctamente';
} catch (Exception $e) {
    error_log("Error deleting logo: " . $e->getMessage());
    $return['err'] = 'Error al eliminar el logotipo: ' . $e->getMessage();
}

if (ob_get_level()) ob_clean();
header('Content-Type: application/json');
echo json_encode($return);
