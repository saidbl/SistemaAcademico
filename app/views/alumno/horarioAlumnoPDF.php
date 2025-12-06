<?php
session_start();
require_once __DIR__ . '/../../helper/session_helper.php';
require_once __DIR__ . '/../../helper/auth_helper.php';
exigirSesionActiva();
exigirRol('Alumno');

require_once __DIR__ . '/../../../libs/dompdf/autoload.inc.php';
use Dompdf\Dompdf;

require_once __DIR__ . '/../../config/database.php';

$id_usuario = $_SESSION['usuario_id'];

// 1. Obtener id_alumno
$stmt = $pdo->prepare("SELECT id_alumno FROM alumnos WHERE id_usuario = ?");
$stmt->execute([$id_usuario]);
$alumno = $stmt->fetch(PDO::FETCH_ASSOC);

// 2. Obtener id_grupo
$stmt = $pdo->prepare("
    SELECT id_grupo 
    FROM reinscripciones
    WHERE id_alumno = ?
    ORDER BY id_reinscripcion DESC
    LIMIT 1
");
$stmt->execute([$alumno['id_alumno']]);
$id_grupo = $stmt->fetchColumn();

// 3. Obtener horario real
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

$turno = $data[0]["turno"];

// -------------------------
//  BLOQUES EXACTOS
// -------------------------
$bloques = [];

if ($turno === "Vespertino") {
    $bloques = [
        "15:00:00" => "15:00 - 16:30",
        "16:30:00" => "16:30 - 18:00",
        "18:00:00" => "18:00 - 19:00",
        "19:00:00" => "19:00 - 20:30",
        "20:30:00" => "20:30 - 22:00",
    ];
} else {
    $bloques = [
        "07:00:00" => "07:00 - 08:30",
        "08:30:00" => "08:30 - 10:00",
        "10:30:00" => "10:30 - 12:00",
        "12:00:00" => "12:00 - 13:30",
        "13:30:00" => "13:30 - 15:00",
    ];
}

// -------------------------
//  Construcción del horario
// -------------------------
$dias = ["Lunes","Martes","Miercoles","Jueves","Viernes"];

$horario = [];
foreach ($dias as $d) {
    foreach ($bloques as $inicio => $label) {
        $horario[$d][$inicio] = "";
    }
}

foreach ($data as $row) {
    $dia = $row["dia_semana"];
    $inicio = $row["hora_inicio"];
    $horario[$dia][$inicio] =
        "<strong>{$row['materia']}</strong><br><small>{$row['docente']}</small>";
}

// -------------------------
//  HTML PARA PDF
// -------------------------

$html = "
<h2 style='text-align:center;'>Horario de {$data[0]['grupo']} ({$turno})</h2>
<table width='100%' border='1' cellspacing='0' cellpadding='6'>
<tr style='background:#004b97;color:white;text-align:center;'>
<th>Bloque</th>
<th>Lunes</th>
<th>Martes</th>
<th>Miércoles</th>
<th>Jueves</th>
<th>Viernes</th>
</tr>
";

foreach ($bloques as $inicio => $label) {
    $html .= "<tr>";
    $html .= "<td><strong>$label</strong></td>";

    foreach ($dias as $dia) {
        $html .= "<td>{$horario[$dia][$inicio]}</td>";
    }

    $html .= "</tr>";
}

$html .= "</table>";

// -------------------------
//  GENERAR PDF
// -------------------------

$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper("A4", "landscape");
$dompdf->render();
$dompdf->stream("Mi_Horario.pdf", ["Attachment" => true]);
