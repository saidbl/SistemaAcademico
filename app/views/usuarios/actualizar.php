<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/Usuario.php';
require_once __DIR__ . '/../../helper/session_helper.php';
require_once __DIR__ . '/../../helper/auth_helper.php';

exigirSesionActiva();
exigirRol('Administrador');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$model = new Usuario($pdo);

$ok = $model->actualizar([
    'id_usuario' => $_POST['id_usuario'],
    'nombre' => $_POST['nombre'],
    'apellido_paterno' => $_POST['apellido_paterno'],
    'apellido_materno' => $_POST['apellido_materno'],
    'correo_institucional' => $_POST['correo_institucional'],
    'tipo_usuario' => $_POST['tipo_usuario'],
    'estatus' => $_POST['estatus'],
]);

if ($ok) {
    header('Location: ../dashboard/administrador_dashboard.php?msg=Usuario actualizado correctamente');
} else {
    header('Location: ../dashboard/administrador_dashboard.php?error=Error al actualizar usuario');
}
exit;
