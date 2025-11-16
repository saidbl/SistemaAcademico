<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helper/session_helper.php';
require_once __DIR__ . '/../../helper/auth_helper.php';
require_once __DIR__ . '/../../models/Materia.php';

exigirSesionActiva();
exigirRol('Administrador');

$materiaModel = new Materia($pdo);
$materias = $materiaModel->listar();

$agrupadas = [];
foreach ($materias as $m) {
    $agrupadas[$m['nivel']][] = $m;
}
?>


<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Gestión de Planes de Estudio</title>
  <script src="https://unpkg.com/lucide@latest"></script>
  <style>
    :root {
      --color-primario: #2563eb;
      --color-secundario: #1e293b;
      --color-fondo: #f8fafc;
      --color-texto: #111827;
      --color-tabla: #f1f5f9;
      --color-hover: #e0f2fe;
      --color-borde: #e5e7eb;
      --color-exito: #10b981;
      --color-error: #ef4444;
      --color-editar: #3b82f6;
    }

    body {
      font-family: "Inter", Arial, sans-serif;
      background-color: var(--color-fondo);
      margin: 0;
      padding: 20px 40px;
      color: var(--color-texto);
    }

    header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 30px;
    }

    h1 {
      font-size: 26px;
      font-weight: 600;
      color: var(--color-secundario);
      display: flex;
      align-items: center;
      gap: 10px;
    }

    h1 svg {
      color: var(--color-primario);
      width: 28px;
      height: 28px;
    }

    .boton-agregar {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      background-color: var(--color-exito);
      color: white;
      padding: 10px 16px;
      border-radius: 8px;
      text-decoration: none;
      font-weight: 500;
      transition: 0.3s;
    }

    .boton-agregar:hover {
      background-color: #059669;
    }

    .contenedor-planes {
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
      gap: 25px;
    }

    .card-nivel {
      background: #fff;
      border: 1px solid var(--color-borde);
      border-radius: 12px;
      width: 45%;
      min-width: 380px;
      box-shadow: 0 3px 10px rgba(0,0,0,0.06);
      transition: 0.3s;
    }

    .card-nivel:hover {
      transform: translateY(-5px);
      box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }

    .nivel-header {
      background: var(--color-primario);
      color: white;
      padding: 15px 20px;
      border-top-left-radius: 12px;
      border-top-right-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }

    .nivel-header h2 {
      margin: 0;
      font-size: 18px;
      font-weight: 500;
    }

    .nivel-header svg {
      width: 22px;
      height: 22px;
      opacity: 0.9;
    }

    table {
      width: 100%;
      border-collapse: collapse;
    }

    th, td {
      padding: 10px 14px;
      text-align: left;
      border-bottom: 1px solid var(--color-borde);
    }

    th {
      background-color: var(--color-tabla);
      font-weight: 600;
      color: var(--color-secundario);
    }

    tr:hover td {
      background-color: var(--color-hover);
    }

    .acciones {
      display: flex;
      gap: 8px;
    }

    .btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 32px;
      height: 32px;
      border-radius: 6px;
      text-decoration: none;
      color: white;
      transition: 0.2s;
    }

    .btn-editar { background-color: var(--color-editar); }
    .btn-eliminar { background-color: var(--color-error); }

    .btn:hover { opacity: 0.85; }

    .footer {
      text-align: center;
      font-size: 13px;
      color: #6b7280;
      margin-top: 40px;
    }

    .msg {
      background: #d1fae5;
      color: #065f46;
      border: 1px solid #10b981;
      padding: 10px 15px;
      border-radius: 8px;
      width: 70%;
      margin: 0 auto 20px;
      text-align: center;
      font-weight: 500;
    }

    @media (max-width: 900px) {
      .card-nivel { width: 100%; }
    }
  </style>
</head>
<body>

<header>
  <h1><i data-lucide="book-open"></i> Gestión de Planes de Estudio</h1>
  <a href="crear.php" class="boton-agregar">
    <i data-lucide="plus-circle"></i> Nueva Materia
  </a>
</header>

<?php if (isset($_GET['msg'])): ?>
  <div class="msg"><?= htmlspecialchars($_GET['msg']) ?></div>
<?php endif; ?>

<div class="contenedor-planes">
  <?php if (count($agrupadas) > 0): ?>
    <?php foreach ($agrupadas as $nivel => $materiasNivel): ?>
      <div class="card-nivel">
        <div class="nivel-header">
          <h2><?= strtoupper($nivel) ?></h2>
          <i data-lucide="layers"></i>
        </div>
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Materia</th>
              <th>Tipo</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($materiasNivel as $m): ?>
              <tr>
                <td><?= $m['id_materia'] ?></td>
                <td><?= htmlspecialchars($m['nombre']) ?></td>
                <td><?= htmlspecialchars($m['tipo']) ?></td>
                <td>
                  <div class="acciones">
                    <a href="editar.php?id=<?= $m['id_materia'] ?>" class="btn btn-editar" title="Editar">
                      <i data-lucide="edit"></i>
                    </a>
                    <a href="../../controllers/MateriasController.php?accion=eliminar&id=<?= $m['id_materia'] ?>"
                      class="btn btn-eliminar"
                      title="Eliminar"
                      onclick="return confirm('¿Eliminar la materia \"<?= $m['nombre'] ?>\"?')">
                      <i data-lucide="trash-2"></i>
                    </a>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endforeach; ?>
  <?php else: ?>
    <p style="text-align:center;">No hay materias registradas en el sistema.</p>
  <?php endif; ?>
</div>

<div class="footer">
  Sistema Académico © <?= date('Y') ?> — Módulo de Planes de Estudio
</div>

<script>
  lucide.createIcons();
</script>

</body>
</html>
