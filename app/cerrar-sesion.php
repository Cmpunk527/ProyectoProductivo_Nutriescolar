<?php
require_once __DIR__ . '/includes/sesion.php';
cerrarSesionActual();
header('Location: index.php');
exit;
