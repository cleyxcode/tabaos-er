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
 * Data demo lengkap Kota Ambon, Maluku — minimal 5 record per entitas.
 *
 * Jalankan: php artisan db:seed --class=AmbonDemoSeeder
 *
 * Password semua akun demo: password123
 */
class AmbonDemoSeeder extends Seeder
{
    private const PASSWORD = 'password123';

    /** @var array<int, Wilayah> */
    private array $wilayah = [];

    /** @var array<int, Pengguna> */
    private array $pengguna = [];

    /** @var array<int, Relawan> */
    private array $relawan = [];

    /** @var array<int, AkunRelawan> */
    private array $akunRelawan = [];

    /** @var array<int, Faskes> */
    private array $faskes = [];

    /** @var array<int, LaporanBencana> */
    private array $laporan = [];

    /** @var array<int, ZonaRawanBencana> */
    private array $zona = [];

    /** @var array<int, Ambulans> */
    private array $ambulans = [];

    private ?User $admin = null;

    public function run(): void
    {
        $this->admin = User::query()->first();

        $this->seedWilayah();
        $this->seedPengguna();
        $this->seedRelawan();
        $this->seedFaskes();
        $this->seedLaporan();
        $this->seedPetugasEmergency();
        $this->seedZonaRawan();
        $this->seedTitikEvakuasi();
        $this->seedAmbulans();
        $this->seedPenugasan();
        $this->seedPedoman();
        $this->seedRelawanNotifikasi();
        $this->seedNotifikasiAdmin();
        $this->seedNotifikasiAdminPanel();

        $this->command?->info('AmbonDemoSeeder selesai — data demo Kota Ambon siap digunakan.');
        $this->command?->table(
            ['Entitas', 'Jumlah'],
            [
                ['Wilayah', count($this->wilayah)],
                ['Pengguna / Masyarakat', count($this->pengguna)],
                ['Relawan + Akun', count($this->akunRelawan)],
                ['Faskes + Akun', count($this->akunFaskes)],
                ['Laporan Bencana', count($this->laporan)],
                ['Petugas Emergency', 5],
                ['Zona Rawan', count($this->zona)],
                ['Titik Evakuasi', 5],
                ['Ambulans', count($this->ambulans)],
                ['Penugasan', 5],
                ['Pedoman BHD', 5],
                ['Notifikasi Relawan', 6],
                ['Notifikasi Admin (broadcast)', 5],
            ],
        );
        $this->command?->info('Login demo relawan: relawan1.ambon@demo.test s/d relawan5.ambon@demo.test | password123');
        $this->command?->info('Login demo faskes: faskes1.ambon@demo.test s/d faskes5.ambon@demo.test | password123');
    }

    private function seedWilayah(): void
    {
        $data = [
            ['nama' => 'Sirimau', 'kecamatan' => 'Sirimau', 'kota' => 'Kota Ambon'],
            ['nama' => 'Nusaniwe', 'kecamatan' => 'Nusaniwe', 'kota' => 'Kota Ambon'],
            ['nama' => 'Teluk Ambon', 'kecamatan' => 'Teluk Ambon', 'kota' => 'Kota Ambon'],
            ['nama' => 'Baguala', 'kecamatan' => 'Baguala', 'kota' => 'Kota Ambon'],
            ['nama' => 'Leitimur Selatan', 'kecamatan' => 'Leitimur Selatan', 'kota' => 'Kota Ambon'],
        ];

        foreach ($data as $item) {
            $this->wilayah[] = Wilayah::firstOrCreate(
                ['nama' => $item['nama'], 'kecamatan' => $item['kecamatan']],
                ['kota' => $item['kota']],
            );
        }
    }

