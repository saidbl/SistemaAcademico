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
<title>Mis Materias e Historial</title>
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
}

.section-title {
    font-size: 1.4em;
    color: #002d63;
    margin-top: 25px;
    margin-bottom: 10px;
}

.grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
    gap: 20px;
    margin-top: 15px;
}

.card {
    background: #fdfdfd;
    padding: 18px;
    border-radius: 12px;
    box-shadow: 0 3px 8px rgba(0,0,0,0.12);
    border-left: 5px solid #004b97;
}

.card h3 {
    margin: 0;
    font-size: 1.2em;
    color: #002e5b;
}

.card .docente {
    margin-top: 8px;
    font-size: 0.9em;
    color: #555;
}

.hist-block {
    margin-top: 25px;
    padding: 15px;
    border-radius: 12px;
    background: #f5f9ff;
    border: 1px solid #cbd9f0;
}

.hist-title {
    font-size: 1.2em;
    color: #004b97;
    margin-bottom: 10px;
}

.materia-item {
    margin-left: 10px;
    padding: 6px 0;
    border-bottom: 1px solid #ddd;
}

.materia-item small {
    color: #555;
}

</style>

</head>
<body>

<div class="container">

    <h2><i class="fa-solid fa-book-open-reader"></i> Mis Materias & Historial Académico</h2>

    <div id="grupoActual" style="margin-bottom:20px; font-size:1.1em;"></div>

    <h3 class="section-title">Materias actuales</h3>
    <div id="materiasActuales" class="grid"></div>

    <h3 class="section-title">Historial de materias cursadas</h3>
    <div id="historial"></div>

</div>

<script>
async function cargarDatos() {

    const res = await fetch("misMateriasHistoricoData.php");
    const data = await res.json();

    // -----------------------
    // GRUPO ACTUAL
    // -----------------------
    document.getElementById("grupoActual").innerHTML =
        `<strong>Grupo actual:</strong> ${data.grupo} 
         &nbsp;&nbsp;<strong>Turno:</strong> ${data.turno}`;

    // -----------------------
    // MATERIAS ACTUALES
    // -----------------------
    const contActual = document.getElementById("materiasActuales");
    contActual.innerHTML = "";

    data.materias_actuales.forEach(m => {
        contActual.innerHTML += `
            <div class="card">
                <h3>${m.nombre}</h3>
                <div class="docente"><strong>Docente:</strong> ${m.docente}</div>
                <div><strong>Tipo:</strong> ${m.tipo}</div>
            </div>
        `;
    });

    // -----------------------
    // HISTORIAL
    // -----------------------
    const contHist = document.getElementById("historial");
    contHist.innerHTML = "";

    data.historial.forEach(entry => {

        let html = `
            <div class="hist-block">
            <div class="hist-title">
                <i class="fa-solid fa-clock-rotate-left"></i> 
                Grupo: ${entry.grupo} (${entry.turno}) — Fecha: ${entry.fecha}
            </div>
        `;

        entry.materias.forEach(mat => {

            let calificaciones = mat.calificaciones.map(c => 
                `<small>Periodo: ${c.periodo} — Calificación: <strong>${c.calificacion}</strong></small>`
            ).join("<br>");

            if (!calificaciones) calificaciones = "<small>Sin calificaciones registradas</small>";

            html += `
                <div class="materia-item">
                    <strong>${mat.nombre}</strong><br>
                    ${calificaciones}
                </div>
            `;
        });

        html += "</div>";

        contHist.innerHTML += html;
    });
}

cargarDatos();
</script>

</body>
</html>
