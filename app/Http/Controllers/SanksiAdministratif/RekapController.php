<?php

namespace App\Http\Controllers\SanksiAdministratif;

use App\Http\Controllers\Controller;
use App\Models\SanksiAdministratif;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;

class RekapController extends Controller
{
    /**
     * Stream PDF rekap tabel:
     * No | Pelaku Usaha | NIB | Penanaman Modal | Skala | Lokasi
     * Sumber: filter/sort sama dengan Index, plus ?ids=1,2 untuk cetak terpilih.
     * Default: stream PDF inline landscape F4. ?html=1 untuk preview HTML.
     */
    public function rekap(Request $request)
    {
        $selectedIds = null;
        $ids = $request->query('ids');
        if (filled($ids)) {
            $selectedIds = collect(explode(',', (string) $ids))
                ->map(fn($v) => (int) trim($v))->filter()->values()->all();
        }

        $filters = [
            'search' => $request->query('search', ''),
            'skala_usaha' => $request->query('skala_usaha', ''),
            'kecamatan' => $request->query('kecamatan', ''),
        ];

        $query = SanksiAdministratif::query();
        if ($selectedIds !== null && count($selectedIds) > 0) {
            $query->whereIn('id', $selectedIds);
        } else {
            if (filled($filters['search'])) {
                $s = $filters['search'];
                $query->where(function ($q) use ($s) {
                    $q->where('nama_pelaku_usaha', 'like', "%{$s}%")
                        ->orWhere('nib', 'like', "%{$s}%")
                        ->orWhere('alamat', 'like', "%{$s}%")
                        ->orWhere('kelurahan', 'like', "%{$s}%")
                        ->orWhere('kecamatan', 'like', "%{$s}%")
                        ->orWhere('kab_kota', 'like', "%{$s}%");
                });
            }
            if (filled($filters['skala_usaha'])) $query->where('skala_usaha', $filters['skala_usaha']);
            if (filled($filters['kecamatan'])) $query->where('kecamatan', $filters['kecamatan']);
        }

        $query->orderByRaw('no IS NULL')->orderBy('no')->orderBy('id');
        // Cetak: jangan paginate — ambil semua yang terfilter/terpilih (cap 500)
        $items = $query->limit(500)->get();

        if ($items->isEmpty()) {
            return redirect()->route('sanksi-administratif.index')->with('success', 'Tidak ada data untuk rekap.');
        }

        $meta = [
            'nomor' => $request->query('nomor', ''),
            'tanggal' => $request->query('tanggal', now()->format('d F Y')),
            'tempat' => $request->query('tempat', 'Sigli'),
        ];

        $subtitle = $selectedIds ? 'Data terpilih' : 'Seluruh data terfilter';

        if ($request->boolean('html')) {
            return view('template.rekap-sanksi', [
                'items' => $items,
                'filters' => $filters,
                'meta' => $meta,
                'subtitle' => $subtitle,
                'selectedIds' => $selectedIds,
                'logoSrc' => '/logo-pidie.svg',
                'isPdf' => false,
            ]);
        }

        $download = $request->boolean('download');
        $filename = 'Rekap-Usulan-Pencabutan-' . now()->format('Ymd-His') . '.pdf';

        return $this->streamPdf($items, $filters, $meta, $subtitle, $selectedIds, $filename, $download);
    }

    private function streamPdf($items, array $filters, array $meta, string $subtitle, ?array $selectedIds, string $filename, bool $download)
    {
        $logoSrc = '/logo-pidie.svg';
        $pngPath = public_path('logo.png');
        $svgPath = public_path('logo-pidie.svg');
        if (is_file($pngPath)) {
            $logoSrc = 'data:image/png;base64,' . base64_encode((string) file_get_contents($pngPath));
        } elseif (is_file($svgPath)) {
            $logoSrc = 'data:image/svg+xml;base64,' . base64_encode((string) file_get_contents($svgPath));
        }

        $html = view('template.rekap-sanksi', [
            'items' => $items,
            'filters' => $filters,
            'meta' => $meta,
            'subtitle' => $subtitle,
            'selectedIds' => $selectedIds,
            'logoSrc' => $logoSrc,
            'isPdf' => true,
        ])->render();

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('defaultFont', 'Times New Roman');

        $dompdf = new Dompdf($options);
        // Landscape F4: 330mm x 215mm → 935.43pt x 609.45pt — cocok untuk 6 kolom
        $dompdf->setPaper([0, 0, 935.43, 609.45], 'landscape');
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->render();

        $output = $dompdf->output();

        return response($output, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => ($download ? 'attachment' : 'inline') . '; filename="' . $filename . '"',
            'Content-Length' => strlen($output),
        ]);
    }
}
