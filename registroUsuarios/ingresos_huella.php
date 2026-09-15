<?php
require_once 'Model/bd.php';
require_once __DIR__ . '/app/bootstrap.php';
require_once __DIR__ . '/inc/token_sesion.php';
require_once __DIR__ . '/inc/tiempo_asistencia.php';
set_time_limit(0);
date_default_timezone_set("America/Bogota");

use Huella\Core\Database;
use Huella\Repositories\BiometricRepository;
use Huella\Services\AttendanceExcelExport;
use Huella\Services\LaborHoursStats;

function consultar_ingresos_periodo($con, $fechaDesde, $fechaHasta, $sede, $busqueda)
{
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

    return $con->findAll($sql);
}

function periodo_anterior_igual($fechaDesde, $fechaHasta)
{
    $desdeTs = strtotime($fechaDesde);
    $hastaTs = strtotime($fechaHasta);
    if ($desdeTs === false || $hastaTs === false || $hastaTs < $desdeTs) {
        return null;
    }

    $esMesCompleto = date('Y-m-d', $desdeTs) === date('Y-m-01', $desdeTs)
        && date('Y-m-d', $hastaTs) === date('Y-m-t', $desdeTs);
    if ($esMesCompleto) {
        $prevRef = strtotime(date('Y-m-01', $desdeTs) . ' -1 month');

        return array(date('Y-m-01', $prevRef), date('Y-m-t', $prevRef));
    }

    $dias = (int) floor(($hastaTs - $desdeTs) / 86400) + 1;
    $prevHasta = date('Y-m-d', strtotime($fechaDesde . ' -1 day'));
    $prevDesde = date('Y-m-d', strtotime($prevHasta . ' -' . ($dias - 1) . ' days'));

    return array($prevDesde, $prevHasta);
}

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
$verEstadisticas = isset($_GET['ver']) && $_GET['ver'] === 'estadisticas';

$biometricRepository->ensureBreakColumns();
$biometricRepository->ensureTodayAttendanceRowsForActiveUsers(date('Y-m-d'));

$rows = consultar_ingresos_periodo($con, $fechaDesde, $fechaHasta, $sede, $busqueda);
$total = count($rows);

if (isset($_GET['export']) && $_GET['export'] === 'xlsx') {
    $configJornada = require __DIR__ . '/config/jornada_laboral.php';
    $export = new AttendanceExcelExport($biometricRepository, $configJornada);
    $export->download($rows, $fechaDesde, $fechaHasta);
    $con->desconectar();
    exit;
}

$estadisticas = null;
$comparacion = null;
$variaciones = null;
$fechaPrevDesde = '';
$fechaPrevHasta = '';

if ($verEstadisticas) {
    $configJornada = require __DIR__ . '/config/jornada_laboral.php';
    $calculadora = new LaborHoursStats();
    $estadisticas = $calculadora->buildFromRows($biometricRepository, $configJornada, $rows);
    $periodoPrev = periodo_anterior_igual($fechaDesde, $fechaHasta);
    if ($periodoPrev) {
        $fechaPrevDesde = $periodoPrev[0];
        $fechaPrevHasta = $periodoPrev[1];
        $rowsPrev = consultar_ingresos_periodo($con, $fechaPrevDesde, $fechaPrevHasta, $sede, $busqueda);
        if (!empty($rowsPrev)) {
            $comparacion = $calculadora->buildFromRows($biometricRepository, $configJornada, $rowsPrev);
            $variaciones = $calculadora->comparar($estadisticas, $comparacion);
        }
    }
}

$queryBase = $_GET;
unset($queryBase['export']);
$queryExport = $queryBase;
$queryExport['export'] = 'xlsx';
unset($queryExport['ver']);
$urlExportExcel = 'ingresos_huella.php?' . http_build_query($queryExport);

$queryStats = $queryBase;
$queryStats['ver'] = 'estadisticas';
$urlEstadisticas = 'ingresos_huella.php?' . http_build_query($queryStats);

