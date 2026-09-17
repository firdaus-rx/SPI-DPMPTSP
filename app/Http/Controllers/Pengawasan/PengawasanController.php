<?php

namespace App\Http\Controllers\Pengawasan;

use App\Http\Controllers\Controller;
use App\Models\Pengawasan;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class PengawasanController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'search' => 'nullable|string|max:255',
            'tingkat_risiko' => 'nullable|string|max:255',
            'per_page' => ['sometimes', 'integer', Rule::in([10, 15, 25, 50])],
        ]);

        $filters = [
            'search' => $validated['search'] ?? '',
            'tingkat_risiko' => $validated['tingkat_risiko'] ?? '',
            'per_page' => (int) ($validated['per_page'] ?? 15),
        ];

        $query = Pengawasan::query()->select([
            'id',
            'nama_pelaku_usaha',
            'nib',
            'jenis_penanaman_modal',
            'skala_usaha',
            'kab_kota',
            'provinsi',
            'tingkat_risiko',
            'nomor_sanksi',
            'status_sanksi',
        ]);

        if ($filters['search'] !== '') {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('nama_pelaku_usaha', 'like', "%{$search}%")
                    ->orWhere('nib', 'like', "%{$search}%")
                    ->orWhere('nomor_perizinan', 'like', "%{$search}%");
            });
        }

        if ($filters['tingkat_risiko'] !== '') {
            $query->where('tingkat_risiko', $filters['tingkat_risiko']);
        }

        $pengawasan = $query->orderByDesc('id')
            ->paginate($filters['per_page'])
            ->withQueryString()
            ->onEachSide(1);

        return Inertia::render('Pengawasan/Index', [
            'pengawasan' => $pengawasan,
            'filters' => $filters,
            'flash' => [
                'success' => session('success'),
            ],
        ]);
    }

    public function create()
    {
        return Inertia::render('Pengawasan/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_pelaku_usaha' => 'required|string|max:255',
            'nib' => 'nullable|string|max:255',
            'nomor_sanksi' => 'nullable|string|max:255',
            'tanggal_pengenaan_sanksi' => 'nullable|date',
            'tenggat_waktu_pemenuhan_kewajiban_tanggapan' => 'nullable|date',
            'jenis_sanksi' => 'nullable|string|max:255',
            'masa_berlaku' => 'nullable|string|max:255',
            'status_sanksi' => 'nullable|string|max:255',
            'sumber_sanksi' => 'nullable|string',
            'jenis_penanaman_modal' => 'nullable|string|max:255',
            'skala_usaha' => 'nullable|string|max:255',
            'sumber_data' => 'nullable|string|max:255',
            'tingkat_risiko' => 'nullable|string|max:255',
            'jenis_perizinan' => 'nullable|string|max:255',
            'nomor_perizinan' => 'nullable|string|max:255',
            'status_perizinan' => 'nullable|string|max:255',
            'kementerian_lembaga' => 'nullable|string|max:255',
            'kewenangan' => 'nullable|string|max:255',
            'alamat' => 'nullable|string',
            'kelurahan' => 'nullable|string|max:255',
            'kecamatan' => 'nullable|string|max:255',
            'kab_kota' => 'nullable|string|max:255',
            'provinsi' => 'nullable|string|max:255',
            'nomor_kode_proyek' => 'nullable|string|max:255',
            'luas_lahan' => 'nullable|numeric',
            'satuan_luas' => 'nullable|string|max:255',
            'jumlah_tenaga_kerja' => 'nullable|integer',
            'rencana_investasi' => 'nullable|numeric',
        ]);

        Pengawasan::create($validated);

        return redirect()->route('pengawasan.index')->with('success', 'Data pengawasan berhasil ditambahkan.');
    }

    public function show(Pengawasan $pengawasan)
    {
        return Inertia::render('Pengawasan/Show', [
            'pengawasan' => $pengawasan,
            'flash' => [
                'success' => session('success'),
            ],
        ]);
    }

    public function edit(Pengawasan $pengawasan)
    {
        return Inertia::render('Pengawasan/Edit', [
            'pengawasan' => $pengawasan,
        ]);
    }

    public function update(Request $request, Pengawasan $pengawasan)
    {
        $validated = $request->validate([
            'nama_pelaku_usaha' => 'required|string|max:255',
            'nib' => 'nullable|string|max:255',
            'nomor_sanksi' => 'nullable|string|max:255',
            'tanggal_pengenaan_sanksi' => 'nullable|date',
            'tenggat_waktu_pemenuhan_kewajiban_tanggapan' => 'nullable|date',
            'jenis_sanksi' => 'nullable|string|max:255',
            'masa_berlaku' => 'nullable|string|max:255',
            'status_sanksi' => 'nullable|string|max:255',
            'sumber_sanksi' => 'nullable|string',
            'jenis_penanaman_modal' => 'nullable|string|max:255',
            'skala_usaha' => 'nullable|string|max:255',
            'sumber_data' => 'nullable|string|max:255',
            'tingkat_risiko' => 'nullable|string|max:255',
            'jenis_perizinan' => 'nullable|string|max:255',
            'nomor_perizinan' => 'nullable|string|max:255',
            'status_perizinan' => 'nullable|string|max:255',
            'kementerian_lembaga' => 'nullable|string|max:255',
            'kewenangan' => 'nullable|string|max:255',
            'alamat' => 'nullable|string',
            'kelurahan' => 'nullable|string|max:255',
            'kecamatan' => 'nullable|string|max:255',
            'kab_kota' => 'nullable|string|max:255',
            'provinsi' => 'nullable|string|max:255',
            'nomor_kode_proyek' => 'nullable|string|max:255',
            'luas_lahan' => 'nullable|numeric',
            'satuan_luas' => 'nullable|string|max:255',
            'jumlah_tenaga_kerja' => 'nullable|integer',
            'rencana_investasi' => 'nullable|numeric',
        ]);

        $pengawasan->update($validated);

        return redirect()->route('pengawasan.show', $pengawasan)->with('success', 'Data pengawasan berhasil diperbarui.');
    }

    public function destroy(Pengawasan $pengawasan)
    {
        $pengawasan->delete();

        return redirect()->route('pengawasan.index')->with('success', 'Data pengawasan berhasil dihapus.');
    }
}
