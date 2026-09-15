<?php
/**
 * Alias: el alta de usuario ahora vive en Asociar huella.
 */
require_once __DIR__ . '/inc/token_sesion.php';
require_once __DIR__ . '/inc/auth.php';

requerir_admin();
list($token, $sede) = requerir_token_sesion();

$destino = 'asociar_huellas.php?token=' . rawurlencode($token);
if ($sede !== '') {
    $destino .= '&sede=' . rawurlencode($sede);
}

header('Location: ' . $destino, true, 302);
exit;
