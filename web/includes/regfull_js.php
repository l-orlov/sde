<?php
session_start();
set_time_limit(0);
error_reporting(E_ALL);
ob_implicit_flush();

include "functions.php";
require_once __DIR__ . '/FileManager.php';

DBconnect();

$return = ['res' => '', 'ok' => 0, 'err' => ''];

// Проверка авторизации
if (!isset($_SESSION['uid'])) {
    $return['err'] = 'No autorizado. Por favor, inicie sesión.';
    echo json_encode($return);
    exit;
}

$userId = intval($_SESSION['uid']);
$fileManager = new FileManager();

// Получаем данные из POST (FormData)
// FormData автоматически парсится PHP в $_POST и $_FILES
$input = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // PHP автоматически обрабатывает FormData:
    // - Обычные поля → $_POST['key'] = 'value'
    // - Массивы (name="key[]") → $_POST['key'] = ['value1', 'value2']
    // - Файлы → $_FILES['key']
    
    foreach ($_POST as $key => $value) {
        // Убираем [] из ключей для удобства работы
        $cleanKey = str_replace('[]', '', $key);
        
        if (is_array($value)) {
            // Если это массив, сохраняем как массив
            $input[$cleanKey] = $value;
        } else {
            // Если это не массив, но ключ был с [], создаем массив
            if (strpos($key, '[]') !== false) {
                if (!isset($input[$cleanKey])) {
                    $input[$cleanKey] = [];
                }
                $input[$cleanKey][] = $value;
            } else {
                $input[$cleanKey] = $value;
            }
        }
    }
}

if (empty($input) && empty($_FILES)) {
    $return['err'] = 'No se recibieron datos';
    echo json_encode($return);
    exit;
}

// Начинаем транзакцию
mysqli_begin_transaction($link);

