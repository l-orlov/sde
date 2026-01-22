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
    // Проверяем, есть ли поля current_markets и target_markets в таблице products
    $checkFieldsQuery = "SHOW COLUMNS FROM products LIKE 'current_markets'";
    $checkResult = $link->query($checkFieldsQuery);
    $hasCurrentMarketsField = ($checkResult && $checkResult->num_rows > 0);
    
    if ($hasCurrentMarketsField) {
        $query = "SELECT id, is_main, type, activity, name, description, annual_export, certifications, current_markets, target_markets
                  FROM products
                  WHERE user_id = ?
                  ORDER BY type ASC, id ASC";
    } else {
        $query = "SELECT id, is_main, type, activity, name, description, annual_export, certifications
                  FROM products
                  WHERE user_id = ?
                  ORDER BY type ASC, id ASC";
    }
    
    $stmt = $link->prepare($query);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $allProducts = [];
    $allServices = [];
    $hasProducts = false;
    $hasServices = false;
    
    while ($row = $result->fetch_assoc()) {
        // Определяем тип продукта/услуги
        $itemType = $row['type'] ?? null;
        // Если type пустой, NULL или не равен 'service', считаем продуктом
        if (empty($itemType) || $itemType === '' || $itemType !== 'service') {
            $itemType = 'product';
        }
        
        $item = [
            'id' => intval($row['id']),
            'is_main' => (bool)$row['is_main'],
            'type' => $itemType,
            'activity' => $row['activity'] ?? null,
            'name' => $row['name'],
            'description' => $row['description'] ?? '',
            'annual_export' => $row['annual_export'] ?? '',
            'certifications' => $row['certifications'] ?? ''
        ];
        
        // Добавляем current_markets и target_markets если они есть
        if ($hasCurrentMarketsField) {
            $item['current_markets'] = $row['current_markets'] ?? '';
            $targetMarkets = $row['target_markets'] ?? null;
            if ($targetMarkets) {
                $decoded = json_decode($targetMarkets, true);
                $item['target_markets'] = ($decoded !== null && is_array($decoded)) ? $decoded : [];
            } else {
                $item['target_markets'] = [];
            }
        } else {
            $item['current_markets'] = '';
            $item['target_markets'] = [];
        }
        
        if ($itemType === 'service') {
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

