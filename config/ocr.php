<?php

return [
    /*
    |--------------------------------------------------------------------------
    | OCR Driver
    |--------------------------------------------------------------------------
    |
    | Driver yang digunakan untuk OCR. Saat ini hanya mendukung tesseract.
    |
    */

    'driver' => env('LARAVEL_OCR_DRIVER', 'tesseract'),

    /*
    |--------------------------------------------------------------------------
    | Tesseract Configuration
    |--------------------------------------------------------------------------
    |
    | Konfigurasi Tesseract OCR untuk ekstraksi teks dari gambar/PDF.
    |
    */

    'tesseract' => [
        'binary' => env('TESSERACT_BINARY', 'C:/Program Files/Tesseract-OCR/tesseract.exe'),
        'language' => env('TESSERACT_LANGUAGE', 'ind+eng'),
        'timeout' => env('TESSERACT_TIMEOUT', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Poppler Configuration
    |--------------------------------------------------------------------------
    |
    | Konfigurasi Poppler (pdftoppm) untuk konversi PDF ke gambar.
    | Digunakan karena Imagick extension tidak tersedia.
    |
    */

    'poppler' => [
        'binary' => env('POPPLER_BINARY', 'C:/Users/ASUS/AppData/Local/Microsoft/WinGet/Packages/oschwartz10612.Poppler_Microsoft.Winget.Source_8wekyb3d8bbwe/poppler-25.07.0/Library/bin/pdftoppm.exe'),
        'dpi' => 300,
    ],
];
