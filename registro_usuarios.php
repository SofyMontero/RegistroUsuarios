<?php
require_once __DIR__ . '/app/bootstrap.php';

use Huella\Core\Database;
use Huella\Repositories\BiometricRepository;

$token = isset($_GET['token']) ? $_GET['token'] : '';
$sede = isset($_GET['sede']) ? $_GET['sede'] : '';
$repository = new BiometricRepository(new Database());
$sedes = $repository->getHeadquartersList();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Registro de Usuarios | Bermudas</title>
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
                        <span class="eyebrow">Modulo administrativo</span>
                        <h1 class="page-title">Registro de usuarios</h1>
                        <p class="section-copy mb-0">Esta vista crea el usuario base en el sistema sin asociar huellas ni tocar el flujo biometrico existente.</p>
                    </div>
                    <div class="col-lg-5">
                        <div class="action-stack">
                            <button class="btn-soft btn-soft-primary" type="button" data-bs-toggle="modal" data-bs-target="#sedesModal">
                                Administrar sedes
                            </button>
                            <a class="btn-soft btn-soft-secondary" href="Home.php?token=<?php echo urlencode($token); ?>&sede=<?php echo urlencode($sede); ?>">Regresar</a>
                            <span class="status-pill">Sin huella</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="summary-layout summary-layout-home">
                <div class="glass-card section-card">
                    <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-4">
                        <div>
                            <h2 class="section-title">Datos del usuario</h2>
                            <p class="section-copy">Completa la informacion principal del colaborador y guarda el registro. Este proceso no activa sensor ni crea plantillas biometrica.</p>
                        </div>
                        <div class="token-box">
                            <div class="metric-label mb-1">Token de sesion</div>
                            <span class="token-value"><?php echo htmlspecialchars($token); ?></span>
                        </div>
                    </div>

                    <form class="form-panel" id="registroUsuariosForm" onsubmit="return false;">
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
                                <label class="field-label" for="sede">Sede</label>
                                <select class="form-control biometric-input" id="sede">
                                    <option value="">Selecciona una sede</option>
                                    <?php foreach ($sedes as $item) { ?>
                                        <option value="<?php echo htmlspecialchars($item['id']); ?>" <?php echo (string) $sede === (string) $item['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($item['nombre']); ?>
                                        </option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>

                        <div class="d-flex flex-column flex-md-row gap-3 pt-4">
                            <button class="btn btn-lg btn-primary flex-fill rounded-4" id="guardarUsuario" type="button">
                                Guardar usuario
                            </button>
                            <button class="btn btn-lg btn-outline-primary flex-fill rounded-4" id="limpiarFormulario" type="button">
                                Limpiar formulario
                            </button>
                        </div>
                    </form>
                </div>

                <div class="desktop-panel">
                    <div class="glass-card section-card">
                        <h2 class="section-title">Notas</h2>
                        <div class="metric-grid">
                            <div class="metric-card">
                                <p class="metric-label">Paso 1</p>
                                <p class="metric-value">Digita documento y nombre.</p>
                            </div>
                            <div class="metric-card">
                                <p class="metric-label">Paso 2</p>
                                <p class="metric-value">Selecciona la sede del usuario.</p>
                            </div>
                            <div class="metric-card">
                                <p class="metric-label">Paso 3</p>
                                <p class="metric-value">Agrega telefono si aplica y guarda.</p>
                            </div>
                            <div class="metric-card">
                                <p class="metric-label">Huella</p>
                                <p class="metric-value">Se registra aparte desde el modulo biometrico.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
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
    <script>
        function escapeHtml(texto) {
            return $("<div>").text(texto || "").html();
        }

        function showMessageBox(mensaje, type) {
            var clas = "";
            var icono = "";
            switch (type) {
                case "success":
                    clas = "mensaje_success";
                    icono = "imagenes/success_16.png";
                    break;
                case "warning":
                    clas = "mensaje_warning";
                    icono = "imagenes/warning_16.png";
                    break;
                default:
                    clas = "mensaje_danger";
                    icono = "imagenes/danger_16.png";
                    break;
            }

            $("#mensaje").removeClass("mensaje_success mensaje_warning mensaje_danger").addClass(clas);
            $("#txtMensaje").html(mensaje);
            $("#imageMenssage").attr("src", icono);
            $("#mensaje").fadeIn(5);
            setTimeout(function () {
                $("#mensaje").fadeOut(1500);
            }, 3000);
        }

        function renderSedes(sedes) {
            var opciones = ['<option value="">Selecciona una sede</option>'];
            var lista = [];

            if (!sedes || !sedes.length) {
                lista.push('<div class="empty-placeholder">No hay sedes registradas.</div>');
            } else {
                $.each(sedes, function (_, sedeItem) {
                    var selected = String($("#sede").data("selected") || $("#sede").val()) === String(sedeItem.id) ? " selected" : "";
                    opciones.push('<option value="' + escapeHtml(sedeItem.id) + '"' + selected + '>' + escapeHtml(sedeItem.nombre) + '</option>');
                    lista.push(
                        '<div class="d-flex justify-content-between align-items-center gap-3 border rounded-4 px-3 py-2">' +
                            '<span>' + escapeHtml(sedeItem.nombre) + '</span>' +
                            '<button class="btn btn-sm btn-outline-danger rounded-4 btn-eliminar-sede" type="button" data-id="' + escapeHtml(sedeItem.id) + '" data-nombre="' + escapeHtml(sedeItem.nombre) + '">Borrar</button>' +
                        '</div>'
                    );
                });
            }

            $("#sede").html(opciones.join(""));
            $("#sedesLista").html(lista.join(""));
        }

        function cargarSedes() {
            $.ajax({
                async: true,
                type: "GET",
                url: "Model/SedesAdmin.php",
                dataType: "json",
                data: {
                    action: "list"
                },
                success: function (data) {
                    if (data.success) {
                        renderSedes(data.sedes || []);
                    }
                }
            });
        }

        function limpiarFormulario() {
            $("#documento").val("");
            $("#nombre").val("");
            $("#telefono").val("");
            $("#sede").val("");
            $("#documento").focus();
        }

        function guardarUsuarioBase() {
            $.ajax({
                async: true,
                type: "POST",
                url: "Model/RegistrarUsuario.php",
                dataType: "json",
                data: {
                    documento: $("#documento").val(),
                    nombre: $("#nombre").val(),
                    telefono: $("#telefono").val(),
                    sede: $("#sede").val()
                },
                success: function (data) {
                    var json = (typeof data === "string") ? JSON.parse(data) : data;
                    if (json["filas"] > 0) {
                        showMessageBox(json["message"] || "Usuario registrado con exito", "success");
                        limpiarFormulario();
                    } else {
                        showMessageBox(json["message"] || "No fue posible registrar el usuario", "warning");
                    }
                },
                error: function (xhr) {
                    var mensaje = "No fue posible registrar el usuario";
                    try {
                        var respuesta = JSON.parse(xhr.responseText);
                        if (respuesta.message) {
                            mensaje = respuesta.message;
                        }
                    } catch (e) {}
                    showMessageBox(mensaje, "danger");
                }
            });
        }

        function agregarSede() {
            $.ajax({
                async: true,
                type: "POST",
                url: "Model/SedesAdmin.php",
                dataType: "json",
                data: {
                    action: "create",
                    nombre: $("#nuevaSede").val()
                },
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
        }

        function borrarSede(sedeId, sedeNombre) {
            if (!confirm('Vas a borrar la sede "' + sedeNombre + '".')) {
                return;
            }

            $.ajax({
                async: true,
                type: "POST",
                url: "Model/SedesAdmin.php",
                dataType: "json",
                data: {
                    action: "delete",
                    sede: sedeId
                },
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
        }

        $("#guardarUsuario").on("click", guardarUsuarioBase);
        $("#limpiarFormulario").on("click", limpiarFormulario);
        $("#agregarSede").on("click", agregarSede);
        $("#registroUsuariosForm input").on("keyup", function (e) {
            if (e.keyCode === 13) {
                guardarUsuarioBase();
            }
        });
        $("#nuevaSede").on("keyup", function (e) {
            if (e.keyCode === 13) {
                agregarSede();
            }
        });
        $(document).on("click", ".btn-eliminar-sede", function () {
            borrarSede($(this).data("id"), $(this).data("nombre"));
        });
        $("#sede").data("selected", $("#sede").val());
        cargarSedes();
    </script>
</body>
</html>
