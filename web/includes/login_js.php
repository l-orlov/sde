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

$phone = isset($input['phone']) ? htmlspecialchars($input['phone']) : '';
$pass = isset($input['pass']) ? htmlspecialchars($input['pass']) : '';

if (empty($phone) || empty($pass)) {
	$return['err'] = 'Por favor, ingrese su phone y contraseña';
    echo json_encode($return);
    exit;
}

$query="SELECT * FROM users WHERE phone = ? AND password = ?";
$stmt = mysqli_prepare($link, $query);
mysqli_stmt_bind_param($stmt, "ss", $phone, $pass);
if (!mysqli_stmt_execute($stmt)) {
    error_log("SQL query error: " . mysqli_error($link));
    $return['err'] = "Login error. Por favor, intentá de nuevo";
	echo json_encode($return);
    exit();
}

$result = mysqli_stmt_get_result($stmt);
$count = mysqli_num_rows($result);
if ( !$count ) {
	$return['err'] = 'Usuario o contraseña incorrectos';
    echo json_encode($return);
    exit;
}

$row = mysqli_fetch_array($result, MYSQLI_ASSOC);

$_SESSION['uid'] = $row['id'];
$_SESSION['lastname'] =  $row['last_name'];
$_SESSION['firstname'] =  $row['first_name'];
$_SESSION['phone'] =  $row['phone'];

$return['ok'] = 1;
echo json_encode( $return );
?>