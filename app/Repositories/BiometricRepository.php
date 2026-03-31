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

    public function getAttendanceRow($documento, $fechaActual)
    {
        return $this->db->fetchOne(
            "SELECT seg_iduser, seg_horaingreso, seg_ingresoAlmuerzo, seg_salioAlmuerzo, seg_horaSalida
             FROM seguimientousers
             WHERE seg_iduser = :documento AND seg_fechaingreso = :fecha",
            array('documento' => $documento, 'fecha' => $fechaActual)
        );
    }

    public function updateAttendanceField($documento, $fechaActual, $campo, $horaActual)
    {
        $allowed = array('seg_horaingreso', 'seg_ingresoAlmuerzo', 'seg_salioAlmuerzo', 'seg_horaSalida');
        if (!in_array($campo, $allowed, true)) {
            return 0;
        }

        return $this->db->execute(
            "UPDATE seguimientousers SET {$campo} = :hora WHERE seg_iduser = :documento AND seg_fechaingreso = :fecha",
            array('hora' => $horaActual, 'documento' => $documento, 'fecha' => $fechaActual)
        );
    }

    public function isDocumentAllowedForManualRegister($cedula)
    {
        return $this->db->fetchOne(
            "SELECT ing_cedula FROM ingreso_con_ced WHERE ing_cedula = :cedula",
            array('cedula' => $cedula)
        );
    }

    public function getUserNameByDocument($cedula)
    {
        return $this->db->fetchOne(
            "SELECT usu_nombre FROM usuarios WHERE usu_identificacion = :cedula",
            array('cedula' => $cedula)
        );
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

    public function getTableColumns($table)
    {
        $statement = $this->db->getPdo()->query("SHOW COLUMNS FROM `{$table}`");
        $columns = array();

        foreach ($statement->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            if (!empty($row['Field'])) {
                $columns[] = $row['Field'];
            }
        }

        return $columns;
    }

    public function userExistsInMainTable($documento)
    {
        $columns = $this->getTableColumns('usuarios');
        $documentColumn = $this->resolveFirstExistingColumn($columns, array('usu_identificacion', 'documento'));

        if ($documentColumn === null) {
            return false;
        }

        return $this->db->fetchOne(
            "SELECT {$documentColumn} FROM usuarios WHERE {$documentColumn} = :documento LIMIT 1",
            array('documento' => $documento)
        );
    }

    public function getHeadquartersList()
    {
        $columns = $this->getTableColumns('sedes');
        $idColumn = $this->resolveFirstExistingColumn($columns, array('idsedes', 'id', 'sede_id'));
        $nameColumn = $this->resolveFirstExistingColumn($columns, array('sed_nombre', 'sede_nombre', 'nombre'));

        if ($idColumn === null || $nameColumn === null) {
            return array();
        }

        return $this->db->fetchAll(
            "SELECT {$idColumn} AS id, {$nameColumn} AS nombre FROM sedes ORDER BY {$nameColumn} ASC"
        );
    }

    public function headquartersExists($sedeId)
    {
        $columns = $this->getTableColumns('sedes');
        $idColumn = $this->resolveFirstExistingColumn($columns, array('idsedes', 'id', 'sede_id'));

        if ($idColumn === null) {
            return false;
        }

        return $this->db->fetchOne(
            "SELECT {$idColumn} FROM sedes WHERE {$idColumn} = :sede LIMIT 1",
            array('sede' => $sedeId)
        );
    }

    public function createHeadquarters($nombre)
    {
        $columns = $this->getTableColumns('sedes');
        $nameColumn = $this->resolveFirstExistingColumn($columns, array('sed_nombre', 'sede_nombre', 'nombre'));

        if ($nameColumn === null) {
            return 0;
        }

        return $this->db->execute(
            "INSERT INTO sedes ({$nameColumn}) VALUES (:nombre)",
            array('nombre' => $nombre)
        );
    }

    public function headquartersNameExists($nombre)
    {
        $columns = $this->getTableColumns('sedes');
        $nameColumn = $this->resolveFirstExistingColumn($columns, array('sed_nombre', 'sede_nombre', 'nombre'));

        if ($nameColumn === null) {
            return false;
        }

        return $this->db->fetchOne(
            "SELECT {$nameColumn} FROM sedes WHERE {$nameColumn} = :nombre LIMIT 1",
            array('nombre' => $nombre)
        );
    }

    public function countUsersByHeadquarters($sedeId)
    {
        $columns = $this->getTableColumns('usuarios');
        $sedeColumn = $this->resolveFirstExistingColumn($columns, array('usu_idsede', 'idsedes', 'sede_id'));

        if ($sedeColumn === null) {
            return 0;
        }

        $row = $this->db->fetchOne(
            "SELECT COUNT(*) AS total FROM usuarios WHERE {$sedeColumn} = :sede",
            array('sede' => $sedeId)
        );

        return $row ? (int) $row['total'] : 0;
    }

    public function deleteHeadquarters($sedeId)
    {
        $columns = $this->getTableColumns('sedes');
        $idColumn = $this->resolveFirstExistingColumn($columns, array('idsedes', 'id', 'sede_id'));

        if ($idColumn === null) {
            return 0;
        }

        return $this->db->execute(
            "DELETE FROM sedes WHERE {$idColumn} = :sede",
            array('sede' => $sedeId)
        );
    }

    public function createMainUser($documento, $nombre, $telefono, $sedeId)
    {
        $columns = $this->getTableColumns('usuarios');
        $insertColumns = array();
        $params = array();

        $documentColumn = $this->resolveFirstExistingColumn($columns, array('usu_identificacion', 'documento'));
        if ($documentColumn !== null) {
            $insertColumns[] = $documentColumn;
            $params[$documentColumn] = $documento;
        }

        $nameColumn = $this->resolveFirstExistingColumn($columns, array('usu_nombre', 'nombre_completo'));
        if ($nameColumn !== null) {
            $insertColumns[] = $nameColumn;
            $params[$nameColumn] = $nombre;
        }

        $phoneColumn = $this->resolveFirstExistingColumn($columns, array('telefono', 'usu_telefono', 'celular'));
        if ($phoneColumn !== null) {
            $insertColumns[] = $phoneColumn;
            $params[$phoneColumn] = $telefono !== '' ? $telefono : null;
        }

        $sedeColumn = $this->resolveFirstExistingColumn($columns, array('usu_idsede', 'idsedes', 'sede_id'));
        if ($sedeColumn !== null) {
            $insertColumns[] = $sedeColumn;
            $params[$sedeColumn] = $sedeId;
        }

        $stateColumn = $this->resolveFirstExistingColumn($columns, array('usu_estado'));
        if ($stateColumn !== null) {
            $insertColumns[] = $stateColumn;
            $params[$stateColumn] = '1';
        }

        $fingerprintColumn = $this->resolveFirstExistingColumn($columns, array('con_huella'));
        if ($fingerprintColumn !== null) {
            $insertColumns[] = $fingerprintColumn;
            $params[$fingerprintColumn] = 'no';
        }

        $createdColumn = $this->resolveFirstExistingColumn($columns, array('fecha_creacion', 'fecha_crecion'));
        if ($createdColumn !== null) {
            $insertColumns[] = $createdColumn;
            $params[$createdColumn] = date('Y-m-d H:i:s');
        }

        if (count($insertColumns) < 2) {
            return 0;
        }

        $placeholders = array();
        foreach ($insertColumns as $column) {
            $placeholders[] = ':' . $column;
        }

        return $this->db->execute(
            'INSERT INTO usuarios (' . implode(', ', $insertColumns) . ') VALUES (' . implode(', ', $placeholders) . ')',
            $params
        );
    }

    public function ensureTodayAttendanceRowsForActiveUsers($fechaActual)
    {
        return $this->db->execute(
            "INSERT INTO seguimientousers (
                seg_iduser,
                seg_fechaingreso,
                seg_horaingreso,
                seg_ingresoAlmuerzo,
                seg_salioAlmuerzo,
                seg_horaSalida
            )
            SELECT
                u.usu_identificacion,
                :fecha,
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

    private function resolveFirstExistingColumn(array $columns, array $candidates)
    {
        foreach ($candidates as $candidate) {
            if (in_array($candidate, $columns, true)) {
                return $candidate;
            }
        }

        return null;
    }
}
