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
    public function createAdministrativeUser($documento, $nombre)
    {
        return $this->db->execute(
            "INSERT INTO usuarios (usu_identificacion, usu_nombre, usu_estado, con_huella, fecha_creacion)
             VALUES (:documento, :nombre, '1', 'no', NOW())",
            array('documento' => $documento, 'nombre' => $nombre)
        );
    }

    /**
     * Actualiza telefono y/o sede si las columnas existen en la BD (fallos se ignoran en el llamador).
     */
    public function updateAdministrativeUserExtras($documento, $telefono, $sedeId)
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
}
