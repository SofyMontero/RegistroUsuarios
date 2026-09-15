<?php

namespace Huella\Services;

/**
 * Festivos de Colombia (Ley 51 de 1983 / Emiliani, y 9 de julio Ley 2466 de 2025).
 */
class ColombianHolidays
{
    public function isHoliday($fechaYmd)
    {
        $fecha = date('Y-m-d', strtotime($fechaYmd));
        $anio = (int) date('Y', strtotime($fecha));
        $festivos = $this->forYear($anio);

        return in_array($fecha, $festivos, true);
    }

    /**
     * @return string[] fechas Y-m-d
     */
    public function forYear($year)
    {
        $year = (int) $year;
        $fijos = array(
            $year . '-01-01',
            $year . '-05-01',
            $year . '-07-09',
            $year . '-07-20',
            $year . '-08-07',
            $year . '-12-08',
            $year . '-12-25',
        );

        $emiliani = array(
            $this->siguienteLunes($year . '-01-06'),
            $this->siguienteLunes($year . '-03-19'),
            $this->siguienteLunes($year . '-06-29'),
            $this->siguienteLunes($year . '-08-15'),
            $this->siguienteLunes($year . '-10-12'),
            $this->siguienteLunes($year . '-11-01'),
            $this->siguienteLunes($year . '-11-11'),
        );

        $pascua = $this->pascua($year);
        $pascuaTs = strtotime($pascua);
        $semanaSanta = array(
            date('Y-m-d', strtotime('-3 days', $pascuaTs)),
            date('Y-m-d', strtotime('-2 days', $pascuaTs)),
        );
        $moviblesPascua = array(
            $this->siguienteLunes(date('Y-m-d', strtotime('+39 days', $pascuaTs))),
            $this->siguienteLunes(date('Y-m-d', strtotime('+60 days', $pascuaTs))),
            $this->siguienteLunes(date('Y-m-d', strtotime('+68 days', $pascuaTs))),
        );

        $todos = array_merge($fijos, $emiliani, $semanaSanta, $moviblesPascua);
        $todos = array_values(array_unique($todos));
        sort($todos);

        return $todos;
    }

    private function siguienteLunes($fechaYmd)
    {
        $ts = strtotime($fechaYmd);
        $dia = (int) date('w', $ts);
        if ($dia === 1) {
            return date('Y-m-d', $ts);
        }
        $suma = $dia === 0 ? 1 : (8 - $dia);

        return date('Y-m-d', strtotime('+' . $suma . ' days', $ts));
    }

    private function pascua($year)
    {
        $a = $year % 19;
        $b = (int) floor($year / 100);
        $c = $year % 100;
        $d = (int) floor($b / 4);
        $e = $b % 4;
        $f = (int) floor(($b + 8) / 25);
        $g = (int) floor(($b - $f + 1) / 3);
        $h = (19 * $a + $b - $d - $g + 15) % 30;
        $i = (int) floor($c / 4);
        $k = $c % 4;
        $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
        $m = (int) floor(($a + 11 * $h + 22 * $l) / 451);
        $mes = (int) floor(($h + $l - 7 * $m + 114) / 31);
        $dia = (($h + $l - 7 * $m + 114) % 31) + 1;

        return sprintf('%04d-%02d-%02d', $year, $mes, $dia);
    }
}
