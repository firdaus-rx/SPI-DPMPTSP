<?php

namespace App\Services\Pengawasan;

use Illuminate\Support\Facades\Log;

class OcrPengawasanService
{
    private float $confidence = 0.0;
    private string $tesseractBinary;
    private string $popplerBinary;
    private string $language;

    private const FIELD_ANCHORS = [
        'nomor_sanksi'                                => ['Nomor Sanksi'],
        'tanggal_pengenaan_sanksi'                    => ['Tanggal Pengenaan Sanksi'],
        'tenggat_waktu_pemenuhan_kewajiban_tanggapan' => ['Tenggat Waktu Pemenuhan Kewajiban/Tanggapan', 'Tenggat Waktu Pemenuhan'],
        'jenis_sanksi'                                => ['Jenis Sanksi'],
        'masa_berlaku'                                => ['Masa Berlaku'],
        'status_sanksi'                               => ['Status Sanksi'],
        'sumber_sanksi'                               => ['Sumber Sanksi'],
        'nama_pelaku_usaha'                           => ['Nama Pelaku Usaha'],
        'nib'                                         => ['Nomor Induk Berusaha (NIB)', 'Nomor Induk', 'Berusaha (NIB)'],
        'jenis_penanaman_modal'                       => ['Jenis Penanaman Modal'],
        'skala_usaha'                                 => ['Skala Usaha'],
        'sumber_data'                                 => ['Sumber Data'],
        'jenis_perizinan'                             => ['Jenis Perizinan'],
        'nomor_perizinan'                             => ['Nomor Perizinan'],
        'status_perizinan'                            => ['Status'],
        'kementerian_lembaga'                         => ['Kementerian/Lembaga'],
        'kewenangan'                                  => ['Kewenangan', 'Kewanangan'],
        'alamat'                                      => ['Alamat'],
        'kelurahan'                                   => ['Kelurahan'],
        'kecamatan'                                   => ['Kecamatan'],
        'kab_kota'                                    => ['Kab/Kota', 'Kab. Kota'],
        'provinsi'                                    => ['Provinsi'],
        'nomor_kode_proyek'                           => ['Nomor Kode Proyek'],
        'luas_lahan'                                  => ['Luas Lahan'],
        'jumlah_tenaga_kerja'                         => ['Jumlah Tenaga Kerja'],
        'rencana_investasi'                           => ['Rencana Investasi'],
        'tingkat_risiko'                              => ['Tingkat Risiko'],
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

    // =========================================================================
    // PUBLIC API
    // =========================================================================

    public function processFile(string $filePath, string $filename): array
    {
        $text = $this->extractText($filePath);

        Log::info('OCR Extract', [
            'file' => $filename,
            'text_length' => strlen($text),
            'text_preview' => mb_substr($text, 0, 2000),
        ]);

        return $this->parse($text, $filename);
    }

    public function parse(string $text, string $filename): array
    {
        $normalized = $this->normalizeText($text);
        $pairs = $this->parseAllPairs($normalized);
        $sectionCounts = $this->countSectionPairs($pairs);
        // Sengaja tanpa dedup: satu NIB bisa punya beberapa Nomor Sanksi,
        // semua baris wajib terbaca (unik di DB = nomor_sanksi).
        $records = $this->buildRecordsFromPairs($pairs);

        Log::info('OCR Parse', [
            'text_length' => strlen($text),
            'normalized_length' => strlen($normalized),
            'pairs_found' => count($pairs),
            'pairs_per_section' => $sectionCounts,
            'records_found' => count($records),
            'nomor_sanksi' => array_values(array_filter(array_map(fn($r) => $r['nomor_sanksi'] ?? null, $records))),
        ]);

        if (empty($records)) return [];

        return $this->finalizeRecords($records, $filename);
    }

    // =========================================================================
    // OCR EXTRACTION
    // =========================================================================

    private function extractText(string $filePath): string
    {
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        if (in_array($ext, ['jpg', 'jpeg', 'png', 'bmp', 'tiff', 'webp'])) {
            return $this->extractFromImage($filePath);
        }

        if ($ext === 'pdf') {
            return $this->extractFromPdf($filePath);
        }

        return '';
    }

    private function extractFromPdf(string $pdfPath): string
    {
        $text = $this->pdfViaPoppler($pdfPath);

        if ($text !== '') return $text;

        Log::info('pdftoppm gagal, coba tesseract langsung pada PDF');
        return $this->tesseractOcr($pdfPath);
    }

    private function pdfViaPoppler(string $pdfPath): string
    {
        $tempDir = sys_get_temp_dir();
        $prefix = 'ocr_' . uniqid();
        $outputBase = $tempDir . '\\' . $prefix;
        $pdfWin = str_replace('/', '\\', $pdfPath);

        $cmd = sprintf('"%s" -png -r 300 "%s" "%s"', $this->popplerBinary, $pdfWin, $outputBase);

        exec($cmd . ' 2>&1', $output, $rc);

        if ($rc !== 0) {
            Log::warning('pdftoppm failed', ['cmd' => $cmd, 'rc' => $rc, 'output' => $output]);
            return '';
        }

        $images = glob($tempDir . '\\' . $prefix . '*.png');

        if (empty($images)) {
            Log::warning('pdftoppm tidak menghasilkan gambar', ['cmd' => $cmd]);
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

        if ($text !== '') {
            $this->confidence = 85.0;
        }

        return $text;
    }

    private function tesseractOcr(string $filePath): string
    {
        $filePathWin = str_replace('/', '\\', $filePath);

        $cmd = sprintf('"%s" "%s" stdout -l %s', $this->tesseractBinary, $filePathWin, $this->language);

        exec($cmd . ' 2>&1', $output, $rc);

        if ($rc !== 0) {
            Log::error('tesseract failed', ['cmd' => $cmd, 'rc' => $rc, 'output' => $output]);
            return '';
        }

        return implode("\n", $output);
    }

    // =========================================================================
    // TEXT NORMALIZATION — merge multi-line anchors & values into key:value
    // =========================================================================

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

            // Header kolom tabel OSS ("Nama Pelaku / Usaha/NIB/Jenis / PM/Skala Usaha"):
            // baris "Nama Pelaku"-nya dibuang agar tidak jadi pendingKey palsu,
            // baris fragmentnya dilewati agar tidak menempel ke value sebelumnya.
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
                        // Nomor halaman/paginasi mandiri ("10", "2") jangan ditempel ke value.
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

        if ($pendingKey !== '') {
            $result[] = $pendingKey . ':';
        }

        return implode("\n", $result);
    }

    /**
     Gabung lanjutan value multi-baris.
     Tanda hubung di ujung baris yang menempel pada kata (mis. "Banda-Aceh-"
     + "Medan") disambung tanpa spasi -> "Banda-Aceh-Medan".
     Tanda pisah " - " (mis. "Geumpang -" + "Tutut") tetap pakai spasi.
    */
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

    /**
     Fragment header kolom tabel OSS. Ditangani khusus di normalizeText
     (bukan noise global) agar pendingKey "Nama Pelaku" ikut dibuang.
    */
    private const HEADER_FRAGMENTS = ['usaha/nib/jenis', 'pm/skala usaha'];

    private function isHeaderFragment(string $text): bool
    {
        return in_array(strtolower(trim($text)), self::HEADER_FRAGMENTS, true);
    }

    /** Baris noise: cocok persis (case-insensitive), tanpa membunuh value asli. */
    private const NOISE_EXACT = [
        'oss',
        'pb',
        'no',
        'ks',
        'pen:',
        'fo v',
        'vv 1',
        'vv 2',
        'data sanksi',
        'perizinan berusaha',
        'lokasi usaha',
        'data usaha',
        'daftar list sanksi',
        'item per halaman',
        'tabel',
        'judul',
    ];

    /**
     Frasa noise (substring, case-insensitive). Dipilih yang TIDAK PERNAH
     muncul di value asli: "Pencabutan" (value jenis_sanksi) dan "OSS RBA"
     (value sumber_data) sengaja tidak ada di daftar ini.
    */
    private const NOISE_CONTAINS = [
        'beranda',
        'pelaporan',
        'pengaduan',
        'pelacakan',
        'pemrosesan.oss.go.id',
        'oss.go.id',
        // Tanpa kata "kementerian": footer © terpotong baris menjadi
        // "Investasi dan Hilirisasi/BKPM". Value asli hanya mengandung
        // "Investasi/BKPM" (tanpa "dan hilirisasi") sehingga aman.
        'investasi dan hilirisasi',
        'cari berdasarkan',
        'daftar list',
        'ikuti lembaga oss',
        'lembaga oss - kementerian',
        'media sosial',
        'non pencabutan',
        'oto1ko',
        'lampiran',
        'lihat detail',
        'nurmarita',
    ];

    private function isNoiseLine(string $text): bool
    {
        $trimmed = trim($text);
        if ($trimmed === '') return true;

        // "Sumber data:" (d kecil) = filter UI di header halaman,
        // bedakan dari anchor asli "Sumber Data:" (D besar).
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
        // Stempel footer "15/09/2026, 15:56" — tanggal asli tak pernah bawa jam.
        if (preg_match('/^\d{1,2}\/\d{1,2}\/\d{4},?\s*\d{1,2}:\d{2}/', $trimmed)) return true;
        // Menu navigasi "SANKSI v" / "PENCABUTAN vy" — value asli "Pencabutan"
        // tidak berekor " v" sehingga aman.
        if (preg_match('/\b(sanksi|pencabutan|pelaporan|pengaduan|pelacakan)\s+v\w*/i', $trimmed)) return true;
        if (preg_match('/^[a-z]{1,3}\s+v$/i', $trimmed)) return true;
        if (preg_match('/^vv?\s*\d+$/i', $trimmed)) return true;

        return false;
    }

    // =========================================================================
    // RECORD PARSING — parse all pairs, group by section, match by NIB
    // =========================================================================

    private const SECTIONS = [
        'sanksi' => [
            'delimiter' => 'Nomor Sanksi',
            'fields' => [
                'Nomor Sanksi' => 'nomor_sanksi',
                'Tanggal Pengenaan Sanksi' => 'tanggal_pengenaan_sanksi',
                'Tenggat Waktu Pemenuhan Kewajiban/Tanggapan' => 'tenggat_waktu_pemenuhan_kewajiban_tanggapan',
                'Tenggat Waktu Pemenuhan' => 'tenggat_waktu_pemenuhan_kewajiban_tanggapan',
                'Jenis Sanksi' => 'jenis_sanksi',
                'Masa Berlaku' => 'masa_berlaku',
                'Status Sanksi' => 'status_sanksi',
                'Sumber Sanksi' => 'sumber_sanksi',
            ],
        ],
        'usaha' => [
            'delimiter' => 'Nama Pelaku Usaha',
            'fields' => [
                'Nama Pelaku Usaha' => 'nama_pelaku_usaha',
                'Nomor Induk Berusaha (NIB)' => 'nib',
                'Nomor Induk' => 'nib',
                'Berusaha (NIB)' => 'nib',
                'Jenis Penanaman Modal' => 'jenis_penanaman_modal',
                'Skala Usaha' => 'skala_usaha',
                'Sumber Data' => 'sumber_data',
            ],
        ],
        'perizinan' => [
            'delimiter' => 'Jenis Perizinan',
            'fields' => [
                'Jenis Perizinan' => 'jenis_perizinan',
                'Nomor Perizinan' => 'nomor_perizinan',
                'Status' => 'status_perizinan',
                'Kementerian/Lembaga' => 'kementerian_lembaga',
                'Kewenangan' => 'kewenangan',
                'Kewanangan' => 'kewenangan',
            ],
        ],
        'lokasi' => [
            'delimiter' => 'Alamat',
            'fields' => [
                'Alamat' => 'alamat',
                'Kelurahan' => 'kelurahan',
                'Kecamatan' => 'kecamatan',
                'Kab/Kota' => 'kab_kota',
                'Kab. Kota' => 'kab_kota',
                'Provinsi' => 'provinsi',
            ],
        ],
        'usaha_detail' => [
            'delimiter' => 'Nomor Kode Proyek',
            'fields' => [
                'Nomor Kode Proyek' => 'nomor_kode_proyek',
                'Luas Lahan' => 'luas_lahan',
                'Jumlah Tenaga Kerja' => 'jumlah_tenaga_kerja',
                'Rencana Investasi' => 'rencana_investasi',
                'Tingkat Risiko' => 'tingkat_risiko',
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

            if ($key !== '') {
                $pairs[] = ['key' => $key, 'value' => $value];
            }
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

            if ($key === $section['delimiter']) {
                // Siklus/halaman baru selalu diawali section sanksi. Saat
                // delimiter sanksi muncul lagi sementara section lain masih
                // terbuka, segel semuanya dulu agar record yatim halaman
                // berikut tidak menimpa baris halaman sebelumnya.
                // (Delimiter sendiri HANYA menutup section-nya: OCR OSS membaca
                // kolom berdampingan sehingga perizinan/lokasi saling selip —
                // flush global akan memecah satu record jadi potongan.)
                if ($sectionName === 'sanksi' && !empty($sectionRecords['sanksi']['records'])) {
                    foreach ($sectionRecords as $sName => $sData) {
                        if ($sName !== 'sanksi' && $sData['current'] !== null) {
                            $sectionRecords[$sName]['records'][] = $sData['current'];
                            $sectionRecords[$sName]['current'] = null;
                        }
                    }
                }
                if ($sectionRecords[$sectionName]['current'] !== null) {
                    $sectionRecords[$sectionName]['records'][] = $sectionRecords[$sectionName]['current'];
                }
                $sectionRecords[$sectionName]['current'] = $this->emptyRecord();
            } elseif ($sectionRecords[$sectionName]['current'] === null) {
                // Record yatim (delimiter terpotong page-break): tetap tampung
                // agar urutan posisi antar-section selaras.
                $sectionRecords[$sectionName]['current'] = $this->emptyRecord();
            } elseif (
                $incoming !== null
                && $sectionRecords[$sectionName]['current'][$fieldName] !== null
            ) {
                // Field yang sama terisi lagi tanpa delimiter (mis. dua blok
                // "Luas Lahan" berurutan) = awal record baru section ini.
                $sectionRecords[$sectionName]['records'][] = $sectionRecords[$sectionName]['current'];
                $sectionRecords[$sectionName]['current'] = $this->emptyRecord();
            }

            // Jangan timpa value terisi dengan null (mis. "Kewenangan:" kosong
            // setelah record yatim).
            if ($sectionRecords[$sectionName]['current'][$fieldName] === null) {
                $sectionRecords[$sectionName]['current'][$fieldName] = $incoming;
            }
        }

        foreach ($sectionRecords as $sectionName => $data) {
            if ($data['current'] !== null) {
                $sectionRecords[$sectionName]['records'][] = $data['current'];
            }
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

    /**
     Gabung potongan per-section menjadi baris utuh.
     Satu NIB bisa punya beberapa Nomor Sanksi, jadi baris didorong oleh
     jumlah slot terbanyak (posisi), BUKAN di-dedup per NIB:
     - sanksi/lokasi/detail/usaha diambil posisional (slot ke-i),
     - perizinan diutamakan yang nomornya cocok NIB baris (claim sekali pakai,
       nomor OSS kadang = NIB + suffix), kalau tak ada baru posisional,
     - nama yang terpotong page-break di-backfill dari baris lain ber-NIB sama
       (NIB unik per perusahaan sehingga aman).
     Baris yang sepenuhnya kosong dibuang.
    */
    private function matchAcrossSections(array $sectionRecords): array
    {
        $lists = [];
        $maxRecords = 0;
        foreach ($sectionRecords as $sectionName => $data) {
            $lists[$sectionName] = array_values($data['records']);
            $maxRecords = max($maxRecords, count($lists[$sectionName]));
        }

        if ($maxRecords === 0) return [];

        $claimedPerizinan = [];
        $records = [];

        for ($i = 0; $i < $maxRecords; $i++) {
            $record = $this->emptyRecord();

            // Usaha: posisional (slot yatim ikut tersimpan).
            if (isset($lists['usaha'][$i])) {
                $this->fillNulls($record, $lists['usaha'][$i]);
            }

            // Perizinan: utamakan nomor yang cocok NIB (exact/prefix),
            // sekali pakai agar duplikat NIB tak berebut slot yang sama.
            $nib = $record['nib'];
            $picked = null;
            if ($nib !== null) {
                foreach ($lists['perizinan'] as $pi => $perizinan) {
                    if (isset($claimedPerizinan[$pi])) continue;
                    $nomor = $perizinan['nomor_perizinan'];
                    if ($nomor !== null && ($nomor === $nib || str_starts_with($nomor, $nib))) {
                        $picked = $pi;
                        break;
                    }
                }
            }
            if ($picked === null && isset($lists['perizinan'][$i]) && !isset($claimedPerizinan[$i])) {
                $picked = $i;
            }
            if ($picked === null) {
                foreach ($lists['perizinan'] as $pi => $perizinan) {
                    if (!isset($claimedPerizinan[$pi])) {
                        $picked = $pi;
                        break;
                    }
                }
            }
            if ($picked !== null) {
                $claimedPerizinan[$picked] = true;
                $this->fillNulls($record, $lists['perizinan'][$picked]);
            }

            foreach (['sanksi', 'lokasi', 'usaha_detail'] as $sectionName) {
                if (isset($lists[$sectionName][$i])) {
                    $this->fillNulls($record, $lists[$sectionName][$i]);
                }
            }

            // Backfill nama perusahaan yang terpotong page-break.
            if ($record['nama_pelaku_usaha'] === null && $record['nib'] !== null) {
                foreach ($lists['usaha'] as $usaha) {
                    if ($usaha['nib'] === $record['nib'] && $usaha['nama_pelaku_usaha'] !== null) {
                        $record['nama_pelaku_usaha'] = $usaha['nama_pelaku_usaha'];
                        break;
                    }
                }
            }

            // Baris kosong-sepenuhnya tetap disimpan sebagai slot agar
            // urutan posisi dan perilaku lama ("-" = null) tidak berubah;
            // yang tanpa nama otomatis dilewati saat simpan.
            $records[] = $record;
        }

        return $records;
    }

    private function fillNulls(array &$target, array $source): void
    {
        foreach ($source as $key => $value) {
            if ($value !== null && ($target[$key] ?? null) === null) {
                $target[$key] = $value;
            }
        }
    }

    private function cleanValue(?string $value): ?string
    {
        if ($value === null) return null;
        $value = trim($value);
        if ($value === '' || $value === '-') return null;
        $value = preg_replace('/\s+/', ' ', $value);
        // Ekor " -" tanpa lanjutan (mis. "Jln. Test -") dibuang.
        $value = preg_replace('/\s+-\s*$/', '', $value);
        $value = trim($value);
        return $value !== '' ? $value : null;
    }

    // =========================================================================
    // NORMALIZATION — convert OCR text to correct types
    // =========================================================================

    private function normalizeRecord(array $data): array
    {
        $data['tanggal_pengenaan_sanksi'] = $this->parseDate($data['tanggal_pengenaan_sanksi'] ?? null);
        $data['tenggat_waktu_pemenuhan_kewajiban_tanggapan'] = $this->parseDate($data['tenggat_waktu_pemenuhan_kewajiban_tanggapan'] ?? null);

        $luas = $this->parseUnit($data['luas_lahan'] ?? null);
        $data['luas_lahan'] = $luas['value'];
        $data['satuan_luas'] = $luas['unit'];

        $data['jumlah_tenaga_kerja'] = $this->parseInteger($data['jumlah_tenaga_kerja'] ?? null);
        $data['rencana_investasi'] = $this->parseRupiah($data['rencana_investasi'] ?? null);

        return $data;
    }

    public static function parseRupiah(?string $text): ?float
    {
        if ($text === null || $text === '' || $text === '-') return null;
        $text = preg_replace('/^Rp\s*/i', '', trim($text));
        $text = str_replace('.', '', $text);
        $text = str_replace(',', '.', $text);
        return is_numeric($text) ? (float) $text : null;
    }

    public static function parseDate(?string $text): ?string
    {
        if ($text === null || $text === '' || $text === '-') return null;
        $text = trim($text);
        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $text, $m)) {
            return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
        }
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $text) ? $text : null;
    }

    public static function parseUnit(?string $text): array
    {
        if ($text === null || $text === '' || $text === '-') return ['value' => null, 'unit' => null];
        $text = trim($text);
        if (preg_match('/^([\d.,]+)\s*(M2|M²|m2|m²|Ha|ha)?$/i', $text, $m)) {
            $val = str_replace(',', '.', $m[1]);
            return ['value' => is_numeric($val) ? (float) $val : null, 'unit' => $m[2] ?? null];
        }
        return is_numeric($text) ? ['value' => (float) $text, 'unit' => null] : ['value' => null, 'unit' => null];
    }

    public static function parseInteger(?string $text): ?int
    {
        if ($text === null || $text === '' || $text === '-') return null;
        $text = preg_replace('/\s*(orang|Orang|ORANG)$/i', '', trim($text));
        return is_numeric($text) ? (int) $text : null;
    }

    // =========================================================================
    // FINALIZE — tambah metadata, return records
    // =========================================================================

    private function finalizeRecords(array $records, string $filename): array
    {
        $results = [];
        foreach ($records as $i => $record) {
            $record = $this->normalizeRecord($record);
            $record['sumber_file'] = $filename;
            $record['sumber_halaman'] = (int) ceil(($i + 1) / 2);
            $record['sumber_metode'] = 'OCR';
            $record['skor_ocr'] = $this->confidence;
            $results[] = $record;
        }
        return $results;
    }

    private function emptyRecord(): array
    {
        return [
            'nomor_sanksi' => null,
            'tanggal_pengenaan_sanksi' => null,
            'tenggat_waktu_pemenuhan_kewajiban_tanggapan' => null,
            'jenis_sanksi' => null,
            'masa_berlaku' => null,
            'status_sanksi' => null,
            'sumber_sanksi' => null,
            'nama_pelaku_usaha' => null,
            'nib' => null,
            'jenis_penanaman_modal' => null,
            'skala_usaha' => null,
            'sumber_data' => null,
            'jenis_perizinan' => null,
            'nomor_perizinan' => null,
            'status_perizinan' => null,
            'kementerian_lembaga' => null,
            'kewenangan' => null,
            'alamat' => null,
            'kelurahan' => null,
            'kecamatan' => null,
            'kab_kota' => null,
            'provinsi' => null,
            'nomor_kode_proyek' => null,
            'luas_lahan' => null,
            'satuan_luas' => null,
            'jumlah_tenaga_kerja' => null,
            'rencana_investasi' => null,
            'tingkat_risiko' => null,
        ];
    }
}
