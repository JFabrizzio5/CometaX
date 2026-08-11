<?php

namespace App\Support\Export;

/**
 * Escritor de PDF sin dependencias.
 *
 * Genera PDF 1.4 a mano con las fuentes base (Helvetica), que todo lector trae
 * incrustadas. Alcanza para reportes tabulares; no hace imágenes ni tipografías
 * propias — si algún día el reporte necesita eso, ahí sí conviene una librería.
 *
 * El texto se pasa a CP1252 (WinAnsiEncoding), que cubre acentos, ñ, ¿ y ¡.
 * Lo que no entra en esa tabla (— y las comillas tipográficas) se sustituye
 * antes en vez de perderse como '?'.
 */
class PdfWriter
{
    private const ANCHO = 595.28;   // A4 vertical, en puntos

    private const ALTO = 841.89;

    private const MARGEN = 40.0;

    /** @var array<int, string> flujos de contenido, uno por página */
    private array $paginas = [];

    private string $actual = '';

    private float $y = 0.0;

    private bool $horizontal;

    public function __construct(bool $horizontal = false)
    {
        $this->horizontal = $horizontal;
        $this->nuevaPagina();
    }

    private function ancho(): float
    {
        return $this->horizontal ? self::ALTO : self::ANCHO;
    }

    private function alto(): float
    {
        return $this->horizontal ? self::ANCHO : self::ALTO;
    }

    public function nuevaPagina(): void
    {
        if ($this->actual !== '') {
            $this->paginas[] = $this->actual;
        }

        $this->actual = '';
        $this->y = $this->alto() - self::MARGEN;
    }

    /** Espacio vertical libre antes de tener que saltar de página. */
    private function asegurarEspacio(float $necesario): void
    {
        if ($this->y - $necesario < self::MARGEN) {
            $this->nuevaPagina();
        }
    }

    public function titulo(string $texto): self
    {
        $this->asegurarEspacio(30);
        $this->texto($texto, self::MARGEN, $this->y, 16, true);
        $this->y -= 24;

        return $this;
    }

    public function subtitulo(string $texto): self
    {
        $this->asegurarEspacio(20);
        $this->texto($texto, self::MARGEN, $this->y, 11, true);
        $this->y -= 16;

        return $this;
    }

    public function parrafo(string $texto, float $tamano = 9.5): self
    {
        $this->asegurarEspacio(16);
        $this->texto($texto, self::MARGEN, $this->y, $tamano, false, '0.35');
        $this->y -= $tamano + 5;

        return $this;
    }

    public function espacio(float $puntos = 10): self
    {
        $this->y -= $puntos;

        return $this;
    }

    /**
     * Tabla con encabezado repetido en cada página.
     *
     * @param  array<int, string>  $encabezados
     * @param  array<int, array<int, string>>  $filas
     * @param  array<int, float>  $proporciones  peso de cada columna (se normaliza)
     * @param  array<int, string>  $alineaciones  'izq' | 'der'
     */
    public function tabla(array $encabezados, array $filas, array $proporciones, array $alineaciones = []): self
    {
        $util = $this->ancho() - (self::MARGEN * 2);
        $suma = array_sum($proporciones) ?: 1;
        $anchos = array_map(fn ($p) => ($p / $suma) * $util, $proporciones);

        $this->asegurarEspacio(40);
        $this->encabezadoTabla($encabezados, $anchos, $alineaciones);

        foreach ($filas as $fila) {
            if ($this->y - 16 < self::MARGEN) {
                $this->nuevaPagina();
                $this->encabezadoTabla($encabezados, $anchos, $alineaciones);
            }

            $x = self::MARGEN;
            foreach (array_values($fila) as $i => $celda) {
                $this->celda((string) $celda, $x, $anchos[$i] ?? 60, $alineaciones[$i] ?? 'izq', 9);
                $x += $anchos[$i] ?? 60;
            }

            $this->y -= 14;
            $this->linea(self::MARGEN, $this->y + 4, $this->ancho() - self::MARGEN, $this->y + 4, '0.88');
        }

        $this->y -= 6;

        return $this;
    }

    /**
     * @param  array<int, string>  $encabezados
     * @param  array<int, float>  $anchos
     * @param  array<int, string>  $alineaciones
     */
    private function encabezadoTabla(array $encabezados, array $anchos, array $alineaciones): void
    {
        $x = self::MARGEN;

        foreach ($encabezados as $i => $titulo) {
            $this->celda($titulo, $x, $anchos[$i] ?? 60, $alineaciones[$i] ?? 'izq', 8.5, true, '0.4');
            $x += $anchos[$i] ?? 60;
        }

        $this->y -= 12;
        $this->linea(self::MARGEN, $this->y + 4, $this->ancho() - self::MARGEN, $this->y + 4, '0.6');
        $this->y -= 4;
    }

