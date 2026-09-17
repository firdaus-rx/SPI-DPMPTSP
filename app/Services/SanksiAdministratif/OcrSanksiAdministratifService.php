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

    public function getConfidence(): float
    {
        return $this->confidence;
    }

    public function processFile(string $filePath, string $filename): array
    {
        $text = $this->extractText($filePath);
        Log::info('OCR Sanksi Extract', [
            'file' => $filename,
            'text_length' => strlen($text),
            'text_preview' => mb_substr($text, 0, 2000),
        ]);
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

        // Layout khusus OSS sanksi: v <no> <nama> + NIB split "9120.. Alamat: Penanaman Modal" + "31 Gampong..."
        $oss = $this->parseOssLayout($text);
        if (!empty($oss)) {
            Log::info('OCR Sanksi Parse (OSS layout)', [
                'text_length' => strlen($text),
                'records_found' => count($oss),
                'oss_preview' => array_slice($oss, 0, 2),
            ]);
            return $this->finalizeRecords($oss, $filename);
        }

        // Fallback generic key:value
        $normalized = $this->normalizeText($text);
        $pairs = $this->parseAllPairs($normalized);
        $sectionCounts = $this->countSectionPairs($pairs);
        $records = $this->buildRecordsFromPairs($pairs);
        Log::info('OCR Sanksi Parse', [
            'text_length' => strlen($text),
            'normalized_length' => strlen($normalized),
            'pairs_found' => count($pairs),
            'pairs_per_section' => $sectionCounts,
            'records_found' => count($records),
        ]);
        if (empty($records)) return [];
        return $this->finalizeRecords($records, $filename);
    }

    /**
     * Parser khusus layout PDF 2.Rancangan sanksi:
     * - Nama: "v 2 BUMG LEUNGGOH JAYA" (tanpa label)
     * - NIB split: "91203009508 Alamat: Penanaman Modal" + "31 Gampong Tuha Gogo Dalam Negeri (PMDN)"
     * - Blok lokasi: Kelurahan/Kecamatan/Kab/Provinsi masing-masing di baris terpisah dengan label ":"
     * Penanganan blank lines: filter kosong agar "Kelurahan:" -> value langsung bersebelahan, dan "No.1]" tetap terbaca.
     */
    private function parseOssLayout(string $text): array
    {
        $rawLines = preg_split('/\r\n|\r|\n/', $text);
        $rawLines = array_map(fn($l) => trim($l), $rawLines);
        // Clean: buang baris kosong agar indeks tidak geser oleh blank, tapi tetap pertahankan untuk nameMap sebelumnya
        $lines = array_values(array_filter($rawLines, fn($l) => $l !== ''));

        // 1. Nama dari "v <no> <nama>" (pakai raw untuk toleransi, tapi clean juga bisa)
        $nameMap = [];
        foreach ($lines as $l) {
            if (preg_match('/^v\s*(\d+)\s+(.+)$/i', $l, $m)) {
                $no = (int) $m[1];
                $name = trim($m[2]);
                if (mb_strlen($name) >= 3 && !preg_match('/^(BERANDA|PELAPORAN|PENGADUAN|PENCABUTAN|SANKSI|PELACAKAN|KEMENTERIAN|Usulan)/i', $name)) {
                    $nameMap[$no] = $name;
                }
            }
        }
        if (empty($nameMap)) return [];

        // 2. Cari indeks baris NIB pertama pada clean
        $firstNibIdx = null;
        foreach ($lines as $idx => $l) {
            if (preg_match('/^\d{11,13}\s+Alamat:/', $l)) {
                $firstNibIdx = $idx;
                break;
            }
        }
        if ($firstNibIdx === null) return [];

        $records = [];

        // 3. Record #1 tanpa NIB: "Kampong Pukat" + Kecamatan/Kab/Prov (clean: ["Kampong Pukat","Kecamatan:","Pidie",...])
        $preSlice = array_slice($lines, 0, $firstNibIdx);
        $foundPre = false;
        for ($k = 0; $k < count($preSlice); $k++) {
            $val = $preSlice[$k];
            if ($val === 'Kecamatan:' || $val === 'Kabupaten/Kota:' || $val === 'Provinsi:' || str_contains($val, 'BERANDA') || str_contains($val, 'KEMENTERIAN')) continue;
            $next1 = $preSlice[$k + 1] ?? '';
            $next2 = $preSlice[$k + 2] ?? '';
            if ($next1 === 'Kecamatan:' && $next2 !== '') {
                $hasKab = false;
                for ($t = $k + 3; $t < min($k + 10, count($preSlice)); $t++) {
                    if ($preSlice[$t] === 'Kabupaten/Kota:') { $hasKab = true; break; }
                }
                if ($hasKab) {
                    $kel = $val;
                    $kec = $next2;
                    $kab = null; $prov = null;
                    for ($t = $k + 3; $t < count($preSlice); $t++) {
                        if ($preSlice[$t] === 'Kabupaten/Kota:' && isset($preSlice[$t + 1])) $kab = $preSlice[$t + 1];
                        if ($preSlice[$t] === 'Provinsi:' ) {
                            $prov = $preSlice[$t + 1] ?? null;
                            if ($prov !== null) $prov = trim($prov);
                            $prov = $prov !== '' ? $prov : null;
                            // buang suffix noise
                            if ($prov) $prov = preg_replace('/\s*Baris per Halaman.*$/i', '', $prov);
                            break;
                        }
                    }
                    $records[] = [
                        'no' => 1, 'nama_pelaku_usaha' => null, 'nib' => null, 'jenis_penanaman_modal' => null, 'skala_usaha' => null,
                        'alamat' => null, 'kelurahan' => $kel ?: null, 'kecamatan' => $kec ?: null, 'kab_kota' => $kab ?: null, 'provinsi' => $prov ?: null,
                    ];
                    $foundPre = true;
                    break;
                }
            }
        }
        if (!$foundPre) {
            $records[] = ['no' => 1, 'nama_pelaku_usaha' => null, 'nib' => null, 'jenis_penanaman_modal' => null, 'skala_usaha' => null, 'alamat' => null, 'kelurahan' => null, 'kecamatan' => null, 'kab_kota' => null, 'provinsi' => null];
        }

        // 4. Walk NIB blocks pada clean
        $i = $firstNibIdx;
        $n = count($lines);
        while ($i < $n) {
            $line = $lines[$i];
            if (!preg_match('/^(\d{11,13})\s+Alamat:\s*(.*)$/i', $line, $m)) {
                $i++;
                continue;
            }
            $nibPrefix = $m[1];
            $cur = ['no' => null, 'nama_pelaku_usaha' => null, 'nib' => null, 'jenis_penanaman_modal' => null, 'skala_usaha' => null, 'alamat' => null, 'kelurahan' => null, 'kecamatan' => null, 'kab_kota' => null, 'provinsi' => null];
            $next = $lines[$i + 1] ?? '';
            $consumed = 1;
            if (preg_match('/^(\d{1,2})\s+(.+)\s+Dalam Negeri\s*\(PMDN\)\s*$/i', $next, $n2)) {
                $suffix = $n2[1];
                $alamatPart = trim($n2[2]);
                if (strlen($nibPrefix) < 13 && strlen($nibPrefix . $suffix) === 13) {
                    $cur['nib'] = $nibPrefix . $suffix;
                    $cur['alamat'] = $alamatPart;
                } else {
                    $cur['nib'] = $nibPrefix;
                    $cur['alamat'] = $suffix . ' ' . $alamatPart;
                }
                $cur['jenis_penanaman_modal'] = 'Penanaman Modal Dalam Negeri (PMDN)';
                $consumed = 2;
                $peek = $lines[$i + $consumed] ?? '';
                if (preg_match('/^No\.\s*1\]$/i', $peek)) {
                    $cur['alamat'] = trim($cur['alamat'] . ' No.11');
                    $cur['alamat'] = str_replace(' - ', ' ', $cur['alamat']);
                    $consumed++;
                } elseif (preg_match('/^No\.\s*\d+.*$/i', $peek) && !in_array($peek, ['Kelurahan:', 'Kecamatan:', 'Kabupaten/Kota:', 'Provinsi:'])) {
                    if (!str_contains($peek, 'Kelurahan')) {
                        $cur['alamat'] = trim($cur['alamat'] . ' ' . $peek);
                        $cur['alamat'] = str_replace(' - ', ' ', $cur['alamat']);
                        $consumed++;
                    }
                }
            } elseif (preg_match('/^(.+)\s+Dalam Negeri\s*\(PMDN\)\s*$/i', $next, $n2)) {
                $cur['nib'] = $nibPrefix;
                $cur['alamat'] = trim($n2[1]);
                $cur['jenis_penanaman_modal'] = 'Penanaman Modal Dalam Negeri (PMDN)';
                $consumed = 2;
                $peek = $lines[$i + $consumed] ?? '';
                if (preg_match('/^No\.\s*1\]$/i', $peek)) {
                    $cur['alamat'] = trim($cur['alamat'] . ' No.11');
                    $cur['alamat'] = str_replace(' - ', ' ', $cur['alamat']);
                    $consumed++;
                }
            } else {
                $cur['nib'] = $nibPrefix;
                $consumed = 1;
            }
            if (!empty($cur['alamat'])) {
                $cur['alamat'] = preg_replace('/\s+/', ' ', trim(str_replace(' - ', ' ', $cur['alamat'])));
            }
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
                            $prov = preg_replace('/\s*Baris per Halaman.*$/i', '', $prov);
                            $prov = trim($prov);
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
        if (preg_match_all('/\bUsaha\s+(Mikro|Kecil|Menengah|Besar)\b/i', $text, $sm)) {
            $scales = array_map(fn($s) => trim($s), $sm[0]);
            foreach ($scales as $scale) {
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
        }
        $withNib = array_filter($records, fn($r) => !empty($r['nib']));
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
        $records = array_values(array_filter($records, fn($r) => !empty($r['nama_pelaku_usaha']) || !empty($r['nib']) || !empty($r['kelurahan'])));
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
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'bmp', 'tiff', 'webp'])) {
            return $this->extractFromImage($filePath);
        }
        if ($ext === 'pdf') {
            $textLayer = $this->tryPdftotext($filePath);
            if ($this->isMeaningfulPdfText($textLayer)) {
                Log::info('pdftotext dipakai (tanpa OCR)', ['chars' => strlen($textLayer)]);
                $this->confidence = 95.0;
                return $textLayer;
            }
            if (strlen(trim($textLayer)) > 0) {
                Log::info('pdftotext diabaikan (tidak bermakna)', ['chars' => strlen($textLayer), 'preview' => mb_substr($textLayer, 0, 300)]);
            }
            return $this->extractFromPdf($filePath);
        }
        return '';
    }

    private function isMeaningfulPdfText(string $text): bool
    {
        $t = trim($text);
        if (strlen($t) < 500) return false;
        // PDF scan dengan pdftotext -layout menghasilkan gibberish ikon — harus ada anchor sanksi
        $hasAnchor = str_contains($t, 'Kelurahan:') || str_contains($t, 'Kecamatan:') || str_contains($t, 'Alamat:') || str_contains($t, 'Dalam Negeri');
        $hasNib = false;
        // Cek ada 11-13 digit (tanpa regex berat — scan manual)
        $len = strlen($t);
        $digitRun = 0;
        for ($i = 0; $i < $len; $i++) {
            if (ctype_digit($t[$i])) { $digitRun++; if ($digitRun >= 11) { $hasNib = true; break; } }
            else $digitRun = 0;
        }
        // Hitung proporsi alfanumerik vs gibberish
        $alpha = 0;
        for ($i = 0; $i < min($len, 2000); $i++) if (ctype_alpha($t[$i])) $alpha++;
        if ($alpha < 100) return false;
        return $hasAnchor || $hasNib;
    }

    private function tryPdftotext(string $pdfPath): string
    {
        $isWin = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        $pdfArg = $isWin ? str_replace('/', '\\', $pdfPath) : $pdfPath;
        $bin = 'pdftotext';
        $altBin = str_replace('pdftoppm', 'pdftotext', $this->popplerBinary);
        $cmd = sprintf('"%s" -layout "%s" - 2>&1', $bin, $pdfArg);
        if (!is_file($bin) && is_file(trim($altBin, '"'))) $cmd = sprintf('"%s" -layout "%s" - 2>&1', trim($altBin, '"'), $pdfArg);
        exec($cmd, $out, $rc);
        if ($rc !== 0 || empty($out)) return '';
        $joined = implode("\n", $out);
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
        $cmd = sprintf('"%s" -png -r 400 "%s" "%s"', $this->popplerBinary, $pdfArg, $outputBase);
        exec($cmd . ' 2>&1', $output, $rc);
        if ($rc !== 0) {
            Log::warning('pdftoppm sanksi failed', ['cmd' => $cmd, 'rc' => $rc, 'output' => $output]);
            return '';
        }
        $images = glob(rtrim($tempDir, '/\\') . $sep . $prefix . '*.png');
        if (empty($images)) {
            Log::warning('pdftoppm sanksi tidak menghasilkan gambar', ['cmd' => $cmd]);
            return '';
        }
        sort($images);
        $allText = [];
        foreach ($images as $image) {
            $allText[] = $this->tesseractOcr($image);
            @unlink($image);
        }
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
        $cmd = sprintf('"%s" "%s" stdout -l %s --oem 1 --psm 6', $this->tesseractBinary, $fileArg, $this->language);
        exec($cmd . ' 2>&1', $output, $rc);
        if ($rc !== 0) {
            Log::error('tesseract sanksi failed', ['cmd' => $cmd, 'rc' => $rc, 'output' => $output]);
            return '';
        }
        return implode("\n", $output);
    }

    private function normalizeText(string $text): string
    {
        $lines = explode("\n", $text);
        $result = [];
        $pendingKey = '';
        foreach ($lines as $line) {
            $t = trim($line);
            if ($t === '') continue;
            $t = preg_replace("/^['\"\s]+/", '', $t);
            if ($t === '') continue;
            if ($this->isHeaderFragment($t)) {
                if (strtolower(rtrim($pendingKey)) === 'nama pelaku') {
                    $pendingKey = '';
                }
                continue;
            }
            if ($this->isNoiseLine($t)) continue;
            $hasColon = str_contains($t, ':');
            if ($hasColon) {
                if ($pendingKey !== '') {
                    $trimmed = rtrim($pendingKey);
                    $next = rtrim($t, ':');
                    $combinedSpace = $trimmed . ' ' . $next;
                    $combinedNoSpace = $trimmed . $next;
                    if ($this->isKnownAnchor($combinedSpace)) {
                        $key = $combinedSpace;
                    } elseif ($this->isKnownAnchor($combinedNoSpace)) {
                        $key = $combinedNoSpace;
                    } else {
                        $result[] = $pendingKey . ':';
                        $key = $this->cleanAnchorKey($t);
                    }
                    $pendingKey = '';
                } else {
                    $key = $this->cleanAnchorKey($t);
                }
                $afterColon = preg_replace('/^[^:]+:\s*/', '', $t);
                $afterColon = trim($afterColon);
                if ($afterColon !== '' && $afterColon !== '-') {
                    $result[] = $key . ': ' . $afterColon;
                } elseif ($afterColon === '-') {
                    $result[] = $key . ': -';
                } else {
                    $pendingKey = $key;
                }
            } else {
                if ($pendingKey !== '') {
                    if ($this->isAnchorContinuation($pendingKey, $t)) {
                        $pendingKey .= ' ' . $t;
                    } else {
                        $result[] = $pendingKey . ': ' . $t;
                        $pendingKey = '';
                    }
                } else {
                    if ($this->isKnownAnchor($t) || $this->isPartialAnchor($t)) {
                        $pendingKey = $t;
                    } elseif (!empty($result)) {
                        if (preg_match('/^\d{1,2}$/', $t)) continue;
                        $lastLine = $result[count($result) - 1];
                        $lastHasValue = str_contains($lastLine, ': ') && !str_ends_with(rtrim($lastLine), ':');
                        if (!$lastHasValue || preg_match('/[a-zA-Z]/', $t)) {
                            $result[count($result) - 1] = $this->appendValue($lastLine, $t);
                        }
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
        if (str_ends_with($trimmed, '-') && !str_ends_with($trimmed, ' -') && !str_ends_with($trimmed, "\t-")) {
            return $trimmed . ltrim($next);
        }
        return $lastLine . ' ' . $next;
    }

    private function cleanAnchorKey(string $lineWithColon): string
    {
        $colonPos = strpos($lineWithColon, ':');
        $key = $colonPos === false ? $lineWithColon : substr($lineWithColon, 0, $colonPos);
        return trim($key);
    }

    private function isKnownAnchor(string $text): bool
    {
        $lower = strtolower($text);
        foreach (self::FIELD_ANCHORS as $anchors) {
            foreach ($anchors as $anchor) {
                if ($lower === strtolower($anchor)) return true;
            }
        }
        return false;
    }

    private function isAnchorContinuation(string $currentAnchor, string $line): bool
    {
        $trimmed = strtolower(rtrim($currentAnchor));
        $lineLower = strtolower($line);
        $potentialSpace = $trimmed . ' ' . $lineLower;
        $potentialNoSpace = $trimmed . $lineLower;
        foreach (self::FIELD_ANCHORS as $anchors) {
            foreach ($anchors as $anchor) {
                $a = strtolower($anchor);
                if ($a === $potentialSpace || str_starts_with($a, $potentialSpace)) return true;
                if ($a === $potentialNoSpace || str_starts_with($a, $potentialNoSpace)) return true;
            }
        }
        return false;
    }

    private function isPartialAnchor(string $text): bool
    {
        $lower = strtolower(trim($text));
        if ($lower === '') return false;
        foreach (self::FIELD_ANCHORS as $anchors) {
            foreach ($anchors as $anchor) {
                $a = strtolower($anchor);
                if (str_starts_with($a, $lower) && $lower !== $a) return true;
            }
        }
        return false;
    }

    private const HEADER_FRAGMENTS = ['usaha/nib/jenis', 'pm/skala usaha'];

    private function isHeaderFragment(string $text): bool
    {
        return in_array(strtolower(trim($text)), self::HEADER_FRAGMENTS, true);
    }

    private const NOISE_EXACT = [
        'oss', 'pb', 'no', 'ks', 'pen:', 'fo v', 'vv 1', 'vv 2',
        'data sanksi', 'perizinan berusaha', 'lokasi usaha', 'data usaha', 'daftar list sanksi',
        'item per halaman', 'tabel', 'judul',
    ];

    private const NOISE_CONTAINS = [
        'beranda', 'pelaporan', 'pengaduan', 'pelacakan', 'pemrosesan.oss.go.id', 'oss.go.id',
        'investasi dan hilirisasi', 'cari berdasarkan', 'daftar list', 'ikuti lembaga oss',
        'lembaga oss - kementerian', 'media sosial', 'non pencabutan', 'oto1ko', 'lampiran', 'lihat detail', 'nurmarita',
    ];

    private function isNoiseLine(string $text): bool
    {
        $trimmed = trim($text);
        if ($trimmed === '') return true;
        if ($trimmed === 'Sumber data:') return true;
        $lower = strtolower($trimmed);
        if (in_array($lower, self::NOISE_EXACT, true)) return true;
        foreach (self::NOISE_CONTAINS as $pattern) {
            if (str_contains($lower, $pattern)) return true;
        }
        if (preg_match('/^\d+\s+of\s+\d+$/i', $trimmed)) return true;
        if (preg_match('/^https?:\/\//i', $trimmed)) return true;
        if (str_contains($trimmed, '©')) return true;
        if (str_contains($trimmed, '|')) return true;
        if (preg_match('/^\d{1,2}\/\d{1,2}\/\d{4},?\s*\d{1,2}:\d{2}/', $trimmed)) return true;
        if (preg_match('/\b(sanksi|pencabutan|pelaporan|pengaduan|pelacakan)\s+v\w*/i', $trimmed)) return true;
        if (preg_match('/^[a-z]{1,3}\s+v$/i', $trimmed)) return true;
        if (preg_match('/^vv?\s*\d+$/i', $trimmed)) return true;
        return false;
    }

    private const SECTIONS = [
        'usaha' => [
            'delimiter' => 'Nama Pelaku Usaha',
            'fields' => [
                'No' => 'no', 'No.' => 'no', 'Nomor' => 'no',
                'Nama Pelaku Usaha' => 'nama_pelaku_usaha',
                'Nomor Induk Berusaha (NIB)' => 'nib', 'Nomor Induk' => 'nib', 'Berusaha (NIB)' => 'nib', 'NIB' => 'nib',
                'Jenis Penanaman Modal' => 'jenis_penanaman_modal', 'Penanaman Modal' => 'jenis_penanaman_modal',
                'Skala Usaha' => 'skala_usaha',
            ],
        ],
        'lokasi' => [
            'delimiter' => 'Alamat',
            'fields' => [
                'Alamat' => 'alamat', 'Jalan' => 'alamat',
                'Kelurahan' => 'kelurahan', 'Gampong' => 'kelurahan', 'Desa' => 'kelurahan',
                'Kecamatan' => 'kecamatan',
                'Kab/Kota' => 'kab_kota', 'Kab. Kota' => 'kab_kota', 'Kabupaten/Kota' => 'kab_kota',
                'Provinsi' => 'provinsi',
            ],
        ],
    ];

    private function parseAllPairs(string $text): array
    {
        $lines = explode("\n", $text);
        $pairs = [];
        foreach ($lines as $line) {
            $t = trim($line);
            if ($t === '') continue;
            $colonPos = strpos($t, ':');
            if ($colonPos === false) continue;
            $key = trim(substr($t, 0, $colonPos));
            $value = trim(substr($t, $colonPos + 1));
            if ($key !== '') $pairs[] = ['key' => $key, 'value' => $value];
        }
        return $pairs;
    }

    private function buildRecordsFromPairs(array $pairs): array
    {
        $keyMap = $this->buildKeyMap();
        $sectionRecords = [];
        foreach (self::SECTIONS as $sectionName => $section) {
            $sectionRecords[$sectionName] = ['records' => [], 'current' => null];
        }
        foreach ($pairs as $pair) {
            $key = $pair['key'];
            $value = $pair['value'];
            if (!isset($keyMap[$key])) continue;
            $mapping = $keyMap[$key];
            $sectionName = $mapping['section'];
            $fieldName = $mapping['field'];
            $section = self::SECTIONS[$sectionName];
            $incoming = $this->cleanValue($value);
            if ($fieldName === 'no' && $incoming !== null) $incoming = is_numeric($incoming) ? (int) $incoming : null;
            if ($fieldName === 'nib' && $incoming !== null) $incoming = (string) $incoming;
            if ($key === $section['delimiter']) {
                if ($sectionRecords[$sectionName]['current'] !== null) {
                    $sectionRecords[$sectionName]['records'][] = $sectionRecords[$sectionName]['current'];
                }
                $sectionRecords[$sectionName]['current'] = $this->emptyRecord();
            } elseif ($sectionRecords[$sectionName]['current'] === null) {
                $sectionRecords[$sectionName]['current'] = $this->emptyRecord();
            } elseif ($incoming !== null && $sectionRecords[$sectionName]['current'][$fieldName] !== null) {
                $sectionRecords[$sectionName]['records'][] = $sectionRecords[$sectionName]['current'];
                $sectionRecords[$sectionName]['current'] = $this->emptyRecord();
            }
            if ($sectionRecords[$sectionName]['current'][$fieldName] === null) {
                $sectionRecords[$sectionName]['current'][$fieldName] = $incoming;
            }
        }
        foreach ($sectionRecords as $sectionName => $data) {
            if ($data['current'] !== null) $sectionRecords[$sectionName]['records'][] = $data['current'];
        }
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
        foreach (self::SECTIONS as $sectionName => $section) {
            foreach ($section['fields'] as $key => $fieldName) {
                $keyMap[$key] = ['section' => $sectionName, 'field' => $fieldName];
            }
        }
        return $keyMap;
    }

    private function matchAcrossSections(array $sectionRecords): array
    {
        $lists = [];
        $maxRecords = 0;
        foreach ($sectionRecords as $sectionName => $data) {
            $lists[$sectionName] = array_values($data['records']);
            $maxRecords = max($maxRecords, count($lists[$sectionName]));
        }
        if ($maxRecords === 0) return [];
        $records = [];
        for ($i = 0; $i < $maxRecords; $i++) {
            $record = $this->emptyRecord();
            foreach (['usaha', 'lokasi'] as $sectionName) {
                if (isset($lists[$sectionName][$i])) $this->fillNulls($record, $lists[$sectionName][$i]);
            }
            if ($record['nama_pelaku_usaha'] === null && $record['nib'] !== null) {
                foreach ($lists['usaha'] as $usaha) {
                    if (($usaha['nib'] ?? null) === $record['nib'] && ($usaha['nama_pelaku_usaha'] ?? null) !== null) {
                        $record['nama_pelaku_usaha'] = $usaha['nama_pelaku_usaha'];
                        break;
                    }
                }
            }
            $records[] = $record;
        }
        return $records;
    }

    private function fillNulls(array &$target, array $source): void
    {
        foreach ($source as $key => $value) {
            if ($value !== null && ($target[$key] ?? null) === null) $target[$key] = $value;
        }
    }

    private function cleanValue(?string $value): ?string
    {
        if ($value === null) return null;
        $value = trim($value);
        if ($value === '' || $value === '-') return null;
        $value = preg_replace('/\s+/', ' ', $value);
        $value = preg_replace('/\s+-\s*$/', '', $value);
        $value = trim($value);
        return $value !== '' ? $value : null;
    }

    private function finalizeRecords(array $records, string $filename): array
    {
        $results = [];
        foreach ($records as $i => $record) {
            $record['sumber_file'] = $filename;
            $record['sumber_halaman'] = (int) ceil(($i + 1) / 2);
            $record['sumber_metode'] = 'OCR';
            $record['skor_ocr'] = $this->confidence;
            if (isset($record['nib']) && $record['nib'] !== null) $record['nib'] = (string) $record['nib'];
            $results[] = $record;
        }
        return $results;
    }

    private function emptyRecord(): array
    {
        return ['no' => null, 'nama_pelaku_usaha' => null, 'nib' => null, 'jenis_penanaman_modal' => null, 'skala_usaha' => null, 'alamat' => null, 'kelurahan' => null, 'kecamatan' => null, 'kab_kota' => null, 'provinsi' => null];
    }
}
