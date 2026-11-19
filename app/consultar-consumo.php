<?php
require_once __DIR__ . '/includes/conexion.php';
require_once __DIR__ . '/includes/sesion.php';
require_once __DIR__ . '/includes/estadisticas.php';
require_once __DIR__ . '/includes/nav.php';
// Pueden consultar: el administrador (cualquier estudiante), el estudiante (solo lo suyo)
// y el acudiente (solo sus estudiantes). El profesor no tiene acceso.
requerirRol(['admin', 'estudiante', 'acudiente']);

$rol       = rolActual();
$uidSesion = (int) $_SESSION['usuario_id'];

$usuarioBuscado = trim($_GET['usuario'] ?? $_POST['usuario'] ?? '');      // solo lo usa el admin
$estudianteId   = (int) ($_GET['estudiante'] ?? $_POST['estudiante'] ?? 0); // estudiante / acudiente
$fecha          = trim($_GET['fecha'] ?? $_POST['fecha'] ?? '');
$periodo        = (string) ($_GET['periodo'] ?? $_POST['periodo'] ?? '30');
if (!in_array($periodo, ['30', '90', 'todo'], true)) {
    $periodo = '30';
}
$seConsulto = isset($_GET['consultar']) || isset($_POST['enviar_reporte']);

$error          = '';
$mensajeReporte = '';
$resultado      = null;
$estudiante     = null;
$permitidos     = [];
$estadisticas   = null;
$historial      = [];
$emailDestino   = '';

$sqlEstudiante = 'SELECT e.EstudianteId, e.UsuarioId, e.Nombre, e.Apellido, c.Nombre AS Curso, u.Email
                  FROM Estudiante e
                  INNER JOIN Curso c    ON c.CursoId   = e.CursoId
                  INNER JOIN Usuario u  ON u.UsuarioId = e.UsuarioId';