    private function seedPengguna(): void
    {
        $data = [
            ['name' => 'Budi Santoso', 'phone' => '0911330101', 'email' => 'budi.ambon@demo.test'],
            ['name' => 'Siti Rahayu', 'phone' => '0911330102', 'email' => 'siti.ambon@demo.test'],
            ['name' => 'Yusuf Latuhalat', 'phone' => '0911330103', 'email' => 'yusuf.ambon@demo.test'],
            ['name' => 'Maria Pattinama', 'phone' => '0911330104', 'email' => 'maria.ambon@demo.test'],
            ['name' => 'Jonis Pelu', 'phone' => '0911330105', 'email' => 'jonis.ambon@demo.test'],
            ['name' => 'Helena Soumokil', 'phone' => '0911330106', 'email' => 'helena.ambon@demo.test'],
        ];

        foreach ($data as $item) {
            $this->pengguna[] = Pengguna::updateOrCreate(
                ['phone' => $item['phone']],
                [
                    'name' => $item['name'],
                    'email' => $item['email'],
                    'password' => Hash::make(self::PASSWORD),
                ],
            );
        }
    }

    private function seedRelawan(): void
    {
        $data = [
            [
                'name' => 'Andi Relawan', 'phone' => '0911340201', 'email' => 'relawan1.ambon@demo.test',
                'umur' => 28, 'keahlian' => 'Medis', 'organisasi' => 'PMI Kota Ambon',
                'alamat' => 'Jl. Diponegoro, Sirimau', 'lat' => -3.6960, 'lng' => 128.1805,
            ],
            [
                'name' => 'Rina Wijaya', 'phone' => '0911340202', 'email' => 'relawan2.ambon@demo.test',
                'umur' => 32, 'keahlian' => 'SAR', 'organisasi' => 'Basarnas Ambon',
                'alamat' => 'Jl. A.Y. Patty, Nusaniwe', 'lat' => -3.6928, 'lng' => 128.1688,
            ],
            [
                'name' => 'Ferdinan Tuha', 'phone' => '0911340203', 'email' => 'relawan3.ambon@demo.test',
                'umur' => 25, 'keahlian' => 'Logistik', 'organisasi' => 'Tagana Maluku',
                'alamat' => 'Kel. Batu Merah, Sirimau', 'lat' => -3.7012, 'lng' => 128.1925,
            ],
            [
                'name' => 'Grace Latupeirissa', 'phone' => '0911340204', 'email' => 'relawan4.ambon@demo.test',
                'umur' => 30, 'keahlian' => 'Dapur Umum', 'organisasi' => 'Relawan Nusantara Ambon',
                'alamat' => 'Jl. Dr. Sutomo, Kudamati', 'lat' => -3.6878, 'lng' => 128.1832,
            ],
            [
                'name' => 'Samuel Hehanussa', 'phone' => '0911340205', 'email' => 'relawan5.ambon@demo.test',
                'umur' => 27, 'keahlian' => 'Evakuasi', 'organisasi' => 'Linmas Sirimau',
                'alamat' => 'Kel. Rijali, Sirimau', 'lat' => -3.7025, 'lng' => 128.1758,
            ],
        ];

        foreach ($data as $index => $item) {
            $pengguna = Pengguna::updateOrCreate(
                ['phone' => $item['phone']],
                [
                    'name' => $item['name'],
                    'email' => str_replace('relawan', 'pengguna.relawan', $item['email']),
                    'password' => Hash::make(self::PASSWORD),
                ],
            );

            $relawan = Relawan::updateOrCreate(
                ['pengguna_id' => $pengguna->id],
                [
                    'umur' => $item['umur'],
                    'alamat' => $item['alamat'],
                    'keahlian' => $item['keahlian'],
                    'organisasi' => $item['organisasi'],
                    'status' => 'disetujui',
                    'approved_by' => $this->admin?->id,
                ],
            );

            $this->relawan[] = $relawan;
            $this->akunRelawan[] = AkunRelawan::updateOrCreate(
                ['email' => $item['email']],
                [
                    'relawan_id' => $relawan->id,
                    'password' => Hash::make(self::PASSWORD),
                    'status' => 'aktif',
                    'latitude' => $item['lat'],
                    'longitude' => $item['lng'],
                    'lokasi_updated_at' => now()->subMinutes(5 + $index),
                ],
            );
        }
    }

