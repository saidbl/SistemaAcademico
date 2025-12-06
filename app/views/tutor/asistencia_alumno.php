<?php
session_start();
require_once __DIR__ . '/../../helper/session_helper.php';
require_once __DIR__ . '/../../helper/auth_helper.php';
exigirSesionActiva();
exigirRol('Padre');

require_once __DIR__ . '/../../config/database.php';

// =======================================
// 1. OBTENER ALUMNO DEL TUTOR
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
// 2. OBTENER TODAS LAS ASISTENCIAS DEL ALUMNO
// =======================================
$stmt = $pdo->prepare("
    SELECT 
        a.fecha,
        a.estado,
        m.nombre AS materia
    FROM asistencias a
    JOIN materias m ON m.id_materia = a.id_materia
    WHERE a.id_alumno = ?
    ORDER BY a.fecha DESC
");
$stmt->execute([$id_alumno]);
$registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

// =======================================
// 3. CONTADORES
// =======================================
$total_presente = 0;
$total_falta    = 0;
$total_retardo  = 0;

foreach ($registros as $r) {
    if ($r['estado'] == "Presente") $total_presente++;
    if ($r['estado'] == "Falta")    $total_falta++;
    if ($r['estado'] == "Retardo")  $total_retardo++;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Asistencias del Alumno</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
body { background:#eef2f7; font-family:Segoe UI; padding:30px; }
.container {
    background:white;
    padding:25px;
    max-width:900px;
    margin:auto;
    border-radius:12px;
    box-shadow:0 3px 10px rgba(0,0,0,0.12);
}
h2 { color:#003f7f; }

.stats {
    display:flex;
    gap:20px;
    margin-top:20px;
}
.stat-box {
    flex:1;
    background:#fff;
    padding:15px;
    border-radius:10px;
    box-shadow:0 2px 6px rgba(0,0,0,0.10);
    text-align:center;
}
.stat-box h3 { margin:0; font-size:1.3em; }
.pres { color:#16a34a; font-weight:bold; }
.falta { color:#dc2626; font-weight:bold; }
.ret { color:#d97706; font-weight:bold; }

table {
    width:100%; border-collapse:collapse;
    margin-top:30px;
}
th, td {
    border:1px solid #ccc; padding:10px;
}
th { background:#004b97; color:white; }
.estado {
    font-weight:bold;
}
.estado.Presente { color:#16a34a; }
.estado.Falta { color:#dc2626; }
.estado.Retardo { color:#d97706; }
</style>

</head>
<body>
<div class="container">

<h2>
    <i class="fa-solid fa-user-check"></i>
    Asistencias de <?= $alumno['nombre'] . " " . $alumno['apellido_paterno'] ?>
</h2>

<!-- ===============================
     ESTADÍSTICAS
================================ -->
<div class="stats">
    <div class="stat-box">
        <h3 class="pres"><?= $total_presente ?></h3>
        Presentes
    </div>

    <div class="stat-box">
        <h3 class="ret"><?= $total_retardo ?></h3>
        Retardos
    </div>

    <div class="stat-box">
        <h3 class="falta"><?= $total_falta ?></h3>
        Faltas
    </div>
</div>

<!-- ===============================
     TABLA DE ASISTENCIAS
================================ -->
<table>
    <tr>
        <th>Fecha</th>
        <th>Materia</th>
        <th>Estado</th>
    </tr>

    <?php foreach ($registros as $r): ?>
    <tr>
        <td><?= $r['fecha'] ?></td>
        <td><?= $r['materia'] ?></td>
        <td class="estado <?= $r['estado'] ?>"><?= $r['estado'] ?></td>
    </tr>
    <?php endforeach; ?>

</table>

</div>
</body>
</html>
