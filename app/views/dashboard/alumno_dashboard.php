<?php
session_start();
require_once __DIR__ . '/../../helper/session_helper.php';
require_once __DIR__ . '/../../helper/auth_helper.php';
exigirSesionActiva();
exigirRol('Alumno'); // 👈 Importante: solo alumnos

require_once __DIR__ . '/../../config/database.php';

// Puedes sacar algunos datos generales si quieres
$materiasTotales = $pdo->query("SELECT COUNT(*) FROM materias")->fetchColumn();
$gruposTotales   = $pdo->query("SELECT COUNT(*) FROM grupos")->fetchColumn();

// Si guardas el grupo del alumno en sesión, podrías mostrarlo (opcional)
// $grupoAlumno = $_SESSION['grupo_nombre'] ?? 'Sin grupo asignado';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Panel del Alumno</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
/* --- Diseño base (reutilizado del admin) --- */
body {
  background: #f5f7fb;
  font-family: "Segoe UI", Arial, sans-serif;
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
  font-size: 1.5em;
  font-weight: 600;
  letter-spacing: 0.5px;
}
.topbar nav a {
  color: white;
  margin-left: 18px;
  text-decoration: none;
  font-weight: 500;
  transition: color 0.2s;
}
.topbar nav a:hover {
  color: #a3d0ff;
}
.container {
  max-width: 1200px;
  margin: 40px auto;
  padding: 0 25px;
}
h2 {
  color: #002e5b;
  font-size: 1.8em;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 30px;
}

/* Tarjetas de stats rápidas (adaptadas al alumno) */
.stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 20px;
}
.stat {
  background: white;
  border-radius: 12px;
  padding: 18px;
  box-shadow: 0 3px 10px rgba(0,0,0,0.08);
  display: flex;
  align-items: center;
  gap: 15px;
  transition: transform 0.2s, box-shadow 0.2s;
}
.stat:hover {
  transform: translateY(-3px);
  box-shadow: 0 6px 14px rgba(0,0,0,0.12);
}
.stat-icon {
  background: #004b97;
  color: white;
  border-radius: 50%;
  width: 50px;
  height: 50px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5em;
}
.stat-content h3 {
  margin: 0;
  font-size: 1.4em;
  color: #004b97;
}
.stat-content p {
  margin: 2px 0 0;
  color: #666;
  font-weight: 500;
}

/* Accesos rápidos */
.cards {
  margin-top: 45px;
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
  gap: 20px;
}
.card {
  background: white;
  border-radius: 14px;
  padding: 28px;
  text-align: center;
  text-decoration: none;
  color: #333;
  box-shadow: 0 3px 10px rgba(0,0,0,0.08);
  transition: all 0.2s ease;
}
.card:hover {
  transform: translateY(-5px);
  box-shadow: 0 8px 20px rgba(0,0,0,0.12);
}
.card i {
  font-size: 2.5em;
  color: #004b97;
  margin-bottom: 10px;
}
.card h3 {
  font-size: 1.2em;
  color: #002e5b;
  margin-bottom: 8px;
}
.card p {
  color: #666;
  font-size: 0.95em;
}

.footer {
  text-align: center;
  margin-top: 50px;
  padding: 15px;
  color: #777;
  font-size: 0.9em;
  border-top: 1px solid #ddd;
}
</style>
</head>
<body>

<header class="topbar">
  <div class="brand">
    <i class="fa-solid fa-graduation-cap"></i> Panel del Alumno
  </div>
  <nav>
    <!-- Ajusta las rutas según tus vistas reales -->
    <a href="/SistemaAcademico/app/views/asignacion/horarioAlumno.php">
      <i class="fa-solid fa-calendar-days"></i> Mi horario
    </a>
    <a href="/SistemaAcademico/app/views/materias/mis_materias.php">
      <i class="fa-solid fa-book-open"></i> Mis materias
    </a>
    <a href="/SistemaAcademico/app/views/calificaciones/mis_calificaciones.php">
      <i class="fa-solid fa-file-pen"></i> Mis calificaciones
    </a>
    <a href="/SistemaAcademico/app/views/auth/logout.php">
      <i class="fa-solid fa-right-from-bracket"></i> Salir
    </a>
  </nav>
</header>

<main class="container">
  <h2>
    <i class="fa-solid fa-user-graduate"></i>
    Bienvenido, <?php echo htmlspecialchars($_SESSION['usuario_nombre']); ?>
  </h2>

  <!-- 🔹 Stats personales / generales (puedes adaptarlos a tus tablas reales) -->
  <section class="stats">
    <div class="stat">
      <div class="stat-icon"><i class="fa-solid fa-book-open-reader"></i></div>
      <div class="stat-content">
        <h3><?= $materiasTotales ?></h3>
        <p>Materias en el plan de estudios</p>
      </div>
    </div>
    <div class="stat">
      <div class="stat-icon"><i class="fa-solid fa-users-rectangle"></i></div>
      <div class="stat-content">
        <h3><?= $gruposTotales ?></h3>
        <p>Grupos activos en la escuela</p>
      </div>
    </div>
    <!-- Ejemplo si guardas grupo en sesión:
    <div class="stat">
      <div class="stat-icon"><i class="fa-solid fa-layer-group"></i></div>
      <div class="stat-content">
        <h3><?= htmlspecialchars($grupoAlumno) ?></h3>
        <p>Mi grupo</p>
      </div>
    </div>
    -->
  </section>

  <!-- 🔹 Accesos rápidos del alumno -->
  <section class="cards">
    <a class="card" href="/SistemaAcademico/app/views/asignacion/horarioAlumno.php">
      <i class="fa-solid fa-calendar-days"></i>
      <h3>Ver mi horario</h3>
      <p>Consulta tus clases por día, bloque y docente asignado.</p>
    </a>

    <a class="card" href="/SistemaAcademico/app/views/materias/mis_materias.php">
      <i class="fa-solid fa-book-open"></i>
      <h3>Mis materias</h3>
      <p>Revisa las materias en las que estás inscrito este ciclo.</p>
    </a>

    <a class="card" href="/SistemaAcademico/app/views/calificaciones/mis_calificaciones.php">
      <i class="fa-solid fa-file-pen"></i>
      <h3>Mis calificaciones</h3>
      <p>Consulta tus calificaciones parciales y finales por materia.</p>
    </a>

    <a class="card" href="/SistemaAcademico/app/views/asistencia/mis_asistencias.php">
      <i class="fa-solid fa-user-check"></i>
      <h3>Mi asistencia</h3>
      <p>Verifica tu registro de asistencias, retardos y ausencias.</p>
    </a>

    <a class="card" href="/SistemaAcademico/app/views/perfil/perfil_alumno.php">
      <i class="fa-solid fa-id-card"></i>
      <h3>Mi perfil</h3>
      <p>Actualiza tus datos de contacto y revisa tu información personal.</p>
    </a>
  </section>

  <div class="footer">
    <p>© <?= date('Y') ?> Sistema Académico Escolar — Panel del Alumno</p>
  </div>
</main>

</body>
</html>
