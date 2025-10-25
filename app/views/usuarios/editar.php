<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/Usuario.php';
require_once __DIR__ . '/../../helpers/session_helper.php';
require_once __DIR__ . '/../../helpers/auth_helper.php';
exigirSesionActiva(); exigirRol('Administrador');

$model = new Usuario($pdo);
$id = (int)($_GET['id'] ?? 0);
$u = $model->obtenerPorId($id);
if(!$u){ header('Location: /routes/web.php?r=admin/usuarios&error=Usuario no encontrado'); exit; }
?>
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Editar Usuario</title>
<link rel="stylesheet" href="/public/css/dashboard.css"></head><body>
<header class="topbar"><div class="brand">🎓</div><nav><a href="/routes/web.php?r=admin/usuarios">Volver</a></nav></header>
<main class="container">
  <h2>Editar usuario #<?= $u['id_usuario'] ?></h2>
  <form action="/app/controllers/UsuarioController.php" method="POST" style="max-width:560px">
    <input type="hidden" name="accion" value="editar">
    <input type="hidden" name="id_usuario" value="<?= $u['id_usuario'] ?>">
    <label>Tipo</label>
    <select name="tipo_usuario">
      <?php foreach(['Administrador','Docente','Alumno','Padre'] as $t): ?>
        <option<?= $u['tipo_usuario']===$t?' selected':''?>><?= $t ?></option>
      <?php endforeach; ?>
    </select>
    <label>Nombre</label><input name="nombre" value="<?= htmlspecialchars($u['nombre']) ?>" required>
    <label>Apellido paterno</label><input name="apellido_paterno" value="<?= htmlspecialchars($u['apellido_paterno']) ?>" required>
    <label>Apellido materno</label><input name="apellido_materno" value="<?= htmlspecialchars($u['apellido_materno']) ?>">
    <label>Correo institucional</label><input type="email" name="correo_institucional" value="<?= htmlspecialchars($u['correo_institucional']) ?>" required>
    <label>Estatus</label>
    <select name="estatus">
      <?php foreach(['Activo','Inactivo','Baja'] as $e): ?>
        <option<?= $u['estatus']===$e?' selected':''?>><?= $e ?></option>
      <?php endforeach; ?>
    </select>
    <label>Nueva contraseña (opcional)</label><input type="password" name="contrasena" minlength="8" placeholder="Dejar vacío para no cambiar">
    <button class="btn primary" style="margin-top:10px">Guardar cambios</button>
  </form>
</main></body></html>
