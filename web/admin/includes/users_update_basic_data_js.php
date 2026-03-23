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

try {
    // Загружаем текущие данные пользователя
    $query = "SELECT email, phone, is_admin, include_in_business_exports FROM users WHERE id = ? LIMIT 1";
    $stmt = mysqli_prepare($link, $query);
    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $currentUser = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    if (!$currentUser) {
        throw new Exception("Usuario no encontrado");
    }
    
    // Объединяем с текущими данными (умная логика)
    $userEmail = isset($input['user_email']) && $input['user_email'] !== '' ? htmlspecialchars(trim($input['user_email'])) : $currentUser['email'];
    $userPhone = isset($input['user_phone']) && $input['user_phone'] !== '' ? htmlspecialchars(trim($input['user_phone'])) : $currentUser['phone'];
    $userIsAdmin = isset($input['user_is_admin']) ? intval($input['user_is_admin']) : intval($currentUser['is_admin']);
    $userIncludeExports = isset($input['user_include_in_business_exports'])
        ? intval($input['user_include_in_business_exports'])
        : intval($currentUser['include_in_business_exports'] ?? 1);
    $userIncludeExports = $userIncludeExports ? 1 : 0;
    
    // Обновляем данные пользователя
    $query = "UPDATE users SET email = ?, phone = ?, is_admin = ?, include_in_business_exports = ?, updated_at = UNIX_TIMESTAMP() WHERE id = ?";
    $stmt = mysqli_prepare($link, $query);
    mysqli_stmt_bind_param($stmt, 'ssiii', $userEmail, $userPhone, $userIsAdmin, $userIncludeExports, $userId);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Error al actualizar datos del usuario: " . mysqli_error($link));
    }
    mysqli_stmt_close($stmt);
    
    $return['ok'] = 1;
    $return['res'] = 'Datos guardados correctamente';
    
} catch (Exception $e) {
    $return['err'] = $e->getMessage();
}

header('Content-Type: application/json');
echo json_encode($return);
?>

