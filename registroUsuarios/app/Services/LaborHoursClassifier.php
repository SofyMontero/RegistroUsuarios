<?php

namespace Huella\Services;

require_once dirname(__DIR__, 2) . '/inc/tiempo_asistencia.php';

/**
 * Clasifica minutos trabajados según CST / Ley 2466 de 2025.
 *
 * Hora extra = fuera del horario pactado o que agote la jornada ordinaria
 * configurada (diaria o semanal). No se trata toda hora nocturna como extra
 * ni se dispara extra solo por superar 8 horas.
 */
class LaborHoursClassifier
{
    private $config;
    private $festivos;

    public function __construct(array $config, ColombianHolidays $festivos)
    {
        $this->config = $config;
        $this->festivos = $festivos;
    }

    /**
     * @param array $filas filas de seguimientousers
     * @param array $horarios documento => horario
     * @return array
     */
    public function summarize(array $filas, array $horarios)
    {
        $porTrabajador = array();
        foreach ($filas as $fila) {
            $documento = isset($fila['documento']) ? (string) $fila['documento'] : '';
            if ($documento === '') {
                continue;
            }
            if (!isset($porTrabajador[$documento])) {
                $horario = isset($horarios[$documento]) ? $horarios[$documento] : array();
                $porTrabajador[$documento] = $this->emptySummary($fila, $horario);
            }
            $this->acumularDia($porTrabajador[$documento], $fila);
        }

        $resultado = array_values($porTrabajador);
        usort($resultado, function ($a, $b) {
            return strcmp($a['nombre'], $b['nombre']);
        });

        return $resultado;
    }

    private function emptySummary(array $fila, array $horario)
    {
        $inicio = $this->horaConfig($horario, 'usu_hora_inicio', 'hora_inicio');
        $fin = $this->horaConfig($horario, 'usu_hora_fin', 'hora_fin');
        $diariaMin = $this->enteroConfig($horario, 'usu_jornada_diaria_minutos', 'jornada_diaria_horas', 60);
        $semanalMin = $this->enteroConfig($horario, 'usu_jornada_semanal_minutos', 'jornada_semanal_horas', 60);
        $descanso = isset($horario['usu_dia_descanso']) && $horario['usu_dia_descanso'] !== null && $horario['usu_dia_descanso'] !== ''
            ? (int) $horario['usu_dia_descanso']
            : (int) $this->config['dia_descanso'];

        return array(
            'nombre' => isset($fila['nombre']) ? $fila['nombre'] : '',
            'documento' => isset($fila['documento']) ? $fila['documento'] : '',
            'horario' => substr($inicio, 0, 5) . ' - ' . substr($fin, 0, 5),
            'jornada_diaria_horas' => round($diariaMin / 60, 2),
            'jornada_semanal_horas' => round($semanalMin / 60, 2),
            'dia_descanso' => $this->nombreDia($descanso),
            'ordinaria_diurna' => 0,
            'ordinaria_nocturna' => 0,
            'extra_diurna' => 0,
            'extra_nocturna' => 0,
            'dominical_descanso' => 0,
            'festiva' => 0,
            'nocturna_extra_dominical_festivo' => 0,
            'total_trabajadas' => 0,
            'total_extra' => 0,
            'total_nocturna' => 0,
            '_inicio_min' => $this->horaAMinutos($inicio),
            '_fin_min' => $this->horaAMinutos($fin),
            '_diaria_min' => $diariaMin,
            '_semanal_min' => $semanalMin,
            '_descanso' => $descanso,
            '_semana' => array(),
            '_dia' => array(),
        );
    }

