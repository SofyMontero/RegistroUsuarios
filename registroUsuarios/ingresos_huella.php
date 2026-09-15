<?php
require_once 'Model/bd.php';
require_once __DIR__ . '/app/bootstrap.php';
require_once __DIR__ . '/inc/token_sesion.php';
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/tiempo_asistencia.php';
set_time_limit(0);
date_default_timezone_set("America/Bogota");

use Huella\Core\Database;
use Huella\Repositories\BiometricRepository;
use Huella\Services\AttendanceExcelExport;
use Huella\Services\LaborHoursStats;

requerir_admin();

$con = new bd();
list($token, $sede) = requerir_token_sesion();
$biometricRepository = new BiometricRepository(new Database());
$listaSedes = $biometricRepository->getHeadquartersList();
$nombreSedeActual = $biometricRepository->getSedeNombreById($sede);
$primerDiaMes = date('Y-m-01');
$ultimoDiaMes = date('Y-m-t');
$fechaDesde = isset($_GET['fecha_desde']) && $_GET['fecha_desde'] !== '' ? $_GET['fecha_desde'] : $primerDiaMes;
$fechaHasta = isset($_GET['fecha_hasta']) && $_GET['fecha_hasta'] !== '' ? $_GET['fecha_hasta'] : $ultimoDiaMes;
$busqueda = isset($_GET['q']) ? trim($_GET['q']) : '';

$biometricRepository->ensureBreakColumns();
$biometricRepository->ensureTodayAttendanceRowsForActiveUsers(date('Y-m-d'));

$where = array(
    "s.seg_fechaingreso >= '" . addslashes($fechaDesde) . "'",
    "s.seg_fechaingreso <= '" . addslashes($fechaHasta) . "'",
);

if ($busqueda !== '') {
    $busquedaSql = addslashes($busqueda);
    $where[] = "(s.seg_iduser LIKE '%{$busquedaSql}%' OR u.usu_nombre LIKE '%{$busquedaSql}%')";
}

if ($sede !== '') {
    $sedeSql = addslashes($sede);
    $where[] = "u.usu_idsede = '{$sedeSql}'";
}

$where[] = "(
    s.seg_horaingreso > '00:00:00'
    OR s.seg_ingresoAlmuerzo > '00:00:00'
    OR s.seg_salioAlmuerzo > '00:00:00'
    OR IFNULL(s.seg_ingresoBreak, '00:00:00') > '00:00:00'
    OR IFNULL(s.seg_salioBreak, '00:00:00') > '00:00:00'
    OR s.seg_horaSalida > '00:00:00'
)";

$sql = "
    SELECT
        s.seg_iduser AS documento,
        COALESCE(u.usu_nombre, 'Sin nombre') AS nombre,
        s.seg_fechaingreso,
        s.seg_horaingreso,
        s.seg_ingresoAlmuerzo,
        s.seg_salioAlmuerzo,
        s.seg_ingresoBreak,
        s.seg_salioBreak,
        s.seg_horaSalida
    FROM seguimientousers s
    LEFT JOIN usuarios u ON u.usu_identificacion = s.seg_iduser
    WHERE " . implode(' AND ', $where) . "
    ORDER BY s.seg_fechaingreso DESC, s.seg_horaingreso DESC
";

$rows = $con->findAll($sql);
$total = count($rows);

if (isset($_GET['export']) && $_GET['export'] === 'xlsx') {
    $configJornada = require __DIR__ . '/config/jornada_laboral.php';
    $export = new AttendanceExcelExport($biometricRepository, $configJornada);
    $export->download($rows, $fechaDesde, $fechaHasta);
    $con->desconectar();
    exit;
}

$queryExport = $_GET;
unset($queryExport['ver']);
$queryExport['export'] = 'xlsx';
$urlExportExcel = 'ingresos_huella.php?' . http_build_query($queryExport);

$verEstadisticas = isset($_GET['ver']) && $_GET['ver'] === 'estadisticas';
$estadisticas = null;
if ($verEstadisticas) {
    $configJornada = require __DIR__ . '/config/jornada_laboral.php';
    $estadisticas = (new LaborHoursStats())->buildFromRows($biometricRepository, $configJornada, $rows);
}

