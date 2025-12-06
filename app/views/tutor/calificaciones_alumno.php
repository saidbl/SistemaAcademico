<?php
session_start();
require_once __DIR__ . '/../../helper/session_helper.php';
require_once __DIR__ . '/../../helper/auth_helper.php';
exigirSesionActiva();
exigirRol('Padre');

require_once __DIR__ . '/../../config/database.php';

// ===============================
// 1. OBTENER ALUMNO ASIGNADO
// ===============================
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

// ===============================
// 2. OBTENER GRUPO ACTUAL DEL ALUMNO
// ===============================
$stmt = $pdo->prepare("
    SELECT r.id_grupo, g.nombre AS grupo
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

// ===============================
// 3. OBTENER MATERIAS DEL GRUPO
// ===============================
$stmt = $pdo->prepare("
    SELECT DISTINCT 
        m.id_materia,
        m.nombre AS materia
    FROM horarios h
    JOIN materias m ON m.id_materia = h.id_materia
    WHERE h.id_grupo = ?
      AND m.nombre <> 'Descanso'
    ORDER BY m.nombre
");
$stmt->execute([$id_grupo]);
$materias = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ===============================
// 4. TRAER CALIFICACIONES POR CADA MATERIA
// ===============================
$calificaciones = [];

foreach ($materias as $m) {
    $stmt = $pdo->prepare("
        SELECT periodo, calificacion
        FROM calificaciones
        WHERE id_alumno = ?
          AND id_materia = ?
        ORDER BY periodo DESC
        LIMIT 1
    ");
    $stmt->execute([$id_alumno, $m['id_materia']]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    $calificaciones[] = [
        "materia" => $m['materia'],
        "periodo" => $row['periodo'] ?? "—",
        "calificacion" => $row['calificacion'] ?? "Sin captura"
    ];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Calificaciones del Alumno</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
body { background:#eef2f7; font-family:Segoe UI; padding:30px; }
.container {
    max-width:900px; margin:auto;
    background:white; padding:25px;
    border-radius:12px; box-shadow:0 3px 10px rgba(0,0,0,0.12);
}
h2 { color:#003f7f; }
table { width:100%; border-collapse:collapse; margin-top:20px; }
th, td {
    border:1px solid #ddd; padding:10px;
}
th { background:#004b97; color:white; }
.calif-ok { color:#059669; font-weight:bold; }
.calif-pend { color:#b91c1c; font-weight:bold; }
</style>
</head>
<body>

<div class="container">

<h2>
    <i class="fa-solid fa-file-lines"></i>
    Calificaciones de <?= $alumno['nombre'] . " " . $alumno['apellido_paterno'] ?>
</h2>

<p><strong>Grupo actual:</strong> <?= $grp['grupo'] ?></p>

<table>
<tr>
    <th>Materia</th>
    <th>Periodo</th>
    <th>Calificación</th>
</tr>

<?php foreach ($calificaciones as $c): ?>

<tr>
    <td><?= $c['materia'] ?></td>
    <td><?= $c['periodo'] ?></td>
    <td class="<?= $c['calificacion']=="Sin captura" ? "calif-pend" : "calif-ok" ?>">
        <?= $c['calificacion'] ?>
    </td>
</tr>

<?php endforeach; ?>

</table>

</div>

</body>
</html>
