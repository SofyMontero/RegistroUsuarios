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
$trabajadores = $repository->listWorkersForSchedule($sede, $busqueda);

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

function horario_hora_input($valor, $fallback)
{
    $valor = trim((string) $valor);
    if ($valor === '') {
        $valor = $fallback;
    }
    return substr($valor, 0, 5);
}

function horario_horas_desde_minutos($minutos, $fallbackHoras)
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
    <title>Horarios de trabajadores | Monteblanco</title>
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
                        <span class="eyebrow">Módulo administrativo</span>
                        <h1 class="page-title">Horarios de trabajadores</h1>
                        <p class="section-copy mb-0">
                            Ajuste hora de ingreso, salida, jornada y día de descanso. Estas horas se usan en las estadísticas y en el Excel.
                        </p>
                    </div>
                    <div class="col-lg-5">
                        <?php auth_render_nav('horarios', $token, $sede); ?>
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
                        <h2 class="section-title">Trabajadores</h2>
                        <p class="section-copy mb-0">Mostrando <?php echo count($trabajadores); ?> registros. Si un campo está vacío, se propone el horario por defecto (<?php echo htmlspecialchars(substr($configJornada['hora_inicio'], 0, 5) . ' - ' . substr($configJornada['hora_fin'], 0, 5)); ?>).</p>
                    </div>
                </div>

                <div class="report-table-shell">
                    <table class="report-table" id="tablaHorarios">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Documento</th>
                                <th>Sede</th>
                                <th>Ingreso</th>
                                <th>Salida</th>
                                <th>Jornada diaria (h)</th>
                                <th>Jornada semanal (h)</th>
                                <th>Descanso</th>
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
                                ?>
                                <tr data-documento="<?php echo htmlspecialchars($trabajador['usu_identificacion']); ?>">
                                    <td><strong><?php echo htmlspecialchars($trabajador['usu_nombre'] ? $trabajador['usu_nombre'] : 'Sin nombre'); ?></strong></td>
                                    <td class="celda-fija"><?php echo htmlspecialchars($trabajador['usu_identificacion']); ?></td>
                                    <td><?php echo htmlspecialchars($nombreSede); ?></td>
                                    <td>
                                        <input class="form-control biometric-input horario-input js-hora-inicio" type="time" value="<?php echo htmlspecialchars(horario_hora_input($trabajador['usu_hora_inicio'], $configJornada['hora_inicio'])); ?>" />
                                    </td>
                                    <td>
                                        <input class="form-control biometric-input horario-input js-hora-fin" type="time" value="<?php echo htmlspecialchars(horario_hora_input($trabajador['usu_hora_fin'], $configJornada['hora_fin'])); ?>" />
                                    </td>
                                    <td>
                                        <input class="form-control biometric-input horario-input js-jornada-diaria" type="number" min="1" max="24" step="0.5" value="<?php echo htmlspecialchars((string) horario_horas_desde_minutos($trabajador['usu_jornada_diaria_minutos'], $configJornada['jornada_diaria_horas'])); ?>" />
                                    </td>
                                    <td>
                                        <input class="form-control biometric-input horario-input js-jornada-semanal" type="number" min="1" max="72" step="0.5" value="<?php echo htmlspecialchars((string) horario_horas_desde_minutos($trabajador['usu_jornada_semanal_minutos'], $configJornada['jornada_semanal_horas'])); ?>" />
                                    </td>
                                    <td>
                                        <select class="form-control biometric-input horario-input js-dia-descanso">
                                            <?php foreach ($diasDescanso as $valorDia => $nombreDia) { ?>
                                                <option value="<?php echo (int) $valorDia; ?>" <?php echo $diaActual === (int) $valorDia ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($nombreDia); ?>
                                                </option>
                                            <?php } ?>
                                        </select>
                                    </td>
                                    <td class="celda-fija">
                                        <button class="btn btn-primary rounded-4 px-3 js-guardar-horario" type="button">Guardar</button>
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

        function guardarHorario($fila) {
            var boton = $fila.find(".js-guardar-horario");
            boton.prop("disabled", true);
            $.ajax({
                type: "POST",
                url: "Model/HorariosAdmin.php",
                dataType: "json",
                data: {
                    documento: $fila.data("documento"),
                    hora_inicio: $fila.find(".js-hora-inicio").val(),
                    hora_fin: $fila.find(".js-hora-fin").val(),
                    jornada_diaria: $fila.find(".js-jornada-diaria").val(),
                    jornada_semanal: $fila.find(".js-jornada-semanal").val(),
                    dia_descanso: $fila.find(".js-dia-descanso").val()
                },
                success: function (data) {
                    if (data.success) {
                        showMessageBox(data.message || "Horario actualizado", "success");
                    } else {
                        showMessageBox(data.message || "No fue posible guardar el horario", "warning");
                    }
                },
                error: function (xhr) {
                    var mensaje = "No fue posible guardar el horario";
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
            MonteblancoTable.init("#tablaHorarios", {
                columnDefs: [{ orderable: false, targets: [3, 4, 5, 6, 7, 8] }]
            });
        }

        $(document).on("click", ".js-guardar-horario", function () {
            guardarHorario($(this).closest("tr"));
        });
    </script>
</body>
</html>
