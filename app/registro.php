<?php
require_once __DIR__ . '/includes/conexion.php';
require_once __DIR__ . '/includes/sesion.php';

if (estaLogueado()) {
    header('Location: ' . (esAdmin() ? 'admin-panel.php' : 'ver-menu.php'));
    exit;
}

$errores = [];
$exito   = false;
$patronLetras = '/^[A-Za-zÁÉÍÓÚáéíóúÑñÜü\s]+$/u';
$patronNumero = '/^[0-9]+$/';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email       = trim($_POST['email'] ?? '');
    $institucion = trim($_POST['institucion'] ?? '');
    $rol         = trim($_POST['rol'] ?? '');
    $password    = (string) ($_POST['password'] ?? '');

    // ---- Validaciones comunes ----
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores[] = 'Ingresa un correo electrónico válido.';
    }
    if ($institucion === '' || !preg_match($patronLetras, $institucion)) {
        $errores[] = 'La institución educativa solo puede contener letras y espacios.';
    }
    if (!in_array($rol, ['estudiante', 'profesor'], true)) {
        $errores[] = 'Selecciona un rol válido.';
    }
    if (strlen($password) < 6) {
        $errores[] = 'La contraseña debe tener al menos 6 caracteres.';
    }

    $nombreCompletoUsuario = '';

    if ($rol === 'profesor') {
        $nombreCompleto = trim($_POST['nombre_completo'] ?? '');
        if ($nombreCompleto === '' || !preg_match($patronLetras, $nombreCompleto)) {
            $errores[] = 'El nombre completo solo puede contener letras y espacios.';
        }
        $nombreCompletoUsuario = $nombreCompleto;
    }

    // ---- Datos exclusivos de Estudiante (tablas Estudiante / Curso / Acudiente) ----
    $nombres            = trim($_POST['nombres'] ?? '');
    $apellidos          = trim($_POST['apellidos'] ?? '');
    $documentoEstudiante = trim($_POST['documento_estudiante'] ?? '');
    $cursoId            = (int) ($_POST['curso_id'] ?? 0);
    $celularEstudiante  = trim($_POST['celular_estudiante'] ?? '');

    $nombreAcudiente     = trim($_POST['nombre_acudiente'] ?? '');
    $apellidoAcudiente   = trim($_POST['apellido_acudiente'] ?? '');
    $documentoAcudiente  = trim($_POST['documento_acudiente'] ?? '');
    $emailAcudiente      = trim($_POST['email_acudiente'] ?? '');
    $celularAcudiente    = trim($_POST['celular_acudiente'] ?? '');

    if ($rol === 'estudiante') {
        if ($nombres === '' || !preg_match($patronLetras, $nombres)) {
            $errores[] = 'Los nombres del estudiante solo pueden contener letras y espacios.';
        }
        if ($apellidos === '' || !preg_match($patronLetras, $apellidos)) {
            $errores[] = 'Los apellidos del estudiante solo pueden contener letras y espacios.';
        }
        if ($documentoEstudiante === '' || !preg_match($patronNumero, $documentoEstudiante)) {
            $errores[] = 'El número de documento del estudiante solo puede contener números.';
        }
        if ($cursoId <= 0) {
            $errores[] = 'Selecciona el curso del estudiante.';
        }
        if ($celularEstudiante !== '' && !preg_match($patronNumero, $celularEstudiante)) {
            $errores[] = 'El celular del estudiante solo puede contener números.';
        }

        if ($nombreAcudiente === '' || !preg_match($patronLetras, $nombreAcudiente)) {
            $errores[] = 'El nombre del acudiente solo puede contener letras y espacios.';
        }
        if ($apellidoAcudiente === '' || !preg_match($patronLetras, $apellidoAcudiente)) {
            $errores[] = 'El apellido del acudiente solo puede contener letras y espacios.';
        }
        if ($documentoAcudiente === '' || !preg_match($patronNumero, $documentoAcudiente)) {
            $errores[] = 'El número de documento del acudiente solo puede contener números.';
        }
        if ($emailAcudiente !== '' && !filter_var($emailAcudiente, FILTER_VALIDATE_EMAIL)) {
            $errores[] = 'El correo del acudiente no es válido.';
        }
        if ($celularAcudiente !== '' && !preg_match($patronNumero, $celularAcudiente)) {
            $errores[] = 'El celular del acudiente solo puede contener números.';
        }

        $nombreCompletoUsuario = trim($nombres . ' ' . $apellidos);
    }

    // ---- Cursos disponibles (para volver a mostrarlos si hay errores) ----
    $cursos = $pdo->query(
        'SELECT c.CursoId, c.Nombre AS Curso, g.Nombre AS Grado
         FROM Curso c INNER JOIN Grado g ON g.GradoId = c.GradoId
         ORDER BY g.GradoId, c.Nombre'
    )->fetchAll();

    if (!$errores) {
        try {
            $consulta = $pdo->prepare('SELECT UsuarioId FROM Usuario WHERE Email = :email');
            $consulta->execute([':email' => $email]);
            if ($consulta->fetch()) {
                $errores[] = 'Ya existe una cuenta registrada con ese correo electrónico.';
            }

            if ($rol === 'estudiante' && !$errores) {
                $consultaDoc = $pdo->prepare('SELECT EstudianteId FROM Estudiante WHERE NumeroDocumento = :doc');
                $consultaDoc->execute([':doc' => $documentoEstudiante]);
                if ($consultaDoc->fetch()) {
                    $errores[] = 'Ya existe un estudiante registrado con ese número de documento.';
                }
            }
        } catch (PDOException $e) {
            $errores[] = 'Ocurrió un error al validar los datos: ' . $e->getMessage();
        }
    }

    if (!$errores) {
        try {
            $pdo->beginTransaction();

            $consultaRol = $pdo->prepare('SELECT RolId FROM Rol WHERE Nombre = :nombre');
            $consultaRol->execute([':nombre' => $rol]);
            $filaRol = $consultaRol->fetch();

            if (!$filaRol) {
                throw new RuntimeException('El rol seleccionado no existe en la base de datos.');
            }

            $hash = password_hash($password, PASSWORD_DEFAULT);

            $insertarUsuario = $pdo->prepare(
                'INSERT INTO Usuario (Nombre, Email, `Contraseña`, RolId, Institucion)
                 VALUES (:nombre, :email, :password, :rolid, :institucion)'
            );
            $insertarUsuario->execute([
                ':nombre'      => $nombreCompletoUsuario,
                ':email'       => $email,
                ':password'    => $hash,
                ':rolid'       => $filaRol['RolId'],
                ':institucion' => $institucion,
            ]);
            $usuarioId = (int) $pdo->lastInsertId();

            if ($rol === 'estudiante') {
                // Se busca un acudiente existente con ese documento, o se crea uno nuevo
                $consultaAcudiente = $pdo->prepare('SELECT AcudienteId FROM Acudiente WHERE NumeroDocumento = :doc');
                $consultaAcudiente->execute([':doc' => $documentoAcudiente]);
                $filaAcudiente = $consultaAcudiente->fetch();

                if ($filaAcudiente) {
                    $acudienteId = (int) $filaAcudiente['AcudienteId'];
                } else {
                    $insertarAcudiente = $pdo->prepare(
                        'INSERT INTO Acudiente (Nombre, Apellido, NumeroDocumento, Email, Celular)
                         VALUES (:nombre, :apellido, :documento, :email, :celular)'
                    );
                    $insertarAcudiente->execute([
                        ':nombre'    => $nombreAcudiente,
                        ':apellido'  => $apellidoAcudiente,
                        ':documento' => $documentoAcudiente,
                        ':email'     => $emailAcudiente !== '' ? $emailAcudiente : null,
                        ':celular'   => $celularAcudiente !== '' ? $celularAcudiente : null,
                    ]);
                    $acudienteId = (int) $pdo->lastInsertId();
                }

                $insertarEstudiante = $pdo->prepare(
                    'INSERT INTO Estudiante (Nombre, Apellido, UsuarioId, NumeroDocumento, CursoId, AcudienteId, Email, Celular)
                     VALUES (:nombre, :apellido, :usuarioId, :documento, :cursoId, :acudienteId, :email, :celular)'
                );
                $insertarEstudiante->execute([
                    ':nombre'      => $nombres,
                    ':apellido'    => $apellidos,
                    ':usuarioId'   => $usuarioId,
                    ':documento'   => $documentoEstudiante,
                    ':cursoId'     => $cursoId,
                    ':acudienteId' => $acudienteId,
                    ':email'       => $email,
                    ':celular'     => $celularEstudiante !== '' ? $celularEstudiante : null,
                ]);
            }

            $pdo->commit();
            $exito = true;
        } catch (Throwable $e) {
            $pdo->rollBack();
            $errores[] = 'No se pudo completar el registro: ' . $e->getMessage();
        }
    }
} else {
    $cursos = $pdo->query(
        'SELECT c.CursoId, c.Nombre AS Curso, g.Nombre AS Grado
         FROM Curso c INNER JOIN Grado g ON g.GradoId = c.GradoId
         ORDER BY g.GradoId, c.Nombre'
    )->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Registrarse – NutriEscolar</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="Estilos/shared.css">
<style>
.hero{
  background-image:
    linear-gradient(rgba(0,0,0,0.52),rgba(0,0,0,0.52)),
    url('Imagenes/Alimentacion_escolar-Foto_1.jpg');
  padding:100px 16px 40px;
}
.campo select{margin-top:8px;}
.alerta{border-radius:8px;padding:12px 16px;font-size:13px;margin-bottom:18px;}
.alerta-error{background:#fff3f3;border-left:4px solid #e53935;color:#b71c1c;}
.alerta-exito{background:#f0fff4;border-left:4px solid #2e7d32;color:#1b5e20;}
.subtitulo-seccion{
  font-size:13px;font-weight:700;color:var(--naranja-oscuro);
  text-transform:uppercase;letter-spacing:0.5px;
  margin:22px 0 4px;
}
</style>
</head>
<body>

<nav class="navbar">
  <a href="index.php" class="nav-logo">
    <div class="nav-logo-icono">NE</div>
    <span class="nav-logo-nombre">NutriEscolar</span>
  </a>
  <button class="hamburger" id="hamburger" aria-label="Menú">
    <span></span><span></span><span></span>
  </button>
  <div class="nav-menu" id="navMenu">
    <a href="ver-menu.php">Ver menú</a>
    <a href="consultar-menu.php">Consultar menú</a>
    <a href="informacion-nutricional.php">Nutrición</a>
    <a href="inicio-sesion.php">Iniciar sesión</a>
  </div>
</nav>

<section class="hero">
  <div class="card card-ancha">
    <h1>Registrar Usuario</h1>

    <?php if ($exito): ?>
      <div class="alerta alerta-exito">
        ¡Registro exitoso! Ya puedes <a href="inicio-sesion.php">iniciar sesión</a>.
      </div>
    <?php endif; ?>

    <?php if ($errores): ?>
      <div class="alerta alerta-error">
        <?php foreach ($errores as $error): ?>
          <div>• <?= htmlspecialchars($error) ?></div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php $rolActual = $_POST['rol'] ?? ''; ?>
    <form data-validar-form novalidate method="post" action="registro.php" id="formularioRegistro">

      <div class="paneles">

        <!-- ===== Panel izquierdo: persona que se registra ===== -->
        <div class="panel-form">
          <div class="titulo-panel" id="tituloPersona">Datos personales</div>
          <div class="form-grid cols-2">

            <div class="campo span-2">
              <select name="rol" id="rolSelect" required onchange="alternarCamposRol()">
                <option value="" disabled <?= $rolActual === '' ? 'selected' : '' ?>>Seleccionar rol</option>
                <option value="estudiante" <?= $rolActual === 'estudiante' ? 'selected' : '' ?>>Estudiante</option>
                <option value="profesor" <?= $rolActual === 'profesor' ? 'selected' : '' ?>>Profesor</option>
              </select>
            </div>

            <!-- Solo PROFESOR -->
            <div id="bloqueProfesor" class="campo span-2">
              <input type="text" name="nombre_completo" data-validar="letras" autocomplete="name"
                     value="<?= htmlspecialchars($_POST['nombre_completo'] ?? '') ?>">
              <label>Nombre completo</label>
            </div>

            <!-- Solo ESTUDIANTE -->
            <div class="campo solo-estudiante">
              <input type="text" name="nombres" data-validar="letras" autocomplete="given-name"
                     value="<?= htmlspecialchars($_POST['nombres'] ?? '') ?>">
              <label>Nombres</label>
            </div>
            <div class="campo solo-estudiante">
              <input type="text" name="apellidos" data-validar="letras" autocomplete="family-name"
                     value="<?= htmlspecialchars($_POST['apellidos'] ?? '') ?>">
              <label>Apellidos</label>
            </div>
            <div class="campo solo-estudiante">
              <input type="text" name="documento_estudiante" inputmode="numeric" data-validar="numeros"
                     value="<?= htmlspecialchars($_POST['documento_estudiante'] ?? '') ?>">
              <label>Documento del estudiante</label>
            </div>
            <div class="campo solo-estudiante">
              <select name="curso_id">
                <option value="" disabled <?= empty($_POST['curso_id']) ? 'selected' : '' ?>>Seleccionar curso</option>
                <?php foreach ($cursos as $c): ?>
                  <option value="<?= (int) $c['CursoId'] ?>" <?= (int) ($_POST['curso_id'] ?? 0) === (int) $c['CursoId'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($c['Curso'] . ' — ' . $c['Grado']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="campo solo-estudiante span-2">
              <input type="text" name="celular_estudiante" inputmode="numeric" data-validar="numeros"
                     value="<?= htmlspecialchars($_POST['celular_estudiante'] ?? '') ?>">
              <label>Celular del estudiante (opcional)</label>
            </div>

          </div>
        </div>

        <!-- ===== Panel derecho: acudiente (solo estudiantes) ===== -->
        <div class="panel-form" id="panelAcudiente">
          <div class="titulo-panel">Datos del acudiente</div>
          <div class="form-grid cols-2">
            <div class="campo">
              <input type="text" name="nombre_acudiente" data-validar="letras"
                     value="<?= htmlspecialchars($_POST['nombre_acudiente'] ?? '') ?>">
              <label>Nombre del acudiente</label>
            </div>
            <div class="campo">
              <input type="text" name="apellido_acudiente" data-validar="letras"
                     value="<?= htmlspecialchars($_POST['apellido_acudiente'] ?? '') ?>">
              <label>Apellido del acudiente</label>
            </div>
            <div class="campo">
              <input type="text" name="documento_acudiente" inputmode="numeric" data-validar="numeros"
                     value="<?= htmlspecialchars($_POST['documento_acudiente'] ?? '') ?>">
              <label>Documento del acudiente</label>
            </div>
            <div class="campo">
              <input type="text" name="celular_acudiente" inputmode="numeric" data-validar="numeros"
                     value="<?= htmlspecialchars($_POST['celular_acudiente'] ?? '') ?>">
              <label>Celular (opcional)</label>
            </div>
            <div class="campo span-2">
              <input type="email" name="email_acudiente" autocomplete="email"
                     value="<?= htmlspecialchars($_POST['email_acudiente'] ?? '') ?>">
              <label>Correo del acudiente (opcional)</label>
            </div>
          </div>
        </div>

      </div>

      <!-- ===== Datos de la cuenta (fila completa) ===== -->
      <div class="titulo-panel" style="margin-top:14px;">Datos de la cuenta</div>
      <div class="form-grid cols-3">
        <div class="campo">
          <input type="email" name="email" required autocomplete="email"
                 value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
          <label>Correo electrónico</label>
        </div>
        <div class="campo">
          <input type="text" name="institucion" required data-validar="letras" autocomplete="organization"
                 value="<?= htmlspecialchars($_POST['institucion'] ?? '') ?>">
          <label>Institución educativa</label>
        </div>
        <div class="campo">
          <input type="password" name="password" required data-pass="nueva" autocomplete="new-password">
          <label>Contraseña</label>
        </div>
      </div>

      <div class="acciones">
        <button type="submit" class="btn">Registrarse</button>
      </div>
    </form>
    <div class="link-secundario">
      ¿Ya tienes una cuenta? <a href="inicio-sesion.php">Inicia sesión</a>
    </div>
  </div>
</section>

<script src="js/validaciones.js"></script>
<script>
const ham=document.getElementById('hamburger'),navMenu=document.getElementById('navMenu');
if(ham){ham.addEventListener('click',()=>navMenu.classList.toggle('abierto'));document.addEventListener('click',e=>{if(!ham.contains(e.target)&&!navMenu.contains(e.target))navMenu.classList.remove('abierto');});}

function alternarCamposRol(){
  const rol = document.getElementById('rolSelect').value;
  const esEst  = rol === 'estudiante';
  const esProf = rol === 'profesor';

  const bloqueProfesor = document.getElementById('bloqueProfesor');
  const panelAcudiente = document.getElementById('panelAcudiente');
  const titulo         = document.getElementById('tituloPersona');

  bloqueProfesor.style.display = esProf ? '' : 'none';
  panelAcudiente.style.display = esEst ? '' : 'none';
  document.querySelectorAll('.solo-estudiante').forEach(b => { b.style.display = esEst ? '' : 'none'; });
  titulo.textContent = esEst ? 'Datos del estudiante' : (esProf ? 'Datos del profesor' : 'Datos personales');

  // Solo son obligatorios los campos del rol elegido (los opcionales nunca)
  const opcionales = ['email_acudiente', 'celular_acudiente', 'celular_estudiante'];
  bloqueProfesor.querySelectorAll('input').forEach(c => { c.required = esProf; });
  document.querySelectorAll('.solo-estudiante input, .solo-estudiante select, #panelAcudiente input').forEach(c => {
    c.required = esEst && !opcionales.includes(c.name);
  });
}
alternarCamposRol();
</script>
</body>
</html>
