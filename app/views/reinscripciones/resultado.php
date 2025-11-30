<?php
require_once __DIR__ . '/../../helper/session_helper.php';
require_once __DIR__ . '/../../helper/auth_helper.php';
exigirSesionActiva();
exigirRol('Administrador');
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Resultado del Proceso de Reinscripción</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
body {
    background: #f5f7fb;
    font-family: "Segoe UI", Arial, sans-serif;
}
.container {
    max-width: 900px;
    margin: 40px auto;
    background: white;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 3px 12px rgba(0,0,0,0.1);
}
h1 {
    text-align: center;
    color: #002e5b;
    margin-bottom: 25px;
}
.section {
    margin-bottom: 30px;
    border-left: 5px solid #004b97;
    padding-left: 15px;
}
.section h2 {
    color: #004b97;
    margin-bottom: 10px;
}
.result-box {
    background: #f0f4ff;
    border-radius: 8px;
    padding: 18px;
    margin-top: 10px;
}
.row {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px solid #ddd;
}
.row:last-child {
    border-bottom: none;
}
.label {
    font-weight: 600;
}
.btn-back {
    display: inline-block;
    padding: 12px 20px;
    background: #004b97;
    color: white;
    text-decoration: none;
    border-radius: 6px;
    margin-top: 20px;
}
.btn-back:hover {
    background: #003b78;
}
.icon {
    color: #004b97;
    margin-right: 8px;
}
</style>
</head>

<body>

<div class="container">

<h1><i class="fa-solid fa-clipboard-check"></i> Resultados del Proceso</h1>


<!-- ========================= RF-025 ========================= -->
<div class="section">
    <h2><i class="fa-solid fa-children icon"></i> RF-025 — Inscripción Automática (Kinder, Primaria y Secundaria)</h2>

    <div class="result-box">
        <div class="row">
            <span class="label">Alumnos procesados:</span>
            <span><?= $resultado["rf025_basicos"]["procesados"] ?? 0 ?></span>
        </div>
        <div class="row">
            <span class="label">Inscritos correctamente:</span>
            <span><?= $resultado["rf025_basicos"]["inscritos"] ?? 0 ?></span>
        </div>
        <div class="row">
            <span class="label">Sin grupo disponible:</span>
            <span><?= $resultado["rf025_basicos"]["sinGrupo"] ?? 0 ?></span>
        </div>
    </div>
</div>


<!-- ========================= RF-026 ========================= -->
<div class="section">
    <h2><i class="fa-solid fa-calendar-check icon"></i> RF-026 — Generación de Citas (Preparatoria y Universidad)</h2>

    <div class="result-box">
        <div class="row">
            <span class="label">Citas generadas:</span>
            <span><?= $resultado["rf026_citas"]["citas_generadas"] ?? 0 ?></span>
        </div>
    </div>
</div>


<!-- ========================= RF-028 ========================= -->
<div class="section">
    <h2><i class="fa-solid fa-triangle-exclamation icon"></i> RF-028 — Generación de Recursamiento</h2>

    <div class="result-box">
        <div class="row">
            <span class="label">Materias en recursamiento:</span>
            <span><?= $resultado["rf028_recursamiento"]["total_recursamientos"] ?? 0 ?></span>
        </div>
    </div>
</div>


<!-- ========================= RF PREPA / UNI ========================= -->
<div class="section">
    <h2><i class="fa-solid fa-arrow-up-right-dots icon"></i> Reinscripción Final (Preparatoria y Universidad)</h2>

    <div class="result-box">
        <div class="row">
            <span class="label">Alumnos con cita válidos:</span>
            <span><?= $resultado["rf_prepa_uni"]["procesados"] ?? 0 ?></span>
        </div>
        <div class="row">
            <span class="label">Reinscritos con éxito:</span>
            <span><?= $resultado["rf_prepa_uni"]["reinscritos"] ?? 0 ?></span>
        </div>
        <div class="row">
            <span class="label">No aptos:</span>
            <span><?= $resultado["rf_prepa_uni"]["noAptos"] ?? 0 ?></span>
        </div>
        <div class="row">
            <span class="label">Sin grupo disponible:</span>
            <span><?= $resultado["rf_prepa_uni"]["sinGrupo"] ?? 0 ?></span>
        </div>
    </div>
</div>


<div style="text-align:center;">
    <a href="/SistemaAcademico/app/views/reinscripciones/index.php" class="btn-back">
        <i class="fa-solid fa-arrow-left"></i> Volver
    </a>
</div>

</div>

</body>
</html>
