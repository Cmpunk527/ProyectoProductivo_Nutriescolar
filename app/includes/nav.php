<?php
/**
 * includes/nav.php
 * -----------------------------------------------------------------------
 * Enlaces del menú de navegación según el rol del usuario con sesión.
 * Se usa dentro de <div class="nav-menu" id="navMenu"> así:
 *
 *     <?php renderNavUsuario(); ?>
 *
 * Roles:
 *   admin       -> Panel admin, Ver menú, Consultar menú, Nutrición
 *   estudiante  -> Ver menú, Mi consumo, Consultar menú, Nutrición
 *   acudiente   -> Ver menú, Consumo del estudiante, Consultar menú, Nutrición
 *   profesor    -> Ver menú, Consultar menú, Nutrición
 * (el enlace de la página actual no se repite)
 * -----------------------------------------------------------------------
 */
require_once __DIR__ . '/sesion.php';

function renderNavUsuario(): void
{
    $actual  = basename($_SERVER['SCRIPT_NAME'] ?? '');
    $enlaces = [];

    if (esAdmin()) {
        $enlaces[] = ['admin-panel.php', 'Panel admin'];
    }
    $enlaces[] = ['ver-menu.php', 'Ver menú'];
    if (puedeConsultarConsumo() && !esAdmin()) {
        $enlaces[] = ['consultar-consumo.php', etiquetaConsumo()];
    }
    $enlaces[] = ['consultar-menu.php', 'Consultar menú'];
    $enlaces[] = ['informacion-nutricional.php', 'Nutrición'];

    foreach ($enlaces as [$href, $texto]) {
        if ($href !== $actual) {
            echo '<a href="' . $href . '">' . htmlspecialchars($texto) . '</a>' . "\n    ";
        }
    }
    echo '<a href="cerrar-sesion.php" class="btn-salir">Cerrar sesión</a>';
}
