<?php
/**
 * Tiempos permitidos de pausa. El regreso se marca en rojo si se pasan.
 */
if (!defined('MINUTOS_ALMUERZO')) {
    define('MINUTOS_ALMUERZO', 60);
}
if (!defined('MINUTOS_BREAK')) {
    define('MINUTOS_BREAK', 15);
}
if (!defined('HORA_CERO_ASISTENCIA')) {
    define('HORA_CERO_ASISTENCIA', '00:00:00');
}

function hora_asistencia_vacia($hora)
{
    $hora = trim((string) $hora);
    if ($hora === '' || $hora === '—' || $hora === '-' || $hora === '--' || $hora === '---') {
        return true;
    }

    return strpos($hora, '00:00:00') === 0;
}

function minutos_entre_horas($inicio, $fin)
{
    if (hora_asistencia_vacia($inicio) || hora_asistencia_vacia($fin)) {
        return null;
    }

    $inicioTs = strtotime($inicio);
    $finTs = strtotime($fin);
    if ($inicioTs === false || $finTs === false) {
        return null;
    }

    return (int) round(($finTs - $inicioTs) / 60);
}

function tiempo_pausa_excedido($inicio, $fin, $minutosPermitidos)
{
    $minutos = minutos_entre_horas($inicio, $fin);

    return $minutos !== null && $minutos > (int) $minutosPermitidos;
}

function clase_celda_tiempo_excedido($inicio, $fin, $minutosPermitidos)
{
    return tiempo_pausa_excedido($inicio, $fin, $minutosPermitidos) ? 'tiempo-excedido' : '';
}

function formatear_hora_asistencia($hora)
{
    if (hora_asistencia_vacia($hora) || $hora === null) {
        return '00:00:00';
    }

    $hora = trim((string) $hora);
    $ts = strtotime($hora);
    if ($ts === false) {
        return '00:00:00';
    }

    return date('g:i:s A', $ts);
}

function formatear_fecha_asistencia($fecha)
{
    $fecha = trim((string) $fecha);
    if ($fecha === '' || strpos($fecha, '0000-00-00') === 0) {
        return '';
    }

    $ts = strtotime($fecha);
    if ($ts === false) {
        return $fecha;
    }

    return date('Y-m-d', $ts);
}

function hora_cruda_asistencia($hora)
{
    if (hora_asistencia_vacia($hora)) {
        return HORA_CERO_ASISTENCIA;
    }

    $hora = trim((string) $hora);
    if (preg_match('/^(\d{2}):(\d{2}):(\d{2})/', $hora, $partes)) {
        return $partes[1] . ':' . $partes[2] . ':' . $partes[3];
    }

    $ts = strtotime($hora);
    if ($ts === false) {
        return HORA_CERO_ASISTENCIA;
    }

    return date('H:i:s', $ts);
}

function html_celda_hora($hora, $campo, $etiqueta, $claseExtra = '')
{
    $cruda = hora_cruda_asistencia($hora);
    $texto = formatear_hora_asistencia($hora);
    $clase = trim('celda-fija celda-hora-edit ' . $claseExtra);
    $html = '<td class="' . htmlspecialchars($clase) . '" data-campo="' . htmlspecialchars($campo) . '" data-hora="' . htmlspecialchars($cruda) . '">';
    $html .= '<div class="hora-celda">';
    $html .= '<span class="hora-celda-valor">' . htmlspecialchars($texto) . '</span>';
    $html .= '<button class="hora-edit-btn js-editar-hora" type="button" data-campo="' . htmlspecialchars($campo) . '" data-label="' . htmlspecialchars($etiqueta) . '" aria-label="Editar ' . htmlspecialchars($etiqueta) . '">';
    $html .= '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 1 1 3 3L7 19l-4 1 1-4Z"/></svg>';
    $html .= '</button></div></td>';
    return $html;
}

function normalizar_hora_asistencia($hora)
{
    $hora = trim((string) $hora);
    if ($hora === '') {
        return HORA_CERO_ASISTENCIA;
    }

    if (!preg_match('/^(\d{1,2}):(\d{2})(?::(\d{2}))?$/', $hora, $partes)) {
        return null;
    }

    $horas = (int) $partes[1];
    $minutos = (int) $partes[2];
    $segundos = isset($partes[3]) ? (int) $partes[3] : 0;
    if ($horas > 23 || $minutos > 59 || $segundos > 59) {
        return null;
    }

    return sprintf('%02d:%02d:%02d', $horas, $minutos, $segundos);
}
