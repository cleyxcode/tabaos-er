<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\AkunRelawan;
use App\Models\Faskes;
use App\Models\LaporanBencana;
use App\Models\Pengguna;
use App\Models\Relawan;
use App\Models\RelawanNotifikasi;
use App\Models\Wilayah;
use App\Services\HaversineService;
use App\Services\RelawanPenugasanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Uji skala Maluku: banyak wilayah, ~5–6 relawan & 3 laporan per wilayah.
 * Memastikan:
 * - tiap laporan ditugaskan ke 1 relawan terdekat di daerah yang sama
 * - relawan jauh / daerah lain tidak melihat laporan tersebut
 * - faskes masyarakat diurut jarak & dibatasi pulau
 */
final class TrackingLokasiSkalaBesarApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @var array<string, array{wilayah: Wilayah, center: array{lat: float, lng: float}, relawan: list<AkunRelawan>, laporan: list<LaporanBencana>}>
     */
    private array $daerah = [];

    private HaversineService $haversine;

    private RelawanPenugasanService $penugasan;

    protected function setUp(): void
    {
        parent::setUp();

        // Skala besar melebihi limit throttle:api default — fokus uji logika lokasi.
        $this->withoutMiddleware(ThrottleRequests::class);

        $this->haversine = app(HaversineService::class);
        $this->penugasan = app(RelawanPenugasanService::class);
        $this->seedDatasetMaluku();
    }

    public function testSetiapLaporanHanyaDitugaskanKeSatuRelawanTerdekatDiDaerahSama(): void
    {
        foreach ($this->daerah as $namaDaerah => $info) {
            foreach ($info['laporan'] as $index => $laporan) {
                $hasil = $this->penugasan->tugaskanRelawanTerdekat($laporan);
                $this->assertNotNull($hasil, "Laporan #{$index} di {$namaDaerah} harus mendapat relawan");

                $expected = $this->relawanTerdekatDari($info['relawan'], (float) $laporan->latitude, (float) $laporan->longitude);
                $this->assertSame(
                    $expected->id,
                    $hasil->id,
                    "Laporan #{$index} di {$namaDaerah} harus ke relawan terdekat ({$expected->email})",
                );

                $laporan->refresh();
                $this->assertSame($expected->id, $laporan->akun_relawan_ditugaskan);
            }
        }
    }

    public function testRelawanHanyaMelihatLaporanYangDitugaskanTidakMelihatDaerahLain(): void
    {
        // Pastikan semua laporan sudah ditugaskan
        foreach ($this->daerah as $info) {
            foreach ($info['laporan'] as $laporan) {
                $this->penugasan->tugaskanRelawanTerdekat($laporan);
            }
        }

        foreach ($this->daerah as $namaDaerah => $info) {
            foreach ($info['relawan'] as $akun) {
                Sanctum::actingAs($akun, [], 'akun_relawan');

                $response = $this->getJson('/api/v1/relawan/laporan-terdekat');
                $response->assertOk()->assertJsonPath('success', true);

                $ids = collect($response->json('data'))->pluck('id')->all();
                $milikSendiri = collect($info['laporan'])
                    ->filter(fn (LaporanBencana $l) => (int) $l->fresh()->akun_relawan_ditugaskan === (int) $akun->id)
                    ->pluck('id')
                    ->all();

                sort($ids);
                sort($milikSendiri);
                $this->assertSame(
                    $milikSendiri,
                    $ids,
                    "Relawan {$akun->email} di {$namaDaerah} hanya boleh lihat laporan miliknya",
                );

                // Tidak boleh melihat laporan daerah lain
                foreach ($this->daerah as $namaLain => $infoLain) {
                    if ($namaLain === $namaDaerah) {
                        continue;
                    }
                    foreach ($infoLain['laporan'] as $laporanLain) {
                        $this->assertNotContains(
                            $laporanLain->id,
                            $ids,
                            "Relawan {$akun->email} tidak boleh lihat laporan {$namaLain} #{$laporanLain->id}",
                        );
                    }
                }

                // Detail laporan daerah lain → 403
                $laporanAsing = collect($this->daerah)
                    ->except($namaDaerah)
                    ->flatMap(fn (array $d) => $d['laporan'])
                    ->first();
                if ($laporanAsing !== null) {
                    $this->getJson("/api/v1/relawan/laporan/{$laporanAsing->id}")
                        ->assertStatus(403);
                }
            }
        }
    }

    public function testEndpointPetaRelawanHanyaTitikLaporanSendiri(): void
    {
        foreach ($this->daerah as $info) {
            foreach ($info['laporan'] as $laporan) {
                $this->penugasan->tugaskanRelawanTerdekat($laporan);
            }
        }

        $akunAmbon = $this->daerah['Pulau Ambon']['relawan'][0];
        Sanctum::actingAs($akunAmbon, [], 'akun_relawan');

        $response = $this->getJson('/api/v1/relawan/peta');
        $response->assertOk();

        $ids = collect($response->json('laporan'))->pluck('id')->all();
        foreach ($ids as $id) {
            $this->assertSame(
                $akunAmbon->id,
                (int) LaporanBencana::findOrFail($id)->akun_relawan_ditugaskan,
            );
        }

        foreach ($this->daerah['Kepulauan Kei']['laporan'] as $laporanKei) {
            $this->assertNotContains($laporanKei->id, $ids);
        }
    }

    public function testRelawanJauhTidakBisaClaimLaporanDaerahLain(): void
    {
        $laporanAmbon = $this->daerah['Pulau Ambon']['laporan'][0];
        // Pastikan belum ditugaskan
        $laporanAmbon->update(['akun_relawan_ditugaskan' => null]);

        $akunKei = $this->daerah['Kepulauan Kei']['relawan'][0];
        Sanctum::actingAs($akunKei, [], 'akun_relawan');

        $this->postJson("/api/v1/relawan/laporan/{$laporanAmbon->id}/claim")
            ->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    public function testObserverMembuatNotifikasiHanyaKeRelawanTerdekat(): void
    {
        $wilayah = $this->daerah['Pulau Ambon']['wilayah'];
        $expected = $this->daerah['Pulau Ambon']['relawan'][0]; // index 0 = terdekat di pusat

        $sebelum = RelawanNotifikasi::count();

        $laporan = LaporanBencana::create([
            'wilayah_id' => $wilayah->id,
            'nama_pelapor' => 'Pelapor Observer',
            'nomor_kontak' => '08129999000',
            'jenis_kejadian' => 'Gempa',
            'latitude' => $this->daerah['Pulau Ambon']['center']['lat'],
            'longitude' => $this->daerah['Pulau Ambon']['center']['lng'],
            'alamat_lokasi' => 'Ambon pusat',
            'tanggal_kejadian' => now(),
            'deskripsi' => 'Laporan observer auto-assign',
            'status' => 'diverifikasi',
            'status_penanganan' => 'belum_ditangani',
        ]);

        $laporan->refresh();
        $this->assertSame($expected->id, $laporan->akun_relawan_ditugaskan);
        $this->assertSame($sebelum + 1, RelawanNotifikasi::count());
        $this->assertSame(
            $expected->id,
            RelawanNotifikasi::where('laporan_id', $laporan->id)->value('akun_relawan_id'),
        );
    }

    public function testFaskesLokasiMasyarakatDiurutJarakDanTidakCampurPulauLain(): void
    {
        foreach ($this->daerah as $nama => $info) {
            // 2 faskes per daerah: dekat & sedikit lebih jauh
            Faskes::create([
                'wilayah_id' => $info['wilayah']->id,
                'nama' => "RS Dekat {$nama}",
                'tipe' => 'rumah_sakit',
                'alamat' => "Dekat {$nama}",
                'latitude' => $info['center']['lat'] + 0.002,
                'longitude' => $info['center']['lng'] + 0.002,
            ]);
            Faskes::create([
                'wilayah_id' => $info['wilayah']->id,
                'nama' => "Puskesmas Jauh {$nama}",
                'tipe' => 'puskesmas',
                'alamat' => "Jauh {$nama}",
                'latitude' => $info['center']['lat'] + 0.03,
                'longitude' => $info['center']['lng'] + 0.03,
            ]);
        }

        foreach ($this->daerah as $nama => $info) {
            $lat = $info['center']['lat'];
            $lng = $info['center']['lng'];

            $response = $this->getJson("/api/v1/faskes?lat={$lat}&lng={$lng}");
            $response->assertOk()->assertJsonPath('success', true);

            $data = collect($response->json('data'));
            $this->assertGreaterThanOrEqual(2, $data->count(), "Faskes di {$nama}");

            // Semua hasil dari pulau yang sama
            foreach ($data as $item) {
                $this->assertStringContainsString($nama, $item['nama']);
            }

            // Urut jarak naik
            $jarak = $data->pluck('jarak_km')->all();
            $sorted = $jarak;
            sort($sorted);
            $this->assertSame($sorted, $jarak, "Faskes {$nama} harus diurut jarak terdekat");

            // Faskes pulau lain tidak ikut
            foreach ($this->daerah as $namaLain => $_) {
                if ($namaLain === $nama) {
                    continue;
                }
                $this->assertFalse(
                    $data->contains(fn (array $row) => str_contains($row['nama'], $namaLain)),
                    "Faskes {$namaLain} tidak boleh muncul di GPS {$nama}",
                );
            }
        }
    }

    public function testTidakMenugaskanLintasPulauMeskiRadiusLegacyBesar(): void
    {
        $laporanAmbon = $this->daerah['Pulau Ambon']['laporan'][0];
        $laporanAmbon->update(['akun_relawan_ditugaskan' => null]);

        // Nonaktifkan semua relawan Ambon — sisakan Kei saja
        foreach ($this->daerah['Pulau Ambon']['relawan'] as $akun) {
            $akun->update(['status' => 'nonaktif']);
        }

        $hasil = $this->penugasan->tugaskanRelawanTerdekat($laporanAmbon, 500);
        $this->assertNull(
            $hasil,
            'Relawan Kepulauan Kei tidak boleh di-assign ke laporan Ambon meski radius 500 km',
        );
    }

    private function seedDatasetMaluku(): void
    {
        $definisi = [
            'Pulau Ambon' => [
                'kota' => 'Kota Ambon',
                'kecamatan' => 'Sirimau',
                'lat' => -3.6954,
                'lng' => 128.1814,
            ],
            'Pulau Seram' => [
                'kota' => 'Kabupaten Maluku Tengah',
                'kecamatan' => 'Masohi',
                'lat' => -3.2950,
                'lng' => 128.9670,
            ],
            'Kepulauan Banda' => [
                'kota' => 'Kabupaten Maluku Tengah',
                'kecamatan' => 'Banda',
                'lat' => -4.5267,
                'lng' => 129.9044,
            ],
            'Kepulauan Kei' => [
                'kota' => 'Kabupaten Maluku Tenggara',
                'kecamatan' => 'Kei Kecil',
                'lat' => -5.6425,
                'lng' => 132.7485,
            ],
            'Kepulauan Aru' => [
                'kota' => 'Kabupaten Kepulauan Aru',
                'kecamatan' => 'Dobo',
                'lat' => -5.7600,
                'lng' => 134.2200,
            ],
            'Pulau Buru' => [
                'kota' => 'Kabupaten Buru',
                'kecamatan' => 'Namlea',
                'lat' => -3.2500,
                'lng' => 127.1000,
            ],
        ];

        foreach ($definisi as $pulau => $meta) {
            $wilayah = Wilayah::create([
                'nama' => $meta['kota'],
                'kecamatan' => $meta['kecamatan'],
                'kota' => $meta['kota'],
                'pulau' => $pulau,
                'provinsi' => 'Maluku',
                'latitude' => $meta['lat'],
                'longitude' => $meta['lng'],
            ]);

            $relawan = [];
            // 6 relawan: index 0 paling dekat ke pusat, index 5 paling jauh dalam kota
            for ($i = 0; $i < 6; $i++) {
                $offset = 0.004 * $i; // ~0.4–2.4 km antar titik
                $relawan[] = $this->buatAkunRelawan(
                    "{$pulau}-R{$i}",
                    $meta['lat'] + $offset,
                    $meta['lng'] + $offset,
                );
            }

            $laporan = [];
            // 3 laporan dekat pusat — paling dekat ke R0, R1, R2 secara berturut
            for ($i = 0; $i < 3; $i++) {
                $offset = 0.001 + (0.004 * $i);
                $laporan[] = LaporanBencana::withoutEvents(fn () => LaporanBencana::create([
                    'wilayah_id' => $wilayah->id,
                    'nama_pelapor' => "Pelapor {$pulau} {$i}",
                    'nomor_kontak' => '081'.str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT),
                    'jenis_kejadian' => ['Banjir', 'Gempa', 'Kebakaran'][$i],
                    'latitude' => $meta['lat'] + $offset,
                    'longitude' => $meta['lng'] + $offset,
                    'alamat_lokasi' => "{$pulau} titik {$i}",
                    'tanggal_kejadian' => now(),
                    'deskripsi' => "Laporan uji {$pulau} #{$i}",
                    'status' => 'diverifikasi',
                    'status_penanganan' => 'belum_ditangani',
                ]));
            }

            $this->daerah[$pulau] = [
                'wilayah' => $wilayah,
                'center' => ['lat' => $meta['lat'], 'lng' => $meta['lng']],
                'relawan' => $relawan,
                'laporan' => $laporan,
            ];
        }
    }

    /**
     * @param  list<AkunRelawan>  $relawan
     */
    private function relawanTerdekatDari(array $relawan, float $lat, float $lng): AkunRelawan
    {
        return collect($relawan)
            ->sortBy(fn (AkunRelawan $akun) => $this->haversine->hitungJarak(
                $lat,
                $lng,
                (float) $akun->latitude,
                (float) $akun->longitude,
            ))
            ->first();
    }

    private function buatAkunRelawan(string $nama, float $lat, float $lng): AkunRelawan
    {
        $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '.', $nama) ?? $nama);

        $pengguna = Pengguna::create([
            'name' => $nama,
            'phone' => '08'.random_int(1000000000, 9999999999),
            'email' => $slug.'@skala.test',
            'password' => bcrypt('secret'),
        ]);

        $relawan = Relawan::create([
            'pengguna_id' => $pengguna->id,
            'status' => 'disetujui',
        ]);

        return AkunRelawan::create([
            'relawan_id' => $relawan->id,
            'email' => 'akun.'.$slug.'@skala.test',
            'password' => bcrypt('secret'),
            'status' => 'aktif',
            'latitude' => $lat,
            'longitude' => $lng,
            'lokasi_updated_at' => now(),
        ]);
    }
}
