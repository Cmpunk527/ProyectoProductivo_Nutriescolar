<?php
require_once __DIR__ . '/includes/conexion.php';
require_once __DIR__ . '/includes/sesion.php';
requerirAdmin();

$errores = [];
$exito   = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $alimentoId    = (int) ($_POST['alimento_id'] ?? 0);
    $descripcion   = trim($_POST['descripcion'] ?? '');
    $calorias      = trim($_POST['calorias'] ?? '');
    $proteinas     = trim($_POST['proteinas'] ?? '');
    $carbohidratos = trim($_POST['carbohidratos'] ?? '');
    $grasas        = trim($_POST['grasas'] ?? '');
    $sodio         = trim($_POST['sodio'] ?? '');

    $patronNumero = '/^[0-9]+(\.[0-9]+)?$/';
    if ($alimentoId <= 0) {
        $errores[] = 'Selecciona un alimento válido.';
    }
    foreach (['calorías' => $calorias, 'proteínas' => $proteinas, 'carbohidratos' => $carbohidratos,
              'grasas' => $grasas, 'sodio' => $sodio] as $etiqueta => $valor) {
        if ($valor === '' || !preg_match($patronNumero, $valor)) {
            $errores[] = "El campo {$etiqueta} solo puede contener números.";
        }
    }

    if (!$errores) {
        try {
            // La descripción vive en Alimento y los valores nutricionales en
            // InformacionNutricional (1:1): se actualizan ambas en una transacción.
            $pdo->beginTransaction();

            $pdo->prepare('UPDATE Alimento SET Descripcion = :descripcion WHERE AlimentoId = :id')
                ->execute([
                    ':descripcion' => $descripcion !== '' ? $descripcion : null,
                    ':id'          => $alimentoId,
                ]);

            // Si el alimento aún no tenía ficha nutricional se crea; si ya la tenía, se actualiza.
            $pdo->prepare(
                'INSERT INTO InformacionNutricional (AlimentoId, Calorias, Proteinas, Grasas, Carbohidratos, Sodio)
                 VALUES (:id, :calorias, :proteinas, :grasas, :carbohidratos, :sodio)
                 ON DUPLICATE KEY UPDATE
                    Calorias = VALUES(Calorias), Proteinas = VALUES(Proteinas), Grasas = VALUES(Grasas),
                    Carbohidratos = VALUES(Carbohidratos), Sodio = VALUES(Sodio)'
            )->execute([
                ':id'            => $alimentoId,
                ':calorias'      => $calorias,
                ':proteinas'     => $proteinas,
                ':grasas'        => $grasas,
                ':carbohidratos' => $carbohidratos,
                ':sodio'         => $sodio,
            ]);

            $pdo->commit();
            $exito = true;
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errores[] = 'No se pudo guardar la información nutricional: ' . $e->getMessage();
        }
    }
}

