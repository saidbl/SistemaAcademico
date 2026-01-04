<?php
session_start();
require_once __DIR__ . '/../../helper/session_helper.php';
require_once __DIR__ . '/../../config/database.php';
exigirSesionActiva();

$id = $_SESSION['usuario_id'];

$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id_usuario = ?");
$stmt->execute([$id]);
$u = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Mi Perfil</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
body{background:#f5f7fb;font-family:'Segoe UI';margin:0}
.container{max-width:500px;margin:40px auto;background:white;padding:30px;border-radius:12px;box-shadow:0 3px 12px rgba(0,0,0,.1)}
h2{color:#002e5b;margin-bottom:20px}
label{font-weight:600}
input{width:100%;padding:10px;margin-bottom:12px;border-radius:6px;border:1px solid #ccc}
button{background:#004b97;color:white;padding:10px;border:none;border-radius:6px;width:100%}
</style>
</head>
<body>

<div class="container">
<h2><i class="fa-solid fa-user"></i> Mi Perfil</h2>

<form action="actualizar.php" method="POST">
<label>Nombre</label>
<input value="<?=$u['nombre']?>" disabled>

<label>Correo</label>
<input name="correo" value="<?=$u['correo_institucional']?>" required>

<label>Nueva contraseña</label>
<input type="password" name="password">

<button>Guardar cambios</button>
</form>
</div>

</body>
</html>
