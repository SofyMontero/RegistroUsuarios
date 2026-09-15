<?php

/**
 * Autenticación de administradores (sesión PHP).
 * El ingreso biométrico permanece público; estas funciones cubren
 * Ver ingresos, Asociar huella y sus APIs.
 */

if (!class_exists('Huella\\Core\\Database')) {
    require_once dirname(__DIR__) . '/app/bootstrap.php';
}

function auth_paginas_permitidas()
{
    return array(
        'ingresos_huella.php',
        'asociar_huellas.php',
        'registro_usuarios.php',
    );
}

function auth_cookie_path()
{
    $script = isset($_SERVER['SCRIPT_NAME']) ? str_replace('\\', '/', $_SERVER['SCRIPT_NAME']) : '/';
    $dir = rtrim(dirname($script), '/');
    if ($dir === '' || $dir === '\\' || $dir === '.') {
        return '/';
    }
    if (strtolower(basename($dir)) === 'model') {
        $dir = rtrim(dirname($dir), '/');
    }
    if ($dir === '' || $dir === '\\' || $dir === '.') {
        return '/';
    }
    return $dir . '/';
}

function auth_iniciar_sesion()
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $secure = false;
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        $secure = true;
    } elseif (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443) {
        $secure = true;
    }

    session_name('ru_admin');
    session_set_cookie_params(array(
        'lifetime' => 0,
        'path' => auth_cookie_path(),
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ));
    session_start();
}

function auth_liberar_sesion()
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }
}

function auth_usuario_actual()
{
    auth_iniciar_sesion();
    if (empty($_SESSION['admin_id'])) {
        return null;
    }

    return array(
        'id' => (int) $_SESSION['admin_id'],
        'usuario' => (string) $_SESSION['admin_usuario'],
        'nombre' => (string) $_SESSION['admin_nombre'],
    );
}

function auth_esta_autenticado()
{
    return auth_usuario_actual() !== null;
}

function auth_csrf_token()
{
    auth_iniciar_sesion();
    if (empty($_SESSION['admin_csrf'])) {
        $_SESSION['admin_csrf'] = bin2hex(random_bytes(16));
    }
    return (string) $_SESSION['admin_csrf'];
}

function auth_csrf_ok($token)
{
    auth_iniciar_sesion();
    if (!is_string($token) || $token === '' || empty($_SESSION['admin_csrf'])) {
        return false;
    }
    return hash_equals((string) $_SESSION['admin_csrf'], $token);
}

function auth_destino_seguro($next)
{
    $fallback = 'ingresos_huella.php';
    if (!is_string($next) || $next === '') {
        return $fallback;
    }

    $next = str_replace('\\', '/', $next);
    if (preg_match('#^(https?:)?//#i', $next) || strpos($next, '..') !== false) {
        return $fallback;
    }

    $partes = parse_url($next);
    if ($partes === false) {
        return $fallback;
    }

    $path = isset($partes['path']) ? $partes['path'] : strtok($next, '?');
    $base = basename($path);
    if (!in_array($base, auth_paginas_permitidas(), true)) {
        return $fallback;
    }

    $query = isset($partes['query']) ? $partes['query'] : '';
    return $base . ($query !== '' ? ('?' . $query) : '');
}

function auth_destino_desde_request()
{
    $script = isset($_SERVER['SCRIPT_NAME']) ? basename($_SERVER['SCRIPT_NAME']) : 'ingresos_huella.php';
    $query = isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '';
    $url = $script;
    if ($query !== '') {
        $url .= '?' . $query;
    }
    return auth_destino_seguro($url);
}

function auth_href_administracion($token)
{
    $destino = 'ingresos_huella.php';
    if ($token !== '') {
        $destino .= '?token=' . rawurlencode($token);
    }
    if (auth_esta_autenticado()) {
        return $destino;
    }
    return 'login.php?next=' . rawurlencode($destino);
}

function requerir_admin()
{
    header('Cache-Control: no-store, private, max-age=0');
    header('Pragma: no-cache');

    if (auth_esta_autenticado()) {
        return auth_usuario_actual();
    }

    $next = auth_destino_desde_request();
    header('Location: login.php?next=' . rawurlencode($next), true, 302);
    exit;
}

function requerir_admin_api()
{
    $ok = auth_esta_autenticado();
    auth_liberar_sesion();
    if ($ok) {
        return;
    }

    http_response_code(401);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array(
        'filas' => 0,
        'success' => false,
        'message' => 'Debe iniciar sesión para usar esta función',
    ));
    exit;
}

function auth_db()
{
    $db = new \Huella\Core\Database();
    try {
        $db->fetchOne('SELECT id FROM admin_usuarios LIMIT 1');
    } catch (\Throwable $exception) {
        $db->execute(
            'CREATE TABLE IF NOT EXISTS admin_usuarios (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                usuario VARCHAR(80) NOT NULL,
                password_hash VARCHAR(255) NOT NULL,
                nombre VARCHAR(120) NOT NULL DEFAULT \'Administrador\',
                creado_en DATETIME NOT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY usuario_unico (usuario)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8'
        );
    }
    return $db;
}

