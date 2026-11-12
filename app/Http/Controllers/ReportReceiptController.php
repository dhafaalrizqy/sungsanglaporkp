<?php

namespace App\Http\Controllers;

use App\Models\Report;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ReportReceiptController extends Controller
{
    /**
     * Identitas instansi di kop & nomor surat "Tanda Bukti Lapor".
     * Semua teks yang tampil di PDF dan bersifat tetap ada di sini.
     */
    private const INSTANSI = [
        'aplikasi' => 'SUNGSANGLAPOR',

        // Kop kiri atas (baris ke-3 dicetak tebal)
        'kop'    => [
            'POLRI DAERAH SUMATERA SELATAN',
            'RESOR BANYUASIN',
            'SEKTOR BANYUASIN II',
        ],
        'alamat' => 'Desa Marga Sungsang, Kec. BA. II, Kab. Banyuasin',
        'model'  => 'Model B',

        // Bagian akhir nomor: STPL/B/{no}/{bulan romawi}/{tahun}/{kode_unit}
        'kode_unit' => 'SPKT/POLSEK BANYUASIN II/POLRES BANYUASIN/POLDA SUMSEL',
        'melapor_di' => 'SPKT Polsek Banyuasin II',

        // Dipakai di "Banyuasin II, 19 September 2026"
        'tempat' => 'Banyuasin II',
    ];

    /**
     * Petugas yang menerima laporan (kolom tanda tangan kanan).
     * Isi nama, pangkat, dan NRP petugas SPKT yang bertugas.
     * Kalau dikosongkan, PDF menampilkan titik-titik untuk diisi tangan.
     */
    private const PENERIMA = [
        'nama'   => null,   // contoh: 'NAMA PETUGAS'
        'pangkat' => null,  // contoh: 'AIPDA'
        'nrp'    => null,   // contoh: '83110216'
    ];

    private const ROMAWI = [1 => 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];

    /**
     * GET /report/{code}/bukti
     *   ?lihat=1  -> tampil di browser
     *   (default) -> langsung diunduh
     */
    public function download(Request $request, string $code)
    {
        $report = Report::where('code', $code)->firstOrFail();

        // PDF memuat NIK & alamat pelapor, jadi hanya pemilik laporan / petugas
        // yang boleh membukanya. Kode laporan mudah ditebak, jangan dibiarkan publik.
        $user    = $request->user();
        $isOwner = $user && (int) $report->resident?->user_id === (int) $user->id; // Report -> Resident -> User
        $isStaff = $user
            && method_exists($user, 'hasAnyRole')              // spatie/laravel-permission
            && $user->hasAnyRole(['admin', 'petugas']);        // <- sesuaikan nama role
        abort_unless($isOwner || $isStaff, 403);

        $pdf = Pdf::loadView('pdf.bukti-laporan', $this->buildData($report))
            ->setPaper('a4', 'portrait');

        $filename = 'Bukti-Laporan-' . $report->code . '.pdf';

        return $request->boolean('lihat')
            ? $pdf->stream($filename)
            : $pdf->download($filename);
    }

    /**
     * Semua pemetaan kolom database -> isi PDF ada di method ini.
     *
     * 19 baris data selalu tampil berurutan seperti format resmi. Baris yang
     * datanya belum dikumpulkan aplikasi diisi "-". Begitu kolomnya ditambahkan
     * ke tabel users / residents / reports (dengan salah satu nama di daftar
     * pick()), baris itu otomatis terisi tanpa perlu mengubah kode lagi.
     */
    private function buildData(Report $report): array
    {
        $resident = $report->resident;
        $user     = $resident?->user; // pelapor: Report -> Resident -> User
        $pelaku   = [$user, $resident]; // sumber data identitas pelapor

        $dibuat = $report->created_at->copy()->timezone('Asia/Jakarta')->locale('id');

        // kategori bisa berupa kolom teks atau relasi ke tabel kategori
        $kategori = $report->reportCategory ?? $report->category;
        $pasal    = is_object($kategori) ? $this->pick($kategori, ['pasal', 'article']) : null;
        $kategori = is_object($kategori) ? ($kategori->name ?? $kategori->nama ?? null) : $kategori;
        $perkara  = $kategori ? mb_strtoupper(trim($kategori)) : '-';

        $pelapor = mb_strtoupper($this->pick($user, ['name', 'nama']) ?? '-');
        $foto    = $this->imageDataUri($this->pick($report, ['image', 'photo', 'foto']));

        $kontak = collect([
            $this->pickAny($pelaku, ['phone', 'no_hp', 'telepon', 'telp']),
            $this->pickAny($pelaku, ['email']),
        ])->filter()->implode(' / ');

        $agama = $this->pickAny($pelaku, ['religion', 'agama']);
        $wni   = $this->pickAny($pelaku, ['kewarganegaraan', 'citizenship']) ?? 'Indonesia';

        $waktuKejadian = $this->waktuKejadian($report) ?? $dibuat;

        // Urutan & label mengikuti format resmi. $narasi = rata kiri-kanan.
        $rows = [
            ['Nama',                 $pelapor,                                                                     false],
            ['NIK',                  $this->pickAny($pelaku, ['nik']),                                             false],
            ['Tempat/Tanggal Lahir', $this->tempatTanggalLahir($pelaku),                                           false],
            ['Jenis Kelamin',        $this->jenisKelamin($this->pickAny($pelaku, ['gender', 'jenis_kelamin'])),    false],
            ['WNI/Agama',            $agama ? $wni . '/ ' . $agama : null,                                         false],
            ['Pekerjaan',            $this->pickAny($pelaku, ['occupation', 'pekerjaan']),                         false],
            ['Alamat',               $this->pickAny($pelaku, ['address', 'alamat']),                               false],
            ['Telp/Fax/Email',       $kontak,                                                                      false],
            ['Telah Melapor Di',     self::INSTANSI['melapor_di'] . ' (melalui aplikasi ' . self::INSTANSI['aplikasi'] . ')', false],
            ['Perkara',              $perkara,                                                                     false],
            ['Waktu Kejadian',       $this->formatWaktu($waktuKejadian),                                           false],
            ['Tempat Kejadian',      $this->pick($report, ['address', 'location', 'lokasi', 'alamat']),            false],
            ['Terlapor',             $this->pick($report, ['reported_party', 'terlapor', 'suspect']),              false],
            ['Korban',               $this->pick($report, ['victim', 'korban']),                                   false],
            ['Bagaimana Terjadi',    $this->narasi($this->pick($report, ['description', 'deskripsi', 'isi'])),     true],
            ['Saksi',                $this->pick($report, ['witness', 'saksi']),                                   false],
            ['Barang Bukti',         $foto ? '1 (satu) lembar foto (terlampir)' : $this->pick($report, ['evidence', 'barang_bukti']), false],
            ['Kerugian',             $this->pick($report, ['loss', 'kerugian']),                                   false],
            ['Dilaporkan Pada',      $this->formatWaktu($dibuat),                                                  false],
        ];

        $rows = array_map(fn ($row, $i) => [
            'no'     => str_pad($i + 1, 2, '0', STR_PAD_LEFT),
            'label'  => $row[0],
            'value'  => ($row[1] !== null && trim($row[1]) !== '') ? trim($row[1]) : '-',
            'narasi' => $row[2],
        ], $rows, array_keys($rows));

        $nomor = $this->nomorLaporan($report, $dibuat);

        $penerima = self::PENERIMA;

        return [
            'code'           => $report->code,
            'aplikasi'       => self::INSTANSI['aplikasi'],
            'kop'            => self::INSTANSI['kop'],
            'alamatKop'      => self::INSTANSI['alamat'],
            'model'          => self::INSTANSI['model'],
            'logo'           => $this->logoDataUri(),
            'nomorStpl'      => 'STPL/B/' . $nomor . '/' . self::INSTANSI['kode_unit'],
            'nomorLp'        => 'LP / B / ' . str_replace('/', ' / ', $nomor . '/' . self::INSTANSI['kode_unit']),
            'tanggalLaporan' => $dibuat->translatedFormat('j F Y'),
            'rows'           => $rows,
            'perkara'        => $perkara,
            'pasal'          => $pasal,
            'foto'           => $foto,
            'pelapor'        => $pelapor,
            'tempat'         => self::INSTANSI['tempat'],
            'penerimaNama'   => $penerima['nama'] ? mb_strtoupper($penerima['nama']) : null,
            'penerimaJabatan' => collect([
                $penerima['pangkat'] ? mb_strtoupper($penerima['pangkat']) : null,
                $penerima['nrp'] ? 'NRP ' . $penerima['nrp'] : null,
            ])->filter()->implode(' '),
            'dicetak'        => now('Asia/Jakarta')->locale('id')->translatedFormat('j F Y, H.i') . ' WIB',
        ];
    }

    /**
     * Nomor urut laporan dalam tahun yang sama, contoh "38/IX/2026".
     * Dihitung dari urutan id (laporan terhapus tetap dihitung) supaya nomor
     * setiap laporan tidak berubah dan tidak bentrok.
     */
    private function nomorLaporan(Report $report, Carbon $dibuat): string
    {
        $tz    = config('app.timezone');
        $awal  = $dibuat->copy()->startOfYear()->setTimezone($tz);
        $akhir = $dibuat->copy()->endOfYear()->setTimezone($tz);

        $urut = Report::withTrashed()
            ->whereBetween('created_at', [$awal, $akhir])
            ->where('id', '<=', $report->id)
            ->count();

        return $urut . '/' . self::ROMAWI[(int) $dibuat->format('n')] . '/' . $dibuat->format('Y');
    }

    /** "Hari Sabtu, tanggal 19 September 2026 sekira jam 14.49 Wib" */
    private function formatWaktu(Carbon $waktu): string
    {
        return 'Hari ' . $waktu->translatedFormat('l')
            . ', tanggal ' . $waktu->translatedFormat('j F Y')
            . ' sekira jam ' . $waktu->format('H.i') . ' Wib';
    }

    /** Uraian diapit tanda strip seperti pada laporan polisi asli. */
    private function narasi(?string $teks): ?string
    {
        $teks = $teks !== null ? trim($teks) : '';

        return $teks === '' ? null : '------ ' . $teks . ' ------';
    }

    /**
     * Waktu kejadian. Aplikasi saat ini belum mencatatnya terpisah, jadi kalau
     * kolomnya belum ada, PDF memakai waktu laporan dibuat.
     */
    private function waktuKejadian(Report $report): ?Carbon
    {
        foreach (['incident_at', 'waktu_kejadian', 'tanggal_kejadian'] as $key) {
            $raw = $report->getAttribute($key);

            try {
                if ($raw instanceof \DateTimeInterface) {
                    return Carbon::instance($raw)->timezone('Asia/Jakarta')->locale('id');
                }
                if (is_string($raw) && trim($raw) !== '') {
                    return Carbon::parse($raw, 'Asia/Jakarta')->locale('id');
                }
            } catch (\Throwable) {
                // format tidak dikenali -> coba kolom berikutnya
            }
        }

        return null;
    }

    private function tempatTanggalLahir(array $models): ?string
    {
        if ($ttl = $this->pickAny($models, ['ttl', 'tempat_tanggal_lahir'])) {
            return $ttl;
        }

        $tempat  = $this->pickAny($models, ['tempat_lahir', 'birth_place']);
        $tanggal = $this->pickAny($models, ['tanggal_lahir', 'tgl_lahir', 'birth_date']);

        if ($tanggal) {
            try {
                $tanggal = Carbon::parse($tanggal)->format('d-m-Y');
            } catch (\Throwable) {
                // pakai apa adanya
            }
        }

        return collect([$tempat, $tanggal])->filter()->implode(', ') ?: null;
    }

    private function jenisKelamin(?string $nilai): ?string
    {
        if ($nilai === null) {
            return null;
        }

        return match (mb_strtoupper(mb_substr($nilai, 0, 1))) {
            'L', 'M' => 'Laki-Laki',
            'P', 'F' => 'Perempuan',
            default  => $nilai,
        };
    }

    /** Ambil nilai pertama yang terisi dari beberapa model sekaligus (user, resident, ...). */
    private function pickAny(array $models, array $keys): ?string
    {
        foreach ($models as $model) {
            if (($value = $this->pick($model, $keys)) !== null) {
                return $value;
            }
        }

        return null;
    }

    /** Ambil nilai pertama yang terisi dari beberapa kemungkinan nama kolom. */
    private function pick($model, array $keys): ?string
    {
        if (! $model) {
            return null;
        }

        foreach ($keys as $key) {
            $value = $model->getAttribute($key);

            if ($value instanceof \DateTimeInterface) {
                $value = $value->format('Y-m-d');
            }

            if (is_scalar($value) && trim((string) $value) !== '') {
                return trim((string) $value);
            }
        }

        return null;
    }

    /**
     * Foto laporan -> data URI (base64), diperkecil supaya PDF tidak berat.
     * Data URI dipakai agar aman di Windows/XAMPP tanpa urusan path & chroot dompdf.
     */
    private function imageDataUri(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        $path = preg_replace('#^/?storage/#', '', $path);

        if (Storage::disk('public')->exists($path)) {
            $binary = Storage::disk('public')->get($path);
        } elseif (is_file(public_path($path))) {
            $binary = file_get_contents(public_path($path));
        } else {
            return null;
        }

        if (function_exists('imagecreatefromstring') && ($img = @imagecreatefromstring($binary))) {
            if (imagesx($img) > 900 && ($scaled = imagescale($img, 900))) {
                $img = $scaled;
            }
            ob_start();
            imagejpeg($img, null, 75);
            $binary = ob_get_clean();
        }

        return 'data:image/jpeg;base64,' . base64_encode($binary);
    }

    /** Logo instansi di bawah kop. Pakai logo bawaan proyek; public/images/logo.png menimpanya kalau ada. */
    private function logoDataUri(): ?string
    {
        foreach (['images/logo.png', 'assets/app/images/logo-polres-sungsang.png'] as $relative) {
            $file = public_path($relative);

            if (is_file($file)) {
                return 'data:image/png;base64,' . base64_encode(file_get_contents($file));
            }
        }

        return null;
    }
}