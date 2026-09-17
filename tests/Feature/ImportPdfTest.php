<?php

namespace Tests\Feature;

use App\Models\Pengawasan;
use App\Services\Pengawasan\OcrPengawasanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImportPdfTest extends TestCase
{
    use RefreshDatabase;

    private OcrPengawasanService $ocrService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ocrService = new OcrPengawasanService();
    }

    public function test_import_page_loads(): void
    {
        $response = $this->get('/pengawasan/import');
        $response->assertStatus(200);
    }

    public function test_import_requires_pdf_file(): void
    {
        $response = $this->post('/pengawasan/import');
        $response->assertSessionHasErrors('file_pdf');
    }

    public function test_import_rejects_non_pdf(): void
    {
        $file = UploadedFile::fake()->create('script.exe', 100, 'application/octet-stream');
        $response = $this->post('/pengawasan/import', ['file_pdf' => $file]);
        $response->assertSessionHasErrors('file_pdf');
    }

    public function test_import_stores_file_to_disk(): void
    {
        Storage::fake('local');
        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $this->post('/pengawasan/import', ['file_pdf' => $file]);

        $files = Storage::disk('local')->files('import');
        $this->assertNotEmpty($files);
        $this->assertStringContainsString('document.pdf', $files[0]);
    }

    // ====================================================================
    // OCR PARSER TESTS - Sesuai AGENT.md section 6 & 16
    // ====================================================================

    public function test_ocr_parse_cv_yahsyi(): void
    {
        $ocrText = <<<'OCR'
1

Nomor Sanksi:
SNK-202608131652033202826
Tanggal Pengenaan Sanksi:
13/08/2026
Tenggat Waktu Pemenuhan Kewajiban/Tanggapan:
Jenis Sanksi:
Pencabutan
Masa Berlaku:
Status Sanksi:
Terkirim ke Pelaku Usaha
Sumber Sanksi:
Pengenaan Sanksi Administratif oleh Kementerian Investasi/BKPM atas kewajiban LKPM

Nama Pelaku Usaha:
CV YAHSYI
Nomor Induk Berusaha (NIB):
0299000951365
Jenis Penanaman Modal:
Penanaman Modal Dalam Negeri (PMDN)
Skala Usaha:
Usaha Mikro
Sumber Data:
OSS RBA

Jenis Perizinan:
Sertifikat Standar
Nomor Perizinan:
0299000951365
Status:
Terbit Otomatis
Kementerian/Lembaga:
Kementerian Perindustrian
Kewenangan:

Alamat:
Jln. Geumpang - Tutut
Kelurahan:
Keune
Kecamatan:
Geumpang
Kab/Kota:
Kab. Pidie
Provinsi:
Aceh

Nomor Kode Proyek:
202205-1014-3945-4036-343
Luas Lahan:
192 M2
Jumlah Tenaga Kerja:
5 Orang
Rencana Investasi:
Rp 500.000.000
Tingkat Risiko:
Rendah
OCR;

        $results = $this->ocrService->parse($ocrText, 'test.pdf');

        $this->assertCount(1, $results);
        $data = $results[0];

        // DATA SANKSI
        $this->assertEquals('SNK-202608131652033202826', $data['nomor_sanksi']);
        $this->assertEquals('2026-08-13', $data['tanggal_pengenaan_sanksi']);
        $this->assertNull($data['tenggat_waktu_pemenuhan_kewajiban_tanggapan']);
        $this->assertEquals('Pencabutan', $data['jenis_sanksi']);
        $this->assertNull($data['masa_berlaku']);
        $this->assertEquals('Terkirim ke Pelaku Usaha', $data['status_sanksi']);
        $this->assertEquals('Pengenaan Sanksi Administratif oleh Kementerian Investasi/BKPM atas kewajiban LKPM', $data['sumber_sanksi']);

        // NAMA PELAKU USAHA
        $this->assertEquals('CV YAHSYI', $data['nama_pelaku_usaha']);
        $this->assertEquals('0299000951365', $data['nib']);
        $this->assertEquals('Penanaman Modal Dalam Negeri (PMDN)', $data['jenis_penanaman_modal']);
        $this->assertEquals('Usaha Mikro', $data['skala_usaha']);
        $this->assertEquals('OSS RBA', $data['sumber_data']);

        // PERIZINAN
        $this->assertEquals('Sertifikat Standar', $data['jenis_perizinan']);
        $this->assertEquals('0299000951365', $data['nomor_perizinan']);
        $this->assertEquals('Terbit Otomatis', $data['status_perizinan']);
        $this->assertEquals('Kementerian Perindustrian', $data['kementerian_lembaga']);
        $this->assertNull($data['kewenangan']);

        // LOKASI
        $this->assertEquals('Jln. Geumpang - Tutut', $data['alamat']);
        $this->assertEquals('Keune', $data['kelurahan']);
        $this->assertEquals('Geumpang', $data['kecamatan']);
        $this->assertEquals('Kab. Pidie', $data['kab_kota']);
        $this->assertEquals('Aceh', $data['provinsi']);

        // DATA USAHA - normalisasi sesuai section 16
        $this->assertEquals('202205-1014-3945-4036-343', $data['nomor_kode_proyek']);
        $this->assertEquals(192, $data['luas_lahan']);
        $this->assertEquals('M2', $data['satuan_luas']);
        $this->assertEquals(5, $data['jumlah_tenaga_kerja']);
        $this->assertEquals(500000000, $data['rencana_investasi']);
        $this->assertEquals('Rendah', $data['tingkat_risiko']);

        // Metadata
        $this->assertEquals('test.pdf', $data['sumber_file']);
        $this->assertEquals(1, $data['sumber_halaman']);
        $this->assertEquals('OCR', $data['sumber_metode']);
    }

    public function test_ocr_parse_pt_kimia_farma(): void
    {
        $ocrText = <<<'OCR'
2

Nomor Sanksi:
SNK-202608131605243997599
Tanggal Pengenaan Sanksi:
13/08/2026
Tenggat Waktu Pemenuhan Kewajiban/Tanggapan:
Jenis Sanksi:
Pencabutan
Masa Berlaku:
Status Sanksi:
Terkirim ke Pelaku Usaha
Sumber Sanksi:
Pengenaan Sanksi Administratif oleh Kementerian Investasi/BKPM atas kewajiban LKPM

Nama Pelaku Usaha:
PT KIMIA FARMA APOTEK
Nomor Induk Berusaha (NIB):
8120004902508
Jenis Penanaman Modal:
Penanaman Modal Asing (PMA)
Skala Usaha:
Usaha Mikro
Sumber Data:
OSS RBA

Jenis Perizinan:
Sertifikat Standar
Nomor Perizinan:
Status:
Izin terbit/SS terverifikasi
Kementerian/Lembaga:
Kementerian Kesehatan
Kewanangan:
Menteri/Kepala Badan

Alamat:
Jln. Banda-Aceh-Medan, Campong Dayah Teungoh-Tijue, Kec. Pidie, Kab.Pidie
Kelurahan:
Dayah Teungoh
Kecamatan:
Pidie
Kab/Kota:
Kab. Pidie
Provinsi:
Aceh

Nomor Kode Proyek:
202206-0218-0046-2443-330
Luas Lahan:
120 M2
Jumlah Tenaga Kerja:
3 Orang
Rencana Investasi:
Rp 620.000.000
Tingkat Risiko:
Tinggi
OCR;

        $results = $this->ocrService->parse($ocrText, 'test.pdf');

        $this->assertCount(1, $results);
        $data = $results[0];

        $this->assertEquals('PT KIMIA FARMA APOTEK', $data['nama_pelaku_usaha']);
        $this->assertEquals('8120004902508', $data['nib']);
        $this->assertEquals('Penanaman Modal Asing (PMA)', $data['jenis_penanaman_modal']);
        $this->assertEquals('Kementerian Kesehatan', $data['kementerian_lembaga']);
        $this->assertEquals('Menteri/Kepala Badan', $data['kewenangan']);
        $this->assertEquals(120, $data['luas_lahan']);
        $this->assertEquals(3, $data['jumlah_tenaga_kerja']);
        $this->assertEquals(620000000, $data['rencana_investasi']);
        $this->assertEquals('Tinggi', $data['tingkat_risiko']);
    }

    public function test_ocr_multi_section(): void
    {
        $ocrText = <<<'OCR'
1

Nama Pelaku Usaha:
CV YAHSYI
Nomor Induk Berusaha (NIB):
0299000951365
Luas Lahan:
192 M2
Tingkat Risiko:
Rendah

2

Nama Pelaku Usaha:
PT KIMIA FARMA APOTEK
Nomor Induk Berusaha (NIB):
8120004902508
Luas Lahan:
120 M2
Tingkat Risiko:
Tinggi
OCR;

        $results = $this->ocrService->parse($ocrText, 'test.pdf');

        $this->assertCount(2, $results);
        $this->assertEquals('CV YAHSYI', $results[0]['nama_pelaku_usaha']);
        $this->assertEquals('PT KIMIA FARMA APOTEK', $results[1]['nama_pelaku_usaha']);
        $this->assertEquals(192, $results[0]['luas_lahan']);
        $this->assertEquals(120, $results[1]['luas_lahan']);
    }

    // ====================================================================
    // PARSER EDGE CASES
    // ====================================================================

    public function test_parser_nib_leading_zero(): void
    {
        $ocrText = <<<'OCR'
Nama Pelaku Usaha:
CV Test
Nomor Induk Berusaha (NIB):
0299000951365
OCR;

        $results = $this->ocrService->parse($ocrText, 'test.pdf');
        $this->assertEquals('0299000951365', $results[0]['nib']);
        $this->assertStringStartsWith('0', $results[0]['nib']);
    }

    public function test_parser_rp_normalization(): void
    {
        $this->assertEquals(500000000, OcrPengawasanService::parseRupiah('Rp 500.000.000'));
        $this->assertEquals(620000000, OcrPengawasanService::parseRupiah('Rp 620.000.000'));
        $this->assertEquals(5000000, OcrPengawasanService::parseRupiah('Rp5.000.000'));
    }

    public function test_parser_luas_lahan_split(): void
    {
        $result = OcrPengawasanService::parseUnit('192 M2');
        $this->assertEquals(192, $result['value']);
        $this->assertEquals('M2', $result['unit']);

        $result2 = OcrPengawasanService::parseUnit('120 M²');
        $this->assertEquals(120, $result2['value']);
        $this->assertEquals('M²', $result2['unit']);
    }

    public function test_parser_tenaga_kerja_normalization(): void
    {
        $this->assertEquals(5, OcrPengawasanService::parseInteger('5 Orang'));
        $this->assertEquals(10, OcrPengawasanService::parseInteger('10 orang'));
        $this->assertEquals(3, OcrPengawasanService::parseInteger('3'));
    }

    public function test_parser_date_normalization(): void
    {
        $this->assertEquals('2026-08-13', OcrPengawasanService::parseDate('13/08/2026'));
        $this->assertEquals('2026-01-01', OcrPengawasanService::parseDate('01/01/2026'));
        $this->assertNull(OcrPengawasanService::parseDate(''));
        $this->assertNull(OcrPengawasanService::parseDate('-'));
    }

    public function test_parser_dash_as_null(): void
    {
        $ocrText = <<<'OCR'
Kewenangan:
-
OCR;

        $results = $this->ocrService->parse($ocrText, 'test.pdf');
        $this->assertNull($results[0]['kewenangan']);
    }

    public function test_parser_tenggat_kosong(): void
    {
        $ocrText = <<<'OCR'
Tenggat Waktu Pemenuhan Kewajiban/Tanggapan:
Jenis Sanksi:
Pencabutan
OCR;

        $results = $this->ocrService->parse($ocrText, 'test.pdf');
        $this->assertNull($results[0]['tenggat_waktu_pemenuhan_kewajiban_tanggapan']);
    }

    public function test_parser_status_perizinan(): void
    {
        $ocrText = <<<'OCR'
Status:
Terbit Otomatis
OCR;

        $results = $this->ocrService->parse($ocrText, 'test.pdf');
        $this->assertEquals('Terbit Otomatis', $results[0]['status_perizinan']);
    }

    public function test_parser_status_sanksi(): void
    {
        $ocrText = <<<'OCR'
Status Sanksi:
Terkirim ke Pelaku Usaha
OCR;

        $results = $this->ocrService->parse($ocrText, 'test.pdf');
        $this->assertEquals('Terkirim ke Pelaku Usaha', $results[0]['status_sanksi']);
    }

    // ====================================================================
    // SIMPAN HASIL
    // ====================================================================

    public function test_simpan_hasil_from_ocr(): void
    {
        $ocrText = <<<'OCR'
1

Nama Pelaku Usaha:
CV YAHSYI
Nomor Induk Berusaha (NIB):
0299000951365
Luas Lahan:
192 M2
Tingkat Risiko:
Rendah
OCR;

        $results = $this->ocrService->parse($ocrText, 'test.pdf');

        foreach ($results as $item) {
            Pengawasan::create($item);
        }

        $this->assertDatabaseHas('pengawasan', [
            'nama_pelaku_usaha' => 'CV YAHSYI',
            'nib' => '0299000951365',
            'luas_lahan' => 192,
            'satuan_luas' => 'M2',
            'tingkat_risiko' => 'Rendah',
        ]);
    }

    public function test_simpan_2_data_dari_ocr(): void
    {
        $ocrText = <<<'OCR'
1

Nama Pelaku Usaha:
CV YAHSYI
Nomor Induk Berusaha (NIB):
0299000951365

2

Nama Pelaku Usaha:
PT KIMIA FARMA APOTEK
Nomor Induk Berusaha (NIB):
8120004902508
OCR;

        $results = $this->ocrService->parse($ocrText, 'test.pdf');

        foreach ($results as $item) {
            Pengawasan::create($item);
        }

        $this->assertEquals(2, Pengawasan::count());
        $this->assertDatabaseHas('pengawasan', ['nib' => '0299000951365']);
        $this->assertDatabaseHas('pengawasan', ['nib' => '8120004902508']);
    }

    public function test_nib_tidak_berubah(): void
    {
        $ocrText = <<<'OCR'
Nama Pelaku Usaha:
CV Test
Nomor Induk Berusaha (NIB):
0299000951365
OCR;

        $results = $this->ocrService->parse($ocrText, 'test.pdf');
        Pengawasan::create($results[0]);

        $db = Pengawasan::where('nib', '0299000951365')->first();
        $this->assertStringStartsWith('0', $db->nib);
        $this->assertEquals(13, strlen($db->nib));
    }

    public function test_multiline_anchor_kementerian_lembaga(): void
    {
        $ocrText = <<<'OCR'
Nama Pelaku Usaha:
CV Test
Kementerian/
Lembaga:
Kementerian Perhubungan
OCR;

        $results = $this->ocrService->parse($ocrText, 'test.pdf');
        $this->assertEquals('Kementerian Perhubungan', $results[0]['kementerian_lembaga']);
    }

    public function test_multiline_value_alamat(): void
    {
        $ocrText = <<<'OCR'
Nama Pelaku Usaha:
CV Test
Alamat:
Jln. Geumpang -
Tutut
OCR;

        $results = $this->ocrService->parse($ocrText, 'test.pdf');
        $this->assertEquals('Jln. Geumpang - Tutut', $results[0]['alamat']);
    }

    public function test_multiline_value_alamat_long(): void
    {
        $ocrText = <<<'OCR'
Nama Pelaku Usaha:
CV Test
Alamat:
Jln. Banda-Aceh-
Medan, Gampong
Dayah Teungoh-
Tijue, Kec. Pidie,
Kab.Pidie
OCR;

        $results = $this->ocrService->parse($ocrText, 'test.pdf');
        $this->assertStringContainsString('Banda-Aceh', $results[0]['alamat']);
        $this->assertStringContainsString('Medan', $results[0]['alamat']);
        $this->assertStringContainsString('Gampong', $results[0]['alamat']);
        $this->assertStringContainsString('Kab.Pidie', $results[0]['alamat']);
    }

    public function test_multiline_anchor_and_value(): void
    {
        $ocrText = <<<'OCR'
Nama Pelaku Usaha:
CV Test
Kementerian/
Lembaga:
Kementerian
Perindustrian
OCR;

        $results = $this->ocrService->parse($ocrText, 'test.pdf');
        $this->assertEquals('Kementerian Perindustrian', $results[0]['kementerian_lembaga']);
    }

    public function test_multiline_nama_pelaku_usaha(): void
    {
        $ocrText = <<<'OCR'
Nama Pelaku
Usaha:
CV YAHSYI
OCR;

        $results = $this->ocrService->parse($ocrText, 'test.pdf');
        $this->assertEquals('CV YAHSYI', $results[0]['nama_pelaku_usaha']);
    }

    public function test_real_ocr_multi_record_structural(): void
    {
        $ocrText = <<<'OCR'
Nomor Sanksi:
SNK-202608131652033202826
Tanggal Pengenaan Sanksi:
13/08/2026
Jenis Sanksi:
Pencabutan
Status Sanksi:
Terkirim ke Pelaku Usaha
Sumber Sanksi:
Pengenaan Sanksi Administratif oleh Kementerian Investasi/BKPM atas kewajiban LKPM

Nomor Sanksi:
SNK-202608131605243997599
Tanggal Pengenaan Sanksi:
13/08/2026
Jenis Sanksi:
Pencabutan
Status Sanksi:
Terkirim ke Pelaku Usaha
Sumber Sanksi:
Pengenaan Sanksi Administratif oleh Kementerian Investasi/BKPM atas kewajiban LKPM

Nama Pelaku
Usaha:
CV YAHSYI
Nomor Induk
Berusaha (NIB):
0299000951365
Jenis Penanaman
Modal:
Penanaman Modal Dalam Negeri (PMDN)
Skala Usaha:
Usaha Mikro
Sumber Data:
OSS RBA

Nama Pelaku
Usaha:
PT KIMIA FARMA APOTEK
Nomor Induk
Berusaha (NIB):
8120004902508
Jenis Penanaman
Modal:
Penanaman Modal Asing (PMA)
Skala Usaha:
Usaha Mikro
Sumber Data:
OSS RBA

Jenis Perizinan:
Sertifikat Standar
Nomor Perizinan:
0299000951365
Status:
Terbit Otomatis
Kementerian/
Lembaga:
Kementerian Perindustrian
Kewenangan:

Jenis Perizinan:
Sertifikat Standar
Nomor Perizinan:
8120004902508
Status:
Izin terbit/SS terverifikasi
Kementerian/
Lembaga:
Kementerian Kesehatan
Kewenangan:
Menteri/Kepala Badan

Alamat:
Jln. Geumpang -
Tutut
Kelurahan:
Keune
Kecamatan:
Geumpang
Kab/Kota:
Kab. Pidie
Provinsi:
Aceh

Alamat:
Jl. Banda-Aceh-Medan
Kelurahan:
Dayah Teungoh
Kecamatan:
Pidie
Kab/Kota:
Kab. Pidie
Provinsi:
Aceh

Nomor Kode Proyek:
202205-1014-3945-4036-343
Luas Lahan:
192 M2
Jumlah Tenaga Kerja:
5 Orang
Rencana Investasi:
Rp 500.000.000
Tingkat Risiko:
Rendah

Nomor Kode Proyek:
202206-0218-0046-2443-330
Luas Lahan:
120 M2
Jumlah Tenaga Kerja:
3 Orang
Rencana Investasi:
Rp 620.000.000
Tingkat Risiko:
Tinggi
OCR;

        $results = $this->ocrService->parse($ocrText, 'test.pdf');

        $this->assertCount(2, $results);

        $r1 = $results[0];
        $this->assertEquals('CV YAHSYI', $r1['nama_pelaku_usaha']);
        $this->assertEquals('0299000951365', $r1['nib']);
        $this->assertEquals('SNK-202608131652033202826', $r1['nomor_sanksi']);
        $this->assertEquals('Kementerian Perindustrian', $r1['kementerian_lembaga']);
        $this->assertEquals('Jln. Geumpang - Tutut', $r1['alamat']);
        $this->assertEquals(192, $r1['luas_lahan']);
        $this->assertEquals('M2', $r1['satuan_luas']);
        $this->assertEquals(5, $r1['jumlah_tenaga_kerja']);
        $this->assertEquals(500000000, $r1['rencana_investasi']);

        $r2 = $results[1];
        $this->assertEquals('PT KIMIA FARMA APOTEK', $r2['nama_pelaku_usaha']);
        $this->assertEquals('8120004902508', $r2['nib']);
        $this->assertEquals('SNK-202608131605243997599', $r2['nomor_sanksi']);
        $this->assertEquals('Kementerian Kesehatan', $r2['kementerian_lembaga']);
        $this->assertEquals('Menteri/Kepala Badan', $r2['kewenangan']);
        $this->assertEquals('Jl. Banda-Aceh-Medan', $r2['alamat']);
        $this->assertEquals(120, $r2['luas_lahan']);
        $this->assertEquals(3, $r2['jumlah_tenaga_kerja']);
        $this->assertEquals(620000000, $r2['rencana_investasi']);
    }

    public function test_real_oss_three_pages_full_log(): void
    {
        $ocrText = <<<'OCR'
OSS

1 of 3

PB

Sumber data:

O Non Pencabutan © Pencabutan

No
Vv 1
Vv 2

OSS

KEMENTERIAN INVESTASI DAN HILIRISASI/BKPM

Data Sanksi

Nomor Sanksi:
SNK-202608131652033202826

Tanggal Pengenaan Sanksi:
13/08/2026

Tenggat Waktu Pemenuhan
Kewajiban/Tanggapan:

Jenis Sanksi:
Pencabutan

Masa Berlaku:

Status Sanksi:
Terkirim ke Pelaku Usaha

Sumber Sanksi:

Pengenaan Sanksi Administratif
oleh Kementerian Investasi/BKPM
atas kewajiban LKPM

Nomor Sanksi:
SNK-202608131605243997599

Tanggal Pengenaan Sanksi:
13/08/2026

Tenggat Waktu Pemenuhan
Kewajiban/Tanggapan:

Jenis Sanksi:
Pencabutan

Masa Berlaku:

Status Sanksi:
Terkirim ke Pelaku Usaha

Sumber Sanksi:

Pengenaan Sanksi Administratif
oleh Kementerian Investasi/BKPM
atas kewajiban LKPM

BERANDA

PELAPORAN v

https://pemrosesan.oss.go.id/#/kegiatan-usaha-sanksi-verif/daftar-list

Nurmarita A 0

PENGADUAN v PENCABUTAN vy SANKSI vw PELACAKAN vw PEN:
Daftar List Sanksi
Cari Berdasarkan v Pencarian

Nama Pelaku
Usaha/NIB/Jenis
PM/Skala Usaha

Nama Pelaku
Usaha
CV YAHSYI

Nomor Induk
Berusaha (NIB):
0299000951365

Jenis Penanaman
Modal:
Penanaman Modal
Dalam Negeri
(PMDN)

Skala Usaha:
Usaha Mikro

Sumber Data:
OSS RBA

Nama Pelaku
Usaha

PT KIMIA FARMA
APOTEK

Nomor Induk
Berusaha (NIB):
8120004902508

Jenis Penanaman
Modal:
Penanaman Modal
Asing (PMA)

Skala Usaha:
Usaha Mikro

Sumber Data:
OSS RBA

Perizinan Berusaha

Jenis Perizinan:
Sertifikat Standar

Lokasi Usaha

Alamat:
Jln. Geumpang -
Tutut

Nomor Perizinan:

0299000951365

Status:
Terbit Otomatis

Kementerian/
Lembaga:
Kementerian
Perindustrian

Kewenangan:

Jenis Perizinan:
Sertifikat Standar

Nomor Perizinan:

Status:
Izin terbit/SS
terverifikasi

Kementerian/
Lembaga:
Kementerian
Kesehatan

Kewenangan:
Menteri/Kepala
Badan

Kelurahan:
Keune

Kecamatan:
Geumpang

Kab/Kota:
Kab. Pidie

Provinsi:
Aceh

Alamat:

Jln. Banda-Aceh-
Medan, Gampong
Dayah Teungoh-
Tijue, Kec. Pidie,
Kab.Pidie

Kelurahan:
Dayah Teungoh

Kecamatan:
Pidie
Kab/Kota:
Kab. Pidie

Provinsi:
Aceh

Data Usaha

Nomor Kode Proyek:
202205-1014-3945-4036-343

Luas Lahan:
192 M2

Jumlah Tenaga Kerja:
5 Orang

Rencana Investasi:
Rp 500.000.000

Tingkat Risiko:
Rendah

Nomor Kode Proyek:
202206-0218-0046-2443-330

Luas Lahan:
120 M2

Jumlah Tenaga Kerja:
3 Orang

Rencana Investasi:
Rp 620.000.000

Tingkat Risiko:
Tinggi

15/09/2026, 15:56

OSS

2 of 3

KEMENTERIAN INVESTASI DAN HILIRISASI/BKPM
Tanggal Pengenaan Sanksi:

13/08/2026

Tenggat Waktu Pemenuhan
Kewajiban/Tanggapan:

Jenis Sanksi:
Pencabutan

Masa Berlaku:

Status Sanksi:
Terkirim ke Pelaku Usaha

Sumber Sanksi:

Pengenaan Sanksi Administratif
oleh Kementerian Investasi/BKPM
atas kewajiban LKPM

Nomor Sanksi:
SNK-202608131327187992705

Tanggal Pengenaan Sanksi:
13/08/2026

Tenggat Waktu Pemenuhan
Kewajiban/Tanggapan:

Jenis Sanksi:
Pencabutan

Masa Berlaku:

Status Sanksi:
Terkirim ke Pelaku Usaha

Sumber Sanksi:

Pengenaan Sanksi Administratif
oleh Kementerian Investasi/BKPM
atas kewajiban LKPM

Nomor Sanksi:
SNK-202608131219048046848

Tanggal Pengenaan Sanksi:
13/08/2026

Tenggat Waktu Pemenuhan
Kewajiban/Tanggapan:

Jenis Sanksi:
Pencabutan

Masa Berlaku:

Status Sanksi:
Terkirim ke Pelaku Usaha

Sumber Sanksi:

Pengenaan Sanksi Administratif
oleh Kementerian Investasi/BKPM
atas kewajiban LKPM

PELAPORAN v

Nomor Induk
Berusaha (NIB):
0299000951365

Jenis Penanaman
Modal:
Penanaman Modal
Dalam Negeri
(PMDN)

Skala Usaha:
Usaha Mikro

Sumber Data:
OSS RBA

Nama Pelaku
Usaha
CV YAHSYI

Nomor Induk
Berusaha (NIB):
0299000951365

Jenis Penanaman
Modal:
Penanaman Modal
Dalam Negeri
(PMDN)

Skala Usaha:
Usaha Mikro

Sumber Data:
OSS RBA

Nama Pelaku
Usaha

PT KURNIA
ANUGERAH
PUSAKA

Nomor Induk
Berusaha (NIB):
1407220057038

Jenis Penanaman
Modal:
Penanaman Modal
Dalam Negeri
(PMDN)

Skala Usaha:
Usaha Menengah

Sumber Data:
OSS RBA

PENGADUAN v

Nomor Perizinan:
0299000951365

Status:
Terbit Otomatis

Kementerian/
Lembaga:
Kementerian
Pertanian

Kewenangan:

Jenis Perizinan:
Sertifikat Standar

Nomor Perizinan:
0299000951365

Status:
Terbit Otomatis

Kementerian/
Lembaga:
Kementerian
Perindustrian

Kewenangan:

Jenis Perizinan:
Sertifikat Standar

Nomor Perizinan:
14072200570380009

Status:
Ditolak

Kementerian/
Lembaga:
Kementerian
Perhubungan

Kewenangan:
Gubernur

PENCABUTAN v

https://pemrosesan.oss.go.id/#/kegiatan-usaha-sanksi-verif/daftar-list

Kelurahan:
Keune

Kecamatan:
Geumpang

Kab/Kota:
Kab. Pidie

Provinsi:
Aceh

Alamat:
Jln. Geumpang -
Tutut

Kelurahan:
Keune

Kecamatan:
Geumpang

Kab/Kota:
Kab. Pidie

Provinsi:
Aceh

Alamat:
Jl. Perdagangan

Kelurahan:
Kramat Dalam

Kecamatan:
Kota Sigli

Kab/Kota:
Kab. Pidie

Provinsi:
Aceh

Item Per Halaman

SANKSI v

10

Nurmarita A 0

PELACAKAN v PEN:
Luas Lahan:
192 M2

Jumlah Tenaga Kerja:
5 Orang

Rencana Investasi:
Rp 500.000.000

Tingkat Risiko:
Rendah

Nomor Kode Proyek:
202205-1014-3604-8433-741

Luas Lahan:
192 M2

Jumlah Tenaga Kerja:
5 Orang

Rencana Investasi:
Rp 500.000.000

Tingkat Risiko:
Rendah

Nomor Kode Proyek:
202207-1413-4857-7847-196

Luas Lahan:
200 M2

Jumlah Tenaga Kerja:
1 Orang

Rencana Investasi:
Rp 5.005.000.000

Tingkat Risiko:
Menengah Tinggi

15/09/2026, 15:56

OSS

KEMENTERIAN INVESTASI DAN HILIRISASI/BKPM
NAS ASAMA oto1ko |

© 2021 Lembaga OSS - Kementerian
Investasi dan Hilirisasi/BKPM

3 of 3

PELAPORAN v

Ikuti Lembaga OSS di Media Sosial:

fo v

PENGADUAN v

https://pemrosesan.oss.go.id/#/kegiatan-usaha-sanksi-verif/daftar-list

PENCABUTAN v

SANKSI v

Nurmarita

PELACAKAN v

KS

PEN:

15/09/2026, 15:56
OCR;

        $results = $this->ocrService->parse($ocrText, '1789577043_5 (1).pdf');

        // 5 baris: 2 halaman-1 + 3 halaman-2 (termasuk 1 baris yatim yang
        // delimiternya terpotong page-break). Satu NIB boleh muncul di
        // beberapa baris karena unik = nomor_sanksi.
        $this->assertCount(5, $results);

        $byNomor = [];
        $orphans = [];
        foreach ($results as $row) {
            if ($row['nomor_sanksi'] !== null) {
                $byNomor[$row['nomor_sanksi']] = $row;
            } else {
                $orphans[] = $row;
            }
        }
        $this->assertCount(4, $byNomor);
        $this->assertCount(1, $orphans);

        // CV YAHSYI — baris pertama tiap section halaman 1
        $r1 = $byNomor['SNK-202608131652033202826'];
        $this->assertEquals('CV YAHSYI', $r1['nama_pelaku_usaha']);
        $this->assertEquals('SNK-202608131652033202826', $r1['nomor_sanksi']);
        $this->assertEquals('2026-08-13', $r1['tanggal_pengenaan_sanksi']);
        $this->assertNull($r1['tenggat_waktu_pemenuhan_kewajiban_tanggapan']);
        $this->assertEquals('Pencabutan', $r1['jenis_sanksi']);
        $this->assertNull($r1['masa_berlaku']);
        $this->assertEquals('Terkirim ke Pelaku Usaha', $r1['status_sanksi']);
        $this->assertStringContainsString('LKPM', $r1['sumber_sanksi']);
        $this->assertStringNotContainsString('BERANDA', $r1['sumber_sanksi']);
        $this->assertEquals('Penanaman Modal Dalam Negeri (PMDN)', $r1['jenis_penanaman_modal']);
        $this->assertEquals('Usaha Mikro', $r1['skala_usaha']);
        $this->assertEquals('OSS RBA', $r1['sumber_data']);
        $this->assertEquals('Sertifikat Standar', $r1['jenis_perizinan']);
        $this->assertEquals('0299000951365', $r1['nomor_perizinan']);
        $this->assertEquals('Terbit Otomatis', $r1['status_perizinan']);
        $this->assertEquals('Kementerian Perindustrian', $r1['kementerian_lembaga']);
        $this->assertNull($r1['kewenangan']);
        $this->assertEquals('Jln. Geumpang - Tutut', $r1['alamat']);
        $this->assertEquals('Keune', $r1['kelurahan']);
        $this->assertEquals('Geumpang', $r1['kecamatan']);
        $this->assertEquals('Kab. Pidie', $r1['kab_kota']);
        $this->assertEquals('Aceh', $r1['provinsi']);
        $this->assertEquals('202205-1014-3945-4036-343', $r1['nomor_kode_proyek']);
        $this->assertEquals(192, $r1['luas_lahan']);
        $this->assertEquals('M2', $r1['satuan_luas']);
        $this->assertEquals(5, $r1['jumlah_tenaga_kerja']);
        $this->assertEquals(500000000, $r1['rencana_investasi']);
        $this->assertEquals('Rendah', $r1['tingkat_risiko']);

        // PT KIMIA FARMA APOTEK — nama & alamat multi-baris
        $r2 = $byNomor['SNK-202608131605243997599'];
        $this->assertEquals('PT KIMIA FARMA APOTEK', $r2['nama_pelaku_usaha']);
        $this->assertEquals('SNK-202608131605243997599', $r2['nomor_sanksi']);
        $this->assertEquals('Penanaman Modal Asing (PMA)', $r2['jenis_penanaman_modal']);
        $this->assertNull($r2['nomor_perizinan']);
        $this->assertEquals('Izin terbit/SS terverifikasi', $r2['status_perizinan']);
        $this->assertEquals('Kementerian Kesehatan', $r2['kementerian_lembaga']);
        $this->assertEquals('Menteri/Kepala Badan', $r2['kewenangan']);
        $this->assertStringContainsString('Banda-Aceh-Medan', $r2['alamat']);
        $this->assertStringContainsString('Teungoh-Tijue', $r2['alamat']);
        $this->assertEquals('Dayah Teungoh', $r2['kelurahan']);
        $this->assertEquals('Pidie', $r2['kecamatan']);
        $this->assertEquals('202206-0218-0046-2443-330', $r2['nomor_kode_proyek']);
        $this->assertEquals(120, $r2['luas_lahan']);
        $this->assertEquals(3, $r2['jumlah_tenaga_kerja']);
        $this->assertEquals(620000000, $r2['rencana_investasi']);
        $this->assertEquals('Tinggi', $r2['tingkat_risiko']);

        // Baris yatim halaman 2 (delimiter terpotong): nomor/address/kode null,
        // tapi tanggal, perizinan, lokasi, dan proyeknya tetap terbaca.
        // Nama di-backfill dari NIB yang sama.
        $ro = $orphans[0];
        $this->assertNull($ro['nomor_sanksi']);
        $this->assertEquals('CV YAHSYI', $ro['nama_pelaku_usaha']);
        $this->assertEquals('0299000951365', $ro['nib']);
        $this->assertEquals('2026-08-13', $ro['tanggal_pengenaan_sanksi']);
        $this->assertEquals('Pencabutan', $ro['jenis_sanksi']);
        $this->assertEquals('Terkirim ke Pelaku Usaha', $ro['status_sanksi']);
        $this->assertStringContainsString('LKPM', $ro['sumber_sanksi']);
        $this->assertNull($ro['jenis_perizinan']);
        $this->assertEquals('0299000951365', $ro['nomor_perizinan']);
        $this->assertEquals('Terbit Otomatis', $ro['status_perizinan']);
        $this->assertEquals('Kementerian Pertanian', $ro['kementerian_lembaga']);
        $this->assertNull($ro['alamat']);
        $this->assertEquals('Keune', $ro['kelurahan']);
        $this->assertEquals('Geumpang', $ro['kecamatan']);
        $this->assertNull($ro['nomor_kode_proyek']);
        $this->assertEquals(192, $ro['luas_lahan']);
        $this->assertEquals(5, $ro['jumlah_tenaga_kerja']);
        $this->assertEquals(500000000, $ro['rencana_investasi']);
        $this->assertEquals('Rendah', $ro['tingkat_risiko']);

        // CV YAHSYI sanksi kedua (halaman 2)
        $r4 = $byNomor['SNK-202608131327187992705'];
        $this->assertEquals('CV YAHSYI', $r4['nama_pelaku_usaha']);
        $this->assertEquals('0299000951365', $r4['nib']);
        $this->assertEquals('Sertifikat Standar', $r4['jenis_perizinan']);
        $this->assertEquals('0299000951365', $r4['nomor_perizinan']);
        $this->assertEquals('Kementerian Perindustrian', $r4['kementerian_lembaga']);
        $this->assertEquals('Jln. Geumpang - Tutut', $r4['alamat']);
        $this->assertEquals('202205-1014-3604-8433-741', $r4['nomor_kode_proyek']);
        $this->assertEquals(192, $r4['luas_lahan']);
        $this->assertEquals(500000000, $r4['rencana_investasi']);
        $this->assertEquals('Rendah', $r4['tingkat_risiko']);

        // PT KURNIA ANUGERAH PUSAKA — nama 3 baris, nomor perizinan bersuffix
        $r3 = $byNomor['SNK-202608131219048046848'];
        $this->assertEquals('PT KURNIA ANUGERAH PUSAKA', $r3['nama_pelaku_usaha']);
        $this->assertEquals('SNK-202608131219048046848', $r3['nomor_sanksi']);
        $this->assertEquals('Usaha Menengah', $r3['skala_usaha']);
        $this->assertEquals('14072200570380009', $r3['nomor_perizinan']);
        $this->assertEquals('Ditolak', $r3['status_perizinan']);
        $this->assertEquals('Kementerian Perhubungan', $r3['kementerian_lembaga']);
        $this->assertEquals('Gubernur', $r3['kewenangan']);
        $this->assertEquals('Jl. Perdagangan', $r3['alamat']);
        $this->assertEquals('Kramat Dalam', $r3['kelurahan']);
        $this->assertEquals('Kota Sigli', $r3['kecamatan']);
        $this->assertEquals('202207-1413-4857-7847-196', $r3['nomor_kode_proyek']);
        $this->assertEquals(200, $r3['luas_lahan']);
        $this->assertEquals(1, $r3['jumlah_tenaga_kerja']);
        $this->assertEquals(5005000000, $r3['rencana_investasi']);
        $this->assertEquals('Menengah Tinggi', $r3['tingkat_risiko']);
    }

    public function test_import_simpan_upsert_by_nomor_sanksi(): void
    {
        $ocrText = <<<'OCR'
Nomor Sanksi:
SNK-0001
Tanggal Pengenaan Sanksi:
13/08/2026
Jenis Sanksi:
Pencabutan
Status Sanksi:
Terkirim ke Pelaku Usaha
Sumber Sanksi:
Pengenaan Sanksi Administratif oleh Kementerian Investasi/BKPM atas kewajiban LKPM

Nama Pelaku Usaha:
CV YAHSYI
Nomor Induk Berusaha (NIB):
0299000951365
OCR;

        $results = $this->ocrService->parse($ocrText, 'upsert.pdf');
        $this->assertCount(1, $results);

        // Simpan dua kali: baris sama (unik nomor_sanksi) tidak dobel.
        $this->post('/pengawasan/import/simpan', ['data' => $results])->assertRedirect();
        $this->assertEquals(1, Pengawasan::count());

        $results[0]['status_sanksi'] = 'Sudah Ditindaklanjuti';
        $this->post('/pengawasan/import/simpan', ['data' => $results])->assertRedirect();
        $this->assertEquals(1, Pengawasan::count());
        $this->assertDatabaseHas('pengawasan', [
            'nomor_sanksi' => 'SNK-0001',
            'status_sanksi' => 'Sudah Ditindaklanjuti',
        ]);

        // Nomor sanksi lain tetap membuat baris baru.
        $results[0]['nomor_sanksi'] = 'SNK-0002';
        $this->post('/pengawasan/import/simpan', ['data' => $results])->assertRedirect();
        $this->assertEquals(2, Pengawasan::count());
        $this->assertDatabaseHas('pengawasan', ['nomor_sanksi' => 'SNK-0002']);
    }

    public function test_import_simpan_orphan_tanpa_nomor_tidak_dobel(): void
    {
        $payload = ['data' => [[
            'nama_pelaku_usaha' => 'CV YAHSYI',
            'nib' => '0299000951365',
            'jenis_sanksi' => 'Pencabutan',
        ]]];

        $this->post('/pengawasan/import/simpan', $payload)->assertRedirect();
        $this->post('/pengawasan/import/simpan', $payload)->assertRedirect();

        $this->assertEquals(1, Pengawasan::where('nib', '0299000951365')->whereNull('nomor_sanksi')->count());
    }
}
