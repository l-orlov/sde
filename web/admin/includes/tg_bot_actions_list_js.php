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
        'err' => "Not valid JSON",
        'server_data' => $inputJSON
    ]);
    exit;
}

$page = isset($input['pg']) 	? intval($input['pg']) : 0;
$busc = isset($input['busc'])	? trim($input['busc']) : '';

$res .= '<div class="adm_busc">
	<input class="adm_busc_input" type="Text" id="busc_texto" value="'.$busc.'">
	<div class="adm_busc_bt" onclick="action_list_by_filter();">Find</div>
</div>';

$res .= '
	<div class="adm_zag">ID</div>
	<div class="adm_zag">User ID</div>
	<div class="adm_zag">Username</div>
	<div class="adm_zag">Command</div>
	<div class="adm_zag">Parameter</div>
	<div class="adm_zag">Status</div>
	<div class="adm_zag">Fail reason</div>
	<div class="adm_zag">Created at</div>
	<div class="adm_zag">Updated at</div>
	<div class="adm_zag"></div>
	<div class="adm_zag"></div>
';

$pger = $page;
$c = 250;
$pger *= $c;

$query="SELECT * FROM tg_bot_actions";

if ( strlen($busc) > 0 ) {
	$query .= " WHERE 
			id			LIKE '%".$busc."%' OR 
			user_id		LIKE '%".$busc."%' OR 
			username	LIKE '%".$busc."%' OR 
			command		LIKE '%".$busc."%' OR 
			parameter	LIKE '%".$busc."%' OR 
			fail_reason	LIKE '%".$busc."%'";
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

	$cant++;
	$res .= '
		<div class="adm_list_txt" id="c0_'.$row['id'].'">'.$row['id'].'</div>
		<div class="adm_list_txt" id="c1_'.$row['id'].'">'.$row['user_id'].'</div>
		<div class="adm_list_txt" id="c2_'.$row['id'].'">'.$row['username'].'</div>
		<div class="adm_list_txt" id="c3_'.$row['id'].'">'.$row['command'].'</div>
		<div class="adm_list_txt" id="c4_'.$row['id'].'">'.$row['parameter'].'</div>
		<div class="adm_list_txt" id="c5_'.$row['id'].'">'.$row['status'].'</div>
		<div class="adm_list_txt" id="c6_'.$row['id'].'">'.$row['fail_reason'].'</div>
		<div class="adm_list_txt" id="c7_'.$row['id'].'">'.$created_at_human.'</div>
		<div class="adm_list_txt" id="c8_'.$row['id'].'">'.$updated_at_human.'</div>
		<div class="adm_list_txt pad" id="c9_'.$row['id'].'">
			<img id="edit_icon_'.$row['id'].'" onclick="action_get_edit_form('.$row['id'].')" src="img/edit.png" class="edit-icon-size">
		</div>
		<div class="adm_list_txt pad" id="c10_'.$row['id'].'">
			<img onclick="action_del('.$row['id'].')" src="img/trash.png" class="edit-icon-size">
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