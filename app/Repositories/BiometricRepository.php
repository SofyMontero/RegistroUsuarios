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
}
