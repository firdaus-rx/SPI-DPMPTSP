<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SanksiAdministratif extends Model
{
    protected $table = 'sanksi_administratif';

    protected $fillable = [
        'no',
        'nama_pelaku_usaha',
        'nib',
        'jenis_penanaman_modal', // OCR key: penanaman_modal
        'skala_usaha',
        'alamat',      // OCR: alamat.jalan
        'kelurahan',   // OCR: alamat.kelurahan
        'kecamatan',   // OCR: alamat.kecamatan
        'kab_kota',    // OCR: alamat.kabupaten_kota
        'provinsi',    // OCR: alamat.provinsi
        'sumber_file',
        'sumber_halaman',
        'sumber_metode',
        'skor_ocr',
    ];

    protected function casts(): array
    {
        return [
            'no' => 'integer',
            'sumber_halaman' => 'integer',
            'skor_ocr' => 'decimal:2',
        ];
    }

    /**
     * Helper mapping dari JSON OCR ke fillable.
     * Contoh JSON OCR:
     * {
     *   "no": 2,
     *   "nama_pelaku_usaha": "BUMG LEUNGGOH JAYA",
     *   "nib": "9120300950831",
     *   "alamat": { "jalan": "...", "kelurahan": "...", "kecamatan": "...", "kabupaten_kota": "...", "provinsi": "..." },
     *   "penanaman_modal": "PMDN",
     *   "skala_usaha": null
     * }
     */
    public static function fromOcrArray(array $item, ?string $sumberFile = null): array
    {
        return [
            'no' => $item['no'] ?? null,
            'nama_pelaku_usaha' => $item['nama_pelaku_usaha'] ?? null,
            'nib' => isset($item['nib']) ? (string) $item['nib'] : null, // string agar 0 depan tidak hilang
            'jenis_penanaman_modal' => $item['penanaman_modal'] ?? null,
            'skala_usaha' => $item['skala_usaha'] ?? null,
            'alamat' => $item['alamat']['jalan'] ?? null,
            'kelurahan' => $item['alamat']['kelurahan'] ?? null,
            'kecamatan' => $item['alamat']['kecamatan'] ?? null,
            'kab_kota' => $item['alamat']['kabupaten_kota'] ?? null,
            'provinsi' => $item['alamat']['provinsi'] ?? null,
            'sumber_file' => $sumberFile ?? '2.Rancangan sistem sanksi administratif terintegrasi dan otomatis (peringatan tertulis dengan.pdf',
            'sumber_metode' => 'OCR',
        ];
    }
}
