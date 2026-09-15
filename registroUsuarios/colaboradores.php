<?php
require_once __DIR__ . '/app/bootstrap.php';
require_once __DIR__ . '/inc/token_sesion.php';
require_once __DIR__ . '/inc/auth.php';

use Huella\Core\Database;
use Huella\Repositories\BiometricRepository;

requerir_admin();
list($token, $sede) = requerir_token_sesion();

$configJornada = require __DIR__ . '/config/jornada_laboral.php';
$busqueda = isset($_GET['q']) ? trim((string) $_GET['q']) : '';
$repository = new BiometricRepository(new Database());
$listaSedes = $repository->getHeadquartersList();
$trabajadores = $repository->listCollaborators($sede, $busqueda);

$nombresSede = array();
foreach ($listaSedes as $item) {
    $nombresSede[(string) $item['id']] = $item['nombre'];
}

$diasDescanso = array(
    0 => 'Domingo',
    1 => 'Lunes',
    2 => 'Martes',
    3 => 'Miércoles',
    4 => 'Jueves',
    5 => 'Viernes',
    6 => 'Sábado',
);

function colaborador_hora_input($valor, $fallback)
{
    $valor = trim((string) $valor);
    if ($valor === '') {
        $valor = $fallback;
    }
    return substr($valor, 0, 5);
}

