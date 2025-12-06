<?php
session_start();
require_once __DIR__ . '/../../../helper/session_helper.php';
require_once __DIR__ . '/../../../helper/auth_helper.php';
exigirSesionActiva();
exigirRol('Docente');

require_once __DIR__ . '/../../../config/database.php';

$data = json_decode(file_get_contents("php://input"), true);

$id_materia = $data['id_materia'];
$periodo    = $data['periodo'];
$califs     = $data['calificaciones'];

foreach ($califs as $c) {
    $id_alumno = $c['id_alumno'];
    $calif     = $c['calificacion'];

    // ¿Ya existe?
    $stmt = $pdo->prepare("
        SELECT id_calificacion 
        FROM calificaciones
        WHERE id_alumno = ?
          AND id_materia = ?
          AND periodo = ?
    ");
    $stmt->execute([$id_alumno, $id_materia, $periodo]);

    if ($stmt->fetchColumn()) {
        // Actualizar
        $stmt2 = $pdo->prepare("
            UPDATE calificaciones
            SET calificacion = ?
            WHERE id_alumno = ?
              AND id_materia = ?
              AND periodo = ?
        ");
        $stmt2->execute([$calif, $id_alumno, $id_materia, $periodo]);

    } else {
        // Insertar
        $stmt2 = $pdo->prepare("
            INSERT INTO calificaciones (id_alumno, id_materia, periodo, calificacion)
            VALUES (?, ?, ?, ?)
        ");
        $stmt2->execute([$id_alumno, $id_materia, $periodo, $calif]);
    }
}

echo json_encode(["status" => "ok"]);
