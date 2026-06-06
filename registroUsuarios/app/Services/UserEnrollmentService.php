<?php

namespace Huella\Services;

use Huella\Repositories\BiometricRepository;

class UserEnrollmentService
{
    private $repository;

    public function __construct(BiometricRepository $repository)
    {
        $this->repository = $repository;
    }

    public function create(array $post, array $files)
    {
        $documento = isset($post['documento']) ? trim($post['documento']) : '';
        $nombre = isset($post['nombre']) ? trim($post['nombre']) : '';
        $token = isset($post['token']) ? trim($post['token']) : '';

        if ($documento === '' || $nombre === '' || $token === '') {
            return array('filas' => 0, 'message' => 'Faltan datos obligatorios');
        }

        if ($this->repository->getFingerprintUserByDocument($documento)) {
            return array('filas' => 0, 'message' => 'El usuario ya tiene huella registrada');
        }

        $captura = $this->repository->getCaptureDataByToken($token);
        if (!$captura) {
            return array('filas' => 0, 'message' => 'No hay una captura activa para este equipo. Activa el sensor e intenta de nuevo');
        }

        if (empty($captura['huella']) || empty($captura['imgHuella'])) {
            $detalle = !empty($captura['statusPlantilla']) ? $captura['statusPlantilla'] : 'La huella aun no fue capturada completamente';
            return array('filas' => 0, 'message' => 'No se puede guardar porque no hay una huella valida capturada. Detalle: ' . $detalle);
        }

        $fotoBinaria = null;
        $imagen = null;
        if (isset($files['foto']) && is_uploaded_file($files['foto']['tmp_name'])) {
            $tipo = $files['foto']['type'];
            if ($tipo === 'image/png' || $tipo === 'image/jpeg') {
                $fotoBinaria = file_get_contents($files['foto']['tmp_name']);
                $imagen = basename($files['foto']['name']);
                $destino = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'imagenes' . DIRECTORY_SEPARATOR . $imagen;
                if (!move_uploaded_file($files['foto']['tmp_name'], $destino)) {
                    return array('filas' => 0, 'message' => 'No fue posible guardar la fotografia del usuario');
                }
            } else {
                return array('filas' => 0, 'message' => 'La fotografia debe estar en formato PNG o JPG');
            }
        }

        $this->repository->markUserHasFingerprint($documento);
        $usuarioCreado = $this->repository->createFingerprintUser($documento, $nombre, $fotoBinaria, $imagen);
        if ($usuarioCreado < 1) {
            return array('filas' => 0, 'message' => 'No fue posible crear el registro base del usuario con huella');
        }

        $row = $this->repository->createFingerprintTemplate($documento, $token);
        if ($row < 1) {
            return array('filas' => 0, 'message' => 'Se guardaron los datos del usuario, pero no fue posible registrar la plantilla de huella');
        }

        $this->repository->clearTempByToken($token);

        return array(
            'filas' => $row,
            'message' => $row > 0 ? 'Usuario creado con exito' : 'No fue posible crear el usuario',
        );
    }
}
