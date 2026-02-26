<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

ob_start();

include __DIR__ . '/functions.php';
DBconnect();

$return = ['ok' => 0, 'err' => '', 'res' => ''];

$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);
$token = isset($input['token']) ? trim((string) $input['token']) : '';
$password = isset($input['password']) ? $input['password'] : '';
$passwordConfirm = isset($input['password_confirm']) ? $input['password_confirm'] : '';

if ($token === '') {
    $return['err'] = 'Enlace inválido o expirado. Solicite uno nuevo.';
    if (ob_get_level()) ob_clean();
    echo json_encode($return);
    exit;
}

if ($password === '' || strlen($password) < 6) {
    $return['err'] = 'La contraseña debe tener al menos 6 caracteres.';
    if (ob_get_level()) ob_clean();
    echo json_encode($return);
    exit;
}

if ($password !== $passwordConfirm) {
    $return['err'] = 'Las contraseñas no coinciden.';
    if (ob_get_level()) ob_clean();
    echo json_encode($return);
    exit;
}

global $link;
$now = time();

$stmt = mysqli_prepare($link, "
    SELECT prt.user_id FROM password_reset_tokens prt
    WHERE prt.token = ? AND prt.expires_at > ?
    LIMIT 1
");
mysqli_stmt_bind_param($stmt, 'si', $token, $now);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$row) {
    $return['err'] = 'Enlace inválido o expirado. Solicite uno nuevo desde la página de inicio de sesión.';
    if (ob_get_level()) ob_clean();
    echo json_encode($return);
    exit;
}

$userId = (int) $row['user_id'];

mysqli_begin_transaction($link);
$upd = mysqli_prepare($link, "UPDATE users SET password = ?, updated_at = UNIX_TIMESTAMP() WHERE id = ?");
mysqli_stmt_bind_param($upd, 'si', $password, $userId);
$ok = mysqli_stmt_execute($upd);
mysqli_stmt_close($upd);

if (!$ok) {
    mysqli_rollback($link);
    $return['err'] = 'Error al guardar la contraseña. Intente de nuevo.';
    if (ob_get_level()) ob_clean();
    echo json_encode($return);
    exit;
}

$del = mysqli_prepare($link, "DELETE FROM password_reset_tokens WHERE user_id = ?");
mysqli_stmt_bind_param($del, 'i', $userId);
mysqli_stmt_execute($del);
mysqli_stmt_close($del);
mysqli_commit($link);

$return['ok'] = 1;
$return['res'] = 'Contraseña actualizada. Ya puede iniciar sesión.';
if (ob_get_level()) ob_clean();
echo json_encode($return);
