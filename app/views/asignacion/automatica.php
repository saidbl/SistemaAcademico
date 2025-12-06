<?php
session_start();
require_once __DIR__ . '/../../helper/session_helper.php';
require_once __DIR__ . '/../../helper/auth_helper.php';
require_once __DIR__ . '/../../config/database.php';

exigirSesionActiva();
exigirRol('Administrador');

// Obtener lista de grupos
$grupos = $pdo->query("SELECT id_grupo, nombre FROM grupos ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Generador y Vista de Horarios</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
body {
    background: #f5f7fb;
    font-family: "Segoe UI", Arial, sans-serif;
    margin: 0;
    color: #333;
}

.topbar {
    background: #002e5b;
    color: white;
    padding: 14px 30px;
    font-size: 20px;
    font-weight: bold;
}

.container {
    max-width: 1400px;
    margin: 40px auto;
    background: white;
    padding: 35px;
    border-radius: 14px;
    box-shadow: 0 3px 10px rgba(0,0,0,0.08);
}

h2 {
    color: #002e5b;
    font-size: 1.8em;
    margin-bottom: 15px;
}

.btn-run {
    background: #0077cc;
    color: white;
    border: none;
    padding: 12px 20px;
    border-radius: 8px;
    cursor: pointer;
    font-size: 16px;
    margin-bottom: 20px;
}
.btn-run:hover { background: #005fa3; }

select {
    padding: 10px;
    border-radius: 6px;
    border: 1px solid #ccc;
    font-size: 15px;
    margin-bottom: 20px;
}

#horario {
    margin-top: 30px;
    display: none;
}

table.horario {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}

table.horario th {
    background: #002e5b;
    color: white;
    padding: 12px;
    text-align: center;
}

table.horario td {
    border: 1px solid #ddd;
    padding: 12px;
    min-height: 80px;
    vertical-align: top;
}

.bloque-hora {
    font-weight: bold;
    color: #004b97;
}

.success-msg {
    padding: 10px;
    background: #d1fae5;
    border: 1px solid #10b981;
    color: #065f46;
    border-radius: 8px;
    margin-bottom: 15px;
}
</style>
</head>
<body>

<div class="topbar">
    <i class="fa-solid fa-calendar-check"></i> Generación y Consulta de Horarios
</div>

<div class="container">

    <h2><i class="fa-solid fa-bolt"></i> Generar Horarios</h2>

    <button class="btn-run" onclick="generarHorarios()">
        <i class="fa-solid fa-play"></i> Ejecutar Generación Completa de Horarios
    </button>

    <div id="genMsg"></div>

    <h2><i class="fa-solid fa-table"></i> Ver Horario por Grupo</h2>

    <label>Selecciona un grupo:</label>
    <select id="grupoSelect" onchange="cargarHorario()">
        <option value="">-- Seleccionar grupo --</option>
        <?php foreach($grupos as $g): ?>
            <option value="<?= $g['id_grupo'] ?>"><?= $g['nombre'] ?></option>
        <?php endforeach; ?>
    </select>

    <div id="horario">
        <h3 id="tituloHorario"></h3>

        <table class="horario">
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
            <tbody id="tablaHorario"></tbody>
        </table>
    </div>

</div>

<script>
async function generarHorarios() {
    const res = await fetch("/SistemaAcademico/app/controllers/AsignacionAutomaticaController.php");
    const json = await res.json();

    document.getElementById("genMsg").innerHTML =
        `<div class="success-msg">Horarios generados correctamente (${json.total_asignaciones} asignaciones).</div>`;
}

async function cargarHorario() {
    const grupo = document.getElementById("grupoSelect").value;
    if (!grupo) return;

    const res = await fetch(`/SistemaAcademico/app/views/asignacion/horarioGrupo.php?id_grupo=${grupo}`);
    const data = await res.json();

    document.getElementById("horario").style.display = "block";
    document.getElementById("tituloHorario").innerText = "Horario de " + data.grupo + " (" + data.turno + ")";

    // BLOQUES DEPENDEN DEL TURNO
    let bloques;

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

    const dias = ["Lunes","Martes","Miercoles","Jueves","Viernes"];

    let html = "";
    for (let b = 0; b < 5; b++) {
        html += `<tr><td class="bloque-hora">${bloques[b]}</td>`;

        dias.forEach(dia => {
            const celda = data.horario[dia] && data.horario[dia][b]
                ? `<strong>${data.horario[dia][b].materia}</strong><br>
                   <small>${data.horario[dia][b].docente}</small>`
                : "";
            html += `<td>${celda}</td>`;
        });

        html += "</tr>";
    }

    document.getElementById("tablaHorario").innerHTML = html;
}
</script>

</body>
</html>
