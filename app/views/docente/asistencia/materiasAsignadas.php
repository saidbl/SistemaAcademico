<?php
session_start();
require_once __DIR__ . '/../../../helper/session_helper.php';
require_once __DIR__ . '/../../../helper/auth_helper.php';
exigirSesionActiva();
exigirRol('Docente');

require_once __DIR__ . '/../../../config/database.php';

$id_usuario = $_SESSION['usuario_id'];

$stmt = $pdo->prepare("SELECT id_docente FROM personal_docente WHERE id_usuario = ?");
$stmt->execute([$id_usuario]);
$id_docente = $stmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT DISTINCT 
        h.id_materia,
        m.nombre AS materia,
        h.id_grupo,
        g.nombre AS grupo
    FROM horarios h
    JOIN materias m ON m.id_materia = h.id_materia
    JOIN grupos g ON g.id_grupo = h.id_grupo
    WHERE h.id_docente = ?
    ORDER BY g.nombre
");
$stmt->execute([$id_docente]);

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
