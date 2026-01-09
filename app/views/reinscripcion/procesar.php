<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middlewares/verificar_sesion.php';
require_once __DIR__ . '/../../helper/session_helper.php';
require_once __DIR__ . '/../../helper/auth_helper.php';
exigirSesionActiva();
exigirRol('Alumno');

$idUsuario = $_SESSION['usuario_id'];

/* 1️⃣ Obtener alumno */
$stmt = $pdo->prepare("
    SELECT id_alumno, id_nivel_academico
    FROM alumnos
    WHERE id_usuario = ?
");
$stmt->execute([$idUsuario]);
$alumno = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$alumno) {
    die("Alumno no encontrado");
}

$idAlumno = $alumno['id_alumno'];
$semestreActual = (int)$alumno['id_nivel_academico'];
$siguienteSemestre = $semestreActual + 1;

/* 2️⃣ Buscar grupo del siguiente semestre */
$stmt = $pdo->prepare("
    SELECT id_grupo
    FROM grupos
    WHERE id_nivel_academico = ?
    LIMIT 1
");
$stmt->execute([$siguienteSemestre]);
$grupo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$grupo) {
    die("No hay grupo disponible para el siguiente semestre");
}

$idGrupo = $grupo['id_grupo'];

$pdo->beginTransaction();

try {
    /* 3️⃣ Registrar reinscripción CON GRUPO */
    $stmt = $pdo->prepare("
        INSERT INTO reinscripciones (id_alumno, id_grupo, periodo)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([
        $idAlumno,
        $idGrupo,
        date('Y') . '-2'
    ]);

    /* 4️⃣ Aumentar semestre del alumno */
    $stmt = $pdo->prepare("
        UPDATE alumnos
        SET id_nivel_academico = ?
        WHERE id_usuario = ?
    ");
    $stmt->execute([
        $siguienteSemestre,
        $idUsuario
    ]);

    if ($stmt->rowCount() === 0) {
        throw new Exception("No se actualizó el semestre");
    }

    $pdo->commit();
    header("Location: alumno.php?ok=1");
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    die("Error en reinscripción: " . $e->getMessage());
}
