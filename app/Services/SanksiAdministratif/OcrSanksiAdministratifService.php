<?php

namespace App\Services\SanksiAdministratif;

use Illuminate\Support\Facades\Log;

class OcrSanksiAdministratifService
{
    private float $confidence = 0.0;
    private string $tesseractBinary;
    private string $popplerBinary;
    private string $language;

    private const FIELD_ANCHORS = [
        'no' => ['No', 'No.', 'Nomor'],
        'nama_pelaku_usaha' => ['Nama Pelaku Usaha', 'Pelaku Usaha', 'Nama'],
        'nib' => ['NIB', 'Nomor Induk Berusaha', 'Nomor Induk Berusaha (NIB)', 'Berusaha (NIB)'],
        'alamat' => ['Alamat', 'Jalan'],
        'kelurahan' => ['Kelurahan', 'Kelurahan:', 'Gampong', 'Desa', 'Kelurahan/Desa'],
        'kecamatan' => ['Kecamatan', 'Kecamatan:'],
        'kabupaten_kota' => ['Kabupaten/Kota:', 'Kabupaten/Kota', 'Kab/Kota', 'Kab. Kota', 'Kabupaten', 'Kota'],
        'provinsi' => ['Provinsi:', 'Provinsi'],
        'penanaman_modal' => ['Penanaman Modal', 'Jenis Penanaman Modal', 'Penanaman Modal Dalam Negeri (PMDN)', 'Penanaman Modal Asing (PMA)', 'PMDN', 'PMA'],
        'skala_usaha' => ['Skala Usaha', 'Usaha Mikro', 'Usaha Kecil', 'Usaha Menengah', 'Usaha Besar'],
    ];

    public function __construct()
    {
        $this->tesseractBinary = config('ocr.tesseract.binary', 'C:/Program Files/Tesseract-OCR/tesseract.exe');
        $this->popplerBinary = config('ocr.poppler.binary', 'pdftoppm');
        $this->language = config('ocr.tesseract.language', 'ind+eng');
    }

    public function getConfidence(): float { return $this->confidence; }

    public function processFile(string $filePath, string $filename): array
    {
        $text = $this->extractText($filePath);
        Log::info('OCR Sanksi Extract', ['file' => $filename, 'text_length' => strlen($text), 'text_preview' => mb_substr($text, 0, 2000)]);
        return $this->parse($text, $filename);
    }

    public function parse(string $text, string $filename): array
    {
        $trimmed = trim($text);
        if (str_starts_with($trimmed, '[') && str_ends_with(trim($trimmed, " \n\r\t"), ']')) {
            $decoded = json_decode($trimmed, true);
            if (is_array($decoded) && isset($decoded[0]['nib']) || isset($decoded[0]['alamat'])) {
                return $this->parseJsonArray($decoded, $filename);
            }
            if (is_array($decoded)) {
                $maybe = $this->tryParseJsonArray($decoded, $filename);
                if (!empty($maybe)) return $maybe;
            }
        }
        $oss = $this->parseOssLayout($text);
        if (!empty($oss)) {
            Log::info('OCR Sanksi Parse (OSS layout - no-regex)', ['text_length' => strlen($text), 'records_found' => count($oss), 'oss_preview' => array_slice($oss, 0, 2)]);
            return $this->finalizeRecords($oss, $filename);
        }
        $normalized = $this->normalizeText($text);
        $pairs = $this->parseAllPairs($normalized);
        $sectionCounts = $this->countSectionPairs($pairs);
        $records = $this->buildRecordsFromPairs($pairs);
        Log::info('OCR Sanksi Parse', ['text_length' => strlen($text), 'normalized_length' => strlen($normalized), 'pairs_found' => count($pairs), 'pairs_per_section' => $sectionCounts, 'records_found' => count($records)]);
        if (empty($records)) return [];
        return $this->finalizeRecords($records, $filename);
    }

    // ——— Helpers tanpa regex split ———
    private function normalizeSpaces(string $s): string
    {
        $s = trim($s);
        while (str_contains($s, '  ')) $s = str_replace('  ', ' ', $s);
        return $s;
    }

    private function isNIBLine(string $line): bool
    {
        if (!str_contains($line, 'Alamat:')) return false;
        $first = explode(' ', trim($line), 2)[0] ?? '';
        $digits = '';
        $len = strlen($first);
        for ($i = 0; $i < $len; $i++) if (ctype_digit($first[$i])) $digits .= $first[$i];
        $dlen = strlen($digits);
        return $dlen >= 11 && $dlen <= 13;
    }

    private function extractLeadingDigits(string $line): string
    {
        $first = explode(' ', trim($line), 2)[0] ?? '';
        $d = '';
        $len = strlen($first);
        for ($i = 0; $i < $len; $i++) if (ctype_digit($first[$i])) $d .= $first[$i];
        return $d;
    }

    private function isPMDNLine(string $line): bool
    {
        return str_contains($line, 'Dalam Negeri') && (str_contains($line, 'PMDN') || str_contains($line, '(PMDN)'));
    }