    private function acumularDia(array &$resumen, array $fila)
    {
        $intervalos = $this->intervalosTrabajados($fila);
        if (empty($intervalos)) {
            return;
        }

        // El día de la jornada es el de la marca de ingreso, no el calendario
        // de cada minuto: un turno 22:00-06:00 no se vuelve dominical a medianoche.
        $fechaJornada = isset($fila['seg_fechaingreso'])
            ? \formatear_fecha_asistencia($fila['seg_fechaingreso'])
            : date('Y-m-d', $intervalos[0][0]);
        $tsJornada = strtotime($fechaJornada);
        $claveSemana = date('o-W', $tsJornada);
        $esFestivo = $this->festivos->isHoliday($fechaJornada);
        $esDescanso = ((int) date('w', $tsJornada) === (int) $resumen['_descanso']);

        foreach ($intervalos as $intervalo) {
            $cursor = $intervalo[0];
            $fin = $intervalo[1];
            while ($cursor < $fin) {
                $minDia = ((int) date('H', $cursor)) * 60 + (int) date('i', $cursor);
                $esNoche = $this->esNocturna($minDia);
                $enHorario = $this->enHorario($minDia, $resumen['_inicio_min'], $resumen['_fin_min']);

                if (!isset($resumen['_semana'][$claveSemana])) {
                    $resumen['_semana'][$claveSemana] = 0;
                }
                if (!isset($resumen['_dia'][$fechaJornada])) {
                    $resumen['_dia'][$fechaJornada] = 0;
                }

                $quedaDiaria = $resumen['_dia'][$fechaJornada] < $resumen['_diaria_min'];
                $quedaSemanal = $resumen['_semana'][$claveSemana] < $resumen['_semanal_min'];
                $esOrdinaria = !$esFestivo && !$esDescanso && $enHorario && $quedaDiaria && $quedaSemanal;
                $esExtra = !$enHorario || !$quedaDiaria || !$quedaSemanal;

                $resumen['total_trabajadas']++;
                if ($esNoche) {
                    $resumen['total_nocturna']++;
                }

                if ($esFestivo) {
                    $resumen['festiva']++;
                    if ($esNoche || $esExtra) {
                        $resumen['nocturna_extra_dominical_festivo']++;
                    }
                } elseif ($esDescanso) {
                    $resumen['dominical_descanso']++;
                    if ($esNoche || $esExtra) {
                        $resumen['nocturna_extra_dominical_festivo']++;
                    }
                } elseif ($esOrdinaria) {
                    if ($esNoche) {
                        $resumen['ordinaria_nocturna']++;
                    } else {
                        $resumen['ordinaria_diurna']++;
                    }
                    $resumen['_dia'][$fechaJornada]++;
                    $resumen['_semana'][$claveSemana]++;
                }

                if ($esExtra) {
                    $resumen['total_extra']++;
                    if ($esNoche) {
                        $resumen['extra_nocturna']++;
                    } else {
                        $resumen['extra_diurna']++;
                    }
                }

                $cursor += 60;
            }
        }
    }

    private function intervalosTrabajados(array $fila)
    {
        $fecha = isset($fila['seg_fechaingreso']) ? \formatear_fecha_asistencia($fila['seg_fechaingreso']) : '';
        $ingreso = isset($fila['seg_horaingreso']) ? $fila['seg_horaingreso'] : '';
        $salida = isset($fila['seg_horaSalida']) ? $fila['seg_horaSalida'] : '';
        if ($fecha === '' || \hora_asistencia_vacia($ingreso) || \hora_asistencia_vacia($salida)) {
            return array();
        }

        $inicio = strtotime($fecha . ' ' . $this->normalizarHora($ingreso));
        $fin = strtotime($fecha . ' ' . $this->normalizarHora($salida));
        if ($inicio === false || $fin === false) {
            return array();
        }
        if ($fin <= $inicio) {
            $fin += 86400;
        }

        $pausas = array();
        $saleAlmuerzo = isset($fila['seg_ingresoAlmuerzo']) ? $fila['seg_ingresoAlmuerzo'] : '';
        $regresaAlmuerzo = isset($fila['seg_salioAlmuerzo']) ? $fila['seg_salioAlmuerzo'] : '';
        if (!\hora_asistencia_vacia($saleAlmuerzo) && !\hora_asistencia_vacia($regresaAlmuerzo)) {
            $pausas[] = $this->parPausa($fecha, $saleAlmuerzo, $regresaAlmuerzo, $inicio, $fin);
        }
        $saleBreak = isset($fila['seg_ingresoBreak']) ? $fila['seg_ingresoBreak'] : '';
        $regresaBreak = isset($fila['seg_salioBreak']) ? $fila['seg_salioBreak'] : '';
        if (!\hora_asistencia_vacia($saleBreak) && !\hora_asistencia_vacia($regresaBreak)) {
            $pausas[] = $this->parPausa($fecha, $saleBreak, $regresaBreak, $inicio, $fin);
        }

        $segmentos = array(array($inicio, $fin));
        foreach ($pausas as $pausa) {
            if ($pausa === null) {
                continue;
            }
            $nuevos = array();
            foreach ($segmentos as $seg) {
                $nuevos = array_merge($nuevos, $this->restarIntervalo($seg[0], $seg[1], $pausa[0], $pausa[1]));
            }
            $segmentos = $nuevos;
        }

        return $segmentos;
    }

