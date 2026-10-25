<?php
require_once __DIR__ . '/includes/conexion.php';
require_once __DIR__ . '/includes/sesion.php';

if (estaLogueado()) {
    header('Location: ' . (esAdmin() ? 'admin-panel.php' : 'ver-menu.php'));
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuarioIngresado = trim($_POST['usuario'] ?? '');
    $password          = (string) ($_POST['password'] ?? '');
    $rolSeleccionado   = trim($_POST['rol'] ?? '');

    if ($usuarioIngresado === '' || $password === '' || $rolSeleccionado === '') {
        $error = 'Completa todos los campos para continuar.';
    } else {
        try {
            // Se busca por nombre de usuario o por correo electrónico
            $consulta = $pdo->prepare(
                'SELECT u.UsuarioId, u.Nombre, u.`Contraseña`, r.Nombre AS Rol
                 FROM Usuario u
                 INNER JOIN Rol r ON r.RolId = u.RolId
                 WHERE u.Nombre = :usuario1 OR u.Email = :usuario2
                 LIMIT 1'
            );
            $consulta->execute([
                ':usuario1' => $usuarioIngresado,
                ':usuario2' => $usuarioIngresado,
            ]);
            $fila = $consulta->fetch();

            if (!$fila || !password_verify($password, $fila['Contraseña'])) {
                $error = 'Usuario o contraseña incorrectos.';
            } elseif ($fila['Rol'] !== $rolSeleccionado) {
                $error = 'El rol seleccionado no corresponde a este usuario.';
            } else {
                // Credenciales válidas: se inicia sesión
                session_regenerate_id(true);
                $_SESSION['usuario_id']     = $fila['UsuarioId'];
                $_SESSION['usuario_nombre'] = $fila['Nombre'];
                $_SESSION['usuario_rol']    = $fila['Rol'];

                if ($fila['Rol'] === 'admin') {
                    header('Location: admin-panel.php');
                } else {
                    header('Location: ver-menu.php');
                }
                exit;
            }
        } catch (PDOException $e) {
            $error = 'Ocurrió un error al iniciar sesión: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Iniciar Sesión – NutriEscolar</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="Estilos/shared.css">
<style>
.hero{
  background-image:
    linear-gradient(rgba(0,0,0,0.52),rgba(0,0,0,0.52)),
    url('Imagenes/Alimentacion_escolar_Bogota_1.jpg');
}
.olvido{
  margin:-12px 0 16px 2px;
  font-size:13px;
}
.olvido a{color:var(--naranja-oscuro);text-decoration:none;font-weight:600;}
.olvido a:hover{text-decoration:underline;}
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
    <a href="consultar-menu.php">Consultar menú</a>
    <a href="informacion-nutricional.php">Nutrición</a>
    <a href="registro.php">Registrarse</a>
  </div>
</nav>

<section class="hero">
  <div class="card">
    <h1>Inicio de Sesión</h1>

    <?php if (isset($_GET['motivo'])): ?>
      <div class="alerta alerta-error">
        <?php if ($_GET['motivo'] === 'admin'): ?>
          Esa sección es exclusiva para administradores.
        <?php else: ?>
          Debes iniciar sesión para ver esta página.
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <?php if ($error): ?>
      <div class="alerta alerta-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form id="formLogin" data-validar-form novalidate method="post" action="inicio-sesion.php">
      <div class="campo">
        <input type="text" name="usuario" id="usuario" required data-validar="alfanumerico" autocomplete="username"
               value="<?= htmlspecialchars($_POST['usuario'] ?? '') ?>">
        <label>Nombre de usuario</label>
      </div>
      <div class="campo">
        <input type="password" name="password" id="password" required autocomplete="current-password">
        <label>Contraseña</label>
      </div>
      <div class="campo">
        <select name="rol" id="rol" required>
          <option value="" disabled <?= empty($_POST['rol']) ? 'selected' : '' ?>>Seleccione un rol</option>
          <option value="estudiante" <?= (($_POST['rol'] ?? '') === 'estudiante') ? 'selected' : '' ?>>Estudiante</option>
          <option value="profesor" <?= (($_POST['rol'] ?? '') === 'profesor') ? 'selected' : '' ?>>Profesor</option>
          <option value="admin" <?= (($_POST['rol'] ?? '') === 'admin') ? 'selected' : '' ?>>Administrador</option>
        </select>
      </div>
      <div class="olvido"><a href="olvide-contraseña.php">¿Olvidó su contraseña?</a></div>
      <button type="submit" class="btn">Iniciar sesión</button>
    </form>
    <div class="link-secundario">
      ¿No tienes una cuenta? <a href="registro.php">Regístrate aquí</a>
    </div>
  </div>
</section>

<script src="js/validaciones.js"></script>
<script>
const ham = document.getElementById('hamburger');
const navMenu = document.getElementById('navMenu');
if(ham){
  ham.addEventListener('click',()=>navMenu.classList.toggle('abierto'));
  document.addEventListener('click',e=>{
    if(!ham.contains(e.target)&&!navMenu.contains(e.target))navMenu.classList.remove('abierto');
  });
}
</script>
</body>
</html>