    private function isNoLine(string $line): bool
    {
        $t = trim($line);
        return str_starts_with($t, 'No.') || $t === 'No.1]' || $t === 'No.1';
    }

    private function cleanProv(string $prov): string
    {
        $prov = trim($prov);
        $marker = 'Baris per Halaman';
        $pos = stripos($prov, $marker);
        if ($pos !== false) $prov = trim(substr($prov, 0, $pos));
        return $prov;
    }

    /**
     * Parser khusus layout PDF 2.Rancangan sanksi TANPA regex split.
     * - Nama: "v 2 BUMG LEUNGGOH JAYA"
     * - NIB split: "91203009508 Alamat: Penanaman Modal" + "31 Gampong Tuha Gogo Dalam Negeri (PMDN)"
     */
    private function parseOssLayout(string $text): array
    {
        // Tanpa preg_split — normalisasi newline manual
        $tmp = str_replace("\r\n", "\n", $text);
        $tmp = str_replace("\r", "\n", $tmp);
        $rawParts = explode("\n", $tmp);
        $lines = [];
        foreach ($rawParts as $p) {
            $t = trim($p);
            if ($t !== '') $lines[] = $t;
        }

        // 1. Nama dari "v <no> <nama>" tanpa preg_match
        $nameMap = [];
        foreach ($lines as $l) {
            $t = trim($l);
            if ($t === '') continue;
            $first = strtolower($t[0] ?? '');
            if ($first !== 'v') continue;
            $afterV = trim(substr($t, 1));
            if ($afterV === '' || !isset($afterV[0]) || !ctype_digit($afterV[0])) continue;
            $numStr = '';
            $idx = 0;
            $alen = strlen($afterV);
            while ($idx < $alen && ctype_digit($afterV[$idx])) { $numStr .= $afterV[$idx]; $idx++; }
            $no = (int)$numStr;
            $name = trim(substr($afterV, $idx));
            if (mb_strlen($name) < 3) continue;
            $low = strtolower($name);
            if (str_contains($low, 'beranda') || str_contains($low, 'pelaporan') || str_contains($low, 'kementerian') || str_contains($low, 'usulan')) continue;
            $nameMap[$no] = $name;
        }
        if (empty($nameMap)) return [];

        // 2. Indeks NIB pertama tanpa regex
        $firstNibIdx = null;
        foreach ($lines as $idx => $l) {
            if ($this->isNIBLine($l)) { $firstNibIdx = $idx; break; }
        }
        if ($firstNibIdx === null) return [];

        $records = [];

        // 3. Record #1 tanpa NIB: "Kampong Pukat" + Kecamatan/Kab/Prov
        $preSlice = array_slice($lines, 0, $firstNibIdx);
        $foundPre = false;
        $preCount = count($preSlice);
        for ($k = 0; $k < $preCount; $k++) {
            $val = $preSlice[$k];
            if ($val === 'Kecamatan:' || $val === 'Kabupaten/Kota:' || $val === 'Provinsi:' || str_contains($val, 'BERANDA') || str_contains($val, 'KEMENTERIAN')) continue;
            $next1 = $preSlice[$k + 1] ?? '';
            $next2 = $preSlice[$k + 2] ?? '';
            if ($next1 === 'Kecamatan:' && $next2 !== '') {
                $hasKab = false;
                $limit = $k + 10 < $preCount ? $k + 10 : $preCount;
                for ($t = $k + 3; $t < $limit; $t++) if ($preSlice[$t] === 'Kabupaten/Kota:') { $hasKab = true; break; }
                if ($hasKab) {
                    $kel = $val;
                    $kec = $next2;
                    $kab = null; $prov = null;
                    for ($t = $k + 3; $t < $preCount; $t++) {
                        if ($preSlice[$t] === 'Kabupaten/Kota:' && isset($preSlice[$t + 1])) $kab = $preSlice[$t + 1];
                        if ($preSlice[$t] === 'Provinsi:') {
                            $prov = $preSlice[$t + 1] ?? null;
                            if ($prov !== null) $prov = trim($prov);
                            $prov = $prov !== '' ? $prov : null;
                            if ($prov) $prov = $this->cleanProv($prov);
                            break;
                        }
                    }
                    $records[] = ['no' => 1, 'nama_pelaku_usaha' => null, 'nib' => null, 'jenis_penanaman_modal' => null, 'skala_usaha' => null, 'alamat' => null, 'kelurahan' => $kel ?: null, 'kecamatan' => $kec ?: null, 'kab_kota' => $kab ?: null, 'provinsi' => $prov ?: null];
                    $foundPre = true;
                    break;
                }
            }
        }
        if (!$foundPre) {
            $records[] = ['no' => 1, 'nama_pelaku_usaha' => null, 'nib' => null, 'jenis_penanaman_modal' => null, 'skala_usaha' => null, 'alamat' => null, 'kelurahan' => null, 'kecamatan' => null, 'kab_kota' => null, 'provinsi' => null];
        }

        // 4. Walk NIB blocks tanpa regex split
        $i = $firstNibIdx;
        $n = count($lines);
        while ($i < $n) {
            $line = $lines[$i];
            if (!$this->isNIBLine($line)) { $i++; continue; }
            $nibPrefix = $this->extractLeadingDigits($line);
            $cur = ['no' => null, 'nama_pelaku_usaha' => null, 'nib' => null, 'jenis_penanaman_modal' => null, 'skala_usaha' => null, 'alamat' => null, 'kelurahan' => null, 'kecamatan' => null, 'kab_kota' => null, 'provinsi' => null];
            $next = $lines[$i + 1] ?? '';
            $consumed = 1;

            // Cek apakah next adalah baris PMDN
            if ($this->isPMDNLine($next)) {
                $leading = $this->extractLeadingDigits($next);
                $isSuffix = $leading !== '' && strlen($leading) <= 2 && ctype_digit($leading);
                // Ambil alamatPart: hapus leading digits dan hapus "Dalam Negeri" + "(PMDN)" manual
                $alamatPart = $next;
                if ($isSuffix) {
                    // hapus leading digits
                    $alamatPart = trim(substr(trim($next), strlen($leading)));
                }
                // hapus "Dalam Negeri" dan "PMDN" secara string, tanpa regex
                $alamatPart = str_replace('Dalam Negeri', '', $alamatPart);
                $alamatPart = str_replace('(PMDN)', '', $alamatPart);
                $alamatPart = str_replace('PMDN', '', $alamatPart);
                $alamatPart = str_replace('(', '', $alamatPart);
                $alamatPart = str_replace(')', '', $alamatPart);
                $alamatPart = $this->normalizeSpaces($alamatPart);

                // NIB 13 digit: prefix + suffix
                if ($isSuffix && strlen($nibPrefix) < 13 && (strlen($nibPrefix) + strlen($leading) === 13)) {
                    $cur['nib'] = $nibPrefix . $leading;
                    $cur['alamat'] = $alamatPart;
                } else {
                    // prefix sudah 13 atau suffix bukan bagian NIB
                    $cur['nib'] = $nibPrefix;
                    if ($isSuffix) {
                        $cur['alamat'] = $this->normalizeSpaces($leading . ' ' . $alamatPart);
                    } else {
                        $cur['alamat'] = $alamatPart;
                    }
                }
                $cur['jenis_penanaman_modal'] = 'Penanaman Modal Dalam Negeri (PMDN)';
                $consumed = 2;

                // Cek kelanjutan "No.1]" -> No.11 tanpa regex
                $peek = $lines[$i + $consumed] ?? '';
                $peekTrim = trim($peek);
                if ($peekTrim === 'No.1]' || $peekTrim === 'No.1') {
                    $cur['alamat'] = $this->normalizeSpaces($cur['alamat'] . ' No.11');
                    $cur['alamat'] = str_replace(' - ', ' ', $cur['alamat']);
                    $consumed++;
                } elseif (str_starts_with($peekTrim, 'No.') && $peekTrim !== 'Kelurahan:' && $peekTrim !== 'Kecamatan:' && $peekTrim !== 'Kabupaten/Kota:' && $peekTrim !== 'Provinsi:') {
                    if (!str_contains($peekTrim, 'Kelurahan')) {
                        $cur['alamat'] = $this->normalizeSpaces($cur['alamat'] . ' ' . $peekTrim);
                        $cur['alamat'] = str_replace(' - ', ' ', $cur['alamat']);
                        $consumed++;
                    }
                }
            } else {
                $cur['nib'] = $nibPrefix;
                $consumed = 1;
            }

            if (!empty($cur['alamat'])) {
                $cur['alamat'] = $this->normalizeSpaces(str_replace(' - ', ' ', $cur['alamat']));
            }

            // Cari Kelurahan/Kecamatan/Kab/Prov tanpa regex — exact match
            $j = $i + $consumed;
            while ($j < $n && $lines[$j] !== 'Kelurahan:') $j++;
            if ($j < $n && $lines[$j] === 'Kelurahan:') {
                $cur['kelurahan'] = $lines[$j + 1] ?? null;
                $cur['kelurahan'] = $cur['kelurahan'] !== '' ? $cur['kelurahan'] : null;
                $j += 2;
                while ($j < $n && $lines[$j] !== 'Kecamatan:') $j++;
                if ($j < $n && $lines[$j] === 'Kecamatan:') {
                    $cur['kecamatan'] = $lines[$j + 1] ?? null;
                    $cur['kecamatan'] = $cur['kecamatan'] !== '' ? $cur['kecamatan'] : null;
                    $j += 2;
                    while ($j < $n && $lines[$j] !== 'Kabupaten/Kota:') $j++;
                    if ($j < $n && $lines[$j] === 'Kabupaten/Kota:') {
                        $cur['kab_kota'] = $lines[$j + 1] ?? null;
                        $cur['kab_kota'] = $cur['kab_kota'] !== '' ? $cur['kab_kota'] : null;
                        $j += 2;
                        while ($j < $n && $lines[$j] !== 'Provinsi:') $j++;
                        if ($j < $n && $lines[$j] === 'Provinsi:') {
                            $prov = $lines[$j + 1] ?? '';
                            $prov = trim($prov);
                            $prov = $this->cleanProv($prov);
                            $cur['provinsi'] = $prov !== '' ? $prov : null;
                            $j += 2;
                        }
                    }
                }
            }
            $maxNo = 0;
            foreach ($records as $r) $maxNo = max($maxNo, (int)($r['no'] ?? 0));
            $cur['no'] = $maxNo + 1;
            if (isset($nameMap[$cur['no']])) $cur['nama_pelaku_usaha'] = $nameMap[$cur['no']];
            if (!empty($cur['nib']) && empty($cur['jenis_penanaman_modal'])) $cur['jenis_penanaman_modal'] = 'Penanaman Modal Dalam Negeri (PMDN)';
            $records[] = $cur;
            $i = $j;
            continue;
        }

        // 5. Skala usaha tanpa regex — cari string "Usaha Mikro/Kecil/..." via stripos
        $lowerText = strtolower($text);
        $knownScales = ['Usaha Mikro', 'Usaha Kecil', 'Usaha Menengah', 'Usaha Besar'];
        $foundScales = [];
        foreach ($knownScales as $sk) {
            $lowSk = strtolower($sk);
            $offset = 0;
            while (true) {
                $pos = strpos($lowerText, $lowSk, $offset);
                if ($pos === false) break;
                $foundScales[] = $sk;
                $offset = $pos + strlen($lowSk);
            }
        }
        foreach ($foundScales as $scale) {
            $assigned = false;
            foreach ($records as &$r) {
                if (($r['nama_pelaku_usaha'] ?? '') === 'ANWAR' && empty($r['skala_usaha'])) { $r['skala_usaha'] = $scale; $assigned = true; break; }
            }
            unset($r);
            if ($assigned) continue;
            foreach ($records as &$r) {
                if (!empty($r['nib']) && empty($r['skala_usaha'])) { $r['skala_usaha'] = $scale; $assigned = true; break; }
            }
            unset($r);
        }

        $withNib = [];
        foreach ($records as $r) if (!empty($r['nib'])) $withNib[] = $r;
        if (count($withNib) < 2) return [];
        usort($records, fn($a, $b) => ($a['no'] ?? 0) <=> ($b['no'] ?? 0));
        return $records;
    }

