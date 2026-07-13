<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AkunRelawan;
use App\Models\Faskes;
use App\Models\Pengguna;
use App\Models\Relawan;
use App\Models\User;
use App\Models\Wilayah;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Data demo Provinsi Maluku — relawan tersebar di berbagai pulau.
 *
 * Jalankan: php artisan db:seed --class=MalukuProvinsiSeeder
 *
 * Password semua akun demo: password123
 */
class MalukuProvinsiSeeder extends Seeder
{
    private const PASSWORD = 'password123';

    private const PROVINSI = 'Maluku';

    /** @var array<string, Wilayah> */
    private array $wilayahKota = [];

    /** @var array<int, AkunRelawan> */
    private array $akunRelawan = [];

    private ?User $admin = null;

    public function run(): void
    {
        $this->admin = User::query()->first();

        $this->seedWilayahKota();
        $this->seedRelawanPerPulau();
        $this->seedFaskesPerKota();

        $this->command?->info('MalukuProvinsiSeeder selesai — data Provinsi Maluku siap digunakan.');
        $this->command?->table(
            ['Entitas', 'Jumlah'],
            [
                ['Kota/Pulau (wilayah)', count($this->wilayahKota)],
                ['Relawan aktif', count($this->akunRelawan)],
                ['Faskes', Faskes::count()],
            ],
        );
        $this->command?->info('Login relawan demo: relawan.ambon@maluku.demo.test, relawan.buru@maluku.demo.test, dll. | password123');
        $this->command?->info('Akun khusus (lokasi unik): relawan.khusus@maluku.demo.test | password123');
    }

    private function seedWilayahKota(): void
    {
        $kota = [
            ['nama' => 'Kota Ambon', 'kecamatan' => 'Sirimau', 'kota' => 'Kota Ambon', 'lat' => -3.6954, 'lng' => 128.1814],
            ['nama' => 'Namlea', 'kecamatan' => 'Namlea', 'kota' => 'Buru', 'lat' => -3.2683, 'lng' => 126.7667],
            ['nama' => 'Namrole', 'kecamatan' => 'Namrole', 'kota' => 'Buru Selatan', 'lat' => -3.8550, 'lng' => 126.7167],
            ['nama' => 'Tual', 'kecamatan' => 'Tual Kota', 'kota' => 'Tual', 'lat' => -5.6417, 'lng' => 132.7472],
            ['nama' => 'Dobo', 'kecamatan' => 'Dobo', 'kota' => 'Dobo', 'lat' => -5.9833, 'lng' => 134.1333],
            ['nama' => 'Elat', 'kecamatan' => 'Elat', 'kota' => 'Kei Besar', 'lat' => -5.7167, 'lng' => 132.6833],
            ['nama' => 'Banda Neira', 'kecamatan' => 'Banda', 'kota' => 'Banda', 'lat' => -4.5267, 'lng' => 129.9044],
            ['nama' => 'Masohi', 'kecamatan' => 'Masohi', 'kota' => 'Seram', 'lat' => -3.3000, 'lng' => 129.3667],
            ['nama' => 'Piru', 'kecamatan' => 'Piru', 'kota' => 'Seram Barat', 'lat' => -3.2000, 'lng' => 128.0833],
            ['nama' => 'Saumlaki', 'kecamatan' => 'Saumlaki', 'kota' => 'Tanimbar', 'lat' => -7.9833, 'lng' => 131.3000],
        ];

        foreach ($kota as $item) {
            $wilayah = Wilayah::updateOrCreate(
                ['nama' => $item['nama'], 'kecamatan' => $item['kecamatan']],
                [
                    'kota' => $item['kota'],
                    'provinsi' => self::PROVINSI,
                    'latitude' => $item['lat'],
                    'longitude' => $item['lng'],
                ],
            );

            $this->wilayahKota[$item['kota']] = $wilayah;
        }
    }

