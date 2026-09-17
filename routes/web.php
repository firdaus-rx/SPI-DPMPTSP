<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Pengawasan\ImportPdfController;
use App\Http\Controllers\Pengawasan\PengawasanController;
use App\Http\Controllers\SanksiAdministratif\ImportPdfController as SanksiImportPdfController;
use App\Http\Controllers\SanksiAdministratif\RekapController;
use App\Http\Controllers\SanksiAdministratif\SanksiAdministratifController;
use App\Http\Controllers\SanksiAdministratif\Sp1Controller;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return view('guest.welcome');
})->name('welcome');

// Auth — hanya login, tanpa register
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
});

Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    Route::prefix('pengawasan')->group(function () {
        Route::get('/import', [ImportPdfController::class, 'index'])
            ->name('pengawasan.import');
        Route::post('/import', [ImportPdfController::class, 'store'])
            ->name('pengawasan.import.store');
        Route::post('/import/simpan', [ImportPdfController::class, 'simpanHasil'])
            ->name('pengawasan.import.simpan');
        Route::get('/import/clear', [ImportPdfController::class, 'clearSession'])
            ->name('pengawasan.import.clear');
    });

    Route::resource('/pengawasan', PengawasanController::class);

    // Sanksi Administratif - pola sama seperti pengawasan, key OCR: no, nib, alamat nested, penanaman_modal, skala_usaha
    Route::prefix('sanksi-administratif')->group(function () {
        Route::get('/import', [SanksiImportPdfController::class, 'index'])
            ->name('sanksi-administratif.import');
        Route::post('/import', [SanksiImportPdfController::class, 'store'])
            ->name('sanksi-administratif.import.store');
        Route::post('/import/simpan', [SanksiImportPdfController::class, 'simpanHasil'])
            ->name('sanksi-administratif.import.simpan');
        Route::get('/import/clear', [SanksiImportPdfController::class, 'clearSession'])
            ->name('sanksi-administratif.import.clear');

        // Cetak SP1 per data / massal dari template sp1.blade.php
        Route::get('/sp1/cetak', [Sp1Controller::class, 'show'])
            ->name('sanksi-administratif.sp1.massal');
        Route::get('/{sanksiAdministratif}/sp1', [Sp1Controller::class, 'show'])
            ->name('sanksi-administratif.sp1.show');
        Route::get('/{sanksiAdministratif}/sp1/print', [Sp1Controller::class, 'print'])
            ->name('sanksi-administratif.sp1.print');

        // Rekap tabel No | Pelaku Usaha | NIB | Penanaman Modal | Skala | Lokasi — stream PDF landscape
        Route::get('/rekap/cetak', [RekapController::class, 'rekap'])
            ->name('sanksi-administratif.rekap');
    });

    Route::resource('/sanksi-administratif', SanksiAdministratifController::class)->parameters(['sanksi-administratif' => 'sanksiAdministratif']);
});

Route::get('/phpinfo', function() {
    phpinfo();
});

Route::get('/print', function() {
    return view('template.sp1');
});

