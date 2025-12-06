<?php
session_start();
require_once __DIR__ . '/../../helper/session_helper.php';
require_once __DIR__ . '/../../helper/auth_helper.php';
exigirSesionActiva();
exigirRol('Alumno');
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Editar mi perfil</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
body {
    background: #eef2f7;
    font-family: "Segoe UI", sans-serif;
    padding: 30px;
}
.container {
    max-width: 600px;
    margin: auto;
    background: white;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 3px 10px rgba(0,0,0,0.15);
}
h2 {
    color: #003b78;
    margin-bottom: 20px;
}
label {
    font-weight: 600;
    margin-top: 10px;
    display: block;
}
input {
    width: 100%;
    padding: 10px;
    margin-top: 4px;
    border-radius: 8px;
    border: 1px solid #ccc;
}
button {
    margin-top: 20px;
    padding: 12px;
    border: none;
    background: #004b97;
    color: white;
    border-radius: 8px;
    cursor: pointer;
}
button:hover {
    background: #003a78;
}
.success {
    margin-top: 15px;
    padding: 10px;
    background: #d1fae5;
    color: #065f46;
    border-left: 5px solid #10b981;
    border-radius: 6px;
}
</style>
</head>

<body>
<div class="container">

    <h2>Editar mi información</h2>

    <form id="formPerfil">

        <label>Correo personal:</label>
        <input type="email" name="correo_personal">

        <label>Teléfono (opcional):</label>
        <input type="text" name="telefono">

        <button type="submit">Guardar cambios</button>
    </form>

    <div id="msg"></div>
</div>

<script>
document.getElementById("formPerfil").addEventListener("submit", async (e) => {
    e.preventDefault();

    const data = new FormData(e.target);

    const res = await fetch("actualizarPerfil.php", {
        method: "POST",
        body: data
    });

    const json = await res.json();

    document.getElementById("msg").innerHTML =
        `<div class="success">Cambios guardados correctamente.</div>`;
});
</script>

</body>
</html>
