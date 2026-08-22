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

    public function pollByToken($token, $timestamp, $shouldRegisterAttendance, $sede = '')
    {
        $currentTimestamp = (int) $timestamp;
        $dbTimestamp = 0;
        $sede = trim((string) $sede);

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
                'foto_usu' => 'mujer.png',
                'sede_ok' => true,
                'evento' => '',
                'excedido' => false,
                'minutos' => null,
                'permitidos' => null,
                'mensaje_marcacion' => '',
            );
        }

        $documento = isset($temp['documento']) ? $temp['documento'] : '';
        $sedeOk = true;
        $nombre = !empty($temp['nombre']) ? $temp['nombre'] : '------';
        $texto = $temp['texto'];
        $marcacion = array(
            'evento' => '',
            'excedido' => false,
            'minutos' => null,
            'permitidos' => null,
            'mensaje' => '',
        );

        if ($shouldRegisterAttendance && $documento !== '') {
            $sedeOk = $this->repository->userBelongsToSede($documento, $sede);
            if ($sedeOk) {
                $marcacion = $this->attendanceService->registerEvent($documento, date('Y-m-d'), date('H:i:s'));
                if (!empty($marcacion['mensaje'])) {
                    $texto = $marcacion['mensaje'];
                }
            } else {
                $nombreSede = $this->repository->getSedeNombreById($sede);
                $texto = $nombreSede !== ''
                    ? ('Usuario no pertenece a la sede ' . $nombreSede)
                    : 'Usuario no pertenece a esta sede';
                $nombre = '------';
            }
        }

        $imagenUsuario = $this->repository->getFingerprintImageByDocument($documento);

        return array(
            'id' => $temp['pc_serial'],
            'timestamp' => strtotime($temp['update_time']),
            'texto' => $texto,
            'statusPlantilla' => $sedeOk ? $temp['statusPlantilla'] : 'Sede no coincide',
            'nombre' => $nombre,
            'documento' => $sedeOk ? $documento : '',
            'imgHuella' => $temp['imgHuella'],
            'tipo' => $temp['opc'],
            'foto_usu' => ($imagenUsuario && !empty($imagenUsuario['ext'])) ? $imagenUsuario['ext'] : 'mujer.png',
            'sede_ok' => $sedeOk,
            'evento' => $marcacion['evento'],
            'excedido' => !empty($marcacion['excedido']),
            'minutos' => $marcacion['minutos'],
            'permitidos' => $marcacion['permitidos'],
            'mensaje_marcacion' => $marcacion['mensaje'],
        );
    }
}
