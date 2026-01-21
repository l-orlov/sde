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
    
    // Загружаем все продукты и услуги с учетом type
    $query = "SELECT id, is_main, type, activity, name, description, annual_export, certifications
              FROM products
              WHERE user_id = ?
              ORDER BY id ASC";
    $stmt = $link->prepare($query);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $allProducts = [];
    $allServices = [];
    $hasProducts = false;
    $hasServices = false;
    
    while ($row = $result->fetch_assoc()) {
        $item = [
            'id' => intval($row['id']),
            'is_main' => (bool)$row['is_main'],
            'type' => $row['type'] ?? 'product',
            'activity' => $row['activity'] ?? null,
            'name' => $row['name'],
            'description' => $row['description'] ?? '',
            'annual_export' => $row['annual_export'] ?? '',
            'certifications' => $row['certifications'] ?? ''
        ];
        
        if ($row['type'] === 'service') {
            $allServices[] = $item;
            $hasServices = true;
        } else {
            $allProducts[] = $item;
            $hasProducts = true;
        }
    }
    
    $stmt->close();
    
    $return['ok'] = 1;
    // Разделяем на продукты и услуги
    $return['products'] = [
        'all' => $allProducts,
        'main' => count($allProducts) > 0 ? $allProducts[0] : null,
        'secondary' => count($allProducts) > 1 ? array_slice($allProducts, 1) : []
    ];
    $return['services'] = [
        'all' => $allServices,
        'main' => count($allServices) > 0 ? $allServices[0] : null,
        'secondary' => count($allServices) > 1 ? array_slice($allServices, 1) : []
    ];
    $return['has_products'] = $hasProducts;
    $return['has_services'] = $hasServices;
    $return['res'] = 'Productos y servicios obtenidos correctamente';
    
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

