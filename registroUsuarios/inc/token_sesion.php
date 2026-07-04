<?php

/**
 * Si ?token= viene vacio, delega en JS (localStorage) o redirige a index.php.
 * Evita renderizar Home/verificar con token="" en la URL.
 */
function requerir_token_sesion()
{
    $token = isset($_GET['token']) ? trim($_GET['token']) : '';
    $sede = isset($_GET['sede']) ? $_GET['sede'] : '';

    if ($token !== '') {
        return array($token, $sede);
    }

    header('Content-Type: text/html; charset=utf-8');
    ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Preparando sesión...</title>
    <script src="js/Utils.js"></script>
    <script>asegurarTokenSesion();</script>
</head>
<body>
    <p style="font-family: sans-serif; padding: 2rem;">Preparando sesión biométrica...</p>
</body>
</html>
    <?php
    exit;
}
