<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Faker\Factory as Faker;
use App\Models\User;

class SampleDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create('id_ID');
        
        $adminUser = User::first();
        $adminUserId = $adminUser ? $adminUser->id : null;

        // 1. Wilayah
        $wilayahIds = [];
        for ($i = 0; $i < 10; $i++) {
            $wilayahIds[] = DB::table('wilayah')->insertGetId([
                'nama' => $faker->streetName,
                'kecamatan' => 'Kecamatan ' . $faker->citySuffix,
                'kota' => 'Kota Ambon',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 2. Pengguna
        $penggunaIds = [];
        for ($i = 0; $i < 10; $i++) {
            $penggunaIds[] = DB::table('pengguna')->insertGetId([
                'name' => $faker->name,
                'phone' => '08' . $faker->randomNumber(8, true),
                'email' => $faker->unique()->safeEmail,
                'password' => Hash::make('password123'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 3. Laporan Bencana
        $laporanIds = [];
        for ($i = 0; $i < 10; $i++) {
            $laporanIds[] = DB::table('laporan_bencana')->insertGetId([
                'pengguna_id' => $faker->randomElement($penggunaIds),
                'wilayah_id' => $faker->randomElement($wilayahIds),
                'nama_pelapor' => $faker->name,
                'nomor_kontak' => '08' . $faker->randomNumber(8, true),
                'jenis_kejadian' => $faker->randomElement(['Banjir', 'Tanah Longsor', 'Gempa Bumi', 'Kebakaran', 'Tsunami']),
                'di_lokasi_kejadian' => $faker->boolean,
                'latitude' => $faker->latitude(-3.75, -3.60),
                'longitude' => $faker->longitude(128.10, 128.25),
                'alamat_lokasi' => $faker->address,
                'tanggal_kejadian' => $faker->dateTimeThisYear(),
                'deskripsi' => $faker->paragraph,
                'foto' => json_encode([$faker->imageUrl(640, 480, 'disaster')]),
                'status' => $faker->randomElement(['pending', 'diverifikasi', 'ditangani', 'selesai']),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 4. Relawan
        $relawanIds = [];
        foreach ($penggunaIds as $penggunaId) {
            $relawanIds[] = DB::table('relawan')->insertGetId([
                'pengguna_id' => $penggunaId,
                'umur' => $faker->numberBetween(20, 55),
                'alamat' => $faker->address,
                'keahlian' => $faker->randomElement(['Medis', 'Logistik', 'SAR', 'Dapur Umum']),
                'status' => $faker->randomElement(['pending', 'disetujui', 'ditolak']),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 5. Petugas Emergency
        $petugasIds = [];
        for ($i = 0; $i < 10; $i++) {
            $petugasIds[] = DB::table('petugas_emergency')->insertGetId([
                'nama' => $faker->name,
                'kategori' => $faker->randomElement(['medis', 'sar', 'logistik', 'lainnya']),
                'nomor_telepon' => '08' . $faker->randomNumber(8, true),
                'latitude' => $faker->latitude(-3.75, -3.60),
                'longitude' => $faker->longitude(128.10, 128.25),
                'alamat' => $faker->address,
                'status' => 'aktif',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 6. Faskes
        $faskesIds = [];
        for ($i = 0; $i < 10; $i++) {
            $faskesIds[] = DB::table('faskes')->insertGetId([
                'wilayah_id' => $faker->randomElement($wilayahIds),
                'admin_id' => $adminUserId,
                'nama' => 'Faskes ' . $faker->company,
                'tipe' => $faker->randomElement(['rumah_sakit', 'puskesmas', 'apotek']),
                'alamat' => $faker->address,
                'latitude' => $faker->latitude(-3.75, -3.60),
                'longitude' => $faker->longitude(128.10, 128.25),
                'nomor_telepon' => '08' . $faker->randomNumber(8, true),
                'jam_operasional' => '24 Jam',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 7. Ambulans
        $ambulansIds = [];
        for ($i = 0; $i < 10; $i++) {
            $ambulansIds[] = DB::table('ambulans')->insertGetId([
                'faskes_id' => $faker->randomElement($faskesIds),
                'nama_layanan' => 'Ambulans ' . $faker->companySuffix,
                'nomor_telepon' => '08' . $faker->randomNumber(8, true),
                'status' => $faker->randomElement(['tersedia', 'tidak_tersedia']),
                'jenis_layanan' => $faker->randomElement(['gratis', 'berbayar']),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 8. Zona Rawan Bencana
        $zonaIds = [];
        for ($i = 0; $i < 10; $i++) {
            $zonaIds[] = DB::table('zona_rawan_bencana')->insertGetId([
                'wilayah_id' => $faker->randomElement($wilayahIds),
                'created_by' => $adminUserId,
                'nama_zona' => 'Zona Rawan ' . $faker->city,
                'tingkat_risiko' => $faker->randomElement(['tinggi', 'sedang', 'rendah']),
                'polygon' => json_encode([
                    ['lat' => $faker->latitude(-3.75, -3.60), 'lng' => $faker->longitude(128.10, 128.25)],
                    ['lat' => $faker->latitude(-3.75, -3.60), 'lng' => $faker->longitude(128.10, 128.25)],
                    ['lat' => $faker->latitude(-3.75, -3.60), 'lng' => $faker->longitude(128.10, 128.25)],
                ]),
                'deskripsi' => $faker->sentence,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 9. Titik Evakuasi
        $titikIds = [];
        for ($i = 0; $i < 10; $i++) {
            $titikIds[] = DB::table('titik_evakuasi')->insertGetId([
                'zona_id' => $faker->randomElement($zonaIds),
                'nama' => 'Titik Evakuasi ' . $faker->streetName,
                'latitude' => $faker->latitude(-3.75, -3.60),
                'longitude' => $faker->longitude(128.10, 128.25),
                'kapasitas' => $faker->numberBetween(50, 500),
                'fasilitas' => 'Tenda, Dapur Umum, Air Bersih',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 10. Penugasan
        for ($i = 0; $i < 10; $i++) {
            DB::table('penugasan')->insert([
                'laporan_id' => $faker->randomElement($laporanIds),
                'relawan_id' => $faker->boolean ? $faker->randomElement($relawanIds) : null,
                'petugas_id' => null, 
                'ambulans_id' => $faker->boolean ? $faker->randomElement($ambulansIds) : null,
                'status' => $faker->randomElement(['ditugaskan', 'dalam_perjalanan', 'selesai', 'dibatalkan']),
                'catatan' => $faker->sentence,
                'ditugaskan_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 11. Pedoman BHD
        for ($i = 0; $i < 10; $i++) {
            DB::table('pedoman_bhd')->insert([
                'judul' => 'Pedoman ' . $faker->words(3, true),
                'tipe_file' => $faker->randomElement(['pdf', 'video', 'gambar', 'dokumen']),
                'deskripsi' => $faker->paragraph,
                'file_path' => 'https://example.com/file-' . $i . '.pdf',
                'uploaded_by' => $adminUserId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}

