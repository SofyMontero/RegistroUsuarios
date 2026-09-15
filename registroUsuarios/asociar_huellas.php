<?php
require_once __DIR__ . '/app/bootstrap.php';
require_once __DIR__ . '/inc/token_sesion.php';
require_once __DIR__ . '/inc/auth.php';

use Huella\Core\Database;
use Huella\Repositories\BiometricRepository;

$fechaactual = date("Y-m-d");
requerir_admin();
list($token, $sede) = requerir_token_sesion();
$biometricRepository = new BiometricRepository(new Database());
$listaSedes = $biometricRepository->getHeadquartersList();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Asociar huellas | Monteblanco</title>
    <link rel="shortcut icon" href="imagenes/marca/isotipo.svg" />
    <?php require_once __DIR__ . '/inc/marca.php'; marca_head_assets(); ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="Css/estilo.css?v=20260915k" rel="stylesheet" type="text/css" />
    <script src="js/jquery-1.7.2.min.js" type="text/javascript"></script>
    <script src="js/Utils.js?v=20260915e" type="text/javascript"></script>
    <script type="text/javascript">asegurarTokenSesion();</script>
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
                    <div class="col-12 col-xl-5">
                        <?php marca_product_badge('Ingreso Usuarios'); ?>
                        <span class="eyebrow">Modulo biometrico</span>
                        <h1 class="page-title">Asociar huellas</h1>
                    </div>
                    <div class="col-12 col-xl-7">
                        <?php auth_render_nav('asociar', $token, $sede); ?>
                    </div>
                </div>
            </div>

            <div class="summary-layout summary-layout-home">
                <div class="glass-card section-card">
                    <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-4">
                        <div>
                            <h2 class="section-title">Datos del colaborador</h2>
                            <p class="section-copy">Registre al usuario y asocie su huella en un solo proceso: complete los datos, capture la huella y guarde.</p>
                        </div>
                        <div class="token-box">
                            <div class="metric-label mb-1">Token de sesion</div>
                            <span class="token-value"><?php echo htmlspecialchars($token); ?></span>
                        </div>
                    </div>

                    <form class="form-panel" autocomplete="off" onsubmit="return false;">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="field-label" for="documento">Documento</label>
                                <input class="form-control biometric-input" placeholder="Numero de documento" id="documento" type="text" />
                            </div>
                            <div class="col-md-6">
                                <label class="field-label" for="nombre">Nombre completo</label>
                                <input class="form-control biometric-input" placeholder="Nombre del colaborador" id="nombre" type="text" />
                            </div>
                            <div class="col-md-6">
                                <label class="field-label" for="telefono">Telefono</label>
                                <input class="form-control biometric-input" placeholder="Telefono opcional" id="telefono" type="text" />
                            </div>
                            <div class="col-md-6">
                                <label class="field-label" for="sedeSelect">Sede</label>
                                <select class="form-control biometric-input" id="sedeSelect">
                                    <option value="">Sin sede</option>
                                    <?php foreach ($listaSedes as $item) { ?>
                                        <option value="<?php echo htmlspecialchars($item['id']); ?>" <?php echo (string) $sede === (string) $item['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($item['nombre']); ?>
                                        </option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="field-label" for="genero">Género</label>
                                <select class="form-control biometric-input" id="genero" required>
                                    <option value="">Seleccione el género</option>
                                    <option value="Femenino">Femenino</option>
                                    <option value="Masculino">Masculino</option>
                                </select>
                            </div>
                        </div>

                        <div class="d-flex flex-column flex-md-row gap-3 pt-2">
                            <button class="btn btn-lg btn-primary flex-fill rounded-4" id="activeSensorLocal" onclick="activarSensor('<?php echo $token; ?>'); return false;" type="button">
                                Capturar huella
                            </button>
                            <button class="btn btn-lg btn-outline-primary flex-fill rounded-4" id="saveChanges" onclick="addUser('<?php echo $token; ?>')" type="button">
                                Guardar usuario y huella
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
                            <p class="metric-value">Diligencia documento, nombre, sede y género.</p>
                        </div>
                        <div class="metric-card">
                            <p class="metric-label">Paso 2</p>
                            <p class="metric-value">Presiona "Capturar huella" y usa el lector.</p>
                        </div>
                        <div class="metric-card">
                            <p class="metric-label">Paso 3</p>
                            <p class="metric-value">Cuando la captura termine, guarda usuario y huella.</p>
                        </div>
                        <div class="metric-card">
                            <p class="metric-label">Resultado</p>
                            <p class="metric-value">El colaborador queda registrado y listo para ingresar.</p>
                        </div>
                    </div>
                </div>
            </details>
            <?php marca_footer(); ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/funciones.js?v=20260915j" type="text/javascript"></script>
    <script src="js/plugin-ws.js" type="text/javascript"></script>
    <script>
        (function () {
            var observer = new MutationObserver(function () {
                var visible = $("#fingerPrint").css("display") !== "none";
                $("#sensorPlaceholder").toggle(!visible);
            });
            observer.observe(document.getElementById("fingerPrint"), {attributes: true, attributeFilter: ["style", "class"]});
            $("#sensorPlaceholder").toggle($("#fingerPrint").css("display") === "none");
        })();

        function escapeHtml(texto) {
            return $("<div>").text(texto || "").html();
        }

        function renderSedes(sedes) {
            var sedeActual = String($("#sedeSelect").data("selected") || $("#sedeSelect").val() || "");
            var opciones = ['<option value="">Sin sede</option>'];

            if (sedes && sedes.length) {
                $.each(sedes, function (_, sedeItem) {
                    var selected = sedeActual === String(sedeItem.id) ? " selected" : "";
                    opciones.push('<option value="' + escapeHtml(sedeItem.id) + '"' + selected + '>' + escapeHtml(sedeItem.nombre) + '</option>');
                });
            }

            $("#sedeSelect").html(opciones.join(""));
        }

        function cargarSedes() {
            $.ajax({
                type: "GET",
                url: "Model/SedesAdmin.php",
                dataType: "json",
                data: { action: "list" },
                success: function (data) {
                    if (data.success) {
                        renderSedes(data.sedes || []);
                    }
                }
            });
        }

        $("#sedeSelect").data("selected", $("#sedeSelect").val());
        cargarSedes();

        $(document).on("change", "#sedeSelect", function () {
            var nuevaSede = $(this).val() || "";
            $("#sedeSelect").data("selected", nuevaSede);
            if (typeof guardarSedeSesion === "function") {
                guardarSedeSesion(nuevaSede);
            }
            if (typeof history === "undefined" || !history.replaceState) {
                return;
            }
            var params = new URLSearchParams(location.search);
            var token = params.get("token") || (typeof obtenerTokenSesion === "function" ? obtenerTokenSesion() : "");
            if (token) {
                params.set("token", token);
            }
            if (nuevaSede) {
                params.set("sede", nuevaSede);
            } else {
                params.delete("sede");
            }
            var query = params.toString();
            var pagina = location.pathname.split("/").pop() || "asociar_huellas.php";
            history.replaceState(null, "", pagina + (query ? "?" + query : "") + location.hash);
        });
    </script>
</body>
</html>
