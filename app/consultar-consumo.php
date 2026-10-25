<?php
require_once __DIR__ . '/includes/conexion.php';
require_once __DIR__ . '/includes/sesion.php';
require_once __DIR__ . '/includes/reporte.php';
requerirLogin();

$usuarioBuscado = trim($_GET['usuario'] ?? $_POST['usuario'] ?? '');
$fecha          = trim($_GET['fecha'] ?? $_POST['fecha'] ?? '');
$seConsultó     = isset($_GET['consultar']) || isset($_POST['enviar_reporte']);
$error          = '';
$mensajeReporte = '';
$resultado      = null;

if ($seConsultó) {
    if ($usuarioBuscado === '' || !preg_match('/^[A-Za-zÁÉÍÓÚáéíóúÑñÜü\s]+$/u', $usuarioBuscado)) {
        $error = 'El nombre del usuario solo admite letras.';
    } elseif ($fecha === '') {
        $error = 'Selecciona una fecha.';
    } else {
        try {
            $consultaUsuario = $pdo->prepare('SELECT UsuarioId, Nombre, Email FROM Usuario WHERE Nombre = :nombre LIMIT 1');
            $consultaUsuario->execute([':nombre' => $usuarioBuscado]);
            $usuario = $consultaUsuario->fetch();

            if (!$usuario) {
                $error = 'Usuario no encontrado.';
            } else {
                $consultaAlimentos = $pdo->prepare(
                    'SELECT a.Nombre, i.Calorias, i.Proteinas, i.Carbohidratos, i.Grasas
                     FROM ConsumoAlimento ca
                     INNER JOIN Alimento a ON a.AlimentoId = ca.AlimentoId
                     LEFT JOIN InformacionNutricional i ON i.AlimentoId = a.AlimentoId
                     WHERE ca.UsuarioId = :uid AND ca.Fecha = :fecha
                     ORDER BY a.Nombre'
                );
                $consultaAlimentos->execute([':uid' => $usuario['UsuarioId'], ':fecha' => $fecha]);
                $alimentos = $consultaAlimentos->fetchAll();

                if (!$alimentos) {
                    $error = 'No hay alimentos registrados para ese usuario en esa fecha. '
                        . '¿Ya usó "Registrar mi consumo"?';
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
                        'usuarioId'  => $usuario['UsuarioId'],
                        'email'      => $usuario['Email'],
                        'nombre'     => $usuario['Nombre'],
                        'fecha'      => $fecha,
                        'alimentos'  => $nombresAlimentos,
                        'evaluacion' => $evaluacion,
                    ];

                    // ---- Envío del reporte por correo (a demanda) ----
                    if (isset($_POST['enviar_reporte'])) {
                        $enviado = enviarReporteConsumo($usuario['Email'], $usuario['Nombre'], $fecha, $nombresAlimentos, $evaluacion);
                        $mensajeReporte = $enviado
                            ? "Reporte enviado a {$usuario['Email']}."
                            : 'No se pudo enviar el reporte. Revisa la configuración SMTP en includes/correo.php.';
                    }
                }
            }
        } catch (PDOException $e) {
            $error = 'Ocurrió un error al consultar el consumo: ' . $e->getMessage();
        }
    }
}

$etiquetasEstado = ['bajo' => 'Bajo', 'adecuado' => 'Adecuado', 'alto' => 'Alto'];
$coloresEstado    = ['bajo' => '#e53935', 'adecuado' => '#2e7d32', 'alto' => '#e53935'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Consultar Consumo – NutriEscolar</title>
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
table.tabla-evaluacion td{padding:8px 10px;border-bottom:1px solid #eee;}
.btn-reporte{background:white;color:var(--naranja-oscuro);border:2px solid var(--naranja);margin-top:10px;}
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
    <a href="registrar-consumo.php">Registrar consumo</a>
    <a href="consultar-menu.php">Consultar menú</a>
    <a href="cerrar-sesion.php" class="btn-salir">Cerrar sesión</a>
  </div>
</nav>

<section class="hero">
  <div class="card card-media">
    <h1>Consultar Consumo</h1>

    <?php if ($error): ?>
      <div class="alerta alerta-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($mensajeReporte): ?>
      <div class="alerta alerta-exito"><?= htmlspecialchars($mensajeReporte) ?></div>
    <?php endif; ?>

    <form method="get" action="consultar-consumo.php" class="formulario form-grid cols-3" id="formularioConsumo">
      <input type="text" id="usuario" name="usuario" placeholder="Nombre del usuario" data-validar="letras"
             value="<?= htmlspecialchars($usuarioBuscado) ?>">
      <input type="date" id="fecha" name="fecha" value="<?= htmlspecialchars($fecha) ?>">
      <button type="submit" name="consultar" value="1" class="btn">Consultar</button>
    </form>

    <?php if ($resultado): ?>
      <div class="resultado" style="display:flex;">
        <div class="nombre-resultado"><?= htmlspecialchars(strtoupper($resultado['nombre'])) ?></div>
        <div class="fecha-resultado">Fecha: <?= htmlspecialchars($resultado['fecha']) ?></div>
        <div class="info-box"><div class="etiqueta">Alimentos consumidos</div><div class="valor"><?= htmlspecialchars(implode(', ', $resultado['alimentos'])) ?></div></div>
      </div>

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

      <form method="post" action="consultar-consumo.php">
        <input type="hidden" name="usuario" value="<?= htmlspecialchars($usuarioBuscado) ?>">
        <input type="hidden" name="fecha" value="<?= htmlspecialchars($fecha) ?>">
        <button type="submit" name="enviar_reporte" value="1" class="btn btn-reporte">
          Enviar este reporte por correo a <?= htmlspecialchars($resultado['email']) ?>
        </button>
      </form>
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
