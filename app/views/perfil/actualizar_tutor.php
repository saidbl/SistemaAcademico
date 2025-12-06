<?php
session_start();
require_once __DIR__ . '/../../helper/session_helper.php';
require_once __DIR__ . '/../../helper/auth_helper.php';
exigirSesionActiva();
exigirRol('Padre');

require_once __DIR__ . '/../../config/database.php';

$id_usuario = $_SESSION['usuario_id'];

$correo_personal = $_POST['correo_personal'] ?? null;
$telefono        = $_POST['telefono'] ?? null;
$direccion       = $_POST['direccion'] ?? null;

// Solo campos EDITABLES.
$stmt = $pdo->prepare("
    UPDATE usuarios
    SET correo_personal = ?,
        telefono = ?,
        direccion = ?
    WHERE id_usuario = ?
");
$stmt->execute([
    $correo_personal,
    $telefono,
    $direccion,
    $id_usuario
]);

echo json_encode(["status" => "ok"]);
