<?php
session_start();
require_once __DIR__ . '/../../helper/session_helper.php';
require_once __DIR__ . '/../../helper/auth_helper.php';
exigirSesionActiva();
exigirRol('Padre');

require_once __DIR__ . '/../../config/database.php';

$id_usuario = $_SESSION['usuario_id'];

// ======================
// Obtener datos del tutor
// ======================
$stmt = $pdo->prepare("
    SELECT 
        nombre,
        apellido_paterno,
        apellido_materno,
        correo_institucional,
        correo_personal,
        telefono,
        direccion,
        fecha_nacimiento,
        fecha_ingreso
    FROM usuarios
    WHERE id_usuario = ?
");
$stmt->execute([$id_usuario]);
$u = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Mi Perfil</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
body { background:#eef2f7; font-family:Segoe UI; padding:30px; }
.container {
    max-width:700px; margin:auto; background:white;
    padding:25px; border-radius:12px;
    box-shadow:0 2px 8px rgba(0,0,0,0.12);
}
h2 { color:#003f7f; margin-bottom:20px; }

label { font-weight:bold; margin-top:10px; display:block; }
input, textarea {
    width:100%; padding:10px; margin-top:4px;
    border-radius:8px; border:1px solid #ccc;
}
button {
    background:#004b97; color:white; padding:10px 15px;
    border:none; border-radius:8px; margin-top:15px;
    cursor:pointer;
}
button:hover { background:#003a78; }

.info-box {
    padding:10px; background:#f5f5f5; border-radius:8px;
    margin-bottom:15px; border-left:4px solid #004b97;
}
</style>

</head>
<body>

<div class="container">

<h2><i class="fa-solid fa-id-card"></i> Mi Perfil</h2>

<div class="info-box">
    <strong>Nombre:</strong> <?= $u['nombre'] . " " . $u['apellido_paterno'] . " " . $u['apellido_materno'] ?><br>
    <strong>Fecha de nacimiento:</strong> <?= $u['fecha_nacimiento'] ?><br>
    <strong>Fecha de ingreso:</strong> <?= $u['fecha_ingreso'] ?><br>
    <strong>Correo institucional:</strong> <?= $u['correo_institucional'] ?>
</div>

<h3>Información editable</h3>

<form id="formPerfil">
    <label>Correo personal:</label>
    <input type="email" name="correo_personal" value="<?= $u['correo_personal'] ?>">

    <label>Teléfono:</label>
    <input type="text" name="telefono" value="<?= $u['telefono'] ?>">

    <label>Dirección:</label>
    <textarea name="direccion"><?= $u['direccion'] ?></textarea>

    <button type="submit">Guardar cambios</button>
</form>

<div id="msg"></div>

</div>

<script>
document.getElementById("formPerfil").addEventListener("submit", async (e) => {
    e.preventDefault();

    const data = new FormData(e.target);

    const res = await fetch("actualizar_tutor.php", {
        method: "POST",
        body: data
    });

    const json = await res.json();

    if (json.status === "ok") {
        document.getElementById("msg").innerHTML =
            "<div style='margin-top:15px;padding:10px;background:#d1fae5;color:#065f46;border-left:4px solid #10b981;border-radius:6px;'>Cambios guardados correctamente.</div>";
    }
});
</script>

</body>
</html>
