<?php
/**
 * app/lib/SimplePdf.php
 * Générateur minimal de rapports PDF tabulaires, sans dépendance externe
 * (pas de Composer / TCPDF / mPDF) : construit directement les objets PDF
 * bruts en utilisant les polices standard Helvetica (aucun fichier de
 * police à embarquer). Suffisant pour des rapports "liste + en-têtes" avec
 * pagination automatique — ce n'est pas un moteur de mise en page général.
 */
class SimplePdf
{
    private const PAGE_W_PORTRAIT = 595.28; // A4 en points (72 dpi)
    private const PAGE_H_PORTRAIT = 841.89;
    private const MARGIN = 34.0;
    private const ROW_H  = 16.0;
    private const LOGO_SIZE = 34.0; // pt — logo carré affiché en en-tête de chaque page

    private float $pageW;
    private float $pageH;
    private array $headers;
    private array $colWidths;
    private string $title;
    private string $subtitle;

    /** Données JPEG du logo (déjà aplati sur fond blanc), ou null si aucun logo */
    private ?string $logoJpeg = null;
    private int $logoW = 0;
    private int $logoH = 0;

    /** @var string[] contenu (flux) déjà finalisé de chaque page terminée */
    private array $pagesContent = [];
    private string $current = '';
    private float $y = 0;
    private int $pageIndex = 0;

    public function __construct(string $title, string $subtitle, array $headers, array $colWidths, string $orientation = 'landscape')
    {
        $this->title     = $title;
        $this->subtitle  = $subtitle;
        $this->headers   = array_values($headers);
        $this->colWidths = array_values($colWidths);

        if ($orientation === 'landscape') {
            $this->pageW = self::PAGE_H_PORTRAIT;
            $this->pageH = self::PAGE_W_PORTRAIT;
        } else {
            $this->pageW = self::PAGE_W_PORTRAIT;
            $this->pageH = self::PAGE_H_PORTRAIT;
        }

        $this->startPage();
    }

    /**
     * Définit le logo affiché en en-tête de chaque page (à appeler avant
     * addRows(), puisque la première page est déjà construite dans le
     * constructeur). Accepte un PNG (avec transparence) : il est aplati sur
     * fond blanc et réencodé en JPEG en mémoire via GD, pour rester dans un
     * générateur PDF sans dépendance externe. Échoue silencieusement si le
     * fichier est introuvable ou si l'extension GD n'est pas disponible —
     * un rapport sans logo reste préférable à une erreur fatale.
     */
    public function setLogo(string $path): void
    {
        if (!is_file($path) || !function_exists('imagecreatefrompng')) {
            return;
        }
        $src = @imagecreatefrompng($path);
        if ($src === false) {
            return;
        }

        $w = imagesx($src);
        $h = imagesy($src);
        $flat = imagecreatetruecolor($w, $h);
        $white = imagecolorallocate($flat, 255, 255, 255);
        imagefill($flat, 0, 0, $white);
        imagealphablending($flat, true);
        imagecopy($flat, $src, 0, 0, 0, 0, $w, $h);
        imagedestroy($src);

        ob_start();
        imagejpeg($flat, null, 90);
        $jpeg = ob_get_clean();
        imagedestroy($flat);

        if ($jpeg === false || $jpeg === '') {
            return;
        }

        $this->logoJpeg = $jpeg;
        $this->logoW    = $w;
        $this->logoH    = $h;

        // Le logo doit apparaître dès la première page, déjà construite par
        // le constructeur : on la reconstruit maintenant qu'on le connaît.
        $this->pagesContent = [];
        $this->pageIndex    = 0;
        $this->startPage();
    }

    /** Ajoute des lignes de données, avec saut de page + réaffichage de l'en-tête automatique */
    public function addRows(array $rows): void
    {
        foreach ($rows as $row) {
            if ($this->y < self::MARGIN + self::ROW_H) {
                $this->endPage();
                $this->startPage();
            }
            $this->drawRow(array_values($row));
        }
    }

