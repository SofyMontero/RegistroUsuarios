<?php

namespace Huella\Controllers;

use Huella\Core\Database;
use Huella\Repositories\BiometricRepository;
use Huella\Services\AttendanceService;
use Huella\Services\BiometricService;
use Huella\Services\UserEnrollmentService;

class BiometricController
{
    /**
     * Hash bcrypt de la clave de ingreso manual por documento (no almacenar la clave en texto plano).
     * Clave actual: Americas*hab*2025 — regenerar con password_hash() si se cambia.
     */
    private const MANUAL_DOC_ACCESS_HASH = '$2b$12$W4Q5lyKCwerQLynwDLNAyOECPQofgrP/XZu/ylAakPLXSnnujEnJW';

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

        $claveAcceso = isset($request['clave_acceso']) ? (string) $request['clave_acceso'] : '';
        if ($claveAcceso === '' || !password_verify($claveAcceso, self::MANUAL_DOC_ACCESS_HASH)) {
            echo json_encode(array(
                'success' => false,
                'message' => 'Clave de acceso incorrecta. Es obligatoria para el ingreso manual por documento.',
                'documento' => '',
                'nombre' => '',
                'foto_usu' => 'default.png',
            ));
            return;
        }

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
