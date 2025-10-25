<?php
class Usuario {
    private $pdo;
    public function __construct($pdo) { $this->pdo = $pdo; }

    public function listar(): array {
        $stmt = $this->pdo->query("SELECT * FROM usuarios ORDER BY id_usuario DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function crear(array $d): void {
        try {
            $this->pdo->beginTransaction();

            // RN-001 → Boleta automática
            if ($d['tipo_usuario'] === 'Alumno' && empty($d['boleta'])) {
                $d['boleta'] = $this->generarBoleta();
            }

            // RN-006 → Número de empleado automático
            if (in_array($d['tipo_usuario'], ['Docente','Administrador']) && empty($d['numero_empleado'])) {
                $d['numero_empleado'] = $this->generarNumeroEmpleado();
            }

            // RN-004 → Contraseña automática de personal
            if (in_array($d['tipo_usuario'], ['Docente','Administrador']) && empty($d['contrasena'])) {
                $iniNom = strtoupper(substr($d['nombre'],0,1));
                $ap = strtolower($d['apellido_paterno']);
                $suf = !empty($d['curp']) ? substr($d['curp'],-3) : strtoupper(substr(md5(rand()),0,3));
                $d['contrasena'] = "{$d['numero_empleado']}{$iniNom}{$ap}{$suf}";
            }

            // RN-003 → Contraseña de tutor
            if ($d['tipo_usuario']==='Padre' && empty($d['contrasena'])) {
                $inicPadre = strtoupper(substr($d['apellido_paterno'],0,2));
                $inicHijo = strtoupper(substr($d['nombre_alumno'],0,2));
                $anio = (new DateTime($d['fecha_nacimiento_alumno']))->format('Y');
                $d['contrasena'] = "{$inicPadre}{$inicHijo}{$anio}";
            }

            // RN-002 → Validar contraseña de alumno
            if ($d['tipo_usuario']==='Alumno') {
                if (strlen($d['contrasena'])<9 || !preg_match('/[A-Z]/',$d['contrasena']) ||
                    !preg_match('/[a-z]/',$d['contrasena']) || !preg_match('/[0-9]/',$d['contrasena']) ||
                    !preg_match('/[^A-Za-z0-9]/',$d['contrasena'])) {
                    throw new Exception("Contraseña inválida para alumno (RN-002).");
                }
            }

            // RN-005 → Correo institucional
            $dominio = "@escuela.edu.mx";
            if (empty($d['correo_institucional'])) {
                $identificador = match($d['tipo_usuario']) {
                    'Alumno' => $d['boleta'],
                    'Docente','Administrador' => $d['numero_empleado'],
                    'Padre' => strtolower(substr($d['nombre'],0,1).substr($d['apellido_paterno'],0,1)).uniqid(),
                    default => strtolower($d['nombre']).uniqid()
                };
                $d['correo_institucional'] = $identificador.$dominio;
            }

            // Insertar usuario
            $sql="INSERT INTO usuarios (tipo_usuario,nombre,apellido_paterno,apellido_materno,
                    correo_institucional,contrasena_hash,boleta,numero_empleado,estatus,fecha_ingreso,
                    fecha_nacimiento,curp)
                  VALUES (:tipo,:nombre,:ap,:am,:correo,:hash,:boleta,:num_emp,'Activo',NOW(),:nac,:curp)";
            $stmt=$this->pdo->prepare($sql);
            $hash=password_hash($d['contrasena'],PASSWORD_BCRYPT);
            $stmt->execute([
                ':tipo'=>$d['tipo_usuario'],':nombre'=>$d['nombre'],':ap'=>$d['apellido_paterno'],
                ':am'=>$d['apellido_materno']??null,':correo'=>$d['correo_institucional'],':hash'=>$hash,
                ':boleta'=>$d['boleta']??null,':num_emp'=>$d['numero_empleado']??null,
                ':nac'=>$d['fecha_nacimiento']??null,':curp'=>$d['curp']??null
            ]);
            $idUsuario=$this->pdo->lastInsertId();

            if ($d['tipo_usuario']==='Alumno') $this->crearAlumno($idUsuario,$d);
            if ($d['tipo_usuario']==='Docente')
                $this->pdo->prepare("INSERT INTO personal_docente (id_usuario) VALUES (?)")->execute([$idUsuario]);
            if ($d['tipo_usuario']==='Padre' && in_array($d['id_nivel_alumno'],[1,2,3]))
                $this->crearAlumnoDesdeTutor($idUsuario,$d);

            $this->pdo->commit();
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw new Exception("Error al crear usuario: ".$e->getMessage());
        }
    }

    // Generadores
    private function generarBoleta(): string {
        $anio=date('Y'); $clave='43'; $prefijo='0';
        $stmt=$this->pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE tipo_usuario='Alumno' AND YEAR(fecha_ingreso)=YEAR(CURDATE())");
        $stmt->execute(); $total=$stmt->fetchColumn()+1;
        return "{$anio}{$clave}{$prefijo}".str_pad($total,3,'0',STR_PAD_LEFT);
    }
    private function generarNumeroEmpleado(): string {
        $anio=date('Y'); $prefijo='EMP';
        $stmt=$this->pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE tipo_usuario IN ('Docente','Administrador') AND YEAR(fecha_ingreso)=YEAR(CURDATE())");
        $stmt->execute(); $total=$stmt->fetchColumn()+1;
        return "{$prefijo}-{$anio}-".str_pad($total,3,'0',STR_PAD_LEFT);
    }

