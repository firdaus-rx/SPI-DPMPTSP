<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rekap Usulan Pencabutan Perizinan Berusaha — DPMPTSP Kabupaten Pidie</title>
    <style>
        @page { size: 330mm 215mm landscape; margin: 12mm 14mm 12mm 14mm; }
        * { box-sizing: border-box; }
        body { font-family: "Times New Roman", Times, serif; font-size: 8.5pt; line-height: 1.25; color:#000; margin:0; padding:0; }
        .kop { width:100%; border-collapse:collapse; }
        .kop td { vertical-align: middle; }
        .kop-logo { width: 58px; text-align:center; padding-right:8px; }
        .kop-text { text-align:center; line-height:1.05; }
        .kop-text .h1 { font-size:13pt; font-weight:700; margin:0; letter-spacing:0.3px; }
        .kop-text .h2 { font-size:11pt; font-weight:800; margin:1px 0 0 0; }
        .kop-text .addr { font-size:7pt; margin-top:2px; line-height:1.15; }
        .garis { border-top:2.5px solid #000; border-bottom:1px solid #000; height:2px; margin:5px 0 8px 0; }
        .title { text-align:center; margin:0 0 2px 0; }
        .title h2 { font-size:11pt; font-weight:800; margin:0; text-transform:uppercase; }
        .meta { font-size:7.5pt; color:#333; margin:0 0 6px 0; display:flex; justify-content:space-between; }
        table.rekap { width:100%; border-collapse:collapse; }
        table.rekap th { font-size:7pt; font-weight:700; text-transform:uppercase; letter-spacing:0.04em; padding:6px 6px; text-align:left; border:1px solid #000; background:#fff; color:#000; }
        table.rekap th.center { text-align:center; }
        table.rekap td { padding:5px 6px; border:1px solid #000; vertical-align:top; font-size:7.8pt; }
        table.rekap td.center { text-align:center; }
        table.rekap td.mono { font-family: "Courier New", monospace; font-size:7.5pt; }
        .lokasi { font-size:7.5pt; }
        .footer { margin-top:8px; font-size:7pt; color:#000; border-top:1px solid #000; padding-top:6px; display:flex; justify-content:space-between; }
        @media screen { .screen-toolbar{ position:fixed; top:14px; right:14px; z-index:9999; display:flex; gap:8px; } .screen-toolbar button,.screen-toolbar a{ display:inline-flex; align-items:center; gap:6px; padding:10px 14px; border-radius:999px; font-family:ui-sans-serif,system-ui; font-size:12px; font-weight:700; text-decoration:none; border:1px solid #e5e7eb; background:#fff; color:#374151; box-shadow:0 4px 12px rgba(0,0,0,0.12); cursor:pointer; } .screen-toolbar .primary{ background:#2563eb; color:#fff; border-color:#1d4ed8; } @media print{ .screen-toolbar{ display:none !important; } } }
        @media print { .screen-toolbar{ display:none !important; } }
    </style>
</head>
<body>
    @if(!($isPdf ?? false))
    <div class="screen-toolbar">
        <button type="button" class="primary" onclick="window.print()">Cetak / Simpan PDF</button>
        <a href="javascript:window.close()">Tutup</a>
    </div>
    @endif

    <table class="kop">
        <tr>
            <td class="kop-logo"><img src="{{ $logoSrc ?? '/logo-pidie.svg' }}" alt="Logo" style="width:52px; height:auto;"></td>
            <td class="kop-text">
                <div class="h1">PEMERINTAH KABUPATEN PIDIE</div>
                <div class="h2">DINAS PENANAMAN MODAL DAN PELAYANAN TERPADU SATU PINTU</div>
                <div class="addr">Jln. Tgk. Chiek Direubee No.5 Sigli · Telp. (0653) 23399 · Faxs. (0653) 7829531 Kode Pos 24112</div>
            </td>
        </tr>
    </table>
    <div class="garis"></div>

    <div class="title">
        <h2>Rekap Data Usulan Pencabutan Perizinan Berusaha</h2>
    </div>

    <div class="meta">
        <span>Dicetak: {{ now()->format('d/m/Y H:i') }} WIB · {{ count($items) }} baris</span>
        <span>Nomor: {{ $meta['nomor'] ?? '—' }}</span>
    </div>

    <table class="rekap">
        <thead>
            <tr>
                <th class="center" style="width:28px;">No</th>
                <th style="width:28%;">Pelaku Usaha</th>
                <th style="width:17%;">NIB</th>
                <th style="width:18%;">Penanaman Modal</th>
                <th class="center" style="width:11%;">Skala</th>
                <th style="width:22%;">Lokasi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $i => $it)
                <tr>
                    <td class="center">{{ $i + 1 }}</td>
                    <td>
                        <div style="font-weight:700;">{{ $it->nama_pelaku_usaha ?? '—' }}</div>
                        @if($it->nib)<div class="mono" style="margin-top:1px;">NIB {{ $it->nib }}</div>@endif
                    </td>
                    <td class="mono">{{ $it->nib ?? '—' }}</td>
                    <td>{{ $it->jenis_penanaman_modal ?? '—' }}</td>
                    <td class="center">{{ $it->skala_usaha ?? '—' }}</td>
                    <td>
                        <div>{{ trim(($it->alamat ? $it->alamat . ', ' : '') . ($it->kelurahan ?? '')) ?: '—' }}</div>
                        <div class="lokasi">
                            @if($it->kecamatan)Kec. {{ $it->kecamatan }}@endif
                            @if($it->kab_kota){{ $it->kecamatan ? ' · ' : '' }}{{ $it->kab_kota }}@endif
                            @if($it->provinsi) · {{ $it->provinsi }}@endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" style="text-align:center; padding:18px; color:#64748b;">Tidak ada data untuk dicetak.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <span>Sumber: Sanksi Administratif Usulan Pencabutan Perizinan Berusaha — DPMPTSP Kab. Pidie</span>
        <span>{{ $meta['tempat'] ?? 'Sigli' }}, {{ $meta['tanggal'] ?? now()->format('d F Y') }}</span>
    </div>
</body>
</html>
