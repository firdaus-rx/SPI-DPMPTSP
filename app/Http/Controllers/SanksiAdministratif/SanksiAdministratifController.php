<?php

namespace App\Http\Controllers\SanksiAdministratif;

use App\Http\Controllers\Controller;
use App\Models\SanksiAdministratif;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class SanksiAdministratifController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'search' => 'nullable|string|max:255',
            'skala_usaha' => 'nullable|string|max:255',
            'kecamatan' => 'nullable|string|max:255',
            'per_page' => ['sometimes', 'integer', Rule::in([10, 15, 25, 50])],
        ]);

        $filters = [
            'search' => $validated['search'] ?? '',
            'skala_usaha' => $validated['skala_usaha'] ?? '',
            'kecamatan' => $validated['kecamatan'] ?? '',
            'per_page' => (int) ($validated['per_page'] ?? 15),
        ];

        $query = SanksiAdministratif::query()->select([
            'id',
            'no',
            'nama_pelaku_usaha',
            'nib',
            'jenis_penanaman_modal',
            'skala_usaha',
            'alamat',
            'kelurahan',
            'kecamatan',
            'kab_kota',
            'provinsi',
        ]);

        if ($filters['search'] !== '') {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('nama_pelaku_usaha', 'like', "%{$search}%")
                    ->orWhere('nib', 'like', "%{$search}%")
                    ->orWhere('alamat', 'like', "%{$search}%")
                    ->orWhere('kelurahan', 'like', "%{$search}%")
                    ->orWhere('kecamatan', 'like', "%{$search}%")
                    ->orWhere('kab_kota', 'like', "%{$search}%");
            });
        }

        if ($filters['skala_usaha'] !== '') {
            $query->where('skala_usaha', $filters['skala_usaha']);
        }

        if ($filters['kecamatan'] !== '') {
            $query->where('kecamatan', $filters['kecamatan']);
        }

        $sanksi = $query->orderByRaw('no IS NULL')->orderBy('no')->orderBy('id')
            ->paginate($filters['per_page'])
            ->withQueryString()
            ->onEachSide(1);

        return Inertia::render('SanksiAdministratif/Index', [
            'sanksi' => $sanksi,
            'filters' => $filters,
            'flash' => [
                'success' => session('success'),
            ],
        ]);
    }

    public function create()
    {
        return Inertia::render('SanksiAdministratif/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'no' => 'nullable|integer|min:1',
            'nama_pelaku_usaha' => 'required|string|max:255',
            'nib' => 'nullable|string|max:50',
            'jenis_penanaman_modal' => 'nullable|string|max:255',
            'skala_usaha' => 'nullable|string|max:255',
            'alamat' => 'nullable|string',
            'kelurahan' => 'nullable|string|max:255',
            'kecamatan' => 'nullable|string|max:255',
            'kab_kota' => 'nullable|string|max:255',
            'provinsi' => 'nullable|string|max:255',
        ]);

        // NIB simpan sebagai string agar leading 0 tidak hilang
        if (isset($validated['nib'])) {
            $validated['nib'] = (string) $validated['nib'];
        }

        SanksiAdministratif::create($validated);

        return redirect()->route('sanksi-administratif.index')->with('success', 'Usulan pencabutan perizinan berusaha berhasil ditambahkan.');
    }

    public function show(SanksiAdministratif $sanksiAdministratif)
    {
        return Inertia::render('SanksiAdministratif/Show', [
            'sanksi' => $sanksiAdministratif,
            'flash' => [
                'success' => session('success'),
            ],
        ]);
    }

    public function edit(SanksiAdministratif $sanksiAdministratif)
    {
        return Inertia::render('SanksiAdministratif/Edit', [
            'sanksi' => $sanksiAdministratif,
        ]);
    }

    public function update(Request $request, SanksiAdministratif $sanksiAdministratif)
    {
        $validated = $request->validate([
            'no' => 'nullable|integer|min:1',
            'nama_pelaku_usaha' => 'required|string|max:255',
            'nib' => 'nullable|string|max:50',
            'jenis_penanaman_modal' => 'nullable|string|max:255',
            'skala_usaha' => 'nullable|string|max:255',
            'alamat' => 'nullable|string',
            'kelurahan' => 'nullable|string|max:255',
            'kecamatan' => 'nullable|string|max:255',
            'kab_kota' => 'nullable|string|max:255',
            'provinsi' => 'nullable|string|max:255',
        ]);

        if (isset($validated['nib'])) {
            $validated['nib'] = (string) $validated['nib'];
        }

        $sanksiAdministratif->update($validated);

        return redirect()->route('sanksi-administratif.show', $sanksiAdministratif)->with('success', 'Usulan pencabutan perizinan berusaha berhasil diperbarui.');
    }

    public function destroy(SanksiAdministratif $sanksiAdministratif)
    {
        $sanksiAdministratif->delete();

        return redirect()->route('sanksi-administratif.index')->with('success', 'Usulan pencabutan perizinan berusaha berhasil dihapus.');
    }
}
