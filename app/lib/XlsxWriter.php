<?php
/**
 * app/lib/XlsxWriter.php
 * Générateur minimal de fichiers .xlsx (format OOXML) pour les rapports de
 * l'administration, sans dépendance externe (Composer / PhpSpreadsheet) —
 * uniquement l'extension ZipArchive, incluse par défaut dans PHP. Suffisant
 * pour exporter un tableau de données à plat (une seule feuille, en-tête en
 * gras sur fond bleu marine).
 */
class XlsxWriter
{
    /**
     * Construit un fichier .xlsx à partir d'un en-tête et de lignes de
     * données, l'envoie directement au navigateur en téléchargement puis
     * termine le script (comme prévu pour un contrôleur d'export).
     *
     * $rows : tableau de tableaux de valeurs scalaires (une ligne = un
     * tableau indexé dans le même ordre que $headers). Les valeurs int/float
     * sont écrites comme des nombres Excel (utilisables dans des formules /
     * sommes) ; tout le reste (chaînes de caractères) est écrit comme texte
     * — important pour préserver les NINU, téléphones ou références qui
     * peuvent commencer par un zéro.
     *
     * $subtitle et $logoPath sont optionnels : quand $logoPath pointe vers
     * un fichier image lisible (PNG/JPEG), le logo est intégré en flottant
     * au-dessus des premières lignes, et le titre + sous-titre sont écrits
     * à côté ; le tableau de données démarre alors quelques lignes plus bas.
     */
    public static function download(
        string $filename,
        string $sheetTitle,
        array $headers,
        array $rows,
        string $subtitle = '',
        ?string $logoPath = null
    ): void {
        $logo = self::loadLogo($logoPath);

        $tmp = tempnam(sys_get_temp_dir(), 'xlsx');
        $zip = new ZipArchive();
        $zip->open($tmp, ZipArchive::OVERWRITE);

        $zip->addFromString('[Content_Types].xml', self::contentTypesXml($logo !== null));
        $zip->addFromString('_rels/.rels', self::rootRelsXml());
        $zip->addFromString('xl/workbook.xml', self::workbookXml($sheetTitle));
        $zip->addFromString('xl/_rels/workbook.xml.rels', self::workbookRelsXml());
        $zip->addFromString('xl/styles.xml', self::stylesXml());
        $zip->addFromString('xl/worksheets/sheet1.xml', self::sheetXml($sheetTitle, $subtitle, $headers, $rows, $logo !== null));

        if ($logo !== null) {
            $zip->addFromString('xl/worksheets/_rels/sheet1.xml.rels', self::sheetRelsXml());
            $zip->addFromString('xl/drawings/drawing1.xml', self::drawingXml($logo));
            $zip->addFromString('xl/drawings/_rels/drawing1.xml.rels', self::drawingRelsXml($logo['ext']));
            $zip->addFromString('xl/media/logo1.' . $logo['ext'], $logo['bytes']);
        }

        $zip->close();

        $bytes = file_get_contents($tmp);
        @unlink($tmp);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($bytes));
        header('Cache-Control: max-age=0');
        echo $bytes;
        exit;
    }

    /**
     * Charge le logo depuis $logoPath et renvoie ses métadonnées (octets
     * bruts, dimensions en pixels, extension), ou null si le chemin est
     * vide, introuvable, ou pas une image PNG/JPEG reconnue. Le fichier est
     * intégré tel quel (pas de réencodage), donc la transparence PNG est
     * conservée.
     */
    private static function loadLogo(?string $logoPath): ?array
    {
        if ($logoPath === null || $logoPath === '' || !is_file($logoPath)) {
            return null;
        }
        $info = @getimagesize($logoPath);
        if ($info === false) {
            return null;
        }
        $ext = match ($info['mime']) {
            'image/png'  => 'png',
            'image/jpeg' => 'jpeg',
            default      => null,
        };
        if ($ext === null) {
            return null;
        }
        $bytes = file_get_contents($logoPath);
        if ($bytes === false) {
            return null;
        }
        return ['bytes' => $bytes, 'w' => (int) $info[0], 'h' => (int) $info[1], 'ext' => $ext, 'mime' => $info['mime']];
    }

    private static function contentTypesXml(bool $hasLogo): string
    {
        $logoOverrides = $hasLogo
            ? '<Default Extension="png" ContentType="image/png"/>'
                . '<Default Extension="jpeg" ContentType="image/jpeg"/>'
                . '<Override PartName="/xl/drawings/drawing1.xml" ContentType="application/vnd.openxmlformats-officedocument.drawing+xml"/>'
            : '';
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . $logoOverrides
            . '</Types>';
    }

    private static function sheetRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/drawing" Target="../drawings/drawing1.xml"/>'
            . '</Relationships>';
    }

    private static function drawingRelsXml(string $ext): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="../media/logo1.' . $ext . '"/>'
            . '</Relationships>';
    }

    /** Ancre le logo en flottant sur les 4 premières lignes, colonne A, taille fixe ~64x64 px */
    private static function drawingXml(array $logo): string
    {
        $displayPx = 64;
        $emuPerPx  = 9525;
        $w = max(1, (int) $logo['w']);
        $h = max(1, (int) $logo['h']);
        $cx = $displayPx * $emuPerPx;
        $cy = (int) round($displayPx * ($h / $w) * $emuPerPx);

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<xdr:wsDr xmlns:xdr="http://schemas.openxmlformats.org/drawingml/2006/spreadsheetDrawing" xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main">'
            . '<xdr:oneCellAnchor>'
            . '<xdr:from><xdr:col>0</xdr:col><xdr:colOff>19050</xdr:colOff><xdr:row>0</xdr:row><xdr:rowOff>19050</xdr:rowOff></xdr:from>'
            . '<xdr:ext cx="' . $cx . '" cy="' . $cy . '"/>'
            . '<xdr:pic>'
            . '<xdr:nvPicPr><xdr:cNvPr id="1" name="Logo Gourde Numérique"/><xdr:cNvPicPr/></xdr:nvPicPr>'
            . '<xdr:blipFill><a:blip r:embed="rId1" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"/><a:stretch><a:fillRect/></a:stretch></xdr:blipFill>'
            . '<xdr:spPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="' . $cx . '" cy="' . $cy . '"/></a:xfrm><a:prstGeom prst="rect"><a:avLst/></a:prstGeom></xdr:spPr>'
            . '</xdr:pic>'
            . '<xdr:clientData/>'
            . '</xdr:oneCellAnchor>'
            . '</xdr:wsDr>';
    }

    private static function rootRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>';
    }

    private static function workbookXml(string $sheetTitle): string
    {
        $title = self::escapeXml(mb_substr($sheetTitle, 0, 31));
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="' . $title . '" sheetId="1" r:id="rId1"/></sheets>'
            . '</workbook>';
    }

    private static function workbookRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>';
    }

    /**
     * Quatre styles : 0 = cellule normale, 1 = en-tête de tableau (gras,
     * blanc sur bleu marine), 2 = titre du rapport (gras, bleu marine,
     * plus grand), 3 = sous-titre (gris, plus petit).
     */
    private static function stylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="4">'
            . '<font><sz val="11"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="14"/><color rgb="FF0B1B3A"/><name val="Calibri"/></font>'
            . '<font><sz val="9"/><color rgb="FF5C6B8A"/><name val="Calibri"/></font>'
            . '</fonts>'
            . '<fills count="3">'
            . '<fill><patternFill patternType="none"/></fill>'
            . '<fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FF0B1B3A"/><bgColor indexed="64"/></patternFill></fill>'
            . '</fills>'
            . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="4">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"/>'
            . '<xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0" applyFont="1"/>'
            . '<xf numFmtId="0" fontId="3" fillId="0" borderId="0" xfId="0" applyFont="1"/>'
            . '</cellXfs>'
            . '</styleSheet>';
    }

    /**
     * $hasLogo décale le tableau de quelques lignes pour laisser la place
     * au logo flottant + au titre/sous-titre écrits à côté (colonne C).
     */
    private static function sheetXml(string $title, string $subtitle, array $headers, array $rows, bool $hasLogo): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<sheetData>';

        $headerRowNum = 1;
        if ($hasLogo) {
            $xml .= '<row r="1"><c r="C1" s="2" t="inlineStr"><is><t xml:space="preserve">' . self::escapeXml($title) . '</t></is></c></row>';
            if ($subtitle !== '') {
                $xml .= '<row r="2"><c r="C2" s="3" t="inlineStr"><is><t xml:space="preserve">' . self::escapeXml($subtitle) . '</t></is></c></row>';
            }
            $headerRowNum = 4; // laisse une ligne d'espace après le titre/sous-titre
        }

        $xml .= '<row r="' . $headerRowNum . '">';
        foreach (array_values($headers) as $i => $h) {
            $ref = self::colLetter($i) . $headerRowNum;
            $xml .= '<c r="' . $ref . '" s="1" t="inlineStr"><is><t xml:space="preserve">' . self::escapeXml((string) $h) . '</t></is></c>';
        }
        $xml .= '</row>';

        $rowNum = $headerRowNum + 1;
        foreach ($rows as $row) {
            $xml .= '<row r="' . $rowNum . '">';
            foreach (array_values($row) as $i => $val) {
                $ref = self::colLetter($i) . $rowNum;
                if (is_int($val) || is_float($val)) {
                    $xml .= '<c r="' . $ref . '"><v>' . self::formatNumber($val) . '</v></c>';
                } else {
                    $xml .= '<c r="' . $ref . '" t="inlineStr"><is><t xml:space="preserve">' . self::escapeXml((string) $val) . '</t></is></c>';
                }
            }
            $xml .= '</row>';
            $rowNum++;
        }

        $xml .= '</sheetData>';
        if ($hasLogo) {
            $xml .= '<drawing r:id="rId1" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"/>';
        }
        $xml .= '</worksheet>';
        return $xml;
    }

    private static function formatNumber($val): string
    {
        if (is_int($val)) {
            return (string) $val;
        }
        // Évite la notation scientifique et les décimales flottantes parasites (ex. 0.1+0.2)
        $s = number_format((float) $val, 4, '.', '');
        $s = rtrim($s, '0');
        return rtrim($s, '.');
    }

    /** Index de colonne base 0 -> lettre(s) Excel (0 -> A, 25 -> Z, 26 -> AA, ...) */
    private static function colLetter(int $index): string
    {
        $letter = '';
        $index++;
        while ($index > 0) {
            $mod = ($index - 1) % 26;
            $letter = chr(65 + $mod) . $letter;
            $index = intdiv($index - $mod - 1, 26);
        }
        return $letter;
    }

    private static function escapeXml(string $s): string
    {
        return htmlspecialchars($s, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }
}