function auth_hay_administradores()
{
    $row = auth_db()->fetchOne('SELECT COUNT(*) AS total FROM admin_usuarios');
    return $row && (int) $row['total'] > 0;
}

function auth_normalizar_usuario($usuario)
{
    return strtolower(trim((string) $usuario));
}

function auth_crear_administrador($usuario, $clave, $nombre)
{
    $usuario = auth_normalizar_usuario($usuario);
    $nombre = trim((string) $nombre);
    if ($nombre === '') {
        $nombre = 'Administrador';
    }

    return auth_db()->execute(
        'INSERT INTO admin_usuarios (usuario, password_hash, nombre, creado_en)
         VALUES (:usuario, :password_hash, :nombre, NOW())',
        array(
            'usuario' => $usuario,
            'password_hash' => password_hash($clave, PASSWORD_DEFAULT),
            'nombre' => $nombre,
        )
    );
}

function auth_intentar_login($usuario, $clave)
{
    $usuario = auth_normalizar_usuario($usuario);
    if ($usuario === '' || $clave === '') {
        return false;
    }

    $row = auth_db()->fetchOne(
        'SELECT id, usuario, password_hash, nombre FROM admin_usuarios WHERE usuario = :usuario LIMIT 1',
        array('usuario' => $usuario)
    );
    if (!$row || !password_verify($clave, $row['password_hash'])) {
        return false;
    }

    auth_iniciar_sesion();
    session_regenerate_id(true);
    $_SESSION['admin_id'] = (int) $row['id'];
    $_SESSION['admin_usuario'] = (string) $row['usuario'];
    $_SESSION['admin_nombre'] = (string) $row['nombre'];
    $_SESSION['admin_csrf'] = bin2hex(random_bytes(16));
    return true;
}

function auth_establecer_sesion_creada($usuario, $nombre)
{
    $row = auth_db()->fetchOne(
        'SELECT id, usuario, nombre FROM admin_usuarios WHERE usuario = :usuario LIMIT 1',
        array('usuario' => auth_normalizar_usuario($usuario))
    );
    if (!$row) {
        return false;
    }

    auth_iniciar_sesion();
    session_regenerate_id(true);
    $_SESSION['admin_id'] = (int) $row['id'];
    $_SESSION['admin_usuario'] = (string) $row['usuario'];
    $_SESSION['admin_nombre'] = $nombre !== '' ? $nombre : (string) $row['nombre'];
    $_SESSION['admin_csrf'] = bin2hex(random_bytes(16));
    return true;
}

function auth_cerrar_sesion()
{
    auth_iniciar_sesion();
    $_SESSION = array();
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

function auth_render_nav($paginaActiva, $token, $sede = '', $extras = array())
{
    $user = auth_usuario_actual();
    $q = 'token=' . rawurlencode($token);
    if ($sede !== '') {
        $q .= '&sede=' . rawurlencode($sede);
    }

    $items = array(
        'ingresos' => array('href' => 'ingresos_huella.php?' . $q, 'label' => 'Ver ingresos'),
        'asociar' => array('href' => 'asociar_huellas.php?' . $q, 'label' => 'Asociar huella'),
    );

    echo '<div class="action-stack">';
    if ($user) {
        $etiqueta = $user['nombre'] !== '' ? $user['nombre'] : $user['usuario'];
        echo '<span class="status-pill">Sesión: ' . htmlspecialchars($etiqueta) . '</span>';
    }

    foreach ($items as $key => $item) {
        $cls = $key === $paginaActiva ? 'btn-soft btn-soft-primary' : 'btn-soft btn-soft-secondary';
        echo '<a class="' . $cls . '" href="' . htmlspecialchars($item['href']) . '">' . htmlspecialchars($item['label']) . '</a>';
    }

    if (is_array($extras)) {
        foreach ($extras as $extra) {
            if (!empty($extra['button'])) {
                $attrs = isset($extra['attrs']) ? $extra['attrs'] : '';
                echo '<button class="btn-soft btn-soft-secondary" type="button" ' . $attrs . '>'
                    . htmlspecialchars($extra['label']) . '</button>';
            } elseif (!empty($extra['href'])) {
                echo '<a class="btn-soft btn-soft-secondary" href="' . htmlspecialchars($extra['href']) . '">'
                    . htmlspecialchars($extra['label']) . '</a>';
            }
        }
    }

    echo '<a class="btn-soft btn-soft-secondary" href="verificar.php?token=' . rawurlencode($token) . '">Ingreso</a>';
    echo '<a class="btn-soft btn-soft-secondary" href="logout.php">Cerrar sesión</a>';
    echo '</div>';
}
