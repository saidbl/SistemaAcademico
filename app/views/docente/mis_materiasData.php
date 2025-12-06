<?php
session_start();
require_once __DIR__ . '/../../helper/session_helper.php';
require_once __DIR__ . '/../../helper/auth_helper.php';
exigirSesionActiva();
exigirRol('Docente');

require_once __DIR__ . '/../../config/database.php';

$id_usuario = $_SESSION['usuario_id'];

// Obtener id_docente real
$stmt = $pdo->prepare("SELECT id_docente FROM personal_docente WHERE id_usuario = ?");
$stmt->execute([$id_usuario]);
$id_docente = $stmt->fetchColumn();

if (!$id_docente) {
    echo json_encode(["error" => "No se encontró el docente"]);
    exit;
}

// CONSULTA PRINCIPAL
$stmt = $pdo->prepare("
    SELECT 
        h.id_materia,
        m.nombre AS materia,
        m.tipo,
        h.id_grupo,
        g.nombre AS grupo,
        g.turno,
        
        h.dia_semana,
        h.hora_inicio,
        h.hora_fin
        
    FROM horarios h
    JOIN materias m ON m.id_materia = h.id_materia
    JOIN grupos g   ON g.id_grupo = h.id_grupo
    WHERE h.id_docente = ?
    ORDER BY g.nombre, h.dia_semana, h.hora_inicio
");
$stmt->execute([$id_docente]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ORGANIZAR POR MATERIA+GRUPO
$materias = [];

foreach ($rows as $r) {

    $key = $r['id_materia'] . "-" . $r['id_grupo'];

    if (!isset($materias[$key])) {

        // Número de alumnos en el grupo
        $stmt2 = $pdo->prepare("
            SELECT COUNT(*) 
            FROM reinscripciones 
            WHERE id_grupo = ?
        ");
        $stmt2->execute([$r['id_grupo']]);
        $total_alumnos = $stmt2->fetchColumn();

        $materias[$key] = [
            "id_materia" => $r["id_materia"],
            "materia"    => $r["materia"],
            "tipo"       => $r["tipo"],

            "id_grupo" => $r["id_grupo"],
            "grupo"    => $r["grupo"],
            "turno"    => $r["turno"],

            "total_alumnos" => $total_alumnos,
            "horario" => []
        ];
    }

    // Agregar horario
    $materias[$key]["horario"][] = [
        "dia"    => $r["dia_semana"],
        "inicio" => $r["hora_inicio"],
        "fin"    => $r["hora_fin"]
    ];
}

echo json_encode(array_values($materias));
