<?php
require_once __DIR__ . '/includes/conexion.php';
require_once __DIR__ . '/includes/sesion.php';
requerirAdmin();   // Ahora SOLO el administrador registra qué se come y quiénes comen

$errores = [];
$exito   = false;
$mensajeExito = '';

$fecha = trim($_POST['fecha'] ?? $_GET['fecha'] ?? date('Y-m-d'));
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
    $fecha = date('Y-m-d');
}
$cursoId = (int) ($_POST['curso_id'] ?? $_GET['curso_id'] ?? 0);

$cursos           = [];
$curso            = null;
$menu             = null;
$alimentosDelMenu = [];
$estudiantes      = [];   // estudiantes del curso elegido
$alimentosMarcados   = [];
$estudiantesMarcados = [];
$envioConErrores  = false;

try {
    // ---- Cursos disponibles ----
    $cursos = $pdo->query(
        'SELECT c.CursoId, c.Nombre AS Curso, g.Nombre AS Grado
         FROM Curso c INNER JOIN Grado g ON g.GradoId = c.GradoId
         ORDER BY g.GradoId, c.Nombre'
    )->fetchAll();

    foreach ($cursos as $c) {
        if ((int) $c['CursoId'] === $cursoId) {
            $curso = $c;
        }
    }
    if (!$curso) {
        $cursoId = 0;
    }

    // ---- Menú del día seleccionado y sus alimentos ----
    $consultaMenu = $pdo->prepare('SELECT MenuId, Descripcion FROM Menu WHERE Fecha = :fecha LIMIT 1');
    $consultaMenu->execute([':fecha' => $fecha]);
    $menu = $consultaMenu->fetch();

    if ($menu) {
        $consultaAlimentos = $pdo->prepare(
            'SELECT a.AlimentoId, a.Nombre, i.Calorias
             FROM MenuAlimento ma
             INNER JOIN Alimento a ON a.AlimentoId = ma.AlimentoId
             LEFT JOIN InformacionNutricional i ON i.AlimentoId = a.AlimentoId
             WHERE ma.MenuId = :menuId
             ORDER BY a.Nombre'
        );
        $consultaAlimentos->execute([':menuId' => $menu['MenuId']]);
        $alimentosDelMenu = $consultaAlimentos->fetchAll();
    }

    // ---- Estudiantes del curso elegido ----
    if ($curso) {
        $consultaEst = $pdo->prepare(
            'SELECT EstudianteId, UsuarioId, Nombre, Apellido
             FROM Estudiante WHERE CursoId = :curso
             ORDER BY Apellido, Nombre'
        );
        $consultaEst->execute([':curso' => $cursoId]);
        $estudiantes = $consultaEst->fetchAll();
    }
} catch (PDOException $e) {
    $errores[] = 'Ocurrió un error al cargar los datos: ' . $e->getMessage();
}

// ---- Guardar: qué se comió (alimentos) y quiénes comieron (estudiantes) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$errores) {
    $alimentosMarcados   = array_values(array_unique(array_filter(array_map('intval', $_POST['alimentos'] ?? []))));
    $estudiantesMarcados = array_values(array_unique(array_filter(array_map('intval', $_POST['estudiantes'] ?? []))));

    $idsMenu       = array_map(fn($a) => (int) $a['AlimentoId'], $alimentosDelMenu);
    $usuarioPorEst = [];   // EstudianteId => UsuarioId
    foreach ($estudiantes as $e) {
        $usuarioPorEst[(int) $e['EstudianteId']] = (int) $e['UsuarioId'];
    }

    if (!$curso) {
        $errores[] = 'Selecciona el curso.';
    } elseif (!$menu) {
        $errores[] = 'No hay un menú creado para esa fecha.';
    } elseif (array_diff($alimentosMarcados, $idsMenu)) {
        $errores[] = 'Hay alimentos que no pertenecen al menú de esa fecha.';
    } elseif (array_diff($estudiantesMarcados, array_keys($usuarioPorEst))) {
        $errores[] = 'Hay estudiantes que no pertenecen al curso seleccionado.';
    } elseif ($estudiantesMarcados && !$alimentosMarcados) {
        $errores[] = 'Marca al menos un alimento de los que se sirvieron.';
    }

    if (!$errores) {
        try {
            $pdo->beginTransaction();

            // Se reemplaza por completo el registro de ese día para el curso
            // (permite corregir: quien se desmarca deja de tener consumo ese día)
            $idsUsuarios = array_values($usuarioPorEst);
            if ($idsUsuarios) {
                $marcas = implode(',', array_fill(0, count($idsUsuarios), '?'));
                $pdo->prepare("DELETE FROM ConsumoAlimento WHERE Fecha = ? AND UsuarioId IN ($marcas)")
                    ->execute(array_merge([$fecha], $idsUsuarios));
            }

            $insertar = $pdo->prepare(
                'INSERT INTO ConsumoAlimento (UsuarioId, AlimentoId, Fecha) VALUES (:uid, :aid, :fecha)'
            );
            foreach ($estudiantesMarcados as $estId) {
                foreach ($alimentosMarcados as $alimentoId) {
                    $insertar->execute([':uid' => $usuarioPorEst[$estId], ':aid' => $alimentoId, ':fecha' => $fecha]);
                }
            }

            $pdo->commit();
            $exito = true;
            $mensajeExito = 'Consumo guardado: ' . count($estudiantesMarcados) . ' estudiante(s) del curso '
                . $curso['Curso'] . ' comieron ' . count($alimentosMarcados) . ' alimento(s) el ' . $fecha . '.';
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errores[] = 'No se pudo guardar el consumo: ' . $e->getMessage();
        }
    }
    $envioConErrores = !empty($errores);
}