    private function parPausa($fecha, $sale, $regresa, $inicioJornada, $finJornada)
    {
        $a = strtotime($fecha . ' ' . $this->normalizarHora($sale));
        $b = strtotime($fecha . ' ' . $this->normalizarHora($regresa));
        if ($a === false || $b === false) {
            return null;
        }
        if ($b <= $a) {
            $b += 86400;
        }
        $a = max($a, $inicioJornada);
        $b = min($b, $finJornada);
        if ($b <= $a) {
            return null;
        }

        return array($a, $b);
    }

    private function restarIntervalo($ini, $fin, $pIni, $pFin)
    {
        if ($pFin <= $ini || $pIni >= $fin) {
            return array(array($ini, $fin));
        }
        $out = array();
        if ($pIni > $ini) {
            $out[] = array($ini, min($pIni, $fin));
        }
        if ($pFin < $fin) {
            $out[] = array(max($pFin, $ini), $fin);
        }

        return $out;
    }

    private function esNocturna($minutosDia)
    {
        $inicioN = $this->horaAMinutos($this->config['hora_inicio_nocturna']);
        $finN = $this->horaAMinutos($this->config['hora_fin_nocturna']);

        return $minutosDia >= $inicioN || $minutosDia < $finN;
    }

    private function enHorario($minutosDia, $inicio, $fin)
    {
        if ($inicio === $fin) {
            return true;
        }
        if ($fin > $inicio) {
            return $minutosDia >= $inicio && $minutosDia < $fin;
        }

        return $minutosDia >= $inicio || $minutosDia < $fin;
    }

    private function horaAMinutos($hora)
    {
        $partes = explode(':', $this->normalizarHora($hora));

        return ((int) $partes[0]) * 60 + (int) $partes[1];
    }

    private function normalizarHora($hora)
    {
        $hora = trim((string) $hora);
        if (preg_match('/^(\d{1,2}:\d{2}:\d{2})/', $hora, $m)) {
            return $m[1];
        }
        if (preg_match('/^(\d{1,2}:\d{2})/', $hora, $m)) {
            return $m[1] . ':00';
        }

        return '00:00:00';
    }

    private function horaConfig(array $horario, $campo, $claveDefault)
    {
        if (isset($horario[$campo]) && trim((string) $horario[$campo]) !== '') {
            return $this->normalizarHora($horario[$campo]);
        }

        return $this->config[$claveDefault];
    }

    private function enteroConfig(array $horario, $campoMinutos, $claveHoras, $factor)
    {
        if (isset($horario[$campoMinutos]) && (int) $horario[$campoMinutos] > 0) {
            return (int) $horario[$campoMinutos];
        }

        return (int) round(((float) $this->config[$claveHoras]) * $factor);
    }

    private function nombreDia($dow)
    {
        $nombres = array('Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado');

        return isset($nombres[$dow]) ? $nombres[$dow] : 'Domingo';
    }
}
