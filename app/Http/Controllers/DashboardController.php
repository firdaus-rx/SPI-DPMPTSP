<?php

namespace App\Http\Controllers;

use App\Models\Pengawasan;
use App\Models\SanksiAdministratif;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $totalPengawasan = Pengawasan::count();
        $totalSanksi = SanksiAdministratif::count();
        $totalAll = $totalPengawasan + $totalSanksi;

        // --- Sebaran Tingkat Risiko (Pengawasan) ---
        $countsRisiko = Pengawasan::query()
            ->selectRaw("COALESCE(NULLIF(TRIM(tingkat_risiko), ''), 'Belum Diisi') as risiko")
            ->selectRaw('COUNT(*) as total')
            ->groupBy('risiko')
            ->pluck('total', 'risiko')
            ->all();
        $orderRisiko = ['Rendah', 'Menengah Rendah', 'Menengah', 'Menengah Tinggi', 'Tinggi', 'Belum Diisi'];
        $risiko = collect($orderRisiko)
            ->filter(fn ($label) => array_key_exists($label, $countsRisiko))
            ->map(fn ($label) => ['label' => $label, 'total' => $countsRisiko[$label]])
            ->values()->all();
        $risikoTinggi = ($countsRisiko['Tinggi'] ?? 0) + ($countsRisiko['Menengah Tinggi'] ?? 0);
        $belumDiisi = $countsRisiko['Belum Diisi'] ?? 0;

        // --- Sebaran Skala Usaha (Sanksi Administratif Usulan Pencabutan) ---
        $countsSkala = SanksiAdministratif::query()
            ->selectRaw("COALESCE(NULLIF(TRIM(skala_usaha), ''), 'Belum Diisi') as skala")
            ->selectRaw('COUNT(*) as total')
            ->groupBy('skala')
            ->pluck('total', 'skala')
            ->all();
        $orderSkala = ['Usaha Mikro', 'Usaha Kecil', 'Usaha Menengah', 'Usaha Besar', 'Belum Diisi'];
        $skala = collect($orderSkala)
            ->filter(fn ($label) => array_key_exists($label, $countsSkala))
            ->map(fn ($label) => ['label' => $label, 'total' => $countsSkala[$label]])
            ->values()->all();

        // --- Sebaran Penanaman Modal (Sanksi) ---
        $penanamanModal = SanksiAdministratif::query()
            ->selectRaw("COALESCE(NULLIF(TRIM(jenis_penanaman_modal), ''), 'Tidak Tercatat') as pm")
            ->selectRaw('COUNT(*) as total')
            ->groupBy('pm')
            ->orderByDesc('total')
            ->limit(6)
            ->get()
            ->map(fn ($r) => ['label' => $r->pm, 'total' => $r->total])
            ->all();

        // --- Top Kecamatan (Sanksi) ---
        $topKecamatan = SanksiAdministratif::query()
            ->selectRaw("COALESCE(NULLIF(TRIM(kecamatan), ''), 'Tidak Tercatat') as kecamatan")
            ->selectRaw('COUNT(*) as total')
            ->groupBy('kecamatan')
            ->orderByDesc('total')
            ->limit(5)
            ->get()
            ->map(fn ($r) => ['label' => $r->kecamatan, 'total' => $r->total])
            ->all();

        // --- Top Kab/Kota (Pengawasan, opsional insight) ---
        $topKabKota = Pengawasan::query()
            ->selectRaw("COALESCE(NULLIF(TRIM(kab_kota), ''), 'Tidak Tercatat') as kab")
            ->selectRaw('COUNT(*) as total')
            ->groupBy('kab')
            ->orderByDesc('total')
            ->limit(3)
            ->get()
            ->map(fn ($r) => ['label' => $r->kab, 'total' => $r->total])
            ->all();

        // --- Data terbaru ---
        $recentPengawasan = Pengawasan::orderByDesc('id')->limit(5)
            ->get(['id', 'nama_pelaku_usaha', 'nib', 'tingkat_risiko', 'kab_kota', 'created_at']);
        $recentSanksi = SanksiAdministratif::orderByRaw('no IS NULL')->orderBy('no')->orderByDesc('id')->limit(5)
            ->get(['id', 'no', 'nama_pelaku_usaha', 'nib', 'skala_usaha', 'kecamatan', 'kab_kota', 'created_at']);

        // --- Ringkasan status sanksi (jika ada) ---
        $statusSanksi = Pengawasan::query()
            ->selectRaw("COALESCE(NULLIF(TRIM(status_sanksi), ''), 'Belum Ada Status') as st")
            ->selectRaw('COUNT(*) as total')
            ->groupBy('st')
            ->orderByDesc('total')
            ->limit(4)
            ->get()->map(fn ($r) => ['label' => $r->st, 'total' => $r->total])->all();

        return Inertia::render('Dashboard', [
            'totalPelakuUsaha' => $totalPengawasan,
            'totalPengawasan' => $totalPengawasan,
            'totalSanksi' => $totalSanksi,
            'totalAll' => $totalAll,
            'risiko' => $risiko,
            'risikoTinggi' => $risikoTinggi,
            'belumDiisi' => $belumDiisi,
            'skala' => $skala,
            'penanamanModal' => $penanamanModal,
            'topKecamatan' => $topKecamatan,
            'topKabKota' => $topKabKota,
            'recentPengawasan' => $recentPengawasan,
            'recentSanksi' => $recentSanksi,
            'statusSanksi' => $statusSanksi,
            'flash' => ['success' => session('success')],
        ]);
    }
}
