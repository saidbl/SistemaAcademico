<?php
require_once __DIR__ . '/../../config/database.php';
header('Content-Type: application/json; charset=utf-8');

$idNivel = $_GET['id_nivel'] ?? null;
if (!$idNivel) {
  echo json_encode([]);
  exit;
}

$stmt = $pdo->prepare("SELECT id_nivel_academico, nombre FROM niveles_academicos WHERE id_nivel = ? ORDER BY orden");
$stmt->execute([$idNivel]);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
