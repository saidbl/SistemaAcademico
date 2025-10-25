<?php
function exigirSesionActiva(): void {

  // --- Verifica que la sesión exista ---
  if (!isset($_SESSION['usuario_id'])) {
    header('Location: /SistemaAcademico/index.php?error=Debes iniciar sesión');
    exit;
  }

  // --- Control de inactividad (5 min) ---
  $maxInactividad = 5 * 60;
  if (isset($_SESSION['ultima_actividad']) && (time() - $_SESSION['ultima_actividad'] > $maxInactividad)) {
    session_unset();
    session_destroy();
    header('Location: /SistemaAcademico/index.php?error=Sesión expirada por inactividad');
    exit;
  }
  $_SESSION['ultima_actividad'] = time();

  // --- Carga la conexión ---
  require_once __DIR__ . '/../config/database.php';
  global $pdo; // 👈 esta línea es la clave para usar la variable del archivo incluido

  if (!isset($pdo) || !$pdo instanceof PDO) {
    die("❌ Error: no se pudo cargar la conexión PDO. Verifica database.php<br>Ruta buscada: " . __DIR__ . '/../config/database.php');
  }

  // --- Verifica token de sesión única ---
  $stmt = $pdo->prepare("SELECT token_sesion FROM usuarios WHERE id_usuario = ?");
  $stmt->execute([$_SESSION['usuario_id']]);
  $token = $stmt->fetchColumn();

  if (!$token || $token !== ($_SESSION['token'] ?? '')) {
    session_unset();
    session_destroy();
    header('Location: /SistemaAcademico/index.php?error=Sesión inválida (otro dispositivo)');
    exit;
  }
}