    // Crear alumno directamente
    private function crearAlumno(int $idUsuario,array $d): void {
        $stmt=$this->pdo->prepare("INSERT INTO alumnos (id_usuario,id_nivel,id_nivel_academico,id_carrera,tutor_id,fecha_ingreso)
                                   VALUES (:u,:n,:na,:c,:t,CURDATE())");
        $stmt->execute([
            ':u'=>$idUsuario,':n'=>$d['id_nivel'],':na'=>$d['id_nivel_academico'],
            ':c'=>$d['id_carrera']??null,':t'=>$d['tutor_id']?:null
        ]);
        $idAlumno=$this->pdo->lastInsertId();
        $this->pdo->prepare("INSERT INTO historial_academico (id_alumno,id_nivel_academico,estatus,fecha_asignacion)
                             VALUES (:a,:na,'Cursando',CURDATE())")->execute([':a'=>$idAlumno,':na'=>$d['id_nivel_academico']]);
    }

    // Crear alumno desde tutor
    private function crearAlumnoDesdeTutor(int $idTutor,array $d): void {
        $boleta=$this->generarBoleta();
        $correo=$boleta."@escuela.edu.mx";
        $hash=password_hash($d['contrasena_alumno']??'Alumno123@',PASSWORD_BCRYPT);
        $stmt=$this->pdo->prepare("INSERT INTO usuarios (tipo_usuario,nombre,apellido_paterno,apellido_materno,
                                   correo_institucional,contrasena_hash,boleta,estatus,fecha_ingreso,fecha_nacimiento)
                                   VALUES ('Alumno',:n,:ap,:am,:c,:h,:b,'Activo',NOW(),:fn)");
        $stmt->execute([':n'=>$d['nombre_alumno'],':ap'=>$d['apellido_paterno_alumno'],':am'=>$d['apellido_materno_alumno']??null,
                        ':c'=>$correo,':h'=>$hash,':b'=>$boleta,':fn'=>$d['fecha_nacimiento_alumno']??null]);
        $idUsuarioAlumno=$this->pdo->lastInsertId();

        $stmtA=$this->pdo->prepare("INSERT INTO alumnos (id_usuario,id_nivel,id_nivel_academico,tutor_id,fecha_ingreso)
                                   VALUES (:u,:n,:na,:t,CURDATE())");
        $stmtA->execute([':u'=>$idUsuarioAlumno,':n'=>$d['id_nivel_alumno'],':na'=>$d['id_nivel_academico_alumno'],':t'=>$idTutor]);
        $idAlumno=$this->pdo->lastInsertId();
        $this->pdo->prepare("INSERT INTO historial_academico (id_alumno,id_nivel_academico,estatus,fecha_asignacion)
                             VALUES (:a,:na,'Cursando',CURDATE())")->execute([':a'=>$idAlumno,':na'=>$d['id_nivel_academico_alumno']]);
    }
    public function obtenerPorId(int $id): array|false {
    $stmt = $this->pdo->prepare("SELECT * FROM usuarios WHERE id_usuario = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

public function actualizar(array $d): void {
    $sql = "UPDATE usuarios 
            SET nombre = :nombre,
                apellido_paterno = :ap,
                apellido_materno = :am,
                correo_institucional = :correo,
                tipo_usuario = :tipo,
                estatus = :estatus
            WHERE id_usuario = :id";

    $stmt = $this->pdo->prepare($sql);
    $stmt->execute([
        ':nombre'  => $d['nombre'],
        ':ap'      => $d['apellido_paterno'],
        ':am'      => $d['apellido_materno'] ?? null,
        ':correo'  => $d['correo_institucional'],
        ':tipo'    => $d['tipo_usuario'],
        ':estatus' => $d['estatus'],
        ':id'      => $d['id_usuario']
    ]);

    if (!empty($d['contrasena'])) {
        $hash = password_hash($d['contrasena'], PASSWORD_BCRYPT);
        $u = $this->pdo->prepare("UPDATE usuarios SET contrasena_hash = ? WHERE id_usuario = ?");
        $u->execute([$hash, $d['id_usuario']]);
    }
}

public function eliminar(int $id): void {
    $stmt = $this->pdo->prepare("DELETE FROM usuarios WHERE id_usuario = ?");
    $stmt->execute([$id]);
}

public function verificarCredenciales($identificador, $contrasena) {
    try {
        $sql = "SELECT * FROM usuarios 
                WHERE (boleta = :identificador OR numero_empleado = :identificador) 
                AND estatus = 'Activo' 
                LIMIT 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['identificador' => $identificador]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuario && password_verify($contrasena, $usuario['contrasena_hash'])) {
            return $usuario;
        } else {
            return false;
        }

    } catch (PDOException $e) {
        error_log("Error en verificarCredenciales: " . $e->getMessage());
        return false;
    }
}

public function actualizarSesion($id_usuario, $token) {
    $sql = "UPDATE usuarios 
            SET token_sesion = :token, 
                ultimo_login = NOW() 
            WHERE id_usuario = :id";

    $stmt = $this->pdo->prepare($sql);
    $stmt->execute([
        'token' => $token,
        'id'    => $id_usuario
    ]);
}

public function incrementarIntentos($id_usuario) {
    $sql = "UPDATE usuarios 
            SET intentos_login = intentos_login + 1 
            WHERE id_usuario = :id";

    $stmt = $this->pdo->prepare($sql);
    $stmt->execute(['id' => $id_usuario]);
}

public function reiniciarIntentos($id_usuario) {
    $sql = "UPDATE usuarios 
            SET intentos_login = 0 
            WHERE id_usuario = :id";

    $stmt = $this->pdo->prepare($sql);
    $stmt->execute(['id' => $id_usuario]);
}

}
?>
