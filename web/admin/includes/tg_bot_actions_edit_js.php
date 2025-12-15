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

$userId     =   isset($input['userId'])     ? mysqli_real_escape_string($link, $input['userId'])        : '';
$username   =   isset($input['username'])   ? mysqli_real_escape_string($link, $input['username'])      : '';
$status     =   isset($input['status'])     ? mysqli_real_escape_string($link, $input['status'])        : '';
$command    =   isset($input['command'])    ? mysqli_real_escape_string($link, $input['command'])       : '';
$parameter  =   isset($input['parameter'])  ? mysqli_real_escape_string($link, $input['parameter'])     : '';
$failReason =   isset($input['failReason']) ? mysqli_real_escape_string($link, $input['failReason'])    : '';

$query = "UPDATE tg_bot_actions SET 
    user_id = ?,
    username = ?,
    command = ?,
    parameter = ?,
    status = ?,
    fail_reason = ?,
    updated_at = UNIX_TIMESTAMP()
WHERE id = ?";

$stmt = mysqli_prepare($link, $query);
mysqli_stmt_bind_param($stmt, "isssssi", 
    $userId, $username, $command, $parameter, $status, $failReason, $id
);
$success = mysqli_stmt_execute($stmt);

if ($success) {
    $selectQuery = "SELECT id, user_id, username, command, parameter, status, fail_reason, created_at, updated_at FROM tg_bot_actions WHERE id = ?";
    $stmt = mysqli_prepare($link, $selectQuery);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $action = mysqli_fetch_assoc($result);

    $actionData = [
        'id' => $action['id'],
        'userId' => $action['user_id'],
        'username' => $action['username'],
        'command' => $action['command'],
        'parameter' => $action['parameter'],
        'status' => $action['status'],
        'failReason' => $action['fail_reason'],
        'created_at' => date('Y-m-d H:i', $action['created_at']),
        'updated_at' => date('Y-m-d H:i', $action['updated_at']),
    ];

    echo json_encode(['ok' => 1, 'action' => $actionData]);
} else {
    echo json_encode(['ok' => 0, 'err' => mysqli_error($link)]);
}

?>
