<?php
session_start();
require_once __DIR__ . '/../config/database.php';

$id_docente = $_POST['id_docente'];
$continuidad = isset($_POST['continuidad_carga']) ? 1 : 0;
$carga = intval($_POST['carga_deseada']);
$nivel = intval($_POST['nivel_preferido']);

// Insertar o actualizar preferencias
$stmt = $pdo->prepare("
    INSERT INTO preferencias_docente (id_docente, continuidad_carga, carga_deseada, nivel_preferido)
    VALUES (?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE
      continuidad_carga = VALUES(continuidad_carga),
      carga_deseada = VALUES(carga_deseada),
      nivel_preferido = VALUES(nivel_preferido),
      respondido = 1,
      fecha_registro = NOW()
");

$stmt->execute([$id_docente, $continuidad, $carga, $nivel]);

header("Location: ../views/docente/preferencias.php?ok=1");
exit;
?>
