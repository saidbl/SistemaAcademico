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
<title>Mi asistencia</title>
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
    display: flex;
    align-items: center;
    gap: 10px;
}

.materia-card {
    background: #ffffff;
    padding: 18px;
    margin-top: 18px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.12);
    border-left: 5px solid #004b97;
}

.materia-card h3 {
    margin: 0 0 8px 0;
    font-size: 1.2em;
    color: #002e5b;
}

.badges {
    margin-bottom: 10px;
}

.badge {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 999px;
    font-size: 0.8em;
    margin-right: 5px;
    color: #fff;
}

.badge-presente { background: #16a34a; }
.badge-falta    { background: #dc2626; }
.badge-retardo  { background: #f59e0b; }

.porcentaje {
    font-weight: bold;
    color: #065f46;
    margin-top: 4px;
}

.table-registros {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
    font-size: 0.9em;
}

.table-registros th,
.table-registros td {
    border: 1px solid #ddd;
    padding: 6px 8px;
    text-align: left;
}

.table-registros th {
    background: #f3f4f6;
}
</style>
</head>

<body>

<div class="container">

    <h2><i class="fa-solid fa-user-check"></i> Mi asistencia</h2>

    <div id="contenido"></div>

</div>

<script>
async function cargarAsistencia() {
    const cont = document.getElementById("contenido");
    cont.innerHTML = "Cargando asistencia...";

    try {
        const res = await fetch("misAsistenciasData.php");
        const data = await res.json();

        if (!data.materias || data.materias.length === 0) {
            cont.innerHTML = data.mensaje 
                ? `<p>${data.mensaje}</p>`
                : "<p>No hay información de asistencia disponible.</p>";
            return;
        }

        cont.innerHTML = "";

        data.materias.forEach(mat => {
            const t = mat.totales;
            const porc = (t.porcentaje !== null)
                ? `${t.porcentaje}%`
                : "Sin registros";

            let html = `
                <div class="materia-card">
                    <h3>${mat.nombre}</h3>
                    <div class="badges">
                        <span class="badge badge-presente">
                            Presente: ${t.presente}
                        </span>
                        <span class="badge badge-retardo">
                            Retardo: ${t.retardo}
                        </span>
                        <span class="badge badge-falta">
                            Falta: ${t.falta}
                        </span>
                    </div>
                    <div class="porcentaje">
                        Asistencia (últimos 90 días): ${porc}
                    </div>
            `;

            if (mat.registros && mat.registros.length > 0) {
                html += `
                    <table class="table-registros">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                `;

                mat.registros.forEach(r => {
                    html += `
                        <tr>
                            <td>${r.fecha}</td>
                            <td>${r.estado}</td>
                        </tr>
                    `;
                });

                html += `
                        </tbody>
                    </table>
                `;
            } else {
                html += `<p style="margin-top:10px;">No hay registros de asistencia recientes para esta materia.</p>`;
            }

            html += `</div>`;

            cont.innerHTML += html;
        });

    } catch (e) {
        cont.innerHTML = "<p>Error al cargar la asistencia.</p>";
        console.error(e);
    }
}

cargarAsistencia();
</script>

</body>
</html>
