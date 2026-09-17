<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sanksi_administratif', function (Blueprint $table) {
            $table->id();

            // URUTAN OCR (key "no" dari JSON)
            $table->unsignedInteger('no')->nullable()->comment('Urutan baris dari OCR');

            // PELAKU USAHA - mapping 1:1 key OCR: nama_pelaku_usaha, nib, penanaman_modal, skala_usaha
            $table->string('nama_pelaku_usaha')->nullable()->index();
            $table->string('nib', 50)->nullable()->index()->comment('Simpan sebagai string agar leading 0 tidak hilang: 9120300950831');
            $table->string('jenis_penanaman_modal')->nullable()->comment('OCR key: penanaman_modal e.g. Penanaman Modal Dalam Negeri (PMDN)');
            $table->string('skala_usaha')->nullable();

            // ALAMAT NESTED OCR: alamat { jalan, kelurahan, kecamatan, kabupaten_kota, provinsi }
            // Di tabel pengawasan: alamat = jalan, kab_kota = kabupaten_kota
            $table->text('alamat')->nullable()->comment('OCR: alamat.jalan');
            $table->string('kelurahan')->nullable();
            $table->string('kecamatan')->nullable();
            $table->string('kab_kota')->nullable()->comment('OCR: alamat.kabupaten_kota');
            $table->string('provinsi')->nullable();

            // TRACKING FILE IMPORT - contoh: 2.Rancangan sistem sanksi administratif terintegrasi dan otomatis (peringatan tertulis dengan.pdf
            $table->string('sumber_file')->nullable();
            $table->unsignedInteger('sumber_halaman')->nullable();
            $table->string('sumber_metode')->nullable()->comment('OCR / MANUAL');
            $table->decimal('skor_ocr', 5, 2)->nullable();

            $table->timestamps();

            $table->index(['kab_kota', 'kecamatan']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sanksi_administratif');
    }
};
