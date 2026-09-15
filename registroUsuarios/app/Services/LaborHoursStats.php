<?php

namespace Huella\Services;

use Huella\Repositories\BiometricRepository;

/**
 * Totales generales de horas del período (sin desglose por trabajador).
 */
class LaborHoursStats
{
    public function totalsFromSummary(array $resumen)
    {
        $ordinarias = 0;
        $nocturnas = 0;
        $extraDiurnas = 0;
        $extraNocturnas = 0;
        $dominicalesFestivas = 0;

        foreach ($resumen as $fila) {
            $ordinarias += (int) $fila['ordinaria_diurna'];
            $nocturnas += (int) $fila['ordinaria_nocturna'];
            $extraDiurnas += isset($fila['extra_diurna_laboral']) ? (int) $fila['extra_diurna_laboral'] : 0;
            $extraNocturnas += isset($fila['extra_nocturna_laboral']) ? (int) $fila['extra_nocturna_laboral'] : 0;
            $dominicalesFestivas += (int) $fila['dominical_descanso'] + (int) $fila['festiva'];
        }

        $total = $ordinarias + $nocturnas + $extraDiurnas + $extraNocturnas + $dominicalesFestivas;

        return array(
            'ordinarias' => $this->minutosAHoras($ordinarias),
            'nocturnas' => $this->minutosAHoras($nocturnas),
            'extra_diurnas' => $this->minutosAHoras($extraDiurnas),
            'extra_nocturnas' => $this->minutosAHoras($extraNocturnas),
            'dominicales_festivas' => $this->minutosAHoras($dominicalesFestivas),
            'total' => $this->minutosAHoras($total),
        );
    }

    public function buildFromRows(BiometricRepository $repository, array $config, array $filas)
    {
        $documentos = array();
        foreach ($filas as $fila) {
            if (!empty($fila['documento'])) {
                $documentos[] = (string) $fila['documento'];
            }
        }

        $repository->ensureJornadaColumns();
        $horarios = $repository->getSchedulesByDocuments($documentos);
        $clasificador = new LaborHoursClassifier($config, new ColombianHolidays());
        $resumen = $clasificador->summarize($filas, $horarios);

        return $this->enrich($this->totalsFromSummary($resumen));
    }

    public function enrich(array $horas)
    {
        $total = (float) $horas['total'];
        $extraTotal = (float) $horas['extra_diurnas'] + (float) $horas['extra_nocturnas'];
        $conRecargo = (float) $horas['nocturnas'] + $extraTotal + (float) $horas['dominicales_festivas'];

        return array(
            'horas' => $horas,
            'extra_total' => round($extraTotal, 2),
            'con_recargo' => round($conRecargo, 2),
            'pct_ordinarias' => $this->porcentaje((float) $horas['ordinarias'], $total),
            'pct_nocturnas' => $this->porcentaje((float) $horas['nocturnas'], $total),
            'pct_extra' => $this->porcentaje($extraTotal, $total),
            'pct_dominicales' => $this->porcentaje((float) $horas['dominicales_festivas'], $total),
            'pct_con_recargo' => $this->porcentaje($conRecargo, $total),
        );
    }

    public function minutosAHoras($minutos)
    {
        return round(((int) $minutos) / 60, 2);
    }

    private function porcentaje($parte, $total)
    {
        if ($total <= 0) {
            return 0.0;
        }

        return round(($parte / $total) * 100, 1);
    }
}