$queryEstadisticas = $_GET;
unset($queryEstadisticas['export']);
$queryEstadisticas['ver'] = 'estadisticas';
$urlEstadisticas = 'ingresos_huella.php?' . http_build_query($queryEstadisticas) . '#estadisticasHoras';

$queryHistorial = $_GET;
unset($queryHistorial['ver'], $queryHistorial['export']);
$urlHistorial = 'ingresos_huella.php?' . http_build_query($queryHistorial);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Historial de ingresos | Monteblanco</title>
    <link rel="shortcut icon" href="imagenes/marca/isotipo.svg" />
    <?php require_once __DIR__ . '/inc/marca.php'; marca_head_assets(); ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <?php marca_datatable_head(); ?>
    <link href="Css/estilo.css?v=20260915h" rel="stylesheet" type="text/css" />
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
                        <span class="eyebrow">Reporte biometrico</span>
                        <h1 class="page-title">Historial de ingresos</h1>
                        <p class="section-copy mb-0">
                            <?php echo $nombreSedeActual !== '' ? ('Sede: ' . htmlspecialchars($nombreSedeActual)) : 'Todas las sedes'; ?>
                        </p>
                    </div>
                    <div class="col-lg-5">
                        <?php auth_render_nav('ingresos', $token, $sede); ?>
                    </div>
                </div>
            </div>

            <div class="glass-card section-card mb-4">
                <form class="row g-3 align-items-end" method="get">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>" />
                    <?php if ($verEstadisticas) { ?>
                        <input type="hidden" name="ver" value="estadisticas" />
                    <?php } ?>
                    <div class="col-md-3">
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
                    <div class="col-md-2">
                        <label class="field-label" for="fecha_desde">Desde</label>
                        <input class="form-control biometric-input" type="date" id="fecha_desde" name="fecha_desde" value="<?php echo htmlspecialchars($fechaDesde); ?>" />
                    </div>
                    <div class="col-md-2">
                        <label class="field-label" for="fecha_hasta">Hasta</label>
                        <input class="form-control biometric-input" type="date" id="fecha_hasta" name="fecha_hasta" value="<?php echo htmlspecialchars($fechaHasta); ?>" />
                    </div>
                    <div class="col-md-3">
                        <label class="field-label" for="q">Buscar</label>
                        <input class="form-control biometric-input" type="text" id="q" name="q" value="<?php echo htmlspecialchars($busqueda); ?>" placeholder="Documento o nombre" />
                    </div>
                    <div class="col-md-2 d-grid">
                        <button class="btn btn-primary btn-lg rounded-4" type="submit">Filtrar</button>
                    </div>
                </form>
            </div>

            <?php if ($verEstadisticas && $estadisticas !== null) { ?>
            <div class="glass-card section-card mb-4" id="estadisticasHoras">
                <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-4">
                    <div>
                        <h2 class="section-title">Estadísticas de horas</h2>
                        <p class="section-copy">Resumen del período consultado (<?php echo htmlspecialchars($fechaDesde); ?> a <?php echo htmlspecialchars($fechaHasta); ?>). Totales generales, sin desglose por trabajador.</p>
                    </div>
                    <a class="btn btn-outline-secondary rounded-4 px-4 align-self-start" href="<?php echo htmlspecialchars($urlHistorial); ?>">Ocultar estadísticas</a>
                </div>
                <?php require __DIR__ . '/inc/vista_estadisticas_horas.php'; ?>
            </div>
            <?php } ?>

            <div class="glass-card section-card">
                <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-4">
                    <div>
                        <h2 class="section-title">Resultados</h2>
                        <p class="section-copy">Mostrando <?php echo $total; ?> registros (<?php echo htmlspecialchars($fechaDesde); ?> a <?php echo htmlspecialchars($fechaHasta); ?>). Use el icono de editar para corregir las horas en casos de borde. La descarga incluye todo ese rango.</p>
                        <div class="tiempo-leyenda mt-2">
                            <span class="tiempo-leyenda-item">
                                <span></span>
                                Rojo: almuerzo &gt; <?php echo (int) MINUTOS_ALMUERZO; ?> min o break &gt; <?php echo (int) MINUTOS_BREAK; ?> min
                            </span>
                        </div>
                    </div>
                    <div class="d-flex flex-wrap gap-2 align-items-center">
                        <div class="status-pill"><?php echo $fechaDesde; ?> a <?php echo $fechaHasta; ?></div>
                        <?php if (!$verEstadisticas) { ?>
                            <a class="btn btn-primary rounded-4 px-4" id="btnVerEstadisticas" href="<?php echo htmlspecialchars($urlEstadisticas); ?>">Ver estadísticas</a>
                        <?php } ?>
                        <a class="btn btn-success rounded-4 px-4" id="btnExportarExcel" href="<?php echo htmlspecialchars($urlExportExcel); ?>">Descargar Excel</a>
                    </div>
                </div>

                <div class="report-table-shell">
                    <table class="report-table" id="tablaIngresosHuella" data-page-length="25" data-order='[[2,"desc"],[3,"desc"]]' data-paging="full">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Documento</th>
                                <th>Fecha</th>
                                <th>Ingreso</th>
                                <th>Sale almuerzo</th>
                                <th>Regresa almuerzo</th>
                                <th>Sale break</th>
                                <th>Regresa break</th>
                                <th>Salida</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rows as $row) {
                                $fechaIso = formatear_fecha_asistencia($row['seg_fechaingreso']);
                                $saleAlmuerzo = isset($row['seg_ingresoAlmuerzo']) ? $row['seg_ingresoAlmuerzo'] : '';
                                $regresaAlmuerzo = isset($row['seg_salioAlmuerzo']) ? $row['seg_salioAlmuerzo'] : '';
                                $saleBreak = isset($row['seg_ingresoBreak']) ? $row['seg_ingresoBreak'] : '';
                                $regresaBreak = isset($row['seg_salioBreak']) ? $row['seg_salioBreak'] : '';
                                $claseAlmuerzo = clase_celda_tiempo_excedido($saleAlmuerzo, $regresaAlmuerzo, MINUTOS_ALMUERZO);
                                $claseBreak = clase_celda_tiempo_excedido($saleBreak, $regresaBreak, MINUTOS_BREAK);
                                ?>
                                <tr data-documento="<?php echo htmlspecialchars($row['documento']); ?>" data-fecha="<?php echo htmlspecialchars($fechaIso); ?>" data-nombre="<?php echo htmlspecialchars($row['nombre']); ?>">
                                    <td><strong><?php echo htmlspecialchars($row['nombre']); ?></strong></td>
                                    <td class="celda-fija"><?php echo htmlspecialchars($row['documento']); ?></td>
                                    <td class="celda-fija" data-order="<?php echo htmlspecialchars($fechaIso); ?>"><?php echo htmlspecialchars($fechaIso); ?></td>
                                    <?php echo html_celda_hora($row['seg_horaingreso'], 'seg_horaingreso'); ?>
                                    <?php echo html_celda_hora($saleAlmuerzo, 'seg_ingresoAlmuerzo'); ?>
                                    <?php echo html_celda_hora($regresaAlmuerzo, 'seg_salioAlmuerzo', 'js-celda-almuerzo ' . $claseAlmuerzo); ?>
                                    <?php echo html_celda_hora($saleBreak, 'seg_ingresoBreak'); ?>
                                    <?php echo html_celda_hora($regresaBreak, 'seg_salioBreak', 'js-celda-break ' . $claseBreak); ?>
                                    <?php echo html_celda_hora($row['seg_horaSalida'], 'seg_horaSalida'); ?>
                                    <td class="celda-fija celda-editar">
                                        <button class="hora-edit-btn js-editar-horas" type="button" aria-label="Editar horas">
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

    <div class="hora-modal" id="horaModal" aria-hidden="true">
        <div class="hora-modal-backdrop" data-close-hora-modal></div>
        <div class="hora-modal-dialog glass-card" role="dialog" aria-modal="true" aria-labelledby="horaModalTitulo">
            <span class="eyebrow">Corrección</span>
            <h2 class="section-title mt-2" id="horaModalTitulo">Editar horas</h2>
            <p class="section-copy" id="horaModalContexto"></p>
            <form class="form-panel" id="horaModalForm" onsubmit="return false;">
                <div class="hora-modal-grid">
                    <div>
                        <label class="field-label" for="hora_seg_horaingreso">Ingreso</label>
                        <input class="form-control biometric-input hora-modal-time" id="hora_seg_horaingreso" name="seg_horaingreso" type="text" inputmode="numeric" maxlength="8" placeholder="00:00:00" autocomplete="off" />
                    </div>
                    <div>
                        <label class="field-label" for="hora_seg_horaSalida">Salida</label>
                        <input class="form-control biometric-input hora-modal-time" id="hora_seg_horaSalida" name="seg_horaSalida" type="text" inputmode="numeric" maxlength="8" placeholder="00:00:00" autocomplete="off" />
                    </div>
                    <div>
                        <label class="field-label" for="hora_seg_ingresoAlmuerzo">Sale almuerzo</label>
                        <input class="form-control biometric-input hora-modal-time" id="hora_seg_ingresoAlmuerzo" name="seg_ingresoAlmuerzo" type="text" inputmode="numeric" maxlength="8" placeholder="00:00:00" autocomplete="off" />
                    </div>
                    <div>
                        <label class="field-label" for="hora_seg_salioAlmuerzo">Regresa almuerzo</label>
                        <input class="form-control biometric-input hora-modal-time" id="hora_seg_salioAlmuerzo" name="seg_salioAlmuerzo" type="text" inputmode="numeric" maxlength="8" placeholder="00:00:00" autocomplete="off" />
                    </div>
                    <div>
                        <label class="field-label" for="hora_seg_ingresoBreak">Sale break</label>
                        <input class="form-control biometric-input hora-modal-time" id="hora_seg_ingresoBreak" name="seg_ingresoBreak" type="text" inputmode="numeric" maxlength="8" placeholder="00:00:00" autocomplete="off" />
                    </div>
                    <div>
                        <label class="field-label" for="hora_seg_salioBreak">Regresa break</label>
                        <input class="form-control biometric-input hora-modal-time" id="hora_seg_salioBreak" name="seg_salioBreak" type="text" inputmode="numeric" maxlength="8" placeholder="00:00:00" autocomplete="off" />
                    </div>
                </div>
                <p class="helper-text mb-0">Use el formato 00:00:00. Ese mismo valor deja la marcación vacía.</p>
                <div class="d-flex flex-column flex-sm-row gap-3 pt-1">
                    <button class="btn-soft btn-soft-secondary flex-fill" type="button" data-close-hora-modal>Cancelar</button>
                    <button class="btn btn-primary btn-lg rounded-4 flex-fill" id="horaModalGuardar" type="submit">Guardar</button>
                </div>
            </form>
        </div>
    </div>
    <?php marca_datatable_scripts(); ?>
    <script>
        var MINUTOS_ALMUERZO = <?php echo (int) MINUTOS_ALMUERZO; ?>;
        var MINUTOS_BREAK = <?php echo (int) MINUTOS_BREAK; ?>;
        var CAMPOS_HORA = [
            "seg_horaingreso",
            "seg_ingresoAlmuerzo",
            "seg_salioAlmuerzo",
            "seg_ingresoBreak",
            "seg_salioBreak",
            "seg_horaSalida"
        ];
        var horaEdicion = null;

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

        function horaVacia(hora) {
            hora = String(hora || "").replace(/^\s+|\s+$/g, "");
            return hora === "" || hora.indexOf("00:00:00") === 0;
        }

        function minutosEntre(inicio, fin) {
            if (horaVacia(inicio) || horaVacia(fin)) {
                return null;
            }
            var a = String(inicio).split(":");
            var b = String(fin).split(":");
            if (a.length < 2 || b.length < 2) {
                return null;
            }
            return ((+b[0] * 60) + (+b[1])) - ((+a[0] * 60) + (+a[1]));
        }

        function marcarExcedidos($fila) {
            var minAlmuerzo = minutosEntre(
                $fila.find('[data-campo="seg_ingresoAlmuerzo"]').attr("data-hora"),
                $fila.find('[data-campo="seg_salioAlmuerzo"]').attr("data-hora")
            );
            var minBreak = minutosEntre(
                $fila.find('[data-campo="seg_ingresoBreak"]').attr("data-hora"),
                $fila.find('[data-campo="seg_salioBreak"]').attr("data-hora")
            );
            $fila.find(".js-celda-almuerzo").toggleClass("tiempo-excedido", minAlmuerzo !== null && minAlmuerzo > MINUTOS_ALMUERZO);
            $fila.find(".js-celda-break").toggleClass("tiempo-excedido", minBreak !== null && minBreak > MINUTOS_BREAK);
        }

        function abrirModalHora($boton) {
            var $fila = $boton.closest("tr");
            horaEdicion = { $fila: $fila };
            $("#horaModalContexto").text(
                ($fila.data("nombre") || "") + " · " + $fila.data("documento") + " · " + $fila.data("fecha")
            );
            CAMPOS_HORA.forEach(function (campo) {
                $("#hora_" + campo).val($fila.find('[data-campo="' + campo + '"]').attr("data-hora") || "00:00:00");
            });
            $("#horaModal").addClass("is-open").attr("aria-hidden", "false");
            $("body").addClass("hora-modal-open");
            setTimeout(function () {
                $("#hora_seg_horaingreso").trigger("focus").trigger("select");
            }, 40);
        }

        function cerrarModalHora() {
            horaEdicion = null;
            $("#horaModal").removeClass("is-open").attr("aria-hidden", "true");
            $("body").removeClass("hora-modal-open");
        }

        function guardarHoraModal() {
            if (!horaEdicion) {
                return;
            }
            var boton = $("#horaModalGuardar");
            var payload = {
                documento: horaEdicion.$fila.data("documento"),
                fecha: horaEdicion.$fila.data("fecha")
            };
            CAMPOS_HORA.forEach(function (campo) {
                payload[campo] = $("#hora_" + campo).val();
            });
            boton.prop("disabled", true);
            $.ajax({
                type: "POST",
                url: "Model/ActualizarIngreso.php",
                dataType: "json",
                data: payload,
                success: function (data) {
                    if (data.success) {
                        CAMPOS_HORA.forEach(function (campo) {
                            var $celda = horaEdicion.$fila.find('[data-campo="' + campo + '"]');
                            $celda.attr("data-hora", data.horas[campo]);
                            $celda.find(".hora-celda-valor").text(data.horas_texto[campo]);
                        });
                        marcarExcedidos(horaEdicion.$fila);
                        cerrarModalHora();
                        showMessageBox(data.message || "Horas actualizadas", "success");
                    } else {
                        showMessageBox(data.message || "No fue posible guardar las horas", "warning");
                    }
                },
                error: function (xhr) {
                    var mensaje = "No fue posible guardar las horas";
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
            MonteblancoTable.init("#tablaIngresosHuella", {
                columnDefs: [{ orderable: false, targets: [9] }]
            });
        }

        $(document).on("click", ".js-editar-horas", function (event) {
            event.preventDefault();
            event.stopPropagation();
            abrirModalHora($(this));
        });
        $(document).on("click", "[data-close-hora-modal]", function () {
            cerrarModalHora();
        });
        $(document).on("submit", "#horaModalForm", function (event) {
            event.preventDefault();
            guardarHoraModal();
        });
        $(document).on("keydown", function (event) {
            if (event.key === "Escape" && $("#horaModal").hasClass("is-open")) {
                cerrarModalHora();
            }
        });
    </script>
</body>
</html>
<?php
$con->desconectar();
?>
