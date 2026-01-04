<?php
session_start();
require_once __DIR__ . '/../../helper/session_helper.php';
require_once __DIR__ . '/../../helper/auth_helper.php';
require_once __DIR__ . '/../../config/database.php';

exigirSesionActiva();
exigirRol('Administrador');

/* 🔹 Reportes con roles asignados */
$stmt = $pdo->query("
SELECT r.id_reporte, r.nombre, r.descripcion,
       GROUP_CONCAT(rr.rol) AS roles
FROM reportes r
LEFT JOIN reporte_rol rr ON r.id_reporte = rr.id_reporte
GROUP BY r.id_reporte
");
$reportes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$rolesDisponibles = ['Alumno','Docente','Padre'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Asignación de Reportes</title>
<style>
body{font-family:'Segoe UI';background:#f5f7fb}
table{width:90%;margin:40px auto;background:white;border-collapse:collapse;border-radius:10px;overflow:hidden}
th,td{padding:12px;border-bottom:1px solid #ddd;text-align:center}
th{background:#002e5b;color:white}
.badge{padding:5px 10px;border-radius:12px;font-size:.85em}
.ok{background:#d4edda;color:#155724}
.no{background:#f8d7da;color:#721c24}
select,button{padding:6px;border-radius:6px}
</style>
</head>
<body>

<h2 style="text-align:center"> Control de Asignación de Reportes</h2>
<div style="width:90%;margin:20px auto;text-align:left;">
  <a href="/SistemaAcademico/app/views/dashboard/administrador_dashboard.php"
     style="
       display:inline-block;
       padding:10px 16px;
       background:#6c757d;
       color:white;
       border-radius:6px;
       text-decoration:none;
       font-weight:500;
     ">
    ⬅ Volver al Dashboard
  </a>
</div>

<table>
<tr>
  <th>Reporte</th>
  <th>Alumno</th>
  <th>Docente</th>
  <th>Padre</th>
  <th>Asignar nuevo</th>
</tr>

<?php foreach($reportes as $r): 
$rolesAsignados = $r['roles'] ? explode(',', $r['roles']) : [];
?>
<tr>
<td><?=htmlspecialchars($r['nombre'])?></td>

<?php foreach($rolesDisponibles as $rol): ?>
<td>
  <?php if(in_array($rol, $rolesAsignados)): ?>
    <span class="badge ok">Asignado</span>
  <?php else: ?>
    <span class="badge no">No</span>
  <?php endif; ?>
</td>
<?php endforeach; ?>

<td>
<form method="POST" action="../../controllers/ReporteController.php">
  <input type="hidden" name="id_reporte" value="<?=$r['id_reporte']?>">
  <select name="rol">
    <?php foreach($rolesDisponibles as $rol): ?>
      <option><?=$rol?></option>
    <?php endforeach; ?>
  </select>
  <button>Asignar</button>
</form>
</td>
</tr>
<?php endforeach; ?>

</table>
</body>
</html>
