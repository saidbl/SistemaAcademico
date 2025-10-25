<?php
session_start();
require_once __DIR__ . '/../../helper/session_helper.php';
require_once __DIR__ . '/../../helper/auth_helper.php';
require_once __DIR__ . '/../../config/database.php';
exigirSesionActiva(); exigirRol('Administrador');
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Nuevo Grupo</title>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
body{background:#f5f7fb;font-family:'Segoe UI',sans-serif;margin:0;}
.topbar{background:#002e5b;color:#fff;padding:14px 30px;display:flex;justify-content:space-between;align-items:center;}
.container{max-width:700px;margin:40px auto;background:white;padding:25px 30px;border-radius:12px;box-shadow:0 4px 12px rgba(0,0,0,0.08);}
label{display:block;margin-top:10px;font-weight:600;}
input,select{width:100%;padding:8px;margin-top:5px;border-radius:6px;border:1px solid #ccc;}
.btn{padding:10px 14px;background:#004b97;color:white;border:none;border-radius:6px;margin-top:20px;cursor:pointer;}
.btn:hover{background:#0061c4;}
</style>
</head>
<body>

<header class="topbar">
  <div><i class="fa-solid fa-layer-group"></i> Crear grupo</div>
  <nav><a href="/SistemaAcademico/app/views/grupos/listar.php" style="color:white;"><i class="fa-solid fa-arrow-left"></i> Volver</a></nav>
</header>

<main class="container">
  <form id="formGrupo" action="/SistemaAcademico/app/controllers/GrupoController.php" method="POST">
    <input type="hidden" name="accion" value="crear">

    <label>Nivel educativo</label>
    <select name="id_nivel" id="id_nivel" required onchange="cargarNivelesAcademicos()">
      <option value="">-- Selecciona --</option>
      <option value="1">Preescolar</option>
      <option value="2">Primaria</option>
      <option value="3">Secundaria</option>
      <option value="4">Preparatoria</option>
      <option value="5">Universidad</option>
    </select>

    <label>Grado / Semestre</label>
    <select name="id_nivel_academico" id="id_nivel_academico" required>
      <option value="">-- Selecciona --</option>
    </select>

    <div id="campo_carrera" style="display:none;">
      <label>Carrera (solo Prepa/Universidad)</label>
      <select name="id_carrera" id="id_carrera">
        <option value="">-- Selecciona una carrera --</option>
      </select>
    </div>

    <label>Nombre del grupo</label>
    <input name="nombre" placeholder="Ej. 1°A, 2°B, 1° Semestre A" required>

    <label>Turno</label>
    <select name="turno" required>
      <option>Matutino</option>
      <option>Vespertino</option>
    </select>

    <label>Cupo máximo (RN-005)</label>
    <input type="number" name="cupo_maximo" id="cupo_maximo" value="40" min="10" max="40" required>

    <button class="btn" type="submit"><i class="fa-solid fa-floppy-disk"></i> Guardar grupo</button>
  </form>
</main>

<script>
function cargarNivelesAcademicos(){
  const nivel=document.getElementById('id_nivel').value;
  const sel=document.getElementById('id_nivel_academico');
  const carrera=document.getElementById('campo_carrera');
  const selCarr=document.getElementById('id_carrera');
  sel.innerHTML='<option value="">-- Selecciona --</option>';
  selCarr.innerHTML='<option value="">-- Selecciona una carrera --</option>';

  if(!nivel)return;
  fetch(`/SistemaAcademico/app/views/usuarios/niveles_academicos_por_nivel.php?id_nivel=${nivel}`)
    .then(r=>r.json()).then(data=>{
      data.forEach(n=>{
        const o=document.createElement('option');
        o.value=n.id_nivel_academico;o.textContent=n.nombre;
        sel.appendChild(o);
      });
    });
  if(nivel==='4'||nivel==='5'){
    carrera.style.display='block';
    fetch(`/SistemaAcademico/app/views/usuarios/carreras_por_nivel.php?nivel=${nivel}`)
      .then(r=>r.json()).then(data=>{
        data.forEach(c=>{
          const o=document.createElement('option');
          o.value=c.id_carrera;o.textContent=c.nombre;
          selCarr.appendChild(o);
        });
      });
  } else carrera.style.display='none';
}
document.getElementById("formGrupo").addEventListener("submit",e=>{
  const cupo=parseInt(document.getElementById("cupo_maximo").value);
  if(cupo>40){
    e.preventDefault();
    Swal.fire({icon:'warning',title:'Cupo inválido',text:'El grupo no puede tener más de 40 alumnos (RN-005).'});
  }
});
</script>
</body>
</html>
