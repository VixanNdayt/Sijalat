<?php

namespace App\Support;

/**
 * Penulis file XLSX sederhana tanpa dependency eksternal.
 * ZIP dibuat manual (metode store) sehingga tidak memerlukan ext-zip.
 */
class SimpleXlsx
{
    private const STYLES = [
        'default' => 0,
        'title' => 1,
        'subtitle' => 2,
        'meta' => 3,
        'section' => 4,
        'table_head' => 5,
        'text' => 6,
        'text_bold' => 7,
        'number' => 8,
        'label' => 9,
        'value' => 10,
        'note' => 11,
    ];

    private string $sheetName;
    private array $rows = [];
    private array $merges = [];
    private array $columnWidths = [];

    public function __construct(string $sheetName = 'Laporan')
    {
        $this->sheetName = mb_substr($sheetName, 0, 31);
    }

    public function setColumnWidths(array $widths): void
    {
        $this->columnWidths = $widths;
    }

    /**
     * Tambah satu baris.
     *
     * @param  array<int, mixed>  $cells  Nilai, atau ['v' => nilai, 's' => nama style, 't' => 's'|'n'].
     */
    public function addRow(array $cells, ?float $height = null): int
    {
        $this->rows[] = [
            'cells' => $cells,
            'height' => $height,
        ];

        return count($this->rows);
    }

    public function merge(string $range): void
    {
        $this->merges[] = $range;
    }

    public function output(): string
    {
        $sheet = $this->sheetXml();

        $files = [
            '[Content_Types].xml' => $this->contentTypesXml(),
            '_rels/.rels' => $this->rootRelsXml(),
            'docProps/core.xml' => $this->coreXml(),
            'docProps/app.xml' => $this->appXml(),
            'xl/workbook.xml' => $this->workbookXml(),
            'xl/_rels/workbook.xml.rels' => $this->workbookRelsXml(),
            'xl/styles.xml' => $this->stylesXml(),
            'xl/worksheets/sheet1.xml' => $sheet,
        ];

        return $this->zip($files);
    }

    private function sheetXml(): string
    {
        $maxRow = max(1, count($this->rows));
        $maxColumn = 1;

        foreach ($this->rows as $row) {
            $maxColumn = max($maxColumn, count($row['cells']));
        }

        $dimension = 'A1:' . $this->columnLetter($maxColumn) . $maxRow;

        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $xml .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';
        $xml .= '<dimension ref="' . $dimension . '"/>';
        $xml .= '<sheetViews><sheetView showGridLines="0" workbookViewId="0"/></sheetViews>';
        $xml .= '<sheetFormatPr defaultRowHeight="15"/>';

        if ($this->columnWidths) {
            $xml .= '<cols>';

            foreach ($this->columnWidths as $index => $width) {
                $column = $index + 1;
                $xml .= '<col min="' . $column . '" max="' . $column . '" width="' . $this->number($width) . '" customWidth="1"/>';
            }

            $xml .= '</cols>';
        }

        $xml .= '<sheetData>';

        foreach ($this->rows as $rowIndex => $row) {
            $number = $rowIndex + 1;
            $height = $row['height'];

            $xml .= '<row r="' . $number . '"';

            if ($height !== null) {
                $xml .= ' ht="' . $this->number($height) . '" customHeight="1"';
            }

            $xml .= '>';

            foreach ($row['cells'] as $columnIndex => $cell) {
                if ($cell === null) {
                    continue;
                }

                $reference = $this->columnLetter($columnIndex + 1) . $number;
                $xml .= $this->cellXml($reference, $cell);
            }

            $xml .= '</row>';
        }

        $xml .= '</sheetData>';

        if ($this->merges) {
            $xml .= '<mergeCells count="' . count($this->merges) . '">';

            foreach ($this->merges as $range) {
                $xml .= '<mergeCell ref="' . $range . '"/>';
            }

            $xml .= '</mergeCells>';
        }

        $xml .= '<pageMargins left="0.4" right="0.4" top="0.5" bottom="0.5" header="0.3" footer="0.3"/>';
        $xml .= '<pageSetup orientation="landscape" paperSize="9" fitToWidth="1" fitToHeight="0"/>';
        $xml .= '</worksheet>';

        return $xml;
    }

    private function cellXml(string $reference, mixed $cell): string
    {
        if (is_array($cell)) {
            $value = $cell['v'] ?? '';
            $style = $cell['s'] ?? 'text';
            $type = $cell['t'] ?? null;
        } else {
            $value = $cell;
            $style = is_int($value) || is_float($value) ? 'number' : 'text';
            $type = null;
        }

        $styleIndex = self::STYLES[$style] ?? 0;

        if ($type === 'n' || (! $type && (is_int($value) || is_float($value)))) {
            $number = is_numeric($value) ? $value : 0;

            return '<c r="' . $reference . '" s="' . $styleIndex . '"><v>' . $number . '</v></c>';
        }

        $text = htmlspecialchars((string) $value, ENT_XML1 | ENT_QUOTES, 'UTF-8');

        return '<c r="' . $reference . '" s="' . $styleIndex . '" t="inlineStr"><is><t xml:space="preserve">'
            . $text . '</t></is></c>';
    }

