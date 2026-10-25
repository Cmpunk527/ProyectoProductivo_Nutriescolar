<?php
require_once __DIR__ . '/includes/conexion.php';
require_once __DIR__ . '/includes/sesion.php';
requerirLogin();

$busqueda  = trim($_GET['alimento'] ?? '');
$error     = '';
$alimento  = null;

if (isset($_GET['buscar'])) {
    if ($busqueda === '' || !preg_match('/^[A-Za-zÁÉÍÓÚáéíóúÑñÜü\s]+$/u', $busqueda)) {
        $error = 'Ingresa el nombre de un alimento (solo letras).';
    } else {
        try {
            $consulta = $pdo->prepare(
                'SELECT a.Nombre, a.Descripcion, i.Calorias, i.Proteinas, i.Carbohidratos, i.Grasas, i.Sodio
                 FROM Alimento a
                 INNER JOIN InformacionNutricional i ON i.AlimentoId = a.AlimentoId
                 WHERE a.Nombre LIKE :nombre
                 ORDER BY a.Nombre
                 LIMIT 1'
            );
            $consulta->execute([':nombre' => '%' . $busqueda . '%']);
            $alimento = $consulta->fetch();

            if (!$alimento) {
                $error = 'Alimento no encontrado. Verifica el nombre o pídele al administrador que lo registre.';
            }
        } catch (PDOException $e) {
            $error = 'Ocurrió un error al buscar el alimento: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Información Nutricional – NutriEscolar</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="Estilos/shared.css">
<style>
.hero{
  background-image:
    linear-gradient(rgba(0,0,0,0.52),rgba(0,0,0,0.52)),
    url('Imagenes/imagen 2.png');
}
.card{max-width:460px;}
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
    <a href="consultar-menu.php">Consultar menú</a>
    <a href="cerrar-sesion.php" class="btn-salir">Cerrar sesión</a>
  </div>
</nav>

<section class="hero">
  <div class="card card-media">
    <h1>Consulta Nutricional</h1>

    <?php if ($error): ?>
      <div class="alerta alerta-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="get" action="informacion-nutricional.php" class="search-box" id="formularioNutricion">
      <input type="text" id="alimentoInput" name="alimento" placeholder="Ingrese el alimento (ej: arroz)"
             data-validar="letras" value="<?= htmlspecialchars($busqueda) ?>">
      <button type="submit" name="buscar" value="1">Buscar</button>
    </form>

    <?php if ($alimento): ?>
      <div class="resultado" id="resultado" style="display:flex;">
        <div class="nombre-resultado"><?= htmlspecialchars(strtoupper($alimento['Nombre'])) ?></div>
        <div class="info-fila">
        <div class="info-box"><div class="etiqueta">Calorías</div><div class="valor"><?= htmlspecialchars(number_format((float)$alimento['Calorias'], 2)) ?> kcal</div></div>
        <div class="info-box"><div class="etiqueta">Proteínas</div><div class="valor"><?= htmlspecialchars(number_format((float)$alimento['Proteinas'], 2)) ?> g</div></div>
        <div class="info-box"><div class="etiqueta">Carbohidratos</div><div class="valor"><?= htmlspecialchars(number_format((float)$alimento['Carbohidratos'], 2)) ?> g</div></div>
        <div class="info-box"><div class="etiqueta">Grasas</div><div class="valor"><?= htmlspecialchars(number_format((float)$alimento['Grasas'], 2)) ?> g</div></div>
        <div class="info-box"><div class="etiqueta">Sodio</div><div class="valor"><?= htmlspecialchars(number_format((float)$alimento['Sodio'], 2)) ?> mg</div></div>
        </div>
      </div>
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
