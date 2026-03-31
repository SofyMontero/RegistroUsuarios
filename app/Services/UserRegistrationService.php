<?php

namespace Huella\Services;

use Huella\Repositories\BiometricRepository;

class UserRegistrationService
{
    private $repository;

    public function __construct(BiometricRepository $repository)
    {
        $this->repository = $repository;
    }

    public function create(array $post)
    {
        $documento = isset($post['documento']) ? trim($post['documento']) : '';
        $nombre = isset($post['nombre']) ? trim($post['nombre']) : '';
        $telefono = isset($post['telefono']) ? trim($post['telefono']) : '';
        $sede = isset($post['sede']) ? trim($post['sede']) : '';

        if ($documento === '' || $nombre === '' || $sede === '') {
            return array(
                'filas' => 0,
                'message' => 'Documento, nombre y sede son obligatorios',
            );
        }

        if ($this->repository->userExistsInMainTable($documento)) {
            return array(
                'filas' => 0,
                'message' => 'Ya existe un usuario registrado con ese documento',
            );
        }

        if (!$this->repository->headquartersExists($sede)) {
            return array(
                'filas' => 0,
                'message' => 'La sede seleccionada no es valida',
            );
        }

        $rowCount = $this->repository->createMainUser($documento, $nombre, $telefono, $sede);

        if ($rowCount < 1) {
            return array(
                'filas' => 0,
                'message' => 'No fue posible registrar el usuario. Verifica que la tabla usuarios tenga el campo de sede configurado',
            );
        }

        return array(
            'filas' => $rowCount,
            'message' => 'Usuario registrado con exito',
        );
    }
}
