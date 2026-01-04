<?php
session_start();
require_once __DIR__ . '/../../helper/session_helper.php';
require_once __DIR__ . '/../../helper/auth_helper.php';
require_once __DIR__ . '/../../config/database.php';

exigirSesionActiva();
exigirRol('Administrador');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$sql = "UPDATE grupos SET
nombre = ?,
id_nivel = ?,
id_nivel_academico = ?,
id_carrera = ?,
turno = ?,
cupo_maximo = ?
WHERE id_grupo = ?";

$stmt = $pdo->prepare($sql);
$ok = $stmt->execute([
    $_POST['nombre'],
    $_POST['id_nivel'],
    $_POST['id_nivel_academico'] ?: null,
    $_POST['id_carrera'] ?: null,
    $_POST['turno'],
    $_POST['cupo_maximo'],
    $_POST['id_grupo']
]);

if ($ok) {
    header('Location: ../dashboard/administrador_dashboard.php?msg=Grupo actualizado correctamente');
} else {
    header('Location: ../dashboard/administrador_dashboard.php?error=Error al actualizar grupo');
}
exit;
