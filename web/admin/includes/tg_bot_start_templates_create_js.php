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
  echo json_encode(['ok' => 0, 'err' => 'Not valid JSON']);
  exit;
}

$parameter  = isset($input['parameter'])  ? mysqli_real_escape_string($link, $input['parameter']) : '';
$template   = isset($input['template'])   ? mysqli_real_escape_string($link, $input['template'])  : '';

$query = "INSERT INTO tg_bot_start_templates 
  (parameter, template) 
  VALUES (?, ?)";

$stmt = mysqli_prepare($link, $query);
if (!$stmt) {
  echo json_encode(['ok' => 0, 'err' => mysqli_error($link)]);
  exit;
}

mysqli_stmt_bind_param(
  $stmt, 
  "ss",
  $parameter, $template
);

$success = mysqli_stmt_execute($stmt);

if ($success) {
  echo json_encode(['ok' => 1]);
} else {
  echo json_encode(['ok' => 0, 'err' => mysqli_error($link)]);
}

?>