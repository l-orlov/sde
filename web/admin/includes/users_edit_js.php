<?
session_start();
date_default_timezone_set('America/Argentina/Buenos_Aires');
set_time_limit (0);
error_reporting(E_ALL);
ob_implicit_flush();

include "../../includes/functions.php";
DBconnect();

$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);

$id = isset($input['id']) ? intval($input['id']) : 0;

if ($id <= 0) {
    echo json_encode(['ok' => 0, 'err' => 'Not valid ID']);
    exit;
}

$company_name = isset($input['company_name']) ? mysqli_real_escape_string($link, $input['company_name']) : '';
$tax_id = isset($input['tax_id']) ? mysqli_real_escape_string($link, $input['tax_id']) : '';
$email = isset($input['email']) ? mysqli_real_escape_string($link, $input['email']) : '';
$phone = isset($input['phone']) ? mysqli_real_escape_string($link, $input['phone']) : '';

// Проверка уникальности email и phone (кроме текущего пользователя)
$checkQuery = "SELECT id FROM users WHERE (email = ? OR phone = ?) AND id != ?";
$checkStmt = mysqli_prepare($link, $checkQuery);
mysqli_stmt_bind_param($checkStmt, "ssi", $email, $phone, $id);
mysqli_stmt_execute($checkStmt);
$checkResult = mysqli_stmt_get_result($checkStmt);

if (mysqli_num_rows($checkResult) > 0) {
    echo json_encode(['ok' => 0, 'err' => 'Email or phone already exists']);
    exit;
}

$query = "UPDATE users SET 
    company_name = ?,
    tax_id = ?,
    email = ?,
    phone = ?,
    updated_at = UNIX_TIMESTAMP()
WHERE id = ?";

$stmt = mysqli_prepare($link, $query);
mysqli_stmt_bind_param($stmt, "ssssi", 
    $company_name, $tax_id, $email, $phone, $id
);
$success = mysqli_stmt_execute($stmt);

if ($success) {
    $selectQuery = "SELECT id, company_name, tax_id, email, phone, created_at, updated_at FROM users WHERE id = ?";
    $stmt = mysqli_prepare($link, $selectQuery);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);

    $userData = [
        'id' => $user['id'],
        'company_name' => $user['company_name'],
        'tax_id' => $user['tax_id'],
        'email' => $user['email'],
        'phone' => $user['phone'],
        'created_at' => date('Y-m-d H:i', $user['created_at']),
        'updated_at' => date('Y-m-d H:i', $user['updated_at']),
    ];

    echo json_encode(['ok' => 1, 'user' => $userData]);
} else {
    echo json_encode(['ok' => 0, 'err' => mysqli_error($link)]);
}

?>

