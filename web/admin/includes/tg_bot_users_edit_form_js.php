<?
session_start();
date_default_timezone_set('America/Argentina/Buenos_Aires');
set_time_limit (0);
error_reporting(E_ALL);
ob_implicit_flush();

include "../../includes/functions.php";
DBconnect();

$ok = 0;
$res = '';
$err = '';

$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);

$id = isset($input['id']) ? intval($input['id']) : 0;

if ($id <= 0) {
    echo json_encode(['ok' => 0, 'err' => 'Not valid ID']);
    exit;
}

$query = "SELECT * FROM tg_bot_users WHERE id = ?";
$stmt = mysqli_prepare($link, $query);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (!$result || mysqli_num_rows($result) == 0) {
    echo json_encode(['ok' => 0, 'err' => 'User not found']);
    exit;
}

$row = mysqli_fetch_assoc($result);

$res .= '

    <div class="adm_list_txt">Username:</div>
    <div class="adm_list_txt"><input class="edit_input" type="text" id="username' . $row['id'] . '" value="' . htmlspecialchars($row['username'] ?? '') . '"></div>

    <div class="adm_list_txt">First name:</div>
    <div class="adm_list_txt"><input class="edit_input" type="text" id="firstname' . $row['id'] . '" value="' . htmlspecialchars($row['first_name'] ?? '') . '"></div>

    <div class="adm_list_txt">Last name:</div>
    <div class="adm_list_txt"><input class="edit_input" type="text" id="lastname' . $row['id'] . '" value="' . htmlspecialchars($row['last_name'] ?? '') . '"></div>
    
    <div style="grid-column: 1/-1; text-align:center; margin-top: 5px; ">
        <img src="img/save.png"width="30" height="30" onclick="user_edit_save(' . $row['id'] . ')" style="cursor: pointer;">
    </div>';

echo json_encode([
    'res' => $res,
    'ok' => 1
]);

?>
