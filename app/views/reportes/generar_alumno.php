<?php
session_start();
require_once __DIR__ . '/../../helper/session_helper.php';
require_once __DIR__ . '/../../helper/auth_helper.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../../libs/dompdf/autoload.inc.php';

use Dompdf\Dompdf;

exigirSesionActiva();
exigirRol('Alumno');

$idReporte = $_GET['id'] ?? null;
if (!$idReporte) die('Reporte no especificado');

/* 🔹 Validar que el reporte esté asignado al Alumno */
$check = $pdo->prepare("
SELECT COUNT(*) 
FROM reporte_rol 
WHERE id_reporte = ? AND rol = 'Alumno'
");
$check->execute([$idReporte]);

if ($check->fetchColumn() == 0) {
    die('No tienes permiso para generar este reporte');
}

/* 🔹 Obtener nombre del reporte */
$stmt = $pdo->prepare("SELECT nombre FROM reportes WHERE id_reporte = ?");
$stmt->execute([$idReporte]);
$nombreReporte = $stmt->fetchColumn();

/* 🔹 Obtener id_alumno REAL */
$stmt = $pdo->prepare("SELECT id_alumno FROM alumnos WHERE id_usuario = ?");
$stmt->execute([$_SESSION['usuario_id']]);
$idAlumno = $stmt->fetchColumn();

if (!$idAlumno) die('Alumno no válido');

ob_start();

/* 🔹 Generar según reporte asignado */
switch ($nombreReporte) {

    case 'Kardex Académico':
        include __DIR__ . '/pdf/kardex.php';
        break;

    case 'Boleta de Calificaciones':
        include __DIR__ . '/pdf/boleta.php';
        break;

    case 'Constancia de Estudios':
        include __DIR__ . '/pdf/constancia.php';
        break;

    default:
        die('Reporte no disponible para alumno');
}

$html = ob_get_clean();

/* 🔹 Generar PDF */
$pdf = new Dompdf();
$pdf->loadHtml($html);
$pdf->render();
$pdf->stream("reporte_alumno.pdf", ["Attachment" => false]);
exit;
