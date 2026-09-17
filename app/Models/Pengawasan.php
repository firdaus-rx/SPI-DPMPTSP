<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pengawasan extends Model
{
    protected $table = 'pengawasan';

    protected $fillable = [
        'nomor_sanksi',
        'tanggal_pengenaan_sanksi',
        'tenggat_waktu_pemenuhan_kewajiban_tanggapan',
        'jenis_sanksi',
        'masa_berlaku',
        'status_sanksi',
        'sumber_sanksi',
        'nama_pelaku_usaha',
        'nib',
        'jenis_penanaman_modal',
        'skala_usaha',
        'sumber_data',
        'jenis_perizinan',
        'nomor_perizinan',
        'status_perizinan',
        'kementerian_lembaga',
        'kewenangan',
        'alamat',
        'kelurahan',
        'kecamatan',
        'kab_kota',
        'provinsi',
        'nomor_kode_proyek',
        'luas_lahan',
        'satuan_luas',
        'jumlah_tenaga_kerja',
        'rencana_investasi',
        'tingkat_risiko',
        'sumber_file',
        'sumber_halaman',
        'sumber_metode',
        'skor_ocr',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_pengenaan_sanksi' => 'date',
            'tenggat_waktu_pemenuhan_kewajiban_tanggapan' => 'date',
            'luas_lahan' => 'decimal:2',
            'rencana_investasi' => 'decimal:2',
            'skor_ocr' => 'decimal:2',
        ];
    }
}
