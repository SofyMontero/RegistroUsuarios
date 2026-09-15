<?php

namespace Huella\Repositories;

use Huella\Core\Database;

class BiometricRepository
{
    private $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function getLatestUpdateTimeByToken($token)
    {
        return $this->db->fetchOne(
            "SELECT update_time FROM huellas_temp WHERE pc_serial = :token ORDER BY update_time DESC LIMIT 1",
            array('token' => $token)
        );
    }

    public function getLatestTempByToken($token)
    {
        return $this->db->fetchOne(
            "SELECT pc_serial, imgHuella, update_time, texto, statusPlantilla, documento, nombre, opc, foto_usu
             FROM huellas_temp
             WHERE pc_serial = :token
             ORDER BY update_time DESC
             LIMIT 1",
            array('token' => $token)
        );
    }

    public function getCaptureDataByToken($token)
    {
        return $this->db->fetchOne(
            "SELECT huella, imgHuella, statusPlantilla, texto
             FROM huellas_temp
             WHERE pc_serial = :token
             ORDER BY update_time DESC
             LIMIT 1",
            array('token' => $token)
        );
    }

    public function clearTempByToken($token)
    {
        return $this->db->execute(
            "DELETE FROM huellas_temp WHERE pc_serial = :token",
            array('token' => $token)
        );
    }

    public function getFingerprintImageByDocument($documento)
    {
        return $this->db->fetchOne(
            "SELECT foto, ext FROM usuarios_huella WHERE documento = :documento",
            array('documento' => $documento)
        );
    }

    public function ensureBreakColumns()
    {
        $ingreso = $this->db->fetchAll("SHOW COLUMNS FROM seguimientousers LIKE 'seg_ingresoBreak'");
        if (!$ingreso) {
            $this->db->execute(
                "ALTER TABLE seguimientousers ADD COLUMN seg_ingresoBreak TIME NOT NULL DEFAULT '00:00:00'"
            );
        }

        $salida = $this->db->fetchAll("SHOW COLUMNS FROM seguimientousers LIKE 'seg_salioBreak'");
        if (!$salida) {
            $this->db->execute(
                "ALTER TABLE seguimientousers ADD COLUMN seg_salioBreak TIME NOT NULL DEFAULT '00:00:00'"
            );
        }
    }

    public function getAttendanceRow($documento, $fechaActual)
    {
        $this->ensureBreakColumns();

        return $this->db->fetchOne(
            "SELECT seg_iduser, seg_horaingreso, seg_ingresoAlmuerzo, seg_salioAlmuerzo,
                    seg_ingresoBreak, seg_salioBreak, seg_horaSalida
             FROM seguimientousers
             WHERE seg_iduser = :documento AND seg_fechaingreso = :fecha",
            array('documento' => $documento, 'fecha' => $fechaActual)
        );
    }

    public function updateAttendanceField($documento, $fechaActual, $campo, $horaActual)
    {
        $allowed = array(
            'seg_horaingreso',
            'seg_ingresoAlmuerzo',
            'seg_salioAlmuerzo',
            'seg_ingresoBreak',
            'seg_salioBreak',
            'seg_horaSalida',
        );
        if (!in_array($campo, $allowed, true)) {
            return 0;
        }

        return $this->db->execute(
            "UPDATE seguimientousers SET {$campo} = :hora WHERE seg_iduser = :documento AND seg_fechaingreso = :fecha",
            array('hora' => $horaActual, 'documento' => $documento, 'fecha' => $fechaActual)
        );
    }

    public function updateAttendanceHours($documento, $fecha, array $horas)
    {
        $this->ensureBreakColumns();
        $allowed = array(
            'seg_horaingreso',
            'seg_ingresoAlmuerzo',
            'seg_salioAlmuerzo',
            'seg_ingresoBreak',
            'seg_salioBreak',
            'seg_horaSalida',
        );
        $sets = array();
        $params = array(
            'documento' => $documento,
            'fecha' => $fecha,
        );
        foreach ($allowed as $campo) {
            if (!array_key_exists($campo, $horas)) {
                continue;
            }
            $sets[] = $campo . ' = :' . $campo;
            $params[$campo] = $horas[$campo];
        }
        if (!$sets) {
            return 0;
        }

        return $this->db->execute(
            'UPDATE seguimientousers SET ' . implode(', ', $sets)
            . ' WHERE seg_iduser = :documento AND seg_fechaingreso = :fecha',
            $params
        );
    }

