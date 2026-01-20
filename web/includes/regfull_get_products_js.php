<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

ob_start();

include "functions.php";

DBconnect();

$return = ['res' => '', 'ok' => 0, 'err' => '', 'products' => []];

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
    
    $query = "SELECT id, is_main, name, description, annual_export, certifications
              FROM products
              WHERE user_id = ? AND is_main = 1
              LIMIT 1";
    $stmt = $link->prepare($query);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $mainProduct = null;
    
    if ($row = $result->fetch_assoc()) {
        $mainProduct = [
            'id' => intval($row['id']),
            'is_main' => (bool)$row['is_main'],
            'name' => $row['name'],
            'description' => $row['description'] ?? '',
            'annual_export' => $row['annual_export'] ?? '',
            'certifications' => $row['certifications'] ?? ''
        ];
    }
    
    $stmt->close();
    
    $return['ok'] = 1;
    $return['products'] = [
        'main' => $mainProduct
    ];
    $return['res'] = 'Productos obtenidos correctamente';
    
} catch (Exception $e) {
    error_log("Error getting products: " . $e->getMessage());
    $return['err'] = 'Error al obtener productos: ' . $e->getMessage();
}

if (ob_get_level()) {
    ob_clean();
}
header('Content-Type: application/json');
echo json_encode($return);
?>

