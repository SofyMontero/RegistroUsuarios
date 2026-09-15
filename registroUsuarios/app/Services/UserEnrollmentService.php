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
        $sede = isset($post['sede']) ? trim((string) $post['sede']) : '';
        $telefono = isset($post['telefono']) ? trim((string) $post['telefono']) : '';
        $genero = isset($post['genero']) ? trim((string) $post['genero']) : '';

        if ($documento === '' || $nombre === '' || $token === '') {
            return array('filas' => 0, 'message' => 'Faltan datos obligatorios');
        }

        if (normalizar_genero_usuario($genero) === '') {
            return array('filas' => 0, 'message' => 'Seleccione el género del colaborador');
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

        $imagen = foto_por_genero($genero);
        $fotoBinaria = contenido_foto_usuario($imagen);

        $this->repository->ensureCollaborator($documento, $nombre, $telefono, $sede, $genero);
        $this->guardarHorarioDesdePost($post, $documento);
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
            'message' => $row > 0
                ? 'Usuario y huella guardados. Cierre el plugin biométrico y vuélvalo a abrir para dar ingreso al usuario creado en este momento.'
                : 'No fue posible crear el usuario',
        );
    }

    /**
     * Enrollment desde React + WebSDK: recibe plantilla e imagen sin pasar por huellas_temp.
     */
    public function createDirect(array $data)
    {
        $documento = isset($data['documento']) ? trim($data['documento']) : '';
        $nombre = isset($data['nombre']) ? trim($data['nombre']) : '';
        $huella = isset($data['huella']) ? trim($data['huella']) : '';
        $imgHuella = isset($data['imgHuella']) ? trim($data['imgHuella']) : '';
        $sede = isset($data['sede']) ? trim((string) $data['sede']) : '';
        $genero = isset($data['genero']) ? trim((string) $data['genero']) : '';

        if ($documento === '' || $nombre === '' || $huella === '') {
            return array('filas' => 0, 'message' => 'Faltan datos obligatorios (documento, nombre, huella)');
        }

        if ($this->repository->getFingerprintUserByDocument($documento)) {
            return array('filas' => 0, 'message' => 'El usuario ya tiene huella registrada');
        }

        $imagen = foto_por_genero($genero);
        $fotoBinaria = contenido_foto_usuario($imagen);

        $this->repository->ensureCollaborator($documento, $nombre, '', $sede, $genero);
        $this->guardarHorarioDesdePost($data, $documento);
        $this->repository->markUserHasFingerprint($documento);
        $usuarioCreado = $this->repository->createFingerprintUser($documento, $nombre, $fotoBinaria, $imagen);
        if ($usuarioCreado < 1) {
            return array('filas' => 0, 'message' => 'No fue posible crear el registro base del usuario con huella');
        }

        $row = $this->repository->createFingerprintTemplateDirect(
            $documento,
            $huella,
            $imgHuella !== '' ? $imgHuella : $huella
        );
        if ($row < 1) {
            return array('filas' => 0, 'message' => 'Se guardaron los datos del usuario, pero no fue posible registrar la plantilla de huella');
        }

        return array(
            'filas' => $row,
            'message' => 'Usuario registrado via WebSDK. Cierre el plugin biométrico y vuélvalo a abrir para dar ingreso al usuario creado en este momento.',
        );
    }

    private function guardarHorarioDesdePost(array $post, $documento)
    {
        $config = require dirname(__DIR__, 2) . '/config/jornada_laboral.php';
        $horaInicio = $this->normalizarHoraHorario(
            isset($post['hora_inicio']) ? $post['hora_inicio'] : '',
            $config['hora_inicio']
        );
        $horaFin = $this->normalizarHoraHorario(
            isset($post['hora_fin']) ? $post['hora_fin'] : '',
            $config['hora_fin']
        );
        if ($horaInicio === null || $horaFin === null) {
            return;
        }

        $diariaHoras = isset($post['jornada_diaria']) ? (float) str_replace(',', '.', (string) $post['jornada_diaria']) : (float) $config['jornada_diaria_horas'];
        $semanalHoras = isset($post['jornada_semanal']) ? (float) str_replace(',', '.', (string) $post['jornada_semanal']) : (float) $config['jornada_semanal_horas'];
        $diaDescanso = isset($post['dia_descanso']) ? (int) $post['dia_descanso'] : (int) $config['dia_descanso'];

        if ($diariaHoras < 1 || $diariaHoras > 24) {
            $diariaHoras = (float) $config['jornada_diaria_horas'];
        }
        if ($semanalHoras < 1 || $semanalHoras > 72) {
            $semanalHoras = (float) $config['jornada_semanal_horas'];
        }
        if ($diaDescanso < 0 || $diaDescanso > 6) {
            $diaDescanso = (int) $config['dia_descanso'];
        }

        try {
            $this->repository->updateWorkerSchedule(
                $documento,
                $horaInicio,
                $horaFin,
                (int) round($diariaHoras * 60),
                (int) round($semanalHoras * 60),
                $diaDescanso
            );
        } catch (\Throwable $e) {
            // Columnas de jornada opcionales segun esquema.
        }
    }

    private function normalizarHoraHorario($valor, $fallback)
    {
        $valor = trim((string) $valor);
        if ($valor === '') {
            $valor = (string) $fallback;
        }
        if (preg_match('/^(\d{1,2}):(\d{2})(?::(\d{2}))?$/', $valor, $partes)) {
            $hora = (int) $partes[1];
            $minuto = (int) $partes[2];
            $segundo = isset($partes[3]) ? (int) $partes[3] : 0;
            if ($hora <= 23 && $minuto <= 59 && $segundo <= 59) {
                return sprintf('%02d:%02d:%02d', $hora, $minuto, $segundo);
            }
        }

        return null;
    }
}
