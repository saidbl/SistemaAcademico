<?php
session_start();
require_once __DIR__ . '/../../helper/session_helper.php';
require_once __DIR__ . '/../../helper/auth_helper.php';
exigirSesionActiva();
exigirRol('Alumno');

require_once __DIR__ . '/../../config/database.php';

$id_usuario = $_SESSION['usuario_id'];

// 1. Obtener id_alumno
$stmt = $pdo->prepare("SELECT id_alumno FROM alumnos WHERE id_usuario = ?");
$stmt->execute([$id_usuario]);
$alumno = $stmt->fetch(PDO::FETCH_ASSOC);

$id_alumno = $alumno['id_alumno'];

// 2. Obtener grupo desde última reinscripción
$stmt = $pdo->prepare("
    SELECT id_grupo 
    FROM reinscripciones
    WHERE id_alumno = ?
    ORDER BY id_reinscripcion DESC
    LIMIT 1
");
$stmt->execute([$id_alumno]);
$id_grupo = $stmt->fetchColumn();

if (!$id_grupo) {
    echo json_encode(["status" => "error", "message" => "No tienes grupo asignado."]);
    exit;
}

// 3. Cargar horario del grupo
$stmt = $pdo->prepare("
    SELECT 
        h.dia_semana,
        h.hora_inicio,
        m.nombre AS materia,
        CONCAT(u.nombre,' ',u.apellido_paterno) AS docente,
        g.nombre AS grupo,
        g.turno
    FROM horarios h
    JOIN grupos g ON g.id_grupo = h.id_grupo
    JOIN materias m ON m.id_materia = h.id_materia
    LEFT JOIN personal_docente d ON d.id_docente = h.id_docente
    LEFT JOIN usuarios u ON u.id_usuario = d.id_usuario
    WHERE h.id_grupo = ?
    ORDER BY h.dia_semana, h.hora_inicio
");
$stmt->execute([$id_grupo]);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Inicializar horario vacío
$horario = [
    "Lunes" => [0=>null,1=>null,2=>null,3=>null,4=>null],
    "Martes" => [0=>null,1=>null,2=>null,3=>null,4=>null],
    "Miercoles" => [0=>null,1=>null,2=>null,3=>null,4=>null],
    "Jueves" => [0=>null,1=>null,2=>null,3=>null,4=>null],
    "Viernes" => [0=>null,1=>null,2=>null,3=>null,4=>null]
];

$bloques = [
    "07:00:00"=>0,"08:30:00"=>1,"10:30:00"=>2,"12:00:00"=>3,"13:30:00"=>4,
    "15:00:00"=>0,"16:30:00"=>1,"18:00:00"=>2,"19:00:00"=>3,"20:30:00"=>4
];

function limpiar($dia) {
    $dia = strtolower(trim($dia));
    $dia = str_replace(['á','é','í','ó','ú'], ['a','e','i','o','u'], $dia);
    return ucfirst($dia);
}

foreach ($data as $row) {
    $dia = limpiar($row['dia_semana']);
    $bloque = $bloques[$row['hora_inicio']] ?? -1;

    if ($bloque >= 0) {
        $horario[$dia][$bloque] = [
            "materia" => $row['materia'],
            "docente" => $row['docente'] ?: "Sin asignar"
        ];
    }
}

echo json_encode([
    "grupo" => $data[0]['grupo'] ?? "Sin grupo",
    "turno" => $data[0]['turno'] ?? "Matutino",
    "horario" => $horario
]);
