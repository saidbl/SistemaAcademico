<?php
session_start();
require_once __DIR__ . '/../../helper/session_helper.php';
require_once __DIR__ . '/../../helper/auth_helper.php';
exigirSesionActiva();
exigirRol('Alumno');

require_once __DIR__ . '/../../config/database.php';

$id_usuario = $_SESSION['usuario_id'];

// ⭐ 1. Obtener id_alumno
$stmt = $pdo->prepare("SELECT id_alumno FROM alumnos WHERE id_usuario = ?");
$stmt->execute([$id_usuario]);
$al = $stmt->fetch(PDO::FETCH_ASSOC);

$id_alumno = $al['id_alumno'];

// ⭐ 2. Obtener su reinscripción actual
$stmt = $pdo->prepare("
    SELECT 
        r.id_grupo,
        g.id_nivel,
        g.id_carrera,
        g.id_nivel_academico,
        ne.periodo AS periodo_actual
    FROM reinscripciones r
    JOIN grupos g ON g.id_grupo = r.id_grupo
    JOIN niveles_educativos ne ON ne.id_nivel = g.id_nivel
    WHERE r.id_alumno = ?
    ORDER BY r.id_reinscripcion DESC
    LIMIT 1
");
$stmt->execute([$id_alumno]);
$grupo = $stmt->fetch(PDO::FETCH_ASSOC);

$id_grupo = $grupo['id_grupo'];
$id_nivel = $grupo['id_nivel'];
$id_carrera = $grupo['id_carrera'];
$id_nivel_acad = $grupo['id_nivel_academico'];
$periodo_actual = $grupo['periodo_actual']; // ⭐ ESTE ES EL PERIODO REAL QUE SE CURSA

// ⭐ 3. Obtener materias actuales
if (in_array($id_nivel, [1,2,3])) {
    $stmt = $pdo->prepare("
        SELECT m.id_materia, m.nombre, m.tipo
        FROM materias_por_nivel_academico na
        JOIN materias m ON m.id_materia = na.id_materia
        WHERE na.id_nivel_academico = ?
    ");
    $stmt->execute([$id_nivel_acad]);
} else {
    $stmt = $pdo->prepare("
        SELECT m.id_materia, m.nombre, m.tipo
        FROM materias_por_carrera mc
        JOIN materias m ON m.id_materia = mc.id_materia
        WHERE mc.id_carrera = ?
        AND mc.id_nivel_academico = ?
    ");
    $stmt->execute([$id_carrera, $id_nivel_acad]);
}

$materias = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ⭐ 4. Calificación SOLO si pertenece al periodo actual
foreach ($materias as &$m) {

    $stmt = $pdo->prepare("
        SELECT calificacion
        FROM calificaciones
        WHERE id_alumno = ?
        AND id_materia = ?
        AND periodo = ?
        LIMIT 1
    ");
    $stmt->execute([$id_alumno, $m['id_materia'], $periodo_actual]);
    $cal = $stmt->fetch(PDO::FETCH_ASSOC);

    $m['calificacion'] = $cal['calificacion'] ?? null;
}

echo json_encode([
    "periodo_actual" => $periodo_actual,
    "materias" => $materias
]);
