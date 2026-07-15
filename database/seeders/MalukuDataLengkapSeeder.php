<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AkunRelawan;
use App\Models\Ambulans;
use App\Models\Faskes;
use App\Models\LaporanBencana;
use App\Models\NotifikasiAdmin;
use App\Models\NotifikasiAdminPenerima;
use App\Models\Pengguna;
use App\Models\Penugasan;
use App\Models\PetugasEmergency;
use App\Models\Relawan;
use App\Models\RelawanNotifikasi;
use App\Models\TitikEvakuasi;
use App\Models\User;
use App\Models\Wilayah;
use App\Models\ZonaRawanBencana;
use Filament\Notifications\Notification;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Seeder data demo lengkap Provinsi Maluku.
 *
 * - Lokasi di daratan pulau (pusat kota/kecamatan, offset kecil — bukan lautan)
 * - ~10 record per entitas utama
 * - 5 fasilitas kesehatan per pulau
 *
 * Jalankan:
 *   php artisan db:seed --class=MalukuDataLengkapSeeder
 *
 * Password semua akun demo: password123
 */
class MalukuDataLengkapSeeder extends Seeder
{
    private const PASSWORD = 'password123';

    private const PROVINSI = 'Maluku';

    /**
     * Titik pusat daratan per kota (wilayah) — verified inland town centers.
     *
     * @var list<array{
     *   key: string,
     *   nama: string,
     *   kecamatan: string,
     *   kota: string,
     *   pulau: string,
     *   lat: float,
     *   lng: float,
     *   jitter: float
     * }>
     */
    private const WILAYAH = [
        [
            'key' => 'ambon', 'nama' => 'Sirimau', 'kecamatan' => 'Sirimau',
            'kota' => 'Kota Ambon', 'pulau' => 'Pulau Ambon',
            'lat' => -3.6954, 'lng' => 128.1814, 'jitter' => 0.012,
        ],
        [
            'key' => 'nusaniwe', 'nama' => 'Nusaniwe', 'kecamatan' => 'Nusaniwe',
            'kota' => 'Kota Ambon', 'pulau' => 'Pulau Ambon',
            'lat' => -3.6928, 'lng' => 128.1688, 'jitter' => 0.010,
        ],
        [
            'key' => 'namlea', 'nama' => 'Namlea', 'kecamatan' => 'Namlea',
            'kota' => 'Buru', 'pulau' => 'Pulau Buru',
            'lat' => -3.2683, 'lng' => 126.7667, 'jitter' => 0.010,
        ],
        [
            'key' => 'namrole', 'nama' => 'Namrole', 'kecamatan' => 'Namrole',
            'kota' => 'Buru Selatan', 'pulau' => 'Pulau Buru',
            'lat' => -3.8550, 'lng' => 126.7167, 'jitter' => 0.008,
        ],
        [
            'key' => 'masohi', 'nama' => 'Masohi', 'kecamatan' => 'Masohi',
            'kota' => 'Maluku Tengah', 'pulau' => 'Pulau Seram',
            'lat' => -3.3000, 'lng' => 129.3667, 'jitter' => 0.010,
        ],
        [
            'key' => 'piru', 'nama' => 'Piru', 'kecamatan' => 'Piru',
            'kota' => 'Seram Bagian Barat', 'pulau' => 'Pulau Seram',
            'lat' => -3.2000, 'lng' => 128.0833, 'jitter' => 0.008,
        ],
        [
            'key' => 'tual', 'nama' => 'Tual Kota', 'kecamatan' => 'Tual Kota',
            'kota' => 'Tual', 'pulau' => 'Kepulauan Kei',
            'lat' => -5.6417, 'lng' => 132.7472, 'jitter' => 0.006,
        ],
        [
            'key' => 'elat', 'nama' => 'Elat', 'kecamatan' => 'Elat',
            'kota' => 'Maluku Tenggara', 'pulau' => 'Kepulauan Kei',
            'lat' => -5.7167, 'lng' => 132.6833, 'jitter' => 0.006,
        ],
        [
            'key' => 'dobo', 'nama' => 'Dobo', 'kecamatan' => 'Dobo',
            'kota' => 'Kepulauan Aru', 'pulau' => 'Kepulauan Aru',
            'lat' => -5.7590, 'lng' => 134.2310, 'jitter' => 0.006,
        ],
        [
            'key' => 'banda', 'nama' => 'Banda Neira', 'kecamatan' => 'Banda',
            'kota' => 'Maluku Tengah', 'pulau' => 'Kepulauan Banda',
            'lat' => -4.5267, 'lng' => 129.9044, 'jitter' => 0.003,
        ],
        [
            'key' => 'saumlaki', 'nama' => 'Saumlaki', 'kecamatan' => 'Tanimbar Selatan',
            'kota' => 'Kepulauan Tanimbar', 'pulau' => 'Kepulauan Tanimbar',
            'lat' => -7.9800, 'lng' => 131.3050, 'jitter' => 0.006,
        ],
        [
            'key' => 'saparua', 'nama' => 'Saparua', 'kecamatan' => 'Saparua',
            'kota' => 'Maluku Tengah', 'pulau' => 'Pulau Saparua',
            'lat' => -3.5750, 'lng' => 128.6550, 'jitter' => 0.004,
        ],
    ];

