<?php

namespace App\Support\Export;

use RuntimeException;
use ZipArchive;

/**
 * Escritor de .xlsx sin dependencias.
 *
 * Un .xlsx es un ZIP con XMLs (OOXML). Se arma a mano con ZipArchive, que ya
 * viene en el PHP del servidor, para no meter una librería que obligaría a
 * correr `composer install` en cada despliegue (vendor/ no viaja por git).
 *
 * Las cadenas van como inlineStr en vez de sharedStrings: pesa un poco más
 * pero evita mantener el diccionario y sus índices.
 */
class XlsxWriter
{
    /** @var array<int, array{nombre:string, encabezados:array<int,string>, filas:array<int,array<int,mixed>>, anchos:array<int,int>}> */
    private array $hojas = [];

    /** Estilos declarados en styles.xml: 1 = negritas, 2 = número con 2 decimales. */
    private const ESTILO_ENCABEZADO = 1;

    private const ESTILO_DECIMAL = 2;

    /**
     * @param  array<int, string>  $encabezados
     * @param  array<int, array<int, mixed>>  $filas
     * @param  array<int, int>  $anchos  ancho de columna en caracteres
     */
    public function agregarHoja(string $nombre, array $encabezados, array $filas, array $anchos = []): self
    {
        $this->hojas[] = [
            'nombre' => $this->nombreValido($nombre),
            'encabezados' => $encabezados,
            'filas' => $filas,
            'anchos' => $anchos,
        ];

        return $this;
    }

    /**
     * Devuelve el binario del archivo. ZipArchive solo escribe a disco, así que
     * se usa un temporal y se lee de vuelta.
     */
    public function generar(): string
    {
        if ($this->hojas === []) {
            throw new RuntimeException('El libro no tiene hojas.');
        }

        $ruta = tempnam(sys_get_temp_dir(), 'xlsx');
        if ($ruta === false) {
            throw new RuntimeException('No se pudo crear el archivo temporal del Excel.');
        }

        $zip = new ZipArchive;
        if ($zip->open($ruta, ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('No se pudo abrir el ZIP del Excel.');
        }

        $zip->addFromString('[Content_Types].xml', $this->contentTypes());
        $zip->addFromString('_rels/.rels', $this->relsRaiz());
        $zip->addFromString('xl/workbook.xml', $this->workbook());
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->relsWorkbook());
        $zip->addFromString('xl/styles.xml', $this->styles());

        foreach ($this->hojas as $i => $hoja) {
            $zip->addFromString('xl/worksheets/sheet'.($i + 1).'.xml', $this->hoja($hoja));
        }

        $zip->close();

        $binario = file_get_contents($ruta);
        unlink($ruta);

        if ($binario === false) {
            throw new RuntimeException('No se pudo leer el Excel generado.');
        }

        return $binario;
    }

    /**
     * @param  array{nombre:string, encabezados:array<int,string>, filas:array<int,array<int,mixed>>, anchos:array<int,int>}  $hoja
     */
    private function hoja(array $hoja): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';

        if ($hoja['anchos'] !== []) {
            $xml .= '<cols>';
            foreach ($hoja['anchos'] as $i => $ancho) {
                $col = $i + 1;
                $xml .= '<col min="'.$col.'" max="'.$col.'" width="'.$ancho.'" customWidth="1"/>';
            }
            $xml .= '</cols>';
        }

        $xml .= '<sheetData>';
        $fila = 1;

        if ($hoja['encabezados'] !== []) {
            $xml .= $this->fila($fila++, $hoja['encabezados'], self::ESTILO_ENCABEZADO);
        }

        foreach ($hoja['filas'] as $valores) {
            $xml .= $this->fila($fila++, array_values($valores));
        }

        return $xml.'</sheetData></worksheet>';
    }

    /**
     * @param  array<int, mixed>  $valores
     */
    private function fila(int $numero, array $valores, ?int $estilo = null): string
    {
        $xml = '<row r="'.$numero.'">';

        foreach ($valores as $i => $valor) {
            $ref = $this->columna($i).$numero;

            if ($valor === null || $valor === '') {
                continue;
            }

            // Los numéricos van sin comillas para que Excel los sume; el resto
            // como inlineStr. is_numeric deja fuera cosas como "007" a propósito.
            if (is_int($valor) || is_float($valor) || (is_string($valor) && is_numeric($valor) && ! str_starts_with($valor, '0'))) {
                $s = $estilo ?? (is_float($valor) || str_contains((string) $valor, '.') ? self::ESTILO_DECIMAL : null);
                $xml .= '<c r="'.$ref.'"'.($s !== null ? ' s="'.$s.'"' : '').'><v>'.$valor.'</v></c>';

                continue;
            }

            $xml .= '<c r="'.$ref.'"'.($estilo !== null ? ' s="'.$estilo.'"' : '').' t="inlineStr"><is><t xml:space="preserve">'
                .$this->escapar((string) $valor).'</t></is></c>';
        }

        return $xml.'</row>';
    }

    /** 0 → A, 25 → Z, 26 → AA. */
    private function columna(int $indice): string
    {
        $letras = '';

        for ($i = $indice; $i >= 0; $i = intdiv($i, 26) - 1) {
            $letras = chr(65 + ($i % 26)).$letras;
        }

        return $letras;
    }

    private function escapar(string $texto): string
    {
        // Los caracteres de control rompen el XML sin dar un error legible.
        $texto = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $texto) ?? $texto;

        return htmlspecialchars($texto, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    /** Excel rechaza estos caracteres y trunca a 31 en el nombre de la pestaña. */
    private function nombreValido(string $nombre): string
    {
        $limpio = str_replace(['\\', '/', '?', '*', '[', ']', ':'], '-', $nombre);

        return mb_substr($limpio, 0, 31) ?: 'Hoja';
    }

    private function contentTypes(): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>';

        foreach ($this->hojas as $i => $hoja) {
            $xml .= '<Override PartName="/xl/worksheets/sheet'.($i + 1).'.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        }

        return $xml.'</Types>';
    }

    private function relsRaiz(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'</Relationships>';
    }

    private function workbook(): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"'
            .' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets>';

        foreach ($this->hojas as $i => $hoja) {
            $xml .= '<sheet name="'.$this->escapar($hoja['nombre']).'" sheetId="'.($i + 1).'" r:id="rId'.($i + 1).'"/>';
        }

        return $xml.'</sheets></workbook>';
    }

    private function relsWorkbook(): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';

        foreach ($this->hojas as $i => $hoja) {
            $xml .= '<Relationship Id="rId'.($i + 1).'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet'.($i + 1).'.xml"/>';
        }

        // El id de estilos va después de las hojas para no chocar con los suyos.
        $estilos = count($this->hojas) + 1;

        return $xml.'<Relationship Id="rId'.$estilos.'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            .'</Relationships>';
    }

    /**
     * Excel exige al menos dos fills (none y gray125) o considera el archivo
     * corrupto, aunque no se usen.
     */
    private function styles(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<fonts count="2">'
            .'<font><sz val="11"/><name val="Calibri"/></font>'
            .'<font><b/><sz val="11"/><name val="Calibri"/></font>'
            .'</fonts>'
            .'<fills count="2"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill></fills>'
            .'<borders count="1"><border/></borders>'
            .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            .'<cellXfs count="3">'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            .'<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/>'
            .'<xf numFmtId="2" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
            .'</cellXfs>'
            .'</styleSheet>';
    }
}
