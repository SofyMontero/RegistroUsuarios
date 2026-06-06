<?php

namespace Huella\Controllers;

use Huella\Core\Database;
use Huella\Repositories\BiometricRepository;
use Huella\Services\AttendanceService;
use Huella\Services\BiometricService;
use Huella\Services\UserEnrollmentService;

class BiometricController
{
    private $repository;
    private $attendanceService;
    private $biometricService;
    private $userEnrollmentService;

    public function __construct()
    {
        $database = new Database();
        $this->repository = new BiometricRepository($database);
        $this->attendanceService = new AttendanceService($this->repository);
        $this->biometricService = new BiometricService($this->repository, $this->attendanceService);
        $this->userEnrollmentService = new UserEnrollmentService($this->repository);
    }

    public function pollVerify(array $request)
    {
        header('Content-Type: application/json; charset=utf-8');
        $token = isset($request['token']) ? $request['token'] : '';
        $timestamp = isset($request['timestamp']) && $request['timestamp'] !== 'null' ? $request['timestamp'] : 0;
        echo json_encode($this->biometricService->pollByToken($token, $timestamp, true));
    }

    public function pollEnroll(array $request)
    {
        header('Content-Type: application/json; charset=utf-8');
        $token = isset($request['token']) ? $request['token'] : '';
        $timestamp = isset($request['timestamp']) && $request['timestamp'] !== 'null' ? $request['timestamp'] : 0;
        echo json_encode($this->biometricService->pollByToken($token, $timestamp, false));
    }

    public function createUser(array $post, array $files)
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            echo json_encode($this->userEnrollmentService->create($post, $files));
        } catch (\Throwable $exception) {
            http_response_code(500);
            echo json_encode(array(
                'filas' => 0,
                'message' => 'Error interno al guardar el usuario: ' . $exception->getMessage(),
            ));
        }
    }

    public function registerByDocument(array $request)
    {
        header('Content-Type: application/json; charset=utf-8');

        $cedula = isset($request['param1']) ? trim($request['param1']) : '';
        if ($cedula === '') {
            echo json_encode(array(
                'success' => false,
                'message' => 'No se envio una cedula valida',
                'documento' => '',
                'nombre' => '',
                'foto_usu' => 'default.png',
            ));
            return;
        }

        $allowed = $this->repository->isDocumentAllowedForManualRegister($cedula);
        if (!$allowed) {
            echo json_encode(array(
                'success' => false,
                'message' => 'No se encontro esta cedula, revisa el numero',
                'documento' => '',
                'nombre' => '',
                'foto_usu' => 'default.png',
            ));
            return;
        }

        $this->attendanceService->registerEvent($cedula, date('Y-m-d'), date('H:i:s'));
        $user = $this->repository->getUserNameByDocument($cedula);
        $imagenUsuario = $this->repository->getFingerprintImageByDocument($cedula);

        echo json_encode(array(
            'success' => true,
            'message' => 'Registro ingresado correctamente',
            'documento' => $cedula,
            'nombre' => $user && !empty($user['usu_nombre']) ? $user['usu_nombre'] : '',
            'foto_usu' => $imagenUsuario && !empty($imagenUsuario['ext']) ? $imagenUsuario['ext'] : 'default.png',
        ));
    }
}
