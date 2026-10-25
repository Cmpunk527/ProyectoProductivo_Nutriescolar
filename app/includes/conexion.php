<?php
/**
 * includes/conexion.php
 * -----------------------------------------------------------------------
 * Conexión centralizada a la base de datos MySQL (PROYECTO) usando PDO.
 * Cualquier página que necesite hablar con la base de datos debe hacer:
 *
 *     require_once __DIR__ . '/includes/conexion.php';
 *
 * y luego usar la variable $pdo.
 *
 * IMPORTANTE: ajusta las constantes de abajo con los datos reales de tu
 * servidor MySQL (usuario, contraseña, host, puerto) antes de desplegar.
 * -----------------------------------------------------------------------
 */

// ---- Datos de conexión (ajustar según el entorno) ----
// Valores por defecto de XAMPP. Si tu MySQL usa otro usuario/clave, créalos en
// includes/config.local.php (archivo que NO se sube a GitHub); ver config.local.example.php
if (file_exists(__DIR__ . '/config.local.php')) {
    require_once __DIR__ . '/config.local.php';
}
defined('DB_HOST')    || define('DB_HOST', 'localhost');
defined('DB_NOMBRE')  || define('DB_NOMBRE', 'PROYECTO');   // Nombre creado por database/nutriescolar.sql
defined('DB_USUARIO') || define('DB_USUARIO', 'root');
defined('DB_PASSWORD')|| define('DB_PASSWORD', '');
defined('DB_PUERTO')  || define('DB_PUERTO', '3306');
defined('DB_CHARSET') || define('DB_CHARSET', 'utf8mb4');

$dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PUERTO . ';dbname=' . DB_NOMBRE . ';charset=' . DB_CHARSET;

$opciones = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, DB_USUARIO, DB_PASSWORD, $opciones);
} catch (PDOException $e) {
    // No exponemos detalles sensibles de la excepción al usuario final
    http_response_code(500);
    die('No se pudo conectar a la base de datos. Verifica que MySQL esté activo y que '
        . 'la base de datos "' . DB_NOMBRE . '" exista (importa database/nutriescolar.sql). '
        . 'Detalle técnico: ' . $e->getMessage());
}