    /** @var array<string, Wilayah> keyed by wilayah key */
    private array $wilayah = [];

    /** @var array<string, list<Wilayah>> keyed by pulau */
    private array $wilayahByPulau = [];

    /** @var list<Pengguna> */
    private array $pengguna = [];

    /** @var list<Relawan> */
    private array $relawan = [];

    /** @var list<AkunRelawan> */
    private array $akunRelawan = [];

    /** @var list<Faskes> */
    private array $faskes = [];

    /** @var list<LaporanBencana> */
    private array $laporan = [];

    /** @var list<ZonaRawanBencana> */
    private array $zona = [];

    /** @var list<Ambulans> */
    private array $ambulans = [];

    private ?User $admin = null;

    public function run(): void
    {
        $this->admin = User::query()->first() ?? User::query()->create([
            'name' => 'Admin',
            'email' => 'admin@gmail.com',
            'password' => Hash::make('password'),
            'phone' => '081234567890',
        ]);

        $this->seedWilayah();
        $this->seedPengguna();
        $this->seedRelawan();
        $this->seedFaskesPerPulau();
        $this->seedLaporan();
        $this->seedPetugasEmergency();
        $this->seedZonaRawan();
        $this->seedTitikEvakuasi();
        $this->seedAmbulans();
        $this->seedPenugasan();
        $this->seedEdukasi();
        $this->seedRelawanNotifikasi();
        $this->seedNotifikasiAdmin();
        $this->seedNotifikasiAdminPanel();

        $this->command?->info('MalukuDataLengkapSeeder selesai.');
        $this->command?->table(
            ['Entitas', 'Jumlah'],
            [
                ['Wilayah', count($this->wilayah)],
                ['Pulau (unik)', count($this->wilayahByPulau)],
                ['Pengguna masyarakat', count($this->pengguna)],
                ['Relawan + akun', count($this->akunRelawan)],
                ['Faskes', count($this->faskes)],
                ['Laporan bencana', count($this->laporan)],
                ['Zona rawan', count($this->zona)],
                ['Titik evakuasi', TitikEvakuasi::count()],
                ['Ambulans', count($this->ambulans)],
                ['Petugas emergency', PetugasEmergency::count()],
                ['Penugasan', Penugasan::count()],
                ['Edukasi dan Simulasi', DB::table('pedoman_bhd')->count()],
                ['Notifikasi relawan', RelawanNotifikasi::count()],
                ['Notifikasi admin broadcast', NotifikasiAdmin::count()],
            ],
        );
        $this->command?->info('Login masyarakat: masyarakat1@maluku.demo.test … masyarakat10@maluku.demo.test | password123');
        $this->command?->info('Login relawan: relawan1@maluku.demo.test … relawan10@maluku.demo.test | password123');
    }