// ---- Selección previa de las casillas (lo ya guardado ese día para el curso) ----
if (!$envioConErrores && $curso && $estudiantes) {
    try {
        $idsUsuarios = array_map(fn($e) => (int) $e['UsuarioId'], $estudiantes);
        $marcas      = implode(',', array_fill(0, count($idsUsuarios), '?'));
        $consultaPrev = $pdo->prepare("SELECT UsuarioId, AlimentoId FROM ConsumoAlimento WHERE Fecha = ? AND UsuarioId IN ($marcas)");
        $consultaPrev->execute(array_merge([$fecha], $idsUsuarios));

        $usuariosConConsumo = [];
        $alimentosPrevios   = [];
        foreach ($consultaPrev->fetchAll() as $fila) {
            $usuariosConConsumo[(int) $fila['UsuarioId']] = true;
            $alimentosPrevios[(int) $fila['AlimentoId']]  = true;
        }
        foreach ($estudiantes as $e) {
            if (isset($usuariosConConsumo[(int) $e['UsuarioId']])) {
                $estudiantesMarcados[] = (int) $e['EstudianteId'];
            }
        }
        $alimentosMarcados = $alimentosPrevios
            ? array_keys($alimentosPrevios)
            : array_map(fn($a) => (int) $a['AlimentoId'], $alimentosDelMenu);   // por defecto: todo el menú
    } catch (PDOException $e) {
        $errores[] = 'Ocurrió un error al cargar el consumo previo: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Registrar Consumo – NutriEscolar</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="Estilos/shared.css">
<style>
.hero{background-image:linear-gradient(rgba(0,0,0,0.52),rgba(0,0,0,0.52)),url('Imagenes/Alimentacion_escolar-Foto_1.jpg');padding:100px 16px 40px;}
.card{max-width:620px;}
.alerta{border-radius:8px;padding:12px 16px;font-size:13px;margin-bottom:18px;}
.alerta-error{background:#fff3f3;border-left:4px solid #e53935;color:#b71c1c;}
.alerta-exito{background:#f0fff4;border-left:4px solid #2e7d32;color:#1b5e20;}
.selector-fecha{display:flex;gap:10px;margin-bottom:18px;flex-wrap:wrap;}
.selector-fecha input,.selector-fecha select{flex:1;min-width:140px;padding:10px 12px;border:1px solid #ddd;border-radius:8px;font-family:inherit;}
.selector-fecha button{padding:10px 16px;border:none;border-radius:8px;background:#eee;font-weight:600;cursor:pointer;}
.subtitulo-bloque{display:flex;align-items:center;gap:10px;margin:6px 0 10px;font-size:13px;color:#666;}
.subtitulo-bloque strong{color:var(--naranja-oscuro);text-transform:uppercase;letter-spacing:.5px;font-size:12px;}
.subtitulo-bloque button{margin-left:auto;padding:6px 12px;border:none;border-radius:8px;background:#f1f1f1;font-family:inherit;font-weight:700;font-size:12px;cursor:pointer;}
.subtitulo-bloque button:hover{background:#fdebd3;}
.lista-alimentos{display:flex;flex-direction:column;gap:10px;margin-bottom:20px;max-height:40vh;overflow:auto;padding:2px;}
.item-alimento{
  display:flex;align-items:center;gap:12px;padding:12px 14px;
  border:1px solid #eee;border-radius:10px;cursor:pointer;
}
.item-alimento:hover{background:#fff8ee;border-color:#f6c77a;}
.item-alimento input{width:18px;height:18px;accent-color:var(--naranja);}
.item-alimento .nombre{font-weight:600;flex:1;}
.item-alimento .macros{font-size:11px;color:#888;}
</style>
</head>
<body>
<nav class="navbar">
  <a href="index.php" class="nav-logo"><div class="nav-logo-icono">NE</div><span class="nav-logo-nombre">NutriEscolar</span></a>
  <button class="hamburger" id="hamburger" aria-label="Menú"><span></span><span></span><span></span></button>
  <div class="nav-menu" id="navMenu">
    <a href="admin-panel.php">Panel admin</a>
    <a href="consultar-consumo.php">Consultar consumo</a>
    <a href="cerrar-sesion.php" class="btn-salir">Cerrar sesión</a>
  </div>
</nav>
<section class="hero">
  <div class="card">
    <h1>Registrar Consumo</h1>

    <?php if ($exito): ?>
      <div class="alerta alerta-exito"><?= htmlspecialchars($mensajeExito) ?></div>
    <?php endif; ?>
    <?php if ($errores): ?>
      <div class="alerta alerta-error"><?php foreach ($errores as $error): ?><div>• <?= htmlspecialchars($error) ?></div><?php endforeach; ?></div>
    <?php endif; ?>

    <form method="get" action="registrar-consumo.php" class="selector-fecha">
      <input type="date" name="fecha" value="<?= htmlspecialchars($fecha) ?>">
      <select name="curso_id">
        <option value="" <?= $cursoId === 0 ? 'selected' : '' ?>>Seleccionar curso</option>
        <?php foreach ($cursos as $c): ?>
          <option value="<?= (int) $c['CursoId'] ?>" <?= (int) $c['CursoId'] === $cursoId ? 'selected' : '' ?>>
            <?= htmlspecialchars($c['Curso'] . ' — ' . $c['Grado']) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <button type="submit">Ver</button>
    </form>

    <?php if (!$menu): ?>
      <p style="text-align:center;font-size:14px;color:#888;">No hay ningún menú creado para esta fecha todavía.</p>
    <?php elseif (!$curso): ?>
      <p style="text-align:center;font-size:14px;color:#888;">Selecciona un curso para registrar quiénes comieron.</p>
    <?php elseif (!$estudiantes): ?>
      <p style="text-align:center;font-size:14px;color:#888;">El curso <?= htmlspecialchars($curso['Curso']) ?> todavía no tiene estudiantes registrados.</p>
    <?php else: ?>
      <form method="post" action="registrar-consumo.php">
        <input type="hidden" name="fecha" value="<?= htmlspecialchars($fecha) ?>">
        <input type="hidden" name="curso_id" value="<?= (int) $cursoId ?>">

        <div class="subtitulo-bloque">
          <strong>1. Qué se comió</strong>
          <span><?= htmlspecialchars($menu['Descripcion']) ?></span>
          <button type="button" data-alternar="alimentos[]">Marcar / quitar todos</button>
        </div>
        <div class="lista-alimentos">
          <?php foreach ($alimentosDelMenu as $a): ?>
            <label class="item-alimento">
              <input type="checkbox" name="alimentos[]" value="<?= (int) $a['AlimentoId'] ?>"
                     <?= in_array((int) $a['AlimentoId'], $alimentosMarcados, true) ? 'checked' : '' ?>>
              <span class="nombre"><?= htmlspecialchars($a['Nombre']) ?></span>
              <span class="macros"><?= (int) $a['Calorias'] ?> kcal</span>
            </label>
          <?php endforeach; ?>
        </div>

        <div class="subtitulo-bloque">
          <strong>2. Quiénes comieron</strong>
          <span>Curso <?= htmlspecialchars($curso['Curso']) ?></span>
          <button type="button" data-alternar="estudiantes[]">Marcar / quitar todos</button>
        </div>
        <div class="lista-alimentos">
          <?php foreach ($estudiantes as $e): ?>
            <label class="item-alimento">
              <input type="checkbox" name="estudiantes[]" value="<?= (int) $e['EstudianteId'] ?>"
                     <?= in_array((int) $e['EstudianteId'], $estudiantesMarcados, true) ? 'checked' : '' ?>>
              <span class="nombre"><?= htmlspecialchars($e['Apellido'] . ' ' . $e['Nombre']) ?></span>
            </label>
          <?php endforeach; ?>
        </div>

        <button type="submit" class="btn">Guardar consumo</button>
      </form>
    <?php endif; ?>
    <a href="consultar-consumo.php" class="btn btn-volver" style="margin-top:10px;">Consultar consumo registrado</a>
  </div>
</section>
<script>
const ham=document.getElementById('hamburger'),navMenu=document.getElementById('navMenu');
if(ham){ham.addEventListener('click',()=>navMenu.classList.toggle('abierto'));document.addEventListener('click',e=>{if(!ham.contains(e.target)&&!navMenu.contains(e.target))navMenu.classList.remove('abierto');});}

// "Marcar / quitar todos": si hay alguno sin marcar, marca todos; si no, los quita
document.querySelectorAll('[data-alternar]').forEach(function(btn){
  btn.addEventListener('click',function(){
    const cajas = document.querySelectorAll('input[name="' + btn.dataset.alternar + '"]');
    const marcarTodos = Array.from(cajas).some(function(c){ return !c.checked; });
    cajas.forEach(function(c){ c.checked = marcarTodos; });
  });
});
</script>
</body>
</html>
