<?php
require_once __DIR__ . '/../models/Materia.php';
require_once __DIR__ . '/../config/database.php';

$materiaModel = new Materia($pdo);
$accion = $_GET['accion'] ?? 'listar';

switch ($accion) {
    case 'crear':
        $nombre = $_POST['nombre'];
        $tipo = $_POST['tipo'];
        $id_nivel = $_POST['id_nivel'];
        $id_carrera = $_POST['id_carrera'] ?? null;
        $id_nivel_academico = $_POST['id_nivel_academico'] ?? null;

        $materiaModel->crear($nombre, $tipo, $id_nivel, $id_carrera, $id_nivel_academico);
        header("Location: /app/views/materias/listar.php?msg=Materia agregada correctamente");
        break;

    case 'editar':
        $materiaModel->actualizar($_POST['id_materia'], $_POST['nombre'], $_POST['tipo'], $_POST['id_nivel']);
        header("Location: /app/views/materias/listar.php?msg=Materia actualizada");
        break;

    case 'eliminar':
        $materiaModel->eliminar($_GET['id']);
        header("Location: /app/views/materias/listar.php?msg=Materia eliminada");
        break;
}
?>