    private function seedWilayah(): void
    {
        foreach (self::WILAYAH as $item) {
            $wilayah = Wilayah::updateOrCreate(
                ['nama' => $item['nama'], 'kecamatan' => $item['kecamatan']],
                [
                    'kota' => $item['kota'],
                    'pulau' => $item['pulau'],
                    'provinsi' => self::PROVINSI,
                    'latitude' => $item['lat'],
                    'longitude' => $item['lng'],
                ],
            );

            $this->wilayah[$item['key']] = $wilayah;
            $this->wilayahByPulau[$item['pulau']] ??= [];
            $this->wilayahByPulau[$item['pulau']][] = $wilayah;
        }
    }

    private function seedPengguna(): void
    {
        $names = [
            'Budi Pattiasina', 'Siti Latuhalat', 'Yusuf Soumokil', 'Maria Hehanussa',
            'Jonis Pelu', 'Helena Latupeirissa', 'Rendi Tuha', 'Grace Salamena',
            'Andi Lesnussa', 'Clara Matulessy',
        ];

        foreach ($names as $i => $name) {
            $n = $i + 1;
            $this->pengguna[] = Pengguna::updateOrCreate(
                ['phone' => '0911200'.str_pad((string) $n, 3, '0', STR_PAD_LEFT)],
                [
                    'name' => $name,
                    'email' => "masyarakat{$n}@maluku.demo.test",
                    'password' => Hash::make(self::PASSWORD),
                ],
            );
        }
    }

    private function seedRelawan(): void
    {
        $keahlian = ['Medis', 'SAR', 'Logistik', 'Evakuasi', 'Dapur Umum', 'Psikososial', 'Komunikasi'];
        $keys = array_keys($this->wilayah);

        for ($i = 0; $i < 10; $i++) {
            $n = $i + 1;
            $meta = self::WILAYAH[$i % count(self::WILAYAH)];
            $wilayahKey = $keys[$i % count($keys)];
            [$lat, $lng] = $this->titikDaratan($meta);

            $pengguna = Pengguna::updateOrCreate(
                ['phone' => '0911300'.str_pad((string) $n, 3, '0', STR_PAD_LEFT)],
                [
                    'name' => "Relawan {$meta['nama']} {$n}",
                    'email' => "pengguna.relawan{$n}@maluku.demo.test",
                    'password' => Hash::make(self::PASSWORD),
                ],
            );

            $relawan = Relawan::updateOrCreate(
                ['pengguna_id' => $pengguna->id],
                [
                    'umur' => 24 + $i,
                    'alamat' => "Jl. Relawan {$meta['nama']}, {$meta['kota']}, Maluku",
                    'keahlian' => $keahlian[$i % count($keahlian)],
                    'organisasi' => "Tagana {$meta['pulau']}",
                    'status' => 'disetujui',
                    'approved_by' => $this->admin?->id,
                ],
            );

            $this->relawan[] = $relawan;
            $this->akunRelawan[] = AkunRelawan::updateOrCreate(
                ['email' => "relawan{$n}@maluku.demo.test"],
                [
                    'relawan_id' => $relawan->id,
                    'password' => Hash::make(self::PASSWORD),
                    'status' => 'aktif',
                    'latitude' => $lat,
                    'longitude' => $lng,
                    'lokasi_updated_at' => now()->subMinutes(2 + $i),
                    'fcm_token' => "fcm-maluku-relawan-{$n}",
                ],
            );

            unset($wilayahKey);
        }
    }

