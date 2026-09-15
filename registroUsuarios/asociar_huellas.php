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
$nombreSedeActual = $biometricRepository->getSedeNombreById($sede);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="refresh" content="600" />
    <title>Asociar huellas | Monteblanco</title>
    <link rel="shortcut icon" href="imagenes/marca/isotipo.svg" />
    <?php require_once __DIR__ . '/inc/marca.php'; marca_head_assets(); ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="Css/estilo.css?v=20260915e" rel="stylesheet" type="text/css" />
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
                    <div class="col-lg-7">
                        <?php marca_product_badge('Ingreso Usuarios'); ?>
                        <span class="eyebrow">Modulo biometrico</span>
                        <h1 class="page-title">Asociar huellas</h1>
                    </div>
                    <div class="col-lg-5">
                        <?php
                        auth_render_nav('asociar', $token, $sede, array(
                            array(
                                'button' => true,
                                'label' => 'Administrar sedes',
                                'attrs' => 'data-bs-toggle="modal" data-bs-target="#sedesModal"',
                            ),
                        ));
                        ?>
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
                            <div class="col-md-6">
                                <label class="field-label" for="telefono">Telefono</label>
                                <input class="form-control biometric-input" placeholder="Telefono opcional" id="telefono" type="text" />
                            </div>
                            <div class="col-md-6">
                                <label class="field-label" for="sedeSelect">Sede</label>
                                <select class="form-control biometric-input" id="sedeSelect" onchange="cambiarSedeSesion(this.value)">
                                    <option value="">Sin sede</option>
                                    <?php foreach ($listaSedes as $item) { ?>
                                        <option value="<?php echo htmlspecialchars($item['id']); ?>" <?php echo (string) $sede === (string) $item['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($item['nombre']); ?>
                                        </option>
                                    <?php } ?>
                                </select>
                                <p class="helper-text mt-2">El usuario y la huella quedaran asociados a esta sede<?php echo $nombreSedeActual !== '' ? (': ' . htmlspecialchars($nombreSedeActual)) : ''; ?>.</p>
                            </div>
                            <div class="col-md-6">
                                <label class="field-label" for="foto">Fotografia</label>
                                <input class="form-control biometric-input biometric-file" id="foto" type="file" accept="image/png,image/jpeg" />
                                <p class="helper-text mt-2">Carga una foto limpia para mostrarla cuando el usuario sea reconocido por huella.</p>
                            </div>
                        </div>

                        <div class="d-flex flex-column flex-md-row gap-3 pt-2">
                            <button class="btn btn-lg btn-primary flex-fill rounded-4" id="activeSensorLocal" onclick="activarSensor('<?php echo $token; ?>')" type="button">
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
                            <p class="metric-value">Diligencia documento, nombre, sede y foto.</p>
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

    <div class="modal fade" id="sedesModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 rounded-4">
                <div class="modal-header border-0 pb-0">
                    <div>
                        <h2 class="section-title mb-1">Administrar sedes</h2>
                        <p class="section-copy mb-0">Agrega sedes nuevas o elimina sedes que no tengan usuarios asignados.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body pt-3">
                    <div class="row g-3 align-items-end mb-4">
                        <div class="col-12">
                            <label class="field-label" for="nuevaSede">Nueva sede</label>
                            <input class="form-control biometric-input" id="nuevaSede" type="text" placeholder="Nombre de la sede" />
                        </div>
                        <div class="col-12 d-grid">
                            <button class="btn btn-primary rounded-4" id="agregarSede" type="button">Agregar sede</button>
                        </div>
                    </div>
                    <div>
                        <label class="field-label mb-3">Sedes registradas</label>
                        <div id="sedesLista" class="d-grid gap-2"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/funciones.js?v=20260915h" type="text/javascript"></script>
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
            var lista = [];

            if (!sedes || !sedes.length) {
                lista.push('<div class="empty-placeholder">No hay sedes registradas.</div>');
            } else {
                $.each(sedes, function (_, sedeItem) {
                    var selected = sedeActual === String(sedeItem.id) ? " selected" : "";
                    opciones.push('<option value="' + escapeHtml(sedeItem.id) + '"' + selected + '>' + escapeHtml(sedeItem.nombre) + '</option>');
                    lista.push(
                        '<div class="d-flex justify-content-between align-items-center gap-3 border rounded-4 px-3 py-2">' +
                            '<span>' + escapeHtml(sedeItem.nombre) + '</span>' +
                            '<button class="btn btn-sm btn-outline-danger rounded-4 btn-eliminar-sede" type="button" data-id="' + escapeHtml(sedeItem.id) + '" data-nombre="' + escapeHtml(sedeItem.nombre) + '">Borrar</button>' +
                        '</div>'
                    );
                });
            }

            $("#sedeSelect").html(opciones.join(""));
            $("#sedesLista").html(lista.join(""));
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

        $("#agregarSede").on("click", function () {
            $.ajax({
                type: "POST",
                url: "Model/SedesAdmin.php",
                dataType: "json",
                data: { action: "create", nombre: $("#nuevaSede").val() },
                success: function (data) {
                    if (data.success) {
                        $("#nuevaSede").val("");
                        renderSedes(data.sedes || []);
                        showMessageBox(data.message || "Sede creada con exito", "success");
                    } else {
                        showMessageBox(data.message || "No fue posible crear la sede", "warning");
                    }
                },
                error: function () {
                    showMessageBox("No fue posible crear la sede", "danger");
                }
            });
        });

        $(document).on("click", ".btn-eliminar-sede", function () {
            var sedeNombre = $(this).data("nombre");
            if (!confirm('Vas a borrar la sede "' + sedeNombre + '".')) {
                return;
            }
            $.ajax({
                type: "POST",
                url: "Model/SedesAdmin.php",
                dataType: "json",
                data: { action: "delete", sede: $(this).data("id") },
                success: function (data) {
                    if (data.success) {
                        renderSedes(data.sedes || []);
                        showMessageBox(data.message || "Sede eliminada con exito", "success");
                    } else {
                        showMessageBox(data.message || "No fue posible eliminar la sede", "warning");
                    }
                },
                error: function () {
                    showMessageBox("No fue posible eliminar la sede", "danger");
                }
            });
        });

        $("#sedeSelect").data("selected", $("#sedeSelect").val());
        cargarSedes();
    </script>
</body>
</html>
