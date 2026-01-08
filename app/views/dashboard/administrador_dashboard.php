<?php
session_start();
require_once __DIR__ . '/../../helper/session_helper.php';
require_once __DIR__ . '/../../helper/auth_helper.php';
require_once __DIR__ . '/../../middlewares/verificar_sesion.php';
exigirSesionActiva();
exigirRol('Administrador');
require_once __DIR__ . '/../../config/database.php';


// Estadísticas rápidas
$usuarios = $pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
$alumnos  = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE tipo_usuario='Alumno'")->fetchColumn();
$docentes = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE tipo_usuario='Docente'")->fetchColumn();
$grupos   = $pdo->query("SELECT COUNT(*) FROM grupos")->fetchColumn();
$materias = $pdo->query("SELECT COUNT(*) FROM materias")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Panel del Administrador</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
/* --- Diseño base --- */
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
.stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
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
  font-size: 1.6em;
  color: #004b97;
}
.stat-content p {
  margin: 2px 0 0;
  color: #666;
  font-weight: 500;
}
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
  <div class="brand"><i class="fa-solid fa-graduation-cap"></i> Sistema Académico</div>
  <nav>
    <a href="/SistemaAcademico/app/views/usuarios/listar.php"><i class="fa-solid fa-users"></i> Usuarios</a>
    <a href="/SistemaAcademico/app/views/grupos/listar.php"><i class="fa-solid fa-layer-group"></i> Grupos</a>
    <a href="/SistemaAcademico/app/views/materias/listar.php"><i class="fa-solid fa-book"></i> Materias</a>
    <a href="/SistemaAcademico/app/views/auth/logout.php"><i class="fa-solid fa-right-from-bracket"></i> Salir</a>
  </nav>
</header>

<main class="container">
  <h2><i class="fa-solid fa-chart-line"></i> Bienvenido, <?php echo htmlspecialchars($_SESSION['usuario_nombre']); ?></h2>

  <!-- 🔹 Estadísticas principales -->
  <section class="stats">
    <div class="stat">
      <div class="stat-icon"><i class="fa-solid fa-user-shield"></i></div>
      <div class="stat-content">
        <h3><?= $usuarios ?></h3>
        <p>Usuarios registrados</p>
      </div>
    </div>
    <div class="stat">
      <div class="stat-icon"><i class="fa-solid fa-user-graduate"></i></div>
      <div class="stat-content">
        <h3><?= $alumnos ?></h3>
        <p>Alumnos activos</p>
      </div>
    </div>
    <div class="stat">
      <div class="stat-icon"><i class="fa-solid fa-chalkboard-user"></i></div>
      <div class="stat-content">
        <h3><?= $docentes ?></h3>
        <p>Docentes</p>
      </div>
    </div>
    <div class="stat">
      <div class="stat-icon"><i class="fa-solid fa-users-rectangle"></i></div>
      <div class="stat-content">
        <h3><?= $grupos ?></h3>
        <p>Grupos creados</p>
      </div>
    </div>
    <div class="stat">
      <div class="stat-icon"><i class="fa-solid fa-book-open-reader"></i></div>
      <div class="stat-content">
        <h3><?= $materias ?></h3>
        <p>Materias registradas</p>
      </div>
    </div>
  </section>

  <!-- 🔹 Accesos rápidos -->
  <section class="cards">
    <a class="card" href="/SistemaAcademico/app/views/usuarios/listar.php">
      <i class="fa-solid fa-users-gear"></i>
      <h3>Gestión de Usuarios</h3>
      <p>Administra alumnos, docentes, tutores y personal administrativo.</p>
    </a>
    <a class="card" href="/SistemaAcademico/app/views/grupos/listar.php">
      <i class="fa-solid fa-layer-group"></i>
      <h3>Gestión de Grupos</h3>
      <p>Organiza los grupos por nivel, turno y asigna docentes responsables.</p>
    </a>
    <a class="card" href="/SistemaAcademico/app/views/materias/listar.php">
      <i class="fa-solid fa-book"></i>
      <h3>Gestión de Materias</h3>
      <p>Agrega, edita y administra el plan de estudios y asignaturas.</p>
    </a>
    <a class="card" href="/SistemaAcademico/app/views/reportes/ver.php">
      <i class="fa-solid fa-file-lines"></i>
      <h3>Reportes y Control</h3>
      <p>Consulta reportes asignados según tu rol.</p>
    </a>

    <a class="card" href="../asignacion/automatica.php">
      <i class="fa-solid fa-book-open"></i>
      <h3>Mis Materias</h3>
      <p>Consulta materias y grupos asignados.</p>
    </a>
    <a class="card" href="/SistemaAcademico/app/views/perfil/index.php">
    <i class="fa-solid fa-id-card"></i>
    <h3>Mi Perfil</h3>
    <p>Consulta y actualiza tu información personal.</p>
    </a>
    <a class="card" href="/SistemaAcademico/app/views/reinscripciones/index.php">
  <i class="fa-solid fa-repeat"></i>
  <h3>Reinscripciones</h3>
    <p>Gestiona las reinscripciones del sistema.</p>
</a>
<a class="card" href="/SistemaAcademico/app/views/reportes/administrar.php">
    <i class="fa-solid fa-id-card"></i>
    <h3>Control de Reportes</h3>
    <p>Consulta y actualiza tu información personal.</p>
    </a>


  </section>

  <div class="footer">
    <p>© <?= date('Y') ?> Sistema Académico Escolar — Panel del Administrador</p>
  </div>
</main>

</body>
</html>
