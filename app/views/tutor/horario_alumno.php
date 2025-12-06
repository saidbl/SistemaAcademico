<?php
session_start();
require_once __DIR__ . '/../../helper/session_helper.php';
require_once __DIR__ . '/../../helper/auth_helper.php';
exigirSesionActiva();
exigirRol('Padre');

require_once __DIR__ . '/../../config/database.php';

// ================================
// 1. OBTENER ALUMNO ASIGNADO
// ================================
$id_tutor = $_SESSION['usuario_id'];

$stmt = $pdo->prepare("
    SELECT 
        a.id_alumno,
        u.nombre,
        u.apellido_paterno,
        u.apellido_materno,
        r.id_grupo
    FROM alumnos a
    JOIN usuarios u ON u.id_usuario = a.id_usuario
    JOIN reinscripciones r ON r.id_alumno = a.id_alumno
    WHERE a.tutor_id = ?
    ORDER BY r.id_reinscripcion DESC
    LIMIT 1
");
$stmt->execute([$id_tutor]);
$alumno = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$alumno) {
    die("<h2>No hay alumno asignado a este tutor.</h2>");
}

$id_grupo = $alumno['id_grupo'];

// ================================
// 2. OBTENER HORARIO DEL GRUPO
// ================================
$stmt = $pdo->prepare("
    SELECT 
        h.dia_semana,
        h.hora_inicio,
        h.hora_fin,
        m.nombre AS materia,
        CONCAT(d.nombre, ' ', d.apellido_paterno) AS docente
    FROM horarios h
    JOIN materias m ON m.id_materia = h.id_materia
    LEFT JOIN personal_docente pd ON pd.id_docente = h.id_docente
    LEFT JOIN usuarios d ON d.id_usuario = pd.id_usuario
    WHERE h.id_grupo = ?
    ORDER BY h.dia_semana, h.hora_inicio
");
$stmt->execute([$id_grupo]);
$horas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Inicializar estructura
$horario = [
    "Lunes"     => [],
    "Martes"    => [],
    "Miercoles" => [],
    "Jueves"    => [],
    "Viernes"   => []
];

foreach ($horas as $h) {
    $bloque = $h['hora_inicio'];
    $horario[$h['dia_semana']][$bloque] = [
        "materia" => $h['materia'],
        "docente" => $h['docente']
    ];
}

?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Horario del Alumno</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
body { background:#eef2f7; font-family:Segoe UI; padding:30px; }
table { width:100%; border-collapse:collapse; margin-top:20px; }
th, td { border:1px solid #ccc; padding:10px; text-align:center; }
th { background:#004b97; color:white; }
.materia { font-weight:bold; }
.docente { font-size:0.8em; color:#555; }
</style>
</head>

<body>

<h2>
    <i class="fa-solid fa-calendar"></i>
    Horario de <?= $alumno['nombre'] . " " . $alumno['apellido_paterno'] ?>
</h2>

<table>
    <tr>
        <th>Hora</th>
        <th>Lunes</th>
        <th>Martes</th>
        <th>Miércoles</th>
        <th>Jueves</th>
        <th>Viernes</th>
    </tr>

<?php
$bloques = [
    "07:00:00" => "07:00 - 08:30",
    "08:30:00" => "08:30 - 10:00",
    "10:30:00" => "10:30 - 12:00",
    "12:00:00" => "12:00 - 13:30",
    "13:30:00" => "13:30 - 15:00",
    "15:00:00" => "15:00 - 16:30",
    "16:30:00" => "16:30 - 18:00",
    "18:00:00" => "18:00 - 19:00"
];

foreach ($bloques as $inicio => $label) {
    echo "<tr>";
    echo "<td><b>$label</b></td>";

    foreach ($horario as $dia => $lista) {
        if (isset($lista[$inicio])) {
            echo "<td>
                    <div class='materia'>{$lista[$inicio]['materia']}</div>
                    <div class='docente'>{$lista[$inicio]['docente']}</div>
                 </td>";
        } else {
            echo "<td></td>";
        }
    }

    echo "</tr>";
}
?>
</table>

</body>
</html>
