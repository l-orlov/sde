<?
session_start();
date_default_timezone_set('America/Argentina/Buenos_Aires');
set_time_limit (0);
error_reporting(E_ALL);
ob_implicit_flush();

include __DIR__ . '/path_helper.php';
include getIncludesFilePath('functions.php');
DBconnect();

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

$res .= '<div class="adm_busc">
	<input class="adm_busc_input" type="Text" id="busc_texto" value="'.$busc.'">
	<div class="adm_busc_bt" onclick="user_list_by_filter();">Buscar</div>
</div>';

$res .= '
	<div class="adm_zag">ID</div>
	<div class="adm_zag">Nombre de la Empresa</div>
	<div class="adm_zag">CUIL/CUIT</div>
	<div class="adm_zag">Correo electrónico</div>
	<div class="adm_zag">Teléfono</div>
	<div class="adm_zag">Es Admin</div>
	<div class="adm_zag">Creado el</div>
	<div class="adm_zag">Actualizado el</div>
	<div class="adm_zag"></div>
	<div class="adm_zag"></div>
';

$pger = $page;
$c = 250;
$pger *= $c;

$query="SELECT * FROM users";

if ( strlen($busc) > 0 ) {
	$query .= " WHERE 
			id				LIKE '%".mysqli_real_escape_string($link, $busc)."%' OR 
			company_name	LIKE '%".mysqli_real_escape_string($link, $busc)."%' OR 
			tax_id			LIKE '%".mysqli_real_escape_string($link, $busc)."%' OR 
			email			LIKE '%".mysqli_real_escape_string($link, $busc)."%' OR 
			phone			LIKE '%".mysqli_real_escape_string($link, $busc)."%'";
}

$query .= " ORDER BY id DESC LIMIT ".$pger.", ".$c."";
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
	$created_at_human = date('Y-m-d H:i', $row['created_at']);
	$updated_at_human = date('Y-m-d H:i', $row['updated_at']);

	$is_admin_text = ($row['is_admin'] == 1) ? 'Sí' : 'No';
	$cant++;
	$res .= '
		<div class="adm_list_txt" id="c0_'.$row['id'].'">'.$row['id'].'</div>
		<div class="adm_list_txt" id="c1_'.$row['id'].'">'.htmlspecialchars($row['company_name']).'</div>
		<div class="adm_list_txt" id="c2_'.$row['id'].'">'.htmlspecialchars($row['tax_id']).'</div>
		<div class="adm_list_txt" id="c3_'.$row['id'].'">'.htmlspecialchars($row['email']).'</div>
		<div class="adm_list_txt" id="c4_'.$row['id'].'">'.htmlspecialchars($row['phone']).'</div>
		<div class="adm_list_txt" id="c5_'.$row['id'].'">'.$is_admin_text.'</div>
		<div class="adm_list_txt" id="c6_'.$row['id'].'">'.$created_at_human.'</div>
		<div class="adm_list_txt" id="c7_'.$row['id'].'">'.$updated_at_human.'</div>
		<div class="adm_list_txt pad" id="c8_'.$row['id'].'">
			<img id="edit_icon_'.$row['id'].'" onclick="user_get_edit_form('.$row['id'].')" src="img/edit.png" class="edit-icon-size">
		</div>
		<div class="adm_list_txt pad" id="c9_'.$row['id'].'">
			<img onclick="user_del('.$row['id'].')" src="img/trash.png" class="edit-icon-size">
		</div>
		<div class="adm_list_edit_box" id="adm_list_edit_box'.$row['id'].'"></div>
	';
}

echo json_encode([
    "res" => $res,
    "ok" => 1,
    "cant" => $cant,
]);
?>

