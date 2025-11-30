<?php
session_start();
require_once __DIR__ . '/../../helper/session_helper.php';
require_once __DIR__ . '/../../helper/auth_helper.php';
exigirSesionActiva();
exigirRol('Administrador');
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Reinscripciones e Inscripciones</title>
<style>
body { font-family: Arial; background:#f4f4f4; padding:20px; }
.box { background:white; padding:30px; border-radius:12px; width:600px; margin:auto; }
button{ padding:12px 18px; margin:5px; border:0; background:#004b97; color:white; border-radius:6px; cursor:pointer; }
input{ padding:10px; width:100%; margin:6px 0 12px; }
</style>
</head>
<body>

<div class="box">
<h2>Procesos de Reinscripción</h2>

<form action="/SistemaAcademico/app/controllers/procesar_reinscripciones.php" method="POST">

    <label>Periodo:</label>
    <input type="text" name="periodo" value="<?= date('Y') . '-1' ?>" required>

    <label>Inicio para citas (RF-026):</label>
    <input type="datetime-local" name="inicio" value="<?= date('Y') ?>-01-10T08:00">

    <br><br>

    <button name="accion" value="todo">Ejecutar TODO el Proceso</button>
    <button name="accion" value="rf025">Inscribir niveles básicos (RF-025)</button>
    <button name="accion" value="rf026">Generar citas Prepa/Uni (RF-026)</button>
    <button name="accion" value="rf028">Generar recursamientos (RF-028)</button>
</form>

</div>

</body>
</html>
