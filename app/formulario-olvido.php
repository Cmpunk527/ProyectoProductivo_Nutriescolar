<?php
require_once __DIR__ . '/includes/conexion.php';
require_once __DIR__ . '/includes/sesion.php';
require_once __DIR__ . '/includes/correo.php';

$errores = [];
$exito   = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores[] = 'Ingresa un correo electrónico válido.';
    }

    if (!$errores) {
        try {
            $consulta = $pdo->prepare('SELECT UsuarioId, Nombre FROM Usuario WHERE Email = :email');
            $consulta->execute([':email' => $email]);
            $usuario = $consulta->fetch();

            if (!$usuario) {
                $errores[] = 'No existe ninguna cuenta registrada con ese correo electrónico.';
            } else {
                $token  = generarTokenRecuperacion();
                $expira = (new DateTime('+30 minutes'))->format('Y-m-d H:i:s');

                $insertar = $pdo->prepare(
                    'INSERT INTO RecuperacionContrasena (UsuarioId, Token, Expira, Usado)
                     VALUES (:usuarioId, :token, :expira, 0)'
                );
                $insertar->execute([
                    ':usuarioId' => $usuario['UsuarioId'],
                    ':token'     => $token,
                    ':expira'    => $expira,
                ]);

                $enlace  = construirEnlaceRecuperacion($token);
                $enviado = enviarCorreoRecuperacion($email, $usuario['Nombre'], $enlace);

                if (!$enviado) {
                    $errores[] = 'No se pudo enviar el correo. Revisa la configuración SMTP en '
                        . 'includes/correo.php (usuario, contraseña de aplicación, host y puerto).';
                } else {
                    $exito = true;
                }
            }
        } catch (PDOException $e) {
            $errores[] = 'Ocurrió un error al procesar la solicitud: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Recuperar Contraseña – NutriEscolar</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="Estilos/shared.css">
<style>
.hero{background-image:linear-gradient(rgba(0,0,0,0.52),rgba(0,0,0,0.52)),url('Imagenes/imagen 2.png');}.card{max-width:440px;}
.descripcion-form{font-size:14px;color:#666;text-align:center;margin-bottom:18px;line-height:1.6;}
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
    <h1>Recuperar Contraseña</h1>
    <p class="descripcion-form">Ingresa tu correo registrado. Te enviaremos un enlace para crear una nueva contraseña.</p>

    <?php if ($exito): ?>
      <div class="alerta alerta-exito">
        Te enviamos un correo con el enlace para restablecer tu contraseña. Revisa tu bandeja de entrada (y la carpeta de spam).
      </div>
    <?php endif; ?>
    <?php if ($errores): ?>
      <div class="alerta alerta-error"><?php foreach ($errores as $error): ?><div>• <?= htmlspecialchars($error) ?></div><?php endforeach; ?></div>
    <?php endif; ?>

    <?php if (!$exito): ?>
    <form method="post" action="formulario-olvido.php" id="formularioOlvido">
      <div class="formulario">
        <input type="email" name="email" placeholder="Correo electrónico" required
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
      </div>
      <button type="submit" class="btn">Enviar enlace de recuperación</button>
    </form>
    <?php endif; ?>
    <a href="inicio-sesion.php" class="btn btn-volver" style="margin-top:10px;">Volver al inicio de sesión</a>
  </div>
</section>
<script>
const ham=document.getElementById('hamburger'),navMenu=document.getElementById('navMenu');
if(ham){ham.addEventListener('click',()=>navMenu.classList.toggle('abierto'));document.addEventListener('click',e=>{if(!ham.contains(e.target)&&!navMenu.contains(e.target))navMenu.classList.remove('abierto');});}
</script>
</body>
</html>
