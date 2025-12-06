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
<title>Tomar Asistencia</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
body { background:#eef2f7; padding:35px; font-family:Segoe UI; }
.container {
    background:white; max-width:1100px; margin:auto;
    padding:30px; border-radius:14px;
    box-shadow:0px 3px 10px rgba(0,0,0,0.15)
}
select, input {
    padding:10px; width:100%; font-size:16px;
    margin-top:6px; border-radius:8px; border:1px solid #ccc;
}
button {
    background:#004b97; color:white; border:none;
    padding:10px 15px; border-radius:8px; cursor:pointer;
    margin-top:15px;
}
button:hover { background:#003a78; }

.estado-btn {
    padding:7px 12px; margin-right:4px; cursor:pointer;
    border-radius:6px; border:none; font-size:13px;
}
.presente { background:#4ade80; }
.retardo  { background:#facc15; }
.falta    { background:#f87171; }

table { width:100%; border-collapse:collapse; margin-top:20px; }
th, td { padding:10px; border:1px solid #ccc; }
th { background:#004b97; color:white; }
</style>
</head>

<body>
<div class="container">

<h2><i class="fa-solid fa-calendar-check"></i> Tomar Asistencia</h2>

<label>Materia:</label>
<select id="materiaSelect"></select>

<label>Grupo:</label>
<select id="grupoSelect"></select>

<label>Fecha:</label>
<input type="date" id="fecha" value="<?= date('Y-m-d') ?>">

<button onclick="cargarAlumnos()">Cargar Lista</button>

<div id="lista"></div>

</div>

<script>
let materiasDocente = [];

async function cargarMaterias() {
    const res = await fetch("asistencia/materiasAsignadas.php");
    materiasDocente = await res.json();

    const matSel = document.getElementById("materiaSelect");
    matSel.innerHTML = "<option value=''>Seleccione</option>";

    materiasDocente.forEach(m => {
        matSel.innerHTML += `<option value="${m.id_materia}">${m.materia}</option>`;
    });
}

document.getElementById("materiaSelect").addEventListener("change", () => {
    const idMateria = document.getElementById("materiaSelect").value;
    const grupoSel = document.getElementById("grupoSelect");

    grupoSel.innerHTML = "";

    materiasDocente.forEach(m => {
        if (m.id_materia == idMateria) {
            grupoSel.innerHTML += `<option value="${m.id_grupo}">${m.grupo}</option>`;
        }
    });
});

async function cargarAlumnos() {
    const materia = document.getElementById("materiaSelect").value;
    const grupo   = document.getElementById("grupoSelect").value;
    const fecha   = document.getElementById("fecha").value;

    if (!materia || !grupo || !fecha) {
        alert("Completa materia, grupo y fecha.");
        return;
    }

    const res = await fetch(`asistencia/alumnosDelGrupo.php?grupo=${grupo}&materia=${materia}&fecha=${fecha}`);
    const alumnos = await res.json();

    let html = `
        <table>
            <tr>
                <th>Alumno</th>
                <th>Presente</th>
                <th>Retardo</th>
                <th>Falta</th>
            </tr>
    `;

    alumnos.forEach(a => {
        html += `
            <tr>
                <td>${a.nombre} ${a.apellido_paterno} ${a.apellido_materno}</td>
                <td><input type="radio" name="a${a.id_alumno}" value="Presente" ${a.estado=="Presente"?"checked":""}></td>
                <td><input type="radio" name="a${a.id_alumno}" value="Retardo" ${a.estado=="Retardo"?"checked":""}></td>
                <td><input type="radio" name="a${a.id_alumno}" value="Falta" ${a.estado=="Falta"?"checked":""}></td>
            </tr>
        `;
    });

    html += `
        </table>
        <button onclick="guardar(${materia}, '${fecha}')">Guardar Asistencia</button>
    `;

    document.getElementById("lista").innerHTML = html;
}

async function guardar(id_materia, fecha) {
    const radios = document.querySelectorAll("input[type=radio]:checked");
    let asistencias = [];

    radios.forEach(r => {
        asistencias.push({
            id_alumno: r.name.substring(1),
            estado: r.value
        });
    });

    const res = await fetch("asistencia/guardarAsistencia.php", {
        method: "POST",
        body: JSON.stringify({
            id_materia,
            fecha,
            asistencias
        })
    });

    alert("Asistencia guardada correctamente.");
}

cargarMaterias();
</script>

</body>
</html>
