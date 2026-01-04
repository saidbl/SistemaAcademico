<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/Usuario.php';
require_once __DIR__ . '/../../helper/session_helper.php';
require_once __DIR__ . '/../../helper/auth_helper.php';

exigirSesionActiva();
exigirRol('Administrador');

if (!isset($_GET['id'])) {
    header('Location: index.php?error=Usuario no especificado');
    exit;
}

$id = (int)$_GET['id'];

$model = new Usuario($pdo);
$usuario = $model->obtenerPorId($id);

if (!$usuario) {
    header('Location: index.php?error=Usuario no encontrado');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Editar Usuario</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
body { background:#f5f7fb; font-family:'Segoe UI',sans-serif; margin:0; }
.container {
  max-width: 600px;
  margin: 40px auto;
  background: white;
  padding: 30px;
  border-radius: 12px;
  box-shadow: 0 3px 12px rgba(0,0,0,.1);
}
h2 {
  color:#002e5b;
  margin-bottom: 20px;
  display:flex;
  gap:10px;
  align-items:center;
}
.form-group { margin-bottom: 15px; }
label { font-weight: 600; display:block; margin-bottom: 6px; }
input, select {
  width:100%;
  padding:10px;
  border-radius:6px;
  border:1px solid #ccc;
}
.actions {
  margin-top: 25px;
  display:flex;
  justify-content: space-between;
}
.btn {
  padding:10px 15px;
  border-radius:6px;
  border:0;
  cursor:pointer;
  text-decoration:none;
  font-weight:500;
}
.primary { background:#004b97; color:white; }
.secondary { background:#6c757d; color:white; }
</style>
</head>
<body>

<div class="container">
  <h2><i class="fa-solid fa-user-pen"></i> Editar Usuario</h2>

  <form action="actualizar.php" method="POST">
    <input type="hidden" name="id_usuario" value="<?= $usuario['id_usuario'] ?>">

    <div class="form-group">
      <label>Nombre</label>
      <input type="text" name="nombre" value="<?= htmlspecialchars($usuario['nombre']) ?>" required>
    </div>

    <div class="form-group">
      <label>Apellido Paterno</label>
      <input type="text" name="apellido_paterno" value="<?= htmlspecialchars($usuario['apellido_paterno']) ?>" required>
    </div>

    <div class="form-group">
      <label>Apellido Materno</label>
      <input type="text" name="apellido_materno" value="<?= htmlspecialchars($usuario['apellido_materno']) ?>">
    </div>

    <div class="form-group">
      <label>Correo institucional</label>
      <input type="email" name="correo_institucional"
             value="<?= htmlspecialchars($usuario['correo_institucional']) ?>" required>
    </div>

    <div class="form-group">
      <label>Tipo de usuario</label>
      <select name="tipo_usuario">
        <?php foreach(['Administrador','Docente','Alumno','Padre'] as $tipo): ?>
          <option <?= $usuario['tipo_usuario']===$tipo?'selected':'' ?>><?= $tipo ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="form-group">
      <label>Estatus</label>
      <select name="estatus">
        <?php foreach(['Activo','Inactivo','Baja'] as $e): ?>
          <option <?= $usuario['estatus']===$e?'selected':'' ?>><?= $e ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="actions">
      <a href="../dashboard/administrador_dashboard.php" class="btn secondary">Cancelar</a>
      <button class="btn primary">
        <i class="fa-solid fa-floppy-disk"></i> Guardar cambios
      </button>
    </div>
  </form>
</div>

</body>
</html>
