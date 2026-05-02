<?php

namespace Huella\Services;

use Huella\Repositories\BiometricRepository;

class AttendanceService
{
    private $repository;

    public function __construct(BiometricRepository $repository)
    {
        $this->repository = $repository;
    }

    public function registerEvent($documento, $fechaActual, $horaActual)
    {
        if ($documento === '') {
            return;
        }

        $attendance = $this->repository->getAttendanceRow($documento, $fechaActual);
        if (!$attendance) {
            return;
        }

        $horacero = '00:00:00';
        $minutos = 10;
        $horaIngreso = isset($attendance['seg_horaingreso']) ? $attendance['seg_horaingreso'] : $horacero;
        $horaAlmuerzo = isset($attendance['seg_ingresoAlmuerzo']) ? $attendance['seg_ingresoAlmuerzo'] : $horacero;
        $horaRegreso = isset($attendance['seg_salioAlmuerzo']) ? $attendance['seg_salioAlmuerzo'] : $horacero;
        $horaSalida = isset($attendance['seg_horaSalida']) ? $attendance['seg_horaSalida'] : $horacero;

        if ($horaIngreso === $horacero) {
            $this->repository->updateAttendanceField($documento, $fechaActual, 'seg_horaingreso', $horaActual);
            return;
        }

        if ($horaAlmuerzo === $horacero && $horaActual > $this->sumMinutes($horaIngreso, $minutos)) {
            $this->repository->updateAttendanceField($documento, $fechaActual, 'seg_ingresoAlmuerzo', $horaActual);
            return;
        }

        if ($horaRegreso === $horacero && $horaActual > $this->sumMinutes($horaAlmuerzo, $minutos)) {
            $this->repository->updateAttendanceField($documento, $fechaActual, 'seg_salioAlmuerzo', $horaActual);
            return;
        }

        if ($horaSalida === $horacero && $horaActual > $this->sumMinutes($horaRegreso, $minutos)) {
            $this->repository->updateAttendanceField($documento, $fechaActual, 'seg_horaSalida', $horaActual);
        }
    }

    private function sumMinutes($hora, $minutes)
    {
        return date('H:i:s', strtotime($hora) + ($minutes * 60));
    }
}
