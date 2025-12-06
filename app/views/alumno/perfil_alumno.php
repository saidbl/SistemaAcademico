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
<title>Mi Perfil</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>

body {
    background: #eef2f7;
    font-family: "Segoe UI", sans-serif;
    margin: 0;
    padding: 25px;
}

.container {
    max-width: 1000px;
    background: white;
    padding: 30px;
    margin: 0 auto;
    border-radius: 16px;
    box-shadow: 0 3px 10px rgba(0,0,0,0.12);
}

h2 {
    display: flex;
    align-items: center;
    gap: 10px;
    color: #003b78;
    font-size: 2em;
    margin-bottom: 25px;
}

.section-title {
    font-size: 1.3em;
    color: #004b97;
    margin-top: 20px;
    margin-bottom: 10px;
    border-bottom: 2px solid #dbe3f1;
    padding-bottom: 3px;
}

.info-box {
    background: #f8fafc;
    padding: 18px;
    border-radius: 10px;
    margin-bottom: 15px;
    border-left: 4px solid #004b97;
}

.info-box p {
    margin: 4px 0;
    font-size: 1.05em;
}

</style>
</head>

<body>

<div class="container">

    <h2><i class="fa-solid fa-id-card"></i> Mi Perfil</h2>

    <h3 class="section-title">Información Personal</h3>
    <div class="info-box" id="infoPersonal"></div>

    <h3 class="section-title">Información Escolar</h3>
    <div class="info-box" id="infoEscolar"></div>

    <h3 class="section-title">Grupo Actual</h3>
    <div class="info-box" id="infoGrupo"></div>
    <a href="/SistemaAcademico/app/views/alumno/editar_perfil.php" 
        style="display:inline-block;margin-top:15px;padding:10px 15px;background:#004b97;color:white;border-radius:8px;text-decoration:none;">
        Editar mi información
    </a>


</div>

<script>
async function cargarPerfil() {
    const res = await fetch("perfilData.php");
    const data = await res.json();

    // PERSONAL
    document.getElementById("infoPersonal").innerHTML = `
        <p><strong>Nombre:</strong> ${data.usuario.nombre} ${data.usuario.apellido_paterno} ${data.usuario.apellido_materno}</p>
        <p><strong>Correo:</strong> ${data.usuario.correo_institucional}</p>
        <p><strong>Teléfono:</strong> ${data.usuario.telefono_personal ?? 'Sin registrar'}</p>
        <p><strong>CURP:</strong> ${data.usuario.curp}</p>
        <p><strong>Correo Personal:</strong> ${data.usuario.correo_personal}</p>
    `;

    // ESCOLAR
    document.getElementById("infoEscolar").innerHTML = `
        <p><strong>Nivel educativo:</strong> ${data.nivel}</p>
        <p><strong>Carrera:</strong> ${data.carrera ?? 'No aplica'}</p>
        <p><strong>Nivel académico:</strong> ${data.alumno.id_nivel_academico}</p>
        <p><strong>Fecha de ingreso:</strong> ${data.alumno.fecha_ingreso}</p>
        <p><strong>Promedio general:</strong> ${data.alumno.promedio_general ?? 'N/A'}</p>
    `;

    // GRUPO
    if (data.grupo) {
        document.getElementById("infoGrupo").innerHTML = `
            <p><strong>Grupo:</strong> ${data.grupo.grupo}</p>
            <p><strong>Turno:</strong> ${data.grupo.turno}</p>
        `;
    } else {
        document.getElementById("infoGrupo").innerHTML = "<p>No tienes un grupo asignado actualmente.</p>";
    }
}

cargarPerfil();
</script>

</body>
</html>