    private function celda(string $texto, float $x, float $ancho, string $alineacion, float $tamano, bool $negritas = false, string $gris = '0.15'): void
    {
        $texto = $this->recortar($texto, $ancho - 6, $tamano, $negritas);
        $posX = $alineacion === 'der'
            ? $x + $ancho - 3 - $this->anchoTexto($texto, $tamano, $negritas)
            : $x + 3;

        $this->texto($texto, $posX, $this->y, $tamano, $negritas, $gris);
    }

    private function texto(string $texto, float $x, float $y, float $tamano, bool $negritas = false, string $gris = '0'): void
    {
        $fuente = $negritas ? '/F2' : '/F1';
        $this->actual .= sprintf(
            "BT %s %.2f Tf %s g %.2f %.2f Td (%s) Tj ET\n",
            $fuente, $tamano, $gris, $x, $y, $this->escapar($texto)
        );
    }

    private function linea(float $x1, float $y1, float $x2, float $y2, string $gris): void
    {
        $this->actual .= sprintf("%s G 0.5 w %.2f %.2f m %.2f %.2f l S\n", $gris, $x1, $y1, $x2, $y2);
    }

    /**
     * Anchos reales de Helvetica por carácter serían una tabla enorme; 0.5 del
     * tamaño de fuente es una aproximación suficiente para decidir recortes.
     */
    private function anchoTexto(string $texto, float $tamano, bool $negritas = false): float
    {
        return mb_strlen($texto) * $tamano * ($negritas ? 0.55 : 0.5);
    }

    private function recortar(string $texto, float $anchoMax, float $tamano, bool $negritas): string
    {
        if ($this->anchoTexto($texto, $tamano, $negritas) <= $anchoMax) {
            return $texto;
        }

        $maximo = (int) floor($anchoMax / ($tamano * ($negritas ? 0.55 : 0.5)));

        return mb_substr($texto, 0, max(1, $maximo - 1)).'…';
    }

    /**
     * A CP1252 y escape de los caracteres con significado en un literal PDF.
     */
    private function escapar(string $texto): string
    {
        // El guion largo y las comillas curvas no existen en CP1252 tal cual:
        // sin esto iconv//TRANSLIT los vuelve '?' y se ve como error.
        $texto = strtr($texto, [
            '—' => '-', '–' => '-', '…' => '...',
            '“' => '"', '”' => '"', '‘' => "'", '’' => "'",
        ]);

        $convertido = @iconv('UTF-8', 'CP1252//TRANSLIT', $texto);
        if ($convertido !== false) {
            $texto = $convertido;
        }

        return str_replace(['\\', '(', ')', "\r", "\n"], ['\\\\', '\\(', '\\)', '', ' '], $texto);
    }

    public function generar(): string
    {
        if ($this->actual !== '') {
            $this->paginas[] = $this->actual;
            $this->actual = '';
        }

        $total = count($this->paginas);
        $objetos = [];

        // 1 catálogo, 2 páginas, 3 y 4 fuentes; las páginas arrancan en el 5.
        $primerHijo = 5;
        $ids = [];
        for ($i = 0; $i < $total; $i++) {
            $ids[] = $primerHijo + ($i * 2);
        }

        $objetos[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objetos[2] = '<< /Type /Pages /Kids ['.implode(' ', array_map(fn ($id) => $id.' 0 R', $ids)).'] /Count '.$total.' >>';
        $objetos[3] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
        $objetos[4] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>';

        $caja = sprintf('[0 0 %.2f %.2f]', $this->ancho(), $this->alto());

        foreach ($this->paginas as $i => $contenido) {
            $idPagina = $ids[$i];
            $idFlujo = $idPagina + 1;

            $objetos[$idPagina] = '<< /Type /Page /Parent 2 0 R /MediaBox '.$caja
                .' /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents '.$idFlujo.' 0 R >>';
            $objetos[$idFlujo] = '<< /Length '.strlen($contenido).' >>stream'."\n".$contenido."\n".'endstream';
        }

        ksort($objetos);

        $pdf = "%PDF-1.4\n";
        $desplazamientos = [];

        foreach ($objetos as $id => $cuerpo) {
            $desplazamientos[$id] = strlen($pdf);
            $pdf .= $id." 0 obj\n".$cuerpo."\nendobj\n";
        }

        $inicioXref = strlen($pdf);
        $ultimo = max(array_keys($objetos));

        $pdf .= "xref\n0 ".($ultimo + 1)."\n0000000000 65535 f \n";
        for ($id = 1; $id <= $ultimo; $id++) {
            // Los huecos de numeración se marcan como libres o el lector se queja.
            $pdf .= isset($desplazamientos[$id])
                ? sprintf("%010d 00000 n \n", $desplazamientos[$id])
                : "0000000000 65535 f \n";
        }

        return $pdf.'trailer << /Size '.($ultimo + 1)." /Root 1 0 R >>\nstartxref\n".$inicioXref."\n%%EOF";
    }
}
