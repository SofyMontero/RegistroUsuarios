<?php
require_once __DIR__ . '/app/bootstrap.php';
require_once __DIR__ . '/inc/token_sesion.php';
require_once __DIR__ . '/inc/auth.php';

list($token, $sede) = requerir_token_sesion();
$esAdmin = auth_esta_autenticado();
$hrefIngreso = 'verificar.php?token=' . rawurlencode($token);
$hrefAdmin = 'login.php?next=' . rawurlencode('ingresos_huella.php?token=' . rawurlencode($token));
if ($sede !== '') {
    $hrefIngreso .= '&sede=' . rawurlencode($sede);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Manual de usuario | Monteblanco</title>
    <link rel="shortcut icon" href="imagenes/marca/isotipo.svg" />
    <?php require_once __DIR__ . '/inc/marca.php'; marca_head_assets(); ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="Css/estilo.css?v=20260915p" rel="stylesheet" type="text/css" />
    <script src="js/Utils.js?v=20260915p" type="text/javascript"></script>
    <script type="text/javascript">asegurarTokenSesion();</script>
</head>
<body class="biometric-body">
    <div class="biometric-shell">
        <div class="container page-wrap">
            <div class="glass-card topbar-card mb-4">
                <div class="row g-4 align-items-center">
                    <div class="col-12 col-xl-5">
                        <?php marca_product_badge('Ingreso Usuarios'); ?>
                        <span class="eyebrow">Ayuda operativa</span>
                        <h1 class="page-title">Manual de usuario</h1>
                        <p class="section-copy mb-0">
                            Guía de toda la aplicación: configuración del equipo, ingreso por huella, alta de colaboradores y administración.
                        </p>
                    </div>
                    <div class="col-12 col-xl-7">
                        <?php if ($esAdmin) {
                            auth_render_nav('manual', $token, $sede);
                        } else { ?>
                            <div class="action-stack">
                                <a class="btn-soft btn-soft-primary" href="<?php echo htmlspecialchars($hrefIngreso); ?>">Ingreso</a>
                                <a class="btn-soft btn-soft-secondary" href="<?php echo htmlspecialchars($hrefAdmin); ?>">Administración</a>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            </div>

            <div class="glass-card section-card mb-4">
                <h2 class="section-title">Contenido</h2>
                <p class="section-copy mb-4">Use estos accesos para ir directo a la pantalla o al proceso que necesita.</p>
                <nav class="manual-toc" aria-label="Índice del manual">
                    <a href="#inicio">1. Qué hace el sistema</a>
                    <a href="#configuracion">2. Primera configuración</a>
                    <a href="#plugin">3. Plugin biométrico</a>
                    <a href="#ingreso">4. Ingreso de usuarios</a>
                    <a href="#marcas">5. Cómo se marcan los eventos</a>
                    <a href="#cedula">6. Ingreso por cédula</a>
                    <a href="#admin">7. Administración</a>
                    <a href="#asociar">8. Crear usuario y asociar huella</a>
                    <a href="#colaboradores">9. Colaboradores</a>
                    <a href="#sedes">10. Sedes</a>
                    <a href="#historial">11. Historial, estadísticas y Excel</a>
                    <a href="#problemas">12. Problemas frecuentes</a>
                </nav>
            </div>

            <div class="glass-card section-card mb-4 manual-section" id="inicio">
                <h2 class="section-title">1. Qué hace el sistema</h2>
                <p class="section-copy mb-3">
                    Ingreso Usuarios registra la asistencia de los colaboradores con lector de huella o, si está autorizado, con número de cédula.
                    Hay dos espacios:
                </p>
                <ul class="manual-steps">
                    <li><strong>Ingreso</strong> (pantalla pública): el colaborador coloca el dedo o escribe la cédula para marcar llegada, almuerzo, break o salida.</li>
                    <li><strong>Administración</strong> (requiere usuario administrador): alta de huellas, sedes, datos de colaboradores, historial y reportes.</li>
                </ul>
            </div>

            <div class="glass-card section-card mb-4 manual-section" id="configuracion">
                <h2 class="section-title">2. Primera configuración del equipo</h2>
                <p class="section-copy mb-3">
                    Cada computador tiene un token propio. El navegador y el plugin deben usar exactamente el mismo valor; si no coinciden, la huella no llega al sistema.
                </p>
                <ol class="manual-steps">
                    <li>Abra <code>index.php</code> en el navegador de esa estación.</li>
                    <li>Si es la primera vez, copie el token generado.</li>
                    <li>Péguelo en el plugin biométrico, en el campo <strong>ID único PC</strong>.</li>
                    <li>Confirme que ambos valores son idénticos y pulse <strong>Guardar</strong> en el plugin.</li>
                    <li>Use <strong>Ingreso</strong> para ir a control de asistencia o <strong>Administración</strong> para entrar con un usuario administrador.</li>
                </ol>
                <p class="manual-note mt-4 mb-0">
                    Si el token deja de funcionar, vuelva a abrir <code>index.php</code> o borre el almacenamiento local del sitio y configure de nuevo el mismo valor en el plugin.
                </p>
            </div>

            <div class="glass-card section-card mb-4 manual-section" id="plugin">
                <h2 class="section-title">3. Plugin biométrico</h2>
                <p class="section-copy mb-3">
                    El plugin es el programa de Windows que habla con el lector Digital Persona. Debe quedar instalado y abierto en cada PC de marcación.
                </p>
                <ol class="manual-steps">
                    <li>Instale el plugin en la PC del operador y ejecute <code>PluginBiometrico.exe</code>.</li>
                    <li>Complete la URL de habilitar sensor, la URL de la API REST y el ID único PC (el token de la web).</li>
                    <li>Deje el icono del sensor visible en la bandeja de Windows (esquina inferior derecha).</li>
                    <li>En uso diario no cierre el plugin: la web activa el sensor y el plugin captura o lee la huella.</li>
                </ol>
                <div class="manual-callout mt-4">
                    <strong>Paso obligatorio después de crear un usuario</strong>
                    Al guardar un colaborador nuevo, cierre el plugin por completo (icono de la bandeja → Cerrar) y vuélvalo a abrir.
                    El plugin carga las huellas al iniciar. Si no lo reinicia, el usuario creado en ese momento no podrá marcar ingreso.
                </div>
            </div>

            <div class="glass-card section-card mb-4 manual-section" id="ingreso">
                <h2 class="section-title">4. Ingreso de usuarios</h2>
                <p class="section-copy mb-3">
                    Esta es la pantalla de control de asistencia. El sensor queda en modo lectura al entrar.
                    Muestra documento, nombre, foto y el estado del lector.
                </p>
                <ol class="manual-steps">
                    <li>Confirme que el plugin está abierto y que el recuadro del sensor dice que está escuchando.</li>
                    <li>Pida al colaborador que coloque el dedo en el lector.</li>
                    <li>El sistema identifica la huella, muestra el perfil y registra el siguiente evento del día.</li>
                    <li>Si hace falta, use el campo <strong>Cédula</strong> y el botón <strong>Registrar</strong> (solo para personas autorizadas a ingresar por documento).</li>
                </ol>
                <p class="section-copy mt-4 mb-0">
                    Desde esta pantalla, <strong>Administración</strong> abre el historial si ya hay sesión, o el inicio de sesión si no la hay.
                    También puede abrir este manual en cualquier momento.
                </p>
            </div>

            <div class="glass-card section-card mb-4 manual-section" id="marcas">
                <h2 class="section-title">5. Cómo se marcan los eventos</h2>
                <p class="section-copy mb-3">
                    Cada vez que el colaborador pone el dedo (o registra la cédula), el sistema guarda el siguiente evento pendiente del día, en este orden:
                </p>
                <ol class="manual-steps">
                    <li><strong>Ingreso</strong> a la jornada.</li>
                    <li><strong>Sale almuerzo</strong>.</li>
                    <li><strong>Regresa almuerzo</strong>. Si pasan más de 60 minutos, el historial marca el regreso en rojo.</li>
                    <li><strong>Sale break</strong> (opcional, 15 minutos). Solo se marca si ocurre dentro de las 3 horas siguientes al regreso de almuerzo.</li>
                    <li><strong>Regresa break</strong>. Si pasan más de 15 minutos, el historial marca el regreso en rojo.</li>
                    <li><strong>Salida</strong> de la jornada. Si ya pasaron más de 3 horas desde el almuerzo y no hubo break, la siguiente marca es salida.</li>
                </ol>
                <p class="manual-note mt-4 mb-0">
                    Debe haber al menos 10 minutos entre una marca y la siguiente. Si el colaborador vuelve a poner el dedo demasiado pronto, el sistema no registra un evento nuevo.
                </p>
            </div>

            <div class="glass-card section-card mb-4 manual-section" id="cedula">
                <h2 class="section-title">6. Ingreso por cédula</h2>
                <p class="section-copy mb-3">
                    El campo de cédula de la pantalla de ingreso no está habilitado para todo el mundo.
                    En <strong>Colaboradores</strong>, el administrador decide quién puede marcar escribiendo el documento.
                </p>
                <ol class="manual-steps">
                    <li>Escriba el número de documento.</li>
                    <li>Pulse <strong>Registrar</strong> o la tecla Enter.</li>
                    <li>Si la persona está autorizada, se guarda el mismo tipo de evento que con la huella.</li>
                    <li>Si no está autorizada o el documento no existe, aparece un aviso y no se registra marca.</li>
                </ol>
            </div>

            <div class="glass-card section-card mb-4 manual-section" id="admin">
                <h2 class="section-title">7. Administración</h2>
                <p class="section-copy mb-3">
                    El ingreso de asistencia es público. Las pantallas de gestión piden un usuario con rol administrador.
                </p>
                <ol class="manual-steps">
                    <li>Pulse <strong>Administración</strong> o abra <code>login.php</code>.</li>
                    <li>Escriba usuario y contraseña del registro administrador.</li>
                    <li>Al entrar verá el menú: Ver ingresos, Asociar huella, Colaboradores, Sedes y Manual.</li>
                    <li>Use <strong>Cerrar sesión</strong> al terminar. <strong>Ingreso</strong> vuelve a la pantalla pública de marcación.</li>
                </ol>
            </div>

            <div class="glass-card section-card mb-4 manual-section" id="asociar">
                <h2 class="section-title">8. Crear usuario y asociar huella</h2>
                <p class="section-copy mb-3">
                    En <strong>Asociar huella</strong> se registra al colaborador y se captura su huella en un solo proceso.
                    El plugin debe estar abierto y con el mismo token de la página.
                </p>
                <ol class="manual-steps">
                    <li>Diligencie documento, nombre, teléfono (opcional), sede, género y horario.</li>
                    <li>El horario inicial usa los valores por defecto de la jornada (ingreso 7:00, salida 15:00, 7 horas diarias, 42 semanales y descanso el domingo). Ajústelos si el colaborador tiene otro turno.</li>
                    <li>Pulse <strong>Capturar huella</strong> y pida al colaborador que coloque el dedo en el lector las veces que indique el plugin, hasta que la plantilla quede lista.</li>
                    <li>Cuando la captura termine, pulse <strong>Guardar usuario y huella</strong>.</li>
                    <li>Cierre el plugin biométrico y vuélvalo a abrir antes de que esa persona intente marcar.</li>
                </ol>
                <div class="manual-callout mt-4">
                    <strong>Importante</strong>
                    Crear el usuario no basta para que pueda ingresar de inmediato.
                    Es necesario cerrar el plugin y volverlo a abrir para cargar la huella nueva y dar ingreso al usuario creado en ese momento.
                </div>
            </div>

            <div class="glass-card section-card mb-4 manual-section" id="colaboradores">
                <h2 class="section-title">9. Colaboradores</h2>
                <p class="section-copy mb-3">
                    Aquí se consultan y editan los datos de las personas ya registradas. Puede filtrar por sede o buscar por documento y nombre.
                </p>
                <p class="section-copy mb-3">Use el lápiz de cada fila para cambiar:</p>
                <ul class="manual-steps">
                    <li>Nombre y cédula.</li>
                    <li>Sede.</li>
                    <li>Si puede ingresar por cédula.</li>
                    <li>Hora de ingreso y salida, jornada diaria, jornada semanal y día de descanso.</li>
                </ul>
                <p class="section-copy mt-4 mb-0">
                    Guardar los cambios actualiza el listado. Esta pantalla no recaptura huella: para una huella nueva use <strong>Asociar huella</strong>.
                </p>
            </div>

            <div class="glass-card section-card mb-4 manual-section" id="sedes">
                <h2 class="section-title">10. Sedes</h2>
                <p class="section-copy mb-3">
                    Las sedes se usan para agrupar colaboradores y filtrar ingresos.
                </p>
                <ol class="manual-steps">
                    <li>Escriba el nombre y pulse <strong>Agregar sede</strong>.</li>
                    <li>La sede nueva queda disponible en Asociar huella, Colaboradores e Historial.</li>
                    <li>Solo se puede borrar una sede que no tenga usuarios asignados.</li>
                </ol>
            </div>

            <div class="glass-card section-card mb-4 manual-section" id="historial">
                <h2 class="section-title">11. Historial, estadísticas y Excel</h2>
                <p class="section-copy mb-3">
                    <strong>Ver ingresos</strong> muestra las marcaciones del período. Por defecto consulta el mes en curso.
                </p>
                <ul class="manual-steps">
                    <li>Filtre por sede, rango de fechas y búsqueda (documento o nombre), luego pulse <strong>Filtrar</strong>.</li>
                    <li>La tabla trae colaborador, fecha, ingreso, almuerzo, break y salida. El rojo indica almuerzo de más de 60 minutos o break de más de 15.</li>
                    <li>El lápiz abre la corrección de horas. Use el formato 00:00:00; ese valor deja la marca vacía.</li>
                    <li><strong>Ver estadísticas</strong> resume horas ordinarias, nocturnas, extra diurnas, extra nocturnas y dominicales o festivas del período filtrado.</li>
                    <li><strong>Descargar Excel</strong> exporta todo el rango consultado, no solo la página visible de la tabla.</li>
                </ul>
            </div>

            <div class="glass-card section-card mb-4 manual-section" id="problemas">
                <h2 class="section-title">12. Problemas frecuentes</h2>
                <div class="metric-grid">
                    <div class="metric-card">
                        <p class="metric-label">El lector no responde</p>
                        <p class="metric-value">Confirme que el plugin está abierto, que el token coincide y que el lector tiene drivers instalados.</p>
                    </div>
                    <div class="metric-card">
                        <p class="metric-label">Usuario nuevo no ingresa</p>
                        <p class="metric-value">Cierre el plugin y ábralo de nuevo. Sin ese reinicio, la huella recién guardada no está disponible para marcar.</p>
                    </div>
                    <div class="metric-card">
                        <p class="metric-label">No marca por cédula</p>
                        <p class="metric-value">En Colaboradores active “Ingresa por cédula” para esa persona.</p>
                    </div>
                    <div class="metric-card">
                        <p class="metric-label">No entra a administración</p>
                        <p class="metric-value">Use un usuario con rol administrador. El ingreso de asistencia no pide esa cuenta.</p>
                    </div>
                    <div class="metric-card">
                        <p class="metric-label">El token dejó de servir</p>
                        <p class="metric-value">Abra de nuevo index.php, copie el token y péguelo otra vez en el plugin.</p>
                    </div>
                    <div class="metric-card">
                        <p class="metric-label">No se puede guardar la huella</p>
                        <p class="metric-value">Capture primero hasta que el estado indique que la plantilla está lista y después pulse Guardar.</p>
                    </div>
                </div>
            </div>
            <?php marca_footer(); ?>
        </div>
    </div>
</body>
</html>
