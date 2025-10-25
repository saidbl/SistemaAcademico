<?php
session_start();
require_once __DIR__ . '/../../helper/session_helper.php';
require_once __DIR__ . '/../../helper/auth_helper.php';
require_once __DIR__ . '/../../config/database.php';
exigirSesionActiva();
exigirRol('Administrador');

// Cargar grupos
$stmt = $pdo->query("
  SELECT g.id_grupo, g.nombre, n.nombre AS nivel, na.nombre AS grado,
         g.cupo_maximo, g.turno, c.nombre AS carrera
  FROM grupos g
  JOIN niveles_educativos n ON g.id_nivel = n.id_nivel
  LEFT JOIN carreras c ON g.id_carrera = c.id_carrera
  LEFT JOIN niveles_academicos na ON g.id_nivel_academico = na.id_nivel_academico
  ORDER BY n.id_nivel, na.orden, g.nombre
");
$grupos = $stmt->fetchAll(PDO::FETCH_ASSOC);
$msg = $_GET['msg'] ?? '';
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Gestión de Grupos</title>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
body {background:#f5f7fb;font-family:'Segoe UI',sans-serif;margin:0;}
.topbar{background:#002e5b;color:#fff;padding:14px 30px;display:flex;justify-content:space-between;align-items:center;}
.topbar a{color:white;text-decoration:none;margin-left:15px;}
.container{max-width:1100px;margin:40px auto;padding:0 20px;}
h2{color:#002e5b;font-size:1.6em;margin-bottom:20px;}
.table{width:100%;border-collapse:collapse;background:white;border-radius:10px;overflow:hidden;}
.table th,.table td{padding:12px;border-bottom:1px solid #e0e6ed;text-align:left;}
.table th{background:#002e5b;color:#fff;}
.table tr:hover{background:#f8fafc;}
.btn{padding:8px 12px;border:none;border-radius:6px;cursor:pointer;}
.primary{background:#004b97;color:white;}
.green{background:#28a745;color:white;}
.red{background:#dc3545;color:white;}
</style>
</head>
<body>

<header class="topbar">
  <div><i class="fa-solid fa-layer-group"></i> Gestión de Grupos</div>
  <nav>
    <a href="/SistemaAcademico/app/views/dashboard/admin_dashboard.php"><i class="fa-solid fa-house"></i> Dashboard</a>
    <a href="/SistemaAcademico/app/views/grupos/crear.php"><i class="fa-solid fa-plus"></i> Nuevo grupo</a>
  </nav>
</header>

<main class="container">
  <?php if ($msg): ?>
  <script>Swal.fire({icon:'success',title:'Éxito',text:'<?=htmlspecialchars($msg)?>',confirmButtonColor:'#004b97'});</script>
  <?php endif; ?>

  <?php if ($error): ?>
  <script>Swal.fire({icon:'error',title:'Error',text:'<?=htmlspecialchars($error)?>',confirmButtonColor:'#d33'});</script>
  <?php endif; ?>

  <h2><i class="fa-solid fa-users"></i> Lista de grupos</h2>

  <table class="table">
    <thead>
      <tr>
        <th>ID</th>
        <th>Nivel</th>
        <th>Grado/Semestre</th>
        <th>Nombre</th>
        <th>Carrera</th>
        <th>Turno</th>
        <th>Cupo Máx.</th>
        <th>Acciones</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach($grupos as $g): ?>
      <tr>
        <td><?=$g['id_grupo']?></td>
        <td><?=$g['nivel']?></td>
        <td><?=$g['grado']?></td>
        <td><?=$g['nombre']?></td>
        <td><?=$g['carrera'] ?? '-'?></td>
        <td><?=$g['turno']?></td>
        <td><?=$g['cupo_maximo']?></td>
        <td>
          <a class="btn green" href="/SistemaAcademico/routes/web.php?r=admin/grupos/editar&id=<?=$g['id_grupo']?>">Editar</a>
          <form style="display:inline" action="/SistemaAcademico/app/controllers/GrupoController.php" method="POST"
                onsubmit="return confirm('¿Eliminar este grupo?');">
            <input type="hidden" name="accion" value="eliminar">
            <input type="hidden" name="id_grupo" value="<?=$g['id_grupo']?>">
            <button class="btn red">Eliminar</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</main>
</body>
</html>
