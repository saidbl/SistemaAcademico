<?php
session_start();
require_once __DIR__ . '/../../helper/session_helper.php';
require_once __DIR__ . '/../../helper/auth_helper.php';
exigirSesionActiva();
exigirRol('Administrador');
require_once __DIR__ . '/../../config/database.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Crear Usuario</title>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
  color: white;
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
  margin-left: 15px;
  text-decoration: none;
  font-weight: 500;
}
.topbar nav a:hover { color: #a3d0ff; }
.container {
  max-width: 720px;
  margin: 40px auto;
  background: white;
  padding: 25px 30px;
  border-radius: 12px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}
h2 {
  color: #002e5b;
  font-size: 1.6em;
  margin-bottom: 20px;
  display: flex;
  align-items: center;
  gap: 8px;
}
form label { display:block; font-weight:600; margin-top:12px; }
form input, form select {
  width:100%;
  padding:8px;
  margin-top:5px;
  border-radius:6px;
  border:1px solid #ccc;
  font-size:0.95em;
}
.grupo-campos {
  margin-top:18px;
  border:1px solid #e0e6ed;
  padding:15px;
  border-radius:10px;
  background:#f8fafc;
}
.grupo-campos h3 {
  margin-top:0;
  color:#004b97;
  font-size:1.1em;
  border-bottom:1px solid #d0d8e0;
  padding-bottom:5px;
}
.btn {
  padding:10px 16px;
  border:0;
  border-radius:6px;
  background:#004b97;
  color:white;
  font-weight:600;
  margin-top:20px;
  cursor:pointer;
  transition:all 0.2s;
}
.btn:hover { background:#0061c4; }
.hidden { display:none; }
</style>
</head>
<body>

<header class="topbar">
  <div class="brand"><i class="fa-solid fa-graduation-cap"></i> Sistema Académico</div>
  <nav><a href="/SistemaAcademico/app/views/usuarios/listar.php"><i class="fa-solid fa-arrow-left"></i> Volver</a></nav>
</header>

<main class="container">
  <h2><i class="fa-solid fa-user-plus"></i> Nuevo Usuario</h2>

  <form id="formUsuario" action="/SistemaAcademico/app/controllers/UsuarioController.php" method="POST">
    <input type="hidden" name="accion" value="crear">

    <label><i class="fa-solid fa-user-gear"></i> Tipo de usuario</label>
    <select name="tipo_usuario" id="tipo_usuario" required onchange="mostrarCampos()">
      <option value="">-- Seleccione --</option>
      <option>Administrador</option>
      <option>Docente</option>
      <option>Alumno</option>
      <option>Padre</option>
    </select>

    <label><i class="fa-solid fa-user"></i> Nombre</label>
    <input name="nombre" id="nombre" required>

    <label><i class="fa-solid fa-user-tag"></i> Apellido paterno</label>
    <input name="apellido_paterno" id="apellido_paterno" required>

    <label><i class="fa-solid fa-user-tag"></i> Apellido materno</label>
    <input name="apellido_materno" id="apellido_materno" required>

    <label><i class="fa-solid fa-id-card"></i> CURP (solo personal)</label>
    <input name="curp" id="curp" required>

    <label><i class="fa-solid fa-cake-candles"></i> Fecha de nacimiento</label>
    <input type="date" name="fecha_nacimiento" id="fecha_nacimiento" required>

    <div id="campo_contrasena">
      <label><i class="fa-solid fa-key"></i> Contraseña (opcional)</label>
      <input type="password" name="contrasena" id="contrasena">
    </div>

    <!-- 🔹 Campos de ALUMNO -->
    <div id="campos_alumno" class="grupo-campos hidden">
      <h3><i class="fa-solid fa-graduation-cap"></i> Datos del Alumno</h3>
      <label>Nivel educativo</label>
      <select name="id_nivel" id="id_nivel_alumno" onchange="cargarNivelesAcademicosAlumno()">
        <option value="">-- Selecciona --</option>
        <option value="1">Preescolar</option>
        <option value="2">Primaria</option>
        <option value="3">Secundaria</option>
        <option value="4">Preparatoria</option>
        <option value="5">Universidad</option>
      </select>

      <label>Grado / Semestre</label>
      <select name="id_nivel_academico" id="id_nivel_academico_alumno">
        <option value="">-- Selecciona --</option>
      </select>

      <div id="campo_carrera" style="display:none;">
        <label><i class="fa-solid fa-chalkboard-user"></i> Carrera</label>
        <select name="id_carrera" id="id_carrera">
          <option value="">-- Selecciona una carrera --</option>
        </select>
      </div>

      <label>Tutor</label>
      <select name="tutor_id" id="tutor_id">
        <option value="">-- Ninguno --</option>
        <?php
          $tutores = $pdo->query("SELECT id_usuario, nombre, apellido_paterno FROM usuarios WHERE tipo_usuario='Padre' AND estatus='Activo'");
          foreach ($tutores as $t) {
            echo "<option value='{$t['id_usuario']}'>{$t['nombre']} {$t['apellido_paterno']}</option>";
          }
        ?>
      </select>
    </div>

    <!-- 🔹 Campos de PADRE -->
    <div id="campos_tutor" class="grupo-campos hidden">
      <h3><i class="fa-solid fa-child"></i> Alumno Asociado al Tutor</h3>
      <label>Nombre del alumno</label><input name="nombre_alumno" id="nombre_alumno">
      <label>Apellido paterno</label><input name="apellido_paterno_alumno" id="apellido_paterno_alumno">
      <label>Apellido materno</label><input name="apellido_materno_alumno" id="apellido_materno_alumno">
      <label>Fecha de nacimiento del alumno</label><input type="date" name="fecha_nacimiento_alumno" id="fecha_nacimiento_alumno">

      <label>Nivel educativo</label>
      <select name="id_nivel_alumno" id="id_nivel_tutor" onchange="cargarNivelesAcademicosTutor()">
        <option value="">-- Selecciona --</option>
        <option value="1">Preescolar</option>
        <option value="2">Primaria</option>
        <option value="3">Secundaria</option>
      </select>

      <label>Grado</label>
      <select name="id_nivel_academico_alumno" id="id_nivel_academico_tutor">
        <option value="">-- Selecciona --</option>
      </select>
    </div>

    <button class="btn" type="submit"><i class="fa-solid fa-floppy-disk"></i> Guardar Usuario</button>
  </form>
</main>

<script>
// ✅ Mostrar/Ocultar campos
function mostrarCampos() {
  const tipo = document.getElementById('tipo_usuario').value;
  const alumno = document.getElementById('campos_alumno');
  const tutor = document.getElementById('campos_tutor');
  const pass = document.getElementById('campo_contrasena');

  alumno.classList.add('hidden');
  tutor.classList.add('hidden');
  pass.classList.remove('hidden');

  if (tipo === 'Alumno') { alumno.classList.remove('hidden'); pass.classList.remove('hidden'); }
  else if (tipo === 'Padre') { tutor.classList.remove('hidden'); pass.classList.add('hidden'); }
  else if (tipo === 'Docente' || tipo === 'Administrador') { pass.classList.add('hidden'); }
}

// 🔹 Cargar niveles académicos para alumno
function cargarNivelesAcademicosAlumno() {
  const nivel = document.getElementById('id_nivel_alumno').value;
  const select = document.getElementById('id_nivel_academico_alumno');
  const divCarrera = document.getElementById('campo_carrera');
  const selectCarrera = document.getElementById('id_carrera');
  select.innerHTML = '<option value="">-- Selecciona --</option>';
  selectCarrera.innerHTML = '<option value="">-- Selecciona una carrera --</option>';
  if (!nivel) return;

  fetch(`/SistemaAcademico/app/views/usuarios/niveles_academicos_por_nivel.php?id_nivel=${nivel}`)
    .then(res => res.json())
    .then(data => data.forEach(n => {
      const opt = document.createElement('option');
      opt.value = n.id_nivel_academico;
      opt.textContent = n.nombre;
      select.appendChild(opt);
    }));

  // Mostrar carreras si es Prepa o Uni
  if (nivel === '4' || nivel === '5') {
    divCarrera.style.display = 'block';
    fetch(`/SistemaAcademico/app/views/usuarios/carreras_por_nivel.php?nivel=${nivel}`)
      .then(res => res.json())
      .then(data => {
        data.forEach(c => {
          const opt = document.createElement('option');
          opt.value = c.id_carrera;
          opt.textContent = c.nombre;
          selectCarrera.appendChild(opt);
        });
      });
  } else {
    divCarrera.style.display = 'none';
  }
}

// 🔹 Cargar niveles académicos para hijo del tutor
function cargarNivelesAcademicosTutor() {
  const nivel = document.getElementById('id_nivel_tutor').value;
  const select = document.getElementById('id_nivel_academico_tutor');
  select.innerHTML = '<option value="">-- Selecciona --</option>';
  if (!nivel) return;

  fetch(`/SistemaAcademico/app/views/usuarios/niveles_academicos_por_nivel.php?id_nivel=${nivel}`)
    .then(res => res.json())
    .then(data => data.forEach(n => {
      const opt = document.createElement('option');
      opt.value = n.id_nivel_academico;
      opt.textContent = n.nombre;
      select.appendChild(opt);
    }));
}
</script>

</body>
</html>
