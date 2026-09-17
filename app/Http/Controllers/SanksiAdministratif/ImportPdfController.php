<?php

namespace App\Http\Controllers\SanksiAdministratif;

use App\Http\Controllers\Controller;
use App\Models\SanksiAdministratif;
use App\Services\SanksiAdministratif\OcrSanksiAdministratifService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ImportPdfController extends Controller
{
    public function __construct(
        private OcrSanksiAdministratifService $ocrService
    ) {}

    public function index()
    {
        return Inertia::render('SanksiAdministratif/ImportPdf', [
            'results' => session('sanksi_import_results'),
            'filename' => session('sanksi_import_filename'),
            'flash' => [
                'success' => session('success'),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'file_pdf' => 'required|file|mimes:pdf,jpg,jpeg,png,bmp,tiff,webp,json|max:20480',
        ]);

        $file = $request->file('file_pdf');
        $filename = time() . '_' . $file->getClientOriginalName();
        $path = $file->storeAs('import', $filename, 'local');
        $fullPath = Storage::disk('local')->path($path);

        $ext = strtolower($file->getClientOriginalExtension());
        if ($ext === 'json') {
            $text = file_get_contents($fullPath);
            $decoded = json_decode($text, true);
            if (is_array($decoded)) {
                $results = $this->ocrService->parseJsonArray($decoded, $filename);
            } else {
                $results = $this->ocrService->parse($text, $filename);
            }
        } else {
            $results = $this->ocrService->processFile($fullPath, $filename);
        }

        session([
            'sanksi_import_results' => $results,
            'sanksi_import_filename' => $filename,
        ]);

        return redirect()->route('sanksi-administratif.import')->withInput();
    }

    public function simpanHasil(Request $request)
    {
        $request->validate(['data' => 'required|array', 'data.*.nama_pelaku_usaha' => 'nullable|string']);
        $items = $request->input('data', []);
        if (!is_array($items)) $items = [];

        $allowed = (new SanksiAdministratif)->getFillable();
        $savedCount = 0;

        foreach ($items as $item) {
            if (!is_array($item)) continue;
            if (empty($item['nama_pelaku_usaha'])) continue;

            $filtered = array_intersect_key($item, array_flip($allowed));
            $filtered = array_filter($filtered, fn($v) => $v !== null && $v !== '');

            if (isset($filtered['nib'])) {
                $filtered['nib'] = (string) $filtered['nib'];
            }
            if (isset($filtered['no']) && $filtered['no'] !== '') {
                $filtered['no'] = (int) $filtered['no'];
            }

            // Unique by NIB jika ada, agar import ulang file yang sama tidak duplikat
            if (!empty($filtered['nib'])) {
                SanksiAdministratif::updateOrCreate(
                    ['nib' => $filtered['nib']],
                    $filtered
                );
            } else {
                SanksiAdministratif::create($filtered);
            }
            $savedCount++;
        }

        session()->forget(['sanksi_import_results', 'sanksi_import_filename']);

        return redirect()->route('sanksi-administratif.import')->with('success', "{$savedCount} usulan pencabutan perizinan berusaha berhasil disimpan/diperbarui.");
    }

    public function clearSession()
    {
        session()->forget(['sanksi_import_results', 'sanksi_import_filename']);
        return redirect()->route('sanksi-administratif.import');
    }
}