// Alimentos disponibles para el desplegable
$alimentos = $pdo->query(
    'SELECT a.AlimentoId, a.Nombre, a.Descripcion, i.Calorias, i.Proteinas, i.Carbohidratos, i.Grasas, i.Sodio
     FROM Alimento a
     LEFT JOIN InformacionNutricional i ON i.AlimentoId = a.AlimentoId
     ORDER BY a.Nombre'
)->fetchAll();
$alimentoSeleccionado = (int) ($_POST['alimento_id'] ?? 0);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Editar Nutrición – NutriEscolar</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="Estilos/shared.css">
<style>
.hero{background-image:linear-gradient(rgba(0,0,0,0.52),rgba(0,0,0,0.52)),url('Imagenes/imagen 2.png');}
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
    <h1>Editar Información Nutricional</h1>

    <?php if ($exito): ?>
      <div class="alerta alerta-exito">Información nutricional actualizada correctamente.</div>
    <?php endif; ?>
    <?php if ($errores): ?>
      <div class="alerta alerta-error"><?php foreach ($errores as $error): ?><div>• <?= htmlspecialchars($error) ?></div><?php endforeach; ?></div>
    <?php endif; ?>

    <?php if (!$alimentos): ?>
      <p style="text-align:center;font-size:14px;color:#888;">Aún no hay alimentos registrados. Ve a "Registrar alimentos" primero.</p>
      <div class="acciones"><a href="admin-panel.php" class="btn btn-volver">Volver al panel</a></div>
    <?php else: ?>
    <form method="post" action="editar-nutricion.php" id="formularioNutricion" data-validar-form novalidate>
      <div class="formulario form-grid cols-5">
        <select name="alimento_id" class="span-2" id="alimentoSelect" required onchange="cargarDatosAlimento()">
          <option value="" disabled <?= $alimentoSeleccionado ? '' : 'selected' ?>>Seleccionar alimento</option>
          <?php foreach ($alimentos as $a): ?>
            <option value="<?= (int) $a['AlimentoId'] ?>" <?= $alimentoSeleccionado === (int) $a['AlimentoId'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($a['Nombre']) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <textarea name="descripcion" id="campoDescripcion" class="span-3" placeholder="Descripción (opcional)"><?= htmlspecialchars($_POST['descripcion'] ?? '') ?></textarea>
        <input type="text" name="calorias" id="campoCalorias" inputmode="decimal" placeholder="Calorías (kcal)" required data-validar="numeros"
               value="<?= htmlspecialchars($_POST['calorias'] ?? '') ?>">
        <input type="text" name="proteinas" id="campoProteinas" inputmode="decimal" placeholder="Proteínas (g)" required data-validar="numeros"
               value="<?= htmlspecialchars($_POST['proteinas'] ?? '') ?>">
        <input type="text" name="carbohidratos" id="campoCarbohidratos" inputmode="decimal" placeholder="Carbohidratos (g)" required data-validar="numeros"
               value="<?= htmlspecialchars($_POST['carbohidratos'] ?? '') ?>">
        <input type="text" name="grasas" id="campoGrasas" inputmode="decimal" placeholder="Grasas (g)" required data-validar="numeros"
               value="<?= htmlspecialchars($_POST['grasas'] ?? '') ?>">
        <input type="text" name="sodio" id="campoSodio" inputmode="decimal" placeholder="Sodio (mg)" required data-validar="numeros"
               value="<?= htmlspecialchars($_POST['sodio'] ?? '') ?>">
      </div>
      <div class="acciones">
        <button type="submit" class="btn">Guardar cambios</button>
        <a href="admin-panel.php" class="btn btn-volver">Volver al panel</a>
      </div>
    </form>
    <?php endif; ?>
  </div>
</section>
<script src="js/validaciones.js"></script>
<script>
const ham=document.getElementById('hamburger'),navMenu=document.getElementById('navMenu');
if(ham){ham.addEventListener('click',()=>navMenu.classList.toggle('abierto'));document.addEventListener('click',e=>{if(!ham.contains(e.target)&&!navMenu.contains(e.target))navMenu.classList.remove('abierto');});}

// Autocompleta los campos con los valores actuales del alimento seleccionado
const datosAlimentos = <?= json_encode(array_column($alimentos, null, 'AlimentoId')) ?>;
function cargarDatosAlimento(){
  const id = document.getElementById('alimentoSelect').value;
  const a = datosAlimentos[id];
  if(!a) return;
  document.getElementById('campoDescripcion').value = a.Descripcion || '';
  document.getElementById('campoCalorias').value = a.Calorias ?? '';
  document.getElementById('campoProteinas').value = a.Proteinas ?? '';
  document.getElementById('campoCarbohidratos').value = a.Carbohidratos ?? '';
  document.getElementById('campoGrasas').value = a.Grasas ?? '';
  document.getElementById('campoSodio').value = a.Sodio ?? '';
}
</script>
</body>
</html>
