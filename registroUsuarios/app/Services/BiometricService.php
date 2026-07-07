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
        $maxWaitMicroseconds = 2500000;
        $waited = 0;

        while ($waited < $maxWaitMicroseconds) {
            $lastUpdate = $this->repository->getLatestUpdateTimeByToken($token);
            $dbTimestamp = 0;

            if ($lastUpdate && !empty($lastUpdate['update_time'])) {
                $parsed = strtotime($lastUpdate['update_time']);
                $dbTimestamp = $parsed !== false ? (int) $parsed : 0;
            }

            if ($dbTimestamp > $currentTimestamp) {
                break;
            }

            usleep(100000);
            $waited += 100000;
            clearstatcache();
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
                'foto_usu' => 'mujer.png',
            );
        }

        if ($shouldRegisterAttendance && !empty($temp['documento'])) {
            $this->attendanceService->registerEvent($temp['documento'], date('Y-m-d'), date('H:i:s'));
        }

        $imagenUsuario = $this->repository->getFingerprintImageByDocument($temp['documento']);
        $nombre = !empty($temp['nombre']) ? $temp['nombre'] : '------';
        $dbTimestamp = 0;
        if (!empty($temp['update_time'])) {
            $parsed = strtotime($temp['update_time']);
            $dbTimestamp = $parsed !== false ? (int) $parsed : time();
        }

        return array(
            'id' => $temp['pc_serial'],
            'timestamp' => $dbTimestamp,
            'texto' => $temp['texto'],
            'statusPlantilla' => $temp['statusPlantilla'],
            'nombre' => $nombre,
            'documento' => $temp['documento'],
            'imgHuella' => $this->normalizarImgHuella($temp['imgHuella']),
            'tipo' => $temp['opc'],
            'foto_usu' => ($imagenUsuario && !empty($imagenUsuario['ext'])) ? $imagenUsuario['ext'] : 'mujer.png',
        );
    }

    private function normalizarImgHuella($valor)
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        if (!is_string($valor)) {
            return null;
        }

        if (preg_match('/^[A-Za-z0-9+\/=\r\n]+$/', $valor)) {
            return preg_replace('/\s+/', '', $valor);
        }

        return base64_encode($valor);
    }
}
