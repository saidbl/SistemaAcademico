<?php
session_start();

require_once __DIR__ . '/../../helper/session_helper.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../../libs/dompdf/autoload.inc.php';

use Dompdf\Dompdf;



exigirSesionActiva();

$id = $_GET['id'] ?? null;
if (!$id) die('Reporte no especificado');

/* 🔹 Obtener nombre del reporte */
$stmt = $pdo->prepare("SELECT nombre FROM reportes WHERE id_reporte=?");
$stmt->execute([$id]);
$reporte = $stmt->fetchColumn();

switch ($reporte) {
  case 'Kardex Académico':
    require 'pdf/kardex.php';
    break;
  case 'Boleta de Calificaciones':
    require 'pdf/boleta.php';
    break;
  case 'Constancia de Estudios':
    require 'pdf/constancia.php';
    break;
  case 'Lista de Alumnos por Grupo':
    require 'pdf/lista_grupo.php';
    break;
  case 'Reporte de Asistencia':
    require 'pdf/asistencia.php';
    break;
  default:
    die('Reporte no disponible');
}
