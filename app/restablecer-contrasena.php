<?php
require_once __DIR__ . '/includes/conexion.php';
require_once __DIR__ . '/includes/sesion.php';

$token   = trim($_GET['token'] ?? $_POST['token'] ?? '');
$errores = [];
$exito   = false;
$tokenValido = false;

if ($token === '') {
    $errores[] = 'Enlace de recuperación inválido o incompleto.';
} else {
    try {
        $consulta = $pdo->prepare(
            'SELECT r.RecuperacionId, r.UsuarioId, r.Expira, r.Usado, u.Nombre
             FROM RecuperacionContrasena r
             INNER JOIN Usuario u ON u.UsuarioId = r.UsuarioId
             WHERE r.Token = :token
             LIMIT 1'
        );
        $consulta->execute([':token' => $token]);
        $fila = $consulta->fetch();

        if (!$fila) {
            $errores[] = 'Este enlace de recuperación no existe o ya fue utilizado.';
        } elseif ((int) $fila['Usado'] === 1) {
            $errores[] = 'Este enlace ya fue utilizado. Solicita uno nuevo si necesitas cambiar tu contraseña otra vez.';
        } elseif (strtotime($fila['Expira']) < time()) {
            $errores[] = 'Este enlace ha expirado. Solicita uno nuevo desde "¿Olvidó su contraseña?".';
        } else {
            $tokenValido = true;
        }
    } catch (PDOException $e) {
        $errores[] = 'Ocurrió un error al validar el enlace: ' . $e->getMessage();
    }
}

if ($tokenValido && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $nueva     = (string) ($_POST['nueva'] ?? '');
    $confirmar = (string) ($_POST['confirmar'] ?? '');

    if (strlen($nueva) < 6) {
        $errores[] = 'La nueva contraseña debe tener al menos 6 caracteres.';
    }
    if ($nueva !== $confirmar) {
        $errores[] = 'Las contraseñas no coinciden.';
    }

    if (!$errores) {
        try {
            $pdo->beginTransaction();

            $hash = password_hash($nueva, PASSWORD_DEFAULT);
            $pdo->prepare('UPDATE Usuario SET `Contraseña` = :hash WHERE UsuarioId = :id')
                ->execute([':hash' => $hash, ':id' => $fila['UsuarioId']]);

            $pdo->prepare('UPDATE RecuperacionContrasena SET Usado = 1 WHERE RecuperacionId = :id')
                ->execute([':id' => $fila['RecuperacionId']]);

            $pdo->commit();
            $exito = true;
            $tokenValido = false; // ya no debe reutilizarse el formulario
        } catch (PDOException $e) {
            $pdo->rollBack();
            $errores[] = 'No se pudo actualizar la contraseña: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Restablecer Contraseña – NutriEscolar</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="Estilos/shared.css">
<style>
.hero{background-image:linear-gradient(rgba(0,0,0,0.52),rgba(0,0,0,0.52)),url('Imagenes/imagen 2.png');}.card{max-width:440px;}
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
    <a href="inicio-sesion.php">Iniciar sesión</a>
    <a href="registro.php">Registrarse</a>
  </div>
</nav>
<section class="hero">
  <div class="card">
    <h1>Restablecer Contraseña</h1>

    <?php if ($exito): ?>
      <div class="alerta alerta-exito">Tu contraseña fue actualizada correctamente. Ya puedes <a href="inicio-sesion.php">iniciar sesión</a>.</div>
    <?php endif; ?>
    <?php if ($errores): ?>
      <div class="alerta alerta-error"><?php foreach ($errores as $error): ?><div>• <?= htmlspecialchars($error) ?></div><?php endforeach; ?></div>
    <?php endif; ?>

    <?php if ($tokenValido): ?>
    <form data-validar-form novalidate method="post" action="restablecer-contrasena.php">
      <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
      <div class="campo">
        <input type="password" name="nueva" required data-pass="nueva" autocomplete="new-password">
        <label>Nueva contraseña</label>
      </div>
      <div class="campo">
        <input type="password" name="confirmar" required data-pass="confirmar" autocomplete="new-password">
        <label>Confirmar contraseña</label>
      </div>
      <button type="submit" class="btn">Guardar nueva contraseña</button>
    </form>
    <?php elseif (!$exito): ?>
      <a href="formulario-olvido.php" class="btn" style="display:block;text-align:center;">Solicitar un nuevo enlace</a>
    <?php endif; ?>
    <a href="inicio-sesion.php" class="btn btn-volver" style="margin-top:10px;">Volver al inicio de sesión</a>
  </div>
</section>
<script src="js/validaciones.js"></script>
<script>
const ham=document.getElementById('hamburger'),navMenu=document.getElementById('navMenu');
if(ham){ham.addEventListener('click',()=>navMenu.classList.toggle('abierto'));document.addEventListener('click',e=>{if(!ham.contains(e.target)&&!navMenu.contains(e.target))navMenu.classList.remove('abierto');});}
</script>
</body>
</html>
