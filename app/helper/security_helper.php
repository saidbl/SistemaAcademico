<?php
function validaPasswordAlumno(string $pwd): bool {
  // ≥9, mayúscula, minúscula, número, especial, sin 123 o ABC
  $ok = preg_match('/[A-Z]/',$pwd) && preg_match('/[a-z]/',$pwd) &&
        preg_match('/\d/',$pwd) && preg_match('/[^A-Za-z0-9]/',$pwd) &&
        strlen($pwd) >= 9;
  $noSecuencia = stripos($pwd,'123')===false && stripos($pwd,'abc')===false;
  return $ok && $noSecuencia;
}
function generaPasswordPadre(string $apellidosPrimero, string $inicialesHijo, string $fechaNac_ddmmaa): string {
  // regla de negocio: iniciales en mayúsculas empezando con apellido + iniciales hijo + fecha
  return strtoupper($apellidosPrimero) . strtoupper($inicialesHijo) . $fechaNac_ddmmaa;
}
function generaPasswordAdminDocente(string $numEmpleado, string $nombre, string $apellido, string $curp): string {
  $primLetraNombre = mb_strtoupper(mb_substr($nombre,0,1,'UTF-8'),'UTF-8');
  $apellidoLower   = mb_strtolower($apellido,'UTF-8');
  $ult3Curp        = substr($curp, -3);
  return $numEmpleado . $primLetraNombre . $apellidoLower . $ult3Curp;
}
