<?php
require_once __DIR__ . '/app/bootstrap.php';
require_once __DIR__ . '/inc/auth.php';

$next = isset($_GET['next']) ? $_GET['next'] : (isset($_POST['next']) ? $_POST['next'] : 'ingresos_huella.php');
$destino = auth_destino_seguro($next);
$error = '';

if (auth_esta_autenticado()) {
    header('Location: ' . $destino, true, 302);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!auth_csrf_ok(isset($_POST['csrf']) ? $_POST['csrf'] : '')) {
        $error = 'La sesión del formulario expiró. Inténtelo de nuevo.';
    } else {
        $usuario = isset($_POST['usuario']) ? trim((string) $_POST['usuario']) : '';
        $clave = isset($_POST['clave']) ? (string) $_POST['clave'] : '';
        try {
            if (!auth_intentar_login($usuario, $clave)) {
                $error = 'Usuario o contraseña incorrectos, o el usuario no tiene rol de administrador.';
            } else {
                header('Location: ' . $destino, true, 302);
                exit;
            }
        } catch (\Throwable $exception) {
            $error = 'No fue posible validar el acceso. Revise la conexión a la base de datos.';
        }
    }
}

$csrf = auth_csrf_token();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Administración | Monteblanco</title>
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
                        <span class="eyebrow">Acceso restringido</span>
                        <h1 class="page-title mt-3">Iniciar sesión</h1>
                        <p class="page-subtitle mb-4">
                            Use el usuario y la contraseña de un registro con rol administrador. El ingreso de asistencia sigue siendo público.
                        </p>

                        <?php if ($error !== '') { ?>
                            <div class="auth-alert mb-4" role="alert"><?php echo htmlspecialchars($error); ?></div>
                        <?php } ?>

                        <form class="form-panel" method="post" action="login.php" autocomplete="on">
                            <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>" />
                            <input type="hidden" name="next" value="<?php echo htmlspecialchars($destino); ?>" />

                            <div>
                                <label class="field-label" for="usuario">Usuario</label>
                                <input class="form-control biometric-input" id="usuario" name="usuario" type="text" required maxlength="100" autocomplete="username" />
                            </div>

                            <div>
                                <label class="field-label" for="clave">Contraseña</label>
                                <input class="form-control biometric-input" id="clave" name="clave" type="password" required autocomplete="current-password" />
                            </div>

                            <button class="btn btn-primary btn-lg rounded-4 w-100" type="submit">Entrar</button>
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
