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

if (!$adminData || (intval($adminData['is_admin'] ?? 0) !== 1)) {
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
    $query = "UPDATE companies SET
              moderation_status = 'pending',
              moderation_date = NULL,
              moderated_by = NULL
              WHERE user_id = ?";
    $stmt = mysqli_prepare($link, $query);
    if (!$stmt) {
        throw new Exception("Error al preparar consulta: " . mysqli_error($link));
    }
    mysqli_stmt_bind_param($stmt, 'i', $userId);
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Error al actualizar estado de moderación: " . mysqli_error($link));
    }
    mysqli_stmt_close($stmt);

    if (ob_get_level() > 0) ob_clean();
    header('Content-Type: application/json');
    $return['ok'] = 1;
    $return['res'] = 'Aprobación revocada. La empresa vuelve a estado "En moderación".';
    echo json_encode($return);

} catch (Exception $e) {
    if (ob_get_level() > 0) ob_clean();
    header('Content-Type: application/json');
    $return['err'] = $e->getMessage();
    echo json_encode($return);
}
?>