    private function contentTypesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>'
            . '<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>'
            . '</Types>';
    }

    private function rootRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>'
            . '<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>'
            . '</Relationships>';
    }

    private function workbookXml(): string
    {
        $name = htmlspecialchars($this->sheetName, ENT_XML1 | ENT_QUOTES, 'UTF-8');

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<fileVersion appName="xl" lastEdited="7" lowestEdited="7" rupBuild="9303"/>'
            . '<workbookPr defaultThemeVersion="164011"/>'
            . '<sheets><sheet name="' . $name . '" sheetId="1" r:id="rId1"/></sheets>'
            . '</workbook>';
    }

    private function workbookRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>';
    }

    private function coreXml(): string
    {
        $now = gmdate('Y-m-d\TH:i:s\Z');

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" '
            . 'xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" '
            . 'xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
            . '<dc:title>Laporan Analisis SIJALAT</dc:title>'
            . '<dc:creator>SIJALAT</dc:creator>'
            . '<cp:lastModifiedBy>SIJALAT</cp:lastModifiedBy>'
            . '<dcterms:created xsi:type="dcterms:W3CDTF">' . $now . '</dcterms:created>'
            . '<dcterms:modified xsi:type="dcterms:W3CDTF">' . $now . '</dcterms:modified>'
            . '</cp:coreProperties>';
    }

    private function appXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" '
            . 'xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">'
            . '<Application>SIJALAT</Application>'
            . '</Properties>';
    }

    private function stylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="6">'
            . '<font><sz val="11"/><color rgb="FF000000"/><name val="Calibri"/><family val="2"/></font>'
            . '<font><b/><sz val="16"/><color rgb="FF065F46"/><name val="Calibri"/><family val="2"/></font>'
            . '<font><sz val="9"/><color rgb="FF6B7280"/><name val="Calibri"/><family val="2"/></font>'
            . '<font><b/><sz val="10"/><color rgb="FFFFFFFF"/><name val="Calibri"/><family val="2"/></font>'
            . '<font><b/><sz val="11"/><color rgb="FF065F46"/><name val="Calibri"/><family val="2"/></font>'
            . '<font><b/><sz val="11"/><color rgb="FF111827"/><name val="Calibri"/><family val="2"/></font>'
            . '</fonts>'
            . '<fills count="5">'
            . '<fill><patternFill patternType="none"/></fill>'
            . '<fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FF005C43"/><bgColor indexed="64"/></patternFill></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFE8F5EE"/><bgColor indexed="64"/></patternFill></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFF3F6F9"/><bgColor indexed="64"/></patternFill></fill>'
            . '</fills>'
            . '<borders count="2">'
            . '<border><left/><right/><top/><bottom/><diagonal/></border>'
            . '<border><left style="thin"><color rgb="FFD1D5DB"/></left><right style="thin"><color rgb="FFD1D5DB"/></right>'
            . '<top style="thin"><color rgb="FFD1D5DB"/></top><bottom style="thin"><color rgb="FFD1D5DB"/></bottom><diagonal/></border>'
            . '</borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="12">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1" applyAlignment="1"><alignment vertical="center"/></xf>'
            . '<xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0" applyFont="1" applyAlignment="1"><alignment vertical="center"/></xf>'
            . '<xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0" applyFont="1" applyAlignment="1"><alignment vertical="center"/></xf>'
            . '<xf numFmtId="0" fontId="4" fillId="3" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment vertical="center"/></xf>'
            . '<xf numFmtId="0" fontId="3" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment vertical="center" wrapText="1"/></xf>'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment vertical="center" wrapText="1"/></xf>'
            . '<xf numFmtId="0" fontId="5" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1" applyAlignment="1"><alignment vertical="center" wrapText="1"/></xf>'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            . '<xf numFmtId="0" fontId="5" fillId="4" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment vertical="center" wrapText="1"/></xf>'
            . '<xf numFmtId="0" fontId="0" fillId="4" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment vertical="center" wrapText="1"/></xf>'
            . '<xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0" applyFont="1" applyAlignment="1"><alignment vertical="center"/></xf>'
            . '</cellXfs>'
            . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            . '</styleSheet>';
    }

    private function zip(array $files): string
    {
        $local = '';
        $central = '';
        $offset = 0;
        $count = 0;

        foreach ($files as $name => $data) {
            $name = (string) $name;
            $crc = crc32($data);
            $length = strlen($data);
            $nameLength = strlen($name);

            $header = pack(
                'VvvvvvVVVvv',
                0x04034b50, // signature
                20,         // version needed
                0,          // flags
                0,          // compression: store
                0,          // time
                0x21,       // date (1980-01-01)
                $crc,
                $length,
                $length,
                $nameLength,
                0           // extra length
            );

            $local .= $header . $name . $data;

            $central .= pack(
                'VvvvvvvVVVvvvvvVV',
                0x02014b50, // signature
                20,         // version made by
                20,         // version needed
                0,          // flags
                0,          // compression
                0,          // time
                0x21,       // date
                $crc,
                $length,
                $length,
                $nameLength,
                0,          // extra length
                0,          // comment length
                0,          // disk number
                0,          // internal attributes
                0,          // external attributes
                $offset     // local header offset
            ) . $name;

            $offset += strlen($header) + $nameLength + $length;
            $count++;
        }

        $end = pack(
            'VvvvvVVv',
            0x06054b50,
            0,
            0,
            $count,
            $count,
            strlen($central),
            $offset,
            0
        );

        return $local . $central . $end;
    }

    private function columnLetter(int $number): string
    {
        $letters = '';

        while ($number > 0) {
            $number--;
            $letters = chr(65 + ($number % 26)) . $letters;
            $number = intdiv($number, 26);
        }

        return $letters ?: 'A';
    }

    private function number(float|int $value): string
    {
        return rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.');
    }
}
