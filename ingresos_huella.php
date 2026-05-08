<?php
require_once 'Model/bd.php';
require_once __DIR__ . '/app/bootstrap.php';
set_time_limit(0);
date_default_timezone_set("America/Bogota");

use Huella\Core\Database;
use Huella\Repositories\BiometricRepository;

$con = new bd();
$biometricRepository = new BiometricRepository(new Database());
$sede = isset($_GET['sede']) ? trim($_GET['sede']) : '';
$fechaDesde = isset($_GET['fecha_desde']) && $_GET['fecha_desde'] !== '' ? $_GET['fecha_desde'] : date('Y-m-d');
$fechaHasta = isset($_GET['fecha_hasta']) && $_GET['fecha_hasta'] !== '' ? $_GET['fecha_hasta'] : date('Y-m-d');
$busqueda = isset($_GET['q']) ? trim($_GET['q']) : '';

$biometricRepository->ensureTodayAttendanceRowsForActiveUsers(date('Y-m-d'));

$where = array(
    "s.seg_fechaingreso >= '" . addslashes($fechaDesde) . "'",
    "s.seg_fechaingreso <= '" . addslashes($fechaHasta) . "'",
);

if ($busqueda !== '') {
    $busquedaSql = addslashes($busqueda);
    $where[] = "(s.seg_iduser LIKE '%{$busquedaSql}%' OR u.usu_nombre LIKE '%{$busquedaSql}%')";
}

$sql = "
    SELECT
        s.seg_iduser AS documento,
        COALESCE(u.usu_nombre, 'Sin nombre') AS nombre,
        s.seg_fechaingreso,
        s.seg_horaingreso,
        s.seg_ingresoAlmuerzo,
        s.seg_salioAlmuerzo,
        s.seg_horaSalida
    FROM seguimientousers s
    LEFT JOIN usuarios u ON u.usu_identificacion = s.seg_iduser
    WHERE " . implode(' AND ', $where) . "
    ORDER BY s.seg_fechaingreso DESC, s.seg_horaingreso DESC
    LIMIT 300
";

$rows = $con->findAll($sql);
$total = count($rows);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Ingresos por Huella | Bermudas</title>
    <link rel="shortcut icon" href="images/fondo4.png" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;700;800&display=swap" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.datatables.net/2.0.8/css/dataTables.bootstrap5.css" rel="stylesheet" />
    <link href="https://cdn.datatables.net/buttons/3.0.2/css/buttons.bootstrap5.css" rel="stylesheet" />
    <link href="Css/estilo.css" rel="stylesheet" type="text/css" />
</head>
<body class="biometric-body">
    <div class="biometric-shell">
        <div class="container page-wrap">
            <div class="glass-card topbar-card mb-4">
                <div class="row g-4 align-items-center">
                    <div class="col-lg-7">
                        <span class="eyebrow">Reporte biometrico</span>
                        <h1 class="page-title">Historial de ingresos</h1>
                        
                    </div>
                    <div class="col-lg-5">
                        <div class="action-stack">
                            <a class="btn-soft btn-soft-secondary" href="verificar.php?sede=<?php echo htmlspecialchars($sede); ?>&token=<?php echo isset($_GET['token']) ? htmlspecialchars($_GET['token']) : ''; ?>">Volver a ingreso</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="glass-card section-card mb-4">
                <form class="row g-3 align-items-end" method="get">
                    <input type="hidden" name="sede" value="<?php echo htmlspecialchars($sede); ?>" />
                    <input type="hidden" name="token" value="<?php echo isset($_GET['token']) ? htmlspecialchars($_GET['token']) : ''; ?>" />
                    <div class="col-md-3">
                        <label class="field-label" for="fecha_desde">Desde</label>
                        <input class="form-control biometric-input" type="date" id="fecha_desde" name="fecha_desde" value="<?php echo htmlspecialchars($fechaDesde); ?>" />
                    </div>
                    <div class="col-md-3">
                        <label class="field-label" for="fecha_hasta">Hasta</label>
                        <input class="form-control biometric-input" type="date" id="fecha_hasta" name="fecha_hasta" value="<?php echo htmlspecialchars($fechaHasta); ?>" />
                    </div>
                    <div class="col-md-4">
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
                        <h2 class="section-title">Resultados</h2>
                        <p class="section-copy">Mostrando <?php echo $total; ?> registros dentro del rango seleccionado.</p>
                    </div>
                    <div class="d-flex flex-wrap gap-2 align-items-center">
                        <div class="status-pill"><?php echo $fechaDesde; ?> a <?php echo $fechaHasta; ?></div>
                        <button class="btn btn-success rounded-4 px-4" id="btnExportarExcel" type="button">Descargar Excel</button>
                    </div>
                </div>

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
                                <th>Salida</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($total === 0) { ?>
                                <tr>
                                    <td colspan="7">
                                        <div class="empty-placeholder">No hay ingresos por huella para los filtros seleccionados.</div>
                                    </td>
                                </tr>
                            <?php } ?>
                            <?php foreach ($rows as $row) { ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($row['nombre']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($row['documento']); ?></td>
                                    <td><?php echo htmlspecialchars($row['seg_fechaingreso']); ?></td>
                                    <td><?php echo htmlspecialchars($row['seg_horaingreso']); ?></td>
                                    <td><?php echo htmlspecialchars($row['seg_ingresoAlmuerzo']); ?></td>
                                    <td><?php echo htmlspecialchars($row['seg_salioAlmuerzo']); ?></td>
                                    <td><?php echo htmlspecialchars($row['seg_horaSalida']); ?></td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/2.0.8/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/2.0.8/js/dataTables.bootstrap5.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.0.2/js/dataTables.buttons.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.0.2/js/buttons.bootstrap5.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.0.2/js/buttons.html5.min.js"></script>
    <script>
        $(function () {
            var tabla = new DataTable('#tablaIngresosHuella', {
                pageLength: 30,
                lengthMenu: [[30, 50, 100, -1], [30, 50, 100, 'Todos']],
                order: [[2, 'desc'], [3, 'desc']],
                layout: {
                    topStart: {
                        buttons: [
                            {
                                extend: 'excelHtml5',
                                text: 'Excel',
                                title: 'Ingresos_Huella_' + new Date().toISOString().slice(0, 10),
                                exportOptions: {
                                    columns: ':visible'
                                },
                                className: 'd-none',
                                attr: {
                                    id: 'excelHiddenTrigger'
                                }
                            }
                        ]
                    }
                },
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

            $('#btnExportarExcel').on('click', function () {
                $('#excelHiddenTrigger').trigger('click');
            });
        });
    </script>
</body>
</html>
<?php
$con->desconectar();
?>