$queryHistorial = $queryBase;
unset($queryHistorial['ver']);
$urlHistorial = 'ingresos_huella.php?' . http_build_query($queryHistorial);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo $verEstadisticas ? 'Estadísticas de horas' : 'Historial de ingresos'; ?> | Monteblanco</title>
    <link rel="shortcut icon" href="imagenes/marca/isotipo.svg" />
    <?php require_once __DIR__ . '/inc/marca.php'; marca_head_assets(); ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <?php if (!$verEstadisticas) { ?>
        <link href="https://cdn.datatables.net/2.0.8/css/dataTables.bootstrap5.css" rel="stylesheet" />
    <?php } ?>
    <link href="Css/estilo.css?v=20260915a" rel="stylesheet" type="text/css" />
    <script src="js/Utils.js" type="text/javascript"></script>
    <script type="text/javascript">asegurarTokenSesion();</script>
</head>
<body class="biometric-body">
    <div class="biometric-shell">
        <div class="container page-wrap">
            <div class="glass-card topbar-card mb-4">
                <div class="row g-4 align-items-center">
                    <div class="col-lg-7">
                        <?php marca_product_badge('Ingreso Usuarios'); ?>
                        <span class="eyebrow">Reporte biometrico</span>
                        <h1 class="page-title"><?php echo $verEstadisticas ? 'Estadísticas de horas' : 'Historial de ingresos'; ?></h1>
                        <p class="section-copy mb-0">
                            <?php echo $nombreSedeActual !== '' ? ('Sede: ' . htmlspecialchars($nombreSedeActual)) : 'Todas las sedes'; ?>
                        </p>
                    </div>
                    <div class="col-lg-5">
                        <div class="action-stack">
                            <a class="btn-soft btn-soft-primary" href="verificar.php?token=<?php echo urlencode($token); ?>&sede=<?php echo urlencode($sede); ?>">Volver a ingreso</a>
                        </div>
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

            <div class="glass-card section-card">
                <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-4">
                    <div>
                        <h2 class="section-title"><?php echo $verEstadisticas ? 'Resumen del período' : 'Resultados'; ?></h2>
                        <p class="section-copy">
                            <?php if ($verEstadisticas) { ?>
                                Totales generales de <?php echo htmlspecialchars($fechaDesde); ?> a <?php echo htmlspecialchars($fechaHasta); ?>. Sin desglose por trabajador.
                            <?php } else { ?>
                                Mostrando <?php echo $total; ?> registros (<?php echo htmlspecialchars($fechaDesde); ?> a <?php echo htmlspecialchars($fechaHasta); ?>). La descarga incluye todo ese rango.
                            <?php } ?>
                        </p>
                        <?php if (!$verEstadisticas) { ?>
                            <div class="tiempo-leyenda mt-2">
                                <span class="tiempo-leyenda-item">
                                    <span></span>
                                    Rojo: almuerzo &gt; <?php echo (int) MINUTOS_ALMUERZO; ?> min o break &gt; <?php echo (int) MINUTOS_BREAK; ?> min
                                </span>
                            </div>
                        <?php } ?>
                    </div>
                    <div class="d-flex flex-wrap gap-2 align-items-center">
                        <div class="status-pill"><?php echo $fechaDesde; ?> a <?php echo $fechaHasta; ?></div>
                        <?php if ($verEstadisticas) { ?>
                            <a class="btn btn-outline-secondary rounded-4 px-4" href="<?php echo htmlspecialchars($urlHistorial); ?>">Volver al historial</a>
                        <?php } else { ?>
                            <a class="btn btn-outline-primary rounded-4 px-4" id="btnVerEstadisticas" href="<?php echo htmlspecialchars($urlEstadisticas); ?>">Ver estadísticas</a>
                        <?php } ?>
                        <a class="btn btn-success rounded-4 px-4" id="btnExportarExcel" href="<?php echo htmlspecialchars($urlExportExcel); ?>">Descargar Excel</a>
                    </div>
                </div>

                <?php if ($verEstadisticas) {
                    require __DIR__ . '/inc/vista_estadisticas_horas.php';
                } else { ?>
                <div class="report-table-wrap">
                    <table class="report-table" id="tablaIngresosHuella">
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
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($total === 0) { ?>
                                <tr>
                                    <td colspan="9">
                                        <div class="empty-placeholder">No hay ingresos por huella para los filtros seleccionados.</div>
                                    </td>
                                </tr>
                            <?php } ?>
                            <?php foreach ($rows as $row) {
                                $saleAlmuerzo = isset($row['seg_ingresoAlmuerzo']) ? $row['seg_ingresoAlmuerzo'] : '';
                                $regresaAlmuerzo = isset($row['seg_salioAlmuerzo']) ? $row['seg_salioAlmuerzo'] : '';
                                $saleBreak = isset($row['seg_ingresoBreak']) ? $row['seg_ingresoBreak'] : '';
                                $regresaBreak = isset($row['seg_salioBreak']) ? $row['seg_salioBreak'] : '';
                                $claseAlmuerzo = clase_celda_tiempo_excedido($saleAlmuerzo, $regresaAlmuerzo, MINUTOS_ALMUERZO);
                                $claseBreak = clase_celda_tiempo_excedido($saleBreak, $regresaBreak, MINUTOS_BREAK);
                                ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($row['nombre']); ?></strong></td>
                                    <td class="celda-fija"><?php echo htmlspecialchars($row['documento']); ?></td>
                                    <td class="celda-fija"><?php echo htmlspecialchars(formatear_fecha_asistencia($row['seg_fechaingreso'])); ?></td>
                                    <td class="celda-fija"><?php echo htmlspecialchars(formatear_hora_asistencia($row['seg_horaingreso'])); ?></td>
                                    <td class="celda-fija"><?php echo htmlspecialchars(formatear_hora_asistencia($saleAlmuerzo)); ?></td>
                                    <td class="celda-fija <?php echo htmlspecialchars($claseAlmuerzo); ?>"><?php echo htmlspecialchars(formatear_hora_asistencia($regresaAlmuerzo)); ?></td>
                                    <td class="celda-fija"><?php echo htmlspecialchars(formatear_hora_asistencia($saleBreak)); ?></td>
                                    <td class="celda-fija <?php echo htmlspecialchars($claseBreak); ?>"><?php echo htmlspecialchars(formatear_hora_asistencia($regresaBreak)); ?></td>
                                    <td class="celda-fija"><?php echo htmlspecialchars(formatear_hora_asistencia($row['seg_horaSalida'])); ?></td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
                <?php } ?>
            </div>
            <?php marca_footer(); ?>
        </div>
    </div>
    <?php if ($verEstadisticas) { ?>
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
        <script>
            (function () {
                var datos = window.datosGraficaHoras;
                var canvas = document.getElementById('graficaHorasCategoria');
                if (!datos || !canvas || typeof Chart === 'undefined') {
                    return;
                }
                new Chart(canvas, {
                    type: 'doughnut',
                    data: {
                        labels: datos.labels,
                        datasets: [{
                            data: datos.values,
                            backgroundColor: ['#5D8F8A', '#2B2F33', '#C4B8A5', '#8FB3AF', '#6B7C85'],
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    usePointStyle: true,
                                    padding: 16,
                                    font: { family: 'Mulish, Segoe UI, sans-serif' }
                                }
                            }
                        }
                    }
                });
            })();
        </script>
    <?php } else { ?>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/2.0.8/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/2.0.8/js/dataTables.bootstrap5.js"></script>
    <script>
        $(function () {
            var tabla = new DataTable('#tablaIngresosHuella', {
                pageLength: 30,
                lengthMenu: [[30, 50, 100, -1], [30, 50, 100, 'Todos']],
                order: [[2, 'desc'], [3, 'desc']],
                deferRender: true,
                language: {
                    search: 'Buscar en tabla:',
                    lengthMenu: 'Mostrar _MENU_ registros',
                    info: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
                    infoEmpty: 'Mostrando 0 a 0 de 0 registros',
                    infoFiltered: '(filtrados de _MAX_ registros)',
                    zeroRecords: 'No se encontraron registros',
                    emptyTable: 'No hay datos disponibles',
                    paginate: {
                        first: '<span aria-hidden="true">&laquo;</span>',
                        last: '<span aria-hidden="true">&raquo;</span>',
                        next: '<span aria-hidden="true">&rsaquo;</span>',
                        previous: '<span aria-hidden="true">&lsaquo;</span>'
                    }
                }
            });
        });
    </script>
    <?php } ?>
</body>
</html>
<?php
$con->desconectar();
?>
