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
<title>Mis Calificaciones</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>

body {
    background: #eef2f7;
    font-family: "Segoe UI", Arial, sans-serif;
    margin: 0;
    padding: 25px;
}

.container {
    max-width: 1100px;
    background: white;
    padding: 30px;
    margin: 0 auto;
    border-radius: 14px;
    box-shadow: 0 3px 10px rgba(0,0,0,0.15);
}

h2 {
    color: #003b78;
    font-size: 2em;
    margin-bottom: 20px;
}

.card {
    background: #ffffff;
    padding: 16px;
    margin-top: 12px;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.12);
    border-left: 5px solid #004b97;
}

.card h3 {
    margin: 0;
    font-size: 1.2em;
    color: #002e5b;
}

.card small {
    color: #555;
}

</style>
</head>

<body>

<div class="container">

    <h2><i class="fa-solid fa-file-pen"></i> Mis Calificaciones</h2>

    <div id="lista"></div>

</div>

<script>
async function cargarCalificaciones() {

    const res = await fetch("misCalificacionesData.php");
    const data = await res.json();

    const cont = document.getElementById("lista");
    cont.innerHTML = "";

    if (data.materias.length === 0) {
        cont.innerHTML = "<p>No tienes materias registradas actualmente.</p>";
        return;
    }

    data.materias.forEach(mat => {

        cont.innerHTML += `
            <div class="card">
                <h3>${mat.nombre}</h3>
                <small><strong>Tipo:</strong> ${mat.tipo}</small><br><br>

                <strong>Calificación: ${
                    mat.calificacion !== null 
                    ? mat.calificacion 
                    : "<em>Aún no asignada</em>"
                }</strong>
            </div>
        `;
    });
}

cargarCalificaciones();
</script>

</body>
</html>