    public function isDocumentAllowedForManualRegister($cedula)
    {
        try {
            $row = $this->db->fetchOne(
                "SELECT * FROM ingreso_con_ced WHERE ing_cedula = :cedula LIMIT 1",
                array('cedula' => $cedula)
            );
        } catch (\Throwable $e) {
            return false;
        }

        if (!$row) {
            return false;
        }

        return $this->ingresoCedulaEstaActivo($row);
    }

    public function getUserNameByDocument($cedula)
    {
        return $this->db->fetchOne(
            "SELECT usu_nombre, usu_idsede FROM usuarios WHERE usu_identificacion = :cedula",
            array('cedula' => $cedula)
        );
    }

    /**
     * True si no hay sede de contexto, o el usuario pertenece a esa sede.
     * Usuarios sin usu_idsede se permiten (datos legacy).
     */
    public function userBelongsToSede($documento, $sedeId)
    {
        $sedeId = trim((string) $sedeId);
        if ($sedeId === '') {
            return true;
        }

        $user = $this->db->fetchOne(
            'SELECT usu_idsede FROM usuarios WHERE usu_identificacion = :documento LIMIT 1',
            array('documento' => $documento)
        );
        if (!$user) {
            return false;
        }

        $userSede = isset($user['usu_idsede']) ? trim((string) $user['usu_idsede']) : '';
        if ($userSede === '') {
            return true;
        }

        return $userSede === $sedeId;
    }

    public function getSedeNombreById($sedeId)
    {
        $sedeId = trim((string) $sedeId);
        if ($sedeId === '') {
            return '';
        }

        try {
            $fila = $this->db->fetchOne('SELECT * FROM sedes WHERE idsedes = :id LIMIT 1', array('id' => $sedeId));
        } catch (\Throwable $e) {
            return '';
        }

        if (!$fila) {
            return '';
        }

        $columnasNombre = array('nombre', 'sed_nombre', 'sed_descripcion', 'sed_nom', 'descripcion', 'nom_sede', 'sed_descrip');
        foreach ($columnasNombre as $columna) {
            if (isset($fila[$columna]) && trim((string) $fila[$columna]) !== '') {
                return trim((string) $fila[$columna]);
            }
        }

        return 'Sede ' . $sedeId;
    }

    public function markUserHasFingerprint($documento)
    {
        return $this->db->execute(
            "UPDATE usuarios SET fecha_creacion = NOW(), con_huella = 'si' WHERE usu_identificacion = :documento",
            array('documento' => $documento)
        );
    }

    public function getFingerprintUserByDocument($documento)
    {
        return $this->db->fetchOne(
            "SELECT documento FROM usuarios_huella WHERE documento = :documento",
            array('documento' => $documento)
        );
    }

    public function createFingerprintUser($documento, $nombre, $fotoBinaria, $imagen)
    {
        $imagen = function_exists('foto_usuario_o_default') ? foto_usuario_o_default($imagen) : trim((string) $imagen);
        if ($fotoBinaria === null || $fotoBinaria === false) {
            $fotoBinaria = function_exists('contenido_foto_usuario') ? contenido_foto_usuario($imagen) : '';
        }

        return $this->db->execute(
            "INSERT INTO usuarios_huella (documento, nombre_completo, fecha_crecion, foto, ext)
             VALUES (:documento, :nombre, NOW(), :foto, :ext)",
            array('documento' => $documento, 'nombre' => $nombre, 'foto' => $fotoBinaria, 'ext' => $imagen)
        );
    }