    private function seedRelawanPerPulau(): void
    {
        $relawan = [
            [
                'kota' => 'Kota Ambon', 'name' => 'Andi Relawan Ambon', 'email' => 'relawan.ambon@maluku.demo.test',
                'phone' => '0911400101', 'lat' => -3.6960, 'lng' => 128.1805, 'keahlian' => 'Medis',
            ],
            [
                'kota' => 'Buru', 'name' => 'Budi Relawan Buru', 'email' => 'relawan.buru@maluku.demo.test',
                'phone' => '0911400102', 'lat' => -3.2690, 'lng' => 126.7680, 'keahlian' => 'SAR',
            ],
            [
                'kota' => 'Buru Selatan', 'name' => 'Citra Relawan Namrole', 'email' => 'relawan.namrole@maluku.demo.test',
                'phone' => '0911400103', 'lat' => -3.8565, 'lng' => 126.7180, 'keahlian' => 'Logistik',
            ],
            [
                'kota' => 'Tual', 'name' => 'Dedi Relawan Tual', 'email' => 'relawan.tual@maluku.demo.test',
                'phone' => '0911400104', 'lat' => -5.6425, 'lng' => 132.7485, 'keahlian' => 'Evakuasi',
            ],
            [
                'kota' => 'Dobo', 'name' => 'Eka Relawan Dobo', 'email' => 'relawan.dobo@maluku.demo.test',
                'phone' => '0911400105', 'lat' => -5.9845, 'lng' => 134.1350, 'keahlian' => 'Medis',
            ],
            [
                'kota' => 'Kei Besar', 'name' => 'Fajar Relawan Kei', 'email' => 'relawan.kei@maluku.demo.test',
                'phone' => '0911400106', 'lat' => -5.7180, 'lng' => 132.6850, 'keahlian' => 'SAR',
            ],
            [
                'kota' => 'Banda', 'name' => 'Gita Relawan Banda', 'email' => 'relawan.banda@maluku.demo.test',
                'phone' => '0911400107', 'lat' => -4.5280, 'lng' => 129.9060, 'keahlian' => 'Dapur Umum',
            ],
            [
                'kota' => 'Seram', 'name' => 'Hadi Relawan Seram', 'email' => 'relawan.seram@maluku.demo.test',
                'phone' => '0911400108', 'lat' => -3.3015, 'lng' => 129.3680, 'keahlian' => 'Medis',
            ],
            [
                'kota' => 'Seram Barat', 'name' => 'Indra Relawan Piru', 'email' => 'relawan.piru@maluku.demo.test',
                'phone' => '0911400109', 'lat' => -3.2015, 'lng' => 128.0850, 'keahlian' => 'Evakuasi',
            ],
            [
                'kota' => 'Tanimbar', 'name' => 'Joko Relawan Tanimbar', 'email' => 'relawan.tanimbar@maluku.demo.test',
                'phone' => '0911400110', 'lat' => -7.9850, 'lng' => 131.3020, 'keahlian' => 'Logistik',
            ],
            // Akun khusus dengan lokasi unik (tidak sama dengan relawan lain)
            [
                'kota' => 'Kota Ambon', 'name' => 'Relawan Khusus Demo', 'email' => 'relawan.khusus@maluku.demo.test',
                'phone' => '0911400199', 'lat' => -3.7125, 'lng' => 128.2050, 'keahlian' => 'Koordinator',
            ],
        ];

        foreach ($relawan as $index => $item) {
            $wilayah = $this->wilayahKota[$item['kota']];

            $pengguna = Pengguna::updateOrCreate(
                ['phone' => $item['phone']],
                [
                    'name' => $item['name'],
                    'email' => str_replace('@maluku', '@pengguna.maluku', $item['email']),
                    'password' => Hash::make(self::PASSWORD),
                ],
            );

            $relawanModel = Relawan::updateOrCreate(
                ['pengguna_id' => $pengguna->id],
                [
                    'umur' => 25 + $index,
                    'alamat' => 'Jl. Relawan, '.$item['kota'].', Maluku',
                    'keahlian' => $item['keahlian'],
                    'organisasi' => 'Relawan '.$item['kota'],
                    'status' => 'disetujui',
                    'approved_by' => $this->admin?->id,
                ],
            );

            $this->akunRelawan[] = AkunRelawan::updateOrCreate(
                ['email' => $item['email']],
                [
                    'relawan_id' => $relawanModel->id,
                    'password' => Hash::make(self::PASSWORD),
                    'status' => 'aktif',
                    'latitude' => $item['lat'],
                    'longitude' => $item['lng'],
                    'lokasi_updated_at' => now()->subMinutes(3 + $index),
                    'fcm_token' => 'fcm-demo-'.$item['email'],
                ],
            );
        }
    }

