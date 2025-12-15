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

$username =    isset($input['username'])      ? mysqli_real_escape_string($link, $input['username'])     : '';
$firstname = isset($input['firstname'])   ? mysqli_real_escape_string($link, $input['firstname'])  : '';
$lastname = isset($input['lastname'])   ? mysqli_real_escape_string($link, $input['lastname'])  : '';

$query = "UPDATE tg_bot_users SET 
    username = ?,
    first_name = ?,
    last_name = ?,
    updated_at = UNIX_TIMESTAMP()
WHERE id = ?";

$stmt = mysqli_prepare($link, $query);
mysqli_stmt_bind_param($stmt, "sssi", 
    $username, $firstname, $lastname, $id
);
$success = mysqli_stmt_execute($stmt);

if ($success) {
    $selectQuery = "SELECT id, username, first_name, last_name, created_at, updated_at FROM tg_bot_users WHERE id = ?";
    $stmt = mysqli_prepare($link, $selectQuery);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);

    $userData = [
        'id' => $user['id'],
        'username' => $user['username'],
        'first_name' => $user['first_name'],
        'last_name' => $user['last_name'],
        'created_at' => date('Y-m-d H:i', $user['created_at']),
        'updated_at' => date('Y-m-d H:i', $user['updated_at']),
    ];

    echo json_encode(['ok' => 1, 'user' => $userData]);
} else {
    echo json_encode(['ok' => 0, 'err' => mysqli_error($link)]);
}

?>