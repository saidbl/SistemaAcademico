<?php
require_once __DIR__ . '/../../config/database.php';

$id_grupo = $_GET['id_grupo'] ?? 0;

$stmt = $pdo->prepare("
SELECT 
    h.dia_semana,
    h.hora_inicio,
    h.hora_fin,
    m.nombre AS materia,
    CONCAT(u.nombre,' ',u.apellido_paterno) AS docente,
    g.nombre AS grupo
FROM horarios h
JOIN grupos g ON g.id_grupo = h.id_grupo
JOIN materias m ON m.id_materia = h.id_materia
JOIN personal_docente d ON d.id_docente = h.id_docente
JOIN usuarios u ON u.id_usuario = d.id_usuario
WHERE h.id_grupo = ?
ORDER BY h.dia_semana, h.hora_inicio
");

$stmt->execute([$id_grupo]);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

$horario = [
    "Lunes"     => [],
    "Martes"    => [],
    "Miercoles" => [],
    "Jueves"    => [],
    "Viernes"   => []
];

foreach ($data as $row) {
    $bloque = match($row['hora_inicio']) {
        "07:00:00" => 0,
        "08:30:00" => 1,
        "10:30:00" => 2,
        "12:00:00" => 3,
        "13:30:00" => 4,
        default => -1
    };

    if ($bloque >= 0) {
        $horario[$row['dia_semana']][$bloque] = [
            'materia' => $row['materia'],
            'docente' => $row['docente']
        ];
    }
}

echo json_encode([
    'grupo'    => $data[0]['grupo'] ?? 'Desconocido',
    'horario'  => $horario
]);
