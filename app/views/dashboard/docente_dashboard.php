<?php
session_start();
require_once __DIR__ . '/../../helper/session_helper.php';
require_once __DIR__ . '/../../helper/auth_helper.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middlewares/verificar_sesion.php';

exigirSesionActiva();
exigirRol('Docente');

// Obtener id_usuario
$id_usuario = $_SESSION['usuario_id'];

// Extraer id_docente desde personal_docente
$stmt = $pdo->prepare("SELECT id_docente FROM personal_docente WHERE id_usuario = ?");
$stmt->execute([$id_usuario]);
$id_docente = $stmt->fetchColumn();

// ===============================
// ESTADÍSTICAS REALES DEL DOCENTE
// ===============================

// Materias asignadas según HORARIOS
$stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT id_materia)
    FROM horarios
    WHERE id_docente = ?
");
$stmt->execute([$id_docente]);
$total_materias = $stmt->fetchColumn();

// Grupos asignados
$stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT id_grupo)
    FROM horarios
    WHERE id_docente = ?
");
$stmt->execute([$id_docente]);
$total_grupos = $stmt->fetchColumn();

// Alumnos asignados (si tuvieras tabla alumnos_grupos)
$stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT a.id_alumno)
    FROM horarios h
    JOIN alumnos a ON a.id_nivel_academico = h.id_materia
    WHERE h.id_docente = ?
");
$alumnos = 0; // placeholder
try { 
    $stmt->execute([$id_docente]);
    $alumnos = $stmt->fetchColumn();
} catch (Exception $e) {
    $alumnos = 0;
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Panel del Docente</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
/* === MISMO DISEÑO DEL ADMIN (AZUL INSTITUCIONAL) === */

body {
  background: #f5f7fb;
  font-family: "Segoe UI", Arial, sans-serif;
  margin: 0;
  color: #333;
}

.topbar {
  background: #004b97;
  padding: 14px 30px;
  color: #fff;
  display: flex;
  justify-content: space-between;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.topbar .brand {
  font-size: 1.5em;
  font-weight: 600;
}

.topbar nav a {
  color: white;
  margin-left: 18px;
  text-decoration: none;
  transition: .2s;
}
.topbar nav a:hover { color: #a3d0ff; }

.container {
  max-width: 1200px;
  margin: 40px auto;
  padding: 0 25px;
}

h2 {
  color: #004b97;
  font-size: 1.8em;
  display: flex;
  align-items: center;
  gap: 10px;
}

.stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(190px,1fr));
  gap: 20px;
  margin-top: 30px;
}

.stat {
  background: white;
  border-radius: 12px;
  padding: 18px;
  display: flex;
  align-items: center;
  gap: 15px;
  box-shadow: 0 3px 10px rgba(0,0,0,0.08);
  transition: .3s;
}
.stat:hover { transform: translateY(-4px); }

.stat-icon {
  width: 50px;
  height: 50px;
  background: #006dd9;
  border-radius: 50%;
  color: white;
  display:flex;align-items:center;justify-content:center;
  font-size:1.5em;
}

.cards {
  margin-top: 45px;
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(260px,1fr));
  gap: 20px;
}

.card {
  background:white;
  border-radius:14px;
  padding:28px;
  text-align:center;
  box-shadow:0 3px 10px rgba(0,0,0,0.08);
  text-decoration:none;
  color:#333;
  transition: .3s;
}
.card:hover { transform:translateY(-6px); }

.card i {
  font-size:2.6em;
  color:#004b97;
  margin-bottom:12px;
}

.footer{
  text-align:center;
  margin-top:45px;
  padding:20px;
  border-top:1px solid #ddd;
  color:#777;
}
</style>
</head>

<body>

<header class="topbar">
  <div class="brand"><i class="fa-solid fa-chalkboard-user"></i> Panel Docente</div>
  <nav>
    <a href="#"><i class="fa-solid fa-book"></i> Materias</a>
    <a href="#"><i class="fa-solid fa-clipboard-list"></i> Preferencias</a>
    <a href="#"><i class="fa-solid fa-calendar"></i> Horarios</a>
    <a href="/SistemaAcademico/app/views/auth/logout.php"><i class="fa-solid fa-right-from-bracket"></i> Salir</a>
  </nav>
</header>

<main class="container">
  <h2><i class="fa-solid fa-user-tie"></i> Bienvenido, <?php echo htmlspecialchars($_SESSION['usuario_nombre']); ?></h2>

  <!-- ESTADÍSTICAS DEL DOCENTE -->
  <section class="stats">
    <div class="stat">
      <div class="stat-icon"><i class="fa-solid fa-book"></i></div>
      <div>
        <h3><?= $total_materias ?></h3>
        <p>Materias Asignadas</p>
      </div>
    </div>

    <div class="stat">
      <div class="stat-icon"><i class="fa-solid fa-users-rectangle"></i></div>
      <div>
        <h3><?= $total_grupos ?></h3>
        <p>Grupos A Cargo</p>
      </div>
    </div>

    <div class="stat">
      <div class="stat-icon"><i class="fa-solid fa-user-graduate"></i></div>
      <div>
        <h3><?= $alumnos ?></h3>
        <p>Alumnos Totales</p>
      </div>
    </div>
  </section>

  <!-- ACCESOS RÁPIDOS -->
  <section class="cards">

    <a class="card" href="/SistemaAcademico/app/views/docente/mis_materias.php">
      <i class="fa-solid fa-book-open"></i>
      <h3>Mis Materias</h3>
      <p>Consulta materias y grupos asignados.</p>
    </a>

    <a class="card" href="/SistemaAcademico/app/views/docente/capturar_calificaciones.php">
      <i class="fa-solid fa-pen-to-square"></i>
      <h3>Calificaciones</h3>
      <p>Captura y consulta calificaciones.</p>
    </a>

    <a class="card" href="/SistemaAcademico/app/views/docente/tomar_asistencia.php">
      <i class="fa-solid fa-calendar-check"></i>
      <h3>Asistencia</h3>
      <p>Registro diario por grupo.</p>
    </a>

    <a class="card" href="../docente/preferencias.php">
      <i class="fa-solid fa-list-check"></i>
      <h3>Preferencias Docentes</h3>
      <p>Indica tus preferencias de carga académica.</p>
    </a>
    <a class="card" href="/SistemaAcademico/app/views/docente/perfil_docente.php">
  <i class="fa-solid fa-id-card"></i>
  <h3>Mi Perfil</h3>
  <p>Consulta y actualiza tu información personal.</p>
</a>

<a class="card" href="/SistemaAcademico/app/views/reportes/docente.php">
  <i class="fa-solid fa-file-pdf"></i>
  <h3>Reportes</h3>
  <p>Genera reportes asignados por la administración.</p>
</a>


  </section>

  <div class="footer">
    © <?= date('Y') ?> Sistema Académico — Panel Docente
  </div>

</main>

</body>
</html>
