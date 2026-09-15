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
    <link href="Css/estilo.css?v=20260915f" rel="stylesheet" type="text/css" />
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
                        <p class="section-copy">Mostrando <?php echo $total; ?> registros (<?php echo htmlspecialchars($fechaDesde); ?> a <?php echo htmlspecialchars($fechaHasta); ?>). Puede corregir horas en casos de borde y guardar por fila. La descarga incluye todo ese rango.</p>
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
                    <table class="report-table" id="tablaIngresosHuella" data-page-length="25" data-order='[[2,"desc"]]' data-paging="full">
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
                                <tr data-documento="<?php echo htmlspecialchars($row['documento']); ?>" data-fecha="<?php echo htmlspecialchars($fechaIso); ?>">
                                    <td><strong><?php echo htmlspecialchars($row['nombre']); ?></strong></td>
                                    <td class="celda-fija"><?php echo htmlspecialchars($row['documento']); ?></td>
                                    <td class="celda-fija" data-order="<?php echo htmlspecialchars($fechaIso); ?>"><?php echo htmlspecialchars($fechaIso); ?></td>
                                    <td class="celda-hora">
                                        <input class="form-control biometric-input hora-input js-hora-ingreso" type="time" value="<?php echo htmlspecialchars(hora_para_input($row['seg_horaingreso'])); ?>" />
                                    </td>
                                    <td class="celda-hora">
                                        <input class="form-control biometric-input hora-input js-sale-almuerzo" type="time" value="<?php echo htmlspecialchars(hora_para_input($saleAlmuerzo)); ?>" />
                                    </td>
                                    <td class="celda-hora js-celda-almuerzo <?php echo htmlspecialchars($claseAlmuerzo); ?>">
                                        <input class="form-control biometric-input hora-input js-regresa-almuerzo" type="time" value="<?php echo htmlspecialchars(hora_para_input($regresaAlmuerzo)); ?>" />
                                    </td>
                                    <td class="celda-hora">
                                        <input class="form-control biometric-input hora-input js-sale-break" type="time" value="<?php echo htmlspecialchars(hora_para_input($saleBreak)); ?>" />
                                    </td>
                                    <td class="celda-hora js-celda-break <?php echo htmlspecialchars($claseBreak); ?>">
                                        <input class="form-control biometric-input hora-input js-regresa-break" type="time" value="<?php echo htmlspecialchars(hora_para_input($regresaBreak)); ?>" />
                                    </td>
                                    <td class="celda-hora">
                                        <input class="form-control biometric-input hora-input js-hora-salida" type="time" value="<?php echo htmlspecialchars(hora_para_input($row['seg_horaSalida'])); ?>" />
                                    </td>
                                    <td class="celda-fija">
                                        <button class="btn btn-primary rounded-4 px-3 js-guardar-horas" type="button">Guardar</button>
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
    <?php marca_datatable_scripts(); ?>
    <script>
        var MINUTOS_ALMUERZO = <?php echo (int) MINUTOS_ALMUERZO; ?>;
        var MINUTOS_BREAK = <?php echo (int) MINUTOS_BREAK; ?>;

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

        function minutosEntre(inicio, fin) {
            if (!inicio || !fin) {
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
            var minAlmuerzo = minutosEntre($fila.find(".js-sale-almuerzo").val(), $fila.find(".js-regresa-almuerzo").val());
            var minBreak = minutosEntre($fila.find(".js-sale-break").val(), $fila.find(".js-regresa-break").val());
            $fila.find(".js-celda-almuerzo").toggleClass("tiempo-excedido", minAlmuerzo !== null && minAlmuerzo > MINUTOS_ALMUERZO);
            $fila.find(".js-celda-break").toggleClass("tiempo-excedido", minBreak !== null && minBreak > MINUTOS_BREAK);
        }

        function guardarHoras($fila) {
            var boton = $fila.find(".js-guardar-horas");
            boton.prop("disabled", true);
            $.ajax({
                type: "POST",
                url: "Model/ActualizarIngreso.php",
                dataType: "json",
                data: {
                    documento: $fila.data("documento"),
                    fecha: $fila.data("fecha"),
                    seg_horaingreso: $fila.find(".js-hora-ingreso").val() || "",
                    seg_ingresoAlmuerzo: $fila.find(".js-sale-almuerzo").val() || "",
                    seg_salioAlmuerzo: $fila.find(".js-regresa-almuerzo").val() || "",
                    seg_ingresoBreak: $fila.find(".js-sale-break").val() || "",
                    seg_salioBreak: $fila.find(".js-regresa-break").val() || "",
                    seg_horaSalida: $fila.find(".js-hora-salida").val() || ""
                },
                success: function (data) {
                    if (data.success) {
                        marcarExcedidos($fila);
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
                columnDefs: [{ orderable: false, targets: [3, 4, 5, 6, 7, 8, 9] }]
            });
        }

        $(document).on("click", ".js-guardar-horas", function () {
            guardarHoras($(this).closest("tr"));
        });
        $(document).on("change", "#tablaIngresosHuella .hora-input", function () {
            marcarExcedidos($(this).closest("tr"));
        });
    </script>
</body>
</html>
<?php
$con->desconectar();
?>
