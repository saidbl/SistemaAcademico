<?php
declare(strict_types=1);

// 🚨 Muy importante: Siempre JSON
header('Content-Type: application/json; charset=utf-8');

try {
    // 🔹 Conexión a la base de datos
    require_once __DIR__ . '/../../config/database.php';
    global $pdo;

    if (!isset($pdo) || !$pdo instanceof PDO) {
        throw new Exception("No se pudo cargar la conexión a la base de datos.");
    }

    // 🔹 Validar parámetro
    if (!isset($_GET['nivel']) || !is_numeric($_GET['nivel'])) {
        echo json_encode([]);
        exit;
    }

    $nivel = (int) $_GET['nivel'];

    // 🔹 Solo Preparatoria (4) y Universidad (5) tienen carreras
    if ($nivel !== 4 && $nivel !== 5) {
        echo json_encode([]);
        exit;
    }

    // 🔹 Consulta de carreras según nivel
    $stmt = $pdo->prepare("SELECT id_carrera, nombre FROM carreras WHERE id_nivel = :nivel ORDER BY nombre ASC");
    $stmt->execute(['nivel' => $nivel]);
    $carreras = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 🔹 Enviar JSON limpio
    echo json_encode($carreras ?: []);
} catch (Throwable $e) {
    // 🔹 Si algo falla, enviar JSON con error controlado
    echo json_encode([
        "error" => true,
        "message" => "Error al cargar carreras: " . $e->getMessage()
    ]);
}
