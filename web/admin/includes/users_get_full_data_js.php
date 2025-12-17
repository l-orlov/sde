<?
session_start();
date_default_timezone_set('America/Argentina/Buenos_Aires');
set_time_limit (0);
error_reporting(E_ALL);
ini_set('display_errors', 0); // Отключаем вывод ошибок в HTML
ob_start(); // Начинаем буферизацию вывода

include __DIR__ . '/path_helper.php';
include getIncludesFilePath('functions.php');

DBconnect();

$return = ['ok' => 0, 'err' => '', 'data' => null];

$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);

if (!$input || !isset($input['user_id'])) {
    if (ob_get_level() > 0) {
        ob_clean();
    }
    header('Content-Type: application/json');
    echo json_encode(['ok' => 0, 'err' => 'user_id requerido']);
    exit;
}

$userId = intval($input['user_id']);

try {
    // 0. Основные данные пользователя из таблицы users
    $query = "SELECT id, company_name, tax_id, email, phone, is_admin, created_at, updated_at 
              FROM users WHERE id = ? LIMIT 1";
    $stmt = mysqli_prepare($link, $query);
    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $userData = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    if (!$userData) {
        if (ob_get_level() > 0) {
            ob_clean();
        }
        header('Content-Type: application/json');
        echo json_encode(['ok' => 0, 'err' => 'Usuario no encontrado']);
        exit;
    }
    
    // Конвертация дат
    $userData['created_at'] = $userData['created_at'] ? date('Y-m-d H:i', $userData['created_at']) : '';
    $userData['updated_at'] = $userData['updated_at'] ? date('Y-m-d H:i', $userData['updated_at']) : '';
    
    // 1. Основные данные компании
    $query = "SELECT id, name, tax_id, legal_name, start_date, website, organization_type, main_activity 
              FROM companies WHERE user_id = ? LIMIT 1";
    $stmt = mysqli_prepare($link, $query);
    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $companyData = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    $hasCompanyData = !empty($companyData);
    $companyId = $hasCompanyData ? intval($companyData['id']) : null;
    
    // Если нет данных компании, возвращаем только данные пользователя
    if (!$hasCompanyData) {
        if (ob_get_level() > 0) {
            ob_clean();
        }
        $return['ok'] = 1;
        $return['data'] = [
            'user' => $userData,
            'company' => null,
            'has_company_data' => false
        ];
        header('Content-Type: application/json');
        echo json_encode($return);
        exit;
    }
    
    // Конвертация start_date (только если есть данные компании)
    if ($companyData['start_date']) {
        $timestamp = intval($companyData['start_date']);
        if ($timestamp > 0) {
            $dateObj = new DateTime();
            $dateObj->setTimestamp($timestamp);
            $companyData['start_date'] = $dateObj->format('d/m/Y');
        } else {
            $companyData['start_date'] = '';
        }
    }
    
    // 2. Адреса
    $addresses = ['legal' => null, 'admin' => null];
    $query = "SELECT type, street, street_number, postal_code, floor, apartment, locality, department 
              FROM company_addresses WHERE company_id = ?";
    $stmt = mysqli_prepare($link, $query);
    mysqli_stmt_bind_param($stmt, 'i', $companyId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $addresses[$row['type']] = $row;
    }
    mysqli_stmt_close($stmt);
    
    // 3. Контакты
    $contacts = null;
    $query = "SELECT contact_person, position, email, area_code, phone 
              FROM company_contacts WHERE company_id = ? LIMIT 1";
    $stmt = mysqli_prepare($link, $query);
    mysqli_stmt_bind_param($stmt, 'i', $companyId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $contacts = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    // 4. Социальные сети
    $socialNetworks = [];
    $query = "SELECT network_type, url 
              FROM company_social_networks WHERE company_id = ? 
              ORDER BY id ASC";
    $stmt = mysqli_prepare($link, $query);
    mysqli_stmt_bind_param($stmt, 'i', $companyId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $socialNetworks[] = $row;
    }
    mysqli_stmt_close($stmt);
    
    // 5. Продукты
    $products = ['main' => null, 'secondary' => []];
    $query = "SELECT id, is_main, name, tariff_code, description, volume_unit, volume_amount, annual_export, certifications
              FROM products
              WHERE user_id = ?
              ORDER BY is_main DESC, id ASC";
    $stmt = mysqli_prepare($link, $query);
    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $product = [
            'id' => intval($row['id']),
            'is_main' => (bool)$row['is_main'],
            'name' => $row['name'] ?? '',
            'tariff_code' => $row['tariff_code'] ?? '',
            'description' => $row['description'] ?? '',
            'volume_unit' => $row['volume_unit'] ?? '',
            'volume_amount' => $row['volume_amount'] ?? '',
            'annual_export' => $row['annual_export'] ?? '',
            'certifications' => $row['certifications'] ?? ''
        ];
        
        if ($row['is_main']) {
            $products['main'] = $product;
        } else {
            $products['secondary'][] = $product;
        }
    }
    mysqli_stmt_close($stmt);
    
    // 6. История экспорта
    $exportHistory = [];
    $query = "SELECT year, amount_usd 
              FROM company_export_history 
              WHERE company_id = ? 
              ORDER BY year ASC";
    $stmt = mysqli_prepare($link, $query);
    mysqli_stmt_bind_param($stmt, 'i', $companyId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $exportHistory[$row['year']] = $row['amount_usd'];
    }
    mysqli_stmt_close($stmt);
    
    // 7. Дополнительные данные (JSON)
    $companyDataJson = null;
    $query = "SELECT current_markets, target_markets, differentiation_factors, needs, 
                     competitiveness, logistics, expectations, consents
              FROM company_data WHERE company_id = ? LIMIT 1";
    $stmt = mysqli_prepare($link, $query);
    mysqli_stmt_bind_param($stmt, 'i', $companyId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $companyDataJson = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    // Парсинг JSON полей
    if ($companyDataJson) {
        foreach ($companyDataJson as $key => $value) {
            if ($value) {
                $decoded = json_decode($value, true);
                $companyDataJson[$key] = $decoded !== null ? $decoded : $value;
            } else {
                $companyDataJson[$key] = null;
            }
        }
    }
    
    // 8. Файлы (загружаем классы только здесь, когда они нужны)
    $files = [];
    try {
        $fileManagerPath = getIncludesFilePath('FileManager.php');
        $storageFactoryPath = getIncludesFilePath('storage/StorageFactory.php');
        
        if (!file_exists($fileManagerPath)) {
            throw new Exception("FileManager.php no encontrado en: " . $fileManagerPath);
        }
        if (!file_exists($storageFactoryPath)) {
            throw new Exception("StorageFactory.php no encontrado en: " . $storageFactoryPath);
        }
        
        require_once $fileManagerPath;
        require_once $storageFactoryPath;
    } catch (Exception $e) {
        // Если не можем загрузить классы для работы с файлами, просто пропускаем файлы
        error_log("Warning: No se pudieron cargar clases de archivos: " . $e->getMessage());
        $files = [];
    }
    
    $query = "SELECT f.id, f.product_id, f.file_type, f.file_name, f.file_path, f.storage_type, f.mime_type,
                     p.is_main, p.id as product_exists
              FROM files f
              LEFT JOIN products p ON f.product_id = p.id AND p.user_id = ?
              WHERE f.user_id = ? AND f.is_temporary = 0
              ORDER BY f.file_type, f.product_id, f.created_at";
    $stmt = mysqli_prepare($link, $query);
    mysqli_stmt_bind_param($stmt, 'ii', $userId, $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    while ($row = mysqli_fetch_assoc($result)) {
        $fileType = $row['file_type'];
        $fileId = $row['id'];
        $productId = $row['product_id'];
        $isMain = $row['is_main'] ?? false;
        $productExists = $row['product_exists'] !== null;
        
        try {
            if (class_exists('StorageFactory')) {
                $storage = StorageFactory::createByType($row['storage_type']);
                $url = $storage->getUrl($row['file_path']);
            } else {
                // Если StorageFactory не загружен, используем базовый путь
                $url = $row['file_path'];
            }
        } catch (Exception $e) {
            error_log("Warning: Error al obtener URL del archivo: " . $e->getMessage());
            $url = $row['file_path']; // Fallback на базовый путь
        }
        
        $fileData = [
            'id' => $fileId,
            'url' => $url,
            'name' => $row['file_name'],
            'mime_type' => $row['mime_type']
        ];
        
        if ($productId !== null && $productId > 0) {
            $fileData['product_id'] = $productId;
        }
        
        if ($fileType === 'product_photo') {
            if ($productId !== null && $productId > 0 && $productExists && !$isMain) {
                if (!isset($files['product_photo_sec'])) {
                    $files['product_photo_sec'] = [];
                }
                if (!isset($files['product_photo_sec'][$productId])) {
                    $files['product_photo_sec'][$productId] = [];
                }
                $files['product_photo_sec'][$productId][] = $fileData;
            } else {
                if (!isset($files['product_photo'])) {
                    $files['product_photo'] = [];
                }
                $files['product_photo'][] = $fileData;
            }
        } else {
            if (!isset($files[$fileType])) {
                $files[$fileType] = [];
            }
            $files[$fileType][] = $fileData;
        }
    }
    mysqli_stmt_close($stmt);
    
    $return['ok'] = 1;
    $return['data'] = [
        'user' => $userData,
        'company' => $companyData,
        'addresses' => $addresses,
        'contacts' => $contacts,
        'social_networks' => $socialNetworks,
        'products' => $products,
        'export_history' => $exportHistory,
        'company_data' => $companyDataJson,
        'files' => $files,
        'has_company_data' => true
    ];
    
} catch (Exception $e) {
    $return['err'] = 'Error: ' . $e->getMessage();
    error_log("Error en users_get_full_data_js.php: " . $e->getMessage() . " en línea " . $e->getLine());
} catch (Error $e) {
    $return['err'] = 'Error fatal: ' . $e->getMessage();
    error_log("Error fatal en users_get_full_data_js.php: " . $e->getMessage() . " en línea " . $e->getLine());
}

// Очищаем буфер вывода перед отправкой JSON
if (ob_get_level() > 0) {
    ob_clean();
}

header('Content-Type: application/json');
echo json_encode($return);
exit;
?>

