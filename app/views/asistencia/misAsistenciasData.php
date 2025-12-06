<?php
session_start();
require_once __DIR__ . '/../../helper/session_helper.php';
require_once __DIR__ . '/../../helper/auth_helper.php';
exigirSesionActiva();
exigirRol('Alumno');

require_once __DIR__ . '/../../config/database.php';

$id_usuario = $_SESSION['usuario_id'];

// 1) Obtener id_alumno desde el usuario
$stmt = $pdo->prepare("SELECT id_alumno FROM alumnos WHERE id_usuario = ?");
$stmt->execute([$id_usuario]);
$al = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$al) {
    echo json_encode([
        "materias" => [],
        "mensaje"  => "No se encontró el alumno para este usuario."
    ]);
    exit;
}

$id_alumno = (int)$al['id_alumno'];

// 2) Obtener su última reinscripción (grupo actual)
$stmt = $pdo->prepare("
    SELECT 
        r.id_grupo,
        g.id_nivel,
        g.id_carrera,
        g.id_nivel_academico
    FROM reinscripciones r
    JOIN grupos g ON g.id_grupo = r.id_grupo
    WHERE r.id_alumno = ?
    ORDER BY r.id_reinscripcion DESC
    LIMIT 1
");
$stmt->execute([$id_alumno]);
$grupo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$grupo) {
    echo json_encode([
        "materias" => [],
        "mensaje"  => "No tienes un grupo asignado actualmente."
    ]);
    exit;
}

$id_grupo      = (int)$grupo['id_grupo'];
$id_nivel      = (int)$grupo['id_nivel'];
$id_carrera    = $grupo['id_carrera'] !== null ? (int)$grupo['id_carrera'] : null;
$id_nivel_acad = (int)$grupo['id_nivel_academico'];

// 3) Obtener materias actuales según nivel (igual que en calificaciones)
if (in_array($id_nivel, [1,2,3])) {
    // Preescolar / Primaria / Secundaria
    $stmt = $pdo->prepare("
        SELECT m.id_materia, m.nombre, m.tipo
        FROM materias_por_nivel_academico na
        JOIN materias m ON m.id_materia = na.id_materia
        WHERE na.id_nivel_academico = ?
        ORDER BY m.nombre
    ");
    $stmt->execute([$id_nivel_acad]);
} else {
    // Preparatoria / Universidad
    if ($id_carrera === null) {
        echo json_encode([
            "materias" => [],
            "mensaje"  => "Tu grupo no tiene carrera asociada, no se pueden cargar materias."
        ]);
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT m.id_materia, m.nombre, m.tipo
        FROM materias_por_carrera mc
        JOIN materias m ON m.id_materia = mc.id_materia
        WHERE mc.id_carrera = ?
          AND mc.id_nivel_academico = ?
        ORDER BY m.nombre
    ");
    $stmt->execute([$id_carrera, $id_nivel_acad]);
}

$materias = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Si no hay materias, salimos limpio
if (!$materias) {
    echo json_encode([
        "materias" => [],
        "mensaje"  => "No tienes materias configuradas para tu grupo actual."
    ]);
    exit;
}

// 4) Para cada materia, calculamos asistencia (últimos 90 días) y últimos registros
$fecha_limite = (new DateTime())->modify('-90 days')->format('Y-m-d');

$resultado = [];

foreach ($materias as $m) {
    $id_materia = (int)$m['id_materia'];

    // 4.1 Totales por estado
    $stmt = $pdo->prepare("
        SELECT estado, COUNT(*) AS total
        FROM asistencias
        WHERE id_alumno = ?
          AND id_materia = ?
          AND fecha >= ?
        GROUP BY estado
    ");
    $stmt->execute([$id_alumno, $id_materia, $fecha_limite]);
    $totRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $totPresente = 0;
    $totFalta    = 0;
    $totRetardo  = 0;

    foreach ($totRows as $t) {
        switch ($t['estado']) {
            case 'Presente': $totPresente = (int)$t['total']; break;
            case 'Falta':    $totFalta    = (int)$t['total']; break;
            case 'Retardo':  $totRetardo  = (int)$t['total']; break;
        }
    }

    $totalReg = $totPresente + $totFalta + $totRetardo;

    // Porcentaje simple: (Presente + Retardo) / total
    if ($totalReg > 0) {
        $porcentaje = round((($totPresente + $totRetardo) / $totalReg) * 100, 1);
    } else {
        $porcentaje = null;
    }

    // 4.2 Últimos registros (hasta 10)
    $stmt = $pdo->prepare("
        SELECT fecha, estado
        FROM asistencias
        WHERE id_alumno = ?
          AND id_materia = ?
          AND fecha >= ?
        ORDER BY fecha DESC
        LIMIT 10
    ");
    $stmt->execute([$id_alumno, $id_materia, $fecha_limite]);
    $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $resultado[] = [
        "id_materia" => $id_materia,
        "nombre"     => $m['nombre'],
        "tipo"       => $m['tipo'],
        "totales"    => [
            "presente"  => $totPresente,
            "falta"     => $totFalta,
            "retardo"   => $totRetardo,
            "total"     => $totalReg,
            "porcentaje"=> $porcentaje,
        ],
        "registros"  => $registros
    ];
}

echo json_encode([
    "materias" => $resultado
]);
