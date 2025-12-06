<?php
session_start();
require_once __DIR__ . '/../../helper/session_helper.php';
require_once __DIR__ . '/../../helper/auth_helper.php';
exigirSesionActiva();
exigirRol('Alumno');

require_once __DIR__ . '/../../config/database.php';

// VALIDAR CAMPOS PERMITIDOS
$correo_personal = $_POST['correo_personal'] ?? null;
$telefono        = $_POST['telefono'] ?? null;
$password        = $_POST['password'] ?? null;

$id_usuario = $_SESSION['usuario_id'];

$response = [];

// 1) ACTUALIZAR CORREO PERSONAL
if ($correo_personal !== null) {
    $stmt = $pdo->prepare("
        UPDATE usuarios 
        SET correo_personal = ?
        WHERE id_usuario = ?
    ");
    $stmt->execute([$correo_personal, $id_usuario]);
    $response['correo_personal'] = "Actualizado";
}

// 2) ACTUALIZAR TELÉFONO (si existe en la BD)
$stmt = $pdo->query("SHOW COLUMNS FROM usuarios LIKE 'telefono_personal'");
if ($stmt->rowCount() > 0) {
    $stmt = $pdo->prepare("
        UPDATE usuarios 
        SET telefono_personal = ?
        WHERE id_usuario = ?
    ");
    $stmt->execute([$telefono, $id_usuario]);
    $response['telefono'] = "Actualizado";
}


echo json_encode([
    "status" => "ok",
    "updated" => $response
]);
