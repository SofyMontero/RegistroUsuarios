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
    array('key' => 'ordinarias', 'label' => 'Ordinarias', 'valor' => (float) $horas['ordinarias'], 'color' => '#5D8F8A'),
    array('key' => 'nocturnas', 'label' => 'Nocturnas', 'valor' => (float) $horas['nocturnas'], 'color' => '#2B2F33'),
    array('key' => 'extra_diurnas', 'label' => 'Extra diurnas', 'valor' => (float) $horas['extra_diurnas'], 'color' => '#D4A574'),
    array('key' => 'extra_nocturnas', 'label' => 'Extra nocturnas', 'valor' => (float) $horas['extra_nocturnas'], 'color' => '#7A8B99'),
    array('key' => 'dominicales_festivas', 'label' => 'Dominicales/festivas', 'valor' => (float) $horas['dominicales_festivas'], 'color' => '#A67C52'),
);
$totalHoras = (float) $horas['total'];
$cx = 160;
$cy = 160;
$radio = 70;
$circunferencia = 2 * M_PI * $radio;
$separacion = 10;
$offset = 0;
$arcos = array();

if ($totalHoras > 0) {
    $visibles = array();
    foreach ($categorias as $categoria) {
        if ($categoria['valor'] > 0) {
            $visibles[] = $categoria;
        }
    }
    $nVisibles = count($visibles);
    foreach ($visibles as $categoria) {
        $fraccion = $categoria['valor'] / $totalHoras;
        $largo = $fraccion * $circunferencia;
        $hueco = $nVisibles > 1 ? $separacion : 0;
        $trazo = max($largo - $hueco, 0.8);
        $arcos[] = array(
            'color' => $categoria['color'],
            'dash' => round($trazo, 2),
            'gap' => round($circunferencia - $trazo, 2),
            'offset' => round(-$offset, 2),
        );
        $offset += $largo;
    }
}

$kpis = array(
    array('Total de horas ordinarias', $horas['ordinarias'], '#5D8F8A', ''),
    array('Total de horas nocturnas', $horas['nocturnas'], '#2B2F33', ''),
    array('Total de horas extra diurnas', $horas['extra_diurnas'], '#D4A574', ''),
    array('Total de horas extra nocturnas', $horas['extra_nocturnas'], '#7A8B99', ''),
    array('Total de horas dominicales/festivas', $horas['dominicales_festivas'], '#A67C52', ''),
    array('Total general de horas pagadas', $horas['total'], '#5D8F8A', 'stats-kpi-total'),
);
?>
<div class="stats-kpis metric-grid stats-kpi-grid mb-4">
    <?php foreach ($kpis as $kpi) { ?>
        <article class="metric-card stats-kpi <?php echo htmlspecialchars($kpi[3]); ?>" style="--kpi-color: <?php echo htmlspecialchars($kpi[2]); ?>;">
            <p class="metric-label"><?php echo htmlspecialchars($kpi[0]); ?></p>
            <p class="metric-value stats-kpi-value"><?php echo htmlspecialchars(formato_horas_estadistica($kpi[1])); ?></p>
        </article>
    <?php } ?>
</div>

<div class="row g-4 align-items-stretch">
    <div class="col-lg-6">
        <div class="stats-chart-card">
            <h3 class="stats-subtitle">Distribución por categoría</h3>
            <?php if ($totalHoras > 0) { ?>
                <div class="stats-donut-wrap">
                    <svg class="stats-donut" viewBox="0 0 320 320" role="img" aria-label="Distribución de horas del período">
                        <defs>
                            <filter id="statsDonutGlow" x="-20%" y="-20%" width="140%" height="140%">
                                <feDropShadow dx="0" dy="6" stdDeviation="8" flood-color="#2B2F33" flood-opacity="0.12"/>
                            </filter>
                        </defs>
                        <circle class="stats-donut-track" cx="<?php echo $cx; ?>" cy="<?php echo $cy; ?>" r="<?php echo $radio; ?>"></circle>
                        <g filter="url(#statsDonutGlow)" transform="rotate(-90 <?php echo $cx; ?> <?php echo $cy; ?>)">
                            <?php foreach ($arcos as $arco) { ?>
                                <circle
                                    class="stats-donut-slice"
                                    cx="<?php echo $cx; ?>"
                                    cy="<?php echo $cy; ?>"
                                    r="<?php echo $radio; ?>"
                                    stroke="<?php echo htmlspecialchars($arco['color']); ?>"
                                    stroke-dasharray="<?php echo htmlspecialchars($arco['dash'] . ' ' . $arco['gap']); ?>"
                                    stroke-dashoffset="<?php echo htmlspecialchars((string) $arco['offset']); ?>"
                                ></circle>
                            <?php } ?>
                        </g>
                        <circle class="stats-donut-hole" cx="<?php echo $cx; ?>" cy="<?php echo $cy; ?>" r="36"></circle>
                    </svg>
                    <ul class="stats-legend">
                        <?php foreach ($categorias as $categoria) { ?>
                            <li>
                                <span class="stats-legend-dot" style="background: <?php echo htmlspecialchars($categoria['color']); ?>;"></span>
                                <span><?php echo htmlspecialchars($categoria['label']); ?></span>
                            </li>
                        <?php } ?>
                    </ul>
                </div>
            <?php } else { ?>
                <div class="empty-placeholder">No hay horas clasificadas en el período consultado.</div>
            <?php } ?>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="stats-extra-grid">
            <article class="metric-card">
                <p class="metric-label">Total de horas extra</p>
                <p class="metric-value"><?php echo htmlspecialchars(formato_horas_estadistica($estadisticas['extra_total'])); ?></p>
            </article>
            <article class="metric-card">
                <p class="metric-label">Total de horas con recargo</p>
                <p class="metric-value"><?php echo htmlspecialchars(formato_horas_estadistica($estadisticas['con_recargo'])); ?></p>
            </article>
            <article class="metric-card stats-kpi-total stats-extra-wide">
                <p class="metric-label">% horas con recargo sobre pagadas</p>
                <p class="metric-value"><?php echo htmlspecialchars(formato_porcentaje_estadistica($estadisticas['pct_con_recargo'])); ?></p>
            </article>
        </div>
    </div>
</div>
