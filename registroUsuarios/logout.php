<?php
require_once __DIR__ . '/inc/auth.php';

auth_cerrar_sesion();
header('Location: verificar.php', true, 302);
exit;
