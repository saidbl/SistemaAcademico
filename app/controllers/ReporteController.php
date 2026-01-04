<?php
session_start();

require_once __DIR__ . '/../helper/session_helper.php';
require_once __DIR__ . '/../helper/auth_helper.php';
require_once __DIR__ . '/../config/database.php';

exigirSesionActiva();
exigirRol('Administrador');

/* 
------------------------------------------------
 VALIDACIÓN DE PETICIÓN
------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../views/reportes/administrar.php?error=Petición no válida');
    exit;
}

$id_reporte = $_POST['id_reporte'] ?? null;
$rol        = $_POST['rol'] ?? null;

if (!$id_reporte || !$rol) {
    header('Location: ../views/reportes/administrar.php?error=Datos incompletos');
    exit;
}

/* 
------------------------------------------------
 EVITAR DUPLICADOS
------------------------------------------------
*/
$check = $pdo->prepare("
    SELECT COUNT(*) 
    FROM reporte_rol 
    WHERE id_reporte = ? AND rol = ?
");
$check->execute([$id_reporte, $rol]);

if ($check->fetchColumn() > 0) {
    header('Location: ../views/reportes/administrar.php?error=El reporte ya está asignado a ese rol');
    exit;
}

/* 
------------------------------------------------
 INSERTAR ASIGNACIÓN
------------------------------------------------
*/
$stmt = $pdo->prepare("
    INSERT INTO reporte_rol (id_reporte, rol)
    VALUES (?, ?)
");

if ($stmt->execute([$id_reporte, $rol])) {
    header('Location: ../views/reportes/administrar.php?msg=Reporte asignado correctamente');
} else {
    header('Location: ../views/reportes/administrar.php?error=No se pudo asignar el reporte');
}

exit;
