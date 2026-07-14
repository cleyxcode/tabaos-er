<?php

namespace Database\Seeders;

use App\Models\AkunRelawan;
use App\Models\Faskes;
use App\Models\LaporanBencana;
use App\Models\Pengguna;
use App\Models\Relawan;
use App\Models\Wilayah;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeder data testing Ambon — 2 laporan + akun masyarakat, relawan, faskes.
 *
 * Jalankan: php artisan db:seed --class=TestingAmbonSeeder
 *
 * ┌─────────────┬──────────────────────────┬──────────────┐
 * │ Role        │ Login                    │ Password     │
 * ├─────────────┼──────────────────────────┼──────────────┤
 * │ Masyarakat  │ 081290000001 / email     │ password123  │
 * │ Relawan     │ relawan.ambon@test.com   │ password123  │
 * │ Faskes      │ faskes.ambon@test.com    │ password123  │
 * └─────────────┴──────────────────────────┴──────────────┘
 *
 * Koordinat referensi (Kota Ambon):
 *  - Relawan GPS : -3.6960, 128.1805  (dekat Lapangan Merdeka)
 *  - Faskes      : -3.6900, 128.1850  (RSUD area)
 *  - Laporan #1  : -3.6958, 128.1810  (Banjir — Lapangan Merdeka)
 *  - Laporan #2  : -3.7020, 128.1750  (Gempa Bumi — area Sirimau)
 */
class TestingAmbonSeeder extends Seeder
{
    private const PASSWORD = 'password123';

    public function run(): void
    {
        $wilayah = Wilayah::firstOrCreate(
            ['nama' => 'Sirimau', 'kecamatan' => 'Sirimau'],
            [
                'kota' => 'Kota Ambon',
                'pulau' => 'Pulau Ambon',
                'provinsi' => 'Maluku',
            ]
        );

        // ── 1. Akun Masyarakat (pelapor) ────────────────────────────────────
        $masyarakat = Pengguna::updateOrCreate(
            ['phone' => '081290000001'],
            [
                'name'     => 'Budi Santoso',
                'email'    => 'masyarakat.ambon@test.com',
                'password' => Hash::make(self::PASSWORD),
            ]
        );

        // ── 2. Akun Relawan ───────────────────────────────────────────────────
        $penggunaRelawan = Pengguna::updateOrCreate(
            ['phone' => '081290000002'],
            [
                'name'     => 'Andi Relawan',
                'email'    => 'andi.relawan@test.com',
                'password' => Hash::make(self::PASSWORD),
            ]
        );

        $relawan = Relawan::updateOrCreate(
            ['pengguna_id' => $penggunaRelawan->id],
            [
                'umur'      => 28,
                'alamat'   => 'Jl. Diponegoro No. 12, Sirimau, Kota Ambon',
                'keahlian' => 'Medis',
                'organisasi' => 'PMI Kota Ambon',
                'status'   => 'disetujui',
            ]
        );

        AkunRelawan::updateOrCreate(
            ['email' => 'relawan.ambon@test.com'],
            [
                'relawan_id'        => $relawan->id,
                'password'          => Hash::make(self::PASSWORD),
                'status'            => 'aktif',
                'latitude'          => -3.6960,
                'longitude'         => 128.1805,
                'lokasi_updated_at' => now(),
            ]
        );

        // ── 3. Akun Faskes ────────────────────────────────────────────────────
        $faskes = Faskes::updateOrCreate(
            ['nama' => 'RSUD Dr. Haulussy Ambon (Testing)'],
            [
                'wilayah_id'      => $wilayah->id,
                'tipe'            => 'rumah_sakit',
                'alamat'          => 'Jl. Dr. Sutomo, Kudamati, Kota Ambon',
                'latitude'        => -3.6900,
                'longitude'       => 128.1850,
                'nomor_telepon'   => '09113411234',
                'jam_operasional' => '24 Jam',
            ]
        );

        // ── 4. Dua Laporan Kejadian di Ambon ────────────────────────────────
        $laporanData = [
            [
                'jenis_kejadian'   => 'Banjir',
                'deskripsi'        => 'Banjir setinggi 50cm di sekitar Lapangan Merdeka akibat hujan deras sejak dini hari. Beberapa rumah warga terendam.',
                'latitude'         => -3.695845,
                'longitude'        => 128.181011,
                'alamat_lokasi'    => 'Lapangan Merdeka, Sirimau, Kota Ambon',
                'meninggal_jumlah' => 0,
                'luka_berat_jumlah' => 1,
                'luka_ringan_jumlah' => 3,
                'hilang_jumlah'    => 0,
            ],
            [
                'jenis_kejadian'   => 'Gempa Bumi',
                'deskripsi'        => 'Getaran gempa dirasakan warga di wilayah Sirimau. Beberapa bangunan mengalami retak ringan, warga berhamburan ke luar rumah.',
                'latitude'         => -3.702000,
                'longitude'        => 128.175000,
                'alamat_lokasi'    => 'Kelurahan Rijali, Sirimau, Kota Ambon',
                'meninggal_jumlah' => 0,
                'luka_berat_jumlah' => 0,
                'luka_ringan_jumlah' => 2,
                'hilang_jumlah'    => 0,
            ],
        ];

        foreach ($laporanData as $data) {
            $existing = LaporanBencana::where('deskripsi', $data['deskripsi'])->first();

            if ($existing) {
                $existing->update(array_merge($data, [
                    'pengguna_id'        => $masyarakat->id,
                    'wilayah_id'         => $wilayah->id,
                    'nama_pelapor'       => $masyarakat->name,
                    'nomor_kontak'       => $masyarakat->phone,
                    'di_lokasi_kejadian' => true,
                    'tanggal_kejadian'   => now()->subHours(2),
                    'status'             => 'pending',
                    'status_penanganan'  => 'belum_ditangani',
                    'foto'               => [],
                ]));
                continue;
            }

            // Tanpa observer agar tidak trigger notifikasi FCM saat seeding
            LaporanBencana::withoutEvents(function () use ($masyarakat, $wilayah, $data) {
                LaporanBencana::create(array_merge($data, [
                    'pengguna_id'        => $masyarakat->id,
                    'wilayah_id'         => $wilayah->id,
                    'nama_pelapor'       => $masyarakat->name,
                    'nomor_kontak'       => $masyarakat->phone,
                    'di_lokasi_kejadian' => true,
                    'tanggal_kejadian'   => now()->subHours(2),
                    'status'             => 'pending',
                    'status_penanganan'  => 'belum_ditangani',
                    'foto'               => [],
                ]));
            });
        }

        $this->command?->info('TestingAmbonSeeder selesai.');
        $this->command?->table(
            ['Role', 'Kredensial', 'Password'],
            [
                ['Masyarakat', 'Phone: 081290000001 | Email: masyarakat.ambon@test.com', self::PASSWORD],
                ['Relawan',    'Email: relawan.ambon@test.com', self::PASSWORD],
                ['Faskes',     'Email: faskes.ambon@test.com', self::PASSWORD],
            ]
        );
        $this->command?->info('2 laporan bencana dibuat di Kota Ambon (radius < 10 km dari relawan).');
    }
}
