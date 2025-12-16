<?php
session_start();
date_default_timezone_set('America/Argentina/Buenos_Aires');
set_time_limit(0);
error_reporting(E_ALL);
ob_implicit_flush();

include __DIR__ . '/path_helper.php';
include getIncludesFilePath('functions.php');
DBconnect();

$ok = 0;
$res = '';

$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);

if (!isset($input['id']) || !is_numeric($input['id'])) {
    echo json_encode(["ok" => 0, "err" => "ID no válido"]);
    exit;
}

$id = intval($input['id']);

$query = "DELETE FROM users WHERE id = ?";
$stmt = mysqli_prepare($link, $query);
mysqli_stmt_bind_param($stmt, 'i', $id);

if (mysqli_stmt_execute($stmt)) {
    $ok = 1;
} else {
    $res = "Error al eliminar: " . mysqli_stmt_error($stmt);
}

echo json_encode([
    "ok" => $ok,
    "res" => $res
]);
?>

