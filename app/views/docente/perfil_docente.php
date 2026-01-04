<?php
session_start();
require_once __DIR__ . '/../../helper/session_helper.php';
require_once __DIR__ . '/../../helper/auth_helper.php';
require_once __DIR__ . '/../../config/database.php';

exigirSesionActiva();
exigirRol('Docente');

$idUsuario = $_SESSION['usuario_id'];

/* Datos del docente */
$stmt = $pdo->prepare("
SELECT u.nombre, u.apellido_paterno, u.apellido_materno, u.correo_institucional
FROM usuarios u
WHERE u.id_usuario = ?
");
$stmt->execute([$idUsuario]);
$docente = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Mi Perfil</title>
<style>
body{font-family:'Segoe UI';background:#f5f7fb}
.box{background:white;width:500px;margin:40px auto;padding:25px;border-radius:12px}
label{font-weight:600}
input,button{width:100%;padding:10px;margin-top:8px}
button{background:#004b97;color:white;border:none;border-radius:6px}
</style>
</head>
<body>

<div class="box">
<h3>👤 Mi Perfil (Docente)</h3>

<form action="perfil_guardar.php" method="POST">
<label>Nombre</label>
<input value="<?=htmlspecialchars($docente['nombre'])?>" disabled>

<label>Correo institucional</label>
<input name="correo" value="<?=htmlspecialchars($docente['correo_institucional'])?>" required>

<label>Nueva contraseña</label>
<input type="password" name="password" placeholder="Opcional">

<button>Guardar cambios</button>
</form>

<a href="/SistemaAcademico/app/views/dashboard/docente_dashboard.php">⬅ Volver</a>
</div>

</body>
</html>
