<?php

namespace Huella\Services;

use Huella\Repositories\BiometricRepository;

require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'tiempo_asistencia.php';

class AttendanceService
{
    private $repository;

    public function __construct(BiometricRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Marca el siguiente evento del día.
     * Orden: ingreso → sale almuerzo → regresa almuerzo → (break 15 min, opcional) → salida.
     * El break solo se marca si ocurre después del almuerzo y dentro de 3 horas
     * del regreso; si marcan más tarde, se registra la salida.
     *
     * @return array{evento:string,excedido:bool,minutos:?int,permitidos:?int,mensaje:string}
     */
    public function registerEvent($documento, $fechaActual, $horaActual)
    {
        $vacio = array(
            'evento' => '',
            'excedido' => false,
            'minutos' => null,
            'permitidos' => null,
            'mensaje' => '',
        );

        if ($documento === '') {
            return $vacio;
        }

        $this->repository->ensureBreakColumns();
        $this->repository->ensureTodayAttendanceRowsForActiveUsers($fechaActual);

        $attendance = $this->repository->getAttendanceRow($documento, $fechaActual);
        if (!$attendance) {
            return $vacio;
        }

        $horacero = HORA_CERO_ASISTENCIA;
        $minutosEntreMarcas = 10;
        // El break va después del almuerzo. Si ya pasaron más de 3 horas
        // desde el regreso, la siguiente marca es salida (no se come el break).
        $minutosVentanaBreak = 180;
        $horaIngreso = isset($attendance['seg_horaingreso']) ? $attendance['seg_horaingreso'] : $horacero;
        $horaSaleAlmuerzo = isset($attendance['seg_ingresoAlmuerzo']) ? $attendance['seg_ingresoAlmuerzo'] : $horacero;
        $horaRegresaAlmuerzo = isset($attendance['seg_salioAlmuerzo']) ? $attendance['seg_salioAlmuerzo'] : $horacero;
        $horaSaleBreak = isset($attendance['seg_ingresoBreak']) ? $attendance['seg_ingresoBreak'] : $horacero;
        $horaRegresaBreak = isset($attendance['seg_salioBreak']) ? $attendance['seg_salioBreak'] : $horacero;
        $horaSalida = isset($attendance['seg_horaSalida']) ? $attendance['seg_horaSalida'] : $horacero;

        if (hora_asistencia_vacia($horaIngreso)) {
            $this->repository->updateAttendanceField($documento, $fechaActual, 'seg_horaingreso', $horaActual);
            return $this->resultadoEvento('ingreso', false, null, null, 'Ingreso registrado');
        }

        if (hora_asistencia_vacia($horaSaleAlmuerzo) && $horaActual > $this->sumMinutes($horaIngreso, $minutosEntreMarcas)) {
            $this->repository->updateAttendanceField($documento, $fechaActual, 'seg_ingresoAlmuerzo', $horaActual);
            return $this->resultadoEvento('almuerzo_sale', false, null, null, 'Salida a almuerzo');
        }

        if (hora_asistencia_vacia($horaRegresaAlmuerzo)
            && !hora_asistencia_vacia($horaSaleAlmuerzo)
            && $horaActual > $this->sumMinutes($horaSaleAlmuerzo, $minutosEntreMarcas)
        ) {
            $this->repository->updateAttendanceField($documento, $fechaActual, 'seg_salioAlmuerzo', $horaActual);
            $minutos = minutos_entre_horas($horaSaleAlmuerzo, $horaActual);
            $excedido = tiempo_pausa_excedido($horaSaleAlmuerzo, $horaActual, MINUTOS_ALMUERZO);

            return $this->resultadoEvento(
                'almuerzo_regreso',
                $excedido,
                $minutos,
                MINUTOS_ALMUERZO,
                $excedido
                    ? ('Almuerzo excedido: ' . $minutos . ' min (máximo ' . MINUTOS_ALMUERZO . ')')
                    : 'Regreso de almuerzo'
            );
        }

        if (hora_asistencia_vacia($horaSaleBreak)
            && !hora_asistencia_vacia($horaRegresaAlmuerzo)
            && $horaActual > $this->sumMinutes($horaRegresaAlmuerzo, $minutosEntreMarcas)
            && $horaActual <= $this->sumMinutes($horaRegresaAlmuerzo, $minutosVentanaBreak)
        ) {
            $this->repository->updateAttendanceField($documento, $fechaActual, 'seg_ingresoBreak', $horaActual);
            return $this->resultadoEvento('break_sale', false, null, null, 'Salida a break (15 min)');
        }

        if (hora_asistencia_vacia($horaRegresaBreak)
            && !hora_asistencia_vacia($horaSaleBreak)
            && $horaActual > $this->sumMinutes($horaSaleBreak, $minutosEntreMarcas)
        ) {
            $this->repository->updateAttendanceField($documento, $fechaActual, 'seg_salioBreak', $horaActual);
            $minutos = minutos_entre_horas($horaSaleBreak, $horaActual);
            $excedido = tiempo_pausa_excedido($horaSaleBreak, $horaActual, MINUTOS_BREAK);

            return $this->resultadoEvento(
                'break_regreso',
                $excedido,
                $minutos,
                MINUTOS_BREAK,
                $excedido
                    ? ('Break excedido: ' . $minutos . ' min (máximo ' . MINUTOS_BREAK . ')')
                    : 'Regreso de break'
            );
        }

        $horaReferenciaSalida = !hora_asistencia_vacia($horaRegresaBreak)
            ? $horaRegresaBreak
            : $horaRegresaAlmuerzo;

        if (hora_asistencia_vacia($horaSalida)
            && !hora_asistencia_vacia($horaReferenciaSalida)
            && $horaActual > $this->sumMinutes($horaReferenciaSalida, $minutosEntreMarcas)
        ) {
            $this->repository->updateAttendanceField($documento, $fechaActual, 'seg_horaSalida', $horaActual);
            return $this->resultadoEvento('salida', false, null, null, 'Salida registrada');
        }

        return $vacio;
    }

    private function resultadoEvento($evento, $excedido, $minutos, $permitidos, $mensaje)
    {
        return array(
            'evento' => $evento,
            'excedido' => (bool) $excedido,
            'minutos' => $minutos,
            'permitidos' => $permitidos,
            'mensaje' => $mensaje,
        );
    }

    private function sumMinutes($hora, $minutes)
    {
        return date('H:i:s', strtotime($hora) + ($minutes * 60));
    }
}
