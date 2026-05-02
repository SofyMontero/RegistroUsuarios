<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Token Biométrico | Bermudas</title>
    <link rel="shortcut icon" href="images/fondo4.png" />
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
                                <li>Si es la primera vez que abres este módulo en el navegador, configura el token dentro del plugin biométrico.</li>
                                <li>Si ya estaba configurado y dejó de funcionar, es posible que otra aplicación lo haya eliminado y debas registrarlo de nuevo.</li>
                            </ol>

                            <div class="d-flex flex-column flex-md-row gap-3">
                                <a class="btn-soft btn-soft-primary" href="index.php?sede=<?php echo isset($_GET['sede']) ? $_GET['sede'] : ''; ?>">Refrescar</a>
                                <a class="btn-soft btn-soft-secondary" href="javascript:void(0)">Descargar plugin</a>
                            </div>
                        </div>

                        <div id="loadingState" class="empty-placeholder">
                            Verificando el token local del navegador y preparando la redirección al módulo principal.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="js/jquery-1.7.2.min.js" type="text/javascript"></script>
    <script src="js/Utils.js" type="text/javascript"></script>
    <script>
        if (localStorage.getItem("srnPc")) {
            window.location = "Home.php?token=" + localStorage.getItem("srnPc") + "&sede=<?php echo isset($_GET['sede']) ? $_GET['sede'] : ''; ?>";
        } else {
            saveSrnPc();
            $("#Token").html(localStorage.getItem("srnPc"));
            $("#content").css("display", "block");
            $("#loadingState").hide();
        }
    </script>
</body>
</html>
