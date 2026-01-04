<?php
session_start();
require_once __DIR__ . '/../../helper/session_helper.php';
require_once __DIR__ . '/../../helper/auth_helper.php';
require_once __DIR__ . '/../../config/database.php';

exigirSesionActiva();
exigirRol('Alumno');

/* 🔹 Obtener reportes asignados al rol Alumno */
$stmt = $pdo->prepare("
SELECT r.id_reporte, r.nombre, r.descripcion
FROM reportes r
JOIN reporte_rol rr ON r.id_reporte = rr.id_reporte
WHERE rr.rol = 'Alumno'
ORDER BY r.nombre
");
$stmt->execute();
$reportes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Mis Reportes</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
body{font-family:'Segoe UI';background:#f5f7fb;margin:0}
.container{max-width:900px;margin:40px auto}
h2{text-align:center;color:#002e5b}
.cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:20px;margin-top:30px}
.card{background:white;padding:25px;border-radius:12px;text-align:center;
box-shadow:0 3px 10px rgba(0,0,0,.08)}
.card i{font-size:2.2em;color:#004b97;margin-bottom:10px}
.card h3{margin:10px 0;color:#002e5b}
.card p{color:#555;font-size:.95em}
.card a{display:inline-block;margin-top:12px;padding:8px 14px;
background:#004b97;color:white;border-radius:6px;text-decoration:none}
.back{text-align:center;margin-top:30px}
</style>
</head>
<body>

<div class="container">
<h2><i class="fa-solid fa-file-pdf"></i> Mis Reportes</h2>

<?php if(empty($reportes)): ?>
<p style="text-align:center;color:#777">
  No tienes reportes asignados por el administrador.
</p>
<?php endif; ?>

<div class="cards">
<?php foreach($reportes as $r): ?>
  <div class="card">
    <i class="fa-solid fa-file-lines"></i>
    <h3><?= htmlspecialchars($r['nombre']) ?></h3>
    <p><?= htmlspecialchars($r['descripcion']) ?></p>
    <a href="generar_alumno.php?id=<?= $r['id_reporte'] ?>">
      Generar PDF
    </a>
  </div>
<?php endforeach; ?>
</div>

<div class="back">
  <a href="/SistemaAcademico/app/views/dashboard/alumno_dashboard.php">
    ⬅ Volver al panel
  </a>
</div>
</div>

</body>
</html>
