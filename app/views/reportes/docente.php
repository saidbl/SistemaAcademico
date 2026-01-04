<?php
session_start();
require_once __DIR__ . '/../../helper/session_helper.php';
require_once __DIR__ . '/../../helper/auth_helper.php';
require_once __DIR__ . '/../../config/database.php';

exigirSesionActiva();
exigirRol('Docente');

/* Reportes asignados al rol Docente */
$stmt = $pdo->prepare("
SELECT r.id_reporte, r.nombre, r.descripcion
FROM reportes r
JOIN reporte_rol rr ON r.id_reporte = rr.id_reporte
WHERE rr.rol = 'Docente'
");
$stmt->execute();
$reportes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Reportes Docente</title>
<style>
body{font-family:'Segoe UI';background:#f5f7fb}
.container{max-width:900px;margin:40px auto}
.cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:20px}
.card{background:white;padding:25px;border-radius:12px;text-align:center}
.card a{display:inline-block;margin-top:10px;padding:8px 14px;background:#004b97;color:white;border-radius:6px;text-decoration:none}
</style>
</head>
<body>

<div class="container">
<h2>📄 Reportes del Docente</h2>

<?php if(empty($reportes)): ?>
<p>No hay reportes asignados.</p>
<?php endif; ?>

<div class="cards">
<?php foreach($reportes as $r): ?>
  <div class="card">
    <h3><?=htmlspecialchars($r['nombre'])?></h3>
    <p><?=htmlspecialchars($r['descripcion'])?></p>
    <a href="generar_docente.php?id=<?=$r['id_reporte']?>">Generar PDF</a>
  </div>
<?php endforeach; ?>
</div>

<a href="/SistemaAcademico/app/views/dashboard/docente_dashboard.php">⬅ Volver</a>
</div>

</body>
</html>
