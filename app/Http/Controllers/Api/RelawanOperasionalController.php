<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AkunRelawan;
use App\Models\LaporanBencana;
use App\Models\RelawanNotifikasi;
use App\Services\HaversineService;
use App\Services\RelawanKedatanganService;
use App\Traits\FormatsLaporanRingkas;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RelawanOperasionalController extends Controller
{
    use FormatsLaporanRingkas;

    public function __construct(
        protected HaversineService $haversine,
        protected RelawanKedatanganService $kedatangan,
    ) {}

    // PUT /relawan/lokasi
    public function updateLokasi(Request $request): JsonResponse
    {
        $data = $request->validate([
            'latitude'  => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        $akun = $request->user('akun_relawan');
        $akun->update([
            'latitude'          => $data['latitude'],
            'longitude'         => $data['longitude'],
            'lokasi_updated_at' => now(),
        ]);

        $this->kedatangan->periksaDanBeritahuAdmin(
            $akun->fresh(),
            (float) $data['latitude'],
            (float) $data['longitude'],
        );

        return response()->json([
            'success'    => true,
            'message'    => 'Lokasi diperbarui',
            'updated_at' => $akun->lokasi_updated_at,
        ]);
    }

    // POST /relawan/fcm-token
    public function updateFcmToken(Request $request): JsonResponse
    {
        $request->validate(['fcm_token' => 'required|string']);

        $request->user('akun_relawan')->update(['fcm_token' => $request->fcm_token]);

        return response()->json([
            'success' => true,
            'message' => 'FCM token tersimpan',
        ]);
    }

    // GET /relawan/laporan-terdekat
    public function laporanTerdekat(Request $request): JsonResponse
    {
        $request->validate([
            'lat'    => 'required|numeric',
            'lng'    => 'required|numeric',
            'radius' => 'nullable|numeric|min:1|max:100',
        ]);

        $lat    = (float) $request->lat;
        $lng    = (float) $request->lng;
        $radius = (float) ($request->radius ?? 10);

        $query = LaporanBencana::query()
            ->whereIn('status_penanganan', ['belum_ditangani', 'sedang_ditangani'])
            ->whereNotNull('latitude')
            ->whereNotNull('longitude');

        $laporan = $this->haversine->scopeQuery($query, $lat, $lng, $radius)
            ->with('relawanDitugaskan.relawan.pengguna')
            ->paginate(10);

        $laporan->getCollection()->transform(fn (LaporanBencana $item) => $this->formatLaporanRingkas($item));

        return response()->json([
            'success' => true,
            'data'    => $laporan->items(),
            'meta'    => [
                'current_page' => $laporan->currentPage(),
                'last_page'    => $laporan->lastPage(),
                'total'        => $laporan->total(),
            ],
        ]);
    }

    // GET /relawan/laporan/{id}
    public function detailLaporan(Request $request, int $id): JsonResponse
    {
        $laporan = LaporanBencana::with(['pengguna', 'wilayah', 'relawanDitugaskan.relawan.pengguna'])
            ->findOrFail($id);

        // Relawan aktif dalam radius 20 km dari laporan
        $relawanTerdekat = [];
        if ($laporan->latitude && $laporan->longitude) {
            $relawanTerdekat = AkunRelawan::where('status', 'aktif')
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->where('lokasi_updated_at', '>=', now()->subMinutes(30))
                ->get()
                ->map(function (AkunRelawan $akun) use ($laporan) {
                    $jarak = $this->haversine->hitungJarak(
                        (float) $laporan->latitude, (float) $laporan->longitude,
                        (float) $akun->latitude,   (float) $akun->longitude,
                    );
                    return [
                        'id'        => $akun->id,
                        'nama'      => $akun->relawan?->pengguna?->name,
                        'latitude'  => $akun->latitude,
                        'longitude' => $akun->longitude,
                        'jarak_km'  => $jarak,
                    ];
                })
                ->filter(fn ($r) => $r['jarak_km'] <= 20)
                ->sortBy('jarak_km')
                ->values();
        }

        return response()->json([
            'success'          => true,
            'laporan'          => $this->formatLaporanRingkas($laporan),
            'relawan_terdekat' => $relawanTerdekat,
        ]);
    }

    // POST /relawan/laporan/{id}/claim
    public function claimLaporan(Request $request, int $id): JsonResponse
    {
        $laporan = LaporanBencana::findOrFail($id);
        $akun    = $request->user('akun_relawan');

        if (
            $laporan->akun_relawan_ditugaskan !== null &&
            $laporan->akun_relawan_ditugaskan !== $akun->id
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Laporan ini sudah diklaim oleh relawan lain.',
            ], 409);
        }

        $laporan->update([
            'akun_relawan_ditugaskan' => $akun->id,
            'status_penanganan'       => 'sedang_ditangani',
            'relawan_sampai_notified_at' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Laporan berhasil diklaim',
            'laporan' => $laporan->fresh(),
        ]);
    }

    // PUT /relawan/laporan/{id}/selesai
    public function selesaikanLaporan(Request $request, int $id): JsonResponse
    {
        $laporan = LaporanBencana::findOrFail($id);
        $akun    = $request->user('akun_relawan');

        if ($laporan->akun_relawan_ditugaskan !== $akun->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak berwenang mengubah status laporan ini.',
            ], 403);
        }

        $laporan->update([
            'status_penanganan' => 'selesai_ditangani',
            'status'            => 'selesai',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Laporan ditandai selesai',
            'laporan' => $laporan->fresh(),
        ]);
    }

    // GET /relawan/peta
    public function dataPeta(Request $request): JsonResponse
    {
        $request->validate([
            'lat'    => 'required|numeric',
            'lng'    => 'required|numeric',
            'radius' => 'nullable|numeric|min:1|max:100',
        ]);

        $lat    = (float) $request->lat;
        $lng    = (float) $request->lng;
        $radius = (float) ($request->radius ?? 20);

        $laporanQuery = LaporanBencana::query()
            ->where('status', '!=', 'selesai')
            ->whereIn('status_penanganan', ['belum_ditangani', 'sedang_ditangani'])
            ->whereNotNull('latitude')
            ->whereNotNull('longitude');

        $laporan = $this->haversine->scopeQuery($laporanQuery, $lat, $lng, $radius)
            ->get(['id', 'latitude', 'longitude', 'jenis_kejadian', 'status_penanganan']);

        $relawanAktif = AkunRelawan::where('status', 'aktif')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where('lokasi_updated_at', '>=', now()->subMinutes(30))
            ->with('relawan.pengguna')
            ->get()
            ->filter(function (AkunRelawan $akun) use ($lat, $lng, $radius) {
                $jarak = $this->haversine->hitungJarak(
                    $lat, $lng, (float) $akun->latitude, (float) $akun->longitude,
                );
                return $jarak <= $radius;
            })
            ->map(fn (AkunRelawan $akun) => [
                'id'               => $akun->id,
                'nama'             => $akun->relawan?->pengguna?->name,
                'latitude'         => $akun->latitude,
                'longitude'        => $akun->longitude,
                'lokasi_updated_at' => $akun->lokasi_updated_at,
            ])
            ->values();

        return response()->json([
            'success'       => true,
            'laporan'       => $laporan,
            'relawan_aktif' => $relawanAktif,
        ]);
    }

    // GET /relawan/notifikasi
    public function notifikasi(Request $request): JsonResponse
    {
        $akun = $request->user('akun_relawan');

        $notifikasi = RelawanNotifikasi::with(['laporan:id,jenis_kejadian,alamat_lokasi'])
            ->where('akun_relawan_id', $akun->id)
            ->orderByDesc('created_at')
            ->paginate(15);

        $unreadCount = RelawanNotifikasi::where('akun_relawan_id', $akun->id)
            ->where('sudah_dibaca', false)
            ->count();

        return response()->json([
            'success'      => true,
            'data'         => collect($notifikasi->items())->map(fn (RelawanNotifikasi $n) => [
                'id'           => $n->id,
                'jenis'        => $n->jenis ?? 'laporan',
                'judul'        => $n->judul,
                'pesan'        => $n->pesan,
                'sudah_dibaca' => $n->sudah_dibaca,
                'created_at'   => $n->created_at?->toISOString(),
                'laporan'      => $n->laporan ? [
                    'id'             => $n->laporan->id,
                    'jenis_kejadian' => $n->laporan->jenis_kejadian,
                    'alamat_lokasi'  => $n->laporan->alamat_lokasi,
                ] : null,
            ])->values(),
            'unread_count' => $unreadCount,
            'meta'         => [
                'current_page' => $notifikasi->currentPage(),
                'last_page'    => $notifikasi->lastPage(),
                'total'        => $notifikasi->total(),
            ],
        ]);
    }

    // PUT /relawan/notifikasi/{id}/baca
    public function tandaiBaca(Request $request, int $id): JsonResponse
    {
        $akun = $request->user('akun_relawan');

        $notifikasi = RelawanNotifikasi::where('id', $id)
            ->where('akun_relawan_id', $akun->id)
            ->firstOrFail();

        $notifikasi->update([
            'sudah_dibaca' => true,
            'dibaca_at'    => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Notifikasi ditandai dibaca',
        ]);
    }
}
