<?php

class ReinscripcionController
{
    private $model;

    public function __construct($pdo)
    {
        require_once __DIR__ . '/../models/ReinscripcionModel.php';
        $this->model = new ReinscripcionModel($pdo);
    }

    public function index()
    {
        require_once __DIR__ . '/../views/reinscripciones/index.php';
    }

    public function procesar()
    {
        $accion  = $_POST["accion"] ?? "todo";
        $periodo = $_POST["periodo"] ?? date("Y") . "-1";
        $inicio  = $_POST["inicio"] ?? date("Y") . "-01-10 08:00:00";

        $resultado = [];

        switch ($accion) {
            case "rf025":
                $resultado = $this->model->inscribirBasicos($periodo);
                break;

            case "rf026":
                $resultado = $this->model->generarCitas($periodo, $inicio);
                break;

            case "rf028":
                $resultado = $this->model->generarRecursamiento($periodo);
                break;

            case "todo":
                $resultado["rf025"] = $this->model->inscribirBasicos($periodo);
                $resultado["rf026"] = $this->model->generarCitas($periodo, $inicio);
                $resultado["rf028"] = $this->model->generarRecursamiento($periodo);
                $resultado["rf_prepa_uni"] = $this->model->reinscribirPrepaUni($periodo);
                break;
        }

        require_once __DIR__ . '/../views/reinscripciones/resultado.php';
    }
}
