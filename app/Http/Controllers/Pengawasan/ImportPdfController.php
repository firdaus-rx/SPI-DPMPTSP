<?php

namespace App\Http\Controllers\Pengawasan;

use App\Http\Controllers\Controller;
use App\Models\Pengawasan;
use App\Services\Pengawasan\OcrPengawasanService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ImportPdfController extends Controller
{
    public function __construct(
        private OcrPengawasanService $ocrService
    ) {}

    public function index()
    {
        return Inertia::render('Pengawasan/ImportPdf', [
            'results' => session('import_results'),
            'filename' => session('import_filename'),
            'flash' => [
                'success' => session('success'),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'file_pdf' => 'required|file|mimes:pdf,jpg,jpeg,png,bmp,tiff,webp|max:20480',
        ]);

        $file = $request->file('file_pdf');
        $filename = time() . '_' . $file->getClientOriginalName();
        $path = $file->storeAs('import', $filename, 'local');

        $fullPath = Storage::disk('local')->path($path);

        $results = $this->ocrService->processFile($fullPath, $filename);

        // Log::info('OCR Results', [
        //     'count' => count($results),
        //     'results' => $results,
        //     'confidence' => $this->ocrService->getConfidence(),
        // ]);

        session([
            'import_results' => $results,
            'import_filename' => $filename,
        ]);

        return redirect()->route('pengawasan.import')->withInput();
    }

    public function simpanHasil(Request $request)
    {
        // Validasi hanya struktur: rule nested per-field TIDAK dipakai agar
        // $request->validate() tidak memangkas field lain (hanya nama/nomor
        // yang disebut yang akan lolos).
        $request->validate(['data' => 'required|array']);
        $items = $request->input('data', []);
        if (!is_array($items)) $items = [];

        $allowed = (new Pengawasan)->getFillable();
        $savedCount = 0;

        foreach ($items as $item) {
            if (!is_array($item)) continue;
            if (empty($item['nama_pelaku_usaha'])) continue;

            // Whitelist kolom DB agar key UI (selected, *_display) tak ikut tersimpan.
            $filtered = array_intersect_key($item, array_flip($allowed));
            $filtered = array_filter($filtered, fn($v) => $v !== null && $v !== '');

            $filtered['rencana_investasi'] = self::toNumber($filtered['rencana_investasi'] ?? null);
            $filtered['luas_lahan'] = self::toNumber($filtered['luas_lahan'] ?? null);
            $filtered['jumlah_tenaga_kerja'] = self::toNumber($filtered['jumlah_tenaga_kerja'] ?? null, true);
            if ($filtered['rencana_investasi'] === null) unset($filtered['rencana_investasi']);
            if ($filtered['luas_lahan'] === null) unset($filtered['luas_lahan']);
            if ($filtered['jumlah_tenaga_kerja'] === null) unset($filtered['jumlah_tenaga_kerja']);

            // Unique = nomor_sanksi: sudah ada -> update, belum -> buat baru.
            // Baris yatim tanpa nomor dicocokkan via NIB + nomor_sanksi NULL
            // agar import ulang file yang sama tidak dobel.
            if (!empty($filtered['nomor_sanksi'])) {
                Pengawasan::updateOrCreate(
                    ['nomor_sanksi' => $filtered['nomor_sanksi']],
                    $filtered
                );
            } elseif (!empty($filtered['nib'])) {
                Pengawasan::updateOrCreate(
                    ['nib' => $filtered['nib'], 'nomor_sanksi' => null],
                    $filtered
                );
            } else {
                Pengawasan::create($filtered);
            }
            $savedCount++;
        }

        session()->forget(['import_results', 'import_filename']);

        return redirect()->route('pengawasan.import')->with('success', "{$savedCount} data berhasil disimpan/diperbarui dari import PDF.");
    }

    /**
     Bersihkan angka dari format tampilan ("Rp 5.005.000.000", "5 Orang",
     "192 M2") maupun angka mentah dari OCR service.
    */
    private static function toNumber(mixed $value, bool $asInt = false): int|float|null
    {
        if ($value === null || $value === '') return null;
        if (is_int($value) || is_float($value)) return $asInt ? (int) $value : $value;
        $text = trim((string) $value);
        if ($text === '' || $text === '-') return null;
        $text = preg_replace('/^Rp\s*/i', '', $text);
        $text = preg_replace('/\s*(orang|m2|m²|ha)$/i', '', $text);
        $text = trim($text);
        if (str_contains($text, ',')) {
            $text = str_replace('.', '', $text);
            $text = str_replace(',', '.', $text);
        } else {
            // "5.005.000.000" = ribuan bertitik; "192.5" = desimal.
            $dotCount = substr_count($text, '.');
            if ($dotCount > 1 || preg_match('/\.\d{3}(\.|$)/', $text)) {
                $text = str_replace('.', '', $text);
            }
        }
        if (!is_numeric($text)) return null;
        return $asInt ? (int) $text : (float) $text;
    }

    public function clearSession()
    {
        session()->forget(['import_results', 'import_filename']);
        return redirect()->route('pengawasan.import');
    }
}
