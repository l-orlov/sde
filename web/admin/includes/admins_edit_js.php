<?
session_start();
date_default_timezone_set('America/Argentina/Buenos_Aires');
set_time_limit (0);
error_reporting(E_ALL);
ob_implicit_flush();

include "../../includes/functions.php";
DBconnect();

$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);

$id = isset($input['id']) ? intval($input['id']) : 0;

if ($id <= 0) {
    echo json_encode(['ok' => 0, 'err' => 'Not valid ID']);
    exit;
}

$login =    isset($input['login'])      ? mysqli_real_escape_string($link, $input['login'])     : '';
$password = isset($input['password'])   ? mysqli_real_escape_string($link, $input['password'])  : '';

$query = "UPDATE admins SET 
    login = ?,
    password = ?
WHERE id = ?";

$stmt = mysqli_prepare($link, $query);
mysqli_stmt_bind_param($stmt, "ssi", 
    $login, $password, $id
);
$success = mysqli_stmt_execute($stmt);

if ($success) {
    echo json_encode(['ok' => 1]);
} else {
    echo json_encode(['ok' => 0, 'err' => mysqli_error($link)]);
}

?>