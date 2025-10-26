<?php
require_once __DIR__ . '/../../models/Materia.php';
require_once __DIR__ . '/../../config/database.php';
$materiaModel = new Materia($pdo);
$niveles = $materiaModel->obtenerNiveles();
?>

<h2>Agregar nueva materia</h2>
<form action="../../controllers/MateriasController.php?accion=crear" method="POST">
    <label>Nombre:</label>
    <input type="text" name="nombre" required><br>

    <label>Tipo:</label>
    <select name="tipo">
        <option value="Obligatoria">Obligatoria</option>
        <option value="Optativa">Optativa</option>
        <option value="Taller">Taller</option>
    </select><br>

    <label>Nivel educativo:</label>
    <select name="id_nivel" id="nivel" required onchange="mostrarOpcionesCarrera()">
        <option value="">Seleccione nivel</option>
        <?php foreach($niveles as $n): ?>
            <option value="<?= $n['id_nivel'] ?>"><?= $n['nombre'] ?></option>
        <?php endforeach; ?>
    </select><br>

    <div id="carreraYsemestre" style="display:none;">
        <label>Carrera:</label>
        <select name="id_carrera" id="carrera"></select><br>

        <label>Semestre:</label>
        <select name="id_nivel_academico" id="semestre"></select><br>
    </div>

    <button type="submit">Guardar Materia</button>
</form>

<script>
async function mostrarOpcionesCarrera() {
    const nivel = document.getElementById('nivel').value;
    const contenedor = document.getElementById('carreraYsemestre');

    if (nivel === '4' || nivel === '5') {
        contenedor.style.display = 'block';

        const resCarreras = await fetch(`/SistemaAcademico/app/views/materias/carreras.php?id_nivel=${nivel}`);
        const carreras = await resCarreras.json();
        const carreraSelect = document.getElementById('carrera');
        carreraSelect.innerHTML = '';
        carreras.forEach(c => {
            carreraSelect.innerHTML += `<option value="${c.id_carrera}">${c.nombre}</option>`;
        });

        const resSemestres = await fetch(`/SistemaAcademico/app/views/materias/niveles_academicos.php?id_nivel=${nivel}`);
        const semestres = await resSemestres.json();
        const semestreSelect = document.getElementById('semestre');
        semestreSelect.innerHTML = '';
        semestres.forEach(s => {
            semestreSelect.innerHTML += `<option value="${s.id_nivel_academico}">${s.nombre}</option>`;
        });
    } else {
        contenedor.style.display = 'none';
    }
}
</script>
