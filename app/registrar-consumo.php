<?php
require_once __DIR__ . '/includes/conexion.php';
require_once __DIR__ . '/includes/sesion.php';
requerirLogin();

$usuarioId = (int) $_SESSION['usuario_id'];
$errores   = [];
$exito     = false;

$fecha = trim($_GET['fecha'] ?? $_POST['fecha'] ?? date('Y-m-d'));
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
    $fecha = date('Y-m-d');
}

// ---- Guardar selección ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fecha           = trim($_POST['fecha'] ?? '');
    $alimentosMarcados = array_map('intval', $_POST['alimentos'] ?? []);

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
        $errores[] = 'Selecciona una fecha válida.';
    } else {
        try {
            $pdo->beginTransaction();

            // Se reemplaza por completo el registro de ese día (permite corregir lo marcado)
            $pdo->prepare('DELETE FROM ConsumoAlimento WHERE UsuarioId = :uid AND Fecha = :fecha')
                ->execute([':uid' => $usuarioId, ':fecha' => $fecha]);

            if ($alimentosMarcados) {
                $insertar = $pdo->prepare(
                    'INSERT INTO ConsumoAlimento (UsuarioId, AlimentoId, Fecha) VALUES (:uid, :aid, :fecha)'
                );
                foreach ($alimentosMarcados as $alimentoId) {
                    if ($alimentoId > 0) {
                        $insertar->execute([':uid' => $usuarioId, ':aid' => $alimentoId, ':fecha' => $fecha]);
                    }
                }
            }

            $pdo->commit();
            $exito = true;
        } catch (PDOException $e) {
            $pdo->rollBack();
            $errores[] = 'No se pudo guardar tu consumo: ' . $e->getMessage();
        }
    }
}

// ---- Menú del día seleccionado ----
$menu = null;
$alimentosDelMenu = [];
try {
    $consultaMenu = $pdo->prepare('SELECT MenuId, Descripcion FROM Menu WHERE Fecha = :fecha LIMIT 1');
    $consultaMenu->execute([':fecha' => $fecha]);
    $menu = $consultaMenu->fetch();

    if ($menu) {
        $consultaAlimentos = $pdo->prepare(
            'SELECT a.AlimentoId, a.Nombre, i.Calorias, i.Proteinas, i.Carbohidratos, i.Grasas
             FROM MenuAlimento ma
             INNER JOIN Alimento a ON a.AlimentoId = ma.AlimentoId
             LEFT JOIN InformacionNutricional i ON i.AlimentoId = a.AlimentoId
             WHERE ma.MenuId = :menuId
             ORDER BY a.Nombre'
        );
        $consultaAlimentos->execute([':menuId' => $menu['MenuId']]);
        $alimentosDelMenu = $consultaAlimentos->fetchAll();
    }

    // Alimentos que el usuario ya había marcado ese día (para pre-marcar los checkboxes)
    $consultaMarcados = $pdo->prepare('SELECT AlimentoId FROM ConsumoAlimento WHERE UsuarioId = :uid AND Fecha = :fecha');
    $consultaMarcados->execute([':uid' => $usuarioId, ':fecha' => $fecha]);
    $marcadosPrevios = $consultaMarcados->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $errores[] = 'Ocurrió un error al cargar el menú: ' . $e->getMessage();
    $marcadosPrevios = [];
}