function colaborador_horas_desde_minutos($minutos, $fallbackHoras)
{
    $minutos = (int) $minutos;
    if ($minutos <= 0) {
        return (float) $fallbackHoras;
    }
    return round($minutos / 60, 2);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Colaboradores | Monteblanco</title>
    <link rel="shortcut icon" href="imagenes/marca/isotipo.svg" />
    <?php require_once __DIR__ . '/inc/marca.php'; marca_head_assets(); ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <?php marca_datatable_head(); ?>
    <link href="Css/estilo.css?v=20260915n" rel="stylesheet" type="text/css" />
    <script src="js/Utils.js?v=20260915k" type="text/javascript"></script>
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
                        <span class="eyebrow">Módulo administrativo</span>
                        <h1 class="page-title">Colaboradores</h1>
                        <p class="section-copy mb-0">
                            Consulte y edite datos, sede, horario y si el colaborador puede ingresar por cédula.
                        </p>
                    </div>
                    <div class="col-12 col-xl-7">
                        <?php auth_render_nav('colaboradores', $token, $sede); ?>
                    </div>
                </div>
            </div>

            <div class="glass-card section-card mb-4">
                <form class="row g-3 align-items-end" method="get">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>" />
                    <div class="col-md-4">
                        <label class="field-label" for="sede">Sede</label>
                        <select class="form-control biometric-input" id="sede" name="sede" onchange="guardarSedeSesion(this.value)">
                            <option value="">Todas</option>
                            <?php foreach ($listaSedes as $item) { ?>
                                <option value="<?php echo htmlspecialchars($item['id']); ?>" <?php echo (string) $sede === (string) $item['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($item['nombre']); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="field-label" for="q">Buscar</label>
                        <input class="form-control biometric-input" type="text" id="q" name="q" value="<?php echo htmlspecialchars($busqueda); ?>" placeholder="Documento o nombre" />
                    </div>
                    <div class="col-md-2 d-grid">
                        <button class="btn btn-primary btn-lg rounded-4" type="submit">Filtrar</button>
                    </div>
                </form>
            </div>

            <div class="glass-card section-card">
                <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-4">
                    <div>
                        <h2 class="section-title">Listado</h2>
                        <p class="section-copy mb-0">Mostrando <?php echo count($trabajadores); ?> registros. Use el lápiz para editar al colaborador.</p>
                    </div>
                </div>

                <div class="report-table-shell">
                    <table class="report-table" id="tablaColaboradores" data-page-length="25" data-order='[[0,"asc"]]' data-paging="full">
                        <thead>
                            <tr>
                                <th>Colaborador</th>
                                <th>Sede</th>
                                <th>Horario</th>
                                <th>Ingresa por cédula</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($trabajadores as $trabajador) {
                                $idSede = isset($trabajador['usu_idsede']) ? (string) $trabajador['usu_idsede'] : '';
                                $nombreSede = $idSede !== '' && isset($nombresSede[$idSede]) ? $nombresSede[$idSede] : 'Sin sede';
                                $diaActual = isset($trabajador['usu_dia_descanso']) && $trabajador['usu_dia_descanso'] !== null && $trabajador['usu_dia_descanso'] !== ''
                                    ? (int) $trabajador['usu_dia_descanso']
                                    : (int) $configJornada['dia_descanso'];
                                $horaInicio = colaborador_hora_input(isset($trabajador['usu_hora_inicio']) ? $trabajador['usu_hora_inicio'] : '', $configJornada['hora_inicio']);
                                $horaFin = colaborador_hora_input(isset($trabajador['usu_hora_fin']) ? $trabajador['usu_hora_fin'] : '', $configJornada['hora_fin']);
                                $jornadaDiaria = colaborador_horas_desde_minutos(
                                    isset($trabajador['usu_jornada_diaria_minutos']) ? $trabajador['usu_jornada_diaria_minutos'] : 0,
                                    $configJornada['jornada_diaria_horas']
                                );
                                $jornadaSemanal = colaborador_horas_desde_minutos(
                                    isset($trabajador['usu_jornada_semanal_minutos']) ? $trabajador['usu_jornada_semanal_minutos'] : 0,
                                    $configJornada['jornada_semanal_horas']
                                );
                                $ingresaCedula = !empty($trabajador['ingresa_cedula']);
                                $nombre = $trabajador['usu_nombre'] ? $trabajador['usu_nombre'] : 'Sin nombre';
                                ?>
                                <tr
                                    data-documento="<?php echo htmlspecialchars($trabajador['usu_identificacion']); ?>"
                                    data-nombre="<?php echo htmlspecialchars($nombre); ?>"
                                    data-sede="<?php echo htmlspecialchars($idSede); ?>"
                                    data-hora-inicio="<?php echo htmlspecialchars($horaInicio); ?>"
                                    data-hora-fin="<?php echo htmlspecialchars($horaFin); ?>"
                                    data-jornada-diaria="<?php echo htmlspecialchars((string) $jornadaDiaria); ?>"
                                    data-jornada-semanal="<?php echo htmlspecialchars((string) $jornadaSemanal); ?>"
                                    data-dia-descanso="<?php echo (int) $diaActual; ?>"
                                    data-ingresa-cedula="<?php echo $ingresaCedula ? '1' : '0'; ?>"
                                >
                                    <td class="celda-persona" data-order="<?php echo htmlspecialchars($nombre); ?>">
                                        <strong class="celda-persona-nombre"><?php echo htmlspecialchars($nombre); ?></strong>
                                        <span class="celda-persona-cc">CC <?php echo htmlspecialchars($trabajador['usu_identificacion']); ?></span>
                                    </td>
                                    <td><?php echo htmlspecialchars($nombreSede); ?></td>
                                    <td class="celda-fija"><?php echo htmlspecialchars($horaInicio . ' – ' . $horaFin); ?></td>
                                    <td><?php echo $ingresaCedula ? 'Sí' : 'No'; ?></td>
                                    <td class="celda-fija celda-editar">
                                        <button class="hora-edit-btn js-editar-colaborador" type="button" aria-label="Editar colaborador">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 1 1 3 3L7 19l-4 1 1-4Z"/></svg>
                                        </button>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php marca_footer(); ?>
        </div>
    </div>

    <div class="hora-modal" id="colaboradorModal" aria-hidden="true">
        <div class="hora-modal-backdrop" data-close-colaborador-modal></div>
        <div class="hora-modal-dialog glass-card" role="dialog" aria-modal="true" aria-labelledby="colaboradorModalTitulo">
            <span class="eyebrow">Edición</span>
            <h2 class="section-title mt-2" id="colaboradorModalTitulo">Editar colaborador</h2>
            <p class="section-copy" id="colaboradorModalContexto"></p>
            <form class="form-panel" id="colaboradorModalForm" autocomplete="off" onsubmit="return false;">
                <input type="hidden" id="colab_documento_actual" name="documento_actual" />
                <div class="hora-modal-grid">
                    <div>
                        <label class="field-label" for="colab_nombre">Nombre</label>
                        <input class="form-control biometric-input" id="colab_nombre" name="nombre" type="text" required />
                    </div>
                    <div>
                        <label class="field-label" for="colab_documento">Cédula</label>
                        <input class="form-control biometric-input" id="colab_documento" name="documento" type="text" required />
                    </div>
                    <div>
                        <label class="field-label" for="colab_sede">Sede</label>
                        <select class="form-control biometric-input" id="colab_sede" name="sede">
                            <option value="">Sin sede</option>
                            <?php foreach ($listaSedes as $item) { ?>
                                <option value="<?php echo htmlspecialchars($item['id']); ?>"><?php echo htmlspecialchars($item['nombre']); ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div>
                        <label class="field-label" for="colab_ingresa_cedula">Ingresa por cédula</label>
                        <select class="form-control biometric-input" id="colab_ingresa_cedula" name="ingresa_cedula">
                            <option value="0">No</option>
                            <option value="1">Sí</option>
                        </select>
                    </div>
                    <div>
                        <label class="field-label" for="colab_hora_inicio">Ingreso</label>
                        <input class="form-control biometric-input" id="colab_hora_inicio" name="hora_inicio" type="time" required />
                    </div>
                    <div>
                        <label class="field-label" for="colab_hora_fin">Salida</label>
                        <input class="form-control biometric-input" id="colab_hora_fin" name="hora_fin" type="time" required />
                    </div>
                    <div>
                        <label class="field-label" for="colab_jornada_diaria">Jornada diaria (h)</label>
                        <input class="form-control biometric-input" id="colab_jornada_diaria" name="jornada_diaria" type="number" min="1" max="24" step="0.5" required />
                    </div>
                    <div>
                        <label class="field-label" for="colab_jornada_semanal">Jornada semanal (h)</label>
                        <input class="form-control biometric-input" id="colab_jornada_semanal" name="jornada_semanal" type="number" min="1" max="72" step="0.5" required />
                    </div>
                    <div class="hora-modal-grid-full">
                        <label class="field-label" for="colab_dia_descanso">Día de descanso</label>
                        <select class="form-control biometric-input" id="colab_dia_descanso" name="dia_descanso">
                            <?php foreach ($diasDescanso as $valorDia => $nombreDia) { ?>
                                <option value="<?php echo (int) $valorDia; ?>"><?php echo htmlspecialchars($nombreDia); ?></option>
                            <?php } ?>
                        </select>
                    </div>
                </div>
                <div class="d-flex flex-column flex-sm-row gap-3 pt-1">
                    <button class="btn-soft btn-soft-secondary flex-fill" type="button" data-close-colaborador-modal>Cancelar</button>
                    <button class="btn btn-primary btn-lg rounded-4 flex-fill" id="colaboradorModalGuardar" type="submit">Guardar</button>
                </div>
            </form>
        </div>
    </div>
    <?php marca_datatable_scripts(); ?>
    <script>
        function showMessageBox(mensaje, type) {
            var clas = "";
            switch (type) {
                case "success": clas = "mensaje_success"; break;
                case "danger": clas = "mensaje_danger"; break;
                default: clas = "mensaje_warning";
            }
            $("#mensaje").removeClass("mensaje_success mensaje_danger mensaje_warning").addClass(clas).show();
            $("#txtMensaje").text(mensaje);
            setTimeout(function () { $("#mensaje").fadeOut(); }, 3200);
        }

        function abrirModalColaborador($fila) {
            $("#colab_documento_actual").val($fila.data("documento") || "");
            $("#colab_nombre").val($fila.data("nombre") || "");
            $("#colab_documento").val($fila.data("documento") || "");
            $("#colab_sede").val($fila.data("sede") || "");
            $("#colab_hora_inicio").val($fila.data("hora-inicio") || "");
            $("#colab_hora_fin").val($fila.data("hora-fin") || "");
            $("#colab_jornada_diaria").val($fila.data("jornada-diaria") || "");
            $("#colab_jornada_semanal").val($fila.data("jornada-semanal") || "");
            $("#colab_dia_descanso").val(String($fila.data("dia-descanso")));
            $("#colab_ingresa_cedula").val(String($fila.data("ingresa-cedula")) === "1" ? "1" : "0");
            $("#colaboradorModalContexto").text(($fila.data("nombre") || "") + " · " + ($fila.data("documento") || ""));
            $("#colaboradorModal").addClass("is-open").attr("aria-hidden", "false");
            $("body").addClass("hora-modal-open");
        }

        function cerrarModalColaborador() {
            $("#colaboradorModal").removeClass("is-open").attr("aria-hidden", "true");
            $("body").removeClass("hora-modal-open");
        }

        function guardarColaborador() {
            var boton = $("#colaboradorModalGuardar");
            boton.prop("disabled", true);
            $.ajax({
                type: "POST",
                url: "Model/ColaboradoresAdmin.php",
                dataType: "json",
                data: {
                    documento_actual: $("#colab_documento_actual").val(),
                    documento: $("#colab_documento").val(),
                    nombre: $("#colab_nombre").val(),
                    sede: $("#colab_sede").val(),
                    hora_inicio: $("#colab_hora_inicio").val(),
                    hora_fin: $("#colab_hora_fin").val(),
                    jornada_diaria: $("#colab_jornada_diaria").val(),
                    jornada_semanal: $("#colab_jornada_semanal").val(),
                    dia_descanso: $("#colab_dia_descanso").val(),
                    ingresa_cedula: $("#colab_ingresa_cedula").val()
                },
                success: function (data) {
                    if (data.success) {
                        showMessageBox(data.message || "Colaborador actualizado", "success");
                        cerrarModalColaborador();
                        window.location.reload();
                    } else {
                        showMessageBox(data.message || "No fue posible guardar el colaborador", "warning");
                    }
                },
                error: function (xhr) {
                    var mensaje = "No fue posible guardar el colaborador";
                    try {
                        var respuesta = JSON.parse(xhr.responseText);
                        if (respuesta.message) {
                            mensaje = respuesta.message;
                        }
                    } catch (e) {}
                    showMessageBox(mensaje, "danger");
                },
                complete: function () {
                    boton.prop("disabled", false);
                }
            });
        }

        if (window.MonteblancoTable) {
            MonteblancoTable.init("#tablaColaboradores", {
                columnDefs: [{ orderable: false, targets: [4] }]
            });
        }

        $(document).on("click", ".js-editar-colaborador", function () {
            abrirModalColaborador($(this).closest("tr"));
        });
        $(document).on("click", "[data-close-colaborador-modal]", cerrarModalColaborador);
        $("#colaboradorModalForm").on("submit", function (e) {
            e.preventDefault();
            guardarColaborador();
        });
        $(document).on("keydown", function (e) {
            if (e.key === "Escape") {
                cerrarModalColaborador();
            }
        });
    </script>
</body>
</html>
