<?php
require_once __DIR__ . '/includes/conexion.php';
require_once __DIR__ . '/includes/sesion.php';
requerirAdmin();

$errores = [];
$exito   = false;
$dias    = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dia   = trim($_POST['dia'] ?? '');
    $fecha = trim($_POST['fecha'] ?? '');

    if (!in_array($dia, $dias, true)) {
        $errores[] = 'Selecciona un día válido.';
    }
    if ($fecha === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
        $errores[] = 'Selecciona una fecha válida.';
    }

    if (!$errores) {
        try {
            $consulta = $pdo->prepare('SELECT MenuId FROM Menu WHERE Fecha = :fecha');
            $consulta->execute([':fecha' => $fecha]);
            if ($consulta->fetch()) {
                $errores[] = 'Ya existe un menú creado para esa fecha.';
            } else {
                $insertar = $pdo->prepare('INSERT INTO Menu (Fecha, Descripcion) VALUES (:fecha, :descripcion)');
                $insertar->execute([':fecha' => $fecha, ':descripcion' => 'Menú ' . strtolower($dia)]);
                $exito = true;
            }
        } catch (PDOException $e) {
            $errores[] = 'No se pudo crear el menú: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Crear Menú – NutriEscolar</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="Estilos/shared.css">
<style>.hero{background-image:linear-gradient(rgba(0,0,0,0.52),rgba(0,0,0,0.52)),url('Imagenes/imagen 2.png');}.card{max-width:440px;}
.alerta{border-radius:8px;padding:12px 16px;font-size:13px;margin-bottom:18px;}
.alerta-error{background:#fff3f3;border-left:4px solid #e53935;color:#b71c1c;}
.alerta-exito{background:#f0fff4;border-left:4px solid #2e7d32;color:#1b5e20;}
</style>
</head>
<body>
<nav class="navbar">
  <a href="index.php" class="nav-logo"><div class="nav-logo-icono">NE</div><span class="nav-logo-nombre">NutriEscolar</span></a>
  <button class="hamburger" id="hamburger" aria-label="Menú"><span></span><span></span><span></span></button>
  <div class="nav-menu" id="navMenu">
    <a href="admin-panel.php">Panel admin</a>
    <a href="cerrar-sesion.php" class="btn-salir">Cerrar sesión</a>
  </div>
</nav>
<section class="hero">
  <div class="card card-media">
    <h1>Crear Menú Escolar</h1>

    <?php if ($exito): ?>
      <div class="alerta alerta-exito">Menú creado correctamente. Ahora puedes asignarle alimentos.</div>
    <?php endif; ?>
    <?php if ($errores): ?>
      <div class="alerta alerta-error"><?php foreach ($errores as $error): ?><div>• <?= htmlspecialchars($error) ?></div><?php endforeach; ?></div>
    <?php endif; ?>

    <form method="post" action="crear-menu.php">
      <div class="filtros form-grid cols-3">
        <select name="dia" required>
          <option value="" disabled selected>Seleccionar día</option>
          <?php foreach ($dias as $d): ?><option><?= $d ?></option><?php endforeach; ?>
        </select>
        <input type="date" name="fecha" required>
        <button type="submit" class="btn">Crear menú</button>
      </div>
    </form>
    <div class="acciones"><a href="admin-panel.php" class="btn btn-volver">Volver al panel</a></div>
  </div>
</section>
<script>
const ham=document.getElementById('hamburger'),navMenu=document.getElementById('navMenu');
if(ham){ham.addEventListener('click',()=>navMenu.classList.toggle('abierto'));document.addEventListener('click',e=>{if(!ham.contains(e.target)&&!navMenu.contains(e.target))navMenu.classList.remove('abierto');});}
</script>
</body>
</html>
