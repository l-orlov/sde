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

$lastname       =		isset($input['lastname'])		? htmlspecialchars($input['lastname'])		: '';
$firstname      =		isset($input['firstname'])		? htmlspecialchars($input['firstname'])		: '';
$mail           =		isset($input['mail'])			? htmlspecialchars($input['mail'])			: '';
$phone          =		isset($input['phone'])			? htmlspecialchars($input['phone'])			: '';
$pass           =		isset($input['pass'])			? htmlspecialchars($input['pass'])			: '';

// Remove all non-digit characters from a phone number
$phone = clear_phone($phone);

$requiredFields = [
    'lastname' => $lastname,
	'firstname' => $firstname,
	'mail' => $mail,
	'phone' => $phone,
	'pass' => $pass
];
foreach ($requiredFields as $field => $value) {
    if (empty($value)) {
        $return['err'] = "Todos los campos deben estar completos. (Falta: $field)";
        echo json_encode($return);
        exit;
    }
}

$query="SELECT * FROM users WHERE phone = ?";
$stmt = mysqli_prepare($link, $query);
mysqli_stmt_bind_param($stmt, 's', $phone);
if (!mysqli_stmt_execute($stmt)) {
    error_log("SQL query error: " . mysqli_error($link));
    $return['err'] = "Error al registrar. Por favor, intentá de nuevo.";
	echo json_encode($return);
    exit();
}
$result = mysqli_stmt_get_result($stmt);
$count = mysqli_num_rows($result);
if ($count != 0) {
    $return['err'] = 'El Teléfono ya existe en sistema';
	echo json_encode($return);
    exit();
}

$query="SELECT * FROM users WHERE email = ?";
$stmt = mysqli_prepare($link, $query);
mysqli_stmt_bind_param($stmt, 's', $mail);
if (!mysqli_stmt_execute($stmt)) {
    error_log("SQL query error: " . mysqli_error($link));
    $return['err'] = "Error al verificar el email. Por favor, intentá de nuevo.";
	echo json_encode($return);
    exit();
}
$result = mysqli_stmt_get_result($stmt);
$count = mysqli_num_rows($result);
if ($count != 0) {
    $return['err'] = 'El Email ya existe en sistema';
	echo json_encode($return);
    exit();
}

$query = "INSERT INTO users (first_name, last_name, email, phone, password) VALUES (?, ?, ?, ?, ?)";
$stmt = mysqli_prepare($link, $query);
mysqli_stmt_bind_param($stmt, 'sssss', $firstname, $lastname, $mail, $phone, $pass);
if (!mysqli_stmt_execute($stmt)) {
    error_log("SQL query error: " . mysqli_error($link));
    $return['err'] = "Error al registrar. Por favor, intentá de nuevo.";
	echo json_encode($return);
    exit();
}

$user_id = mysqli_insert_id($link);

$_SESSION['uid'] = $user_id;
$_SESSION['lastname'] = $lastname;
$_SESSION['firstname'] = $firstname;
$_SESSION['mail'] = $mail;
$_SESSION['phone'] = $phone;

$return['ok'] = 1;
echo json_encode( $return );
?>