// Si se acaba de guardar, los recién marcados son los que el usuario envió
$marcadosActuales = $exito ? ($alimentosMarcados ?? []) : $marcadosPrevios;
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Registrar mi Consumo – NutriEscolar</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="Estilos/shared.css">
<style>
.hero{background-image:linear-gradient(rgba(0,0,0,0.52),rgba(0,0,0,0.52)),url('Imagenes/Alimentacion_escolar-Foto_1.jpg');padding:100px 16px 40px;}
.card{max-width:520px;}
.alerta{border-radius:8px;padding:12px 16px;font-size:13px;margin-bottom:18px;}
.alerta-error{background:#fff3f3;border-left:4px solid #e53935;color:#b71c1c;}
.alerta-exito{background:#f0fff4;border-left:4px solid #2e7d32;color:#1b5e20;}
.selector-fecha{display:flex;gap:10px;margin-bottom:18px;}
.selector-fecha input{flex:1;padding:10px 12px;border:1px solid #ddd;border-radius:8px;font-family:inherit;}
.selector-fecha button{padding:10px 16px;border:none;border-radius:8px;background:#eee;font-weight:600;cursor:pointer;}
.lista-alimentos{display:flex;flex-direction:column;gap:10px;margin-bottom:20px;}
.item-alimento{
  display:flex;align-items:center;gap:12px;padding:12px 14px;
  border:1px solid #eee;border-radius:10px;
}
.item-alimento input{width:18px;height:18px;}
.item-alimento .nombre{font-weight:600;flex:1;}
.item-alimento .macros{font-size:11px;color:#888;}
</style>
</head>
<body>
<nav class="navbar">
  <a href="index.php" class="nav-logo"><div class="nav-logo-icono">NE</div><span class="nav-logo-nombre">NutriEscolar</span></a>
  <button class="hamburger" id="hamburger" aria-label="Menú"><span></span><span></span><span></span></button>
  <div class="nav-menu" id="navMenu">
    <a href="ver-menu.php">Ver menú</a>
    <a href="consultar-consumo.php">Mi consumo</a>
    <a href="informacion-nutricional.php">Nutrición</a>
    <a href="cerrar-sesion.php" class="btn-salir">Cerrar sesión</a>
  </div>
</nav>
<section class="hero">
  <div class="card">
    <h1>Registrar mi Consumo</h1>

    <?php if ($exito): ?>
      <div class="alerta alerta-exito">
        ¡Listo! Guardamos lo que marcaste para el <?= htmlspecialchars($fecha) ?>.
        Puedes verlo o pedir tu reporte nutricional en <a href="consultar-consumo.php">Mi consumo</a>.
      </div>
    <?php endif; ?>
    <?php if ($errores): ?>
      <div class="alerta alerta-error"><?php foreach ($errores as $error): ?><div>• <?= htmlspecialchars($error) ?></div><?php endforeach; ?></div>
    <?php endif; ?>

    <form method="get" action="registrar-consumo.php" class="selector-fecha">
      <input type="date" name="fecha" value="<?= htmlspecialchars($fecha) ?>">
      <button type="submit">Ver ese día</button>
    </form>

    <?php if (!$menu): ?>
      <p style="text-align:center;font-size:14px;color:#888;">No hay ningún menú creado para esta fecha todavía.</p>
    <?php else: ?>
      <form method="post" action="registrar-consumo.php">
        <input type="hidden" name="fecha" value="<?= htmlspecialchars($fecha) ?>">
        <p style="font-size:13px;color:#666;margin-bottom:12px;">
          Marca los alimentos de <strong><?= htmlspecialchars($menu['Descripcion']) ?></strong> que sí comiste:
        </p>
        <div class="lista-alimentos">
          <?php foreach ($alimentosDelMenu as $a): ?>
            <label class="item-alimento">
              <input type="checkbox" name="alimentos[]" value="<?= (int) $a['AlimentoId'] ?>"
                     <?= in_array((int) $a['AlimentoId'], $marcadosActuales, true) ? 'checked' : '' ?>>
              <span class="nombre"><?= htmlspecialchars($a['Nombre']) ?></span>
              <span class="macros"><?= (int) $a['Calorias'] ?> kcal</span>
            </label>
          <?php endforeach; ?>
        </div>
        <button type="submit" class="btn">Guardar mi consumo</button>
      </form>
    <?php endif; ?>
    <a href="consultar-consumo.php" class="btn btn-volver" style="margin-top:10px;">Ver mi consumo registrado</a>
  </div>
</section>
<script>
const ham=document.getElementById('hamburger'),navMenu=document.getElementById('navMenu');
if(ham){ham.addEventListener('click',()=>navMenu.classList.toggle('abierto'));document.addEventListener('click',e=>{if(!ham.contains(e.target)&&!navMenu.contains(e.target))navMenu.classList.remove('abierto');});}
</script>
</body>
</html>
