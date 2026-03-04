<?php
/**
 * Проверка логина/пароля заглушки для доступа к публичным страницам.
 * Логин: adminsantiago, пароль: sde12345
 */
if (ob_get_length()) ob_clean();
header('Content-Type: application/json; charset=utf-8');

$input = file_get_contents('php://input');
$data = $input ? json_decode($input, true) : null;
$login = isset($data['login']) ? trim((string) $data['login']) : '';
$pass  = isset($data['pass']) ? trim((string) $data['pass']) : '';
$returnPage = isset($data['return']) ? trim((string) $data['return']) : 'landing';

$allowedPages = ['search', 'landing', 'regfull', 'regnew', 'login', 'reset_password'];
if ($returnPage === '' || !in_array($returnPage, $allowedPages, true)) {
    $returnPage = 'landing';
}

$validLogin = 'adminsantiago';
$validPass  = 'sde12345';

if ($login === $validLogin && $pass === $validPass) {
    // Автоматически: после успешного входа не спрашивать снова до конца сессии
    $_SESSION['gate_remember'] = true;
    echo json_encode(['ok' => 1, 'redirect' => 'index.php?page=' . $returnPage]);
} else {
    echo json_encode(['ok' => 0, 'err' => 'Usuario o contraseña incorrectos.']);
}
