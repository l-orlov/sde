<?
session_start();
date_default_timezone_set('America/Argentina/Buenos_Aires');
set_time_limit (0);
error_reporting(E_ALL);
ob_implicit_flush();

include __DIR__ . '/path_helper.php';
include getIncludesFilePath('functions.php');
DBconnect();

$return = ['ok' => 0, 'err' => ''];

$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);

if (!$input || !isset($input['user_id'])) {
    echo json_encode(['ok' => 0, 'err' => 'user_id requerido']);
    exit;
}

$userId = intval($input['user_id']);

mysqli_begin_transaction($link);

try {
    // Получаем текущие данные компании из БД
    $query = "SELECT id, name, tax_id, legal_name, start_date, website, organization_type, main_activity 
              FROM companies WHERE user_id = ? LIMIT 1";
    $stmt = mysqli_prepare($link, $query);
    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $currentCompany = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    if (!$currentCompany) {
        throw new Exception("No se encontró la empresa para este usuario");
    }
    
    $companyId = intval($currentCompany['id']);
    
    // 0. Обновление данных пользователя из таблицы users
    $userEmail = isset($input['user_email']) && $input['user_email'] !== '' ? htmlspecialchars(trim($input['user_email'])) : null;
    $userPhone = isset($input['user_phone']) && $input['user_phone'] !== '' ? htmlspecialchars(trim($input['user_phone'])) : null;
    $userIsAdmin = isset($input['user_is_admin']) ? intval($input['user_is_admin']) : null;
    
    // Загружаем текущие данные пользователя
    $query = "SELECT email, phone, is_admin FROM users WHERE id = ? LIMIT 1";
    $stmt = mysqli_prepare($link, $query);
    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $currentUser = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    // Объединяем с текущими данными
    if ($userEmail === null) $userEmail = $currentUser['email'];
    if ($userPhone === null) $userPhone = $currentUser['phone'];
    if ($userIsAdmin === null) $userIsAdmin = intval($currentUser['is_admin']);
    
    // Обновляем данные пользователя
    $query = "UPDATE users SET email = ?, phone = ?, is_admin = ?, updated_at = UNIX_TIMESTAMP() WHERE id = ?";
    $stmt = mysqli_prepare($link, $query);
    mysqli_stmt_bind_param($stmt, 'ssii', $userEmail, $userPhone, $userIsAdmin, $userId);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Error al actualizar datos del usuario: " . mysqli_error($link));
    }
    mysqli_stmt_close($stmt);
    
    // Объединяем текущие данные с изменениями из формы
    // Если поле не передано в форме, используем текущее значение из БД
    $name = isset($input['name']) && $input['name'] !== '' ? htmlspecialchars(trim($input['name'])) : $currentCompany['name'];
    $taxId = isset($input['tax_id']) && $input['tax_id'] !== '' ? htmlspecialchars(trim($input['tax_id'])) : $currentCompany['tax_id'];
    $legalName = isset($input['legal_name']) && $input['legal_name'] !== '' ? htmlspecialchars(trim($input['legal_name'])) : $currentCompany['legal_name'];
    $startDate = isset($input['start_date']) && $input['start_date'] !== '' ? htmlspecialchars(trim($input['start_date'])) : null;
    $website = isset($input['website']) && $input['website'] !== '' ? htmlspecialchars(trim($input['website'])) : $currentCompany['website'];
    $organizationType = isset($input['organization_type']) && $input['organization_type'] !== '' ? htmlspecialchars(trim($input['organization_type'])) : $currentCompany['organization_type'];
    $mainActivity = isset($input['main_activity']) && $input['main_activity'] !== '' ? htmlspecialchars(trim($input['main_activity'])) : $currentCompany['main_activity'];
    
    // Обработка start_date - если не передан, используем текущий из БД
    if (!$startDate && $currentCompany['start_date']) {
        $timestamp = intval($currentCompany['start_date']);
        if ($timestamp > 0) {
            $dateObj = new DateTime();
            $dateObj->setTimestamp($timestamp);
            $startDate = $dateObj->format('d/m/Y');
        }
    }
    
    // Конвертация start_date из dd/mm/yyyy в timestamp
    $startDateTimestamp = null;
    if ($startDate) {
        $dateParts = explode('/', $startDate);
        if (count($dateParts) === 3) {
            $day = intval($dateParts[0]);
            $month = intval($dateParts[1]);
            $year = intval($dateParts[2]);
            if ($day > 0 && $month > 0 && $year > 0) {
                $dateObj = new DateTime();
                $dateObj->setDate($year, $month, $day);
                $dateObj->setTime(0, 0, 0);
                $startDateTimestamp = $dateObj->getTimestamp();
            }
        }
    }
    
    $query = "UPDATE companies SET name = ?, tax_id = ?, legal_name = ?, start_date = ?, 
              website = ?, organization_type = ?, main_activity = ?, updated_at = UNIX_TIMESTAMP() 
              WHERE id = ?";
    $stmt = mysqli_prepare($link, $query);
    mysqli_stmt_bind_param($stmt, 'sssissis', $name, $taxId, $legalName, $startDateTimestamp, $website, $organizationType, $mainActivity, $companyId);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Error al actualizar datos de la empresa: " . mysqli_error($link));
    }
    mysqli_stmt_close($stmt);
    
    // 2. Обновление продуктов и услуг (массив)
    $certifications = isset($input['certifications']) ? htmlspecialchars(trim($input['certifications'])) : '';
    
    if (isset($input['products']) && is_array($input['products'])) {
        // Загружаем все существующие продукты и услуги
        $query = "SELECT id FROM products WHERE user_id = ? ORDER BY id ASC";
        $stmt = mysqli_prepare($link, $query);
        mysqli_stmt_bind_param($stmt, 'i', $userId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $existingProductIds = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $existingProductIds[] = intval($row['id']);
        }
        mysqli_stmt_close($stmt);
        
        $submittedProductIds = [];
        $firstProductIndex = null;
        $firstServiceIndex = null;
        
        // Разделяем на продукты и услуги
        $productsList = [];
        $servicesList = [];
        foreach ($input['products'] as $index => $item) {
            $itemType = isset($item['type']) ? $item['type'] : 'product';
            if ($itemType === 'service') {
                $servicesList[] = ['index' => $index, 'data' => $item];
                if ($firstServiceIndex === null) {
                    $firstServiceIndex = $index;
                }
            } else {
                $productsList[] = ['index' => $index, 'data' => $item];
                if ($firstProductIndex === null) {
                    $firstProductIndex = $index;
                }
            }
        }
        
        // Обрабатываем каждый продукт/услугу из формы
        foreach ($input['products'] as $index => $item) {
            $itemId = isset($item['id']) && $item['id'] !== null && $item['id'] !== '' ? intval($item['id']) : null;
            $itemType = isset($item['type']) ? $item['type'] : 'product';
            $itemName = isset($item['name']) ? htmlspecialchars(trim($item['name'])) : '';
            $itemDescription = isset($item['description']) ? htmlspecialchars(trim($item['description'])) : '';
            $itemAnnualExport = isset($item['annual_export']) ? htmlspecialchars(trim($item['annual_export'])) : '';
            $itemActivity = isset($item['activity']) ? htmlspecialchars(trim($item['activity'])) : null;
            
            // Определяем is_main: первый продукт или первая услуга
            $isMain = 0;
            if ($itemType === 'product' && $index === $firstProductIndex) {
                $isMain = 1;
            } else if ($itemType === 'service' && $index === $firstServiceIndex) {
                $isMain = 1;
            }
            
            if ($itemId && in_array($itemId, $existingProductIds)) {
                // Обновляем существующий продукт/услугу
                if ($itemType === 'service') {
                    $query = "UPDATE products SET type = ?, activity = ?, name = ?, description = ?, annual_export = ?, certifications = ?, is_main = ?, updated_at = UNIX_TIMESTAMP()
                              WHERE id = ?";
                    $stmt = mysqli_prepare($link, $query);
                    mysqli_stmt_bind_param($stmt, 'ssssssii', $itemType, $itemActivity, $itemName, $itemDescription, $itemAnnualExport, $certifications, $isMain, $itemId);
                } else {
                    $query = "UPDATE products SET type = ?, name = ?, description = ?, annual_export = ?, certifications = ?, is_main = ?, updated_at = UNIX_TIMESTAMP()
                              WHERE id = ?";
                    $stmt = mysqli_prepare($link, $query);
                    mysqli_stmt_bind_param($stmt, 'sssssii', $itemType, $itemName, $itemDescription, $itemAnnualExport, $certifications, $isMain, $itemId);
                }
                
                if (!mysqli_stmt_execute($stmt)) {
                    throw new Exception("Error al actualizar producto/servicio: " . mysqli_error($link));
                }
                mysqli_stmt_close($stmt);
                
                $submittedProductIds[] = $itemId;
            } else {
                // Создаем новый продукт/услугу
                if ($itemType === 'service') {
                    $query = "INSERT INTO products (company_id, user_id, type, activity, is_main, name, description, annual_export, certifications) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt = mysqli_prepare($link, $query);
                    mysqli_stmt_bind_param($stmt, 'iississss', $companyId, $userId, $itemType, $itemActivity, $isMain, $itemName, $itemDescription, $itemAnnualExport, $certifications);
                } else {
                    $query = "INSERT INTO products (company_id, user_id, type, is_main, name, description, annual_export, certifications) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt = mysqli_prepare($link, $query);
                    mysqli_stmt_bind_param($stmt, 'iisissss', $companyId, $userId, $itemType, $isMain, $itemName, $itemDescription, $itemAnnualExport, $certifications);
                }
                
                if (!mysqli_stmt_execute($stmt)) {
                    throw new Exception("Error al crear producto/servicio: " . mysqli_error($link));
                }
                $newItemId = mysqli_insert_id($link);
                mysqli_stmt_close($stmt);
                
                if ($newItemId) {
                    $submittedProductIds[] = $newItemId;
                }
            }
        }
        
        // Удаляем продукты/услуги, которые не были отправлены в форме
        if (!empty($submittedProductIds)) {
            $placeholders = implode(',', array_fill(0, count($submittedProductIds), '?'));
            $query = "DELETE FROM products WHERE user_id = ? AND id NOT IN ($placeholders)";
            $stmt = mysqli_prepare($link, $query);
            $params = array_merge([$userId], $submittedProductIds);
            $types = str_repeat('i', count($params));
            mysqli_stmt_bind_param($stmt, $types, ...$params);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        } else {
            // Если нет продуктов/услуг в форме, удаляем все
            $query = "DELETE FROM products WHERE user_id = ?";
            $stmt = mysqli_prepare($link, $query);
            mysqli_stmt_bind_param($stmt, 'i', $userId);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
    }
    
    // 4. Обновляем users.company_name синхронизированно с companies.name
    $query = "UPDATE users SET company_name = ?, updated_at = UNIX_TIMESTAMP() WHERE id = ?";
    $stmt = mysqli_prepare($link, $query);
    mysqli_stmt_bind_param($stmt, 'si', $name, $userId);
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Error al actualizar nombre en users: " . mysqli_error($link));
    }
    mysqli_stmt_close($stmt);
    
    // 5. Обновление company_data (все поля)
    // Загружаем текущие данные company_data
    $query = "SELECT current_markets, target_markets, differentiation_factors, needs, 
                     competitiveness, logistics, expectations, consents
              FROM company_data WHERE company_id = ? LIMIT 1";
    $stmt = mysqli_prepare($link, $query);
    mysqli_stmt_bind_param($stmt, 'i', $companyId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $currentCompanyData = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    // Парсим текущие JSON данные
    $currentCompetitiveness = $currentCompanyData && $currentCompanyData['competitiveness'] 
        ? json_decode($currentCompanyData['competitiveness'], true) 
        : [];
    $currentLogistics = $currentCompanyData && $currentCompanyData['logistics'] 
        ? json_decode($currentCompanyData['logistics'], true) 
        : [];
    $currentExpectations = $currentCompanyData && $currentCompanyData['expectations'] 
        ? json_decode($currentCompanyData['expectations'], true) 
        : [];
    $currentConsents = $currentCompanyData && $currentCompanyData['consents'] 
        ? json_decode($currentCompanyData['consents'], true) 
        : [];
    
    // Обновляем competitiveness
    if (isset($input['differentiation_factors'])) {
        $currentCompetitiveness['differentiation_factors'] = $input['differentiation_factors'];
    }
    if (isset($input['other_differentiation'])) {
        $currentCompetitiveness['other_differentiation'] = htmlspecialchars(trim($input['other_differentiation']));
    }
    if (isset($input['company_history'])) {
        $currentCompetitiveness['company_history'] = htmlspecialchars(trim($input['company_history']));
    }
    if (isset($input['awards'])) {
        $currentCompetitiveness['awards'] = htmlspecialchars(trim($input['awards']));
    }
    if (isset($input['awards_detail'])) {
        $currentCompetitiveness['awards_detail'] = htmlspecialchars(trim($input['awards_detail']));
    }
    if (isset($input['fairs'])) {
        $currentCompetitiveness['fairs'] = htmlspecialchars(trim($input['fairs']));
    }
    if (isset($input['rounds'])) {
        $currentCompetitiveness['rounds'] = htmlspecialchars(trim($input['rounds']));
    }
    if (isset($input['export_experience'])) {
        $currentCompetitiveness['export_experience'] = htmlspecialchars(trim($input['export_experience']));
    }
    if (isset($input['commercial_references'])) {
        $currentCompetitiveness['commercial_references'] = htmlspecialchars(trim($input['commercial_references']));
    }
    
    // Обновляем logistics
    if (isset($input['export_capacity'])) {
        $currentLogistics['export_capacity'] = htmlspecialchars(trim($input['export_capacity']));
    }
    if (isset($input['estimated_term'])) {
        $currentLogistics['estimated_term'] = htmlspecialchars(trim($input['estimated_term']));
    }
    if (isset($input['logistics_infrastructure'])) {
        $currentLogistics['logistics_infrastructure'] = htmlspecialchars(trim($input['logistics_infrastructure']));
    }
    if (isset($input['ports_airports'])) {
        $currentLogistics['ports_airports'] = htmlspecialchars(trim($input['ports_airports']));
    }
    
    // Обновляем expectations
    if (isset($input['needs'])) {
        $currentExpectations['needs'] = $input['needs'];
    }
    if (isset($input['other_needs'])) {
        $currentExpectations['other_needs'] = htmlspecialchars(trim($input['other_needs']));
    }
    if (isset($input['interest_participate'])) {
        $currentExpectations['interest_participate'] = htmlspecialchars(trim($input['interest_participate']));
    }
    if (isset($input['training_availability'])) {
        $currentExpectations['training_availability'] = htmlspecialchars(trim($input['training_availability']));
    }
    
    // Обновляем consents
    if (isset($input['authorization_publish'])) {
        $currentConsents['authorization_publish'] = htmlspecialchars(trim($input['authorization_publish']));
    }
    if (isset($input['authorization_publication'])) {
        $currentConsents['authorization_publication'] = htmlspecialchars(trim($input['authorization_publication']));
    }
    if (isset($input['accept_contact'])) {
        $currentConsents['accept_contact'] = htmlspecialchars(trim($input['accept_contact']));
    }
    
    // Подготавливаем JSON для всех полей
    $currentMarkets = isset($input['current_markets']) && $input['current_markets'] !== '' 
        ? htmlspecialchars(trim($input['current_markets'])) 
        : ($currentCompanyData && $currentCompanyData['current_markets'] ? json_decode($currentCompanyData['current_markets'], true) : '');
    $currentMarketsJson = json_encode($currentMarkets, JSON_UNESCAPED_UNICODE);
    
    $targetMarketsJson = $currentCompanyData && $currentCompanyData['target_markets'] 
        ? $currentCompanyData['target_markets'] 
        : '[]';
    
    $diffFactorsJson = isset($input['differentiation_factors']) && is_array($input['differentiation_factors'])
        ? json_encode($input['differentiation_factors'], JSON_UNESCAPED_UNICODE)
        : ($currentCompanyData && $currentCompanyData['differentiation_factors'] ? $currentCompanyData['differentiation_factors'] : '[]');
    
    $needsJson = isset($input['needs']) && is_array($input['needs'])
        ? json_encode($input['needs'], JSON_UNESCAPED_UNICODE)
        : ($currentCompanyData && $currentCompanyData['needs'] ? $currentCompanyData['needs'] : '[]');
    
    $competitivenessJson = json_encode($currentCompetitiveness, JSON_UNESCAPED_UNICODE);
    $logisticsJson = json_encode($currentLogistics, JSON_UNESCAPED_UNICODE);
    $expectationsJson = json_encode($currentExpectations, JSON_UNESCAPED_UNICODE);
    $consentsJson = json_encode($currentConsents, JSON_UNESCAPED_UNICODE);
    
    if ($currentCompanyData) {
        // Обновляем существующую запись
        $query = "UPDATE company_data SET current_markets = ?, target_markets = ?, differentiation_factors = ?, needs = ?, 
                         competitiveness = ?, logistics = ?, expectations = ?, consents = ?, updated_at = UNIX_TIMESTAMP() 
                  WHERE company_id = ?";
        $stmt = mysqli_prepare($link, $query);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'ssssssssi', $currentMarketsJson, $targetMarketsJson, $diffFactorsJson, $needsJson, 
                                  $competitivenessJson, $logisticsJson, $expectationsJson, $consentsJson, $companyId);
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("Error al actualizar company_data: " . mysqli_error($link));
            }
            mysqli_stmt_close($stmt);
        }
    } else {
        // Создаем новую запись
        $query = "INSERT INTO company_data (company_id, current_markets, target_markets, differentiation_factors, needs, competitiveness, logistics, expectations, consents) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($link, $query);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'issssssss', $companyId, $currentMarketsJson, $targetMarketsJson, $diffFactorsJson, $needsJson, $competitivenessJson, $logisticsJson, $expectationsJson, $consentsJson);
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("Error al crear company_data: " . mysqli_error($link));
            }
            mysqli_stmt_close($stmt);
        }
    }
    
    mysqli_commit($link);
    $return['ok'] = 1;
    $return['res'] = 'Datos guardados correctamente';
    
} catch (Exception $e) {
    mysqli_rollback($link);
    $return['err'] = $e->getMessage();
}

header('Content-Type: application/json');
echo json_encode($return);
?>