    private function seedFaskesPerPulau(): void
    {
        $tipeCycle = ['rumah_sakit', 'puskesmas', 'puskesmas', 'apotek', 'apotek'];
        $namaTipe = [
            'rumah_sakit' => 'RSUD',
            'puskesmas' => 'Puskesmas',
            'apotek' => 'Apotek',
        ];

        foreach ($this->wilayahByPulau as $pulau => $wilayahList) {
            $anchor = $wilayahList[0];
            $meta = $this->metaByWilayahId($anchor->id) ?? self::WILAYAH[0];

            for ($i = 0; $i < 5; $i++) {
                $tipe = $tipeCycle[$i];
                $wilayah = $wilayahList[$i % count($wilayahList)];
                [$lat, $lng] = $this->titikDaratan($meta, $i);

                $nama = sprintf(
                    '%s %s %s',
                    $namaTipe[$tipe],
                    str_replace(['Pulau ', 'Kepulauan '], '', $pulau),
                    $i + 1,
                );

                $this->faskes[] = Faskes::updateOrCreate(
                    ['nama' => $nama],
                    [
                        'wilayah_id' => $wilayah->id,
                        'admin_id' => $this->admin?->id,
                        'tipe' => $tipe,
                        'alamat' => "Jl. Kesehatan No. ".($i + 1).", {$wilayah->nama}, {$pulau}, Maluku",
                        'latitude' => $lat,
                        'longitude' => $lng,
                        'nomor_telepon' => '09115'.str_pad((string) (abs(crc32($nama)) % 100000), 5, '0', STR_PAD_LEFT),
                        'jam_operasional' => $tipe === 'apotek' ? '08.00–21.00 WIT' : '24 Jam',
                    ],
                );
            }
        }
    }

    private function seedLaporan(): void
    {
        $jenisList = [
            'Banjir', 'Gempa Bumi', 'Kebakaran', 'Tanah Longsor', 'Tsunami',
            'Angin Kencang', 'Kekeringan', 'Wabah', 'Lainnya', 'Banjir',
        ];
        $statusList = [
            ['pending', 'belum_ditangani'],
            ['diverifikasi', 'belum_ditangani'],
            ['ditangani', 'sedang_ditangani'],
            ['ditangani', 'sedang_ditangani'],
            ['selesai', 'selesai_ditangani'],
            ['diverifikasi', 'belum_ditangani'],
            ['pending', 'belum_ditangani'],
            ['ditangani', 'sedang_ditangani'],
            ['selesai', 'selesai_ditangani'],
            ['diverifikasi', 'sedang_ditangani'],
        ];

        $deskripsi = [
            'Genangan air setinggi lutut menghambat akses jalan utama di wilayah sekitar pasar.',
            'Getaran kuat dirasakan warga; beberapa dinding rumah mengalami retak ringan.',
            'Api menjalar di deretan kios; sudah dikendalikan petugas setempat.',
            'Longsor menutup sebagian badan jalan menuju pelabuhan rakyat.',
            'Gelombang tinggi mendekati pesisir; warga diimbau naik ke dataran tinggi.',
            'Angin kencang merobohkan pohon dan merusak atap bangunan warga.',
            'Kekeringan berkepanjangan; sumber air warga mulai berkurang.',
            'Peningkatan kasus diare di beberapa dusun; butuh bantuan medis.',
            'Pohon tumbang menimpa saluran listrik; arus lalu lintas terganggu.',
            'Hujan deras terus-menerus, drainase tersumbat di pusat kota.',
        ];

        $keys = array_keys($this->wilayah);

        for ($i = 0; $i < 10; $i++) {
            $meta = self::WILAYAH[$i % count(self::WILAYAH)];
            $wilayah = $this->wilayah[$keys[$i % count($keys)]];
            $pengguna = $this->pengguna[$i % count($this->pengguna)];
            [$lat, $lng] = $this->titikDaratan($meta, $i + 3);
            [$status, $penanganan] = $statusList[$i];

            $akunRelawanId = in_array($penanganan, ['sedang_ditangani', 'selesai_ditangani'], true)
                ? $this->akunRelawan[$i % count($this->akunRelawan)]->id
                : null;

            $alamat = "Lokasi {$jenisList[$i]} — {$meta['nama']}, {$meta['kota']}, {$meta['pulau']}";

            $laporan = LaporanBencana::withoutEvents(function () use (
                $i, $jenisList, $pengguna, $wilayah, $lat, $lng, $alamat,
                $deskripsi, $status, $penanganan, $akunRelawanId, $meta
            ) {
                return LaporanBencana::updateOrCreate(
                    [
                        'nama_pelapor' => $pengguna->name,
                        'jenis_kejadian' => $jenisList[$i],
                        'alamat_lokasi' => $alamat,
                    ],
                    [
                        'pengguna_id' => $pengguna->id,
                        'wilayah_id' => $wilayah->id,
                        'nomor_kontak' => $pengguna->phone,
                        'di_lokasi_kejadian' => true,
                        'latitude' => $lat,
                        'longitude' => $lng,
                        'tanggal_kejadian' => now()->subHours(2 + ($i * 3)),
                        'deskripsi' => $deskripsi[$i].' ('.$meta['pulau'].').',
                        'foto' => [],
                        'meninggal_jumlah' => 0,
                        'hilang_jumlah' => $i === 4 ? 1 : 0,
                        'luka_berat_jumlah' => $i % 3,
                        'luka_ringan_jumlah' => 1 + ($i % 5),
                        'status' => $status,
                        'status_penanganan' => $penanganan,
                        'akun_relawan_ditugaskan' => $akunRelawanId,
                        'verified_by' => in_array($status, ['diverifikasi', 'ditangani', 'selesai'], true)
                            ? $this->admin?->id
                            : null,
                        'verified_at' => in_array($status, ['diverifikasi', 'ditangani', 'selesai'], true)
                            ? now()->subHours(1 + $i)
                            : null,
                    ],
                );
            });

            $this->laporan[] = $laporan;
        }
    }