    private function seedFaskesPerKota(): void
    {
        $faskes = [
            ['kota' => 'Kota Ambon', 'nama' => 'RSUD Dr. Haulussy Ambon', 'tipe' => 'rumah_sakit', 'alamat' => 'Jl. Dr. Sutomo, Kudamati, Kota Ambon', 'lat' => -3.6900, 'lng' => 128.1850],
            ['kota' => 'Kota Ambon', 'nama' => 'Puskesmas Kudamati', 'tipe' => 'puskesmas', 'alamat' => 'Kel. Kudamati, Kota Ambon', 'lat' => -3.6885, 'lng' => 128.1810],
            ['kota' => 'Buru', 'nama' => 'RSUD Buru Namlea', 'tipe' => 'rumah_sakit', 'alamat' => 'Jl. Pelabuhan, Namlea, Buru', 'lat' => -3.2695, 'lng' => 126.7690],
            ['kota' => 'Buru Selatan', 'nama' => 'Puskesmas Namrole', 'tipe' => 'puskesmas', 'alamat' => 'Kel. Namrole, Buru Selatan', 'lat' => -3.8570, 'lng' => 126.7190],
            ['kota' => 'Tual', 'nama' => 'RSUD Tual', 'tipe' => 'rumah_sakit', 'alamat' => 'Jl. Merdeka, Tual, Maluku Tenggara', 'lat' => -5.6430, 'lng' => 132.7490],
            ['kota' => 'Dobo', 'nama' => 'Puskesmas Dobo', 'tipe' => 'puskesmas', 'alamat' => 'Jl. Pelabuhan, Dobo, Kepulauan Aru', 'lat' => -5.9850, 'lng' => 134.1360],
            ['kota' => 'Kei Besar', 'nama' => 'RS Kei Besar', 'tipe' => 'rumah_sakit', 'alamat' => 'Jl. Raya Elat, Kei Besar', 'lat' => -5.7190, 'lng' => 132.6860],
            ['kota' => 'Banda', 'nama' => 'Puskesmas Banda Neira', 'tipe' => 'puskesmas', 'alamat' => 'Banda Neira, Maluku Tengah', 'lat' => -4.5290, 'lng' => 129.9070],
            ['kota' => 'Seram', 'nama' => 'RSUD Masohi', 'tipe' => 'rumah_sakit', 'alamat' => 'Jl. Ahmad Yani, Masohi, Seram', 'lat' => -3.3020, 'lng' => 129.3690],
            ['kota' => 'Seram Barat', 'nama' => 'Puskesmas Piru', 'tipe' => 'puskesmas', 'alamat' => 'Kel. Piru, Seram Barat', 'lat' => -3.2020, 'lng' => 128.0860],
            ['kota' => 'Tanimbar', 'nama' => 'Puskesmas Saumlaki', 'tipe' => 'puskesmas', 'alamat' => 'Jl. Raya Saumlaki, Tanimbar', 'lat' => -7.9860, 'lng' => 131.3030],
        ];

        foreach ($faskes as $item) {
            $wilayah = $this->wilayahKota[$item['kota']];

            Faskes::updateOrCreate(
                ['nama' => $item['nama']],
                [
                    'wilayah_id' => $wilayah->id,
                    'admin_id' => $this->admin?->id,
                    'tipe' => $item['tipe'],
                    'alamat' => $item['alamat'],
                    'latitude' => $item['lat'],
                    'longitude' => $item['lng'],
                    'nomor_telepon' => '0911500'.random_int(1000, 9999),
                    'jam_operasional' => '24 Jam',
                ],
            );
        }
    }
}