    private function seedFaskes(): void
    {
        $data = [
            [
                'nama' => 'RSUD Dr. Haulussy Ambon', 'tipe' => 'rumah_sakit',
                'alamat' => 'Jl. Dr. Sutomo, Kudamati, Kota Ambon', 'lat' => -3.6900, 'lng' => 128.1850,
                'telepon' => '09113411234', 'email' => 'faskes1.ambon@demo.test', 'petugas' => 'Dr. Sari Wijaya',
            ],
            [
                'nama' => 'RS Stella Maris Ambon', 'tipe' => 'rumah_sakit',
                'alamat' => 'Jl. A.Y. Patty, Nusaniwe, Kota Ambon', 'lat' => -3.6935, 'lng' => 128.1720,
                'telepon' => '09113555678', 'email' => 'faskes2.ambon@demo.test', 'petugas' => 'Dr. Michael Pattinama',
            ],
            [
                'nama' => 'Puskesmas Kudamati', 'tipe' => 'puskesmas',
                'alamat' => 'Kel. Kudamati, Nusaniwe, Kota Ambon', 'lat' => -3.6885, 'lng' => 128.1810,
                'telepon' => '09113666789', 'email' => 'faskes3.ambon@demo.test', 'petugas' => 'Bidan Merry Soumokil',
            ],
            [
                'nama' => 'Puskesmas Batu Merah', 'tipe' => 'puskesmas',
                'alamat' => 'Kel. Batu Merah, Sirimau, Kota Ambon', 'lat' => -3.7005, 'lng' => 128.1910,
                'telepon' => '09113777890', 'email' => 'faskes4.ambon@demo.test', 'petugas' => 'Dr. Anton Latuhalat',
            ],
            [
                'nama' => 'Apotek Kimia Farma Ambon', 'tipe' => 'apotek',
                'alamat' => 'Jl. Diponegoro, Sirimau, Kota Ambon', 'lat' => -3.6968, 'lng' => 128.1795,
                'telepon' => '09113888901', 'email' => 'faskes5.ambon@demo.test', 'petugas' => 'Apt. Dewi Hehanussa',
            ],
        ];

        foreach ($data as $index => $item) {
            $faskes = Faskes::updateOrCreate(
                ['nama' => $item['nama']],
                [
                    'wilayah_id' => $this->wilayah[$index % count($this->wilayah)]->id,
                    'admin_id' => $this->admin?->id,
                    'tipe' => $item['tipe'],
                    'alamat' => $item['alamat'],
                    'latitude' => $item['lat'],
                    'longitude' => $item['lng'],
                    'nomor_telepon' => $item['telepon'],
                    'jam_operasional' => '24 Jam',
                ],
            );

            $this->faskes[] = $faskes;
        }
    }

