<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengawasan', function (Blueprint $table) {
            $table->id();

            // DATA SANKSI
            $table->string('nomor_sanksi')->nullable()->index();
            $table->date('tanggal_pengenaan_sanksi')->nullable();
            $table->date('tenggat_waktu_pemenuhan_kewajiban_tanggapan')->nullable();
            $table->string('jenis_sanksi')->nullable();
            $table->string('masa_berlaku')->nullable();
            $table->string('status_sanksi')->nullable();
            $table->text('sumber_sanksi')->nullable();

            // NAMA PELAKU USAHA/NIB/JENIS PM/SKALA USAHA
            $table->string('nama_pelaku_usaha')->nullable();
            $table->string('nib', 50)->nullable()->index();
            $table->string('jenis_penanaman_modal')->nullable();
            $table->string('skala_usaha')->nullable();
            $table->string('sumber_data')->nullable();

            // PERIZINAN BERUSAHA
            $table->string('jenis_perizinan')->nullable();
            $table->string('nomor_perizinan')->nullable();
            $table->string('status_perizinan')->nullable();
            $table->string('kementerian_lembaga')->nullable();
            $table->string('kewenangan')->nullable();

            // LOKASI USAHA
            $table->text('alamat')->nullable();
            $table->string('kelurahan')->nullable();
            $table->string('kecamatan')->nullable();
            $table->string('kab_kota')->nullable();
            $table->string('provinsi')->nullable();

            // DATA USAHA
            $table->string('nomor_kode_proyek')->nullable()->index();
            $table->decimal('luas_lahan', 18, 2)->nullable();
            $table->string('satuan_luas')->nullable();
            $table->unsignedInteger('jumlah_tenaga_kerja')->nullable();
            $table->decimal('rencana_investasi', 20, 2)->nullable();
            $table->string('tingkat_risiko')->nullable();

            // SUMBER IMPORT
            $table->string('sumber_file')->nullable();
            $table->unsignedInteger('sumber_halaman')->nullable();
            $table->string('sumber_metode')->nullable();
            $table->decimal('skor_ocr', 5, 2)->nullable();

            $table->timestamps();

            $table->index('nama_pelaku_usaha');
            $table->index('tingkat_risiko');
            $table->index('status_sanksi');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengawasan');
    }
};
