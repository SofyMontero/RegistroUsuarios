<?php
$fechaactual = date("Y-m-d");
$token = isset($_GET["token"]) ? $_GET["token"] : "";
$sede = isset($_GET["sede"]) ? $_GET["sede"] : "";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="refresh" content="600" />
    <title>Huella Biometrica | Bermudas</title>
    <link rel="shortcut icon" href="images/fondo4.png" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;700;800&display=swap" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="Css/estilo.css" rel="stylesheet" type="text/css" />
    <script src="js/jquery-1.7.2.min.js" type="text/javascript"></script>
</head>
<body class="biometric-body">
    <div class="biometric-shell">
        <div id="mensaje">
            <img id="imageMenssage" class="message-icon" alt="" />
            <div class="messageStyle">
                <p id="txtMensaje" class="mb-0"></p>
            </div>
        </div>

        <div class="container page-wrap">
            <div class="glass-card topbar-card mb-4">
                <div class="row g-4 align-items-center">
                    <div class="col-lg-7">
                        <span class="eyebrow">Modulo biometrico</span>
                        <h1 class="page-title">Asociar huellas</h1>
                    </div>
                    <div class="col-lg-5">
                        <div class="action-stack">
                            <a class="btn-soft btn-soft-secondary" href="../seguimientouser.php">Regresar</a>
                            <a class="btn-soft btn-soft-primary" href="Model/ActivarSensorReader.php?token=<?php echo $token; ?>&sede=<?php echo $sede; ?>">
                                Ingreso de usuarios
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="summary-layout summary-layout-home">
                <div class="glass-card section-card">
                    <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-4">
                        <div>
                            <h2 class="section-title">Datos del colaborador</h2>
                            <p class="section-copy">Completa la informacion base y luego activa el lector para asociar la huella sin perder de vista el estado del sensor.</p>
                        </div>
                        <div class="token-box">
                            <div class="metric-label mb-1">Token de sesion</div>
                            <span class="token-value"><?php echo htmlspecialchars($token); ?></span>
                        </div>
                    </div>

                    <form class="form-panel" onsubmit="return false;">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="field-label" for="documento">Documento</label>
                                <input class="form-control biometric-input" placeholder="Numero de documento" id="documento" type="text" />
                            </div>
                            <div class="col-md-6">
                                <label class="field-label" for="nombre">Nombre completo</label>
                                <input class="form-control biometric-input" placeholder="Nombre del colaborador" id="nombre" type="text" />
                            </div>
                            <div class="col-12">
                                <input type="hidden" id="tel" />
                                <label class="field-label" for="foto">Fotografia</label>
                                <input class="form-control biometric-input biometric-file" id="foto" type="file" accept="image/png,image/jpeg" />
                                <p class="helper-text mt-2">Carga una foto limpia para mostrarla cuando el usuario sea reconocido por huella.</p>
                            </div>
                        </div>

                        <div class="d-flex flex-column flex-md-row gap-3 pt-2">
                            <button class="btn btn-lg btn-primary flex-fill rounded-4" id="activeSensorLocal" onclick="activarSensor('<?php echo $token; ?>')" type="button">
                                Asociar huella
                            </button>
                            <button class="btn btn-lg btn-outline-primary flex-fill rounded-4" id="saveChanges" onclick="addUser('<?php echo $token; ?>')" type="button">
                                Guardar registro
                            </button>
                        </div>
                    </form>
                </div>

                <div class="desktop-panel">
                    <div class="glass-card section-card scanner-card" id="fingerPrint" style="display: none;">
                        <div class="scanner-frame">
                            <img id="<?php echo $token; ?>" src="imagenes/finger.png" alt="Lector de huella" />
                        </div>
                        <div class="scanner-status">
                            <label id="<?php echo $token . "_status"; ?>">Estado del sensor: Inactivo</label>
                            <textarea id="<?php echo $token . "_texto"; ?>" readonly>---</textarea>
                        </div>
                        <div class="d-flex gap-2 flex-wrap justify-content-center">
                            <span class="status-pill">Lector activo</span>
                            <span class="eyebrow">Vista para PC</span>
                        </div>
                    </div>

                    <div class="glass-card section-card empty-placeholder" id="sensorPlaceholder">
                        Activa el sensor para empezar la captura. Cuando el lector este listo, aqui veras el estado y la imagen de la huella.
                    </div>
                </div>
            </div>

            <details class="glass-card guide-collapse mt-4">
                <summary class="guide-summary">
                    <span class="guide-summary-text">Guia de uso</span>
                    <span class="guide-summary-icon" aria-hidden="true">+</span>
                </summary>
                <div class="guide-content">
                    <p class="section-copy mb-4">La ayuda queda al final y oculta por defecto para mantener la pantalla principal enfocada en el trabajo operativo.</p>
                    <div class="metric-grid">
                        <div class="metric-card">
                            <p class="metric-label">Paso 1</p>
                            <p class="metric-value">Diligencia documento y nombre.</p>
                        </div>
                        <div class="metric-card">
                            <p class="metric-label">Paso 2</p>
                            <p class="metric-value">Carga la foto del colaborador.</p>
                        </div>
                        <div class="metric-card">
                            <p class="metric-label">Paso 3</p>
                            <p class="metric-value">Presiona "Asociar huella".</p>
                        </div>
                        <div class="metric-card">
                            <p class="metric-label">Paso 4</p>
                            <p class="metric-value">Guarda cuando la captura termine.</p>
                        </div>
                    </div>
                </div>
            </details>
        </div>
    </div>

    <script src="js/funciones.js" type="text/javascript"></script>
    <script>
        (function () {
            var observer = new MutationObserver(function () {
                var visible = $("#fingerPrint").css("display") !== "none";
                $("#sensorPlaceholder").toggle(!visible);
            });
            observer.observe(document.getElementById("fingerPrint"), {attributes: true, attributeFilter: ["style", "class"]});
            $("#sensorPlaceholder").toggle($("#fingerPrint").css("display") === "none");
        })();
    </script>
</body>
</html>
