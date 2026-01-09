<?php
session_start();
require_once __DIR__ . '/../../middlewares/verificar_sesion.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helper/session_helper.php';
require_once __DIR__ . '/../../helper/auth_helper.php';
exigirSesionActiva();
exigirRol('Alumno');

$idUsuario = $_SESSION['usuario_id'];

/* Obtener alumno */
$stmt = $pdo->prepare("
    SELECT a.id_nivel_academico, n.nombre AS semestre
    FROM alumnos a
    JOIN niveles_academicos n ON n.id_nivel_academico = a.id_nivel_academico
    WHERE a.id_usuario = ?
");
$stmt->execute([$idUsuario]);
$alumno = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$alumno) {
    die("Alumno no encontrado");
}

$semestreActual = (int)$alumno['id_nivel_academico'];
$siguienteSemestre = $semestreActual + 1;

/* Buscar grupo del siguiente semestre */
$stmt = $pdo->prepare("
    SELECT id_grupo, nombre
    FROM grupos
    WHERE id_nivel_academico = ?
    LIMIT 1
");
$stmt->execute([$siguienteSemestre]);
$grupo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$grupo) {
    die("No hay grupos para el siguiente semestre");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Reinscripción</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
body {
  margin: 0;
  font-family: "Segoe UI", Arial, sans-serif;
  background: #f4f6fb;
  color: #333;
}

.container {
  max-width: 520px;
  margin: 70px auto;
  background: white;
  border-radius: 14px;
  box-shadow: 0 10px 25px rgba(0,0,0,0.1);
  padding: 35px;
}

.header {
  text-align: center;
  margin-bottom: 30px;
}

.header i {
  font-size: 2.5em;
  color: #004b97;
}

.header h2 {
  margin: 10px 0 5px;
  color: #002e5b;
}

.info {
  background: #f0f4ff;
  border-radius: 10px;
  padding: 18px;
  margin-bottom: 20px;
}

.info-item {
  display: flex;
  justify-content: space-between;
  padding: 8px 0;
  font-weight: 500;
}

.info-item span {
  color: #555;
}

.group-box {
  background: #e8f6ef;
  border-left: 5px solid #198754;
  padding: 15px;
  border-radius: 8px;
  margin-bottom: 25px;
}

.group-box strong {
  color: #146c43;
}

form {
  text-align: center;
}

button {
  background: #004b97;
  color: white;
  border: none;
  padding: 14px 28px;
  border-radius: 10px;
  font-size: 1em;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.2s, transform 0.2s;
}

button:hover {
  background: #00396f;
  transform: translateY(-2px);
}

.note {
  margin-top: 20px;
  font-size: 0.9em;
  color: #777;
  text-align: center;
}
</style>
</head>

<body>

<div class="container">

  <div class="header">
    <i class="fa-solid fa-graduation-cap"></i>
    <h2>Reinscripción</h2>
    <p>Confirma tu avance académico</p>
  </div>

  <div class="info">
    <div class="info-item">
      <span>Semestre actual:</span>
      <strong><?= htmlspecialchars($alumno['semestre']) ?></strong>
    </div>
    <div class="info-item">
      <span>Siguiente semestre:</span>
      <strong><?= $siguienteSemestre ?></strong>
    </div>
  </div>

  <div class="group-box">
    <p>
      <i class="fa-solid fa-users"></i>
      Grupo asignado:
      <strong><?= htmlspecialchars($grupo['nombre']) ?></strong>
    </p>
  </div>

  <form method="POST" action="procesar.php">
    <input type="hidden" name="id_grupo" value="<?= $grupo['id_grupo'] ?>">
    <button type="submit">
      <i class="fa-solid fa-check"></i>
      Confirmar reinscripción
    </button>
  </form>

  <p class="note">
    Al confirmar, tu semestre se actualizará automáticamente.
  </p>

</div>

</body>
</html>
