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
	<div class="adm_zag">№</div>
	<div class="adm_zag">Nombre de la Empresa</div>
	<div class="adm_zag"></div>
	<div class="adm_zag"></div>
';

// Убираем пагинацию - загружаем всех пользователей
// Используем companies.name если есть, иначе users.company_name
// Статус: нет компании (c.id IS NULL) = серый; компания pending = красный; approved = зеленый
$query="SELECT u.id, COALESCE(c.name, u.company_name) as company_name, 
               c.id as company_id,
               COALESCE(c.moderation_status, 'pending') as moderation_status
        FROM users u 
        LEFT JOIN companies c ON c.user_id = u.id";

if ( strlen($busc) > 0 ) {
	$buscEscaped = mysqli_real_escape_string($link, $busc);
	$query .= " WHERE 
			u.id				LIKE '%".$buscEscaped."%' OR 
			u.company_name		LIKE '%".$buscEscaped."%' OR
			c.name				LIKE '%".$buscEscaped."%'";
}

// Сортировка: красный (pending) сверху, серый (sin datos) по середине, зеленый (approved) внизу
$query .= " ORDER BY 
            CASE 
                WHEN c.id IS NULL THEN 1 
                WHEN COALESCE(c.moderation_status, 'pending') = 'pending' THEN 0 
                ELSE 2 
            END,
            u.id DESC";
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

$num = 0;
while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
	$num++;
	$cant++;
	$rawName = $row['company_name'] ? $row['company_name'] : '';
	$nameLen = function_exists('mb_strlen') ? mb_strlen($rawName) : strlen($rawName);
	$displayName = $rawName ? htmlspecialchars(($nameLen > 16) ? (function_exists('mb_substr') ? mb_substr($rawName, 0, 16) : substr($rawName, 0, 16)) . '…' : $rawName) : '(Sin nombre)';
	$displayTitle = $rawName ? htmlspecialchars($rawName) : '';
	$hasCompany = !empty($row['company_id']);
	$moderationStatus = $row['moderation_status'] ?? 'pending';
	// Серый = зарегался, но не заполнил большую форму; красный = данные не подтверждены; зеленый = подтверждены
	if (!$hasCompany) {
		$statusColor = '#9e9e9e';
		$statusTitle = 'Sin datos completos (no completó el formulario)';
	} elseif ($moderationStatus === 'approved') {
		$statusColor = '#4CAF50';
		$statusTitle = 'Aprobado';
	} else {
		$statusColor = '#f44336';
		$statusTitle = 'En moderación';
	}

	$res .= '
		<div class="adm_list_txt user-row" id="user_row_'.$row['id'].'" onclick="selectUser('.$row['id'].')" style="cursor: pointer;">
			'.$num.'
		</div>
		<div class="adm_list_txt user-row" id="user_row_name_'.$row['id'].'" onclick="selectUser('.$row['id'].')" style="cursor: pointer;"'.($displayTitle ? ' title="'.$displayTitle.'"' : '').'>
			'.$displayName.'
		</div>
		<div class="adm_list_txt pad user-row" id="user_row_status_'.$row['id'].'" style="display: flex; align-items: center; justify-content: center;">
			<div class="moderation-status-indicator" style="width: 14px; height: 14px; background-color: '.$statusColor.'; border-radius: 2px;" title="'.$statusTitle.'"></div>
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

