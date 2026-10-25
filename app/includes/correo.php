<?php
/**
 * includes/correo.php
 * -----------------------------------------------------------------------
 * Envío de correos para el flujo de "olvidé mi contraseña", usando
 * PHPMailer + SMTP (mucho más confiable que la función mail() nativa,
 * que casi nunca funciona en XAMPP/local sin un relay configurado).
 *
 * PHPMailer ya viene incluido en includes/PHPMailer/ (no requiere Composer).
 *
 * ============================================================
 *  CONFIGURACIÓN SMTP: crea includes/config.local.php (ver config.local.example.php)
 * ============================================================
 * Por defecto está configurado para Gmail. Para usar Gmail necesitas:
 *   1. Activar la verificación en dos pasos en tu cuenta de Google:
 *      https://myaccount.google.com/security
 *   2. Generar una "Contraseña de aplicación" (App Password) en:
 *      https://myaccount.google.com/apppasswords
 *      (Gmail YA NO permite usar tu contraseña normal de la cuenta para
 *      esto, así que la conexión SMTP fallará si usas tu contraseña real).
 *   3. Pega esa contraseña de aplicación (16 caracteres) en SMTP_PASSWORD
 *      dentro de includes/config.local.php (ese archivo NO se sube a GitHub).
 *
 * Si prefieres otro proveedor (Outlook, Zoho, SendGrid, tu propio
 * hosting, etc.) solo cambia estas constantes por las de tu proveedor.
 * -----------------------------------------------------------------------
 */

// Los datos reales (correo y contraseña de aplicación) van en config.local.php,
// archivo que .gitignore excluye para que nunca se publique en GitHub.
if (file_exists(__DIR__ . '/config.local.php')) {
    require_once __DIR__ . '/config.local.php';
}
defined('SMTP_HOST')        || define('SMTP_HOST', 'smtp.gmail.com');
defined('SMTP_PUERTO')      || define('SMTP_PUERTO', 587);          // 587 = TLS · 465 = SSL
defined('SMTP_SEGURIDAD')   || define('SMTP_SEGURIDAD', 'tls');     // 'tls' o 'ssl'
defined('SMTP_USUARIO')     || define('SMTP_USUARIO', 'tu_correo@gmail.com');
defined('SMTP_PASSWORD')    || define('SMTP_PASSWORD', '');
defined('SMTP_NOMBRE_FROM') || define('SMTP_NOMBRE_FROM', 'NutriEscolar');

require_once __DIR__ . '/PHPMailer/Exception.php';
require_once __DIR__ . '/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

/**
 * Genera un token aleatorio y seguro para el enlace de recuperación.
 */
function generarTokenRecuperacion(): string
{
    return bin2hex(random_bytes(32));
}

/**
 * Función genérica de envío de correo HTML vía SMTP (PHPMailer).
 * La reutilizan tanto la recuperación de contraseña como el reporte
 * nutricional, para no duplicar la configuración SMTP en dos sitios.
 *
 * @return bool true si el correo se envió correctamente
 */
function enviarCorreo(string $destino, string $nombreDestino, string $asunto, string $cuerpoHtml, string $textoPlano = ''): bool
{
    // Sin credenciales configuradas no se intenta enviar (se evita esperar un error de conexión)
    if (SMTP_PASSWORD === '' || SMTP_USUARIO === 'tu_correo@gmail.com') {
        error_log('Correo no enviado: falta configurar includes/config.local.php (SMTP_USUARIO y SMTP_PASSWORD).');
        return false;
    }

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USUARIO;
        $mail->Password   = str_replace(' ', '', SMTP_PASSWORD); // Google la muestra con espacios
        $mail->SMTPSecure = SMTP_SEGURIDAD === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PUERTO;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom(SMTP_USUARIO, SMTP_NOMBRE_FROM);
        $mail->addAddress($destino, $nombreDestino);

        $mail->isHTML(true);
        $mail->Subject = $asunto;
        $mail->Body    = $cuerpoHtml;
        $mail->AltBody = $textoPlano !== '' ? $textoPlano : strip_tags($cuerpoHtml);

        $mail->send();
        return true;
    } catch (PHPMailerException $e) {
        error_log('Error enviando correo ("' . $asunto . '"): ' . $mail->ErrorInfo);
        return false;
    }
}

/**
 * Envía el correo con el enlace para restablecer la contraseña, vía SMTP.
 *
 * @return bool true si el correo se envió correctamente
 */
function enviarCorreoRecuperacion(string $destino, string $nombre, string $enlace): bool
{
    $cuerpo = '
    <div style="font-family:Nunito,Arial,sans-serif;max-width:480px;margin:auto;">
      <h2 style="color:#f39c12;">NutriEscolar</h2>
      <p>Hola ' . htmlspecialchars($nombre) . ',</p>
      <p>Recibimos una solicitud para restablecer tu contraseña. Haz clic en el siguiente
         botón para crear una nueva (el enlace expira en 30 minutos):</p>
      <p style="text-align:center;margin:28px 0;">
        <a href="' . htmlspecialchars($enlace) . '"
           style="background:#f39c12;color:white;padding:12px 24px;border-radius:8px;
                  text-decoration:none;font-weight:bold;display:inline-block;">
          Restablecer contraseña
        </a>
      </p>
      <p>Si no solicitaste este cambio, puedes ignorar este mensaje.</p>
      <p style="font-size:12px;color:#999;">Si el botón no funciona, copia y pega este enlace en tu navegador:<br>' . htmlspecialchars($enlace) . '</p>
    </div>';

    $textoPlano = "Hola {$nombre}, para restablecer tu contraseña visita: {$enlace}";

    return enviarCorreo($destino, $nombre, 'Recupera tu contraseña - NutriEscolar', $cuerpo, $textoPlano);
}

/**
 * Construye la URL absoluta hacia restablecer-contrasena.php con el token.
 */
function construirEnlaceRecuperacion(string $token): string
{
    $protocolo = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host      = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $carpeta   = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
    return "{$protocolo}://{$host}{$carpeta}/restablecer-contrasena.php?token=" . urlencode($token);
}
