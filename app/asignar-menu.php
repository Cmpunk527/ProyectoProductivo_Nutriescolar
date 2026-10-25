<?php
require_once __DIR__ . '/includes/conexion.php';
require_once __DIR__ . '/includes/sesion.php';
requerirAdmin();

$errores   = [];
$exito     = false;
$agregados = 0;

$menus     = $pdo->query('SELECT MenuId, Fecha, Descripcion FROM Menu ORDER BY Fecha')->fetchAll();
$alimentos = $pdo->query('SELECT AlimentoId, Nombre FROM Alimento ORDER BY Nombre')->fetchAll();
$idsAlimentosValidos = array_map('intval', array_column($alimentos, 'AlimentoId'));
$idsMenusValidos     = array_map('intval', array_column($menus, 'MenuId'));

$menuSeleccionado = (int) ($_POST['menu_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Selección múltiple: llega un arreglo alimentos[] con todos los marcados
    $marcados = array_values(array_unique(array_map('intval', (array) ($_POST['alimentos'] ?? []))));
    $marcados = array_values(array_intersect($marcados, $idsAlimentosValidos));

    if (!in_array($menuSeleccionado, $idsMenusValidos, true)) {
        $errores[] = 'Selecciona un menú válido.';
    }
    if (!$marcados) {
        $errores[] = 'Marca al menos un alimento para asignar al menú.';
    }

    if (!$errores) {
        try {
            $pdo->beginTransaction();

            $consulta = $pdo->prepare('SELECT AlimentoId FROM MenuAlimento WHERE MenuId = :m');
            $consulta->execute([':m' => $menuSeleccionado]);
            $yaAsignados = array_map('intval', $consulta->fetchAll(PDO::FETCH_COLUMN));

            $insertar = $pdo->prepare('INSERT INTO MenuAlimento (MenuId, AlimentoId) VALUES (:m, :a)');
            foreach ($marcados as $alimentoId) {
                if (!in_array($alimentoId, $yaAsignados, true)) {
                    $insertar->execute([':m' => $menuSeleccionado, ':a' => $alimentoId]);
                    $agregados++;
                }
            }

            $pdo->commit();
            $exito = true;
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errores[] = 'No se pudieron asignar los alimentos: ' . $e->getMessage();
        }
    }
}

// Alimentos que ya tiene cada menú (para marcarlos al elegir el menú)
$asignadosPorMenu = [];
foreach ($pdo->query('SELECT MenuId, AlimentoId FROM MenuAlimento')->fetchAll() as $fila) {
    $asignadosPorMenu[(int) $fila['MenuId']][] = (int) $fila['AlimentoId'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Asignar Alimentos al Menú – NutriEscolar</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="Estilos/shared.css">
<style>.hero{background-image:linear-gradient(rgba(0,0,0,0.52),rgba(0,0,0,0.52)),url('Imagenes/Alimentacion_escolar_Bogota_1.jpg');}
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
  <div class="card card-ancha">
    <h1>Asignar Alimentos al Menú</h1>

    <?php if ($exito): ?>
      <div class="alerta alerta-exito">
        <?php if ($agregados > 0): ?>
          Se asignaron <strong><?= (int) $agregados ?></strong> alimento(s) al menú correctamente.
        <?php else: ?>
          Esos alimentos ya estaban asignados a este menú; no hubo cambios.
        <?php endif; ?>
      </div>
    <?php endif; ?>
    <?php if ($errores): ?>
      <div class="alerta alerta-error"><?php foreach ($errores as $error): ?><div>• <?= htmlspecialchars($error) ?></div><?php endforeach; ?></div>
    <?php endif; ?>

    <?php if (!$menus || !$alimentos): ?>
      <p style="text-align:center;font-size:14px;color:#888;">Necesitas al menos un menú y un alimento registrados para poder continuar.</p>
      <div class="acciones"><a href="admin-panel.php" class="btn btn-volver">Volver al panel</a></div>
    <?php else: ?>
    <form method="post" action="asignar-menu.php" data-seleccion-alimentos data-modo="agregar">
      <div class="form-grid cols-2">
        <div class="filtros">
          <select name="menu_id" required>
            <option value="" disabled <?= $menuSeleccionado ? '' : 'selected' ?>>Seleccionar menú</option>
            <?php foreach ($menus as $m): ?>
              <option value="<?= (int) $m['MenuId'] ?>" <?= $menuSeleccionado === (int) $m['MenuId'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($m['Descripcion'] . ' — ' . $m['Fecha']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="barra-checks" style="margin:0;">
          <input type="search" placeholder="Buscar alimento...">
          <button type="button" data-accion="todos">Marcar todos</button>
          <button type="button" data-accion="ninguno">Quitar marcas</button>
        </div>
      </div>

      <div class="barra-checks" style="margin-top:14px;">
        <span style="font-size:13px;color:#666;">Marca todos los alimentos que quieras cargar al menú:</span>
        <span class="contador-checks"></span>
      </div>

      <div class="lista-checks">
        <?php foreach ($alimentos as $a): ?>
          <label class="check-item" data-nombre="<?= htmlspecialchars(mb_strtolower($a['Nombre'])) ?>">
            <input type="checkbox" name="alimentos[]" value="<?= (int) $a['AlimentoId'] ?>">
            <span><?= htmlspecialchars($a['Nombre']) ?></span>
            <small hidden>ya asignado</small>
          </label>
        <?php endforeach; ?>
      </div>

      <div class="acciones">
        <button type="submit" class="btn">Asignar alimentos seleccionados</button>
        <a href="admin-panel.php" class="btn btn-volver">Volver al panel</a>
      </div>
    </form>
    <?php endif; ?>
  </div>
</section>
<script>window.MENU_ASIGNADOS = <?= json_encode($asignadosPorMenu, JSON_UNESCAPED_UNICODE) ?>;</script>
<script src="js/seleccion-alimentos.js"></script>
<script>
const ham=document.getElementById('hamburger'),navMenu=document.getElementById('navMenu');
if(ham){ham.addEventListener('click',()=>navMenu.classList.toggle('abierto'));document.addEventListener('click',e=>{if(!ham.contains(e.target)&&!navMenu.contains(e.target))navMenu.classList.remove('abierto');});}
</script>
</body>
</html>
