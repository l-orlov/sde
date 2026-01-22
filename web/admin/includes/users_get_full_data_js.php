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
    $query = "SELECT id, name, tax_id, legal_name, start_date, website, organization_type, main_activity,
                     moderation_status, moderation_date, moderated_by
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
    
    // 5. Продукты и услуги (загружаем все, не только main)
    $products = ['all' => [], 'main' => null];
    $services = ['all' => [], 'main' => null];
    
    // Проверяем, есть ли поля current_markets и target_markets в таблице products
    $checkFieldsQuery = "SHOW COLUMNS FROM products LIKE 'current_markets'";
    $checkResult = mysqli_query($link, $checkFieldsQuery);
    $hasMarketsFields = ($checkResult && mysqli_num_rows($checkResult) > 0);
    
    if ($hasMarketsFields) {
        $query = "SELECT id, is_main, type, activity, name, description, annual_export, certifications, current_markets, target_markets
                  FROM products
                  WHERE user_id = ?
                  ORDER BY type, id ASC";
    } else {
        $query = "SELECT id, is_main, type, activity, name, description, annual_export, certifications
                  FROM products
                  WHERE user_id = ?
                  ORDER BY type, id ASC";
    }
    
    $stmt = mysqli_prepare($link, $query);
    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $allProducts = [];
    $allServices = [];
    while ($row = mysqli_fetch_assoc($result)) {
        // Определяем тип продукта/услуги
        $itemType = $row['type'] ?? null;
        // Если type пустой, NULL или не равен 'service', считаем продуктом
        if (empty($itemType) || $itemType === '' || $itemType !== 'service') {
            $itemType = 'product';
        }
        
        $itemData = [
            'id' => intval($row['id']),
            'is_main' => (bool)$row['is_main'],
            'type' => $itemType,
            'activity' => $row['activity'] ?? null,
            'name' => $row['name'] ?? '',
            'description' => $row['description'] ?? '',
            'annual_export' => $row['annual_export'] ?? '',
            'certifications' => $row['certifications'] ?? ''
        ];
        
        // Добавляем current_markets и target_markets если они есть
        if ($hasMarketsFields) {
            $itemData['current_markets'] = $row['current_markets'] ?? '';
            $targetMarkets = $row['target_markets'] ?? null;
            if ($targetMarkets) {
                $decoded = json_decode($targetMarkets, true);
                $itemData['target_markets'] = ($decoded !== null && is_array($decoded)) ? $decoded : [];
            } else {
                $itemData['target_markets'] = [];
            }
        } else {
            $itemData['current_markets'] = '';
            $itemData['target_markets'] = [];
        }
        
        if ($itemType === 'service') {
            $allServices[] = $itemData;
            // Для обратной совместимости сохраняем первую услугу как main
            if ($services['main'] === null) {
                $services['main'] = $itemData;
            }
        } else {
            $allProducts[] = $itemData;
            // Для обратной совместимости сохраняем первый продукт как main
            if ($products['main'] === null) {
                $products['main'] = $itemData;
            }
        }
    }
    mysqli_stmt_close($stmt);
    
    $products['all'] = $allProducts;
    $services['all'] = $allServices;
    
    // 6. Дополнительные данные (JSON)
    $companyDataJson = null;
    
    // Проверяем, существует ли таблица company_data и колонка current_markets
    $checkTableQuery = "SHOW TABLES LIKE 'company_data'";
    $checkTableResult = mysqli_query($link, $checkTableQuery);
    $tableExists = ($checkTableResult && mysqli_num_rows($checkTableResult) > 0);
    
    if ($tableExists) {
        $checkColumnQuery = "SHOW COLUMNS FROM company_data LIKE 'current_markets'";
        $checkColumnResult = mysqli_query($link, $checkColumnQuery);
        $columnExists = ($checkColumnResult && mysqli_num_rows($checkColumnResult) > 0);
        
        if ($columnExists) {
            $query = "SELECT current_markets, target_markets, differentiation_factors, needs, 
                         competitiveness, logistics, expectations, consents
                  FROM company_data WHERE company_id = ? LIMIT 1";
            $stmt = mysqli_prepare($link, $query);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, 'i', $companyId);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                $companyDataJson = mysqli_fetch_assoc($result);
                mysqli_stmt_close($stmt);
            }
        } else {
            // Если колонки нет, создаем пустую структуру
            $companyDataJson = null;
        }
    } else {
        // Если таблицы нет, создаем пустую структуру
        $companyDataJson = null;
    }
    
    // Парсинг JSON полей с правильной обработкой
    if ($companyDataJson) {
        // current_markets может быть строкой или JSON
        if (!empty($companyDataJson['current_markets'])) {
            $decoded = json_decode($companyDataJson['current_markets'], true);
            $companyDataJson['current_markets'] = ($decoded !== null) ? $decoded : $companyDataJson['current_markets'];
        } else {
            $companyDataJson['current_markets'] = '';
        }
        
        // target_markets - массив
        if (!empty($companyDataJson['target_markets'])) {
            $decoded = json_decode($companyDataJson['target_markets'], true);
            $companyDataJson['target_markets'] = ($decoded !== null && is_array($decoded)) ? $decoded : [];
        } else {
            $companyDataJson['target_markets'] = [];
        }
        
        // differentiation_factors - массив
        if (!empty($companyDataJson['differentiation_factors'])) {
            $decoded = json_decode($companyDataJson['differentiation_factors'], true);
            $companyDataJson['differentiation_factors'] = ($decoded !== null && is_array($decoded)) ? $decoded : [];
        } else {
            $companyDataJson['differentiation_factors'] = [];
        }
        
        // needs - массив
        if (!empty($companyDataJson['needs'])) {
            $decoded = json_decode($companyDataJson['needs'], true);
            $companyDataJson['needs'] = ($decoded !== null && is_array($decoded)) ? $decoded : [];
        } else {
            $companyDataJson['needs'] = [];
        }
        
        // competitiveness - объект
        if (!empty($companyDataJson['competitiveness'])) {
            $decoded = json_decode($companyDataJson['competitiveness'], true);
            $companyDataJson['competitiveness'] = ($decoded !== null && is_array($decoded)) ? $decoded : [];
        } else {
            $companyDataJson['competitiveness'] = [];
        }
        
        // logistics - объект
        if (!empty($companyDataJson['logistics'])) {
            $decoded = json_decode($companyDataJson['logistics'], true);
            $companyDataJson['logistics'] = ($decoded !== null && is_array($decoded)) ? $decoded : [];
        } else {
            $companyDataJson['logistics'] = [];
        }
        
        // expectations - объект
        if (!empty($companyDataJson['expectations'])) {
            $decoded = json_decode($companyDataJson['expectations'], true);
            $companyDataJson['expectations'] = ($decoded !== null && is_array($decoded)) ? $decoded : [];
        } else {
            $companyDataJson['expectations'] = [];
        }
        
        // consents - объект
        if (!empty($companyDataJson['consents'])) {
            $decoded = json_decode($companyDataJson['consents'], true);
            $companyDataJson['consents'] = ($decoded !== null && is_array($decoded)) ? $decoded : [];
        } else {
            $companyDataJson['consents'] = [];
        }
    } else {
        // Если нет данных, создаем пустую структуру
        $companyDataJson = [
            'current_markets' => '',
            'target_markets' => [],
            'differentiation_factors' => [],
            'needs' => [],
            'competitiveness' => [],
            'logistics' => [],
            'expectations' => [],
            'consents' => []
        ];
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
                     p.id as product_exists
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
            // Группируем фото продуктов по product_id (без учета is_main)
            if ($productId !== null && $productId > 0 && $productExists) {
                // Группируем по product_id в объект
                if (!isset($files['product_photo'])) {
                    $files['product_photo'] = [];
                }
                // Если это объект с ключами product_id
                if (!isset($files['product_photo'][$productId])) {
                    $files['product_photo'][$productId] = [];
                }
                $files['product_photo'][$productId][] = $fileData;
            } else {
                // Фото без product_id (старые данные или ошибка)
                if (!isset($files['product_photo'])) {
                    $files['product_photo'] = [];
                }
                // Если это массив, добавляем в него
                if (is_array($files['product_photo']) && !isset($files['product_photo'][0])) {
                    $files['product_photo'] = [$fileData];
                } else {
                    $files['product_photo'][] = $fileData;
                }
            }
        } else if ($fileType === 'service_photo') {
            // Группируем фото услуг по product_id (аналогично product_photo)
            if ($productId !== null && $productId > 0 && $productExists) {
                // Группируем по product_id в объект
                if (!isset($files['service_photo'])) {
                    $files['service_photo'] = [];
                }
                // Если это объект с ключами product_id
                if (!isset($files['service_photo'][$productId])) {
                    $files['service_photo'][$productId] = [];
                }
                $files['service_photo'][$productId][] = $fileData;
            } else {
                // Фото без product_id (старые данные или ошибка)
                if (!isset($files['service_photo'])) {
                    $files['service_photo'] = [];
                }
                // Если это массив, добавляем в него
                if (is_array($files['service_photo']) && !isset($files['service_photo'][0])) {
                    $files['service_photo'] = [$fileData];
                } else {
                    $files['service_photo'][] = $fileData;
                }
            }
        } else {
            // Остальные типы файлов
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
        'services' => $services,
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

