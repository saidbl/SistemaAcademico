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
<title>Captura de Calificaciones</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
body { background:#eef2f7; font-family:Segoe UI; padding:25px; }
.container {
    max-width:1000px; margin:auto; background:white;
    padding:25px; border-radius:12px;
    box-shadow:0 3px 10px rgba(0,0,0,0.12);
}
select, input {
    padding:10px; font-size:15px; width:100%; margin-top:7px;
    border-radius:8px; border:1px solid #ccc;
}
button {
    padding:10px 18px; background:#004b97; color:white;
    border:none; border-radius:7px; margin-top:15px; cursor:pointer;
}
button:hover { background:#003a78; }
table {
    width:100%; border-collapse:collapse; margin-top:20px;
}
th, td {
    border:1px solid #ddd; padding:10px; text-align:left;
}
th { background:#004b97; color:white; }
</style>
</head>

<body>
<div class="container">

    <h2><i class="fa-solid fa-pen-to-square"></i> Captura de Calificaciones</h2>

    <label>Materia:</label>
    <select id="materiaSelect"></select>

    <label>Grupo:</label>
    <select id="grupoSelect"></select>

    <label>Periodo:</label>
    <input type="text" id="periodo" placeholder="Ej: 2025-P1">

    <button onclick="cargarAlumnos()">Cargar alumnos</button>

    <div id="tablaAlumnos"></div>

</div>

<script>
let materiasDocente = [];

async function cargarMaterias() {
    const res = await fetch("calificaciones/materiasDocente.php");
    materiasDocente = await res.json();

    const matSel = document.getElementById("materiaSelect");
    const gruposSel = document.getElementById("grupoSelect");

    let materiasUnicas = {};

    materiasDocente.forEach(m => {
        materiasUnicas[m.id_materia] = m.materia;
    });

    matSel.innerHTML = "<option value=''>Seleccione</option>";
    gruposSel.innerHTML = "<option value=''>Seleccione una materia primero</option>";

    for (let id in materiasUnicas) {
        matSel.innerHTML += `<option value="${id}">${materiasUnicas[id]}</option>`;
    }
}

document.getElementById("materiaSelect").addEventListener("change", () => {
    const idMateria = document.getElementById("materiaSelect").value;
    const gruposSel = document.getElementById("grupoSelect");

    gruposSel.innerHTML = "";

    materiasDocente.forEach(m => {
        if (m.id_materia == idMateria) {
            gruposSel.innerHTML += `<option value="${m.id_grupo}">${m.grupo}</option>`;
        }
    });
});

async function cargarAlumnos() {
    const materia = document.getElementById("materiaSelect").value;
    const grupo   = document.getElementById("grupoSelect").value;
    const periodo = document.getElementById("periodo").value;

    if (!materia || !grupo || !periodo) {
        alert("Selecciona materia, grupo y periodo.");
        return;
    }

    const res = await fetch(`calificaciones/listaAlumnos.php?materia=${materia}&grupo=${grupo}&periodo=${periodo}`);
    const alumnos = await res.json();

    let html = `
        <table>
            <tr>
                <th>Alumno</th>
                <th>Calificación</th>
            </tr>
    `;

    alumnos.forEach(a => {
        html += `
            <tr>
                <td>${a.nombre} ${a.apellido_paterno} ${a.apellido_materno}</td>
                <td>
                    <input type="number" step="0.1" min="0" max="10"
                           value="${a.calificacion ?? ''}"
                           data-id="${a.id_alumno}">
                </td>
            </tr>
        `;
    });

    html += `</table>
        <button onclick="guardar(${materia}, '${periodo}')">Guardar</button>
    `;

    document.getElementById("tablaAlumnos").innerHTML = html;
}

async function guardar(id_materia, periodo) {
    const inputs = document.querySelectorAll("input[data-id]");
    let califs = [];

    inputs.forEach(inp => {
        califs.push({
            id_alumno: inp.getAttribute("data-id"),
            calificacion: inp.value || null
        });
    });

    const res = await fetch("calificaciones/guardarCalificaciones.php", {
        method: "POST",
        body: JSON.stringify({
            id_materia,
            periodo,
            calificaciones: califs
        })
    });

    const json = await res.json();
    alert("Calificaciones guardadas correctamente.");
}

cargarMaterias();
</script>

</body>
</html>
