<?php
session_start();
require_once __DIR__ . '/../../helper/session_helper.php';
require_once __DIR__ . '/../../helper/auth_helper.php';
require_once __DIR__ . '/../../middlewares/verificar_sesion.php';
exigirSesionActiva();
exigirRol('Padre'); // 👈 Solo accesible para TUTOR

require_once __DIR__ . '/../../config/database.php';

// Si tienes vinculado alumno–tutor puedes cargarlo aquí
// Ejemplo (si existe la tabla 'alumnos_tutores'):
// $stmt = $pdo->prepare("SELECT u.nombre, u.apellido_paterno FROM usuarios u
//                        JOIN alumnos_tutores at ON at.id_alumno = u.id_usuario
//                        WHERE at.id_tutor = ? LIMIT 1");
// $stmt->execute([$_SESSION['usuario_id']]);
// $alumno = $stmt->fetch(PDO::FETCH_ASSOC);
// $alumnoNombre = $alumno ? $alumno['nombre'] . " " . $alumno['apellido_paterno'] : "Sin alumno asignado";

$alumnoNombre = "Mi Alumno"; // 👈 Cambia esto según tu sistema real
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Panel del Tutor</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
/* --- Diseño base igual que Admin y Alumno --- */
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

/* Tarjetas de estadísticas */
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

/* Tarjetas de accesos */
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
    <i class="fa-solid fa-user-tie"></i> Panel del Tutor
  </div>
  <nav>
    <a href="/SistemaAcademico/app/views/asignacion/horarioTutor.php">
      <i class="fa-solid fa-calendar-days"></i> Horario
    </a>
    <a href="/SistemaAcademico/app/views/tutor/ver_alumno.php">
      <i class="fa-solid fa-child"></i> Alumno
    </a>
    <a href="/SistemaAcademico/app/views/auth/logout.php">
      <i class="fa-solid fa-right-from-bracket"></i> Salir
    </a>
  </nav>
</header>

<main class="container">
  <h2>
    <i class="fa-solid fa-user-tie"></i>
    Bienvenido, <?php echo htmlspecialchars($_SESSION['usuario_nombre']); ?>
  </h2>

  <!-- 🔹 Estadísticas del alumno del tutor -->
  <section class="stats">
    <div class="stat">
      <div class="stat-icon"><i class="fa-solid fa-child"></i></div>
      <div class="stat-content">
        <h3><?= $alumnoNombre ?></h3>
        <p>Alumno a cargo</p>
      </div>
    </div>

    <div class="stat">
      <div class="stat-icon"><i class="fa-solid fa-book"></i></div>
      <div class="stat-content">
        <h3>Materias</h3>
        <p>Plan académico del alumno</p>
      </div>
    </div>

    <div class="stat">
      <div class="stat-icon"><i class="fa-solid fa-user-check"></i></div>
      <div class="stat-content">
        <h3>Asistencias</h3>
        <p>Control de asistencia</p>
      </div>
    </div>
  </section>

  <!-- 🔹 Accesos rápidos del tutor -->
  <section class="cards">

    <a class="card" href="/SistemaAcademico/app/views/tutor/horario_alumno.php">
      <i class="fa-solid fa-calendar"></i>
      <h3>Horario del alumno</h3>
      <p>Consulta el horario escolar de tu hijo/tutorado.</p>
    </a>

    <a class="card" href="/SistemaAcademico/app/views/tutor/calificaciones_alumno.php">
      <i class="fa-solid fa-file-lines"></i>
      <h3>Calificaciones</h3>
      <p>Revisa las calificaciones parciales y finales.</p>
    </a>

    <a class="card" href="/SistemaAcademico/app/views/tutor/asistencia_alumno.php">
      <i class="fa-solid fa-user-check"></i>
      <h3>Asistencias</h3>
      <p>Consulta asistencias, faltas y retardos.</p>
    </a>

    <a class="card" href="/SistemaAcademico/app/views/tutor/materias_alumno.php">
      <i class="fa-solid fa-book-open-reader"></i>
      <h3>Materias del alumno</h3>
      <p>Materias cursando y docentes asignados.</p>
    </a>

    <a class="card" href="/SistemaAcademico/app/views/perfil/perfil_tutor.php">
      <i class="fa-solid fa-id-card"></i>
      <h3>Mi perfil</h3>
      <p>Editar datos personales y de contacto.</p>
    </a>

  </section>

  <div class="footer">
    <p>© <?= date('Y') ?> Sistema Académico Escolar — Panel del Tutor</p>
  </div>
</main>

</body>
</html>
