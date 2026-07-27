<?php
/**
 * Entrada legacy: antes esta era la pantalla inicial.
 * Ahora el hub es verificar.php (Control de asistencia).
 */
require_once __DIR__ . '/inc/token_sesion.php';

list($token, $sede) = requerir_token_sesion();

$destino = 'verificar.php?token=' . rawurlencode($token);
if ($sede !== '') {
    $destino .= '&sede=' . rawurlencode($sede);
}

header('Location: ' . $destino, true, 302);
exit;
