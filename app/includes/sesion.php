<?php
/**
 * includes/sesion.php
 * -----------------------------------------------------------------------
 * Manejo de sesión y control de acceso (usuarios registrados / admin).
 * Incluir SIEMPRE antes de imprimir cualquier HTML:
 *
 *     require_once __DIR__ . '/includes/sesion.php';
 * -----------------------------------------------------------------------
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/** ¿Hay una sesión iniciada? */
function estaLogueado(): bool
{
    return isset($_SESSION['usuario_id']);
}

/** ¿El usuario logueado tiene rol administrador? */
function esAdmin(): bool
{
    return estaLogueado() && ($_SESSION['usuario_rol'] ?? '') === 'admin';
}

/** Nombre del usuario logueado (o cadena vacía) */
function nombreUsuarioActual(): string
{
    return $_SESSION['usuario_nombre'] ?? '';
}

/**
 * Obliga a que exista una sesión activa (cualquier rol).
 * Se usa en las páginas de "solo lectura" que deben verse únicamente
 * por usuarios registrados y autenticados (requisito del proyecto).
 */
function requerirLogin(string $redireccion = 'inicio-sesion.php'): void
{
    if (!estaLogueado()) {
        header('Location: ' . $redireccion . '?motivo=login');
        exit;
    }
}

/**
 * Obliga a que la sesión activa sea de un administrador.
 * Se usa en todas las pantallas del panel administrativo.
 */
function requerirAdmin(string $redireccion = 'inicio-sesion.php'): void
{
    requerirLogin($redireccion);
    if (!esAdmin()) {
        // Usuario con sesión pero sin rol admin: se le devuelve a su pantalla principal
        header('Location: ver-menu.php');
        exit;
    }
}

/** Cierra la sesión actual por completo */
function cerrarSesionActual(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $parametros = session_get_cookie_params();
        setcookie('PHPSESSID', '', time() - 42000, $parametros['path'], $parametros['domain'], $parametros['secure'], $parametros['httponly']);
    }
    session_destroy();
}
