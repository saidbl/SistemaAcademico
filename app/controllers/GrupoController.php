<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helper/session_helper.php';
require_once __DIR__ . '/../helper/auth_helper.php';
require_once __DIR__ . '/../helper/security_helper.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'crear') {
        try {
            $nivel = $_POST['id_nivel'];
            $nivel_acad = $_POST['id_nivel_academico'];
            $carrera = $_POST['id_carrera'] ?: null;
            $nombre = trim($_POST['nombre']);
            $turno = $_POST['turno'];
            $cupo = (int)$_POST['cupo_maximo'];

            // RN-005
            if ($cupo > 40) throw new Exception("El cupo máximo no puede ser mayor a 40 alumnos (RN-005).");

            // RN-007
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM grupos WHERE id_nivel_academico = ?");
            $stmt->execute([$nivel_acad]);
            $total = $stmt->fetchColumn();
            if ($total >= 4) throw new Exception("Ya existen 4 grupos para este grado o semestre (RN-007).");

            $stmt = $pdo->prepare("
                INSERT INTO grupos (id_nivel, id_carrera, id_nivel_academico, nombre, cupo_maximo, turno)
                VALUES (:nivel, :carrera, :nivel_acad, :nombre, :cupo, :turno)
            ");
            $stmt->execute([
                ':nivel' => $nivel,
                ':carrera' => $carrera,
                ':nivel_acad' => $nivel_acad,
                ':nombre' => $nombre,
                ':cupo' => $cupo,
                ':turno' => $turno
            ]);

            header("Location: /SistemaAcademico/app/views/grupos/listar.php?msg=Grupo creado correctamente");
            exit;

        } catch (Exception $e) {
            header("Location: /SistemaAcademico/app/views/grupos/crear.php?error=" . urlencode($e->getMessage()));
            exit;
        }
    }

    if ($accion === 'eliminar') {
        $id = (int)$_POST['id_grupo'];
        $stmt = $pdo->prepare("DELETE FROM grupos WHERE id_grupo = ?");
        $stmt->execute([$id]);
        header("Location: /SistemaAcademico/app/views/grupos/listar.php?msg=Grupo eliminado correctamente");
        exit;
    }
}
