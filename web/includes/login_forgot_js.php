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
$email = isset($input['email']) ? trim((string) $input['email']) : '';

if ($email === '') {
    $return['err'] = 'Ingrese su correo electrónico';
    if (ob_get_level()) ob_clean();
    echo json_encode($return);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $return['err'] = 'El formato del correo electrónico no es válido';
    if (ob_get_level()) ob_clean();
    echo json_encode($return);
    exit;
}

global $link;

// Siempre devolvemos el mismo mensaje para no revelar si el email existe
$successMessage = 'Si este correo está registrado, recibirá un enlace para restablecer la contraseña. Revise su bandeja de entrada y la carpeta de spam.';

$stmt = mysqli_prepare($link, "SELECT id FROM users WHERE email = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, 's', $email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$user) {
    $return['ok'] = 1;
    $return['res'] = $successMessage;
    if (ob_get_level()) ob_clean();
    echo json_encode($return);
    exit;
}

$userId = (int) $user['id'];
$token = bin2hex(random_bytes(32));
$expiresAt = time() + 3600; // 1 hora

// Eliminar tokens antiguos de este usuario
$del = mysqli_prepare($link, "DELETE FROM password_reset_tokens WHERE user_id = ?");
mysqli_stmt_bind_param($del, 'i', $userId);
mysqli_stmt_execute($del);
mysqli_stmt_close($del);

$ins = mysqli_prepare($link, "INSERT INTO password_reset_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
mysqli_stmt_bind_param($ins, 'isi', $userId, $token, $expiresAt);
if (!mysqli_stmt_execute($ins)) {
    $return['ok'] = 1;
    $return['res'] = $successMessage;
    if (ob_get_level()) ob_clean();
    echo json_encode($return);
    exit;
}
mysqli_stmt_close($ins);

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$path = dirname($_SERVER['SCRIPT_NAME'] ?? '/');
$path = rtrim(str_replace('/includes', '', $path), '/') ?: '';
$resetLink = $scheme . '://' . $host . $path . '/index.php?page=reset_password&token=' . urlencode($token);

$subject = 'Restablecer contraseña';
$body = "Hola,\n\nSolicitó restablecer su contraseña. Use el siguiente enlace (válido 1 hora):\n\n" . $resetLink . "\n\nSi no solicitó este correo, ignore este mensaje.\n";

$sent = @mail($email, $subject, $body, 'From: noreply@' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "\r\nContent-Type: text/plain; charset=UTF-8\r\n");

$return['ok'] = 1;
$return['res'] = $successMessage;
if (ob_get_level()) ob_clean();
echo json_encode($return);
