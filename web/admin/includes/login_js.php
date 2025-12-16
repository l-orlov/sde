<?
ob_start();
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

session_start();
date_default_timezone_set('America/Argentina/Buenos_Aires');
set_time_limit(0);

include __DIR__ . '/path_helper.php';
include getIncludesFilePath('functions.php');
DBconnect();

$return = [];
$res = '';
$ok = 0;
$err = '';

$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);

if (!$input) {
    if (ob_get_level()) ob_clean();
    header('Content-Type: application/json');
    echo json_encode([
        'res' => '',
        'ok' => 0,
        'err' => "JSON no válido",
        'server' => $inputJSON
    ]);
    exit;
}

if (!isset($input['login'], $input['pass']) || empty($input['login']) || empty($input['pass'])) {
    if (ob_get_level()) ob_clean();
    header('Content-Type: application/json');
    echo json_encode([
        'res' => $res,
        'ok' => 0,
        'err' => "Faltan campos obligatorios",
        'server' => $input
    ]);
    exit;
}

$login = $input['login'];
$pass = $input['pass'];

// Проверяем пользователя в таблице users с is_admin = 1
// login может быть email или tax_id
$query = "SELECT id, email, tax_id FROM users WHERE (email = ? OR tax_id = ?) AND password = ? AND is_admin = 1";
$stmt = mysqli_prepare($link, $query);
mysqli_stmt_bind_param($stmt, 'sss', $login, $login, $pass);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($row = mysqli_fetch_assoc($result)) {
    $_SESSION['admid'] = $row['id'];
    $ok = 1;
} else {
    $err = "Usuario o contraseña no válidos o no tiene permisos de administrador";
}

$return = [
    'res' => $res,
    'ok' => $ok,
    'err' => $err,
    'server' => $input
];

ob_clean();
header('Content-Type: application/json');
echo json_encode($return);
?>
