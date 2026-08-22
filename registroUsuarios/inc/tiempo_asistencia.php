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
    if ($hora === '') {
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
    if (hora_asistencia_vacia($hora)) {
        return HORA_CERO_ASISTENCIA;
    }

    $hora = trim((string) $hora);
    $ts = strtotime($hora);
    if ($ts === false) {
        return $hora;
    }

    return date('g:i:s A', $ts);
}

function formatear_fecha_asistencia($fecha)
{
    $fecha = trim((string) $fecha);
    if ($fecha === '' || strpos($fecha, '0000-00-00') === 0) {
        return '—';
    }

    $ts = strtotime($fecha);
    if ($ts === false) {
        return $fecha;
    }

    return date('Y-m-d', $ts);
}
