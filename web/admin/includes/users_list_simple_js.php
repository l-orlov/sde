<?
session_start();
date_default_timezone_set('America/Argentina/Buenos_Aires');
set_time_limit (0);
error_reporting(E_ALL);
ob_implicit_flush();

include __DIR__ . '/path_helper.php';
include getIncludesFilePath('functions.php');
DBconnect();

$basePath = getAdminBasePath();

$ok=0;
$res='';
$cant=0;

$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);

if (!$input) {
    echo json_encode([
        'res' => '',
        'ok' => 0,
        'cant' => 0,
        'err' => "JSON no válido",
        'server_data' => $inputJSON
    ]);
    exit;
}

$page = isset($input['pg']) 	? intval($input['pg']) : 0;
$busc = isset($input['busc'])	? trim($input['busc']) : '';

$res .= '
	<div class="adm_zag">ID</div>
	<div class="adm_zag">Nombre de la Empresa</div>
	<div class="adm_zag"></div>
';

// Убираем пагинацию - загружаем всех пользователей
// Используем companies.name если есть, иначе users.company_name
$query="SELECT u.id, COALESCE(c.name, u.company_name) as company_name 
        FROM users u 
        LEFT JOIN companies c ON c.user_id = u.id";

if ( strlen($busc) > 0 ) {
	$buscEscaped = mysqli_real_escape_string($link, $busc);
	$query .= " WHERE 
			u.id				LIKE '%".$buscEscaped."%' OR 
			u.company_name		LIKE '%".$buscEscaped."%' OR
			c.name				LIKE '%".$buscEscaped."%'";
}

$query .= " ORDER BY u.id DESC";
$result = mysqli_query($link, $query);

if (!$result) {
    echo json_encode([
        'res' => '',
        'ok' => 0,
        'cant' => 0,
        'err' => "Error SQL: " . mysqli_error($link),
    ]);
    exit;
}

while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
	$cant++;
	$displayName = $row['company_name'] ? htmlspecialchars($row['company_name']) : '(Sin nombre)';
	$res .= '
		<div class="adm_list_txt user-row" id="user_row_'.$row['id'].'" onclick="selectUser('.$row['id'].')" style="cursor: pointer;">
			'.$row['id'].'
		</div>
		<div class="adm_list_txt user-row" id="user_row_name_'.$row['id'].'" onclick="selectUser('.$row['id'].')" style="cursor: pointer;">
			'.$displayName.'
		</div>
		<div class="adm_list_txt pad user-row" id="user_row_del_'.$row['id'].'" onclick="event.stopPropagation(); user_del('.$row['id'].')">
			<img src="'.$basePath.'img/trash.png" class="edit-icon-size">
		</div>
	';
}

echo json_encode([
    "res" => $res,
    "ok" => 1,
    "cant" => $cant,
]);
?>

