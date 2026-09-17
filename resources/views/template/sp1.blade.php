<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Surat Teguran I - DPMPTSP Kabupaten Pidie</title>
    <style>
        @page {
            /* Ukuran standar F4 / Folio (215mm x 330mm) */
            size: 215mm 330mm;
            /* Margin: top 0, right 20mm, bottom 15mm, left 20mm */
            margin: 0 20mm 15mm 20mm;
        }

        body {
            font-family: "Times New Roman", Times, serif;
            font-size: 11pt;
            line-height: 1.25;
            color: #000;
            margin: 0;
            padding: 0;
        }

        *, *::before, *::after {
            box-sizing: border-box;
        }

        /* Kop Surat Layout (Rapat & Tebal) */
        .kop-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0px; /* Menghilangkan jarak bawah tabel kop */
        }

        .kop-logo {
            width: 80px;
            text-align: center;
            vertical-align: middle;
            padding-right: 10px;
        }

        .kop-text {
            text-align: center;
            vertical-align: middle;
            line-height: 1.1; /* Membuat jarak antar baris teks lebih rapat */
        }

        .kop-text .header-1 {
            font-size: 18pt;
            font-weight: 700;
            letter-spacing: 0.5px;
            margin: 0;
        }

        .kop-text .header-2 {
            font-size: 16pt;
            font-weight: 900; /* Dibuat lebih tebal secara maksimal */
            letter-spacing: 0.5px;
            margin: 1px 0 0 0;
        }

        .kop-text .header-3 {
            font-size: 16pt;
            font-weight: 900; /* Dibuat lebih tebal secara maksimal */
            letter-spacing: 0.5px;
            margin: 1px 0 0 0;
        }

        .kop-text .alamat {
            font-size: 9pt;
            margin-top: 2px;
            font-weight: normal;
            line-height: 1.15;
        }

        /* Garis Pembatas Kop */
        .garis-kop {
            border-top: 3px solid #000;
            border-bottom: 1px solid #000;
            height: 2px;
            margin-top: 4px;
            margin-bottom: 10px; /* Merapatkan jarak dari garis kop ke isi surat */
        }

        /* Metadata & Tujuan */
        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        .meta-table td {
            vertical-align: top;
            padding: 1px 0;
        }

        .left-col {
            width: 58%;
        }

        .right-col {
            width: 42%;
        }

        .inner-meta {
            width: 100%;
            border-collapse: collapse;
        }

        .inner-meta td {
            vertical-align: top;
            padding: 1px 0;
        }

        .label {
            width: 65px;
        }

        .colon {
            width: 15px;
            text-align: center;
        }

        .penerima-list {
            margin: 0;
            padding-left: 0;
            list-style: none;
        }

        .penerima-list li {
            margin-bottom: 1px;
        }

        /* Isi Surat */
        .content {
            text-align: justify;
        }

        .point-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
        }

        .point-table td {
            vertical-align: top;
            padding: 2px 0;
        }

        .point-num {
            width: 24px;
            font-weight: normal;
        }

        .point-text {
            text-align: justify;
            line-height: 1.25;
        }

        .sub-list {
            margin-top: 3px;
            margin-bottom: 3px;
            padding-left: 18px;
        }

        .sub-list li {
            margin-bottom: 2px;
        }

        /* Tanda Tangan */
        .ttd-section {
            width: 100%;
            margin-top: 15px;
            page-break-inside: avoid;
        }

        .ttd-table {
            width: 100%;
            border-collapse: collapse;
        }

        .ttd-table td {
            vertical-align: top;
        }


        .ttd-title {
            font-weight: bold;
            line-height: 1.2;
            margin-bottom: 65px;
        }

        .ttd-right {
            width: 55%;
            margin-left: 60%;
            text-align: left; /* Diubah dari center ke left */
        }

        .ttd-nama {
            font-weight: bold;
            text-decoration: underline;
            margin: 0;
        }

        .ttd-nip {
            margin: 2px 0 0 0;
        }

        /* Footer / Tembusan & Banner */
        .footer-section {
            margin-top: 10px;
            page-break-inside: avoid;
        }

        .tembusan {
            font-size: 10pt;
            margin-bottom: 15px;
        }

        .tembusan ol {
            margin: 2px 0 0 0;
            padding-left: 20px;
        }

        .tembusan li {
            margin-bottom: 1px;
        }

        /* Banner PORA / PON */
        .banner-box {
            border: 2px dashed #000;
            padding: 6px 10px;
            text-align: center;
            width: 80%;
            margin: 0 auto;
            font-weight: bold;
            font-size: 10pt;
            line-height: 1.2;
        }
    </style>
