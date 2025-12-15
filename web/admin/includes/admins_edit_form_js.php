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
    echo json_encode(['ok' => 0, 'err' => 'ID no válido']);
    exit;
}

$query = "SELECT * FROM admins WHERE id = ?";
$stmt = mysqli_prepare($link, $query);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (!$result || mysqli_num_rows($result) == 0) {
    echo json_encode(['ok' => 0, 'err' => 'Administrador no encontrado']);
    exit;
}

$row = mysqli_fetch_assoc($result);
$res .= '
    <div class="adm_list_txt">Usuario: </div>
    <div class="adm_list_txt"><input class="edit_input" type="text" id="login' . $row['id'] . '" value="' . htmlspecialchars($row['login'] ?? '') . '"></div>

    <div class="adm_list_txt">Contraseña: </div>
    <div class="adm_list_txt"><input class="edit_input" type="text" id="password' . $row['id'] . '" value="' . htmlspecialchars($row['password'] ?? '') . '"></div>

    <div style="grid-column: 1/-1; text-align:center; margin-top: 5px; ">
        <img src="img/save.png"width="30" height="30" onclick="admin_edit_save(' . $row['id'] . ')" style="cursor: pointer;">
    </div>';

echo json_encode([
    'res' => $res,
    'ok' => 1
]);

?>
