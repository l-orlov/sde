<?
session_start();
date_default_timezone_set('America/Argentina/Buenos_Aires');
set_time_limit (0);
error_reporting(E_ALL);
ob_implicit_flush();

include "../../includes/functions.php";
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

	<div class="adm_busc_bt" onclick="admin_list_by_filter();">Buscar</div>

</div>';



$res .= '
	<div class="adm_zag">ID</div>

	<div class="adm_zag">Usuario</div>

	<div class="adm_zag">Contraseña</div>

	<div class="adm_zag"></div>

	<div class="adm_zag"></div>
';

$pger = $page;
$c = 250;
$pger *= $c;

$query="SELECT * FROM admins";

if ( strlen($busc) > 0 ) {
	$query .= " WHERE login LIKE '%".$busc."%'";
}

$query .= " ORDER BY id LIMIT ".$pger.", ".$c."";

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

	$res .= '
        <div class="adm_list_txt" id="c0_'.$row['id'].'">'.$row['id'].'</div>

		<div class="adm_list_txt" id="c1_'.$row['id'].'">'.$row['login'].'</div>

		<div class="adm_list_txt" id="c2_'.$row['id'].'">'.$row['password'].'</div>

		<div class="adm_list_txt pad" id="c3_'.$row['id'].'">
			<img id="edit_icon_'.$row['id'].'" onclick="admin_get_edit_form('.$row['id'].')" src="img/edit.png" class="edit-icon-size">
        </i></div>

		<div class="adm_list_txt pad" id="c4_'.$row['id'].'">
			<img onclick="admin_del('.$row['id'].')" src="img/trash.png" class="edit-icon-size">
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