try {
    // Проверяем, есть ли уже компания у пользователя
    $query = "SELECT id FROM companies WHERE user_id = ?";
    $stmt = mysqli_prepare($link, $query);
    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $company = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    $companyId = $company ? $company['id'] : null;
    
    // ========== 1. СОХРАНЕНИЕ ДАННЫХ КОМПАНИИ ==========
    
    // Подготовка данных компании
    $name = isset($input['name']) ? htmlspecialchars(trim($input['name'])) : '';
    $taxId = isset($input['tax_id']) ? htmlspecialchars(trim($input['tax_id'])) : '';
    $legalName = isset($input['legal_name']) ? htmlspecialchars(trim($input['legal_name'])) : '';
    $startDate = isset($input['start_date']) ? htmlspecialchars(trim($input['start_date'])) : null;
    $website = isset($input['website']) ? htmlspecialchars(trim($input['website'])) : '';
    $organizationType = isset($input['organization_type']) ? htmlspecialchars(trim($input['organization_type'])) : '';
    $mainActivity = isset($input['main_activity']) ? htmlspecialchars(trim($input['main_activity'])) : '';
    
    // Преобразуем дату из формата dd/mm/yyyy в UNIX timestamp
    $startDateTimestamp = null;
    if ($startDate) {
        $parts = explode('/', $startDate);
        if (count($parts) === 3) {
            // Создаем объект DateTime из dd/mm/yyyy
            $dateObj = DateTime::createFromFormat('d/m/Y', $startDate);
            if ($dateObj) {
                // Устанавливаем время на начало дня (00:00:00)
                $dateObj->setTime(0, 0, 0);
                $startDateTimestamp = $dateObj->getTimestamp();
            }
        }
    }
    
    if ($companyId) {
        // Обновляем существующую компанию
        $query = "UPDATE companies SET name = ?, tax_id = ?, legal_name = ?, start_date = ?, 
                  website = ?, organization_type = ?, main_activity = ?, updated_at = UNIX_TIMESTAMP() 
                  WHERE id = ?";
        $stmt = mysqli_prepare($link, $query);
        mysqli_stmt_bind_param($stmt, 'sssissi', $name, $taxId, $legalName, $startDateTimestamp, $website, $organizationType, $mainActivity, $companyId);
    } else {
        // Создаем новую компанию
        $query = "INSERT INTO companies (user_id, name, tax_id, legal_name, start_date, website, 
                  organization_type, main_activity) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($link, $query);
        mysqli_stmt_bind_param($stmt, 'isssisss', $userId, $name, $taxId, $legalName, $startDateTimestamp, $website, $organizationType, $mainActivity);
    }
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Error al guardar datos de la empresa: " . mysqli_error($link));
    }
    
    if (!$companyId) {
        $companyId = mysqli_insert_id($link);
    }
    mysqli_stmt_close($stmt);
    
    // ========== 2. АДРЕСА ==========
    
    // Удаляем старые адреса
    $query = "DELETE FROM company_addresses WHERE company_id = ?";
    $stmt = mysqli_prepare($link, $query);
    mysqli_stmt_bind_param($stmt, 'i', $companyId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    // Сохраняем юридический адрес
    if (isset($input['street_legal']) && !empty($input['street_legal'])) {
        $query = "INSERT INTO company_addresses (company_id, type, street, street_number, postal_code, floor, 
                  apartment, locality, department) VALUES (?, 'legal', ?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($link, $query);
        $street = htmlspecialchars(trim($input['street_legal'] ?? ''));
        $streetNumber = htmlspecialchars(trim($input['street_number_legal'] ?? ''));
        $postalCode = htmlspecialchars(trim($input['postal_code_legal'] ?? ''));
        $floor = htmlspecialchars(trim($input['floor_legal'] ?? ''));
        $apartment = htmlspecialchars(trim($input['apartment_legal'] ?? ''));
        $locality = htmlspecialchars(trim($input['locality_legal'] ?? ''));
        $department = htmlspecialchars(trim($input['department_legal'] ?? ''));
        mysqli_stmt_bind_param($stmt, 'isssssss', $companyId, $street, $streetNumber, $postalCode, $floor, $apartment, $locality, $department);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
    
    // Сохраняем административный адрес
    if (isset($input['street_admin']) && !empty($input['street_admin'])) {
        $query = "INSERT INTO company_addresses (company_id, type, street, street_number, postal_code, floor, 
                  apartment, locality, department) VALUES (?, 'admin', ?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($link, $query);
        $street = htmlspecialchars(trim($input['street_admin'] ?? ''));
        $streetNumber = htmlspecialchars(trim($input['street_number_admin'] ?? ''));
        $postalCode = htmlspecialchars(trim($input['postal_code_admin'] ?? ''));
        $floor = htmlspecialchars(trim($input['floor_admin'] ?? ''));
        $apartment = htmlspecialchars(trim($input['apartment_admin'] ?? ''));
        $locality = htmlspecialchars(trim($input['locality_admin'] ?? ''));
        $department = htmlspecialchars(trim($input['department_admin'] ?? ''));
        mysqli_stmt_bind_param($stmt, 'isssssss', $companyId, $street, $streetNumber, $postalCode, $floor, $apartment, $locality, $department);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
    
    // ========== 3. КОНТАКТНОЕ ЛИЦО ==========
    
    // Удаляем старые контакты
    $query = "DELETE FROM company_contacts WHERE company_id = ?";
    $stmt = mysqli_prepare($link, $query);
    mysqli_stmt_bind_param($stmt, 'i', $companyId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    if (isset($input['contact_person']) && !empty($input['contact_person'])) {
        $query = "INSERT INTO company_contacts (company_id, contact_person, position, email, area_code, phone) 
                  VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($link, $query);
        $contactPerson = htmlspecialchars(trim($input['contact_person'] ?? ''));
        $position = htmlspecialchars(trim($input['contact_position'] ?? ''));
        $email = htmlspecialchars(trim($input['contact_email'] ?? ''));
        $areaCode = htmlspecialchars(trim($input['contact_area_code'] ?? ''));
        $phone = htmlspecialchars(trim($input['contact_phone'] ?? ''));
        mysqli_stmt_bind_param($stmt, 'isssss', $companyId, $contactPerson, $position, $email, $areaCode, $phone);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
    
    // ========== 4. СОЦИАЛЬНЫЕ СЕТИ ==========
    
    // Удаляем старые соцсети
    $query = "DELETE FROM company_social_networks WHERE company_id = ?";
    $stmt = mysqli_prepare($link, $query);
    mysqli_stmt_bind_param($stmt, 'i', $companyId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    if (isset($input['social_network_type']) && is_array($input['social_network_type'])) {
        $query = "INSERT INTO company_social_networks (company_id, network_type, url) VALUES (?, ?, ?)";
        $stmt = mysqli_prepare($link, $query);
        
        $networkTypes = $input['social_network_type'];
        $urls = isset($input['social_url']) && is_array($input['social_url']) ? $input['social_url'] : [];
        
        for ($i = 0; $i < count($networkTypes); $i++) {
            $networkType = htmlspecialchars(trim($networkTypes[$i] ?? ''));
            $url = isset($urls[$i]) ? htmlspecialchars(trim($urls[$i])) : '';
            
            if (!empty($networkType) && !empty($url)) {
                mysqli_stmt_bind_param($stmt, 'iss', $companyId, $networkType, $url);
                mysqli_stmt_execute($stmt);
            }
        }
        mysqli_stmt_close($stmt);
    }
    
    // ========== 5. ПРОДУКТЫ ==========
    
    // Удаляем старые продукты
    $query = "DELETE FROM products WHERE company_id = ?";
    $stmt = mysqli_prepare($link, $query);
    mysqli_stmt_bind_param($stmt, 'i', $companyId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    // Сохраняем основной продукт
    if (isset($input['main_product']) && !empty($input['main_product'])) {
        $query = "INSERT INTO products (company_id, user_id, is_main, name, tariff_code, description, 
                  volume_unit, volume_amount, annual_export, certifications) 
                  VALUES (?, ?, TRUE, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($link, $query);
        $productName = htmlspecialchars(trim($input['main_product'] ?? ''));
        $tariffCode = htmlspecialchars(trim($input['tariff_code'] ?? ''));
        $description = htmlspecialchars(trim($input['product_description'] ?? ''));
        $volumeUnit = htmlspecialchars(trim($input['volume_unit'] ?? ''));
        $volumeAmount = htmlspecialchars(trim($input['volume_amount'] ?? ''));
        $annualExport = htmlspecialchars(trim($input['annual_export'] ?? ''));
        $certifications = htmlspecialchars(trim($input['certifications'] ?? ''));
        mysqli_stmt_bind_param($stmt, 'iisssssss', $companyId, $userId, $productName, $tariffCode, $description, $volumeUnit, $volumeAmount, $annualExport, $certifications);
        mysqli_stmt_execute($stmt);
        $mainProductId = mysqli_insert_id($link);
        mysqli_stmt_close($stmt);
    }
    
    // Сохраняем вторичные продукты
    if (isset($input['secondary_products']) && is_array($input['secondary_products'])) {
        $query = "INSERT INTO products (company_id, user_id, is_main, name, tariff_code, description, 
                  volume_unit, volume_amount, annual_export) 
                  VALUES (?, ?, FALSE, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($link, $query);
        
        $names = $input['secondary_products'];
        $tariffCodes = isset($input['tariff_code_sec']) && is_array($input['tariff_code_sec']) ? $input['tariff_code_sec'] : [];
        $descriptions = isset($input['product_description_sec']) && is_array($input['product_description_sec']) ? $input['product_description_sec'] : [];
        $volumeUnits = isset($input['volume_unit_sec']) && is_array($input['volume_unit_sec']) ? $input['volume_unit_sec'] : [];
        $volumeAmounts = isset($input['volume_amount_sec']) && is_array($input['volume_amount_sec']) ? $input['volume_amount_sec'] : [];
        $annualExports = isset($input['annual_export_sec']) && is_array($input['annual_export_sec']) ? $input['annual_export_sec'] : [];
        
        for ($i = 0; $i < count($names); $i++) {
            $productName = htmlspecialchars(trim($names[$i] ?? ''));
            if (empty($productName)) continue;
            
            $tariffCode = isset($tariffCodes[$i]) ? htmlspecialchars(trim($tariffCodes[$i])) : '';
            $description = isset($descriptions[$i]) ? htmlspecialchars(trim($descriptions[$i])) : '';
            $volumeUnit = isset($volumeUnits[$i]) ? htmlspecialchars(trim($volumeUnits[$i])) : '';
            $volumeAmount = isset($volumeAmounts[$i]) ? htmlspecialchars(trim($volumeAmounts[$i])) : '';
            $annualExport = isset($annualExports[$i]) ? htmlspecialchars(trim($annualExports[$i])) : '';
            
            mysqli_stmt_bind_param($stmt, 'iissssss', $companyId, $userId, $productName, $tariffCode, $description, $volumeUnit, $volumeAmount, $annualExport);
            mysqli_stmt_execute($stmt);
        }
        mysqli_stmt_close($stmt);
    }
    
    // ========== 6. ИСТОРИЯ ЭКСПОРТА ==========
    
    // Удаляем старую историю
    $query = "DELETE FROM company_export_history WHERE company_id = ?";
    $stmt = mysqli_prepare($link, $query);
    mysqli_stmt_bind_param($stmt, 'i', $companyId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    $years = [2022, 2023, 2024];
    foreach ($years as $year) {
        $key = 'export_' . $year;
        if (isset($input[$key]) && !empty($input[$key])) {
            $amount = floatval($input[$key]);
            if ($amount > 0) {
                $query = "INSERT INTO company_export_history (company_id, year, amount_usd) VALUES (?, ?, ?)";
                $stmt = mysqli_prepare($link, $query);
                mysqli_stmt_bind_param($stmt, 'iid', $companyId, $year, $amount);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }
        }
    }
    
    // ========== 7. ДОПОЛНИТЕЛЬНЫЕ ДАННЫЕ (JSON) ==========
    
    $jsonData = [
        'current_markets' => isset($input['current_markets']) && is_array($input['current_markets']) ? $input['current_markets'] : [],
        'target_markets' => isset($input['target_markets']) ? htmlspecialchars(trim($input['target_markets'])) : '',
        'differentiation_factors' => [],
        'competitiveness' => [
            'company_history' => isset($input['company_history']) ? htmlspecialchars(trim($input['company_history'])) : '',
            'awards' => isset($input['awards']) ? htmlspecialchars(trim($input['awards'])) : '',
            'awards_detail' => isset($input['awards_detail']) ? htmlspecialchars(trim($input['awards_detail'])) : '',
            'fairs' => isset($input['fairs']) ? htmlspecialchars(trim($input['fairs'])) : '',
            'rounds' => isset($input['rounds']) ? htmlspecialchars(trim($input['rounds'])) : '',
            'export_experience' => isset($input['export_experience']) ? htmlspecialchars(trim($input['export_experience'])) : '',
            'commercial_references' => isset($input['commercial_references']) ? htmlspecialchars(trim($input['commercial_references'])) : '',
        ],
        'logistics' => [
            'export_capacity' => isset($input['export_capacity']) ? htmlspecialchars(trim($input['export_capacity'])) : '',
            'estimated_term' => isset($input['estimated_term']) ? htmlspecialchars(trim($input['estimated_term'])) : '',
            'logistics_infrastructure' => isset($input['logistics_infrastructure']) ? htmlspecialchars(trim($input['logistics_infrastructure'])) : '',
            'ports_airports' => isset($input['ports_airports']) ? htmlspecialchars(trim($input['ports_airports'])) : '',
        ],
        'expectations' => [
            'interest_participate' => isset($input['interest_participate']) ? htmlspecialchars(trim($input['interest_participate'])) : '',
            'training_availability' => isset($input['training_availability']) ? htmlspecialchars(trim($input['training_availability'])) : '',
        ],
        'consents' => [
            'authorization_publish' => isset($input['authorization_publish']) ? htmlspecialchars(trim($input['authorization_publish'])) : '',
            'authorization_publication' => isset($input['authorization_publication']) ? htmlspecialchars(trim($input['authorization_publication'])) : '',
            'accept_contact' => isset($input['accept_contact']) ? htmlspecialchars(trim($input['accept_contact'])) : '',
        ],
    ];
    
    // Собираем факторы дифференциации из чекбоксов
    // Ищем все чекбоксы с факторами (они могут быть без name, нужно искать по структуре)
    $diffFactors = [];
    // Проверяем, есть ли в POST данные о факторах
    if (isset($input['differentiation_factors']) && is_array($input['differentiation_factors'])) {
        $diffFactors = $input['differentiation_factors'];
    }
    $jsonData['differentiation_factors'] = $diffFactors;
    
    // Собираем потребности из чекбоксов
    $needs = [];
    if (isset($input['needs']) && is_array($input['needs'])) {
        $needs = $input['needs'];
    }
    $jsonData['needs'] = $needs;
    
    // Кодируем каждое поле отдельно
    $currentMarketsJson = json_encode($jsonData['current_markets'], JSON_UNESCAPED_UNICODE);
    $targetMarketsJson = json_encode($jsonData['target_markets'], JSON_UNESCAPED_UNICODE);
    $diffFactorsJson = json_encode($jsonData['differentiation_factors'], JSON_UNESCAPED_UNICODE);
    $needsJson = json_encode($jsonData['needs'], JSON_UNESCAPED_UNICODE);
    $competitivenessJson = json_encode($jsonData['competitiveness'], JSON_UNESCAPED_UNICODE);
    $logisticsJson = json_encode($jsonData['logistics'], JSON_UNESCAPED_UNICODE);
    $expectationsJson = json_encode($jsonData['expectations'], JSON_UNESCAPED_UNICODE);
    $consentsJson = json_encode($jsonData['consents'], JSON_UNESCAPED_UNICODE);
    
    // Проверяем, есть ли уже запись
    $query = "SELECT id FROM company_data WHERE company_id = ?";
    $stmt = mysqli_prepare($link, $query);
    mysqli_stmt_bind_param($stmt, 'i', $companyId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $exists = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    if ($exists) {
        $query = "UPDATE company_data SET current_markets = ?, target_markets = ?, differentiation_factors = ?, 
                  needs = ?, competitiveness = ?, logistics = ?, expectations = ?, consents = ?, updated_at = UNIX_TIMESTAMP() 
                  WHERE company_id = ?";
        $stmt = mysqli_prepare($link, $query);
        mysqli_stmt_bind_param($stmt, 'ssssssssi', $currentMarketsJson, $targetMarketsJson, $diffFactorsJson, $needsJson,
                               $competitivenessJson, $logisticsJson, $expectationsJson, $consentsJson, $companyId);
    } else {
        $query = "INSERT INTO company_data (company_id, current_markets, target_markets, differentiation_factors, 
                  needs, competitiveness, logistics, expectations, consents) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($link, $query);
        mysqli_stmt_bind_param($stmt, 'issssssss', $companyId, $currentMarketsJson, $targetMarketsJson, $diffFactorsJson, 
                               $needsJson, $competitivenessJson, $logisticsJson, $expectationsJson, $consentsJson);
    }
    
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    // ========== 8. ЗАГРУЗКА ФАЙЛОВ ==========
    
    $uploadedFiles = [];
    
    // Загружаем фото основного продукта
    if (isset($_FILES['product_photo']) && $_FILES['product_photo']['error'] === UPLOAD_ERR_OK) {
        try {
            // Получаем ID основного продукта
            $query = "SELECT id FROM products WHERE company_id = ? AND is_main = TRUE LIMIT 1";
            $stmt = mysqli_prepare($link, $query);
            mysqli_stmt_bind_param($stmt, 'i', $companyId);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $product = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
            
            if ($product) {
                $fileId = $fileManager->upload($_FILES['product_photo'], $product['id'], $userId, 'product_photo');
                $uploadedFiles[] = $fileId;
            }
        } catch (Exception $e) {
            error_log("Error uploading product photo: " . $e->getMessage());
        }
    }
    
    // Загружаем фото вторичных продуктов
    if (isset($_FILES['product_photo_sec']) && is_array($_FILES['product_photo_sec']['name'])) {
        $query = "SELECT id FROM products WHERE company_id = ? AND is_main = FALSE ORDER BY id";
        $stmt = mysqli_prepare($link, $query);
        mysqli_stmt_bind_param($stmt, 'i', $companyId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $products = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $products[] = $row['id'];
        }
        mysqli_stmt_close($stmt);
        
        $count = count($_FILES['product_photo_sec']['name']);
        for ($i = 0; $i < $count && $i < count($products); $i++) {
            if ($_FILES['product_photo_sec']['error'][$i] === UPLOAD_ERR_OK) {
                try {
                    $file = [
                        'name' => $_FILES['product_photo_sec']['name'][$i],
                        'type' => $_FILES['product_photo_sec']['type'][$i],
                        'tmp_name' => $_FILES['product_photo_sec']['tmp_name'][$i],
                        'error' => $_FILES['product_photo_sec']['error'][$i],
                        'size' => $_FILES['product_photo_sec']['size'][$i],
                    ];
                    $fileId = $fileManager->upload($file, $products[$i], $userId, 'product_photo');
                    $uploadedFiles[] = $fileId;
                } catch (Exception $e) {
                    error_log("Error uploading secondary product photo: " . $e->getMessage());
                }
            }
        }
    }
    
    // Загружаем логотипы компании
    if (isset($_FILES['company_logo']) && is_array($_FILES['company_logo']['name'])) {
        $count = count($_FILES['company_logo']['name']);
        for ($i = 0; $i < $count; $i++) {
            if ($_FILES['company_logo']['error'][$i] === UPLOAD_ERR_OK) {
                try {
                    $file = [
                        'name' => $_FILES['company_logo']['name'][$i],
                        'type' => $_FILES['company_logo']['type'][$i],
                        'tmp_name' => $_FILES['company_logo']['tmp_name'][$i],
                        'error' => $_FILES['company_logo']['error'][$i],
                        'size' => $_FILES['company_logo']['size'][$i],
                    ];
                    // Для файлов компании используем company_id как product_id (временное решение)
                    // Или можно создать отдельную логику в FileManager
                    $fileId = $fileManager->upload($file, null, $userId, 'logo');
                    $uploadedFiles[] = $fileId;
                } catch (Exception $e) {
                    error_log("Error uploading company logo: " . $e->getMessage());
                }
            }
        }
    }
    
    // Загружаем фото процессов
    if (isset($_FILES['process_photos']) && is_array($_FILES['process_photos']['name'])) {
        $count = count($_FILES['process_photos']['name']);
        for ($i = 0; $i < $count; $i++) {
            if ($_FILES['process_photos']['error'][$i] === UPLOAD_ERR_OK) {
                try {
                    $file = [
                        'name' => $_FILES['process_photos']['name'][$i],
                        'type' => $_FILES['process_photos']['type'][$i],
                        'tmp_name' => $_FILES['process_photos']['tmp_name'][$i],
                        'error' => $_FILES['process_photos']['error'][$i],
                        'size' => $_FILES['process_photos']['size'][$i],
                    ];
                    $fileId = $fileManager->upload($file, null, $userId, 'process_photo');
                    $uploadedFiles[] = $fileId;
                } catch (Exception $e) {
                    error_log("Error uploading process photo: " . $e->getMessage());
                }
            }
        }
    }
    
    // Загружаем каталоги
    if (isset($_FILES['digital_catalog']) && is_array($_FILES['digital_catalog']['name'])) {
        $count = count($_FILES['digital_catalog']['name']);
        for ($i = 0; $i < $count; $i++) {
            if ($_FILES['digital_catalog']['error'][$i] === UPLOAD_ERR_OK) {
                try {
                    $file = [
                        'name' => $_FILES['digital_catalog']['name'][$i],
                        'type' => $_FILES['digital_catalog']['type'][$i],
                        'tmp_name' => $_FILES['digital_catalog']['tmp_name'][$i],
                        'error' => $_FILES['digital_catalog']['error'][$i],
                        'size' => $_FILES['digital_catalog']['size'][$i],
                    ];
                    $fileId = $fileManager->upload($file, null, $userId, 'catalog');
                    $uploadedFiles[] = $fileId;
                } catch (Exception $e) {
                    error_log("Error uploading catalog: " . $e->getMessage());
                }
            }
        }
    }
    
    // Загружаем видео
    if (isset($_FILES['institutional_video']) && $_FILES['institutional_video']['error'] === UPLOAD_ERR_OK) {
        try {
            $fileId = $fileManager->upload($_FILES['institutional_video'], null, $userId, 'video');
            $uploadedFiles[] = $fileId;
        } catch (Exception $e) {
            error_log("Error uploading video: " . $e->getMessage());
        }
    }
    
    // Коммитим транзакцию
    mysqli_commit($link);
    
    $return['ok'] = 1;
    $return['res'] = 'Datos guardados correctamente';
    $return['company_id'] = $companyId;
    $return['uploaded_files'] = count($uploadedFiles);
    
} catch (Exception $e) {
    // Откатываем транзакцию при ошибке
    mysqli_rollback($link);
    $return['err'] = 'Error al guardar: ' . $e->getMessage();
    error_log("Error in regfull_js.php: " . $e->getMessage());
}

echo json_encode($return);
?>