try {
    // ---- 1) A qué estudiantes puede acceder esta cuenta ----
    if ($rol === 'estudiante') {
        $consulta = $pdo->prepare($sqlEstudiante . ' WHERE e.UsuarioId = :uid ORDER BY e.Apellido, e.Nombre');
        $consulta->execute([':uid' => $uidSesion]);
        $permitidos = $consulta->fetchAll();
    } elseif ($rol === 'acudiente') {
        $consulta = $pdo->prepare(
            $sqlEstudiante . ' INNER JOIN Acudiente ac ON ac.AcudienteId = e.AcudienteId
             WHERE ac.UsuarioId = :uid ORDER BY e.Apellido, e.Nombre'
        );
        $consulta->execute([':uid' => $uidSesion]);
        $permitidos = $consulta->fetchAll();
    }

    // ---- 2) Estudiante que se va a mostrar ----
    if ($rol === 'admin') {
        if ($seConsulto) {
            if ($usuarioBuscado === '' || !preg_match('/^[A-Za-zÁÉÍÓÚáéíóúÑñÜü\s]+$/u', $usuarioBuscado)) {
                $error = 'El nombre del estudiante solo admite letras.';
            } else {
                $consulta = $pdo->prepare(
                    $sqlEstudiante . " WHERE u.Nombre = :nombre1 OR CONCAT(e.Nombre, ' ', e.Apellido) = :nombre2 LIMIT 1"
                );
                $consulta->execute([':nombre1' => $usuarioBuscado, ':nombre2' => $usuarioBuscado]);
                $estudiante = $consulta->fetch() ?: null;
                if (!$estudiante) {
                    $error = 'Estudiante no encontrado.';
                }
            }
        }
    } elseif (!$permitidos) {
        $error = $rol === 'acudiente'
            ? 'Tu cuenta todavía no tiene estudiantes vinculados. Verifica que el documento con el que te registraste sea el que se indicó al registrar al estudiante.'
            : 'Tu cuenta no tiene un estudiante asociado.';
    } else {
        $estudiante = $permitidos[0];
        foreach ($permitidos as $p) {
            if ((int) $p['EstudianteId'] === $estudianteId) {
                $estudiante = $p;
            }
        }
    }

    if ($estudiante) {
        // Correo al que se envía el reporte: admin -> el del estudiante; estudiante/acudiente -> el propio
        if ($rol === 'admin') {
            $emailDestino = (string) $estudiante['Email'];
        } else {
            $consultaMail = $pdo->prepare('SELECT Email FROM Usuario WHERE UsuarioId = :uid');
            $consultaMail->execute([':uid' => $uidSesion]);
            $emailDestino = (string) $consultaMail->fetchColumn();
        }

        // ---- 3) Estadísticas e historial del período elegido ----
        $diasPeriodo  = consumoPorDia($pdo, (int) $estudiante['UsuarioId'], fechaDesdePeriodo($periodo));
        $estadisticas = resumenEstadisticas($diasPeriodo);
        $historial    = array_slice($diasPeriodo, 0, 15, true);

        // ---- 4) Detalle de un día (y envío del reporte por correo) ----
        if ($seConsulto) {
            if ($fecha === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
                $error = 'Selecciona una fecha.';
            } else {
                $consultaAlimentos = $pdo->prepare(
                    'SELECT a.Nombre, i.Calorias, i.Proteinas, i.Carbohidratos, i.Grasas
                     FROM ConsumoAlimento ca
                     INNER JOIN Alimento a ON a.AlimentoId = ca.AlimentoId
                     LEFT JOIN InformacionNutricional i ON i.AlimentoId = a.AlimentoId
                     WHERE ca.UsuarioId = :uid AND ca.Fecha = :fecha
                     ORDER BY a.Nombre'
                );
                $consultaAlimentos->execute([':uid' => $estudiante['UsuarioId'], ':fecha' => $fecha]);
                $alimentos = $consultaAlimentos->fetchAll();

                if (!$alimentos) {
                    $error = 'No hay alimentos registrados para ese estudiante en esa fecha.';
                } else {
                    $totales = ['calorias' => 0, 'proteinas' => 0, 'carbohidratos' => 0, 'grasas' => 0];
                    $nombresAlimentos = [];
                    foreach ($alimentos as $a) {
                        $nombresAlimentos[]        = $a['Nombre'];
                        $totales['calorias']      += (float) $a['Calorias'];
                        $totales['proteinas']     += (float) $a['Proteinas'];
                        $totales['carbohidratos'] += (float) $a['Carbohidratos'];
                        $totales['grasas']        += (float) $a['Grasas'];
                    }
                    $evaluacion = generarEvaluacionConsumo($totales);

                    $resultado = [
                        'nombre'     => trim($estudiante['Nombre'] . ' ' . $estudiante['Apellido']),
                        'fecha'      => $fecha,
                        'alimentos'  => $nombresAlimentos,
                        'evaluacion' => $evaluacion,
                    ];

                    if (isset($_POST['enviar_reporte'])) {
                        if ($emailDestino === '') {
                            $error = 'No hay un correo registrado al cual enviar el reporte.';
                        } else {
                            $nombreDestino = $rol === 'admin' ? $resultado['nombre'] : nombreUsuarioActual();
                            $enviado = enviarReporteConsumo(
                                $emailDestino, $nombreDestino, $fecha, $nombresAlimentos, $evaluacion,
                                $rol === 'acudiente' ? $resultado['nombre'] : ''
                            );
                            $mensajeReporte = $enviado
                                ? "Reporte enviado a {$emailDestino}."
                                : 'No se pudo enviar el reporte. Revisa la configuración SMTP en includes/correo.php.';
                        }
                    }
                }
            }
        }
    }
} catch (PDOException $e) {
    $error = 'Ocurrió un error al consultar el consumo: ' . $e->getMessage();
}

$etiquetasEstado = ['bajo' => 'Bajo', 'adecuado' => 'Adecuado', 'alto' => 'Alto'];
$coloresEstado   = ['bajo' => '#e53935', 'adecuado' => '#2e7d32', 'alto' => '#e53935'];
$etiquetasPeriodo = ['30' => 'Últimos 30 días', '90' => 'Últimos 90 días', 'todo' => 'Todo el historial'];

// Parámetro que identifica al estudiante en los enlaces de "Ver detalle"
$parametroEstudiante = ($rol === 'admin')
    ? 'usuario=' . urlencode($usuarioBuscado)
    : 'estudiante=' . (int) ($estudiante['EstudianteId'] ?? 0);

