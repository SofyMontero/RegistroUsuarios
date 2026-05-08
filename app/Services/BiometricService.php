<?php

namespace Huella\Services;

use Huella\Repositories\BiometricRepository;

class BiometricService
{
    private $repository;
    private $attendanceService;

    public function __construct(BiometricRepository $repository, AttendanceService $attendanceService)
    {
        $this->repository = $repository;
        $this->attendanceService = $attendanceService;
    }

    public function pollByToken($token, $timestamp, $shouldRegisterAttendance)
    {
        $currentTimestamp = (int) $timestamp;
        $dbTimestamp = 0;

        while ($dbTimestamp <= $currentTimestamp) {
            $lastUpdate = $this->repository->getLatestUpdateTimeByToken($token);
            usleep(100000);
            clearstatcache();

            if ($lastUpdate && !empty($lastUpdate['update_time'])) {
                $dbTimestamp = strtotime($lastUpdate['update_time']);
            } else {
                break;
            }
        }

        $temp = $this->repository->getLatestTempByToken($token);
        if (!$temp) {
            return array(
                'id' => $token,
                'timestamp' => $currentTimestamp,
                'texto' => '---',
                'statusPlantilla' => 'Esperando lectura',
                'nombre' => '------',
                'documento' => '',
                'imgHuella' => null,
                'tipo' => '',
                'foto_usu' => 'default.png',
            );
        }

        if ($shouldRegisterAttendance && !empty($temp['documento'])) {
            $this->attendanceService->registerEvent($temp['documento'], date('Y-m-d'), date('H:i:s'));
        }

        $imagenUsuario = $this->repository->getFingerprintImageByDocument($temp['documento']);

        return array(
            'id' => $temp['pc_serial'],
            'timestamp' => strtotime($temp['update_time']),
            'texto' => $temp['texto'],
            'statusPlantilla' => $temp['statusPlantilla'],
            'nombre' => $temp['nombre'],
            'documento' => $temp['documento'],
            'imgHuella' => $temp['imgHuella'],
            'tipo' => $temp['opc'],
            'foto_usu' => ($imagenUsuario && !empty($imagenUsuario['ext'])) ? $imagenUsuario['ext'] : 'default.png',
        );
    }
}