    private function seedPetugasEmergency(): void
    {
        $data = [
            ['nama' => 'Basarnas Ambon', 'kategori' => 'sar', 'key' => 'ambon'],
            ['nama' => 'BPBD Provinsi Maluku', 'kategori' => 'lainnya', 'key' => 'ambon'],
            ['nama' => 'Damkar Kota Ambon', 'kategori' => 'lainnya', 'key' => 'nusaniwe'],
            ['nama' => 'PMI Kota Ambon', 'kategori' => 'medis', 'key' => 'ambon'],
            ['nama' => 'Pos SAR Namlea Buru', 'kategori' => 'sar', 'key' => 'namlea'],
            ['nama' => 'Tim Medis Masohi Seram', 'kategori' => 'medis', 'key' => 'masohi'],
            ['nama' => 'Posko Logistik Tual', 'kategori' => 'logistik', 'key' => 'tual'],
            ['nama' => 'Satgas Aru Dobo', 'kategori' => 'sar', 'key' => 'dobo'],
            ['nama' => 'Pos SAR Banda Neira', 'kategori' => 'sar', 'key' => 'banda'],
            ['nama' => 'Posko Saumlaki Tanimbar', 'kategori' => 'logistik', 'key' => 'saumlaki'],
        ];

        foreach ($data as $i => $item) {
            $meta = $this->metaByKey($item['key']);
            [$lat, $lng] = $this->titikDaratan($meta, $i);

            PetugasEmergency::updateOrCreate(
                ['nama' => $item['nama']],
                [
                    'kategori' => $item['kategori'],
                    'nomor_telepon' => '09113999'.str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT),
                    'latitude' => $lat,
                    'longitude' => $lng,
                    'alamat' => "Kantor {$item['nama']}, {$meta['nama']}, {$meta['pulau']}",
                    'status' => 'aktif',
                ],
            );
        }
    }

    private function seedZonaRawan(): void
    {
        $risiko = ['tinggi', 'tinggi', 'sedang', 'sedang', 'rendah', 'tinggi', 'sedang', 'rendah', 'tinggi', 'sedang'];
        $keys = array_keys($this->wilayah);

        for ($i = 0; $i < 10; $i++) {
            $meta = self::WILAYAH[$i % count(self::WILAYAH)];
            $wilayah = $this->wilayah[$keys[$i % count($keys)]];
            [$lat, $lng] = $this->titikDaratan($meta, $i + 1);

            $this->zona[] = ZonaRawanBencana::updateOrCreate(
                ['nama_zona' => "Zona Rawan {$meta['nama']} {$i}"],
                [
                    'wilayah_id' => $wilayah->id,
                    'created_by' => $this->admin?->id,
                    'tingkat_risiko' => $risiko[$i],
                    'polygon' => $this->buatPolygon($lat, $lng, $meta['jitter'] * 0.35),
                    'deskripsi' => "Zona rawan bencana di daratan {$meta['nama']}, {$meta['pulau']}, Provinsi Maluku.",
                ],
            );
        }
    }

    private function seedTitikEvakuasi(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $meta = self::WILAYAH[$i % count(self::WILAYAH)];
            $zona = $this->zona[$i % count($this->zona)];
            [$lat, $lng] = $this->titikDaratan($meta, $i + 5);

            TitikEvakuasi::updateOrCreate(
                ['nama' => "Titik Evakuasi {$meta['nama']} {$meta['pulau']}"],
                [
                    'zona_id' => $zona->id,
                    'latitude' => $lat,
                    'longitude' => $lng,
                    'kapasitas' => 200 + ($i * 50),
                    'fasilitas' => 'Tenda, dapur umum, air bersih, toilet darurat, genset',
                ],
            );
        }
    }

    private function seedAmbulans(): void
    {
        // Ambil 10 faskes pertama (tersebar antar pulau)
        $subset = array_slice($this->faskes, 0, 10);

        foreach ($subset as $i => $faskes) {
            $this->ambulans[] = Ambulans::updateOrCreate(
                ['faskes_id' => $faskes->id, 'nama_layanan' => 'Unit Ambulans '.$faskes->nama],
                [
                    'nomor_telepon' => '09113888'.str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT),
                    'status' => $i % 3 === 0 ? 'tidak_tersedia' : 'tersedia',
                    'jenis_layanan' => $i % 4 === 0 ? 'berbayar' : 'gratis',
                ],
            );
        }
    }

    private function seedPenugasan(): void
    {
        for ($i = 0; $i < 10; $i++) {
            if (! isset($this->laporan[$i], $this->relawan[$i], $this->ambulans[$i % count($this->ambulans)])) {
                continue;
            }

            Penugasan::updateOrCreate(
                [
                    'laporan_id' => $this->laporan[$i]->id,
                    'relawan_id' => $this->relawan[$i]->id,
                ],
                [
                    'petugas_id' => $this->admin?->id,
                    'ambulans_id' => $this->ambulans[$i % count($this->ambulans)]->id,
                    'status' => match ($i % 3) {
                        0 => 'ditugaskan',
                        1 => 'dalam_perjalanan',
                        default => 'selesai',
                    },
                    'catatan' => 'Penugasan demo penanganan bencana Provinsi Maluku.',
                    'ditugaskan_at' => now()->subHours(1 + $i),
                ],
            );
        }
    }

    private function seedEdukasi(): void
    {
        $data = [
            ['judul' => 'Pedoman Evakuasi Tsunami Maluku', 'tipe' => 'pdf'],
            ['judul' => 'SOP Penanganan Banjir Kepulauan', 'tipe' => 'pdf'],
            ['judul' => 'Video Simulasi Gempa Bumi Ambon', 'tipe' => 'video'],
            ['judul' => 'Infografis Titik Kumpul Seram', 'tipe' => 'gambar'],
            ['judul' => 'Panduan Pertolongan Pertama Darurat', 'tipe' => 'pdf'],
            ['judul' => 'Siaga Cuaca Ekstrem Laut Banda', 'tipe' => 'gambar'],
            ['judul' => 'Video Evakuasi Mandiri Kei Islands', 'tipe' => 'video'],
            ['judul' => 'Checklist Logistik Posko Aru', 'tipe' => 'dokumen'],
            ['judul' => 'Peta Evakuasi Banda Neira', 'tipe' => 'gambar'],
            ['judul' => 'SOP Koordinasi Relawan Tanimbar', 'tipe' => 'dokumen'],
            ['judul' => 'Aplikasi Simulasi Evakuasi Gempa', 'tipe' => 'aplikasi'],
            ['judul' => 'Aplikasi Simulasi Tsunami Maluku', 'tipe' => 'aplikasi'],
        ];

        foreach ($data as $i => $item) {
            $ext = match ($item['tipe']) {
                'video' => 'mp4',
                'gambar' => 'jpg',
                'aplikasi' => 'apk',
                default => 'pdf',
            };

            DB::table('pedoman_bhd')->updateOrInsert(
                ['judul' => $item['judul']],
                [
                    'tipe_file' => $item['tipe'],
                    'deskripsi' => $item['tipe'] === 'aplikasi'
                        ? 'Aplikasi simulasi kebencanaan. Unduh file APK lalu instal di perangkat Android Anda.'
                        : 'Materi edukasi kebencanaan untuk masyarakat dan relawan Provinsi Maluku.',
                    'file_path' => 'https://example.com/edukasi-maluku-'.($i + 1).'.'.$ext,
                    'uploaded_by' => $this->admin?->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }
    }

    private function seedRelawanNotifikasi(): void
    {
        foreach ($this->laporan as $i => $laporan) {
            $akun = $this->akunRelawan[$i % count($this->akunRelawan)];

            RelawanNotifikasi::updateOrCreate(
                [
                    'akun_relawan_id' => $akun->id,
                    'laporan_id' => $laporan->id,
                ],
                [
                    'sudah_dibaca' => $i % 2 === 0,
                    'dibaca_at' => $i % 2 === 0 ? now()->subMinutes(20 + $i) : null,
                ],
            );
        }
    }

    private function seedNotifikasiAdmin(): void
    {
        $broadcasts = [
            'Siaga Cuaca Ekstrem Maluku' => 'BMKG memprakirakan hujan lebat dan gelombang tinggi. Relawan standby di pulau masing-masing.',
            'Koordinasi Evakuasi Ambon' => 'Titik evakuasi Sirimau dan Nusaniwe dibuka. Relawan Ambon segera ke posko.',
            'Update Laporan Banjir Namlea' => 'Laporan banjir di Namlea sedang ditangani. Relawan Buru dimohon bantu evakuasi.',
            'Briefing Relawan Seram' => 'Briefing koordinasi Tagana Masohi pukul 08.00 WIT di kantor BPBD Masohi.',
            'Siaga Gelombang Laut Kei' => 'Nelayan dan warga pesisir Tual–Elat diimbau tidak melaut sementara.',
            'Logistik Dobo Siap Distribusi' => 'Bantuan logistik tiba di Dobo. Relawan Aru siapkan distribusi ke dusun terdampak.',
            'Pemantauan Gunung Api Banda' => 'Tingkatkan kewaspadaan di Banda Neira. Siapkan jalur evakuasi darat.',
            'Posko Saumlaki Aktif 24 Jam' => 'Posko penanganan di Saumlaki beroperasi 24 jam. Laporkan kebutuhan warga.',
            'Cek Inventaris Ambulans' => 'Seluruh unit ambulans dimohon update status ketersediaan di aplikasi.',
            'Latihan Gabungan SAR Maluku' => 'Simulasi SAR lintas pulau digelar akhir pekan. Relawan terdaftar wajib hadir.',
        ];

        $i = 0;
        foreach ($broadcasts as $judul => $pesan) {
            $semua = $i % 3 !== 0;
            $targets = $semua
                ? $this->akunRelawan
                : [$this->akunRelawan[$i % count($this->akunRelawan)]];

            $notifikasi = NotifikasiAdmin::updateOrCreate(
                ['judul' => $judul],
                [
                    'admin_id' => $this->admin?->id ?? 1,
                    'pesan' => $pesan,
                    'kirim_ke_relawan' => true,
                    'kirim_semua_relawan' => $semua,
                    'akun_relawan_ids' => $semua ? null : collect($targets)->pluck('id')->all(),
                    'status' => 'terkirim',
                    'jumlah_penerima' => 0,
                    'dikirim_at' => now()->subHours(12 - $i),
                ],
            );

            $count = 0;
            foreach ($targets as $akun) {
                NotifikasiAdminPenerima::updateOrCreate(
                    [
                        'notifikasi_admin_id' => $notifikasi->id,
                        'penerima_type' => AkunRelawan::class,
                        'penerima_id' => $akun->id,
                    ],
                    [
                        'sudah_dibaca' => $i % 4 === 0,
                        'dibaca_at' => $i % 4 === 0 ? now()->subHours(1) : null,
                    ],
                );
                $count++;
            }

            $notifikasi->update(['jumlah_penerima' => $count]);
            $i++;
        }
    }

    private function seedNotifikasiAdminPanel(): void
    {
        if (! $this->admin) {
            return;
        }

        $pesan = [
            '10 laporan bencana baru tersebar di pulau-pulau Maluku.',
            'Relawan aktif terdeteksi di Ambon, Buru, Seram, Kei, Aru, Banda, dan Tanimbar.',
            'Faskes demo: 5 fasilitas kesehatan per pulau telah tersedia di peta.',
            'Notifikasi broadcast admin telah dikirim ke seluruh akun relawan demo.',
            'Peringatan dini cuaca ekstrem untuk perairan Maluku — pantau aplikasi.',
            'Penugasan ambulans Namlea dan Masohi sedang dalam perjalanan.',
            'Zona rawan tinggi dipetakan di Sirimau dan Saumlaki.',
            'Materi edukasi kebencanaan Maluku (10 item) siap ditampilkan di app.',
            'Pos SAR Banda Neira melaporkan status siap operasi.',
            'Sinkronisasi data demo Maluku selesai — siap uji aplikasi lapangan.',
        ];

        foreach ($pesan as $body) {
            Notification::make()
                ->title('Update Operasional Maluku')
                ->icon('heroicon-o-bell-alert')
                ->warning()
                ->body($body)
                ->sendToDatabase($this->admin);
        }
    }

    /**
     * Titik acak kecil di sekitar pusat daratan — tetap di dalam jitter pulau.
     *
     * @param  array{lat: float, lng: float, jitter: float}  $meta
     * @return array{0: float, 1: float}
     */
    private function titikDaratan(array $meta, int $salt = 0): array
    {
        // Offset deterministik (bukan random murni) agar re-seed stabil & tetap dekat pusat daratan.
        $angle = deg2rad(($salt * 37) % 360);
        $radius = $meta['jitter'] * (0.25 + (($salt % 5) * 0.12));

        $lat = $meta['lat'] + (sin($angle) * $radius);
        $lng = $meta['lng'] + (cos($angle) * $radius * 1.05);

        return [round($lat, 6), round($lng, 6)];
    }

    /**
     * @return list<array{lat: float, lng: float}>
     */
    private function buatPolygon(float $centerLat, float $centerLng, float $offset = 0.004): array
    {
        $offset = max(0.002, $offset);

        return [
            ['lat' => $centerLat + $offset, 'lng' => $centerLng - $offset],
            ['lat' => $centerLat + $offset, 'lng' => $centerLng + $offset],
            ['lat' => $centerLat - $offset, 'lng' => $centerLng + $offset],
            ['lat' => $centerLat - $offset, 'lng' => $centerLng - $offset],
        ];
    }

    /** @return array{key: string, nama: string, kecamatan: string, kota: string, pulau: string, lat: float, lng: float, jitter: float} */
    private function metaByKey(string $key): array
    {
        foreach (self::WILAYAH as $item) {
            if ($item['key'] === $key) {
                return $item;
            }
        }

        return self::WILAYAH[0];
    }

    /** @return array{key: string, nama: string, kecamatan: string, kota: string, pulau: string, lat: float, lng: float, jitter: float}|null */
    private function metaByWilayahId(int $wilayahId): ?array
    {
        foreach ($this->wilayah as $key => $wilayah) {
            if ($wilayah->id === $wilayahId) {
                return $this->metaByKey($key);
            }
        }

        return null;
    }
}
