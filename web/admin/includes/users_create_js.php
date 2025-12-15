<?
session_start();
date_default_timezone_set('America/Argentina/Buenos_Aires');
set_time_limit(0);
error_reporting(E_ALL);
ob_implicit_flush();

include "../../includes/functions.php";
DBconnect();

$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);

if (!$input) {
  echo json_encode(['ok' => 0, 'err' => 'JSON no válido']);
  exit;
}

$company_name = isset($input['company_name']) ? mysqli_real_escape_string($link, $input['company_name']) : '';
$tax_id = isset($input['tax_id']) ? mysqli_real_escape_string($link, $input['tax_id']) : '';
$email = isset($input['email']) ? mysqli_real_escape_string($link, $input['email']) : '';
$phone = isset($input['phone']) ? mysqli_real_escape_string($link, $input['phone']) : '';
$password = isset($input['password']) ? mysqli_real_escape_string($link, $input['password']) : '';

// Проверка обязательных полей
if (empty($company_name) || empty($tax_id) || empty($email) || empty($phone) || empty($password)) {
  echo json_encode(['ok' => 0, 'err' => 'Todos los campos son obligatorios']);
  exit;
}

// Проверка уникальности email и phone
$checkQuery = "SELECT id FROM users WHERE email = ? OR phone = ?";
$checkStmt = mysqli_prepare($link, $checkQuery);
mysqli_stmt_bind_param($checkStmt, "ss", $email, $phone);
mysqli_stmt_execute($checkStmt);
$checkResult = mysqli_stmt_get_result($checkStmt);

if (mysqli_num_rows($checkResult) > 0) {
  echo json_encode(['ok' => 0, 'err' => 'El correo electrónico o teléfono ya existe']);
  exit;
}

$query = "INSERT INTO users 
  (company_name, tax_id, email, phone, password) 
  VALUES (?, ?, ?, ?, ?)";

$stmt = mysqli_prepare($link, $query);
if (!$stmt) {
  echo json_encode(['ok' => 0, 'err' => mysqli_error($link)]);
  exit;
}

mysqli_stmt_bind_param(
  $stmt, 
  "sssss",
  $company_name, $tax_id, $email, $phone, $password
);

$success = mysqli_stmt_execute($stmt);

if ($success) {
  echo json_encode(['ok' => 1]);
} else {
  echo json_encode(['ok' => 0, 'err' => mysqli_error($link)]);
}

?>