    public function createFingerprintTemplate($documento, $token)
    {
        return $this->db->execute(
            "INSERT INTO huellas (documento, nombre_dedo, huella, imgHuella)
             VALUES (
                :documento,
                'Indice D',
                (SELECT huella FROM huellas_temp WHERE pc_serial = :token ORDER BY update_time DESC LIMIT 1),
                (SELECT imgHuella FROM huellas_temp WHERE pc_serial = :token ORDER BY update_time DESC LIMIT 1)
             )",
            array('documento' => $documento, 'token' => $token)
        );
    }

    /** Enrollment directo desde React WebSDK (sin huellas_temp). */
    public function createFingerprintTemplateDirect($documento, $huella, $imgHuella)
    {
        return $this->db->execute(
            "INSERT INTO huellas (documento, nombre_dedo, huella, imgHuella)
             VALUES (:documento, 'Indice D', :huella, :imgHuella)",
            array('documento' => $documento, 'huella' => $huella, 'imgHuella' => $imgHuella)
        );
    }

    public function ensureTodayAttendanceRowsForActiveUsers($fechaActual)
    {
        $this->ensureBreakColumns();

        return $this->db->execute(
            "INSERT INTO seguimientousers (
                seg_iduser,
                seg_fechaingreso,
                seg_horaingreso,
                seg_ingresoAlmuerzo,
                seg_salioAlmuerzo,
                seg_ingresoBreak,
                seg_salioBreak,
                seg_horaSalida
            )
            SELECT
                u.usu_identificacion,
                :fecha,
                '00:00:00',
                '00:00:00',
                '00:00:00',
                '00:00:00',
                '00:00:00',
                '00:00:00'
            FROM usuarios u
            LEFT JOIN seguimientousers s
                ON s.seg_iduser = u.usu_identificacion
               AND s.seg_fechaingreso = :fecha
            WHERE u.usu_estado = '1'
              AND u.usu_identificacion IS NOT NULL
              AND u.usu_identificacion <> ''
              AND s.seg_iduser IS NULL",
            array('fecha' => $fechaActual)
        );
    }

    /**
     * Sedes para selects administrativos (id + nombre).
     * SELECT * evita depender de un nombre fijo de columna de descripcion.
     * Si la tabla no existe o falla la consulta, devuelve array vacio (evita tumbar toda la pagina).
     */
    public function getHeadquartersList()
    {
        try {
            $filas = $this->db->fetchAll('SELECT * FROM sedes');
        } catch (\Throwable $e) {
            return array();
        }

        $lista = array();
        $columnasNombre = array('nombre', 'sed_nombre', 'sed_descripcion', 'sed_nom', 'descripcion', 'nom_sede', 'sed_descrip');

        foreach ($filas as $fila) {
            $id = null;
            if (isset($fila['idsedes'])) {
                $id = $fila['idsedes'];
            } elseif (isset($fila['id'])) {
                $id = $fila['id'];
            }

            if ($id === null || $id === '') {
                continue;
            }

            $nombre = '';
            foreach ($columnasNombre as $columna) {
                if (isset($fila[$columna]) && trim((string) $fila[$columna]) !== '') {
                    $nombre = trim((string) $fila[$columna]);
                    break;
                }
            }
            if ($nombre === '') {
                $nombre = 'Sede ' . $id;
            }

            $lista[] = array('id' => $id, 'nombre' => $nombre);
        }

        usort(
            $lista,
            function ($a, $b) {
                return strcmp((string) $a['id'], (string) $b['id']);
            }
        );

        return $lista;
    }

    public function getUserRowByIdentification($documento)
    {
        return $this->db->fetchOne(
            'SELECT usu_identificacion FROM usuarios WHERE usu_identificacion = :documento LIMIT 1',
            array('documento' => $documento)
        );
    }

    /**
     * Alta de colaborador en usuarios sin plantilla biometrica (con_huella = no).
     */
    public function createAdministrativeUser($documento, $nombre, $genero = '')
    {
        $genero = function_exists('normalizar_genero_usuario') ? normalizar_genero_usuario($genero) : trim((string) $genero);
        if ($genero !== '') {
            try {
                return $this->db->execute(
                    "INSERT INTO usuarios (usu_identificacion, usu_nombre, usu_estado, con_huella, fecha_creacion, usu_genero)
                     VALUES (:documento, :nombre, '1', 'no', NOW(), :genero)",
                    array('documento' => $documento, 'nombre' => $nombre, 'genero' => $genero)
                );
            } catch (\Throwable $exception) {
                // usu_genero puede no existir o ser incompatible en algunos esquemas.
            }
        }

        return $this->db->execute(
            "INSERT INTO usuarios (usu_identificacion, usu_nombre, usu_estado, con_huella, fecha_creacion)
             VALUES (:documento, :nombre, '1', 'no', NOW())",
            array('documento' => $documento, 'nombre' => $nombre)
        );
    }

    /**
     * Crea el colaborador en usuarios si no existe y actualiza telefono/sede.
     */
    public function ensureCollaborator($documento, $nombre, $telefono = '', $sedeId = '', $genero = '')
    {
        if (!$this->getUserRowByIdentification($documento)) {
            $this->createAdministrativeUser($documento, $nombre, $genero);
        }

        try {
            $this->updateAdministrativeUserExtras($documento, $telefono, $sedeId, $genero);
        } catch (\Throwable $exception) {
            // Telefono, sede o genero: columnas opcionales segun esquema.
        }
    }

    /**
     * Actualiza telefono y/o sede si las columnas existen en la BD (fallos se ignoran en el llamador).
     */
    public function updateAdministrativeUserExtras($documento, $telefono, $sedeId, $genero = '')
    {
        $sets = array();
        $params = array('documento' => $documento);
        if ($telefono !== '') {
            $sets[] = 'usu_telefono = :telefono';
            $params['telefono'] = $telefono;
        }
        if ($sedeId !== '' && $sedeId !== null) {
            $sets[] = 'usu_idsede = :sede';
            $params['sede'] = $sedeId;
        }
        $genero = function_exists('normalizar_genero_usuario') ? normalizar_genero_usuario($genero) : trim((string) $genero);
        if ($genero !== '') {
            $sets[] = 'usu_genero = :genero';
            $params['genero'] = $genero;
        }
        if (empty($sets)) {
            return 0;
        }

        $sql = 'UPDATE usuarios SET ' . implode(', ', $sets) . ' WHERE usu_identificacion = :documento';

        return $this->db->execute($sql, $params);
    }

    public function countUsersBySede($sedeId)
    {
        $row = $this->db->fetchOne(
            'SELECT COUNT(*) AS total FROM usuarios WHERE usu_idsede = :sede',
            array('sede' => $sedeId)
        );

        return $row && isset($row['total']) ? (int) $row['total'] : 0;
    }

    public function createHeadquarters($nombre)
    {
        $nombre = trim((string) $nombre);
        if ($nombre === '') {
            return false;
        }

        $intentos = array(
            'INSERT INTO sedes (nombre, sed_estactual) VALUES (:nombre, 1)',
            'INSERT INTO sedes (sed_nombre, sed_estactual) VALUES (:nombre, 1)',
            'INSERT INTO sedes (sed_descripcion, sed_estactual) VALUES (:nombre, 1)',
            'INSERT INTO sedes (descripcion, sed_estactual) VALUES (:nombre, 1)',
            'INSERT INTO sedes (nombre) VALUES (:nombre)',
        );

        foreach ($intentos as $sql) {
            try {
                $this->db->execute($sql, array('nombre' => $nombre));
                return true;
            } catch (\Throwable $e) {
                continue;
            }
        }

        return false;
    }

    public function deleteHeadquarters($sedeId)
    {
        $sedeId = trim((string) $sedeId);
        if ($sedeId === '') {
            return false;
        }

        if ($this->countUsersBySede($sedeId) > 0) {
            return false;
        }

        try {
            $this->db->execute('DELETE FROM sedes WHERE idsedes = :id', array('id' => $sedeId));
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function ensureJornadaColumns()
    {
        $columnas = array(
            'usu_hora_inicio' => "ALTER TABLE usuarios ADD COLUMN usu_hora_inicio TIME NULL DEFAULT '07:00:00'",
            'usu_hora_fin' => "ALTER TABLE usuarios ADD COLUMN usu_hora_fin TIME NULL DEFAULT '15:00:00'",
            'usu_jornada_diaria_minutos' => 'ALTER TABLE usuarios ADD COLUMN usu_jornada_diaria_minutos INT NULL DEFAULT 420',
            'usu_jornada_semanal_minutos' => 'ALTER TABLE usuarios ADD COLUMN usu_jornada_semanal_minutos INT NULL DEFAULT 2520',
            'usu_dia_descanso' => 'ALTER TABLE usuarios ADD COLUMN usu_dia_descanso TINYINT NULL DEFAULT 0',
        );

        foreach ($columnas as $nombre => $sql) {
            try {
                $existe = $this->db->fetchAll("SHOW COLUMNS FROM usuarios LIKE '" . $nombre . "'");
                if (!$existe) {
                    $this->db->execute($sql);
                }
            } catch (\Throwable $e) {
                continue;
            }
        }
    }

    public function getSchedulesByDocuments(array $documentos)
    {
        $documentos = array_values(array_filter(array_unique($documentos)));
        if (empty($documentos)) {
            return array();
        }

        $this->ensureJornadaColumns();

        $placeholders = array();
        $params = array();
        foreach ($documentos as $i => $documento) {
            $clave = 'd' . $i;
            $placeholders[] = ':' . $clave;
            $params[$clave] = $documento;
        }

        try {
            $filas = $this->db->fetchAll(
                'SELECT usu_identificacion, usu_nombre, usu_hora_inicio, usu_hora_fin,
                        usu_jornada_diaria_minutos, usu_jornada_semanal_minutos, usu_dia_descanso
                 FROM usuarios
                 WHERE usu_identificacion IN (' . implode(',', $placeholders) . ')',
                $params
            );
        } catch (\Throwable $e) {
            return array();
        }

        $mapa = array();
        foreach ($filas as $fila) {
            $mapa[(string) $fila['usu_identificacion']] = $fila;
        }

        return $mapa;
    }

    public function listWorkersForSchedule($sede = '', $busqueda = '')
    {
        $this->ensureJornadaColumns();

        $sql = 'SELECT usu_identificacion, usu_nombre, usu_idsede, usu_hora_inicio, usu_hora_fin,
                       usu_jornada_diaria_minutos, usu_jornada_semanal_minutos, usu_dia_descanso
                FROM usuarios
                WHERE usu_identificacion IS NOT NULL
                  AND usu_identificacion <> \'\'';
        $params = array();

        if ($sede !== '') {
            $sql .= ' AND usu_idsede = :sede';
            $params['sede'] = $sede;
        }

        if ($busqueda !== '') {
            $sql .= ' AND (usu_identificacion LIKE :busquedaDoc OR usu_nombre LIKE :busquedaNom)';
            $params['busquedaDoc'] = '%' . $busqueda . '%';
            $params['busquedaNom'] = '%' . $busqueda . '%';
        }

        $sql .= ' ORDER BY usu_nombre ASC, usu_identificacion ASC';

        try {
            return $this->db->fetchAll($sql, $params);
        } catch (\Throwable $e) {
            return array();
        }
    }

    public function updateWorkerSchedule($documento, $horaInicio, $horaFin, $diariaMinutos, $semanalMinutos, $diaDescanso)
    {
        $documento = trim((string) $documento);
        if ($documento === '') {
            return 0;
        }

        $this->ensureJornadaColumns();

        return $this->db->execute(
            'UPDATE usuarios
             SET usu_hora_inicio = :inicio,
                 usu_hora_fin = :fin,
                 usu_jornada_diaria_minutos = :diaria,
                 usu_jornada_semanal_minutos = :semanal,
                 usu_dia_descanso = :descanso
             WHERE usu_identificacion = :documento',
            array(
                'inicio' => $horaInicio,
                'fin' => $horaFin,
                'diaria' => (int) $diariaMinutos,
                'semanal' => (int) $semanalMinutos,
                'descanso' => (int) $diaDescanso,
                'documento' => $documento,
            )
        );
    }

    public function listCollaborators($sede = '', $busqueda = '')
    {
        $trabajadores = $this->listWorkersForSchedule($sede, $busqueda);
        $documentos = array();
        foreach ($trabajadores as $fila) {
            if (!empty($fila['usu_identificacion'])) {
                $documentos[] = (string) $fila['usu_identificacion'];
            }
        }

        $acceso = $this->mapaAccesoPorCedula($documentos);
        foreach ($trabajadores as $indice => $fila) {
            $documento = (string) $fila['usu_identificacion'];
            $trabajadores[$indice]['ingresa_cedula'] = !empty($acceso[$documento]);
        }

        return $trabajadores;
    }

    public function updateCollaborator(array $datos)
    {
        $documentoActual = isset($datos['documento_actual']) ? trim((string) $datos['documento_actual']) : '';
        $documentoNuevo = isset($datos['documento']) ? trim((string) $datos['documento']) : '';
        $nombre = isset($datos['nombre']) ? trim((string) $datos['nombre']) : '';
        $sedeId = isset($datos['sede']) ? trim((string) $datos['sede']) : '';
        $horaInicio = isset($datos['hora_inicio']) ? $datos['hora_inicio'] : '';
        $horaFin = isset($datos['hora_fin']) ? $datos['hora_fin'] : '';
        $diariaMinutos = isset($datos['jornada_diaria_minutos']) ? (int) $datos['jornada_diaria_minutos'] : 0;
        $semanalMinutos = isset($datos['jornada_semanal_minutos']) ? (int) $datos['jornada_semanal_minutos'] : 0;
        $diaDescanso = isset($datos['dia_descanso']) ? (int) $datos['dia_descanso'] : 0;
        $ingresaCedula = !empty($datos['ingresa_cedula']);

        if ($documentoActual === '' || $documentoNuevo === '' || $nombre === '') {
            throw new \InvalidArgumentException('Nombre y cédula son obligatorios');
        }

        if (!$this->getUserRowByIdentification($documentoActual)) {
            throw new \RuntimeException('No existe un colaborador con esa cédula');
        }

        if ($documentoNuevo !== $documentoActual) {
            $otro = $this->getUserRowByIdentification($documentoNuevo);
            if ($otro) {
                throw new \RuntimeException('Ya existe un colaborador con la cédula nueva');
            }
        }

        $this->ensureJornadaColumns();
        $this->db->beginTransaction();
        try {
            $this->db->execRaw('SET FOREIGN_KEY_CHECKS = 0');

            $this->db->execute(
                'UPDATE usuarios
                 SET usu_identificacion = :nuevo,
                     usu_nombre = :nombre,
                     usu_idsede = :sede,
                     usu_hora_inicio = :inicio,
                     usu_hora_fin = :fin,
                     usu_jornada_diaria_minutos = :diaria,
                     usu_jornada_semanal_minutos = :semanal,
                     usu_dia_descanso = :descanso
                 WHERE usu_identificacion = :actual',
                array(
                    'nuevo' => $documentoNuevo,
                    'nombre' => $nombre,
                    'sede' => $sedeId === '' ? null : $sedeId,
                    'inicio' => $horaInicio,
                    'fin' => $horaFin,
                    'diaria' => $diariaMinutos,
                    'semanal' => $semanalMinutos,
                    'descanso' => $diaDescanso,
                    'actual' => $documentoActual,
                )
            );

            if ($documentoNuevo !== $documentoActual) {
                $this->actualizarDocumentoRelacionado('usuarios_huella', 'documento', $documentoActual, $documentoNuevo);
                $this->actualizarDocumentoRelacionado('huellas', 'documento', $documentoActual, $documentoNuevo);
                $this->actualizarDocumentoRelacionado('ingreso_con_ced', 'ing_cedula', $documentoActual, $documentoNuevo);
                $this->actualizarDocumentoRelacionado('seguimientousers', 'seg_iduser', $documentoActual, $documentoNuevo);
            }

            try {
                $this->db->execute(
                    'UPDATE usuarios_huella SET nombre_completo = :nombre WHERE documento = :documento',
                    array('nombre' => $nombre, 'documento' => $documentoNuevo)
                );
            } catch (\Throwable $e) {
                // usuarios_huella puede no existir para colaboradores sin huella.
            }

            $this->sincronizarAccesoPorCedula($documentoNuevo, $ingresaCedula);

            $this->db->execRaw('SET FOREIGN_KEY_CHECKS = 1');
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            try {
                $this->db->execRaw('SET FOREIGN_KEY_CHECKS = 1');
            } catch (\Throwable $ignored) {
            }
            throw $e;
        }

        return true;
    }

    private function actualizarDocumentoRelacionado($tabla, $columna, $actual, $nuevo)
    {
        try {
            $this->db->execute(
                'UPDATE ' . $tabla . ' SET ' . $columna . ' = :nuevo WHERE ' . $columna . ' = :actual',
                array('nuevo' => $nuevo, 'actual' => $actual)
            );
        } catch (\Throwable $e) {
            // Tabla o columna opcional según esquema.
        }
    }

    private function columnasIngresoConCed()
    {
        static $columnas = null;
        if ($columnas !== null) {
            return $columnas;
        }

        $columnas = array();
        try {
            $filas = $this->db->fetchAll('SHOW COLUMNS FROM ingreso_con_ced');
            foreach ($filas as $fila) {
                if (!empty($fila['Field'])) {
                    $columnas[strtolower((string) $fila['Field'])] = $fila;
                }
            }
        } catch (\Throwable $e) {
            $columnas = array();
        }

        return $columnas;
    }

    private function ingresoCedulaEstaActivo(array $fila)
    {
        $columnas = $this->columnasIngresoConCed();
        if (!isset($columnas['ing_estado'])) {
            return true;
        }

        return (int) $fila['ing_estado'] === 1;
    }

    private function valorEstadoCedula($activo)
    {
        return $activo ? 1 : 0;
    }

    private function mapaAccesoPorCedula(array $documentos)
    {
        $documentos = array_values(array_filter(array_unique($documentos)));
        $mapa = array();
        if (empty($documentos) || empty($this->columnasIngresoConCed())) {
            return $mapa;
        }

        $placeholders = array();
        $params = array();
        foreach ($documentos as $i => $documento) {
            $clave = 'c' . $i;
            $placeholders[] = ':' . $clave;
            $params[$clave] = $documento;
        }

        try {
            $filas = $this->db->fetchAll(
                'SELECT * FROM ingreso_con_ced WHERE ing_cedula IN (' . implode(',', $placeholders) . ')',
                $params
            );
        } catch (\Throwable $e) {
            return $mapa;
        }

        foreach ($filas as $fila) {
            if (empty($fila['ing_cedula'])) {
                continue;
            }
            $mapa[(string) $fila['ing_cedula']] = $this->ingresoCedulaEstaActivo($fila);
        }

        return $mapa;
    }

    private function sincronizarAccesoPorCedula($documento, $habilitado)
    {
        $columnas = $this->columnasIngresoConCed();
        if (empty($columnas) || !isset($columnas['ing_cedula'])) {
            return;
        }

        $row = null;
        try {
            $row = $this->db->fetchOne(
                'SELECT * FROM ingreso_con_ced WHERE ing_cedula = :cedula LIMIT 1',
                array('cedula' => $documento)
            );
        } catch (\Throwable $e) {
            return;
        }

        $tieneEstado = isset($columnas['ing_estado']);
        $estado = $this->valorEstadoCedula($habilitado);

        if ($row) {
            if ($tieneEstado) {
                $this->db->execute(
                    'UPDATE ingreso_con_ced SET ing_estado = :estado WHERE ing_cedula = :cedula',
                    array('estado' => $estado, 'cedula' => $documento)
                );
            }
            return;
        }

        if (!$habilitado) {
            return;
        }

        if ($tieneEstado) {
            $this->db->execute(
                'INSERT INTO ingreso_con_ced (ing_cedula, ing_estado) VALUES (:cedula, :estado)',
                array('cedula' => $documento, 'estado' => 1)
            );
            return;
        }

        $this->db->execute(
            'INSERT INTO ingreso_con_ced (ing_cedula) VALUES (:cedula)',
            array('cedula' => $documento)
        );
    }
}
