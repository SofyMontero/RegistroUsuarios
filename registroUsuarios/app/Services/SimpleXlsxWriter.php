<?php

namespace Huella\Services;

/**
 * Genera un .xlsx mínimo (Open XML) con varias hojas, sin dependencias.
 */
class SimpleXlsxWriter
{
    public function download(array $hojas, $nombreArchivo)
    {
        if (!class_exists('ZipArchive')) {
            header('Content-Type: text/plain; charset=utf-8');
            echo 'No está disponible ZipArchive en el servidor para generar Excel.';
            return;
        }

        $tmp = tempnam(sys_get_temp_dir(), 'xlsx');
        $zip = new \ZipArchive();
        if ($zip->open($tmp, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            header('Content-Type: text/plain; charset=utf-8');
            echo 'No fue posible crear el archivo Excel.';
            return;
        }

        $zip->addFromString('[Content_Types].xml', $this->contentTypes(count($hojas)));
        $zip->addFromString('_rels/.rels', $this->rootRels());
        $zip->addFromString('xl/workbook.xml', $this->workbook($hojas));
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRels(count($hojas)));
        $zip->addFromString('xl/styles.xml', $this->styles());

        $indice = 1;
        foreach ($hojas as $hoja) {
            $filas = isset($hoja['filas']) ? $hoja['filas'] : array();
            $zip->addFromString('xl/worksheets/sheet' . $indice . '.xml', $this->sheetXml($filas));
            $indice++;
        }

        $zip->close();

        $nombreArchivo = preg_replace('/[^\w.\-]+/', '_', $nombreArchivo);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Content-Length: ' . filesize($tmp));
        readfile($tmp);
        unlink($tmp);
    }

    private function contentTypes($cantidadHojas)
    {
        $overrides = '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>';
        for ($i = 1; $i <= $cantidadHojas; $i++) {
            $overrides .= '<Override PartName="/xl/worksheets/sheet' . $i . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . $overrides
            . '</Types>';
    }

    private function rootRels()
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>';
    }

    private function workbook(array $hojas)
    {
        $sheets = '';
        $i = 1;
        foreach ($hojas as $hoja) {
            $nombre = $this->xml(isset($hoja['nombre']) ? $hoja['nombre'] : ('Hoja' . $i));
            $sheets .= '<sheet name="' . $nombre . '" sheetId="' . $i . '" r:id="rId' . $i . '"/>';
            $i++;
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"'
            . ' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets>' . $sheets . '</sheets></workbook>';
    }

    private function workbookRels($cantidadHojas)
    {
        $rels = '';
        for ($i = 1; $i <= $cantidadHojas; $i++) {
            $rels .= '<Relationship Id="rId' . $i . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . $i . '.xml"/>';
        }
        $rels .= '<Relationship Id="rId' . ($cantidadHojas + 1) . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . $rels
            . '</Relationships>';
    }

    private function styles()
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="2">'
            . '<font><sz val="11"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="11"/><name val="Calibri"/></font>'
            . '</fonts>'
            . '<fills count="3">'
            . '<fill><patternFill patternType="none"/></fill>'
            . '<fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FF5D8F8A"/><bgColor indexed="64"/></patternFill></fill>'
            . '</fills>'
            . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="4">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1">'
            . '<alignment wrapText="1"/>'
            . '</xf>'
            . '<xf numFmtId="2" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"><alignment wrapText="1"/></xf>'
            . '</cellXfs>'
            . '</styleSheet>';
    }

    private function sheetXml(array $filas)
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<sheetData>';

        $r = 1;
        foreach ($filas as $fila) {
            $xml .= '<row r="' . $r . '">';
            $c = 0;
            foreach ($fila as $valor) {
                $ref = $this->colLetter($c) . $r;
                $esEncabezado = ($r === 1);
                $esNumero = is_int($valor) || is_float($valor);
                if ($esNumero && !$esEncabezado) {
                    $xml .= '<c r="' . $ref . '" s="2"><v>' . $valor . '</v></c>';
                } else {
                    $estilo = $esEncabezado ? ' s="1"' : '';
                    $xml .= '<c r="' . $ref . '" t="inlineStr"' . $estilo . '><is><t>' . $this->xml((string) $valor) . '</t></is></c>';
                }
                $c++;
            }
            $xml .= '</row>';
            $r++;
        }

        $xml .= '</sheetData></worksheet>';

        return $xml;
    }

    private function colLetter($index)
    {
        $letter = '';
        $index++;
        while ($index > 0) {
            $mod = ($index - 1) % 26;
            $letter = chr(65 + $mod) . $letter;
            $index = (int) floor(($index - $mod) / 26);
        }

        return $letter;
    }

    private function xml($texto)
    {
        $texto = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $texto);

        return htmlspecialchars($texto, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }
}
