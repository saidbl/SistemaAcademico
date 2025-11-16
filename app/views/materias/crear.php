<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../models/Materia.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helper/session_helper.php';
require_once __DIR__ . '/../../helper/auth_helper.php';

exigirSesionActiva();
exigirRol('Administrador');

$materiaModel = new Materia($pdo);
$niveles = $materiaModel->obtenerNiveles();
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Agregar nueva materia</title>
<script src="https://unpkg.com/lucide@latest"></script>
<style>
  :root {
    --color-primario: #2563eb;
    --color-secundario: #1e293b;
    --color-fondo: #f8fafc;
    --color-texto: #111827;
    --color-borde: #d1d5db;
    --color-boton: #2563eb;
    --color-boton-hover: #1e40af;
  }

  body {
    font-family: "Inter", Arial, sans-serif;
    background-color: var(--color-fondo);
    margin: 0;
    padding: 40px;
    color: var(--color-texto);
    display: flex;
    justify-content: center;
  }

  .form-container {
    background: #fff;
    border: 1px solid var(--color-borde);
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.06);
    width: 100%;
    max-width: 550px;
    padding: 30px 40px;
  }

  h2 {
    font-size: 24px;
    color: var(--color-secundario);
    text-align: center;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
  }

  form {
    margin-top: 25px;
  }

  label {
    display: block;
    margin-bottom: 6px;
    font-weight: 600;
    color: #374151;
  }

  input[type="text"], select {
    width: 100%;
    padding: 10px;
    border: 1px solid var(--color-borde);
    border-radius: 8px;
    margin-bottom: 18px;
    font-size: 15px;
  }

  input:focus, select:focus {
    outline: 2px solid var(--color-primario);
  }

  button {
    background: var(--color-boton);
    color: white;
    padding: 12px 18px;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    font-size: 15px;
    cursor: pointer;
    width: 100%;
    transition: 0.3s;
  }

  button:hover {
    background: var(--color-boton-hover);
  }

  .grupo-carrera {
    display: none;
    border-top: 1px solid var(--color-borde);
    padding-top: 15px;
    margin-top: 10px;
  }

  .volver {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    margin-top: 20px;
    color: var(--color-primario);
    text-decoration: none;
    font-weight: 500;
  }

  .volver:hover {
    text-decoration: underline;
  }
</style>
</head>
<body>

<div class="form-container">
  <h2><i data-lucide="plus-circle"></i> Agregar nueva materia</h2>

  <form action="../../controllers/MateriasController.php?accion=crear" method="POST" onsubmit="return validarFormulario()">
    <label for="nombre">Nombre de la materia:</label>
    <input type="text" id="nombre" name="nombre" required placeholder="Ej. Matemáticas II">

    <label for="tipo">Tipo de materia:</label>
    <select name="tipo" id="tipo" required>
      <option value="">Seleccione tipo</option>
      <option value="Obligatoria">Obligatoria</option>
      <option value="Optativa">Optativa</option>
      <option value="Taller">Taller</option>
    </select>

    <label for="nivel">Nivel educativo:</label>
    <select name="id_nivel" id="nivel" required onchange="mostrarOpcionesCarrera()">
      <option value="">Seleccione nivel</option>
      <?php foreach($niveles as $n): ?>
          <option value="<?= $n['id_nivel'] ?>"><?= htmlspecialchars($n['nombre']) ?></option>
      <?php endforeach; ?>
    </select>

    <div class="grupo-carrera" id="carreraYsemestre">
      <label for="carrera">Carrera:</label>
      <select name="id_carrera" id="carrera" required>
        <option value="">Seleccione carrera</option>
      </select>

      <label for="semestre">Semestre:</label>
      <select name="id_nivel_academico" id="semestre" required>
        <option value="">Seleccione semestre</option>
      </select>
    </div>

    <button type="submit">Guardar materia</button>
  </form>

  <a href="listar.php" class="volver"><i data-lucide="arrow-left"></i> Volver al listado</a>
</div>

<script>
lucide.createIcons();

async function mostrarOpcionesCarrera() {
  const nivel = document.getElementById('nivel').value;
  const contenedor = document.getElementById('carreraYsemestre');
  const carreraSelect = document.getElementById('carrera');
  const semestreSelect = document.getElementById('semestre');

  // Limpiar selects
  carreraSelect.innerHTML = '<option value="">Seleccione carrera</option>';
  semestreSelect.innerHTML = '<option value="">Seleccione semestre</option>';

  // Mostrar solo si es prepa o universidad
  if (nivel === '4' || nivel === '5') {
    contenedor.style.display = 'block';

    // Cargar carreras
    const resCarreras = await fetch(`/SistemaAcademico/api/carreras.php?id_nivel=${nivel}`);
    const carreras = await resCarreras.json();

    carreras.forEach(c => {
      carreraSelect.innerHTML += `<option value="${c.id_carrera}">${c.nombre}</option>`;
    });

    // Cargar semestres
    const resSemestres = await fetch(`/SistemaAcademico/api/niveles_academicos.php?id_nivel=${nivel}`);
    const semestres = await resSemestres.json();

    semestres.forEach(s => {
      semestreSelect.innerHTML += `<option value="${s.id_nivel_academico}">${s.nombre}</option>`;
    });
  } else {
    contenedor.style.display = 'none';
  }
}

// Validación extra (por seguridad del lado cliente)
function validarFormulario() {
  const nombre = document.getElementById('nombre').value.trim();
  const tipo = document.getElementById('tipo').value;
  const nivel = document.getElementById('nivel').value;
  const carreraDiv = document.getElementById('carreraYsemestre');
  
  if (!nombre || !tipo || !nivel) {
    alert('Por favor completa todos los campos obligatorios.');
    return false;
  }

  if ((nivel === '4' || nivel === '5') && carreraDiv.style.display === 'block') {
    const carrera = document.getElementById('carrera').value;
    const semestre = document.getElementById('semestre').value;
    if (!carrera || !semestre) {
      alert('Debes seleccionar una carrera y un semestre.');
      return false;
    }
  }

  return true;
}
</script>
</body>
</html>
