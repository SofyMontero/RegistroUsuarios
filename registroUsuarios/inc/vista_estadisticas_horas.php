<?php
if (!function_exists('formato_horas_estadistica')) {
    function formato_horas_estadistica($horas)
    {
        return number_format((float) $horas, 2, ',', '.') . ' h';
    }
}

if (!function_exists('formato_porcentaje_estadistica')) {
    function formato_porcentaje_estadistica($pct)
    {
        return number_format((float) $pct, 1, ',', '.') . ' %';
    }
}

if (!function_exists('html_variacion_estadistica')) {
    function html_variacion_estadistica($pct)
    {
        if ($pct === null) {
            return '<span class="stats-delta is-flat">—</span>';
        }
        $clase = $pct > 0 ? 'is-up' : ($pct < 0 ? 'is-down' : 'is-flat');
        $signo = $pct > 0 ? '+' : '';

        return '<span class="stats-delta ' . $clase . '">' . $signo . number_format((float) $pct, 1, ',', '.') . ' %</span>';
    }
}

$horas = $estadisticas['horas'];
$chartPayload = array(
    'labels' => array('Ordinarias', 'Nocturnas', 'Extra diurnas', 'Extra nocturnas', 'Dominicales/festivas'),
    'values' => array(
        (float) $horas['ordinarias'],
        (float) $horas['nocturnas'],
        (float) $horas['extra_diurnas'],
        (float) $horas['extra_nocturnas'],
        (float) $horas['dominicales_festivas'],
    ),
);
?>
<div class="stats-kpis metric-grid stats-kpi-grid mb-4">
    <article class="metric-card stats-kpi">
        <p class="metric-label">Horas ordinarias</p>
        <p class="metric-value stats-kpi-value"><?php echo htmlspecialchars(formato_horas_estadistica($horas['ordinarias'])); ?></p>
        <?php if ($comparacion) { echo html_variacion_estadistica($variaciones['ordinarias']); } ?>
    </article>
    <article class="metric-card stats-kpi">
        <p class="metric-label">Horas nocturnas</p>
        <p class="metric-value stats-kpi-value"><?php echo htmlspecialchars(formato_horas_estadistica($horas['nocturnas'])); ?></p>
        <?php if ($comparacion) { echo html_variacion_estadistica($variaciones['nocturnas']); } ?>
    </article>
    <article class="metric-card stats-kpi">
        <p class="metric-label">Horas extra diurnas</p>
        <p class="metric-value stats-kpi-value"><?php echo htmlspecialchars(formato_horas_estadistica($horas['extra_diurnas'])); ?></p>
        <?php if ($comparacion) { echo html_variacion_estadistica($variaciones['extra_diurnas']); } ?>
    </article>
    <article class="metric-card stats-kpi">
        <p class="metric-label">Horas extra nocturnas</p>
        <p class="metric-value stats-kpi-value"><?php echo htmlspecialchars(formato_horas_estadistica($horas['extra_nocturnas'])); ?></p>
        <?php if ($comparacion) { echo html_variacion_estadistica($variaciones['extra_nocturnas']); } ?>
    </article>
    <article class="metric-card stats-kpi">
        <p class="metric-label">Dominicales / festivas</p>
        <p class="metric-value stats-kpi-value"><?php echo htmlspecialchars(formato_horas_estadistica($horas['dominicales_festivas'])); ?></p>
        <?php if ($comparacion) { echo html_variacion_estadistica($variaciones['dominicales_festivas']); } ?>
    </article>
    <article class="metric-card stats-kpi stats-kpi-total">
        <p class="metric-label">Total horas pagadas</p>
        <p class="metric-value stats-kpi-value"><?php echo htmlspecialchars(formato_horas_estadistica($horas['total'])); ?></p>
        <?php if ($comparacion) { echo html_variacion_estadistica($variaciones['total']); } ?>
    </article>
</div>

