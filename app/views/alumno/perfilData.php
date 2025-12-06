<?php
session_start();
require_once __DIR__ . '/../../helper/session_helper.php';
require_once __DIR__ . '/../../helper/auth_helper.php';
exigirSesionActiva();
exigirRol('Alumno');

require_once __DIR__ . '/../../config/database.php';

$id_usuario = $_SESSION['usuario_id'];

// ===============================
// 1. DATOS DEL USUARIO
// ===============================
$stmt = $pdo->prepare("
    SELECT 
        nombre,
        apellido_paterno,
        apellido_materno,
        correo_institucional,
        correo_personal,
        curp,
        fecha_nacimiento,
        telefono_personal
    FROM usuarios
    WHERE id_usuario = ?
");
$stmt->execute([$id_usuario]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

// ===============================
// 2. DATOS DEL ALUMNO
// ===============================
$stmt = $pdo->prepare("
    SELECT 
        id_alumno,
        fecha_ingreso,
        promedio_general,
        id_nivel
    FROM alumnos
    WHERE id_usuario = ?
");
$stmt->execute([$id_usuario]);
$alumno = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$alumno) {
    echo json_encode(["error" => "El usuario no está registrado como alumno."]);
    exit;
}

// ===============================
// 3. GRUPO ACTUAL (última reinscripción)
// ===============================
$stmt = $pdo->prepare("
    SELECT 
        g.id_grupo,
        g.nombre AS grupo,
        g.turno,
        g.grado,
        g.id_carrera,
        g.id_nivel_academico
    FROM reinscripciones r
    JOIN grupos g ON g.id_grupo = r.id_grupo
    WHERE r.id_alumno = ?
    ORDER BY r.id_reinscripcion ASC
    LIMIT 1
");
$stmt->execute([$alumno["id_alumno"]]);
$grupo = $stmt->fetch(PDO::FETCH_ASSOC);

// ===============================
// 4. NOMBRE NIVEL EDUCATIVO
// ===============================
$stmt = $pdo->prepare("SELECT nombre FROM niveles_educativos WHERE id_nivel = ?");
$stmt->execute([$alumno["id_nivel"]]);
$nivel = $stmt->fetchColumn();

// ===============================
// 5. CARRERA REAL (SI APLICA – SE TOMA DEL GRUPO!)
// ===============================
$carrera = null;
if (!empty($grupo["id_carrera"])) {
    $stmt = $pdo->prepare("SELECT nombre FROM carreras WHERE id_carrera = ?");
    $stmt->execute([$grupo["id_carrera"]]);
    $carrera = $stmt->fetchColumn();
}

// ===============================
// 6. SEMESTRE/NIVEL ACADÉMICO real (se toma del grupo!)
// ===============================
$nivel_academico_nombre = null;
if (!empty($grupo["id_nivel_academico"])) {
    $stmt = $pdo->prepare("SELECT nombre FROM niveles_academicos WHERE id_nivel_academico = ?");
    $stmt->execute([$grupo["id_nivel_academico"]]);
    $nivel_academico_nombre = $stmt->fetchColumn();
}

echo json_encode([
    "usuario" => $usuario,
    "alumno"  => $alumno,
    "grupo"   => $grupo,
    "nivel"   => $nivel,
    "carrera" => $carrera,
    "nivel_academico_real" => $nivel_academico_nombre
]);
