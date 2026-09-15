<?php
require_once __DIR__ . '/inc/auth.php';
requerir_admin();

$query = isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] !== ''
    ? ('?' . $_SERVER['QUERY_STRING'])
    : '';
header('Location: colaboradores.php' . $query, true, 302);
exit;
