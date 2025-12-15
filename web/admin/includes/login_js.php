<?
session_start();
date_default_timezone_set('America/Argentina/Buenos_Aires');
set_time_limit(0);
error_reporting(E_ALL);
ob_implicit_flush();

include "../../includes/functions.php";
DBconnect();

$return = [];
$res = '';
$ok = 0;
$err = '';

$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);

if (!$input) {
    echo json_encode([
        'res' => '',
        'ok' => 0,
        'err' => "Not valid JSON",
        'server' => $inputJSON
    ]);
    exit;
}

if (!isset($input['login'], $input['pass']) || empty($input['login']) || empty($input['pass'])) {
    echo json_encode([
        'res' => $res,
        'ok' => 0,
        'err' => "Required fields are missing",
        'server' => $input
    ]);
    exit;
}

$login = $input['login'];
$pass = $input['pass'];

$query = "SELECT id, login FROM admins WHERE login = ? AND password = ?";
$stmt = mysqli_prepare($link, $query);
mysqli_stmt_bind_param($stmt, 'ss', $login, $pass);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($row = mysqli_fetch_assoc($result)) {
    $_SESSION['admid'] = $row['id'];
    $ok = 1;
} else {
    $err = "Not valid login o password";
}

$return = [
    'res' => $res,
    'ok' => $ok,
    'err' => $err,
    'server' => $input
];

echo json_encode($return);
?>
