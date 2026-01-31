<?
session_start();
set_time_limit (0);
error_reporting(E_ALL);
ob_implicit_flush();

include "functions.php";
DBconnect();

$return = ['res' => '', 'ok' => 0, 'err' => ''];

$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true); // JSON to Array

$tax_id_raw = isset($input['tax_id']) ? trim((string) $input['tax_id']) : '';
$tax_id = preg_replace('/\D/', '', $tax_id_raw);
$pass = isset($input['pass']) ? htmlspecialchars($input['pass']) : '';

if (empty($tax_id) || empty($pass)) {
	$return['err'] = 'Por favor, ingrese su CUIL/CUIT y contraseña';
    echo json_encode($return);
    exit;
}
if (strlen($tax_id) !== 11) {
	$return['err'] = 'CUIT / Identificación Fiscal debe tener exactamente 11 dígitos.';
	echo json_encode($return);
	exit;
}

$query="SELECT * FROM users WHERE tax_id = ? AND password = ?";
$stmt = mysqli_prepare($link, $query);
mysqli_stmt_bind_param($stmt, "ss", $tax_id, $pass);
if (!mysqli_stmt_execute($stmt)) {
    error_log("SQL query error: " . mysqli_error($link));
    $return['err'] = "Login error. Por favor, intentá de nuevo";
	echo json_encode($return);
    exit();
}

$result = mysqli_stmt_get_result($stmt);
$count = mysqli_num_rows($result);
if ( !$count ) {
	$return['err'] = 'CUIL/CUIT o contraseña incorrectos';
    echo json_encode($return);
    exit;
}

$row = mysqli_fetch_array($result, MYSQLI_ASSOC);

$_SESSION['uid'] = $row['id'];
$_SESSION['company_name'] = $row['company_name'] ?? '';
$_SESSION['tax_id'] = $row['tax_id'] ?? '';
$_SESSION['phone'] = $row['phone'];

$return['ok'] = 1;
echo json_encode( $return );
?>