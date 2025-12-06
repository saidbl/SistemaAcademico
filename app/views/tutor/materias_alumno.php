<?php
session_start();
require_once __DIR__ . '/../../helper/session_helper.php';
require_once __DIR__ . '/../../helper/auth_helper.php';
exigirSesionActiva();
exigirRol('Padre');

require_once __DIR__ . '/../../config/database.php';

// =======================================
// 1. OBTENER ALUMNO ASIGNADO AL TUTOR
// =======================================
$id_tutor = $_SESSION['usuario_id'];

$stmt = $pdo->prepare("
    SELECT 
        a.id_alumno,
        u.nombre,
        u.apellido_paterno,
        u.apellido_materno
    FROM alumnos a
    JOIN usuarios u ON u.id_usuario = a.id_usuario
    WHERE a.tutor_id = ?
    LIMIT 1
");
$stmt->execute([$id_tutor]);
$alumno = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$alumno) {
    die("<h2>No hay alumno asignado a este tutor.</h2>");
}

$id_alumno = $alumno['id_alumno'];

// =======================================
// 2. OBTENER GRUPO ACTUAL
// =======================================
$stmt = $pdo->prepare("
    SELECT r.id_grupo, g.nombre AS grupo, g.turno
    FROM reinscripciones r
    JOIN grupos g ON g.id_grupo = r.id_grupo
    WHERE r.id_alumno = ?
    ORDER BY r.id_reinscripcion DESC
    LIMIT 1
");
$stmt->execute([$id_alumno]);
$grp = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$grp) {
    die("<h2>El alumno no está inscrito en ningún grupo.</h2>");
}

$id_grupo = $grp['id_grupo'];

// =======================================
// 3. OBTENER TODAS LAS MATERIAS DEL GRUPO
// =======================================
$stmt = $pdo->prepare("
    SELECT 
        m.id_materia,
        m.nombre AS materia,
        m.tipo,
        h.dia_semana,
        h.hora_inicio,
        h.hora_fin,

        u.nombre AS docente_nombre,
        u.apellido_paterno AS docente_ap,

        g.turno
    FROM horarios h
    JOIN materias m ON m.id_materia = h.id_materia
    LEFT JOIN personal_docente pd ON pd.id_docente = h.id_docente
    LEFT JOIN usuarios u ON u.id_usuario = pd.id_usuario
    JOIN grupos g ON g.id_grupo = h.id_grupo
    WHERE h.id_grupo = ?
    ORDER BY m.nombre, h.dia_semana, h.hora_inicio
");
$stmt->execute([$id_grupo]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// =======================================
// 4. ORGANIZAR LAS MATERIAS POR MATERIA
// =======================================
$materias = [];

foreach ($rows as $r) {

    if (!isset($materias[$r['id_materia']])) {
        $materias[$r['id_materia']] = [
            "materia" => $r['materia'],
            "tipo"    => $r['tipo'],
            "docente" => trim($r['docente_nombre'] . " " . $r['docente_ap']),
            "turno"   => $r['turno'],
            "horario" => []
        ];
    }

    $materias[$r['id_materia']]["horario"][] = [
        "dia"    => $r["dia_semana"],
        "inicio" => $r["hora_inicio"],
        "fin"    => $r["hora_fin"]
    ];
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Materias del Alumno</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
body { background:#eef2f7; font-family:Segoe UI; padding:30px; }
.container {
    max-width:1000px; margin:auto;
    background:white; padding:25px;
    border-radius:12px; box-shadow:0 3px 10px rgba(0,0,0,0.12);
}
h2 { color:#003f7f; }
.materia-card {
    background:white;
    border-left:6px solid #004b97;
    padding:18px;
    border-radius:10px;
    margin-top:15px;
    box-shadow:0 2px 8px rgba(0,0,0,0.10);
}
.materia-card h3 {
    margin:0; color:#002d63;
}
.tipo { color:#666; font-size:0.9em; margin-bottom:6px; }
.docente { margin-top:6px; font-weight:bold; }
.horario { margin-top:6px; }
.horario-item {
    background:#eef6ff;
    padding:6px;
    border-radius:6px;
    margin-top:4px;
    font-size:0.9em;
}
.turno-box {
    margin-top:15px; font-size:1.1em;
    padding:10px; background:#004b97; color:white;
    border-radius:8px; display:inline-block;
}
</style>

</head>
<body>

<div class="container">

<h2>
    <i class="fa-solid fa-book-open-reader"></i>
    Materias de <?= $alumno['nombre'] . " " . $alumno['apellido_paterno'] ?>
</h2>

<p class="turno-box">
    Grupo: <?= $grp['grupo'] ?> — Turno: <?= $grp['turno'] ?>
</p>

<?php foreach ($materias as $m): ?>

<div class="materia-card">
    <h3><?= $m['materia'] ?></h3>
    <div class="tipo">Tipo: <?= $m['tipo'] ?></div>

    <div class="docente">
        <i class="fa-solid fa-chalkboard-user"></i>
        Docente: <?= $m['docente'] ?>
    </div>

    <div class="horario"><strong>Horario:</strong>
        <?php foreach ($m['horario'] as $h): ?>
            <div class="horario-item">
                <?= $h['dia'] ?> — <?= $h['inicio'] ?> a <?= $h['fin'] ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php endforeach; ?>

</div>

</body>
</html>
