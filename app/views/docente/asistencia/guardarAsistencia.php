<?php
session_start();
require_once __DIR__ . '/../../../helper/session_helper.php';
require_once __DIR__ . '/../../../helper/auth_helper.php';
exigirSesionActiva();
exigirRol('Docente');

require_once __DIR__ . '/../../../config/database.php';

$data = json_decode(file_get_contents("php://input"), true);

$id_materia = $data['id_materia'];
$fecha      = $data['fecha'];
$asistencia = $data['asistencias']; // array id_alumno + estado

foreach ($asistencia as $a) {

    $id_alumno = $a['id_alumno'];
    $estado    = $a['estado'];

    // Ver si ya existe asistencia
    $stmt = $pdo->prepare("
        SELECT id_asistencia 
        FROM asistencias
        WHERE id_alumno = ?
          AND id_materia = ?
          AND fecha = ?
    ");
    $stmt->execute([$id_alumno, $id_materia, $fecha]);
    $id = $stmt->fetchColumn();

    if ($id) {
        // Actualizar
        $stmt2 = $pdo->prepare("
            UPDATE asistencias
            SET estado = ?
            WHERE id_asistencia = ?
        ");
        $stmt2->execute([$estado, $id]);
    } else {
        // Insertar
        $stmt2 = $pdo->prepare("
            INSERT INTO asistencias (id_alumno, id_materia, fecha, estado)
            VALUES (?, ?, ?, ?)
        ");
        $stmt2->execute([$id_alumno, $id_materia, $fecha, $estado]);
    }
}

echo json_encode(["status" => "ok"]);
