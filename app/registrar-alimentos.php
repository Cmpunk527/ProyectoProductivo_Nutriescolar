<?php
require_once __DIR__ . '/includes/conexion.php';
require_once __DIR__ . '/includes/sesion.php';
requerirAdmin();

$errores = [];
$exito   = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre        = trim($_POST['nombre'] ?? '');
    $descripcion   = trim($_POST['descripcion'] ?? '');
    $calorias      = trim($_POST['calorias'] ?? '');
    $proteinas     = trim($_POST['proteinas'] ?? '');
    $grasas        = trim($_POST['grasas'] ?? '');
    $carbohidratos = trim($_POST['carbohidratos'] ?? '');
    $sodio         = trim($_POST['sodio'] ?? '');

    $patronNumero = '/^[0-9]+(\.[0-9]+)?$/';

    if ($nombre === '' || !preg_match('/^[A-Za-zÁÉÍÓÚáéíóúÑñÜü\s]+$/u', $nombre)) {
        $errores[] = 'El nombre del alimento solo puede contener letras y espacios.';
    }
    foreach (['Calorías' => $calorias, 'Proteínas' => $proteinas, 'Grasas' => $grasas,
              'Carbohidratos' => $carbohidratos, 'Sodio' => $sodio] as $etiqueta => $valor) {
        if ($valor === '' || !preg_match($patronNumero, $valor)) {
            $errores[] = "El campo {$etiqueta} solo puede contener números.";
        }
    }

    if (!$errores) {
        try {
            $existe = $pdo->prepare('SELECT AlimentoId FROM Alimento WHERE Nombre = :nombre LIMIT 1');
            $existe->execute([':nombre' => $nombre]);

            if ($existe->fetch()) {
                $errores[] = 'Ya existe un alimento registrado con ese nombre.';
            } else {
                // El alimento y su información nutricional viven en dos tablas
                // relacionadas (Alimento 1:1 InformacionNutricional); se guardan
                // juntos en una transacción para que nunca quede uno sin el otro.
                $pdo->beginTransaction();

                $pdo->prepare('INSERT INTO Alimento (Nombre, Descripcion) VALUES (:nombre, :descripcion)')
                    ->execute([
                        ':nombre'      => $nombre,
                        ':descripcion' => $descripcion !== '' ? $descripcion : null,
                    ]);
                $alimentoId = (int) $pdo->lastInsertId();

                $pdo->prepare(
                    'INSERT INTO InformacionNutricional (AlimentoId, Calorias, Proteinas, Grasas, Carbohidratos, Sodio)
                     VALUES (:alimentoId, :calorias, :proteinas, :grasas, :carbohidratos, :sodio)'
                )->execute([
                    ':alimentoId'    => $alimentoId,
                    ':calorias'      => $calorias,
                    ':proteinas'     => $proteinas,
                    ':grasas'        => $grasas,
                    ':carbohidratos' => $carbohidratos,
                    ':sodio'         => $sodio,
                ]);

                $pdo->commit();
                $exito = true;
                $_POST = []; // limpia el formulario para registrar otro alimento
            }
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errores[] = 'No se pudo registrar el alimento: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Registrar Alimentos – NutriEscolar</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="Estilos/shared.css">
<style>
.hero{background-image:linear-gradient(rgba(0,0,0,0.52),rgba(0,0,0,0.52)),url('Imagenes/Alimentacion_escolar_Bogota_1.jpg');}
textarea{
  width:100%;padding:12px 14px;border:none;border-bottom:2px solid #ccc;outline:none;
  font-size:14px;font-family:inherit;background:none;resize:vertical;min-height:70px;
}
textarea:focus{border-bottom-color:var(--naranja);}
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
    <h1>Registrar Alimento</h1>

    <?php if ($exito): ?>
      <div class="alerta alerta-exito">Alimento registrado correctamente con toda su información nutricional.</div>
    <?php endif; ?>
    <?php if ($errores): ?>
      <div class="alerta alerta-error"><?php foreach ($errores as $error): ?><div>• <?= htmlspecialchars($error) ?></div><?php endforeach; ?></div>
    <?php endif; ?>

    <form method="post" action="registrar-alimentos.php" id="formularioAlimento" data-validar-form novalidate>
      <div class="formulario form-grid cols-5">
        <input type="text" name="nombre" class="span-2" placeholder="Nombre del alimento" required data-validar="letras"
               value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>">
        <textarea name="descripcion" class="span-3" placeholder="Descripción (opcional)"><?= htmlspecialchars($_POST['descripcion'] ?? '') ?></textarea>
        <input type="text" name="calorias" inputmode="decimal" placeholder="Calorías (kcal)" required data-validar="numeros"
               value="<?= htmlspecialchars($_POST['calorias'] ?? '') ?>">
        <input type="text" name="proteinas" inputmode="decimal" placeholder="Proteínas (g)" required data-validar="numeros"
               value="<?= htmlspecialchars($_POST['proteinas'] ?? '') ?>">
        <input type="text" name="grasas" inputmode="decimal" placeholder="Grasas (g)" required data-validar="numeros"
               value="<?= htmlspecialchars($_POST['grasas'] ?? '') ?>">
        <input type="text" name="carbohidratos" inputmode="decimal" placeholder="Carbohidratos (g)" required data-validar="numeros"
               value="<?= htmlspecialchars($_POST['carbohidratos'] ?? '') ?>">
        <input type="text" name="sodio" inputmode="decimal" placeholder="Sodio (mg)" required data-validar="numeros"
               value="<?= htmlspecialchars($_POST['sodio'] ?? '') ?>">
      </div>
      <div class="acciones">
        <button type="submit" class="btn">Registrar alimento</button>
        <a href="admin-panel.php" class="btn btn-volver">Volver al panel</a>
      </div>
    </form>
  </div>
</section>
<script src="js/validaciones.js"></script>
<script>
const ham=document.getElementById('hamburger'),navMenu=document.getElementById('navMenu');
if(ham){ham.addEventListener('click',()=>navMenu.classList.toggle('abierto'));document.addEventListener('click',e=>{if(!ham.contains(e.target)&&!navMenu.contains(e.target))navMenu.classList.remove('abierto');});}
</script>
</body>
</html>