$columnas = ($rol === 'admin' || ($rol === 'acudiente' && count($permitidos) > 1)) ? 4 : 3;
$titulo   = $rol === 'admin' ? 'Consultar Consumo' : ($rol === 'acudiente' ? 'Consumo del Estudiante' : 'Mi Consumo');
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($titulo) ?> – NutriEscolar</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="Estilos/shared.css">
<style>
.hero{
  background-image:
    linear-gradient(rgba(0,0,0,0.52),rgba(0,0,0,0.52)),
    url('Imagenes/Alimentacion_escolar_Bogota_1.jpg');
  padding:100px 16px 40px;
}
.card{max-width:560px;}
.alerta{border-radius:8px;padding:12px 16px;font-size:13px;margin-bottom:18px;}
.alerta-error{background:#fff3f3;border-left:4px solid #e53935;color:#b71c1c;}
.alerta-exito{background:#f0fff4;border-left:4px solid #2e7d32;color:#1b5e20;}
table.tabla-evaluacion{width:100%;border-collapse:collapse;font-size:13px;margin:14px 0;}
table.tabla-evaluacion th{background:#fdf1de;text-align:left;padding:8px 10px;}
table.tabla-evaluacion td{padding:8px 10px;border-bottom:1px solid #eee;vertical-align:top;}
.btn-reporte{background:white;color:var(--naranja-oscuro);border:2px solid var(--naranja);margin-top:10px;}
.seccion{margin-top:26px;}
.nota-seccion{font-size:12px;color:#888;margin:8px 0 0;}
.tabla-scroll{overflow-x:auto;}
.enlace-detalle{color:var(--naranja-oscuro);font-weight:700;text-decoration:none;white-space:nowrap;}
.enlace-detalle:hover{text-decoration:underline;}
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
    <?php renderNavUsuario(); ?>
  </div>
</nav>

<section class="hero">
  <div class="card card-media">
    <h1><?= htmlspecialchars($titulo) ?></h1>

    <?php if ($error): ?>
      <div class="alerta alerta-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($mensajeReporte): ?>
      <div class="alerta alerta-exito"><?= htmlspecialchars($mensajeReporte) ?></div>
    <?php endif; ?>

    <?php if ($rol === 'admin' || $permitidos): ?>
    <form method="get" action="consultar-consumo.php" class="formulario form-grid cols-<?= $columnas ?>" id="formularioConsumo">
      <?php if ($rol === 'admin'): ?>
        <input type="text" id="usuario" name="usuario" placeholder="Nombre del estudiante" data-validar="letras"
               value="<?= htmlspecialchars($usuarioBuscado) ?>">
      <?php elseif (count($permitidos) > 1): ?>
        <select name="estudiante" aria-label="Estudiante">
          <?php foreach ($permitidos as $p): ?>
            <option value="<?= (int) $p['EstudianteId'] ?>" <?= $estudiante && (int) $estudiante['EstudianteId'] === (int) $p['EstudianteId'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($p['Nombre'] . ' ' . $p['Apellido'] . ' — ' . $p['Curso']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      <?php endif; ?>
      <input type="date" id="fecha" name="fecha" value="<?= htmlspecialchars($fecha) ?>">
      <select name="periodo" aria-label="Período de las estadísticas">
        <?php foreach ($etiquetasPeriodo as $valor => $texto): ?>
          <option value="<?= $valor ?>" <?= $periodo === (string) $valor ? 'selected' : '' ?>><?= $texto ?></option>
        <?php endforeach; ?>
      </select>
      <button type="submit" name="consultar" value="1" class="btn">Consultar</button>
    </form>
    <?php endif; ?>

    <?php if ($estudiante && $rol !== 'admin'): ?>
      <div class="info-box" style="margin-top:16px;">
        <div class="etiqueta">Estudiante</div>
        <div class="valor"><?= htmlspecialchars($estudiante['Nombre'] . ' ' . $estudiante['Apellido']) ?> — curso <?= htmlspecialchars($estudiante['Curso']) ?></div>
      </div>
    <?php endif; ?>

    <?php if ($resultado): ?>
      <div class="resultado" style="display:flex;">
        <div class="nombre-resultado"><?= htmlspecialchars(strtoupper($resultado['nombre'])) ?></div>
        <div class="fecha-resultado">Fecha: <?= htmlspecialchars($resultado['fecha']) ?></div>
        <div class="info-box"><div class="etiqueta">Alimentos consumidos</div><div class="valor"><?= htmlspecialchars(implode(', ', $resultado['alimentos'])) ?></div></div>
      </div>

      <div class="tabla-scroll">
      <table class="tabla-evaluacion">
        <thead><tr><th>Nutriente</th><th>Consumido</th><th>Recomendado (almuerzo)</th><th>Estado</th></tr></thead>
        <tbody>
          <?php foreach ($resultado['evaluacion'] as $item): ?>
            <tr>
              <td><?= htmlspecialchars($item['etiqueta']) ?></td>
              <td><?= number_format($item['valor'], 2) ?> <?= htmlspecialchars($item['unidad']) ?></td>
              <td style="color:#888;"><?= $item['min'] ?>–<?= $item['max'] ?> <?= htmlspecialchars($item['unidad']) ?></td>
              <td style="color:<?= $coloresEstado[$item['estado']] ?>;font-weight:bold;"><?= $etiquetasEstado[$item['estado']] ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      </div>

      <?php if ($emailDestino !== ''): ?>
      <form method="post" action="consultar-consumo.php">
        <input type="hidden" name="usuario" value="<?= htmlspecialchars($usuarioBuscado) ?>">
        <input type="hidden" name="estudiante" value="<?= (int) $estudiante['EstudianteId'] ?>">
        <input type="hidden" name="fecha" value="<?= htmlspecialchars($fecha) ?>">
        <input type="hidden" name="periodo" value="<?= htmlspecialchars($periodo) ?>">
        <button type="submit" name="enviar_reporte" value="1" class="btn btn-reporte">
          Enviar este reporte por correo a <?= htmlspecialchars($emailDestino) ?>
        </button>
      </form>
      <?php endif; ?>
    <?php endif; ?>

    <?php if ($estadisticas): ?>
      <div class="seccion">
        <div class="titulo-panel">Estadísticas — <?= htmlspecialchars($etiquetasPeriodo[$periodo]) ?></div>
        <?php if ($estadisticas['dias'] === 0): ?>
          <p class="nota-seccion">Todavía no hay consumo registrado en este período.</p>
        <?php else: ?>
          <p class="nota-seccion">Días con consumo registrado: <strong><?= (int) $estadisticas['dias'] ?></strong>. El promedio se compara con lo recomendado para un almuerzo escolar.</p>
          <div class="tabla-scroll">
          <table class="tabla-evaluacion">
            <thead><tr><th>Nutriente</th><th>Promedio por día</th><th>Recomendado</th><th>Estado</th><th>Días bajo / adecuado / alto</th></tr></thead>
            <tbody>
              <?php foreach ($estadisticas['nutrientes'] as $n): ?>
                <tr>
                  <td><?= htmlspecialchars($n['etiqueta']) ?></td>
                  <td><?= number_format($n['promedio'], 1) ?> <?= htmlspecialchars($n['unidad']) ?></td>
                  <td style="color:#888;"><?= $n['min'] ?>–<?= $n['max'] ?> <?= htmlspecialchars($n['unidad']) ?></td>
                  <td style="color:<?= $coloresEstado[$n['estado']] ?>;font-weight:bold;"><?= $etiquetasEstado[$n['estado']] ?></td>
                  <td><?= (int) $n['conteo']['bajo'] ?> / <?= (int) $n['conteo']['adecuado'] ?> / <?= (int) $n['conteo']['alto'] ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          </div>
        <?php endif; ?>
      </div>

      <?php if ($historial): ?>
      <div class="seccion">
        <div class="titulo-panel">Lo que ha comido (días más recientes)</div>
        <div class="tabla-scroll">
        <table class="tabla-evaluacion">
          <thead><tr><th>Fecha</th><th>Alimentos</th><th>Calorías</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($historial as $dia): ?>
              <tr>
                <td style="white-space:nowrap;"><?= htmlspecialchars($dia['fecha']) ?></td>
                <td><?= htmlspecialchars(implode(', ', $dia['alimentos'])) ?></td>
                <td style="white-space:nowrap;"><?= number_format($dia['totales']['calorias'], 0) ?> kcal</td>
                <td>
                  <a class="enlace-detalle"
                     href="consultar-consumo.php?<?= htmlspecialchars($parametroEstudiante . '&fecha=' . urlencode($dia['fecha']) . '&periodo=' . urlencode($periodo) . '&consultar=1') ?>">Ver detalle</a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        </div>
      </div>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</section>

<script src="js/validaciones.js"></script>
<script>
const ham=document.getElementById('hamburger'),navMenu=document.getElementById('navMenu');
if(ham){ham.addEventListener('click',()=>navMenu.classList.toggle('abierto'));document.addEventListener('click',e=>{if(!ham.contains(e.target)&&!navMenu.contains(e.target))navMenu.classList.remove('abierto');});}
</script>
</body>
</html>
