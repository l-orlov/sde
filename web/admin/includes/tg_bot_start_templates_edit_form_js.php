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

$query = "SELECT * FROM tg_bot_start_templates WHERE id = ?";
$stmt = mysqli_prepare($link, $query);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (!$result || mysqli_num_rows($result) == 0) {
    echo json_encode(['ok' => 0, 'err' => 'Admin not found']);
    exit;
}

$row = mysqli_fetch_assoc($result);
$res .= '
    <div class="adm_list_txt">Parameter: </div>
    <div class="adm_list_txt"><input class="edit_input" type="text" id="parameter' . $row['id'] . '" value="' . htmlspecialchars($row['parameter'] ?? '') . '"></div>

    <div class="adm_list_txt">Template: </div>
    <div class="adm_list_txt"><input class="edit_input" type="text" id="template' . $row['id'] . '" value="' . htmlspecialchars($row['template'] ?? '') . '"></div>

    <div style="grid-column: 1/-1; text-align:center; margin-top: 5px; ">
        <img src="img/save.png"width="30" height="30" onclick="template_edit_save(' . $row['id'] . ')" style="cursor: pointer;">
    </div>';

echo json_encode([
    'res' => $res,
    'ok' => 1
]);

?>