    /** Termine le document, l'envoie au navigateur en téléchargement, puis quitte le script */
    public function output(string $filename): void
    {
        $this->endPage();
        $bytes = $this->build();

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($bytes));
        header('Cache-Control: max-age=0');
        echo $bytes;
        exit;
    }

    /* ================= Mise en page interne ================= */

    private function startPage(): void
    {
        $this->pageIndex++;
        $this->current = '';
        $topY    = $this->pageH - self::MARGIN;
        $this->y = $topY;
        $isFirst = $this->pageIndex === 1;
        $hasLogo = $this->logoJpeg !== null;

        // Logo en en-tête sur chaque page (cohérence de marque sur les rapports multi-pages)
        if ($hasLogo) {
            $this->current .= sprintf(
                "q %.2F 0 0 %.2F %.2F %.2F cm /Im1 Do Q\n",
                self::LOGO_SIZE,
                self::LOGO_SIZE,
                self::MARGIN,
                $topY - self::LOGO_SIZE
            );
        }

        $textX = self::MARGIN + ($hasLogo ? self::LOGO_SIZE + 10 : 0);
        $bandBottom = $topY;

        if ($isFirst) {
            $this->drawText($this->title, $textX, $topY - 12, 15, true, [11, 27, 58]);
            if ($this->subtitle !== '') {
                $this->drawText($this->subtitle, $textX, $topY - 27, 9, false, [92, 107, 138]);
            }
            $bandBottom = min($bandBottom, $topY - 30);
        }
        if ($hasLogo) {
            $bandBottom = min($bandBottom, $topY - self::LOGO_SIZE);
        }

        $this->y = ($isFirst || $hasLogo) ? ($bandBottom - 8) : $topY;

        $this->drawHeaderRow();
    }

    private function endPage(): void
    {
        $this->pagesContent[] = $this->current;
    }

    private function drawHeaderRow(): void
    {
        $x = self::MARGIN;
        $w = array_sum($this->colWidths);
        // Bande de fond bleu marine derrière la ligne d'en-tête
        $this->current .= sprintf("0.043 0.106 0.227 rg\n%.2F %.2F %.2F %.2F re f\n", $x, $this->y - 11, $w, self::ROW_H);

        foreach ($this->headers as $i => $h) {
            $colW = $this->colWidths[$i] ?? 60;
            $this->drawText($this->truncate((string) $h, $colW, 9, true), $x + 3, $this->y - 8, 9, true, [255, 255, 255]);
            $x += $colW;
        }
        $this->y -= self::ROW_H;
    }

    private function drawRow(array $values): void
    {
        $x = self::MARGIN;
        foreach ($values as $i => $v) {
            $colW = $this->colWidths[$i] ?? 60;
            $this->drawText($this->truncate((string) $v, $colW, 8, false), $x + 3, $this->y - 8, 8, false, [11, 27, 58]);
            $x += $colW;
        }
        $this->y -= self::ROW_H;
    }

    private function drawText(string $text, float $x, float $y, float $size, bool $bold, array $rgb): void
    {
        $font = $bold ? '/F2' : '/F1';
        [$r, $g, $b] = $rgb;
        $enc = $this->pdfEscape($this->toLatin1($text));
        $this->current .= sprintf(
            "BT %s %.1F Tf %.3F %.3F %.3F rg %.2F %.2F Td (%s) Tj ET\n",
            $font,
            $size,
            $r / 255,
            $g / 255,
            $b / 255,
            $x,
            $y,
            $enc
        );
    }

    private function truncate(string $text, float $colWidth, float $size, bool $bold): string
    {
        $avgChar  = $size * ($bold ? 0.62 : 0.52);
        $maxChars = max(3, (int) floor(($colWidth - 6) / max($avgChar, 1)));
        if (mb_strlen($text) <= $maxChars) {
            return $text;
        }
        return mb_substr($text, 0, max(0, $maxChars - 1)) . '…';
    }

    private function toLatin1(string $text): string
    {
        $converted = @iconv('UTF-8', 'CP1252//TRANSLIT//IGNORE', $text);
        return $converted !== false ? $converted : (preg_replace('/[^\x20-\x7E]/', '?', $text) ?? $text);
    }

    private function pdfEscape(string $text): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
    }

    /* ================= Assemblage binaire du PDF ================= */

    private function build(): string
    {
        $hasLogo = $this->logoJpeg !== null;

        $fontRegularNum = 3;
        $fontBoldNum    = 4;
        $imageObjNum    = $hasLogo ? 5 : null;
        $firstPageNum   = $hasLogo ? 6 : 5;

        $pageCount      = count($this->pagesContent);
        $pageObjNums    = [];
        $contentObjNums = [];
        for ($i = 0; $i < $pageCount; $i++) {
            $pageObjNums[$i]    = $firstPageNum + $i * 2;
            $contentObjNums[$i] = $firstPageNum + $i * 2 + 1;
        }

        $objects = [];
        $kids = implode(' ', array_map(static fn ($n) => "$n 0 R", $pageObjNums));
        $objects[1] = "<< /Type /Catalog /Pages 2 0 R >>";
        $objects[2] = "<< /Type /Pages /Kids [$kids] /Count $pageCount >>";
        $objects[$fontRegularNum] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>";
        $objects[$fontBoldNum]    = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>";

        if ($hasLogo) {
            $objects[$imageObjNum] = "IMG:" . $this->logoJpeg;
        }

        $mediaW = $this->fmtNum($this->pageW);
        $mediaH = $this->fmtNum($this->pageH);

        $xobjectDict = $hasLogo ? " /XObject << /Im1 {$imageObjNum} 0 R >>" : '';

        for ($i = 0; $i < $pageCount; $i++) {
            $objects[$pageObjNums[$i]] =
                "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 {$mediaW} {$mediaH}] "
                . "/Resources << /Font << /F1 {$fontRegularNum} 0 R /F2 {$fontBoldNum} 0 R >>{$xobjectDict} >> "
                . "/Contents {$contentObjNums[$i]} 0 R >>";
            $objects[$contentObjNums[$i]] = "STREAM:" . $this->pagesContent[$i];
        }

        ksort($objects);

        $pdf = "%PDF-1.4\n";
        $offsets = [];
        $maxObjNum = max(array_keys($objects));

        foreach ($objects as $num => $body) {
            $offsets[$num] = strlen($pdf);
            if (str_starts_with($body, 'STREAM:')) {
                $stream = substr($body, 7);
                $pdf .= "$num 0 obj\n<< /Length " . strlen($stream) . " >>\nstream\n" . $stream . "endstream\nendobj\n";
            } elseif (str_starts_with($body, 'IMG:')) {
                $stream = substr($body, 4);
                $pdf .= "$num 0 obj\n<< /Type /XObject /Subtype /Image /Width {$this->logoW} /Height {$this->logoH} "
                    . "/ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length " . strlen($stream) . " >>\n"
                    . "stream\n" . $stream . "\nendstream\nendobj\n";
            } else {
                $pdf .= "$num 0 obj\n$body\nendobj\n";
            }
        }

        $xrefStart = strlen($pdf);
        $pdf .= "xref\n0 " . ($maxObjNum + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";
        for ($n = 1; $n <= $maxObjNum; $n++) {
            $pdf .= isset($offsets[$n]) ? sprintf("%010d 00000 n \n", $offsets[$n]) : "0000000000 00000 f \n";
        }
        $pdf .= "trailer\n<< /Size " . ($maxObjNum + 1) . " /Root 1 0 R >>\nstartxref\n$xrefStart\n%%EOF";

        return $pdf;
    }

    private function fmtNum(float $v): string
    {
        $s = number_format($v, 2, '.', '');
        $s = rtrim($s, '0');
        return rtrim($s, '.');
    }
}
