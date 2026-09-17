<?php

namespace App\Http\Controllers\SanksiAdministratif;

use App\Http\Controllers\Controller;
use App\Models\SanksiAdministratif;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;

class Sp1Controller extends Controller
{
    /**
     * Stream PDF SP1 langsung preview inline via Dompdf (F4 215x330mm).
     * Browser menampilkan PDF preview dengan toolbar Print & Download native.
     * Query ?ids=1,2 massal; tanpa ids → 50 terbaru. ?html=1 untuk preview HTML saja.
     * ?download=1 untuk force download attachment.
     */
    public function show(Request $request, ?SanksiAdministratif $sanksiAdministratif = null)
    {
        if ($sanksiAdministratif && $sanksiAdministratif->exists) {
            $items = collect([$sanksiAdministratif]);
            $filename = 'SP1-' . ($sanksiAdministratif->nib ?? $sanksiAdministratif->id) . '.pdf';
        } else {
            $ids = $request->query('ids');
            if ($ids) {
                $idList = collect(explode(',', (string) $ids))->map(fn($v) => (int) trim($v))->filter()->values()->all();
                $items = SanksiAdministratif::whereIn('id', $idList)->orderBy('no')->orderBy('id')->get();
            } else {
                $items = SanksiAdministratif::orderBy('no')->orderBy('id')->limit(50)->get();
            }
            if ($items->isEmpty()) {
                return redirect()->route('sanksi-administratif.index')->with('success', 'Belum ada data hasil import untuk dicetak.');
            }
            $filename = 'SP1-massal-' . $items->count() . '-data.pdf';
        }

        $meta = [
            'nomor' => $request->query('nomor', '005/132/2022'),
            'sifat' => $request->query('sifat', 'Segera'),
            'lamp' => $request->query('lamp', '-'),
            'tanggal' => $request->query('tanggal', '24 Oktober 2022'),
            'tempat' => $request->query('tempat', 'Sigli'),
        ];

        if ($request->boolean('html')) {
            return view('template.sp1', [
                'items' => $items,
                'meta' => $meta,
                'isMassal' => $items->count() > 1,
                'logoSrc' => '/logo-pidie.svg',
                'isPdf' => false,
            ]);
        }

        $download = $request->boolean('download');
        return $this->streamPdf($items, $meta, $filename, $download);
    }

    public function print(Request $request, SanksiAdministratif $sanksiAdministratif)
    {
        return $this->show($request, $sanksiAdministratif);
    }

    private function streamPdf($items, array $meta, string $filename, bool $download = false)
    {
        $logoSrc = '/logo-pidie.svg';
        $pngPath = public_path('logo.png');
        $svgPath = public_path('logo-pidie.svg');
        if (is_file($pngPath)) {
            $logoSrc = 'data:image/png;base64,' . base64_encode((string) file_get_contents($pngPath));
        } elseif (is_file($svgPath)) {
            $svg = (string) file_get_contents($svgPath);
            $logoSrc = 'data:image/svg+xml;base64,' . base64_encode($svg);
        }

        $html = view('template.sp1', [
            'items' => $items,
            'meta' => $meta,
            'isMassal' => $items->count() > 1,
            'logoSrc' => $logoSrc,
            'isPdf' => true,
        ])->render();

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('defaultFont', 'Times New Roman');

        $dompdf = new Dompdf($options);
        // F4 Folio 215mm x 330mm → 609.45pt x 935.43pt
        $dompdf->setPaper([0, 0, 609.45, 935.43], 'portrait');
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
