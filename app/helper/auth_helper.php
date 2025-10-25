<?php
function exigirRol(string $rol): void {
  if (!isset($_SESSION['tipo_usuario']) || $_SESSION['tipo_usuario'] !== $rol) {
    header('Location: /index.php?error=Acceso denegado');
    exit;
  }
}