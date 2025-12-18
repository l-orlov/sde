<?
session_start();
date_default_timezone_set('America/Argentina/Buenos_Aires');
set_time_limit(0);
error_reporting(E_ALL);
ini_set('display_errors', 0);
ob_start();

include __DIR__ . '/path_helper.php';
include getIncludesFilePath('functions.php');

DBconnect();

$return = ['ok' => 0, 'err' => ''];

// Проверка авторизации и прав администратора
// В админке используется $_SESSION['admid'], а не $_SESSION['uid']
if (!isset($_SESSION['admid'])) {
    if (ob_get_level() > 0) ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['ok' => 0, 'err' => 'No autorizado. Sesión no encontrada.']);
    exit;
}

$adminId = intval($_SESSION['admid']);

if ($adminId <= 0) {
    if (ob_get_level() > 0) ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['ok' => 0, 'err' => 'No autorizado. ID de administrador inválido.']);
    exit;
}

// Проверка, что пользователь является администратором
$query = "SELECT is_admin FROM users WHERE id = ? LIMIT 1";
$stmt = mysqli_prepare($link, $query);
if (!$stmt) {
    if (ob_get_level() > 0) ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['ok' => 0, 'err' => 'Error de base de datos: ' . mysqli_error($link)]);
    exit;
}

mysqli_stmt_bind_param($stmt, 'i', $adminId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$adminData = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

// Проверка is_admin: может быть 1, '1', true - проверяем все варианты
// Если пользователь залогинен в админку, он уже проверен как админ в login_js.php
// Но на всякий случай проверяем is_admin
$isAdmin = true; // По умолчанию считаем админом, если залогинен в админку
if ($adminData && isset($adminData['is_admin'])) {
    $isAdminValue = $adminData['is_admin'];
    // Проверяем разные варианты: 1, '1', true
    $isAdmin = (intval($isAdminValue) === 1) || ($isAdminValue === '1') || ($isAdminValue === true);
}

if (!$adminData) {
    if (ob_get_level() > 0) ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['ok' => 0, 'err' => 'Usuario no encontrado en la base de datos.']);
    exit;
}

if (!$isAdmin) {
    if (ob_get_level() > 0) ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['ok' => 0, 'err' => 'Acceso denegado. Solo administradores.']);
    exit;
}

$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);

if (!$input || !isset($input['user_id'])) {
    if (ob_get_level() > 0) ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['ok' => 0, 'err' => 'user_id requerido']);
    exit;
}

$userId = intval($input['user_id']);

try {
    // Обновляем статус модерации
    $query = "UPDATE companies SET 
              moderation_status = 'approved',
              moderation_date = UNIX_TIMESTAMP(),
              moderated_by = ?
              WHERE user_id = ?";
    $stmt = mysqli_prepare($link, $query);
    mysqli_stmt_bind_param($stmt, 'ii', $adminId, $userId);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Error al actualizar estado de moderación: " . mysqli_error($link));
    }
    
    $affectedRows = mysqli_stmt_affected_rows($stmt);
    mysqli_stmt_close($stmt);
    
    if ($affectedRows === 0) {
        // Возможно, у пользователя еще нет записи в companies
        // Создаем запись с approved статусом
        $query = "INSERT INTO companies (user_id, name, tax_id, moderation_status, moderation_date, moderated_by)
                  SELECT id, company_name, tax_id, 'approved', UNIX_TIMESTAMP(), ?
                  FROM users WHERE id = ? AND NOT EXISTS (SELECT 1 FROM companies WHERE user_id = ?)";
        $stmt = mysqli_prepare($link, $query);
        mysqli_stmt_bind_param($stmt, 'iii', $adminId, $userId, $userId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
    
    if (ob_get_level() > 0) ob_clean();
    header('Content-Type: application/json');
    $return['ok'] = 1;
    $return['res'] = 'Datos aprobados correctamente';
    echo json_encode($return);
    
} catch (Exception $e) {
    if (ob_get_level() > 0) ob_clean();
    header('Content-Type: application/json');
    $return['err'] = $e->getMessage();
    echo json_encode($return);
} catch (Error $e) {
    if (ob_get_level() > 0) ob_clean();
    header('Content-Type: application/json');
    $return['err'] = 'Error fatal: ' . $e->getMessage();
    echo json_encode($return);
}
?>