</head>
<body>

   <!-- KOP SURAT -->
    <table class="kop-table">
        <tr>
            <td class="kop-logo">
                <img src="{{ $logoSrc ?? '/logo-pidie.svg' }}" alt="Logo Kabupaten Pidie" style="width: 75px; height: auto;">
            </td>
            <td class="kop-text">
                <div class="header-1">PEMERINTAH KABUPATEN PIDIE</div>
                <div class="header-2">DINAS PENANAMAN MODAL DAN</div>
                <div class="header-3">PELAYANAN TERPADU SATU PINTU</div>
                <div class="alamat">
                    Alamat: Jln. Tgk. Chiek Direubee No.5 Sigli<br>
                    Telp. (0653) 23399, Faxs. (0653) 7829531 Kode Pos 24112
                </div>
            </td>
        </tr>
    </table>

    <div class="garis-kop"></div>

    <!-- METADATA & PERIHAL -->
    <table class="meta-table">
        <tr>
            <td class="left-col">
                <table class="inner-meta">
                    <tr>
                        <td class="label">Nomor</td>
                        <td class="colon">:</td>
                        <td>{{ $meta['nomor'] ?? '005/132/2022' }}</td>
                    </tr>
                    <tr>
                        <td class="label">Sifat</td>
                        <td class="colon">:</td>
                        <td>{{ $meta['sifat'] ?? 'Segera' }}</td>
                    </tr>
                    <tr>
                        <td class="label">Lamp</td>
                        <td class="colon">:</td>
                        <td>{{ $meta['lamp'] ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="label">Perihal</td>
                        <td class="colon">:</td>
                        <td>
                            <b>Teguran I (pertama) Kepada Pelaku Usaha<br>
                            Yang Belum Menyampaikan LKPM<br>
                            </b>
                        </td>
                    </tr>
                </table>
            </td>
            <td class="right-col" style="padding-left: 20px;">
                {{ $meta['tempat'] ?? 'Sigli' }}, {{ $meta['tanggal'] ?? '24 Oktober 2022' }}<br><br>
                Kepada YTH:<br>
                @if(isset($items) && $items->count())
                    <ol style="padding-left: 20px; margin-top: 2px; margin-bottom: 0; list-style-type: decimal;">
                        @foreach($items as $it)
                            <li>{{ $it->nama_pelaku_usaha ?? 'Pelaku Usaha' }}</li>
                        @endforeach
                    </ol>
                @else
                    <ol style="padding-left: 20px; margin-top: 2px; margin-bottom: 0; list-style-type: decimal;">
                    </ol>
                @endif
                <div style="margin-top: 4px; ">Masing-masing</div>
                @if(isset($items) && $items->count() === 1)
                @else
                    <div>tempat</div>
                @endif
            </td>
        </tr>
    </table>

    <!-- ISI SURAT -->
    <div class="content">
        <table class="point-table">
            <tr>
                <td class="point-num">1.</td>
                <td class="point-text">
                    Berdasarkan Peraturan Kementerian Investasi/BKPM No.5 Tahun 2021 Tentang Pedoman dan Tata Cara Pengawasan Perizinan Berusaha Berbasis Resiko. Pada Pasal 32 ayat (1) disebutkan bahwa Pelaku Usaha Wajib menyampaikan Laporan Kegiatan Penanaman Modal (LKPM) untuk setiap bidang usaha dan/atau lokasi.
                </td>
            </tr>
        </table>

        <table class="point-table">
            <tr>
                <td class="point-num">2.</td>
                <td class="point-text">
                    Berkenaan dengan maksud tersebut di atas bagi Perusahaan yang belum melaporkan LKPM secara online melalui <span style="text-decoration: underline; color: #0000ee;">https://lkpmonline.bkpm.go.id</span> atau <span style="text-decoration: underline; color: #0000ee;">https://oss.go.id</span> dengan batas waktu penympaaian, dapat kami sampaikan bahwa Perusahaan Saudara hingga batas waktu yang telah di tentukan tersebut belum menyampaikan LKPM.
                </td>
            </tr>
        </table>

        <table class="point-table">
            <tr>
                <td class="point-num">3.</td>
                <td class="point-text">
                    Mengingat Pentingnya LKPM sebagai sebuah Instrumen krusial bagi Pemerintah untuk mengetahui Perkembangan Realisasi Investasi secara Nasional, maka dengan ini kami memberikan <b>Teguran I (pertama)</b> kepada Perusahaan Saudara dan Kami harapkan tanggapan tertulis dan tindak lanjut dapat di sampaikan ke Kantor DPMPTSP Kabupaten Pidie paling lambat 30 (tiga puluh hari) kerja terhitung sejak tanggal surat Ini. Apabila setelah jangka waktu di maksud tidak ada tanggapan tertulis dan tindak lanjut, maka akan kami lanjutkan ketahap Pemberian Peringatan Tertulis Kedua.
                </td>
            </tr>
        </table>

        <table class="point-table">
            <tr>
                <td class="point-num">4.</td>
                <td class="point-text">
                    Surat Peringatan ini tidak berlaku dan dapat di abaikan apabila Perusahaan Saudara:
                    <ul class="sub-list" style="list-style-type: disc;">
                        <li>Telah Menyampaikan LKPM</li>
                        <li>Telah dicabut (Likuidasi atau Non Likuidasi)</li>
                        <li>Tidak di Wajibkan menyampaikan LKPM, yaitu bagi Pelaku Usaha Mikro dan Perusahaan dengan Bidang Usaha Hulu Migas dan Lembaga Keuangan.</li>
                    </ul>
                </td>
            </tr>
        </table>

        <table class="point-table">
            <tr>
                <td class="point-num">5.</td>
                <td class="point-text">
                    Apabila diperlukan Informasi lebih lanjut, agar dapat menghubungi Bidang Pengendalian Pelaksanaan dan Informasi Penanaman Modal DPMPTSP Kabupaten Pidie.
                </td>
            </tr>
        </table>

        <table class="point-table">
            <tr>
                <td class="point-num">6.</td>
                <td class="point-text">
                    Demikian untuk dimaklumi, atas kerjasamanya kami ucapkan terima kasih.
                </td>
            </tr>
        </table>
    </div>

    <!-- TANDA TANGAN -->
    <div class="ttd-section">
        <div class="ttd-right" style="text-align: left;">
            <div class="ttd-title">
                KEPALA DINAS PENANAMAN MODAL<br>
                DAN PELAYANAN TERPADU SATU PINTU<br>
                KABUPATEN PIDIE
            </div>
            <div class="ttd-nama">SRI RAHAYU, SE</div>
            <div class="ttd-nip">Pembina / IV.a<br>NIP. 19741111 200604 2 002</div>
        </div>
    </div>

    <!-- FOOTER / TEMBUSAN & BANNER -->
    <div class="footer-section">
        <div class="tembusan">
            <b>Tembusan:</b>
            <ol>
                <li>Kementerian Investasi/BKPM Republik Indonesia di Jakarta;</li>
                <li>Bupati Pidie di Sigli</li>
                <li>Pertinggal.</li>
            </ol>
        </div>
        @if(isset($items) && $items->count())
            <div style="margin-top:10px; font-size:8pt; color:#555; border-top:1px dotted #999; padding-top:6px;">
                Dicetak dari data sanksi administratif — {{ $items->count() }} pelaku usaha
                · No {{ $meta['nomor'] ?? '-' }}
                · {{ now()->format('d/m/Y H:i') }}
            </div>
        @endif
    </div>

    @if(!($isPdf ?? false))
    <!-- Toolbar hanya untuk preview HTML, tidak ikut ter-render ke PDF Dompdf -->
    <style media="screen">
        .print-toolbar {
            position: fixed;
            top: 16px;
            right: 16px;
            z-index: 9999;
            display: flex;
            gap: 8px;
        }
        .print-toolbar button, .print-toolbar a {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 10px 16px;
            border-radius: 999px;
            font-family: ui-sans-serif, system-ui, sans-serif;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            border: 1px solid #e5e7eb;
            background: #fff;
            color: #374151;
            box-shadow: 0 4px 12px rgba(0,0,0,0.12);
            cursor: pointer;
        }
        .print-toolbar .primary {
            background: #2563eb;
            color: #fff;
            border-color: #1d4ed8;
        }
        @media print {
            .print-toolbar { display: none !important; }
            @page { size: 215mm 330mm; margin: 0 20mm 15mm 20mm; }
        }
    </style>
    <div class="print-toolbar">
        <button type="button" class="primary" onclick="window.print()">Cetak / Simpan PDF</button>
        <a href="javascript:window.close()">Tutup</a>
    </div>
    @endif

</body>
</html>
