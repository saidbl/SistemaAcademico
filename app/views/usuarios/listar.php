<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/Usuario.php';
require_once __DIR__ . '/../../helper/session_helper.php';
require_once __DIR__ . '/../../helper/auth_helper.php';
exigirSesionActiva();
exigirRol('Administrador');

$model = new Usuario($pdo);
$usuarios = $model->listar();
$msg = $_GET['msg'] ?? '';
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Gestión de Usuarios</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
body {
  background: #f5f7fb;
  font-family: 'Segoe UI', sans-serif;
  color: #333;
  margin: 0;
}
.topbar {
  background: #002e5b;
  color: #fff;
  padding: 14px 30px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}
.topbar .brand {
  font-weight: bold;
  font-size: 1.4em;
  display: flex;
  align-items: center;
  gap: 8px;
}
.topbar nav a {
  color: white;
  margin-left: 18px;
  text-decoration: none;
  font-weight: 500;
}
.topbar nav a:hover {
  color: #a3d0ff;
}
.container {
  max-width: 1200px;
  margin: 40px auto;
  padding: 0 25px;
}
.header-actions {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}
h2 {
  color: #002e5b;
  font-size: 1.7em;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 10px;
}
.btn {
  padding: 9px 14px;
  border: 0;
  border-radius: 6px;
  cursor: pointer;
  text-decoration: none;
  font-weight: 500;
  transition: all 0.2s ease;
}
.btn.primary { background: #004b97; color: #fff; }
.btn.primary:hover { background: #0061c4; }
.btn.green { background: #28a745; color: white; }
.btn.red { background: #dc3545; color: white; }
.btn.sm { padding: 6px 10px; font-size: 0.9em; }

.alert {
  padding: 12px;
  border-radius: 6px;
  margin-bottom: 12px;
  font-weight: 500;
}
.success { background: #e6f4ea; color: #1b5e20; }
.danger { background: #fdecea; color: #b71c1c; }

.filters {
  margin-bottom: 18px;
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}
.filters select, .filters input {
  padding: 8px 10px;
  border-radius: 6px;
  border: 1px solid #ccc;
  font-size: 0.95em;
}

.table-container {
  background: white;
  border-radius: 12px;
  box-shadow: 0 3px 10px rgba(0,0,0,0.08);
  padding: 20px;
}
.table {
  width: 100%;
  border-collapse: collapse;
}
.table th, .table td {
  padding: 12px;
  border-bottom: 1px solid #e8edf2;
  text-align: left;
}
.table th {
  background: #004b97;
  color: #fff;
  text-transform: uppercase;
  font-size: 0.85em;
  letter-spacing: 0.5px;
}
.table tr:hover {
  background: #f8fbff;
}
.table td:last-child {
  text-align: center;
}
@media (max-width: 768px) {
  .table th, .table td { font-size: 0.9em; }
  .header-actions { flex-direction: column; align-items: flex-start; gap: 10px; }
}
</style>
</head>
<body>

<header class="topbar">
  <div class="brand">
    <i class="fa-solid fa-graduation-cap"></i> Sistema Académico
  </div>
  <nav>
    <a href="/SistemaAcademico/app/views/dashboard/admin_dashboard.php"><i class="fa-solid fa-house"></i> Dashboard</a>
    <a href="/SistemaAcademico/app/views/auth/logout.php"><i class="fa-solid fa-right-from-bracket"></i> Salir</a>
  </nav>
</header>

<main class="container">
  <div class="header-actions">
    <h2><i class="fa-solid fa-users-gear"></i> Gestión de Usuarios</h2>
    <a href="crear.php" class="btn primary"><i class="fa-solid fa-user-plus"></i> Crear Usuario</a>
  </div>

  <?php if($msg): ?><div class="alert success"><i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($msg) ?></div><?php endif; ?>
  <?php if($error): ?><div class="alert danger"><i class="fa-solid fa-triangle-exclamation"></i> <?= htmlspecialchars($error) ?></div><?php endif; ?>

  <!-- 🔹 Filtros -->
  <div class="filters">
    <input type="text" id="buscador" onkeyup="filtrarUsuarios()" placeholder="🔎 Buscar por nombre o correo...">
    <select id="filtroTipo" onchange="filtrarUsuarios()">
      <option value="">Filtrar por tipo...</option>
      <option>Administrador</option>
      <option>Docente</option>
      <option>Alumno</option>
      <option>Padre</option>
    </select>
    <select id="filtroEstado" onchange="filtrarUsuarios()">
      <option value="">Filtrar por estatus...</option>
      <option>Activo</option>
      <option>Inactivo</option>
      <option>Baja</option>
    </select>
  </div>

  <!-- 🔹 Tabla -->
  <div class="table-container">
    <table class="table" id="tablaUsuarios">
      <thead>
        <tr>
          <th>ID</th>
          <th>Nombre completo</th>
          <th>Tipo</th>
          <th>Correo institucional</th>
          <th>Estatus</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($usuarios as $u): ?>
        <tr>
          <td><?= $u['id_usuario'] ?></td>
          <td><?= htmlspecialchars($u['nombre'].' '.$u['apellido_paterno'].' '.$u['apellido_materno']) ?></td>
          <td><?= $u['tipo_usuario'] ?></td>
          <td><?= htmlspecialchars($u['correo_institucional']) ?></td>
          <td><?= $u['estatus'] ?></td>
          <td>
            <a class="btn green sm" href="/SistemaAcademico/routes/web.php?r=admin/usuarios/editar&id=<?= $u['id_usuario'] ?>">
              <i class="fa-solid fa-pen-to-square"></i> Editar
            </a>
            <form style="display:inline" action="/SistemaAcademico/app/controllers/UsuarioController.php" method="POST"
                  onsubmit="return confirm('¿Eliminar al usuario seleccionado?');">
              <input type="hidden" name="accion" value="eliminar">
              <input type="hidden" name="id_usuario" value="<?= $u['id_usuario'] ?>">
              <button class="btn red sm" type="submit">
                <i class="fa-solid fa-trash"></i> Eliminar
              </button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</main>

<script>
// 🔍 Buscador y filtros
function filtrarUsuarios() {
  const tipo = document.getElementById('filtroTipo').value.toLowerCase();
  const estado = document.getElementById('filtroEstado').value.toLowerCase();
  const busqueda = document.getElementById('buscador').value.toLowerCase();
  const filas = document.querySelectorAll('#tablaUsuarios tbody tr');

  filas.forEach(fila => {
    const nombre = fila.children[1].innerText.toLowerCase();
    const correo = fila.children[3].innerText.toLowerCase();
    const tipoCelda = fila.children[2].innerText.toLowerCase();
    const estadoCelda = fila.children[4].innerText.toLowerCase();

    const coincideTipo = !tipo || tipoCelda.includes(tipo);
    const coincideEstado = !estado || estadoCelda.includes(estado);
    const coincideBusqueda = !busqueda || nombre.includes(busqueda) || correo.includes(busqueda);

    fila.style.display = (coincideTipo && coincideEstado && coincideBusqueda) ? '' : 'none';
  });
}
</script>

</body>
</html>