    public function parseJsonArray(array $items, string $filename): array
    {
        $records = [];
        foreach ($items as $item) {
            if (!is_array($item)) continue;
            $alamat = $item['alamat'] ?? null;
            $isAlamatArray = is_array($alamat);
            $records[] = [
                'no' => isset($item['no']) ? (int) $item['no'] : null,
                'nama_pelaku_usaha' => $item['nama_pelaku_usaha'] ?? null,
                'nib' => isset($item['nib']) ? ($item['nib'] !== null ? (string) $item['nib'] : null) : null,
                'jenis_penanaman_modal' => $item['penanaman_modal'] ?? $item['jenis_penanaman_modal'] ?? null,
                'skala_usaha' => $item['skala_usaha'] ?? null,
                'alamat' => $isAlamatArray ? ($alamat['jalan'] ?? null) : $alamat,
                'kelurahan' => $isAlamatArray ? ($alamat['kelurahan'] ?? $item['kelurahan'] ?? null) : ($item['kelurahan'] ?? null),
                'kecamatan' => $isAlamatArray ? ($alamat['kecamatan'] ?? $item['kecamatan'] ?? null) : ($item['kecamatan'] ?? null),
                'kab_kota' => $isAlamatArray ? ($alamat['kabupaten_kota'] ?? $alamat['kab_kota'] ?? $item['kab_kota'] ?? null) : ($item['kab_kota'] ?? $item['kabupaten_kota'] ?? null),
                'provinsi' => $isAlamatArray ? ($alamat['provinsi'] ?? $item['provinsi'] ?? null) : ($item['provinsi'] ?? null),
            ];
        }
        $filtered = [];
        foreach ($records as $r) if (!empty($r['nama_pelaku_usaha']) || !empty($r['nib']) || !empty($r['kelurahan'])) $filtered[] = $r;
        $records = $filtered;
        if (empty($records) && !empty($items)) {
            $records = array_map(function ($item) {
                $alamat = $item['alamat'] ?? null;
                $isArray = is_array($alamat);
                return [
                    'no' => $item['no'] ?? null,
                    'nama_pelaku_usaha' => $item['nama_pelaku_usaha'] ?? null,
                    'nib' => isset($item['nib']) ? (string) $item['nib'] : null,
                    'jenis_penanaman_modal' => $item['penanaman_modal'] ?? null,
                    'skala_usaha' => $item['skala_usaha'] ?? null,
                    'alamat' => $isArray ? ($alamat['jalan'] ?? null) : $alamat,
                    'kelurahan' => $isArray ? ($alamat['kelurahan'] ?? null) : ($item['kelurahan'] ?? null),
                    'kecamatan' => $isArray ? ($alamat['kecamatan'] ?? null) : ($item['kecamatan'] ?? null),
                    'kab_kota' => $isArray ? ($alamat['kabupaten_kota'] ?? null) : ($item['kab_kota'] ?? null),
                    'provinsi' => $isArray ? ($alamat['provinsi'] ?? null) : ($item['provinsi'] ?? null),
                ];
            }, $items);
        }
        return $this->finalizeRecords($records, $filename);
    }

