<?php
session_start();
require_once __DIR__ . '/../../helper/session_helper.php';
require_once __DIR__ . '/../../helper/auth_helper.php';
exigirSesionActiva();
exigirRol('Alumno');

require_once __DIR__ . '/../../config/database.php';

$id_usuario = $_SESSION['usuario_id'];

// 1. OBTENER DATOS DEL ALUMNO
$stmt = $pdo->prepare("SELECT id_alumno FROM alumnos WHERE id_usuario = ?");
$stmt->execute([$id_usuario]);
$al = $stmt->fetch(PDO::FETCH_ASSOC);

$id_alumno = $al['id_alumno'];

// 2. OBTENER TODAS LAS REINSCRIPCIONES DEL ALUMNO
$stmt = $pdo->prepare("
    SELECT 
        r.id_reinscripcion, 
        r.id_grupo, 
        r.fecha, 
        g.nombre AS grupo, 
        g.turno,
        g.id_nivel,
        g.id_carrera AS id_carrera_grupo,
        g.id_nivel_academico AS id_nivel_acad_grupo
    FROM reinscripciones r
    JOIN grupos g ON g.id_grupo = r.id_grupo
    WHERE r.id_alumno = ?
    ORDER BY r.fecha DESC, r.id_reinscripcion DESC
");
$stmt->execute([$id_alumno]);
$reins = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Si no tiene reinscripción, no puede tener materias
if (empty($reins)) {
    echo json_encode([
        "grupo" => null,
        "turno" => null,
        "materias_actuales" => [],
        "historial" => []
    ]);
    exit;
}

// ============================================================
// 3. MATERIAS ACTUALES - DESDE EL GRUPO ACTUAL, NO DESDE ALUMNOS
// ============================================================
$actual = $reins[0];

$id_grupo_actual = $actual["id_grupo"];
$id_nivel_actual = $actual["id_nivel"];
$id_carrera_actual = $actual["id_carrera_grupo"];
$id_nivel_acad_actual = $actual["id_nivel_acad_grupo"];

// ---------------------------------------
// OBTENER MATERIAS ACTUALES
// ---------------------------------------
if (in_array($id_nivel_actual, [1, 2, 3])) {
    // Niveles básicos
    $stmt = $pdo->prepare("
        SELECT m.id_materia, m.nombre, m.tipo
        FROM materias_por_nivel_academico na
        JOIN materias m ON m.id_materia = na.id_materia
        WHERE na.id_nivel_academico = ?
    ");
    $stmt->execute([$id_nivel_acad_actual]);

} else {
    // Preparatoria / Universidad
    $stmt = $pdo->prepare("
        SELECT m.id_materia, m.nombre, m.tipo
        FROM materias_por_carrera mc
        JOIN materias m ON m.id_materia = mc.id_materia
        WHERE mc.id_carrera = ?
          AND mc.id_nivel_academico = ?
    ");
    $stmt->execute([$id_carrera_actual, $id_nivel_acad_actual]);
}

$materiasActuales = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener docente de cada materia actual
foreach ($materiasActuales as &$m) {
    $stmt = $pdo->prepare("
        SELECT CONCAT(u.nombre,' ',u.apellido_paterno) AS docente
        FROM horarios h
        LEFT JOIN personal_docente d ON d.id_docente = h.id_docente
        LEFT JOIN usuarios u ON u.id_usuario = d.id_usuario
        WHERE h.id_grupo = ?
          AND h.id_materia = ?
        LIMIT 1
    ");
    $stmt->execute([$id_grupo_actual, $m['id_materia']]);
    $m["docente"] = $stmt->fetchColumn() ?: "Sin asignar";
}

// ============================================================
// 4. HISTORIAL COMPLETO
// ============================================================
$historial = [];

foreach ($reins as $r) {

    $id_grupo_h = $r["id_grupo"];
    $nivel_h = $r["id_nivel"];
    $carrera_h = $r["id_carrera_grupo"];
    $nivel_acad_h = $r["id_nivel_acad_grupo"];

    // Obtener materias del ciclo histórico
    if (in_array($nivel_h, [1,2,3])) {
        $stmt = $pdo->prepare("
            SELECT m.id_materia, m.nombre, m.tipo
            FROM materias_por_nivel_academico na
            JOIN materias m ON m.id_materia = na.id_materia
            WHERE na.id_nivel_academico = ?
        ");
        $stmt->execute([$nivel_acad_h]);

    } else {
        $stmt = $pdo->prepare("
            SELECT m.id_materia, m.nombre, m.tipo
            FROM materias_por_carrera mc
            JOIN materias m ON m.id_materia = mc.id_materia
            WHERE mc.id_carrera = ?
              AND mc.id_nivel_academico = ?
        ");
        $stmt->execute([$carrera_h, $nivel_acad_h]);
    }

    $mats = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Agregar calificaciones por materia
    foreach ($mats as &$mat) {
        $stmt = $pdo->prepare("
            SELECT periodo, calificacion
            FROM calificaciones
            WHERE id_alumno = ?
              AND id_materia = ?
        ");
        $stmt->execute([$id_alumno, $mat["id_materia"]]);
        $mat["calificaciones"] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    $historial[] = [
        "grupo" => $r["grupo"],
        "turno" => $r["turno"],
        "fecha" => $r["fecha"],
        "materias" => $mats
    ];
}

echo json_encode([
    "grupo" => $actual["grupo"],
    "turno" => $actual["turno"],
    "materias_actuales" => $materiasActuales,
    "historial" => $historial
]);