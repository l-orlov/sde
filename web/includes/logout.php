<?
// session_start() уже вызван в index.php
session_destroy();
header('Location: index.php');
exit();
?>