<div class="row g-4 align-items-stretch">
    <div class="col-lg-5">
        <div class="stats-chart-card">
            <h3 class="stats-subtitle">Distribución por categoría</h3>
            <?php if ((float) $horas['total'] > 0) { ?>
                <div class="stats-chart-wrap">
                    <canvas id="graficaHorasCategoria" aria-label="Distribución de horas del período"></canvas>
                </div>
            <?php } else { ?>
                <div class="empty-placeholder">No hay horas clasificadas en el período consultado.</div>
            <?php } ?>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="stats-extra-grid">
            <article class="metric-card">
                <p class="metric-label">% horas ordinarias</p>
                <p class="metric-value"><?php echo htmlspecialchars(formato_porcentaje_estadistica($estadisticas['pct_ordinarias'])); ?></p>
            </article>
            <article class="metric-card">
                <p class="metric-label">% horas nocturnas</p>
                <p class="metric-value"><?php echo htmlspecialchars(formato_porcentaje_estadistica($estadisticas['pct_nocturnas'])); ?></p>
            </article>
            <article class="metric-card">
                <p class="metric-label">% horas extra</p>
                <p class="metric-value"><?php echo htmlspecialchars(formato_porcentaje_estadistica($estadisticas['pct_extra'])); ?></p>
            </article>
            <article class="metric-card">
                <p class="metric-label">% dominicales / festivas</p>
                <p class="metric-value"><?php echo htmlspecialchars(formato_porcentaje_estadistica($estadisticas['pct_dominicales'])); ?></p>
            </article>
            <article class="metric-card">
                <p class="metric-label">Total horas extra</p>
                <p class="metric-value"><?php echo htmlspecialchars(formato_horas_estadistica($estadisticas['extra_total'])); ?></p>
                <?php if ($comparacion) { echo html_variacion_estadistica($variaciones['extra_total']); } ?>
            </article>
            <article class="metric-card">
                <p class="metric-label">Horas con recargo</p>
                <p class="metric-value"><?php echo htmlspecialchars(formato_horas_estadistica($estadisticas['con_recargo'])); ?></p>
                <?php if ($comparacion) { echo html_variacion_estadistica($variaciones['con_recargo']); } ?>
            </article>
            <article class="metric-card stats-relacion">
                <p class="metric-label">Ordinarias : recargo</p>
                <p class="metric-value"><?php echo htmlspecialchars($estadisticas['relacion_ordinarias_recargo']); ?></p>
                <p class="helper-text mt-2 mb-0">Por cada hora ordinaria hay esa proporción de horas con recargo (nocturnas, extras, dominicales o festivas).</p>
            </article>
        </div>
    </div>
</div>

<?php if ($comparacion) { ?>
    <div class="stats-compare mt-4">
        <h3 class="stats-subtitle">Comparación con el período anterior</h3>
        <p class="section-copy mb-3">
            Anterior: <?php echo htmlspecialchars($fechaPrevDesde); ?> a <?php echo htmlspecialchars($fechaPrevHasta); ?>.
            Variación porcentual de cada tipo de hora.
        </p>
        <div class="report-table-wrap">
            <table class="report-table stats-compare-table">
                <thead>
                    <tr>
                        <th>Concepto</th>
                        <th>Período actual</th>
                        <th>Período anterior</th>
                        <th>Variación</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $filasComparacion = array(
                        array('Horas ordinarias', $horas['ordinarias'], $comparacion['horas']['ordinarias'], $variaciones['ordinarias']),
                        array('Horas nocturnas', $horas['nocturnas'], $comparacion['horas']['nocturnas'], $variaciones['nocturnas']),
                        array('Horas extra diurnas', $horas['extra_diurnas'], $comparacion['horas']['extra_diurnas'], $variaciones['extra_diurnas']),
                        array('Horas extra nocturnas', $horas['extra_nocturnas'], $comparacion['horas']['extra_nocturnas'], $variaciones['extra_nocturnas']),
                        array('Dominicales / festivas', $horas['dominicales_festivas'], $comparacion['horas']['dominicales_festivas'], $variaciones['dominicales_festivas']),
                        array('Total horas extra', $estadisticas['extra_total'], $comparacion['extra_total'], $variaciones['extra_total']),
                        array('Horas con recargo', $estadisticas['con_recargo'], $comparacion['con_recargo'], $variaciones['con_recargo']),
                        array('Total horas pagadas', $horas['total'], $comparacion['horas']['total'], $variaciones['total']),
                    );
                    foreach ($filasComparacion as $filaCmp) {
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($filaCmp[0]); ?></td>
                            <td class="celda-fija"><?php echo htmlspecialchars(formato_horas_estadistica($filaCmp[1])); ?></td>
                            <td class="celda-fija"><?php echo htmlspecialchars(formato_horas_estadistica($filaCmp[2])); ?></td>
                            <td><?php echo html_variacion_estadistica($filaCmp[3]); ?></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
<?php } else { ?>
    <p class="section-copy mt-4 mb-0">No hay un período anterior con registros para comparar.</p>
<?php } ?>

<?php if ((float) $horas['total'] > 0) { ?>
<script>
    window.datosGraficaHoras = <?php echo json_encode($chartPayload); ?>;
</script>
<?php } ?>
