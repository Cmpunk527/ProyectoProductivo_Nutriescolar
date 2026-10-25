<?php
require_once __DIR__ . '/includes/conexion.php';
require_once __DIR__ . '/includes/sesion.php';
requerirLogin();

$dia          = trim($_GET['dia'] ?? '');
$fecha        = trim($_GET['fecha'] ?? '');
$resultado    = null;
$error        = '';
$seConsultó   = isset($_GET['consultar']);

$diasValidos = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes'];

if ($seConsultó) {
    if ($dia === '' || !in_array($dia, $diasValidos, true)) {
        $error = 'Selecciona un día válido.';
    } else {
        try {
            if ($fecha !== '') {
                $consulta = $pdo->prepare(
                    "SELECT MenuId, Fecha, Descripcion FROM Menu
                     WHERE Fecha = :fecha AND LOWER(Descripcion) LIKE :dia LIMIT 1"
                );
                $consulta->execute([':fecha' => $fecha, ':dia' => '%' . $dia . '%']);
            } else {
                $consulta = $pdo->prepare(
                    "SELECT MenuId, Fecha, Descripcion FROM Menu
                     WHERE LOWER(Descripcion) LIKE :dia ORDER BY Fecha DESC LIMIT 1"
                );
                $consulta->execute([':dia' => '%' . $dia . '%']);
            }
            $menu = $consulta->fetch();

            if (!$menu) {
                $error = $fecha !== ''
                    ? 'No hay menú registrado para ese día y esa fecha.'
                    : 'No hay ningún menú registrado para ese día.';
            } else {
                $alimentos = $pdo->prepare(
                    'SELECT a.Nombre FROM MenuAlimento ma
                     INNER JOIN Alimento a ON a.AlimentoId = ma.AlimentoId
                     WHERE ma.MenuId = :menuId
                     ORDER BY a.Nombre'
                );
                $alimentos->execute([':menuId' => $menu['MenuId']]);

                $resultado = [
                    'dia'       => $dia,
                    'fecha'     => $menu['Fecha'],
                    'alimentos' => $alimentos->fetchAll(PDO::FETCH_COLUMN),
                ];
            }
        } catch (PDOException $e) {
            $error = 'Ocurrió un error al consultar el menú: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Consultar Menú – NutriEscolar</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="Estilos/shared.css">
<style>
.hero{
  background-image:
    linear-gradient(rgba(0,0,0,0.52),rgba(0,0,0,0.52)),
    url('Imagenes/Alimentacion_escolar-Foto_1.jpg');
}
.card{max-width:520px;}
.alerta{border-radius:8px;padding:12px 16px;font-size:13px;margin-bottom:18px;}
.alerta-error{background:#fff3f3;border-left:4px solid #e53935;color:#b71c1c;}
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
    <a href="consultar-consumo.php">Mi consumo</a>
    <a href="informacion-nutricional.php">Nutrición</a>
    <a href="cerrar-sesion.php" class="btn-salir">Cerrar sesión</a>
  </div>
</nav>

<section class="hero">
  <div class="card card-media">
    <h1>Consultar Menú</h1>

    <?php if ($error): ?>
      <div class="alerta alerta-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="get" action="consultar-menu.php" class="filtros form-grid cols-3">
      <select id="dia" name="dia" required>
        <option value="">Seleccione un día</option>
        <option value="lunes" <?= $dia === 'lunes' ? 'selected' : '' ?>>Lunes</option>
        <option value="martes" <?= $dia === 'martes' ? 'selected' : '' ?>>Martes</option>
        <option value="miercoles" <?= $dia === 'miercoles' ? 'selected' : '' ?>>Miércoles</option>
        <option value="jueves" <?= $dia === 'jueves' ? 'selected' : '' ?>>Jueves</option>
        <option value="viernes" <?= $dia === 'viernes' ? 'selected' : '' ?>>Viernes</option>
      </select>
      <input type="date" id="fecha" name="fecha" value="<?= htmlspecialchars($fecha) ?>">
      <button type="submit" name="consultar" value="1" class="btn">Consultar</button>
    </form>

    <?php if ($resultado): ?>
      <div class="resultado" style="display:flex;">
        <div class="menu-dia">
          <h2><?= htmlspecialchars(strtoupper($resultado['dia'])) ?></h2>
          <div class="fecha">Fecha: <?= htmlspecialchars($resultado['fecha']) ?></div>
          <ul>
            <?php if ($resultado['alimentos']): ?>
              <?php foreach ($resultado['alimentos'] as $nombreAlimento): ?>
                <li><?= htmlspecialchars($nombreAlimento) ?></li>
              <?php endforeach; ?>
            <?php else: ?>
              <li>Este menú aún no tiene alimentos asignados.</li>
            <?php endif; ?>
          </ul>
        </div>
      </div>
    <?php endif; ?>
  </div>
</section>

<script>
const ham=document.getElementById('hamburger'),navMenu=document.getElementById('navMenu');
if(ham){ham.addEventListener('click',()=>navMenu.classList.toggle('abierto'));document.addEventListener('click',e=>{if(!ham.contains(e.target)&&!navMenu.contains(e.target))navMenu.classList.remove('abierto');});}
</script>
</body>
</html>
