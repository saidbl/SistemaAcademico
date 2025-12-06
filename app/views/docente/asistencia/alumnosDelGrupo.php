<?php
session_start();
require_once __DIR__ . '/../../../helper/session_helper.php';
require_once __DIR__ . '/../../../helper/auth_helper.php';
exigirSesionActiva();
exigirRol('Docente');

require_once __DIR__ . '/../../../config/database.php';

$id_grupo   = $_GET['grupo'] ?? 0;
$id_materia = $_GET['materia'] ?? 0;
$fecha      = $_GET['fecha'] ?? date("Y-m-d");

// 1. Alumnos del grupo
$stmt = $pdo->prepare("
    SELECT 
        a.id_alumno,
        u.nombre,
        u.apellido_paterno,
        u.apellido_materno
    FROM reinscripciones r
    JOIN alumnos a ON a.id_alumno = r.id_alumno
    JOIN usuarios u ON u.id_usuario = a.id_usuario
    WHERE r.id_grupo = ?
    ORDER BY u.apellido_paterno
");
$stmt->execute([$id_grupo]);
$alumnos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 2. Obtener asistencias si existen
foreach ($alumnos as &$al) {

    $stmt2 = $pdo->prepare("
        SELECT estado 
        FROM asistencias
        WHERE id_alumno = ?
          AND id_materia = ?
          AND fecha = ?
    ");
    $stmt2->execute([$al['id_alumno'], $id_materia, $fecha]);
    $al['estado'] = $stmt2->fetchColumn() ?: null;
}

echo json_encode($alumnos);
