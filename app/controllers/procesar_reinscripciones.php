<?php
session_start();
require_once __DIR__ . '/../helper/session_helper.php';
require_once __DIR__ . '/../helper/auth_helper.php';
exigirSesionActiva();
exigirRol('Administrador');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/ReinscripcionModel.php';

$model = new ReinscripcionModel($pdo);

$accion  = $_POST["accion"] ?? "todo";
$periodo = $_POST["periodo"] ?? date("Y") . "-1";
$inicio  = $_POST["inicio"] ?? date("Y") . "-01-10 08:00:00";

$resultado = [];

switch ($accion) {

    case "rf025":
        $resultado = [
            "rf025_basicos" => $model->inscribirBasicos($periodo)
        ];
        break;

    case "rf026":
        $resultado = [
            "rf026_citas" => $model->generarCitas($periodo, $inicio)
        ];
        break;

    case "rf028":
        $resultado = [
            "rf028_recursamiento" => $model->generarRecursamiento($periodo)
        ];
        break;

    case "todo":
    default:
        $resultado = [
            "rf025_basicos" => $model->inscribirBasicos($periodo),
            "rf026_citas" => $model->generarCitas($periodo, $inicio),
            "rf028_recursamiento" => $model->generarRecursamiento($periodo),
            "rf_prepa_uni" => $model->reinscribirPrepaUni($periodo)
        ];
        break;
}

require_once __DIR__ . '/../views/reinscripciones/resultado.php';
