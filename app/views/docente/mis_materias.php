<?php
session_start();
require_once __DIR__ . '/../../helper/session_helper.php';
require_once __DIR__ . '/../../helper/auth_helper.php';
exigirSesionActiva();
exigirRol('Docente');
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Mis Materias</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
body {
    background: #eef2f7;
    font-family: "Segoe UI", sans-serif;
    padding: 35px;
}
.container {
    max-width: 1100px;
    margin: auto;
    background: white;
    padding: 30px;
    border-radius: 16px;
    box-shadow: 0 3px 10px rgba(0,0,0,0.15);
}
h2 {
    color: #004b97;
    font-size: 2em;
    margin-bottom: 25px;
    display:flex;
    align-items:center;
    gap:10px;
}
.card {
    background: #ffffff;
    margin-top: 18px;
    padding: 18px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.10);
    border-left: 5px solid #004b97;
}
.card h3 {
    margin: 0;
    font-size: 1.4em;
    color: #002d63;
}
.sub {
    font-size: 0.95em;
    margin-top: 5px;
    color:#555;
}
.horario {
    margin-top: 10px;
    font-size: 0.9em;
}
.btns {
    margin-top: 15px;
}
.btns a {
    padding: 10px 12px;
    background: #004b97;
    color: white;
    border-radius: 7px;
    text-decoration: none;
    font-size: 0.9em;
    margin-right: 10px;
}
.btns a:hover {
    background: #003a78;
}
</style>
</head>

<body>
<div class="container">

    <h2><i class="fa-solid fa-book"></i> Mis Materias</h2>

    <div id="contenido">Cargando...</div>

</div>

<script>
async function cargarMaterias() {
    const cont = document.getElementById("contenido");

    const res = await fetch("mis_materiasData.php");
    const data = await res.json();

    cont.innerHTML = "";

    if (!Array.isArray(data) || data.length === 0) {
        cont.innerHTML = "<p>No tienes materias asignadas.</p>";
        return;
    }

    data.forEach(m => {

        let horariosHTML = "";
        m.horario.forEach(h => {
            horariosHTML += `
                <div>${h.dia}: ${h.inicio} - ${h.fin}</div>
            `;
        });

        cont.innerHTML += `
            <div class="card">
                <h3>${m.materia}</h3>
                <div class="sub">
                    Grupo: <strong>${m.grupo}</strong> — Turno: <strong>${m.turno}</strong><br>
                    Tipo: ${m.tipo}<br>
                    Alumnos inscritos: ${m.total_alumnos}
                </div>

                <div class="horario">
                    <strong>Horario:</strong><br>
                    ${horariosHTML}
                </div>

                <div class="btns">
                    <a href="#">Tomar asistencia</a>
                    <a href="#">Capturar calificaciones</a>
                </div>
            </div>
        `;
    });
}

cargarMaterias();
</script>

</body>
</html>
