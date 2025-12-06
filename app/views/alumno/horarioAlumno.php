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
<title>Mi Horario</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
body {
    background: #eef2f7;
    font-family: "Segoe UI", Arial, sans-serif;
    margin: 0;
    padding: 25px;
}
.container {
    background: white;
    max-width: 1100px;
    margin: 0 auto;
    padding: 30px;
    border-radius: 14px;
    box-shadow: 0 3px 10px rgba(0,0,0,0.12);
}
h2 {
    color: #002e5b;
    font-size: 1.8em;
    display: flex;
    align-items: center;
    gap: 10px;
}
#descargar {
    background: #0077cc;
    color: white;
    padding: 12px 20px;
    border-radius: 8px;
    text-decoration: none;
    float: right;
    margin-bottom: 20px;
}
#descargar:hover { background: #005fa3; }
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 25px;
}
th {
    background: #004b97;
    color: white;
    padding: 12px;
}
td {
    border: 1px solid #ccc;
    padding: 14px;
    min-height: 75px;
}
.bloque-hora {
    background: #eaf2ff;
    font-weight: bold;
    color: #004b97;
    text-align: center;
}
.materia { font-weight: bold; color: #002e5b; }
.docente { color: #666; font-size: 0.9em; }
</style>

</head>
<body>

<div class="container">

    <h2><i class="fa-solid fa-calendar-days"></i> Mi Horario</h2>

    <h3 id="titulo"></h3>

    <a id="descargar" href="horarioAlumnoPDF.php" target="_blank">
        <i class="fa-solid fa-file-pdf"></i> Descargar PDF
    </a>

    <table>
        <thead>
            <tr>
                <th>Bloque</th>
                <th>Lunes</th>
                <th>Martes</th>
                <th>Miércoles</th>
                <th>Jueves</th>
                <th>Viernes</th>
            </tr>
        </thead>
        <tbody id="tabla"></tbody>
    </table>

</div>

<script>
async function cargarHorario() {
    const res = await fetch("horarioAlumnoData.php");
    const data = await res.json();

    document.getElementById("titulo").innerText =
        `${data.grupo} (${data.turno})`;

    let bloques = [];

    if (data.turno === "Vespertino") {
        bloques = [
            "15:00 - 16:30",
            "16:30 - 18:00",
            "18:00 - 19:00",
            "19:00 - 20:30",
            "20:30 - 22:00"
        ];
    } else {
        bloques = [
            "07:00 - 08:30",
            "08:30 - 10:00",
            "10:30 - 12:00",
            "12:00 - 13:30",
            "13:30 - 15:00"
        ];
    }

    const dias = ["Lunes", "Martes", "Miercoles", "Jueves", "Viernes"];
    let html = "";

    for (let b = 0; b < 5; b++) {
        html += `<tr><td class="bloque-hora">${bloques[b]}</td>`;

        dias.forEach(dia => {
            const cell = data.horario[dia][b];

            html += `<td>${cell ? `
                <div class="materia">${cell.materia}</div>
                <div class="docente">${cell.docente}</div>
            ` : ""}</td>`;
        });

        html += "</tr>";
    }

    document.getElementById("tabla").innerHTML = html;
}

cargarHorario();
</script>

</body>
</html>
