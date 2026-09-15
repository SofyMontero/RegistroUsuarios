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

$horas = $estadisticas['horas'];
$categorias = array(
    array('Ordinarias', (float) $horas['ordinarias'], '#5D8F8A'),
    array('Nocturnas', (float) $horas['nocturnas'], '#2B2F33'),
    array('Extra diurnas', (float) $horas['extra_diurnas'], '#C5A572'),
    array('Extra nocturnas', (float) $horas['extra_nocturnas'], '#6B7C8A'),
    array('Dominicales/festivas', (float) $horas['dominicales_festivas'], '#8B6B4A'),
);
$totalHoras = (float) $horas['total'];
$conicStops = array();
$acumulado = 0.0;
if ($totalHoras > 0) {
    foreach ($categorias as $categoria) {
        $inicio = ($acumulado / $totalHoras) * 360;
        $acumulado += $categoria[1];
        $fin = ($acumulado / $totalHoras) * 360;
        $conicStops[] = $categoria[2] . ' ' . $inicio . 'deg ' . $fin . 'deg';
    }
}
$conicCss = empty($conicStops) ? '#E7E6E2' : ('conic-gradient(' . implode(', ', $conicStops) . ')');
?>
<div class="stats-kpis metric-grid stats-kpi-grid mb-4">
    <article class="metric-card stats-kpi">
        <p class="metric-label">Total de horas ordinarias</p>
        <p class="metric-value stats-kpi-value"><?php echo htmlspecialchars(formato_horas_estadistica($horas['ordinarias'])); ?></p>
    </article>
    <article class="metric-card stats-kpi">
        <p class="metric-label">Total de horas nocturnas</p>
        <p class="metric-value stats-kpi-value"><?php echo htmlspecialchars(formato_horas_estadistica($horas['nocturnas'])); ?></p>
    </article>
    <article class="metric-card stats-kpi">
        <p class="metric-label">Total de horas extra diurnas</p>
        <p class="metric-value stats-kpi-value"><?php echo htmlspecialchars(formato_horas_estadistica($horas['extra_diurnas'])); ?></p>
    </article>
    <article class="metric-card stats-kpi">
        <p class="metric-label">Total de horas extra nocturnas</p>
        <p class="metric-value stats-kpi-value"><?php echo htmlspecialchars(formato_horas_estadistica($horas['extra_nocturnas'])); ?></p>
    </article>
    <article class="metric-card stats-kpi">
        <p class="metric-label">Total de horas dominicales/festivas</p>
        <p class="metric-value stats-kpi-value"><?php echo htmlspecialchars(formato_horas_estadistica($horas['dominicales_festivas'])); ?></p>
    </article>
    <article class="metric-card stats-kpi stats-kpi-total">
        <p class="metric-label">Total general de horas pagadas</p>
        <p class="metric-value stats-kpi-value"><?php echo htmlspecialchars(formato_horas_estadistica($horas['total'])); ?></p>
    </article>
</div>

<div class="row g-4 align-items-stretch">
    <div class="col-lg-5">
        <div class="stats-chart-card">
            <h3 class="stats-subtitle">Distribución por categoría</h3>
            <?php if ($totalHoras > 0) { ?>
                <div class="stats-chart-wrap">
                    <div class="stats-pie" style="background: <?php echo htmlspecialchars($conicCss); ?>;" role="img" aria-label="Distribución de horas del período"></div>
                    <ul class="stats-legend">
                        <?php foreach ($categorias as $categoria) { ?>
                            <li>
                                <span class="stats-legend-dot" style="background: <?php echo htmlspecialchars($categoria[2]); ?>;"></span>
                                <span><?php echo htmlspecialchars($categoria[0]); ?></span>
                                <strong><?php echo htmlspecialchars(formato_horas_estadistica($categoria[1])); ?></strong>
                            </li>
                        <?php } ?>
                    </ul>
                </div>
            <?php } else { ?>
                <div class="empty-placeholder">No hay horas clasificadas en el período consultado.</div>
            <?php } ?>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="stats-extra-grid">
            <article class="metric-card">
                <p class="metric-label">Total de horas extra</p>
                <p class="metric-value"><?php echo htmlspecialchars(formato_horas_estadistica($estadisticas['extra_total'])); ?></p>
                <p class="helper-text mt-2 mb-0">Diurnas + nocturnas</p>
            </article>
            <article class="metric-card">
                <p class="metric-label">Total de horas con recargo</p>
                <p class="metric-value"><?php echo htmlspecialchars(formato_horas_estadistica($estadisticas['con_recargo'])); ?></p>
                <p class="helper-text mt-2 mb-0">Nocturnas + extras + dominicales/festivas</p>
            </article>
            <article class="metric-card">
                <p class="metric-label">% horas ordinarias</p>
                <p class="metric-value"><?php echo htmlspecialchars(formato_porcentaje_estadistica($estadisticas['pct_ordinarias'])); ?></p>
            </article>
            <article class="metric-card">
                <p class="metric-label">% horas extra</p>
                <p class="metric-value"><?php echo htmlspecialchars(formato_porcentaje_estadistica($estadisticas['pct_extra'])); ?></p>
            </article>
            <article class="metric-card">
                <p class="metric-label">% horas nocturnas</p>
                <p class="metric-value"><?php echo htmlspecialchars(formato_porcentaje_estadistica($estadisticas['pct_nocturnas'])); ?></p>
            </article>
            <article class="metric-card">
                <p class="metric-label">% dominicales/festivas</p>
                <p class="metric-value"><?php echo htmlspecialchars(formato_porcentaje_estadistica($estadisticas['pct_dominicales'])); ?></p>
            </article>
            <article class="metric-card stats-kpi-total stats-extra-wide">
                <p class="metric-label">% horas con recargo sobre pagadas</p>
                <p class="metric-value"><?php echo htmlspecialchars(formato_porcentaje_estadistica($estadisticas['pct_con_recargo'])); ?></p>
            </article>
        </div>
    </div>
</div>
