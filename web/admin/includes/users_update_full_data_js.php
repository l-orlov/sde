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
    
    // 2. Обновление основного продукта
    if (isset($input['main_product'])) {
        $mainProduct = $input['main_product'];
        
        // Загружаем текущие данные основного продукта из БД
        $query = "SELECT id, name, tariff_code, description, volume_unit, volume_amount, annual_export, certifications 
                  FROM products WHERE user_id = ? AND is_main = 1 LIMIT 1";
        $stmt = mysqli_prepare($link, $query);
        mysqli_stmt_bind_param($stmt, 'i', $userId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $currentMainProduct = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        
        $mainProductId = $currentMainProduct ? intval($currentMainProduct['id']) : null;
        
        // Объединяем текущие данные с изменениями из формы
        $mainName = isset($mainProduct['name']) && $mainProduct['name'] !== '' ? htmlspecialchars(trim($mainProduct['name'])) : ($currentMainProduct['name'] ?? '');
        $mainTariffCode = isset($mainProduct['tariff_code']) && $mainProduct['tariff_code'] !== '' ? htmlspecialchars(trim($mainProduct['tariff_code'])) : ($currentMainProduct['tariff_code'] ?? '');
        $mainDescription = isset($mainProduct['description']) && $mainProduct['description'] !== '' ? htmlspecialchars(trim($mainProduct['description'])) : ($currentMainProduct['description'] ?? '');
        $mainVolumeUnit = isset($mainProduct['volume_unit']) && $mainProduct['volume_unit'] !== '' ? htmlspecialchars(trim($mainProduct['volume_unit'])) : ($currentMainProduct['volume_unit'] ?? '');
        $mainVolumeAmount = isset($mainProduct['volume_amount']) && $mainProduct['volume_amount'] !== '' ? htmlspecialchars(trim($mainProduct['volume_amount'])) : ($currentMainProduct['volume_amount'] ?? '');
        $mainAnnualExport = isset($mainProduct['annual_export']) && $mainProduct['annual_export'] !== '' ? htmlspecialchars(trim($mainProduct['annual_export'])) : ($currentMainProduct['annual_export'] ?? '');
        $mainCertifications = isset($mainProduct['certifications']) && $mainProduct['certifications'] !== '' ? htmlspecialchars(trim($mainProduct['certifications'])) : ($currentMainProduct['certifications'] ?? '');
        
        if ($mainProductId) {
            // Обновляем существующий
            $query = "UPDATE products SET name = ?, tariff_code = ?, description = ?, volume_unit = ?, 
                      volume_amount = ?, annual_export = ?, certifications = ?, updated_at = UNIX_TIMESTAMP()
                      WHERE id = ?";
            $stmt = mysqli_prepare($link, $query);
            mysqli_stmt_bind_param($stmt, 'sssssssi', $mainName, $mainTariffCode, $mainDescription, 
                                  $mainVolumeUnit, $mainVolumeAmount, $mainAnnualExport, $mainCertifications, $mainProductId);
        } else {
            // Создаем новый
            $query = "INSERT INTO products (company_id, user_id, is_main, name, tariff_code, description, 
                      volume_unit, volume_amount, annual_export, certifications) 
                      VALUES (?, ?, 1, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($link, $query);
            mysqli_stmt_bind_param($stmt, 'iissssss', $companyId, $userId, $mainName, $mainTariffCode, 
                                  $mainDescription, $mainVolumeUnit, $mainVolumeAmount, $mainAnnualExport, $mainCertifications);
        }
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Error al guardar producto principal: " . mysqli_error($link));
        }
        mysqli_stmt_close($stmt);
    }
    
    // 3. Обновление вторичных продуктов
    // Сначала загружаем все текущие вторичные продукты из БД
    $query = "SELECT id, name, tariff_code, description, volume_unit, volume_amount, annual_export 
              FROM products WHERE user_id = ? AND is_main = 0 ORDER BY id";
    $stmt = mysqli_prepare($link, $query);
    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $currentSecondaryProducts = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $currentSecondaryProducts[$row['id']] = $row;
    }
    mysqli_stmt_close($stmt);
    
    if (isset($input['secondary_products']) && is_array($input['secondary_products'])) {
        foreach ($input['secondary_products'] as $secProduct) {
            $secId = isset($secProduct['id']) && $secProduct['id'] ? intval($secProduct['id']) : null;
            
            // Объединяем текущие данные с изменениями
            $currentSec = $secId && isset($currentSecondaryProducts[$secId]) ? $currentSecondaryProducts[$secId] : null;
            
            $secName = isset($secProduct['name']) && $secProduct['name'] !== '' ? htmlspecialchars(trim($secProduct['name'])) : ($currentSec['name'] ?? '');
            $secTariffCode = isset($secProduct['tariff_code']) && $secProduct['tariff_code'] !== '' ? htmlspecialchars(trim($secProduct['tariff_code'])) : ($currentSec['tariff_code'] ?? '');
            $secDescription = isset($secProduct['description']) && $secProduct['description'] !== '' ? htmlspecialchars(trim($secProduct['description'])) : ($currentSec['description'] ?? '');
            $secVolumeUnit = isset($secProduct['volume_unit']) && $secProduct['volume_unit'] !== '' ? htmlspecialchars(trim($secProduct['volume_unit'])) : ($currentSec['volume_unit'] ?? '');
            $secVolumeAmount = isset($secProduct['volume_amount']) && $secProduct['volume_amount'] !== '' ? htmlspecialchars(trim($secProduct['volume_amount'])) : ($currentSec['volume_amount'] ?? '');
            $secAnnualExport = isset($secProduct['annual_export']) && $secProduct['annual_export'] !== '' ? htmlspecialchars(trim($secProduct['annual_export'])) : ($currentSec['annual_export'] ?? '');
            
            if ($secId && $secId > 0) {
                // Обновляем существующий
                $query = "UPDATE products SET name = ?, tariff_code = ?, description = ?, volume_unit = ?, 
                          volume_amount = ?, annual_export = ?, updated_at = UNIX_TIMESTAMP()
                          WHERE id = ? AND user_id = ? AND is_main = 0";
                $stmt = mysqli_prepare($link, $query);
                mysqli_stmt_bind_param($stmt, 'ssssssii', $secName, $secTariffCode, $secDescription, 
                                      $secVolumeUnit, $secVolumeAmount, $secAnnualExport, $secId, $userId);
            } else if ($secName) {
                // Создаем новый
                $query = "INSERT INTO products (company_id, user_id, is_main, name, tariff_code, description, 
                          volume_unit, volume_amount, annual_export) 
                          VALUES (?, ?, 0, ?, ?, ?, ?, ?, ?)";
                $stmt = mysqli_prepare($link, $query);
                mysqli_stmt_bind_param($stmt, 'iissssss', $companyId, $userId, $secName, $secTariffCode, 
                                      $secDescription, $secVolumeUnit, $secVolumeAmount, $secAnnualExport);
            } else {
                continue; // Пропускаем пустые
            }
            
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("Error al guardar producto secundario: " . mysqli_error($link));
            }
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

