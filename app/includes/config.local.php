<?php
/**
 * includes/config.local.example.php  —  PLANTILLA de configuración local
 * -----------------------------------------------------------------------
 * 1. Copia este archivo con el nombre:  config.local.php  (misma carpeta).
 * 2. Escribe tus datos reales en la copia.
 * 3. config.local.php está en .gitignore: NUNCA se sube a GitHub, así tus
 *    contraseñas quedan solo en tu computador.
 *
 * Todo es opcional: si no creas config.local.php el sitio usa los valores
 * por defecto de XAMPP para la base de datos, y el envío de correos
 * (recuperar contraseña y reporte nutricional) queda desactivado.
 * -----------------------------------------------------------------------
 */

// ---- Base de datos (solo si tu MySQL no usa root sin contraseña) ----
// define('DB_USUARIO', 'root');
// define('DB_PASSWORD', '');

// ---- Correo (SMTP) para recuperar contraseña y enviar reportes ----
// Con Gmail necesitas una "Contraseña de aplicación":
//   https://myaccount.google.com/apppasswords  (requiere verificación en dos pasos)
define('SMTP_USUARIO',  'carrilloleonjuandavid45@gmail.com');
define('SMTP_PASSWORD', 'vicd aiwt wkxr fpoh');
// define('SMTP_HOST',      'smtp.gmail.com');
// define('SMTP_PUERTO',    587);
// define('SMTP_SEGURIDAD', 'tls');
