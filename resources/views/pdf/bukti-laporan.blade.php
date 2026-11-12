<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Tanda Bukti Lapor {{ $nomorStpl }}</title>
    <style>
        @page { margin: 34px 56px 64px 56px; }
        body { font-family: Helvetica, Arial, sans-serif; font-size: 9.5pt; color: #000; line-height: 1.3; }

        table { border-collapse: collapse; }
        td { vertical-align: top; padding: 0; }

        /* Kop kiri atas + "Model B" kanan atas */
        .kop { width: 100%; }
        .kop .kiri { width: 42%; text-align: center; font-size: 8.5pt; line-height: 1.25; }
        .kop .kiri .tebal { font-weight: bold; }
        .kop .kiri .alamat { font-size: 6.5pt; display: inline-block; border-bottom: 1px solid #000; padding-bottom: 1px; margin-top: 1px; }
        .kop .kanan { text-align: right; font-size: 8pt; }

        .logo { text-align: center; margin: 2px 0 4px; }
        .logo img { width: 66px; }

        /* Judul & nomor */
        .judul { text-align: center; font-weight: bold; font-size: 11pt; padding-bottom: 1px; border-bottom: 1px solid #000; }
        .nomor { text-align: center; font-weight: bold; font-size: 9pt; padding: 2px 0 2px; border-bottom: 1px solid #000; }

        .pembuka { margin: 12px 0 10px; text-align: justify; text-indent: 34px; line-height: 1.35; }

        /* Tabel data bernomor */
        .data { width: 100%; }
        .data td { padding: 2px 0; }
        .data .no { width: 30px; }
        .data .label { width: 150px; white-space: nowrap; }
        .data .titik { width: 14px; }
        .data .narasi { text-align: justify; font-size: 9pt; line-height: 1.3; }

        /* Telah melaporkan tindak pidana */
        .penutup { width: 100%; margin-top: 16px; }
        .penutup td { font-weight: bold; }
        .penutup .label { width: 232px; white-space: nowrap; }
        .penutup .titik { width: 14px; }
        .penutup .isi { text-align: center; }

        /* Tanda tangan */
        .ttd { width: 100%; margin-top: 30px; page-break-inside: avoid; }
        .ttd td { text-align: center; }
        .ttd .kiri { width: 46%; }
        .ttd .kanan { width: 54%; }
        .ttd .ruang { height: 66px; }
        .ttd .nama { font-weight: bold; text-decoration: underline; }

        /* Lampiran foto (halaman terpisah, hanya kalau ada foto) */
        .lampiran { page-break-before: always; }
        .lampiran .cap { text-align: center; font-weight: bold; font-size: 11pt; border-bottom: 1px solid #000; padding-bottom: 2px; }
        .lampiran .ket { text-align: center; font-size: 8.5pt; margin: 4px 0 14px; }
        .lampiran .foto { text-align: center; }
        .lampiran img { width: 380px; border: 1px solid #777; }

        /* Catatan kecil di bawah setiap halaman */
        .footer { position: fixed; bottom: -44px; left: 0; right: 0; border-top: 1px solid #999; padding-top: 4px; font-size: 7pt; color: #555; text-align: center; }
    </style>
</head>
<body>

    <table class="kop">
        <tr>
            <td class="kiri">
                @foreach($kop as $baris)
                    <div class="{{ $loop->last ? 'tebal' : '' }}">{{ $baris }}</div>
                @endforeach
                <span class="alamat">{{ $alamatKop }}</span>
            </td>
            <td>&nbsp;</td>
            <td class="kanan">{{ $model }}</td>
        </tr>
    </table>

    @if($logo)
        <div class="logo"><img src="{{ $logo }}" alt=""></div>
    @endif

    <div class="judul">TANDA BUKTI LAPOR</div>
    <div class="nomor">{{ $nomorStpl }}</div>

    <p class="pembuka">
        Berdasarkan Laporan Polisi Nomor : <b>{{ $nomorLp }}</b>,
        tanggal {{ $tanggalLaporan }} dengan ini menerangkan bahwa :
    </p>

    <table class="data">
        @foreach($rows as $row)
            <tr>
                <td class="no">{{ $row['no'] }}.</td>
                <td class="label">{{ $row['label'] }}</td>
                <td class="titik">:</td>
                <td class="{{ $row['narasi'] ? 'narasi' : '' }}">{!! nl2br(e($row['value'])) !!}</td>
            </tr>
        @endforeach
    </table>

    <table class="penutup">
        <tr>
            <td class="label">Telah melaporkan Tindak Pidana</td>
            <td class="titik">:</td>
            <td class="isi">------ &nbsp;{{ $perkara }}&nbsp; ------</td>
        </tr>
        @if($pasal)
            <tr>
                <td colspan="2"></td>
                <td class="isi">------ &nbsp;{{ $pasal }}&nbsp; ------</td>
            </tr>
        @endif
    </table>

    <table class="ttd">
        <tr>
            <td class="kiri">
                Pelapor<br>&nbsp;
                <div class="ruang"></div>
                <span class="nama">{{ $pelapor }}</span>
            </td>
            <td class="kanan">
                {{ $tempat }}, {{ $tanggalLaporan }}<br>
                Yang Menerima Laporan
                <div class="ruang"></div>
                @if($penerimaNama)
                    <span class="nama">{{ $penerimaNama }}</span><br>
                    {{ $penerimaJabatan }}
                @else
                    ..............................<br>
                    Pangkat / NRP : ..................
                @endif
            </td>
        </tr>
    </table>

    @if($foto)
        <div class="lampiran">
            <div class="cap">LAMPIRAN FOTO</div>
            <div class="ket">{{ $nomorStpl }}</div>
            <div class="foto"><img src="{{ $foto }}" alt="Lampiran foto laporan"></div>
        </div>
    @endif

    <div class="footer">
        Kode laporan aplikasi {{ $aplikasi }}: <b>{{ $code }}</b>. Gunakan kode ini untuk memantau perkembangan laporan.
        Dokumen dicetak otomatis pada {{ $dicetak }}.
    </div>

</body>
</html>