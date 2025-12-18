<?php
session_start();
set_time_limit(0);
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('post_max_size', '100M');
ini_set('upload_max_filesize', '100M');
ini_set('max_file_uploads', '50');
ini_set('memory_limit', '256M');
ini_set('max_execution_time', '300');

ob_start();

include "functions.php";
require_once __DIR__ . '/FileManager.php';

DBconnect();

$return = ['res' => '', 'ok' => 0, 'err' => ''];

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
if (!isset($_SESSION['uid'])) {
    if (ob_get_level()) ob_clean();
    $return['err'] = 'No autorizado. Por favor, inicie sesión.';
    header('Content-Type: application/json');
    echo json_encode($return);
    exit;
}

$userId = intval($_SESSION['uid']);
$fileManager = new FileManager();
$input = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST as $key => $value) {
        $cleanKey = str_replace('[]', '', $key);
        
        if (is_array($value)) {
            $input[$cleanKey] = $value;
        } else {
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

mysqli_begin_transaction($link);

try {
    $query = "SELECT id FROM companies WHERE user_id = ?";
    $stmt = mysqli_prepare($link, $query);
    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $company = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    $companyId = $company ? $company['id'] : null;
    
    // ========== 1. СОХРАНЕНИЕ ДАННЫХ КОМПАНИИ ==========
    $name = isset($input['name']) ? htmlspecialchars(trim($input['name'])) : '';
    $taxId = isset($input['tax_id']) ? htmlspecialchars(trim($input['tax_id'])) : '';
    $legalName = isset($input['legal_name']) ? htmlspecialchars(trim($input['legal_name'])) : '';
    $startDate = isset($input['start_date']) ? htmlspecialchars(trim($input['start_date'])) : null;
    $website = isset($input['website']) ? htmlspecialchars(trim($input['website'])) : '';
    $organizationType = isset($input['organization_type']) ? htmlspecialchars(trim($input['organization_type'])) : '';
    $mainActivity = isset($input['main_activity']) ? htmlspecialchars(trim($input['main_activity'])) : '';
    
    $startDateTimestamp = null;
    if ($startDate) {
        $dateObj = DateTime::createFromFormat('d/m/Y', $startDate);
        if ($dateObj) {
            $dateObj->setTime(0, 0, 0);
            $startDateTimestamp = $dateObj->getTimestamp();
        }
    }
    
    if ($companyId) {
        $query = "UPDATE companies SET name = ?, tax_id = ?, legal_name = ?, start_date = ?, 
                  website = ?, organization_type = ?, main_activity = ?, updated_at = UNIX_TIMESTAMP() 
                  WHERE id = ?";
        $stmt = mysqli_prepare($link, $query);
        mysqli_stmt_bind_param($stmt, 'sssissis', $name, $taxId, $legalName, $startDateTimestamp, $website, $organizationType, $mainActivity, $companyId);
    } else {
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
    
    // Синхронизация: обновляем users.company_name и users.tax_id
    if (!empty($name) || !empty($taxId)) {
        $query = "UPDATE users SET company_name = ?, tax_id = ? WHERE id = ?";
        $stmt = mysqli_prepare($link, $query);
        mysqli_stmt_bind_param($stmt, 'ssi', $name, $taxId, $userId);
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Error al sincronizar datos en users: " . mysqli_error($link));
        }
        mysqli_stmt_close($stmt);
    }
    
    // ========== 2. АДРЕСА ==========
    
    $query = "DELETE FROM company_addresses WHERE company_id = ?";
    $stmt = mysqli_prepare($link, $query);
    mysqli_stmt_bind_param($stmt, 'i', $companyId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
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
    
    $mainProductId = null;
    if (isset($input['main_product']) && !empty($input['main_product'])) {
        $query = "SELECT id FROM products WHERE company_id = ? AND is_main = 1 LIMIT 1";
        $stmt = mysqli_prepare($link, $query);
        mysqli_stmt_bind_param($stmt, 'i', $companyId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $existingMain = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        
        $productName = htmlspecialchars(trim($input['main_product'] ?? ''));
        $tariffCode = htmlspecialchars(trim($input['tariff_code'] ?? ''));
        $description = htmlspecialchars(trim($input['product_description'] ?? ''));
        $volumeUnit = htmlspecialchars(trim($input['volume_unit'] ?? ''));
        $volumeAmount = htmlspecialchars(trim($input['volume_amount'] ?? ''));
        $annualExport = htmlspecialchars(trim($input['annual_export'] ?? ''));
        $certifications = htmlspecialchars(trim($input['certifications'] ?? ''));
        
        if ($existingMain && isset($existingMain['id'])) {
            $query = "UPDATE products SET name = ?, tariff_code = ?, description = ?, 
                      volume_unit = ?, volume_amount = ?, annual_export = ?, certifications = ?
                      WHERE id = ? AND company_id = ?";
            $stmt = mysqli_prepare($link, $query);
            mysqli_stmt_bind_param($stmt, 'sssssssii', $productName, $tariffCode, $description, 
                                  $volumeUnit, $volumeAmount, $annualExport, $certifications, 
                                  $existingMain['id'], $companyId);
            mysqli_stmt_execute($stmt);
            $mainProductId = $existingMain['id'];
            mysqli_stmt_close($stmt);
        } else {
            $query = "INSERT INTO products (company_id, user_id, is_main, name, tariff_code, description, 
                      volume_unit, volume_amount, annual_export, certifications) 
                      VALUES (?, ?, TRUE, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($link, $query);
            mysqli_stmt_bind_param($stmt, 'iisssssss', $companyId, $userId, $productName, $tariffCode, $description, $volumeUnit, $volumeAmount, $annualExport, $certifications);
            mysqli_stmt_execute($stmt);
            $mainProductId = mysqli_insert_id($link);
            mysqli_stmt_close($stmt);
        }
    }
    
    $newProductIdsByIndex = [];
    $updatedProductIds = [];
    
    if (isset($input['secondary_products']) && is_array($input['secondary_products'])) {
        $names = $input['secondary_products'];
        $productIds = isset($input['product_id_sec']) && is_array($input['product_id_sec']) ? $input['product_id_sec'] : [];
        $tariffCodes = isset($input['tariff_code_sec']) && is_array($input['tariff_code_sec']) ? $input['tariff_code_sec'] : [];
        $descriptions = isset($input['product_description_sec']) && is_array($input['product_description_sec']) ? $input['product_description_sec'] : [];
        $volumeUnits = isset($input['volume_unit_sec']) && is_array($input['volume_unit_sec']) ? $input['volume_unit_sec'] : [];
        $volumeAmounts = isset($input['volume_amount_sec']) && is_array($input['volume_amount_sec']) ? $input['volume_amount_sec'] : [];
        $annualExports = isset($input['annual_export_sec']) && is_array($input['annual_export_sec']) ? $input['annual_export_sec'] : [];
        
        $updateQuery = "UPDATE products SET name = ?, tariff_code = ?, description = ?, 
                        volume_unit = ?, volume_amount = ?, annual_export = ?
                        WHERE id = ? AND company_id = ? AND is_main = 0";
        $updateStmt = mysqli_prepare($link, $updateQuery);
        
        $insertQuery = "INSERT INTO products (company_id, user_id, is_main, name, tariff_code, description, 
                      volume_unit, volume_amount, annual_export) 
                      VALUES (?, ?, FALSE, ?, ?, ?, ?, ?, ?)";
        $insertStmt = mysqli_prepare($link, $insertQuery);
        
        for ($i = 0; $i < count($names); $i++) {
            $productName = htmlspecialchars(trim($names[$i] ?? ''));
            $productId = isset($productIds[$i]) ? intval($productIds[$i]) : 0;
            $tariffCode = isset($tariffCodes[$i]) ? htmlspecialchars(trim($tariffCodes[$i])) : '';
            $description = isset($descriptions[$i]) ? htmlspecialchars(trim($descriptions[$i])) : '';
            $volumeUnit = isset($volumeUnits[$i]) ? htmlspecialchars(trim($volumeUnits[$i])) : '';
            $volumeAmount = isset($volumeAmounts[$i]) ? htmlspecialchars(trim($volumeAmounts[$i])) : '';
            $annualExport = isset($annualExports[$i]) ? htmlspecialchars(trim($annualExports[$i])) : '';
            
            $hasData = !empty($productName) || !empty($tariffCode) || !empty($description) || 
                       !empty($volumeUnit) || !empty($volumeAmount) || !empty($annualExport);
            
            if (!$hasData && $productId == 0) {
                continue;
            }
            
            if (empty($productName) && $productId == 0) {
                $productName = 'Producto ' . ($i + 1);
            } else if (empty($productName) && $productId > 0) {
                $productName = 'Producto ' . $productId;
            }
            
            if ($productId > 0) {
                mysqli_stmt_bind_param($updateStmt, 'ssssssii', $productName, $tariffCode, $description, 
                                      $volumeUnit, $volumeAmount, $annualExport, $productId, $companyId);
                mysqli_stmt_execute($updateStmt);
                $newProductIdsByIndex[$i] = $productId;
                $updatedProductIds[] = $productId;
            } else {
                mysqli_stmt_bind_param($insertStmt, 'iissssss', $companyId, $userId, $productName, $tariffCode, $description, $volumeUnit, $volumeAmount, $annualExport);
                mysqli_stmt_execute($insertStmt);
                $newProductIdsByIndex[$i] = mysqli_insert_id($link);
            }
        }
        
        mysqli_stmt_close($updateStmt);
        mysqli_stmt_close($insertStmt);
        
        $allProductIds = array_merge($updatedProductIds, array_values($newProductIdsByIndex));
        $allProductIds = array_filter($allProductIds, function($id) { return $id > 0; });
        $allProductIds = array_unique($allProductIds);
        
        if (!empty($allProductIds)) {
            $placeholders = implode(',', array_fill(0, count($allProductIds), '?'));
            $deleteQuery = "DELETE FROM products WHERE company_id = ? AND is_main = 0 AND id NOT IN ($placeholders)";
            $deleteStmt = mysqli_prepare($link, $deleteQuery);
            $types = 'i' . str_repeat('i', count($allProductIds));
            $params = array_merge([$companyId], $allProductIds);
            mysqli_stmt_bind_param($deleteStmt, $types, ...$params);
            mysqli_stmt_execute($deleteStmt);
            mysqli_stmt_close($deleteStmt);
        } else {
            $deleteQuery = "DELETE FROM products WHERE company_id = ? AND is_main = 0";
            $deleteStmt = mysqli_prepare($link, $deleteQuery);
            mysqli_stmt_bind_param($deleteStmt, 'i', $companyId);
            mysqli_stmt_execute($deleteStmt);
            mysqli_stmt_close($deleteStmt);
        }
    } else {
        $query = "DELETE FROM products WHERE company_id = ? AND is_main = 0";
        $stmt = mysqli_prepare($link, $query);
        mysqli_stmt_bind_param($stmt, 'i', $companyId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
    
    // ========== 6. ИСТОРИЯ ЭКСПОРТА ==========
    
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
    
    $diffFactors = [];
    if (isset($input['differentiation_factors']) && is_array($input['differentiation_factors'])) {
        $diffFactors = $input['differentiation_factors'];
    }
    $jsonData['differentiation_factors'] = $diffFactors;
    
    $needs = [];
    if (isset($input['needs']) && is_array($input['needs'])) {
        $needs = $input['needs'];
    }
    $jsonData['needs'] = $needs;
    $currentMarketsJson = json_encode($jsonData['current_markets'], JSON_UNESCAPED_UNICODE);
    $targetMarketsJson = json_encode($jsonData['target_markets'], JSON_UNESCAPED_UNICODE);
    $diffFactorsJson = json_encode($jsonData['differentiation_factors'], JSON_UNESCAPED_UNICODE);
    $needsJson = json_encode($jsonData['needs'], JSON_UNESCAPED_UNICODE);
    $competitivenessJson = json_encode($jsonData['competitiveness'], JSON_UNESCAPED_UNICODE);
    $logisticsJson = json_encode($jsonData['logistics'], JSON_UNESCAPED_UNICODE);
    $expectationsJson = json_encode($jsonData['expectations'], JSON_UNESCAPED_UNICODE);
    $consentsJson = json_encode($jsonData['consents'], JSON_UNESCAPED_UNICODE);
    
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
    
    // ========== 8. ОБРАБОТКА ФАЙЛОВ (ЗАМЕНА И СОХРАНЕНИЕ) ==========
    
    foreach ($input as $key => $value) {
        if (strpos($key, 'new_file_') === 0) {
            $fileKey = substr($key, 9);
            $newFileId = intval($value);
            
            $productIdKey = 'new_file_product_id_' . $fileKey;
            $productId = isset($input[$productIdKey]) ? intval($input[$productIdKey]) : null;
            
            $productIndexKey = 'new_file_product_index_' . $fileKey;
            $productIndex = isset($input[$productIndexKey]) ? intval($input[$productIndexKey]) : null;
            
            if ($productId === null && $productIndex !== null && isset($newProductIdsByIndex[$productIndex])) {
                $productId = $newProductIdsByIndex[$productIndex];
            }
            
            if ($productId === null && $fileKey === 'product_photo' && $mainProductId) {
                $productId = $mainProductId;
            }
            
            $existingFileIds = [];
            
            $existingFileKey1 = 'existing_file_' . $fileKey;
            $existingFileKey2 = 'existing_file_' . $fileKey . '[]';
            
            if (isset($input[$existingFileKey1])) {
                if (is_array($input[$existingFileKey1])) {
                    $existingFileIds = array_merge($existingFileIds, array_map('intval', $input[$existingFileKey1]));
                } else {
                    $existingFileIds[] = intval($input[$existingFileKey1]);
                }
            }
            
            if (isset($input[$existingFileKey2])) {
                if (is_array($input[$existingFileKey2])) {
                    $existingFileIds = array_merge($existingFileIds, array_map('intval', $input[$existingFileKey2]));
                } else {
                    $existingFileIds[] = intval($input[$existingFileKey2]);
                }
            }
            
            $existingFileIds = array_unique($existingFileIds);
            
            foreach ($existingFileIds as $oldFileId) {
                if ($oldFileId > 0) {
                    try {
                        $fileManager->delete($oldFileId, $userId);
                    } catch (Exception $e) {
                        error_log("Error deleting old file {$oldFileId}: " . $e->getMessage());
                    }
                }
            }
            
            if ($productId) {
                $query = "UPDATE files SET is_temporary = 0, product_id = ? WHERE id = ? AND user_id = ?";
                $stmt = mysqli_prepare($link, $query);
                mysqli_stmt_bind_param($stmt, 'iii', $productId, $newFileId, $userId);
            } else {
                $query = "UPDATE files SET is_temporary = 0 WHERE id = ? AND user_id = ?";
                $stmt = mysqli_prepare($link, $query);
                mysqli_stmt_bind_param($stmt, 'ii', $newFileId, $userId);
            }
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
    }
    
    // Обработка существующих файлов (когда нет нового файла)
    foreach ($input as $key => $value) {
        if (strpos($key, 'existing_file_') === 0 && strpos($key, 'new_file_') === false) {
            $fileKey = substr($key, 14);
            if (strpos($fileKey, '[]') !== false) {
                $fileKey = str_replace('[]', '', $fileKey);
            }
            
            $fileIds = [];
            if (is_array($value)) {
                $fileIds = array_map('intval', $value);
            } else {
                $fileIds[] = intval($value);
            }
            
            // Проверяем, есть ли новый файл для этого fileKey
            $hasNewFile = false;
            foreach ($input as $newKey => $newValue) {
                if (strpos($newKey, 'new_file_') === 0) {
                    $newFileKey = substr($newKey, 9);
                    if ($newFileKey === $fileKey) {
                        $hasNewFile = true;
                        break;
                    }
                }
            }
            
            // Если нет нового файла, сохраняем существующий
            if (!$hasNewFile) {
                if ($fileKey === 'product_photo') {
                    // Основной продукт
                    if ($mainProductId) {
                        foreach ($fileIds as $fileId) {
                            if ($fileId > 0) {
                                // Обновляем product_id для файла основного продукта
                                $query = "UPDATE files SET is_temporary = 0, product_id = ? WHERE id = ? AND user_id = ?";
                                $stmt = mysqli_prepare($link, $query);
                                mysqli_stmt_bind_param($stmt, 'iii', $mainProductId, $fileId, $userId);
                                mysqli_stmt_execute($stmt);
                                mysqli_stmt_close($stmt);
                            }
                        }
                    } else {
                        // Если mainProductId еще не определен, просто помечаем файл как постоянный
                        foreach ($fileIds as $fileId) {
                            if ($fileId > 0) {
                                $query = "UPDATE files SET is_temporary = 0 WHERE id = ? AND user_id = ?";
                                $stmt = mysqli_prepare($link, $query);
                                mysqli_stmt_bind_param($stmt, 'ii', $fileId, $userId);
                                mysqli_stmt_execute($stmt);
                                mysqli_stmt_close($stmt);
                            }
                        }
                    }
                } elseif (strpos($fileKey, 'product_photo_sec_') === 0) {
                    // Вторичный продукт
                    $productIdStr = substr($fileKey, 18); // 'product_photo_sec_' = 18 символов
                    $productId = intval($productIdStr);
                    
                    if ($productId > 0) {
                        // Проверяем, что продукт существует и принадлежит пользователю
                        $query = "SELECT id FROM products WHERE id = ? AND user_id = ? AND is_main = 0";
                        $stmt = mysqli_prepare($link, $query);
                        mysqli_stmt_bind_param($stmt, 'ii', $productId, $userId);
                        mysqli_stmt_execute($stmt);
                        $result = mysqli_stmt_get_result($stmt);
                        $product = mysqli_fetch_assoc($result);
                        mysqli_stmt_close($stmt);
                        
                        if ($product) {
                            foreach ($fileIds as $fileId) {
                                if ($fileId > 0) {
                                    $query = "UPDATE files SET is_temporary = 0, product_id = ? WHERE id = ? AND user_id = ?";
                                    $stmt = mysqli_prepare($link, $query);
                                    mysqli_stmt_bind_param($stmt, 'iii', $productId, $fileId, $userId);
                                    mysqli_stmt_execute($stmt);
                                    mysqli_stmt_close($stmt);
                                }
                            }
                        }
                    }
                } else {
                    // Остальные типы файлов (logo, process_photo, digital_catalog, institutional_video)
                    foreach ($fileIds as $fileId) {
                        if ($fileId > 0) {
                            $query = "UPDATE files SET is_temporary = 0 WHERE id = ? AND user_id = ?";
                            $stmt = mysqli_prepare($link, $query);
                            mysqli_stmt_bind_param($stmt, 'ii', $fileId, $userId);
                            mysqli_stmt_execute($stmt);
                            mysqli_stmt_close($stmt);
                        }
                    }
                }
            }
        }
    }
    
    $query = "SELECT id FROM files WHERE user_id = ? AND is_temporary = 1";
    $stmt = mysqli_prepare($link, $query);
    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $temporaryFileIds = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $temporaryFileIds[] = $row['id'];
    }
    mysqli_stmt_close($stmt);
    
    foreach ($temporaryFileIds as $fileId) {
        try {
            $fileManager->delete($fileId, $userId);
        } catch (Exception $e) {
            error_log("Error deleting temporary file {$fileId}: " . $e->getMessage());
        }
    }
    
    mysqli_commit($link);
    
    $return['ok'] = 1;
    $return['res'] = 'Datos guardados correctamente';
    $return['company_id'] = $companyId;
    
} catch (Exception $e) {
    mysqli_rollback($link);
    $return['err'] = 'Error al guardar: ' . $e->getMessage();
    error_log("Error in regfull_js.php: " . $e->getMessage());
}

if (ob_get_level()) {
    ob_clean();
}
header('Content-Type: application/json');
echo json_encode($return);
?>
