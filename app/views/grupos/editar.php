<?php
session_start();
require_once __DIR__ . '/../../helper/session_helper.php';
require_once __DIR__ . '/../../helper/auth_helper.php';
require_once __DIR__ . '/../../config/database.php';

exigirSesionActiva();
exigirRol('Administrador');

if (!isset($_GET['id'])) {
    header('Location: index.php?error=Grupo no especificado');
    exit;
}

$id = (int) $_GET['id'];

/* 🔹 Cargar grupo */
$stmt = $pdo->prepare("SELECT * FROM grupos WHERE id_grupo = ?");
$stmt->execute([$id]);
$grupo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$grupo) {
    header('Location: index.php?error=Grupo no encontrado');
    exit;
}

/* 🔹 Cargar selects */
$niveles = $pdo->query("SELECT * FROM niveles_educativos")->fetchAll();
$grados  = $pdo->query("SELECT * FROM niveles_academicos ORDER BY orden")->fetchAll();
$carreras = $pdo->query("SELECT * FROM carreras")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Editar Grupo</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
body{background:#f5f7fb;font-family:'Segoe UI',sans-serif;margin:0;}
.container{max-width:700px;margin:40px auto;background:#fff;padding:30px;border-radius:12px;box-shadow:0 3px 12px rgba(0,0,0,.1);}
h2{color:#002e5b;margin-bottom:20px;display:flex;gap:10px;}
.form-group{margin-bottom:15px;}
label{font-weight:600;display:block;margin-bottom:6px;}
input,select{width:100%;padding:10px;border-radius:6px;border:1px solid #ccc;}
.actions{margin-top:25px;display:flex;justify-content:space-between;}
.btn{padding:10px 15px;border-radius:6px;border:0;cursor:pointer;text-decoration:none;font-weight:500;}
.primary{background:#004b97;color:white;}
.secondary{background:#6c757d;color:white;}
</style>
</head>
<body>

<div class="container">
<h2><i class="fa-solid fa-pen-to-square"></i> Editar Grupo</h2>

<form action="actualizar.php" method="POST">
<input type="hidden" name="id_grupo" value="<?=$grupo['id_grupo']?>">

<div class="form-group">
<label>Nombre del grupo</label>
<input type="text" name="nombre" value="<?=htmlspecialchars($grupo['nombre'])?>" required>
</div>

<div class="form-group">
<label>Nivel educativo</label>
<select name="id_nivel" required>
<?php foreach($niveles as $n): ?>
<option value="<?=$n['id_nivel']?>" <?=$n['id_nivel']==$grupo['id_nivel']?'selected':''?>>
<?=$n['nombre']?>
</option>
<?php endforeach; ?>
</select>
</div>

<div class="form-group">
<label>Grado / Semestre</label>
<select name="id_nivel_academico">
<?php foreach($grados as $g): ?>
<option value="<?=$g['id_nivel_academico']?>" <?=$g['id_nivel_academico']==$grupo['id_nivel_academico']?'selected':''?>>
<?=$g['nombre']?>
</option>
<?php endforeach; ?>
</select>
</div>

<div class="form-group">
<label>Carrera</label>
<select name="id_carrera">
<option value="">Sin carrera</option>
<?php foreach($carreras as $c): ?>
<option value="<?=$c['id_carrera']?>" <?=$c['id_carrera']==$grupo['id_carrera']?'selected':''?>>
<?=$c['nombre']?>
</option>
<?php endforeach; ?>
</select>
</div>

<div class="form-group">
<label>Turno</label>
<select name="turno">
<?php foreach(['Matutino','Vespertino','Nocturno'] as $t): ?>
<option <?=$grupo['turno']===$t?'selected':''?>><?=$t?></option>
<?php endforeach; ?>
</select>
</div>

<div class="form-group">
<label>Cupo máximo</label>
<input type="number" name="cupo_maximo" value="<?=$grupo['cupo_maximo']?>" min="1" required>
</div>

<div class="actions">
<a href="../dashboard/administrador_dashboard.php" class="btn secondary">Cancelar</a>
<button class="btn primary"><i class="fa-solid fa-floppy-disk"></i> Guardar cambios</button>
</div>

</form>
</div>
</body>
</html>
