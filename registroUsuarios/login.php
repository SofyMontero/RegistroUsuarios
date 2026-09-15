<?php
require_once __DIR__ . '/app/bootstrap.php';
require_once __DIR__ . '/inc/auth.php';

$next = isset($_GET['next']) ? $_GET['next'] : (isset($_POST['next']) ? $_POST['next'] : 'ingresos_huella.php');
$destino = auth_destino_seguro($next);
$error = '';
$esAltaInicial = false;

try {
    $esAltaInicial = !auth_hay_administradores();
} catch (\Throwable $exception) {
    $error = 'No fue posible conectar con la base de datos para autenticar.';
}

if (auth_esta_autenticado() && $error === '') {
    header('Location: ' . $destino, true, 302);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $error === '') {
    if (!auth_csrf_ok(isset($_POST['csrf']) ? $_POST['csrf'] : '')) {
        $error = 'La sesión del formulario expiró. Inténtelo de nuevo.';
    } elseif ($esAltaInicial) {
        $usuario = isset($_POST['usuario']) ? trim((string) $_POST['usuario']) : '';
        $nombre = isset($_POST['nombre']) ? trim((string) $_POST['nombre']) : '';
        $clave = isset($_POST['clave']) ? (string) $_POST['clave'] : '';
        $clave2 = isset($_POST['clave2']) ? (string) $_POST['clave2'] : '';

        if (!preg_match('/^[a-zA-Z0-9._-]{3,80}$/', $usuario)) {
            $error = 'El usuario debe tener entre 3 y 80 caracteres (letras, números, punto, guion o guion bajo).';
        } elseif (strlen($clave) < 8) {
            $error = 'La contraseña debe tener al menos 8 caracteres.';
        } elseif ($clave !== $clave2) {
            $error = 'Las contraseñas no coinciden.';
        } else {
            try {
                auth_crear_administrador($usuario, $clave, $nombre);
                if (auth_establecer_sesion_creada($usuario, $nombre)) {
                    header('Location: ' . $destino, true, 302);
                    exit;
                }
                $error = 'El administrador se creó, pero no fue posible abrir la sesión.';
            } catch (\Throwable $exception) {
                $error = 'No fue posible crear el administrador inicial.';
            }
        }
    } else {
        $usuario = isset($_POST['usuario']) ? trim((string) $_POST['usuario']) : '';
        $clave = isset($_POST['clave']) ? (string) $_POST['clave'] : '';
        if (!auth_intentar_login($usuario, $clave)) {
            $error = 'Usuario o contraseña incorrectos.';
        } else {
            header('Location: ' . $destino, true, 302);
            exit;
        }
    }
}

$csrf = auth_csrf_token();
$titulo = $esAltaInicial ? 'Crear administrador' : 'Administración';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo htmlspecialchars($titulo); ?> | Monteblanco</title>
    <link rel="shortcut icon" href="imagenes/marca/isotipo.svg" />
    <?php require_once __DIR__ . '/inc/marca.php'; marca_head_assets(); ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="Css/estilo.css?v=20260915e" rel="stylesheet" type="text/css" />
</head>
<body class="biometric-body">
    <div class="biometric-shell">
        <div class="container page-wrap">
            <div class="row justify-content-center">
                <div class="col-12 col-md-8 col-lg-5">
                    <div class="glass-card section-card login-card p-4 p-md-5">
                        <?php marca_product_badge('Ingreso Usuarios'); ?>
                        <span class="eyebrow"><?php echo $esAltaInicial ? 'Configuración inicial' : 'Acceso restringido'; ?></span>
                        <h1 class="page-title mt-3"><?php echo $esAltaInicial ? 'Crear primer administrador' : 'Iniciar sesión'; ?></h1>
                        <p class="page-subtitle mb-4">
                            <?php if ($esAltaInicial) { ?>
                                Aún no hay usuarios administrativos. Cree la cuenta que usará para Ver ingresos y Asociar huella.
                            <?php } else { ?>
                                Esta sección protege Ver ingresos y Asociar huella. El ingreso de asistencia sigue siendo público.
                            <?php } ?>
                        </p>

                        <?php if ($error !== '') { ?>
                            <div class="auth-alert mb-4" role="alert"><?php echo htmlspecialchars($error); ?></div>
                        <?php } ?>

                        <form class="form-panel" method="post" action="login.php" autocomplete="on">
                            <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>" />
                            <input type="hidden" name="next" value="<?php echo htmlspecialchars($destino); ?>" />

                            <div>
                                <label class="field-label" for="usuario">Usuario</label>
                                <input class="form-control biometric-input" id="usuario" name="usuario" type="text" required maxlength="80" autocomplete="username" />
                            </div>

                            <?php if ($esAltaInicial) { ?>
                                <div>
                                    <label class="field-label" for="nombre">Nombre</label>
                                    <input class="form-control biometric-input" id="nombre" name="nombre" type="text" maxlength="120" autocomplete="name" placeholder="Administrador" />
                                </div>
                            <?php } ?>

                            <div>
                                <label class="field-label" for="clave">Contraseña</label>
                                <input class="form-control biometric-input" id="clave" name="clave" type="password" required minlength="<?php echo $esAltaInicial ? '8' : '1'; ?>" autocomplete="<?php echo $esAltaInicial ? 'new-password' : 'current-password'; ?>" />
                            </div>

                            <?php if ($esAltaInicial) { ?>
                                <div>
                                    <label class="field-label" for="clave2">Confirmar contraseña</label>
                                    <input class="form-control biometric-input" id="clave2" name="clave2" type="password" required minlength="8" autocomplete="new-password" />
                                </div>
                            <?php } ?>

                            <button class="btn btn-primary btn-lg rounded-4 w-100" type="submit">
                                <?php echo $esAltaInicial ? 'Crear e ingresar' : 'Entrar'; ?>
                            </button>
                        </form>

                        <div class="d-flex justify-content-center mt-4">
                            <a class="btn-soft btn-soft-secondary" href="verificar.php">Volver a ingreso</a>
                        </div>
                    </div>
                </div>
            </div>
            <?php marca_footer(); ?>
        </div>
    </div>
</body>
</html>
