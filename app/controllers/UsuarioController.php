<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../helper/session_helper.php';
require_once __DIR__ . '/../helper/auth_helper.php';
require_once __DIR__ . '/../helper/security_helper.php';

exigirSesionActiva();
exigirRol('Administrador');

$usuarioModel = new Usuario($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $accion = $_POST['accion'] ?? '';

  if ($accion === 'crear') {
    // Política de contraseña por rol (si no pasas password, podrías generarlo según rol)
    $tipo = $_POST['tipo_usuario'];
    $pwd  = $_POST['contrasena'] ?? '';

    if ($tipo === 'Alumno' && !validaPasswordAlumno($pwd)) {
      header('Location: /routes/web.php?r=admin/usuarios&error=Contraseña inválida para alumno');
      exit;
    }

    try {
    $usuarioModel->crear($_POST);
    header("Location: /SistemaAcademico/app/views/usuarios/listar.php?msg=Usuario creado correctamente");
    exit;
} catch (Exception $e) {
    header("Location: /SistemaAcademico/app/views/usuarios/crear.php?error=" . urlencode($e->getMessage()));
    exit;
}
  }

  if ($accion === 'editar') {
    $usuarioModel->actualizar($_POST);
    header('Location: /routes/web.php?r=admin/usuarios&msg=Usuario actualizado');
    exit;
  }

  if ($accion === 'eliminar') {
    $usuarioModel->eliminar((int)$_POST['id_usuario']);
    header('Location: /routes/web.php?r=admin/usuarios&msg=Usuario eliminado');
    exit;
  }
}

header('Location: /routes/web.php?r=admin/usuarios');
