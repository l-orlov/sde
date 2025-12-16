<?
session_start();
date_default_timezone_set('America/Argentina/Buenos_Aires');
set_time_limit (0);
error_reporting(E_ALL);
ob_implicit_flush();

include __DIR__ . '/path_helper.php';
include getIncludesFilePath('functions.php');
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

$query = "SELECT * FROM users WHERE id = ?";
$stmt = mysqli_prepare($link, $query);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (!$result || mysqli_num_rows($result) == 0) {
    echo json_encode(['ok' => 0, 'err' => 'Usuario no encontrado']);
    exit;
}

$row = mysqli_fetch_assoc($result);

$res .= '

    <div class="adm_list_txt">Nombre de la Empresa:</div>
    <div class="adm_list_txt"><input class="edit_input" type="text" id="company_name' . $row['id'] . '" value="' . htmlspecialchars($row['company_name'] ?? '') . '"></div>

    <div class="adm_list_txt">CUIL/CUIT:</div>
    <div class="adm_list_txt"><input class="edit_input" type="text" id="tax_id' . $row['id'] . '" value="' . htmlspecialchars($row['tax_id'] ?? '') . '"></div>

    <div class="adm_list_txt">Correo electrónico:</div>
    <div class="adm_list_txt"><input class="edit_input" type="text" id="email' . $row['id'] . '" value="' . htmlspecialchars($row['email'] ?? '') . '"></div>

    <div class="adm_list_txt">Teléfono:</div>
    <div class="adm_list_txt"><input class="edit_input" type="text" id="phone' . $row['id'] . '" value="' . htmlspecialchars($row['phone'] ?? '') . '"></div>

    <div class="adm_list_txt">Es Administrador:</div>
    <div class="adm_list_txt">
        <select class="edit_input" id="is_admin' . $row['id'] . '">
            <option value="0"' . (($row['is_admin'] ?? 0) == 0 ? ' selected' : '') . '>No</option>
            <option value="1"' . (($row['is_admin'] ?? 0) == 1 ? ' selected' : '') . '>Sí</option>
        </select>
    </div>
    
    <div style="grid-column: 1/-1; text-align:center; margin-top: 5px; ">
        <img src="img/save.png" width="30" height="30" onclick="user_edit_save(' . $row['id'] . ')" style="cursor: pointer;">
    </div>';

echo json_encode([
    'res' => $res,
    'ok' => 1
]);

?>

