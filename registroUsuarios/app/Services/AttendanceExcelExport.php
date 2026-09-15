<?php

namespace Huella\Services;

use Huella\Repositories\BiometricRepository;

class AttendanceExcelExport
{
    private $repository;
    private $config;

    public function __construct(BiometricRepository $repository, array $config)
    {
        $this->repository = $repository;
        $this->config = $config;
    }

    public function download(array $filas, $fechaDesde, $fechaHasta)
    {
        if (!function_exists('formatear_fecha_asistencia')) {
            require_once dirname(__DIR__, 2) . '/inc/tiempo_asistencia.php';
        }

        $this->repository->ensureJornadaColumns();
        $horarios = $this->repository->getSchedulesByDocuments($this->documentosDe($filas));
        $clasificador = new LaborHoursClassifier($this->config, new ColombianHolidays());
        $resumen = $clasificador->summarize($filas, $horarios);

        $hojas = array(
            array(
                'nombre' => 'Historial',
                'filas' => $this->filasHistorial($filas),
            ),
            array(
                'nombre' => 'Clasificacion horas',
                'filas' => $this->filasClasificacion($resumen, $fechaDesde, $fechaHasta),
            ),
        );

        $nombre = 'Ingresos_Huella_' . date('Y-m', strtotime($fechaDesde)) . '.xlsx';
        $writer = new SimpleXlsxWriter();
        $writer->download($hojas, $nombre);
    }

    private function documentosDe(array $filas)
    {
        $docs = array();
        foreach ($filas as $fila) {
            if (!empty($fila['documento'])) {
                $docs[] = (string) $fila['documento'];
            }
        }

        return array_values(array_unique($docs));
    }

    private function filasHistorial(array $filas)
    {
        $out = array(array(
            'Nombre',
            'Documento',
            'Fecha',
            'Ingreso',
            'Sale almuerzo',
            'Regresa almuerzo',
            'Sale break',
            'Regresa break',
            'Salida',
        ));

        foreach ($filas as $fila) {
            $out[] = array(
                isset($fila['nombre']) ? $fila['nombre'] : '',
                isset($fila['documento']) ? $fila['documento'] : '',
                \formatear_fecha_asistencia(isset($fila['seg_fechaingreso']) ? $fila['seg_fechaingreso'] : ''),
                \formatear_hora_asistencia(isset($fila['seg_horaingreso']) ? $fila['seg_horaingreso'] : ''),
                \formatear_hora_asistencia(isset($fila['seg_ingresoAlmuerzo']) ? $fila['seg_ingresoAlmuerzo'] : ''),
                \formatear_hora_asistencia(isset($fila['seg_salioAlmuerzo']) ? $fila['seg_salioAlmuerzo'] : ''),
                \formatear_hora_asistencia(isset($fila['seg_ingresoBreak']) ? $fila['seg_ingresoBreak'] : ''),
                \formatear_hora_asistencia(isset($fila['seg_salioBreak']) ? $fila['seg_salioBreak'] : ''),
                \formatear_hora_asistencia(isset($fila['seg_horaSalida']) ? $fila['seg_horaSalida'] : ''),
            );
        }

        return $out;
    }

    private function filasClasificacion(array $resumen, $fechaDesde, $fechaHasta)
    {
        $out = array(array(
            'Nombre',
            'Documento',
            'Horario configurado',
            'Jornada diaria (h)',
            'Jornada semanal (h)',
            'Día de descanso',
            'Horas ordinarias diurnas',
            'Horas ordinarias nocturnas',
            'Horas extra diurnas',
            'Horas extra nocturnas',
            'Horas dominicales o de descanso obligatorio',
            'Horas festivas',
            'Horas nocturnas y extras en dominical o festivo',
            'Total horas trabajadas',
            'Total horas extra',
        ));

        foreach ($resumen as $fila) {
            $out[] = array(
                $fila['nombre'],
                $fila['documento'],
                $fila['horario'],
                round($fila['jornada_diaria_horas'], 2),
                round($fila['jornada_semanal_horas'], 2),
                $fila['dia_descanso'],
                $this->minutosAHoras($fila['ordinaria_diurna']),
                $this->minutosAHoras($fila['ordinaria_nocturna']),
                $this->minutosAHoras($fila['extra_diurna']),
                $this->minutosAHoras($fila['extra_nocturna']),
                $this->minutosAHoras($fila['dominical_descanso']),
                $this->minutosAHoras($fila['festiva']),
                $this->minutosAHoras($fila['nocturna_extra_dominical_festivo']),
                $this->minutosAHoras($fila['total_trabajadas']),
                $this->minutosAHoras($fila['total_extra']),
            );
        }

        $out[] = array();
        $out[] = array(
            'Periodo: ' . $fechaDesde . ' a ' . $fechaHasta,
        );
        $out[] = array(
            'Normativa: CST arts. 160 y 161 (Ley 2466 de 2025). Diurno 6:00 a.m.-7:00 p.m.; nocturno 7:00 p.m.-6:00 a.m. Jornada máxima 42 h/semana desde el 15-jul-2026.',
        );
        $out[] = array(
            'Hora extra: tiempo fuera del horario pactado o que exceda la jornada ordinaria configurada (diaria o semanal). La hora nocturna dentro del horario pactado es ordinaria nocturna, no extra.',
        );
        $out[] = array(
            'Si el trabajador no tiene horario en usuarios, se usa el de config/jornada_laboral.php. Almuerzo y break se descuentan con las marcas reales.',
        );

        return $out;
    }

    private function minutosAHoras($minutos)
    {
        return round(((int) $minutos) / 60, 2);
    }
}
