<?php
/**
 * Valores por defecto de jornada (CST / Ley 2466 de 2025).
 * Si el trabajador no tiene horario propio en `usuarios`, se usan estos.
 */
return array(
    // Trabajo nocturno: 7:00 p.m. a 6:00 a.m. (art. 160 CST, Ley 2466).
    'hora_inicio_nocturna' => '19:00:00',
    'hora_fin_nocturna' => '06:00:00',
    // Tope legal semanal vigente desde el 15 de julio de 2026.
    'jornada_semanal_horas' => 42,
    // 42 h / 6 días. No se usa 8 h fijas para disparar extra.
    'jornada_diaria_horas' => 7,
    'dias_laborales' => 6,
    'hora_inicio' => '07:00:00',
    'hora_fin' => '15:00:00',
    // 0 = domingo.
    'dia_descanso' => 0,
);
