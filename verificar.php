<?php
require_once 'Model/bd.php';
set_time_limit(0);
date_default_timezone_set("America/Bogota");

$con = new bd();
$token = isset($_GET["token"]) ? $_GET["token"] : "";
$sede = isset($_GET["sede"]) ? $_GET["sede"] : "";

$estadoActual = "";
if ($sede !== "") {
    $sql1 = "SELECT sed_estactual FROM sedes where idsedes = '" . $sede . "'";
    $rows1 = $con->findAll($sql1);
    if (count($rows1) > 0) {
        $sql2 = "SELECT estado_nombre FROM estados where estado_id = '" . $rows1[0]['sed_estactual'] . "'";
        $rows2 = $con->findAll($sql2);
        if (count($rows2) > 0) {
            $estadoActual = $rows2[0]['estado_nombre'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="refresh" content="600" />
    <title>Ingreso por Huella | Bermudas</title>
    <link rel="shortcut icon" href="images/fondo4.png" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;700;800&display=swap" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="Css/estilo.css" rel="stylesheet" type="text/css" />
    <script src="js/jquery-1.7.2.min.js" type="text/javascript"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/howler/2.2.1/howler.min.js"></script>
    <script type="text/javascript">
        function enviar(valor, sede) {
            var cambio = "nada";
            if (valor == 1) {
                cambio = "INGRESO";
            } else if (valor == 2) {
                cambio = "SALIDA ALMUERZO";
            } else if (valor == 3) {
                cambio = "RETORNO ALMUERZO";
            } else if (valor == 4) {
                cambio = "SALIDA";
            }

            var ruta = "param1=" + valor + "&sede=" + sede;
            $.ajax({
                url: 'cambioEstado.php',
                type: 'get',
                data: ruta
            }).done(function (res) {
                $('#resultado').html(res);
                alert("Cambiaste a: " + cambio);
                document.location.reload();
                return true;
            });
        }

        function regced() {
            var cedula = document.getElementById("cedula").value;
            var ruta = "param1=" + cedula;
            $.ajax({
                url: 'ingresoconcedula.php',
                type: 'get',
                data: ruta,
                dataType: 'json'
            }).done(function (res) {
                if (res.success) {
                    $('#documento').text(res.documento);
                    $('#nombre').text(res.nombre);
                    $('#imageUser').attr('src', 'imagenes/' + res.foto_usu);
                    showMessageBox(res.message + (res.nombre ? ' ' + res.nombre : ''), 'success');

                    var sound = new Howl({
                        src: ['sound/bermu.mp3'],
                        volume: 1.0
                    });
                    sound.play();
                } else {
                    $('#documento').text('');
                    $('#nombre').text('');
                    $('#imageUser').attr('src', 'imagenes/default.png');
                    showMessageBox(res.message, 'warning');
                }

                document.getElementById("cedula").value = "";
            }).fail(function () {
                showMessageBox('No fue posible registrar la cedula manualmente', 'danger');
            });
        }
    </script>
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
                        <span class="eyebrow">Control de asistencia</span>
                        <h1 class="page-title">Ingreso </h1>

                    </div>
                    <div class="col-lg-5">
                        <div class="action-stack">
                            <a class="btn-soft btn-soft-secondary" href="ingresos_huella.php?sede=<?php echo $sede; ?>&token=<?php echo $token; ?>">Ver ingresos</a>
                            <a class="btn-soft btn-soft-secondary" href="Home.php?token=<?php echo $token; ?>&sede=<?php echo $sede; ?>">Regresar</a>
                            <span class="status-pill"><?php echo $estadoActual !== "" ? htmlspecialchars($estadoActual) : "Sensor activo"; ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="glass-card toolbar-card mb-4">
                <div class="toolbar-inline">
                    <div class="input-group input-group-lg">
                        <span class="input-group-text rounded-start-4 border-0 bg-white text-secondary">Cedula</span>
                        <input class="form-control biometric-input rounded-end-4 border-start-0" name="cedula" id="cedula" placeholder="Ingresa documento manualmente" />
                    </div>
                    <button class="btn btn-primary btn-lg rounded-4 px-4" onclick="regced()" type="button">Registrar</button>
                </div>
            </div>

            <div class="summary-layout summary-layout-wide">
                <div class="glass-card section-card">
                    <h2 class="section-title">Resumen del usuario</h2>
                    <p class="section-copy mb-4">Esta columna funciona como tablero rapido para confirmar documento, nombre y contexto de la sesion.</p>
                    <div class="metric-grid">
                        <div class="metric-card">
                            <p class="metric-label">Documento</p>
                            <p class="metric-value" id="documento"></p>
                        </div>
                        <div class="metric-card">
                            <p class="metric-label">Nombre completo</p>
                            <p class="metric-value" id="nombre"></p>
                        </div>
                        <div class="metric-card">
                            <p class="metric-label">Token</p>
                            <p class="metric-value"><?php echo htmlspecialchars($token); ?></p>
                        </div>
                        <div class="metric-card">
                            <p class="metric-label">Sede</p>
                            <p class="metric-value"><?php echo $sede !== "" ? htmlspecialchars($sede) : "No definida"; ?></p>
                        </div>
                    </div>
                </div>

                <div class="glass-card section-card user-photo-card">
                    <div class="w-100">
                        <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                            <div>
                                <h2 class="section-title mb-1">Perfil detectado</h2>
                              
                            </div>
                            <span class="eyebrow">Lectura actual</span>
                        </div>
                        <div class="user-photo-frame">
                            <img id="imageUser" src="imagenes/default.png" alt="Foto del usuario" />
                        </div>
                    </div>
                </div>

                <div class="glass-card section-card scanner-card" id="fingerPrint">
                    <div class="scanner-frame">
                        <img id="<?php echo $token; ?>" src="imagenes/finger.png" alt="Huella detectada" />
                    </div>
                    <div class="scanner-status">
                        <label id="<?php echo $token . "_status"; ?>">Estado del sensor: Inactivo</label>
                        <textarea id="<?php echo $token . "_texto"; ?>" readonly>---</textarea>
                    </div>
                    <div class="d-flex gap-2 flex-wrap justify-content-center">
                        <span class="status-pill"><?php echo $estadoActual !== "" ? htmlspecialchars($estadoActual) : "Escuchando"; ?></span>
                        <span class="eyebrow">Modo escritorio</span>
                    </div>
                </div>
            </div>
        </div>

        <div id="resultado" style="display:none;"></div>
    </div>

    <script src="js/funciones.js" type="text/javascript"></script>
    <script>
        cargar_push(<?php echo $sede !== "" ? (int) $sede : 0; ?>);

        var elem = document.getElementById("cedula");
        elem.onkeyup = function (e) {
            if (e.keyCode == 13) {
                regced();
            }
        };
    </script>
</body>
</html>
<?php
$con->desconectar();
?>
