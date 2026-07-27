<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Token Biométrico | Ingreso Usuarios</title>
    <link rel="shortcut icon" href="imagenes/finger.png" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;700;800&display=swap" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="Css/estilo.css" rel="stylesheet" type="text/css" />
</head>
<body class="biometric-body">
    <div class="biometric-shell d-flex align-items-center">
        <div class="container page-wrap">
            <div class="row justify-content-center">
                <div class="col-12 col-lg-9 col-xl-7">
                    <div class="glass-card section-card p-4 p-md-5">
                        <span class="eyebrow">Preparacion del lector</span>
                        <h1 class="page-title mt-3">Configura el token de este navegador</h1>
                        <p class="page-subtitle mb-4">
                            Esta pantalla prepara la sesión local para que el plugin biométrico identifique correctamente el equipo y pueda intercambiar datos con el sistema.
                        </p>

                        <div id="content" style="display: none;">
                            <div class="token-box mb-4">
                                <div class="metric-label">Token generado</div>
                                <span id="Token" class="token-value"></span>
                            </div>

                            <ol class="note-list mb-4">
                                <li>Copie el token de abajo y péguelo en el plugin biométrico (ID único PC).</li>
                                <li>El token del navegador y el del plugin deben ser <strong>exactamente iguales</strong>.</li>
                                <li>Si ya estaba configurado y dejó de funcionar, abra <code>index.php</code> de nuevo o borre el almacenamiento local del sitio.</li>
                            </ol>

                            <div class="d-flex flex-column flex-md-row gap-3">
                                <a class="btn-soft btn-soft-primary" id="irModulo" href="#">Ir a control de asistencia</a>
                                <a class="btn-soft btn-soft-secondary" href="index.php?sede=<?php echo isset($_GET['sede']) ? $_GET['sede'] : ''; ?>">Refrescar</a>
                                <a class="btn-soft btn-soft-secondary" href="javascript:void(0)">Descargar plugin</a>
                            </div>
                        </div>

                        <div id="loadingState" class="empty-placeholder">
                            Verificando el token local del navegador y preparando la redirección a control de asistencia.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="js/jquery-1.7.2.min.js" type="text/javascript"></script>
    <script src="js/Utils.js" type="text/javascript"></script>
    <script>
        (function () {
            var sedeUrl = "<?php echo isset($_GET['sede']) ? $_GET['sede'] : ''; ?>";
            var sede = (sedeUrl || localStorage.getItem("srnSede") || "").replace(/^\s+|\s+$/g, "");
            if (sede) {
                localStorage.setItem("srnSede", sede);
            }
            var token = localStorage.getItem("srnPc");
            if (!token) {
                saveSrnPc();
                token = localStorage.getItem("srnPc");
                $("#Token").html(token);
                var destino = "verificar.php?token=" + encodeURIComponent(token);
                if (sede) {
                    destino += "&sede=" + encodeURIComponent(sede);
                }
                $("#irModulo").attr("href", destino);
                $("#content").css("display", "block");
                $("#loadingState").hide();
                return;
            }
            var ir = "verificar.php?token=" + encodeURIComponent(token);
            if (sede) {
                ir += "&sede=" + encodeURIComponent(sede);
            }
            window.location.replace(ir);
        })();
    </script>
</body>
</html>