    private function tryParseJsonArray(array $items, string $filename): array
    {
        if (empty($items)) return [];
        $first = $items[0] ?? null;
        if (!is_array($first)) return [];
        $hasSanksiKey = isset($first['nib']) || isset($first['nama_pelaku_usaha']) || isset($first['alamat']);
        if (!$hasSanksiKey) return [];
        return $this->parseJsonArray($items, $filename);
    }

    private function extractText(string $filePath): string
    {
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'bmp', 'tiff', 'webp'])) return $this->extractFromImage($filePath);
        if ($ext === 'pdf') {
            // Coba pdftotext -layout dulu (lebih akurat, tanpa OCR)
            $textLayer = $this->tryPdftotext($filePath);
            if (strlen(trim($textLayer)) > 500) {
                Log::info('pdftotext dipakai (tanpa OCR)', ['chars' => strlen($textLayer)]);
                $this->confidence = 95.0;
                return $textLayer;
            }
            return $this->extractFromPdf($filePath);
        }
        return '';
    }

    private function tryPdftotext(string $pdfPath): string
    {
        $isWin = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        $pdfArg = $isWin ? str_replace('/', '\\', $pdfPath) : $pdfPath;
        // pdftotext di Windows kadang bernama pdftotext.exe, di Linux pdftotext
        $bin = $isWin ? 'pdftotext' : 'pdftotext';
        // Cek apakah bin ada di PATH atau di folder poppler
        $cmd = sprintf('"%s" -layout "%s" - 2>&1', $bin, $pdfArg);
        // Jika poppler binary adalah pdftoppm, coba derive pdftotext dari foldernya
        $altBin = str_replace('pdftoppm', 'pdftotext', $this->popplerBinary);
        if (!is_file($bin) && is_file(trim($altBin, '"'))) $cmd = sprintf('"%s" -layout "%s" - 2>&1', trim($altBin, '"'), $pdfArg);
        exec($cmd, $out, $rc);
        if ($rc !== 0 || empty($out)) return '';
        $joined = implode("\n", $out);
        // Jika output mengandung banyak "�" atau kosong, anggap gagal
        if (strlen(trim($joined)) < 100) return '';
        return $joined;
    }

    private function extractFromPdf(string $pdfPath): string
    {
        $text = $this->pdfViaPoppler($pdfPath);
        if ($text !== '') return $text;
        Log::info('pdftoppm sanksi gagal, coba tesseract langsung pada PDF');
        return $this->tesseractOcr($pdfPath);
    }

    private function pdfViaPoppler(string $pdfPath): string
    {
        $tempDir = sys_get_temp_dir();
        $prefix = 'ocr_sanksi_' . uniqid();
        $isWin = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        $sep = $isWin ? '\\' : '/';
        $outputBase = rtrim($tempDir, '/\\') . $sep . $prefix;
        $pdfArg = $isWin ? str_replace('/', '\\', $pdfPath) : $pdfPath;
        // Naikkan DPI ke 400 untuk akurasi NIB 13 digit di VPS (sebelumnya 300)
        $cmd = sprintf('"%s" -png -r 400 "%s" "%s"', $this->popplerBinary, $pdfArg, $outputBase);
        exec($cmd . ' 2>&1', $output, $rc);
        if ($rc !== 0) { Log::warning('pdftoppm sanksi failed', ['cmd' => $cmd, 'rc' => $rc, 'output' => $output]); return ''; }
        $images = glob(rtrim($tempDir, '/\\') . $sep . $prefix . '*.png');
        if (empty($images)) { Log::warning('pdftoppm sanksi tidak menghasilkan gambar', ['cmd' => $cmd]); return ''; }
        sort($images);
        $allText = [];
        foreach ($images as $image) { $allText[] = $this->tesseractOcr($image); @unlink($image); }
        $this->confidence = 85.0;
        return implode("\n\n", $allText);
    }

    private function extractFromImage(string $imagePath): string
    {
        $text = $this->tesseractOcr($imagePath);
        if ($text !== '') $this->confidence = 85.0;
        return $text;
    }

    private function tesseractOcr(string $filePath): string
    {
        $isWin = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        $fileArg = $isWin ? str_replace('/', '\\', $filePath) : $filePath;
        // --oem 1 (LSTM) + --psm 6 (block) lebih stabil di VPS vs default
        $cmd = sprintf('"%s" "%s" stdout -l %s --oem 1 --psm 6', $this->tesseractBinary, $fileArg, $this->language);
        exec($cmd . ' 2>&1', $output, $rc);
        if ($rc !== 0) { Log::error('tesseract sanksi failed', ['cmd' => $cmd, 'rc' => $rc, 'output' => $output]); return ''; }
        return implode("\n", $output);
    }

    private function normalizeText(string $text): string
    {
        $tmp = str_replace("\r\n", "\n", $text);
        $tmp = str_replace("\r", "\n", $tmp);
        $parts = explode("\n", $tmp);
        $lines = [];
        foreach ($parts as $p) {
            $t = trim($p);
            if ($t === '') continue;
            $t2 = ltrim($t, "'\" \t");
            if ($t2 === '') continue;
            $lines[] = $t2;
        }
        $result = [];
        $pendingKey = '';
        foreach ($lines as $line) {
            $t = $line;
            if ($this->isHeaderFragment($t)) { if (strtolower(rtrim($pendingKey)) === 'nama pelaku') $pendingKey = ''; continue; }
            if ($this->isNoiseLine($t)) continue;
            $hasColon = str_contains($t, ':');
            if ($hasColon) {
                if ($pendingKey !== '') {
                    $trimmed = rtrim($pendingKey);
                    $next = rtrim($t, ':');
                    $combinedSpace = $trimmed . ' ' . $next;
                    $combinedNoSpace = $trimmed . $next;
                    if ($this->isKnownAnchor($combinedSpace)) $key = $combinedSpace;
                    elseif ($this->isKnownAnchor($combinedNoSpace)) $key = $combinedNoSpace;
                    else { $result[] = $pendingKey . ':'; $key = $this->cleanAnchorKey($t); }
                    $pendingKey = '';
                } else $key = $this->cleanAnchorKey($t);
                $afterColon = '';
                $pos = strpos($t, ':');
                if ($pos !== false) $afterColon = trim(substr($t, $pos + 1));
                if ($afterColon !== '' && $afterColon !== '-') $result[] = $key . ': ' . $afterColon;
                elseif ($afterColon === '-') $result[] = $key . ': -';
                else $pendingKey = $key;
            } else {
                if ($pendingKey !== '') {
                    if ($this->isAnchorContinuation($pendingKey, $t)) $pendingKey .= ' ' . $t;
                    else { $result[] = $pendingKey . ': ' . $t; $pendingKey = ''; }
                } else {
                    if ($this->isKnownAnchor($t) || $this->isPartialAnchor($t)) $pendingKey = $t;
                    elseif (!empty($result)) {
                        // angka halaman 1-2 digit jangan ditempel
                        $isPageNum = true;
                        $tl = trim($t);
                        $tlen = strlen($tl);
                        if ($tlen < 1 || $tlen > 2) $isPageNum = false;
                        else for ($k=0;$k<$tlen;$k++) if (!ctype_digit($tl[$k])) { $isPageNum = false; break; }
                        if ($isPageNum) continue;
                        $lastLine = $result[count($result) - 1];
                        $hasValue = str_contains($lastLine, ': ') && substr(rtrim($lastLine), -1) !== ':';
                        $hasAlpha = false;
                        for ($k=0;$k<strlen($t);$k++) if (ctype_alpha($t[$k])) { $hasAlpha = true; break; }
                        if (!$hasValue || $hasAlpha) $result[count($result)-1] = $this->appendValue($lastLine, $t);
                    }
                }
            }
        }
        if ($pendingKey !== '') $result[] = $pendingKey . ':';
        return implode("\n", $result);
    }

    private function appendValue(string $lastLine, string $next): string
    {
        $trimmed = rtrim($lastLine);
        $endsDash = strlen($trimmed) > 0 && $trimmed[strlen($trimmed)-1] === '-';
        $endsSpaceDash = str_ends_with($trimmed, ' -') || str_ends_with($trimmed, "\t-");
        if ($endsDash && !$endsSpaceDash) return $trimmed . ltrim($next);
        return $lastLine . ' ' . $next;
    }

    private function cleanAnchorKey(string $lineWithColon): string
    {
        $pos = strpos($lineWithColon, ':');
        $key = $pos === false ? $lineWithColon : substr($lineWithColon, 0, $pos);
        return trim($key);
    }

    private function isKnownAnchor(string $text): bool
    {
        $low = strtolower($text);
        foreach (self::FIELD_ANCHORS as $anchors) foreach ($anchors as $a) if ($low === strtolower($a)) return true;
        return false;
    }

    private function isAnchorContinuation(string $cur, string $line): bool
    {
        $trim = strtolower(rtrim($cur));
        $lowLine = strtolower($line);
        $sp = $trim . ' ' . $lowLine;
        $nsp = $trim . $lowLine;
        foreach (self::FIELD_ANCHORS as $anchors) foreach ($anchors as $a) {
            $al = strtolower($a);
            if ($al === $sp || str_starts_with($al, $sp)) return true;
            if ($al === $nsp || str_starts_with($al, $nsp)) return true;
        }
        return false;
    }

    private function isPartialAnchor(string $text): bool
    {
        $low = strtolower(trim($text));
        if ($low === '') return false;
        foreach (self::FIELD_ANCHORS as $anchors) foreach ($anchors as $a) {
            $al = strtolower($a);
            if (str_starts_with($al, $low) && $low !== $al) return true;
        }
        return false;
    }

    private const HEADER_FRAGMENTS = ['usaha/nib/jenis', 'pm/skala usaha'];
    private function isHeaderFragment(string $text): bool { return in_array(strtolower(trim($text)), self::HEADER_FRAGMENTS, true); }
    private const NOISE_EXACT = ['oss','pb','no','ks','pen:','fo v','vv 1','vv 2','data sanksi','perizinan berusaha','lokasi usaha','data usaha','daftar list sanksi','item per halaman','tabel','judul'];
    private const NOISE_CONTAINS = ['beranda','pelaporan','pengaduan','pelacakan','pemrosesan.oss.go.id','oss.go.id','investasi dan hilirisasi','cari berdasarkan','daftar list','ikuti lembaga oss','lembaga oss - kementerian','media sosial','non pencabutan','oto1ko','lampiran','lihat detail','nurmarita'];
    private function isNoiseLine(string $text): bool
    {
        $trimmed = trim($text);
        if ($trimmed === '') return true;
        if ($trimmed === 'Sumber data:') return true;
        $low = strtolower($trimmed);
        if (in_array($low, self::NOISE_EXACT, true)) return true;
        foreach (self::NOISE_CONTAINS as $p) if (str_contains($low, $p)) return true;
        // tanpa regex untuk yang sederhana
        $isPageOf = false;
        $lowTrim = strtolower($trimmed);
        if (str_contains($lowTrim, ' of ')) {
            $parts = explode(' ', $lowTrim);
            if (count($parts) === 3 && ctype_digit($parts[0]) && $parts[1] === 'of' && ctype_digit($parts[2])) $isPageOf = true;
        }
        if ($isPageOf) return true;
        if (str_starts_with($trimmed, 'http://') || str_starts_with($trimmed, 'https://')) return true;
        if (str_contains($trimmed, '©') || str_contains($trimmed, '|')) return true;
        // tanggal footer 15/09/2026, 15:56 tanpa regex: cek '/' dan ':'
        if (str_contains($trimmed, '/') && str_contains($trimmed, ':') && strlen($trimmed) < 20) {
            $slashCount = substr_count($trimmed, '/');
            if ($slashCount === 2) return true;
        }
        $low2 = strtolower($trimmed);
        if ((str_contains($low2, 'sanksi v') || str_contains($low2, 'pencabutan v') || str_contains($low2, 'pelaporan v')) && str_contains($trimmed, ' v')) return true;
        // vv 1 / vv 2
        $tNoSpace = str_replace(' ', '', strtolower($trimmed));
        if ($tNoSpace === 'vv1' || $tNoSpace === 'vv2' || $tNoSpace === 'vv') return true;
        return false;
    }

    private const SECTIONS = [
        'usaha' => ['delimiter' => 'Nama Pelaku Usaha', 'fields' => ['No' => 'no', 'No.' => 'no', 'Nomor' => 'no', 'Nama Pelaku Usaha' => 'nama_pelaku_usaha', 'Nomor Induk Berusaha (NIB)' => 'nib', 'Nomor Induk' => 'nib', 'Berusaha (NIB)' => 'nib', 'NIB' => 'nib', 'Jenis Penanaman Modal' => 'jenis_penanaman_modal', 'Penanaman Modal' => 'jenis_penanaman_modal', 'Skala Usaha' => 'skala_usaha']],
        'lokasi' => ['delimiter' => 'Alamat', 'fields' => ['Alamat' => 'alamat', 'Jalan' => 'alamat', 'Kelurahan' => 'kelurahan', 'Gampong' => 'kelurahan', 'Desa' => 'kelurahan', 'Kecamatan' => 'kecamatan', 'Kab/Kota' => 'kab_kota', 'Kab. Kota' => 'kab_kota', 'Kabupaten/Kota' => 'kab_kota', 'Provinsi' => 'provinsi']],
    ];
    private function parseAllPairs(string $text): array
    {
        $tmp = str_replace("\r\n", "\n", $text);
        $tmp = str_replace("\r", "\n", $tmp);
        $parts = explode("\n", $tmp);
        $pairs = [];
        foreach ($parts as $line) {
            $t = trim($line);
            if ($t === '') continue;
            $pos = strpos($t, ':');
            if ($pos === false) continue;
            $key = trim(substr($t, 0, $pos));
            $value = trim(substr($t, $pos + 1));
            if ($key !== '') $pairs[] = ['key' => $key, 'value' => $value];
        }
        return $pairs;
    }
    private function buildRecordsFromPairs(array $pairs): array
    {
        $keyMap = $this->buildKeyMap();
        $sectionRecords = [];
        foreach (self::SECTIONS as $sn => $sec) $sectionRecords[$sn] = ['records' => [], 'current' => null];
        foreach ($pairs as $pair) {
            $key = $pair['key']; $value = $pair['value'];
            if (!isset($keyMap[$key])) continue;
            $sectionName = $keyMap[$key]['section']; $fieldName = $keyMap[$key]['field'];
            $section = self::SECTIONS[$sectionName];
            $incoming = $this->cleanValue($value);
            if ($fieldName === 'no' && $incoming !== null) {
                $isNum = true;
                $iv = trim($incoming);
                if ($iv === '') $isNum = false;
                else for ($k=0;$k<strlen($iv);$k++) if (!ctype_digit($iv[$k]) && $iv[$k] !== '-') { $isNum = false; break; }
                $incoming = $isNum && is_numeric($iv) ? (int)$iv : null;
            }
            if ($fieldName === 'nib' && $incoming !== null) $incoming = (string)$incoming;
            if ($key === $section['delimiter']) {
                if ($sectionRecords[$sectionName]['current'] !== null) $sectionRecords[$sectionName]['records'][] = $sectionRecords[$sectionName]['current'];
                $sectionRecords[$sectionName]['current'] = $this->emptyRecord();
            } elseif ($sectionRecords[$sectionName]['current'] === null) $sectionRecords[$sectionName]['current'] = $this->emptyRecord();
            elseif ($incoming !== null && $sectionRecords[$sectionName]['current'][$fieldName] !== null) {
                $sectionRecords[$sectionName]['records'][] = $sectionRecords[$sectionName]['current'];
                $sectionRecords[$sectionName]['current'] = $this->emptyRecord();
            }
            if ($sectionRecords[$sectionName]['current'][$fieldName] === null) $sectionRecords[$sectionName]['current'][$fieldName] = $incoming;
        }
        foreach ($sectionRecords as $sn => $data) if ($data['current'] !== null) $sectionRecords[$sn]['records'][] = $data['current'];
        return $this->matchAcrossSections($sectionRecords);
    }
    private function countSectionPairs(array $pairs): array
    {
        $keyMap = $this->buildKeyMap();
        $counts = [];
        foreach ($pairs as $pair) {
            if (!isset($keyMap[$pair['key']])) continue;
            $section = $keyMap[$pair['key']]['section'];
            $counts[$section] = ($counts[$section] ?? 0) + 1;
        }
        return $counts;
    }
    private function buildKeyMap(): array
    {
        $keyMap = [];
        foreach (self::SECTIONS as $sn => $sec) foreach ($sec['fields'] as $key => $field) $keyMap[$key] = ['section' => $sn, 'field' => $field];
        return $keyMap;
    }
    private function matchAcrossSections(array $sectionRecords): array
    {
        $lists = []; $max = 0;
        foreach ($sectionRecords as $sn => $data) { $lists[$sn] = array_values($data['records']); $max = max($max, count($lists[$sn])); }
        if ($max === 0) return [];
        $records = [];
        for ($i = 0; $i < $max; $i++) {
            $record = $this->emptyRecord();
            foreach (['usaha','lokasi'] as $sn) if (isset($lists[$sn][$i])) $this->fillNulls($record, $lists[$sn][$i]);
            if ($record['nama_pelaku_usaha'] === null && $record['nib'] !== null) foreach ($lists['usaha'] as $u) if (($u['nib'] ?? null) === $record['nib'] && ($u['nama_pelaku_usaha'] ?? null) !== null) { $record['nama_pelaku_usaha'] = $u['nama_pelaku_usaha']; break; }
            $records[] = $record;
        }
        return $records;
    }
    private function fillNulls(array &$target, array $source): void { foreach ($source as $k => $v) if ($v !== null && ($target[$k] ?? null) === null) $target[$k] = $v; }
    private function cleanValue(?string $value): ?string
    {
        if ($value === null) return null;
        $value = trim($value);
        if ($value === '' || $value === '-') return null;
        $value = $this->normalizeSpaces($value);
        $trim = rtrim($value);
        if ($trim !== '' && $trim[strlen($trim)-1] === '-' && !str_ends_with($trim, ' -') && !str_ends_with($trim, "\t-")) $value = $trim;
        else $value = $trim;
        $value = trim($value);
        // hapus " -" di akhir tanpa regex
        while (str_ends_with($value, ' -')) $value = trim(substr($value, 0, -2));
        return $value !== '' ? $value : null;
    }
    private function finalizeRecords(array $records, string $filename): array
    {
        $results = [];
        foreach ($records as $i => $record) {
            $record['sumber_file'] = $filename;
            $record['sumber_halaman'] = (int) ceil(($i + 1) / 2);
            $record['sumber_metode'] = $this->confidence >= 95 ? 'PDF-TEXT' : 'OCR';
            $record['skor_ocr'] = $this->confidence;
            if (isset($record['nib']) && $record['nib'] !== null) $record['nib'] = (string)$record['nib'];
            $results[] = $record;
        }
        return $results;
    }
    private function emptyRecord(): array { return ['no' => null, 'nama_pelaku_usaha' => null, 'nib' => null, 'jenis_penanaman_modal' => null, 'skala_usaha' => null, 'alamat' => null, 'kelurahan' => null, 'kecamatan' => null, 'kab_kota' => null, 'provinsi' => null]; }
}
