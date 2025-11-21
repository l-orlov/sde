<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

ob_start();

include "functions.php";

DBconnect();

$return = ['res' => '', 'ok' => 0, 'err' => ''];

if (!isset($_SESSION['uid'])) {
    if (ob_get_level()) ob_clean();
    $return['err'] = 'No autorizado. Por favor, inicie sesión.';
    header('Content-Type: application/json');
    echo json_encode($return);
    exit;
}

$userId = intval($_SESSION['uid']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        global $link;
        
        $inputJSON = file_get_contents('php://input');
        $input = json_decode($inputJSON, true);
        
        if (!$input) {
            $return['err'] = 'No se recibieron datos';
            if (ob_get_level()) ob_clean();
            header('Content-Type: application/json');
            echo json_encode($return);
            exit;
        }
        
        $lastname = isset($input['lastname']) ? htmlspecialchars(trim($input['lastname'])) : '';
        $firstname = isset($input['firstname']) ? htmlspecialchars(trim($input['firstname'])) : '';
        $companyName = isset($input['company_name']) ? htmlspecialchars(trim($input['company_name'])) : '';
        $email = isset($input['email']) ? htmlspecialchars(trim($input['email'])) : '';
        $phone = isset($input['phone']) ? htmlspecialchars(trim($input['phone'])) : '';
        $password = isset($input['password']) ? trim($input['password']) : '';
        
        if (empty($email)) {
            $return['err'] = 'El correo electrónico es obligatorio';
            if (ob_get_level()) ob_clean();
            header('Content-Type: application/json');
            echo json_encode($return);
            exit;
        }
        
        if (empty($phone)) {
            $return['err'] = 'El número de teléfono es obligatorio';
            if (ob_get_level()) ob_clean();
            header('Content-Type: application/json');
            echo json_encode($return);
            exit;
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $return['err'] = 'El formato del correo electrónico no es válido';
            if (ob_get_level()) ob_clean();
            header('Content-Type: application/json');
            echo json_encode($return);
            exit;
        }
        
        $phone = clear_phone($phone);
        
        $query = "SELECT id FROM users WHERE email = ? AND id != ?";
        $stmt = mysqli_prepare($link, $query);
        mysqli_stmt_bind_param($stmt, 'si', $email, $userId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if (mysqli_num_rows($result) > 0) {
            mysqli_stmt_close($stmt);
            $return['err'] = 'Este correo electrónico ya está en uso';
            if (ob_get_level()) ob_clean();
            header('Content-Type: application/json');
            echo json_encode($return);
            exit;
        }
        mysqli_stmt_close($stmt);
        
        $query = "SELECT id FROM users WHERE phone = ? AND id != ?";
        $stmt = mysqli_prepare($link, $query);
        mysqli_stmt_bind_param($stmt, 'si', $phone, $userId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if (mysqli_num_rows($result) > 0) {
            mysqli_stmt_close($stmt);
            $return['err'] = 'Este número de teléfono ya está en uso';
            if (ob_get_level()) ob_clean();
            header('Content-Type: application/json');
            echo json_encode($return);
            exit;
        }
        mysqli_stmt_close($stmt);
        
        if (!empty($password)) {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $query = "UPDATE users SET last_name = ?, first_name = ?, email = ?, phone = ?, password = ? WHERE id = ?";
            $stmt = mysqli_prepare($link, $query);
            mysqli_stmt_bind_param($stmt, 'sssssi', $lastname, $firstname, $email, $phone, $hashedPassword, $userId);
        } else {
            $query = "UPDATE users SET last_name = ?, first_name = ?, email = ?, phone = ? WHERE id = ?";
            $stmt = mysqli_prepare($link, $query);
            mysqli_stmt_bind_param($stmt, 'ssssi', $lastname, $firstname, $email, $phone, $userId);
        }
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Error al actualizar el perfil: " . mysqli_error($link));
        }
        
        mysqli_stmt_close($stmt);
        
        $query = "SELECT id FROM companies WHERE user_id = ? LIMIT 1";
        $stmt = mysqli_prepare($link, $query);
        mysqli_stmt_bind_param($stmt, 'i', $userId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $company = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        
        $companyId = $company ? $company['id'] : null;
        
        if (!empty($companyName)) {
            if ($companyId) {
                $query = "UPDATE companies SET name = ?, updated_at = UNIX_TIMESTAMP() WHERE id = ?";
                $stmt = mysqli_prepare($link, $query);
                mysqli_stmt_bind_param($stmt, 'si', $companyName, $companyId);
            } else {
                $query = "INSERT INTO companies (user_id, name, created_at, updated_at) VALUES (?, ?, UNIX_TIMESTAMP(), UNIX_TIMESTAMP())";
                $stmt = mysqli_prepare($link, $query);
                mysqli_stmt_bind_param($stmt, 'is', $userId, $companyName);
            }
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        } else if ($companyId) {
            $query = "UPDATE companies SET name = '', updated_at = UNIX_TIMESTAMP() WHERE id = ?";
            $stmt = mysqli_prepare($link, $query);
            mysqli_stmt_bind_param($stmt, 'i', $companyId);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
        
        $return['ok'] = 1;
        $return['res'] = 'Perfil actualizado correctamente';
        
    } catch (Exception $e) {
        error_log("Error updating profile: " . $e->getMessage());
        $return['err'] = 'Error al actualizar el perfil: ' . $e->getMessage();
    }
} else {
    $return['err'] = 'Método no permitido';
}

if (ob_get_level()) {
    ob_clean();
}
header('Content-Type: application/json');
echo json_encode($return);
?>

