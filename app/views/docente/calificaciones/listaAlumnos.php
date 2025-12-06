<?php
session_start();
require_once __DIR__ . '/../../../helper/session_helper.php';
require_once __DIR__ . '/../../../helper/auth_helper.php';
exigirSesionActiva();
exigirRol('Docente');

require_once __DIR__ . '/../../../config/database.php';

$id_grupo   = $_GET['grupo'] ?? 0;
$id_materia = $_GET['materia'] ?? 0;
$periodo    = $_GET['periodo'] ?? "";

// 1. Obtener alumnos inscritos vía reinscripciones
$stmt = $pdo->prepare("
    SELECT a.id_alumno, u.nombre, u.apellido_paterno, u.apellido_materno
    FROM reinscripciones r
    JOIN alumnos a ON a.id_alumno = r.id_alumno
    JOIN usuarios u ON u.id_usuario = a.id_usuario
    WHERE r.id_grupo = ?
");
$stmt->execute([$id_grupo]);
$alumnos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 2. Obtener calificaciones si existen
foreach ($alumnos as &$a) {

    $stmt2 = $pdo->prepare("
        SELECT calificacion 
        FROM calificaciones
        WHERE id_alumno = ?
          AND id_materia = ?
          AND periodo = ?
        LIMIT 1
    ");
    $stmt2->execute([$a['id_alumno'], $id_materia, $periodo]);
    $a['calificacion'] = $stmt2->fetchColumn() ?? null;
}

echo json_encode($alumnos);
