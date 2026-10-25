<?php
require_once __DIR__ . '/includes/conexion.php';
require_once __DIR__ . '/includes/sesion.php';
requerirAdmin();

$errores = [];
$exito   = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $alimentoId = (int) ($_POST['alimento_id'] ?? 0);

    if ($alimentoId <= 0) {
        $errores[] = 'Selecciona un alimento válido.';
    } else {
        try {
            $pdo->beginTransaction();
            // Se eliminan primero todo lo que depende del alimento (integridad referencial):
            // su uso en consumos y menús, y su ficha de InformacionNutricional.
            $pdo->prepare('DELETE FROM ConsumoAlimento WHERE AlimentoId = :id')->execute([':id' => $alimentoId]);
            $pdo->prepare('DELETE FROM MenuAlimento WHERE AlimentoId = :id')->execute([':id' => $alimentoId]);
            $pdo->prepare('DELETE FROM InformacionNutricional WHERE AlimentoId = :id')->execute([':id' => $alimentoId]);
            $borrar = $pdo->prepare('DELETE FROM Alimento WHERE AlimentoId = :id');
            $borrar->execute([':id' => $alimentoId]);

            if ($borrar->rowCount() === 0) {
                $pdo->rollBack();
                $errores[] = 'El alimento seleccionado ya no existe.';
            } else {
                $pdo->commit();
                $exito = true;
            }
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errores[] = 'No se pudo eliminar el alimento: ' . $e->getMessage();
        }
    }
}

$alimentos = $pdo->query('SELECT AlimentoId, Nombre FROM Alimento ORDER BY Nombre')->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Eliminar Alimentos – NutriEscolar</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="Estilos/shared.css">
<style>.hero{background-image:linear-gradient(rgba(0,0,0,0.52),rgba(0,0,0,0.52)),url('Imagenes/Alimentacion_escolar-Foto_1.jpg');}.card{max-width:440px;}
.advertencia{background:#fff3f3;border-left:4px solid #e53935;border-radius:8px;padding:12px 16px;font-size:13px;color:#b71c1c;margin-bottom:18px;}
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
  <div class="card">
    <h1>Eliminar Alimento</h1>
    <div class="advertencia">⚠ Esta acción es irreversible. Confirma antes de eliminar.</div>

    <?php if ($exito): ?>
      <div class="alerta alerta-exito">Alimento eliminado correctamente.</div>
    <?php endif; ?>
    <?php if ($errores): ?>
      <div class="alerta alerta-error"><?php foreach ($errores as $error): ?><div>• <?= htmlspecialchars($error) ?></div><?php endforeach; ?></div>
    <?php endif; ?>

    <?php if (!$alimentos): ?>
      <p style="text-align:center;font-size:14px;color:#888;">No hay alimentos registrados.</p>
    <?php else: ?>
    <form method="post" action="eliminar-alimentos.php"
          onsubmit="return confirm('¿Seguro que deseas eliminar este alimento? Se quitará también de los menús, de los consumos registrados y de su información nutricional. Esta acción no se puede deshacer.');">
      <div class="filtros">
        <select name="alimento_id" required>
          <option value="" disabled selected>Seleccionar alimento</option>
          <?php foreach ($alimentos as $a): ?>
            <option value="<?= (int) $a['AlimentoId'] ?>"><?= htmlspecialchars($a['Nombre']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <button type="submit" class="btn btn-peligro">Eliminar alimento</button>
    </form>
    <?php endif; ?>
    <a href="admin-panel.php" class="btn btn-volver" style="margin-top:10px;">Volver al panel</a>
  </div>
</section>
<script>
const ham=document.getElementById('hamburger'),navMenu=document.getElementById('navMenu');
if(ham){ham.addEventListener('click',()=>navMenu.classList.toggle('abierto'));document.addEventListener('click',e=>{if(!ham.contains(e.target)&&!navMenu.contains(e.target))navMenu.classList.remove('abierto');});}
</script>
</body>
</html>