    private function seedLaporan(): void
    {
        $data = [
            [
                'jenis' => 'Banjir', 'pelapor' => 0, 'wilayah' => 0,
                'lat' => -3.695845, 'lng' => 128.181011,
                'alamat' => 'Lapangan Merdeka, Sirimau, Kota Ambon',
                'deskripsi' => 'Banjir setinggi 40 cm di sekitar Lapangan Merdeka akibat hujan deras sejak dini hari.',
                'status' => 'ditangani', 'penanganan' => 'sedang_ditangani', 'relawan' => 0,
            ],
            [
                'jenis' => 'Gempa Bumi', 'pelapor' => 1, 'wilayah' => 0,
                'lat' => -3.702000, 'lng' => 128.175000,
                'alamat' => 'Kel. Rijali, Sirimau, Kota Ambon',
                'deskripsi' => 'Getaran gempa dirasakan kuat di wilayah Rijali, beberapa rumah retak ringan.',
                'status' => 'diverifikasi', 'penanganan' => 'belum_ditangani', 'relawan' => null,
            ],
            [
                'jenis' => 'Kebakaran', 'pelapor' => 2, 'wilayah' => 1,
                'lat' => -3.691500, 'lng' => 128.170500,
                'alamat' => 'Pasar Mardika, Nusaniwe, Kota Ambon',
                'deskripsi' => 'Kebakaran di kios pasar Mardika, api sudah padam namun ada kerusakan materiil.',
                'status' => 'ditangani', 'penanganan' => 'sedang_ditangani', 'relawan' => 1,
            ],
            [
                'jenis' => 'Tanah Longsor', 'pelapor' => 3, 'wilayah' => 2,
                'lat' => -3.705500, 'lng' => 128.165000,
                'alamat' => 'Jl. Halong, Teluk Ambon, Kota Ambon',
                'deskripsi' => 'Longsor menutup akses jalan utama menuju pelabuhan kecil Halong.',
                'status' => 'pending', 'penanganan' => 'belum_ditangani', 'relawan' => null,
            ],
            [
                'jenis' => 'Tsunami', 'pelapor' => 4, 'wilayah' => 3,
                'lat' => -3.678000, 'lng' => 128.195000,
                'alamat' => 'Pantai Galala, Baguala, Kota Ambon',
                'deskripsi' => 'Gelombang laut tinggi di pesisir Galala, warga diimbau evakuasi ke dataran tinggi.',
                'status' => 'diverifikasi', 'penanganan' => 'sedang_ditangani', 'relawan' => 2,
            ],
            [
                'jenis' => 'Lainnya', 'pelapor' => 5, 'wilayah' => 4,
                'lat' => -3.710000, 'lng' => 128.160000,
                'alamat' => 'Kel. Waihoka, Leitimur Selatan, Kota Ambon',
                'deskripsi' => 'Pohon tumbang menimpa jalan akibat angin kencang, arus lalu lintas terganggu.',
                'status' => 'pending', 'penanganan' => 'belum_ditangani', 'relawan' => null,
            ],
        ];

        foreach ($data as $item) {
            $pengguna = $this->pengguna[$item['pelapor']];
            $akunRelawanId = $item['relawan'] !== null
                ? $this->akunRelawan[$item['relawan']]->id
                : null;

            $laporan = LaporanBencana::withoutEvents(function () use ($item, $pengguna, $akunRelawanId) {
                return LaporanBencana::updateOrCreate(
                    [
                        'nama_pelapor' => $pengguna->name,
                        'jenis_kejadian' => $item['jenis'],
                        'alamat_lokasi' => $item['alamat'],
                    ],
                    [
                        'pengguna_id' => $pengguna->id,
                        'wilayah_id' => $this->wilayah[$item['wilayah']]->id,
                        'nomor_kontak' => $pengguna->phone,
                        'di_lokasi_kejadian' => true,
                        'latitude' => $item['lat'],
                        'longitude' => $item['lng'],
                        'tanggal_kejadian' => now()->subHours(random_int(1, 48)),
                        'deskripsi' => $item['deskripsi'],
                        'foto' => [],
                        'meninggal_jumlah' => 0,
                        'hilang_jumlah' => 0,
                        'luka_berat_jumlah' => random_int(0, 2),
                        'luka_ringan_jumlah' => random_int(1, 5),
                        'status' => $item['status'],
                        'status_penanganan' => $item['penanganan'],
                        'akun_relawan_ditugaskan' => $akunRelawanId,
                        'verified_by' => in_array($item['status'], ['diverifikasi', 'ditangani'], true)
                            ? $this->admin?->id
                            : null,
                        'verified_at' => in_array($item['status'], ['diverifikasi', 'ditangani'], true)
                            ? now()->subHours(1)
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
            ['nama' => 'Tim SAR Ambon', 'kategori' => 'sar', 'lat' => -3.6945, 'lng' => 128.1780, 'alamat' => 'Kantor Basarnas Ambon, Sirimau'],
            ['nama' => 'Unit Medis BPBD Maluku', 'kategori' => 'medis', 'lat' => -3.6970, 'lng' => 128.1820, 'alamat' => 'Kantor BPBD Prov. Maluku, Sirimau'],
            ['nama' => 'Tim Logistik PMI Ambon', 'kategori' => 'logistik', 'lat' => -3.6990, 'lng' => 128.1840, 'alamat' => 'Kantor PMI Kota Ambon'],
            ['nama' => 'Petugas Damkar Ambon', 'kategori' => 'lainnya', 'lat' => -3.6880, 'lng' => 128.1760, 'alamat' => 'Dinas Damkar Kota Ambon, Nusaniwe'],
            ['nama' => 'Tim Evakuasi Teluk Ambon', 'kategori' => 'sar', 'lat' => -3.7060, 'lng' => 128.1620, 'alamat' => 'Posko Teluk Ambon'],
        ];

        foreach ($data as $index => $item) {
            PetugasEmergency::updateOrCreate(
                ['nama' => $item['nama']],
                [
                    'kategori' => $item['kategori'],
                    'nomor_telepon' => '091139990' . str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT),
                    'latitude' => $item['lat'],
                    'longitude' => $item['lng'],
                    'alamat' => $item['alamat'],
                    'status' => 'aktif',
                ],
            );
        }
    }

    private function seedZonaRawan(): void
    {
        $centers = [
            ['nama' => 'Zona Banjir Sirimau', 'risiko' => 'tinggi', 'lat' => -3.6960, 'lng' => 128.1810, 'wilayah' => 0],
            ['nama' => 'Zona Longsor Batu Merah', 'risiko' => 'tinggi', 'lat' => -3.7010, 'lng' => 128.1920, 'wilayah' => 0],
            ['nama' => 'Zona Tsunami Pantai Galala', 'risiko' => 'sedang', 'lat' => -3.6780, 'lng' => 128.1950, 'wilayah' => 3],
            ['nama' => 'Zona Gempa Nusaniwe', 'risiko' => 'sedang', 'lat' => -3.6930, 'lng' => 128.1720, 'wilayah' => 1],
            ['nama' => 'Zona Kebakaran Pasar Mardika', 'risiko' => 'rendah', 'lat' => -3.6915, 'lng' => 128.1705, 'wilayah' => 1],
        ];

        foreach ($centers as $item) {
            $this->zona[] = ZonaRawanBencana::updateOrCreate(
                ['nama_zona' => $item['nama']],
                [
                    'wilayah_id' => $this->wilayah[$item['wilayah']]->id,
                    'created_by' => $this->admin?->id,
                    'tingkat_risiko' => $item['risiko'],
                    'polygon' => $this->buatPolygon($item['lat'], $item['lng']),
                    'deskripsi' => 'Zona rawan bencana di wilayah ' . $item['nama'] . ', Kota Ambon.',
                ],
            );
        }
    }

    private function seedTitikEvakuasi(): void
    {
        $data = [
            ['nama' => 'Titik Evakuasi Lapangan Merdeka', 'zona' => 0, 'lat' => -3.6952, 'lng' => 128.1808, 'kapasitas' => 500],
            ['nama' => 'Titik Evakuasi GOR Amahusu', 'zona' => 3, 'lat' => -3.6775, 'lng' => 128.1945, 'kapasitas' => 800],
            ['nama' => 'Titik Evakuasi Masjid Al-Fatah', 'zona' => 1, 'lat' => -3.7008, 'lng' => 128.1915, 'kapasitas' => 300],
            ['nama' => 'Titik Evakuasi Kantor Camat Nusaniwe', 'zona' => 3, 'lat' => -3.6920, 'lng' => 128.1710, 'kapasitas' => 250],
            ['nama' => 'Titik Evakuasi SD Inpres Halong', 'zona' => 2, 'lat' => -3.7058, 'lng' => 128.1645, 'kapasitas' => 400],
        ];

        foreach ($data as $item) {
            TitikEvakuasi::updateOrCreate(
                ['nama' => $item['nama']],
                [
                    'zona_id' => $this->zona[$item['zona']]->id,
                    'latitude' => $item['lat'],
                    'longitude' => $item['lng'],
                    'kapasitas' => $item['kapasitas'],
                    'fasilitas' => 'Tenda, dapur umum, air bersih, toilet darurat',
                ],
            );
        }
    }

    private function seedAmbulans(): void
    {
        foreach ($this->faskes as $index => $faskes) {
            $this->ambulans[] = Ambulans::updateOrCreate(
                ['faskes_id' => $faskes->id, 'nama_layanan' => 'Ambulans ' . $faskes->nama],
                [
                    'nomor_telepon' => '091138880' . str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT),
                    'status' => $index % 2 === 0 ? 'tersedia' : 'tidak_tersedia',
                    'jenis_layanan' => $index === 4 ? 'berbayar' : 'gratis',
                ],
            );
        }
    }

    private function seedPenugasan(): void
    {
        for ($i = 0; $i < 5; $i++) {
            Penugasan::updateOrCreate(
                [
                    'laporan_id' => $this->laporan[$i]->id,
                    'relawan_id' => $this->relawan[$i]->id,
                ],
                [
                    'petugas_id' => $this->admin?->id,
                    'ambulans_id' => $this->ambulans[$i]->id,
                    'status' => match ($i) {
                        0, 2, 4 => 'dalam_perjalanan',
                        1 => 'ditugaskan',
                        default => 'selesai',
                    },
                    'catatan' => 'Penugasan demo penanganan bencana di Kota Ambon.',
                    'ditugaskan_at' => now()->subHours(3 + $i),
                ],
            );
        }
    }

    private function seedPedoman(): void
    {
        $data = [
            ['judul' => 'Pedoman Evakuasi Tsunami Maluku', 'tipe' => 'pdf'],
            ['judul' => 'SOP Penanganan Banjir Bandang', 'tipe' => 'dokumen'],
            ['judul' => 'Video Simulasi Gempa Bumi', 'tipe' => 'video'],
            ['judul' => 'Infografis Titik Kumpul Ambon', 'tipe' => 'gambar'],
            ['judul' => 'Panduan Pertolongan Pertama Darurat', 'tipe' => 'pdf'],
        ];

        foreach ($data as $index => $item) {
            DB::table('pedoman_bhd')->updateOrInsert(
                ['judul' => $item['judul']],
                [
                    'tipe_file' => $item['tipe'],
                    'deskripsi' => 'Materi pedoman bencana demo untuk wilayah Kota Ambon dan Maluku.',
                    'file_path' => 'https://example.com/pedoman-ambon-' . ($index + 1) . '.' . ($item['tipe'] === 'video' ? 'mp4' : 'pdf'),
                    'uploaded_by' => $this->admin?->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }
    }

    private function seedRelawanNotifikasi(): void
    {
        foreach ($this->laporan as $index => $laporan) {
            $akun = $this->akunRelawan[$index % count($this->akunRelawan)];

            RelawanNotifikasi::updateOrCreate(
                [
                    'akun_relawan_id' => $akun->id,
                    'laporan_id' => $laporan->id,
                ],
                [
                    'sudah_dibaca' => $index % 2 === 0,
                    'dibaca_at' => $index % 2 === 0 ? now()->subMinutes(30) : null,
                ],
            );
        }
    }

    private function seedNotifikasiAdmin(): void
    {
        $broadcasts = [
            [
                'judul' => 'Siaga Bencana Kota Ambon',
                'pesan' => 'Cuaca ekstrem diprakirakan 24 jam ke depan. Seluruh relawan diimbau standby di wilayah masing-masing.',
                'relawan' => true, 'faskes' => true, 'semua_relawan' => true, 'semua_faskes' => true,
            ],
            [
                'judul' => 'Koordinasi Evakuasi Sirimau',
                'pesan' => 'Titik evakuasi Lapangan Merdeka sudah dibuka. Faskes dimohon siapkan tempat tidur darurat.',
                'relawan' => true, 'semua_relawan' => true,
            ],
            [
                'judul' => 'Briefing Relawan PMI',
                'pesan' => 'Briefing koordinasi relawan PMI Ambon pukul 08.00 WIT di kantor PMI.',
                'relawan' => true, 'semua_relawan' => true,
            ],
            [
                'judul' => 'Update Laporan Banjir Merdeka',
                'pesan' => 'Laporan banjir di Lapangan Merdeka sedang ditangani. Relawan terdekat dimohon bantu evakuasi warga.',
                'relawan' => true, 'semua_relawan' => false,
            ],
        ];

        foreach ($broadcasts as $index => $item) {
            $akunRelawanIds = $item['semua_relawan']
                ? collect($this->akunRelawan)->pluck('id')->all()
                : [collect($this->akunRelawan)->pluck('id')->first()];

            $notifikasi = NotifikasiAdmin::updateOrCreate(
                ['judul' => $item['judul']],
                [
                    'admin_id' => $this->admin?->id ?? 1,
                    'pesan' => $item['pesan'],
                    'kirim_ke_relawan' => $item['relawan'],
                    'kirim_semua_relawan' => $item['semua_relawan'],
                    'akun_relawan_ids' => $item['relawan'] && ! $item['semua_relawan'] ? $akunRelawanIds : null,
                    'status' => 'terkirim',
                    'jumlah_penerima' => 0,
                    'dikirim_at' => now()->subHours(6 - $index),
                ],
            );

            $penerimaCount = 0;

            if ($item['relawan']) {
                $targets = $item['semua_relawan'] ? $this->akunRelawan : [AkunRelawan::find($akunRelawanIds[0])];
                foreach ($targets as $akun) {
                    if (! $akun) {
                        continue;
                    }
                    NotifikasiAdminPenerima::updateOrCreate(
                        [
                            'notifikasi_admin_id' => $notifikasi->id,
                            'penerima_type' => AkunRelawan::class,
                            'penerima_id' => $akun->id,
                        ],
                        [
                            'sudah_dibaca' => $index % 3 === 0,
                            'dibaca_at' => $index % 3 === 0 ? now()->subHours(1) : null,
                        ],
                    );
                    $penerimaCount++;
                }
            }

            $notifikasi->update(['jumlah_penerima' => $penerimaCount]);
        }
    }

    private function seedNotifikasiAdminPanel(): void
    {
        if (! $this->admin) {
            return;
        }

        $pesan = [
            'Laporan banjir di Lapangan Merdeka masuk — 3 korban luka ringan.',
            'Relawan Andi Relawan sudah ditugaskan ke laporan kebakaran Pasar Mardika.',
            '5 relawan aktif terdeteksi di peta realtime Kota Ambon.',
            'RSUD Dr. Haulussy melaporkan ketersediaan 12 tempat tidur darurat.',
            'Peringatan dini cuaca ekstrem Maluku — waspada gelombang tinggi.',
        ];

        foreach ($pesan as $body) {
            Notification::make()
                ->title('Update Penanganan Bencana Ambon')
                ->icon('heroicon-o-bell-alert')
                ->warning()
                ->body($body)
                ->sendToDatabase($this->admin);
        }
    }

    /**
     * @return list<array{lat: float, lng: float}>
     */
    private function buatPolygon(float $centerLat, float $centerLng): array
    {
        $offset = 0.004;

        return [
            ['lat' => $centerLat + $offset, 'lng' => $centerLng - $offset],
            ['lat' => $centerLat + $offset, 'lng' => $centerLng + $offset],
            ['lat' => $centerLat - $offset, 'lng' => $centerLng + $offset],
            ['lat' => $centerLat - $offset, 'lng' => $centerLng - $offset],
        ];
    }
}
