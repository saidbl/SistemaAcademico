<?php
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json; charset=utf-8');

$id_grupo = $_GET['id_grupo'] ?? 0;

// Consulta
$stmt = $pdo->prepare("
    SELECT 
        h.dia_semana,
        h.hora_inicio,
        h.hora_fin,
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

// INICIALIZACIÓN FIJA → SIEMPRE 5 BLOQUES (0–4)
$horario = [
    "Lunes"     => [0=>null, 1=>null, 2=>null, 3=>null, 4=>null],
    "Martes"    => [0=>null, 1=>null, 2=>null, 3=>null, 4=>null],
    "Miercoles" => [0=>null, 1=>null, 2=>null, 3=>null, 4=>null],
    "Jueves"    => [0=>null, 1=>null, 2=>null, 3=>null, 4=>null],
    "Viernes"   => [0=>null, 1=>null, 2=>null, 3=>null, 4=>null]
];

// MAPEO DE BLOQUES
$bloques = [
    "07:00:00" => 0, "08:30:00" => 1, "10:30:00" => 2, "12:00:00" => 3, "13:30:00" => 4,
    "15:00:00" => 0, "16:30:00" => 1, "18:00:00" => 2, "19:00:00" => 3, "20:30:00" => 4
];

// Normalizar día
function normalizarDia($dia) {
    $dia = strtolower(trim($dia));
    $dia = str_replace(['á','é','í','ó','ú'], ['a','e','i','o','u'], $dia);
    return ucfirst($dia);
}

foreach ($data as $row) {

    $dia = normalizarDia($row['dia_semana']);
    if (!isset($horario[$dia])) continue;

    $bloque = $bloques[$row['hora_inicio']] ?? -1;

    if ($bloque >= 0) {
        $horario[$dia][$bloque] = [
            "materia" => $row['materia'],
            "docente" => $row['docente'] ?: 'Sin asignar'
        ];
    }
}

echo json_encode([
    'grupo'   => $data[0]['grupo'] ?? 'Desconocido',
    'turno'   => $data[0]['turno'] ?? 'Matutino',
    'horario' => $horario
]);
