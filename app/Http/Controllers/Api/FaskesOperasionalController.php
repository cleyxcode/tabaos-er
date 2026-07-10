<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AkunRelawan;
use App\Models\LaporanBencana;
use App\Services\HaversineService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FaskesOperasionalController extends Controller
{
    public function __construct(protected HaversineService $haversine) {}

    // POST /faskes/fcm-token
    public function updateFcmToken(Request $request): JsonResponse
    {
        $request->validate(['fcm_token' => 'required|string']);

        $request->user('akun_faskes')->update(['fcm_token' => $request->fcm_token]);

        return response()->json([
            'success' => true,
            'message' => 'FCM token tersimpan',
        ]);
    }

    // GET /faskes/laporan
    public function laporan(Request $request): JsonResponse
    {
        $akun   = $request->user('akun_faskes');
        $faskes = $akun->faskes;

        $request->validate([
            'lat'    => 'nullable|numeric',
            'lng'    => 'nullable|numeric',
            'radius' => 'nullable|numeric|min:1|max:100',
            'status' => 'nullable|string',
        ]);

        $lat    = (float) ($request->lat ?? $faskes?->latitude ?? 0);
        $lng    = (float) ($request->lng ?? $faskes?->longitude ?? 0);
        $radius = (float) ($request->radius ?? 15);

        $query = LaporanBencana::query()
            ->whereNotNull('latitude')
            ->whereNotNull('longitude');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $laporan = $this->haversine->scopeQuery($query, $lat, $lng, $radius)
            ->paginate(10);

        $laporan->getCollection()->transform(function (LaporanBencana $item) {
            return [
                'id'               => $item->id,
                'jenis_kejadian'   => $item->jenis_kejadian,
                'deskripsi'        => $item->deskripsi,
                'status'           => $item->status,
                'status_penanganan' => $item->status_penanganan,
                'latitude'         => $item->latitude,
                'longitude'        => $item->longitude,
                'alamat_lokasi'    => $item->alamat_lokasi,
                'tanggal_kejadian' => $item->tanggal_kejadian,
                'korban'           => [
                    'meninggal_jumlah'   => $item->meninggal_jumlah,
                    'luka_berat_jumlah'  => $item->luka_berat_jumlah,
                    'luka_ringan_jumlah' => $item->luka_ringan_jumlah,
                    'hilang_jumlah'      => $item->hilang_jumlah,
                ],
                'jarak_km' => isset($item->jarak_km) ? round((float) $item->jarak_km, 2) : null,
            ];
        });

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

    // GET /faskes/laporan/{id}
    public function detailLaporan(Request $request, int $id): JsonResponse
    {
        $laporan = LaporanBencana::with([
            'pengguna', 'wilayah', 'relawanDitugaskan.relawan.pengguna', 'penugasan',
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'laporan' => $laporan,
        ]);
    }

    // GET /faskes/peta
    public function dataPeta(Request $request): JsonResponse
    {
        $akun   = $request->user('akun_faskes');
        $faskes = $akun->faskes;

        $request->validate(['radius' => 'nullable|numeric|min:1|max:100']);

        $lat    = (float) ($faskes?->latitude  ?? 0);
        $lng    = (float) ($faskes?->longitude ?? 0);
        $radius = (float) ($request->radius ?? 15);

        $laporanQuery = LaporanBencana::query()
            ->whereIn('status_penanganan', ['belum_ditangani', 'sedang_ditangani'])
            ->whereNotNull('latitude')
            ->whereNotNull('longitude');

        $laporan = $this->haversine->scopeQuery($laporanQuery, $lat, $lng, $radius)
            ->get(['id', 'latitude', 'longitude', 'jenis_kejadian', 'status', 'status_penanganan']);

        $relawanAktif = AkunRelawan::where('status', 'aktif')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where('lokasi_updated_at', '>=', now()->subMinutes(30))
            ->with('relawan.pengguna')
            ->get()
            ->filter(function (AkunRelawan $akun) use ($lat, $lng, $radius) {
                return $this->haversine->hitungJarak(
                    $lat, $lng, (float) $akun->latitude, (float) $akun->longitude,
                ) <= $radius;
            })
            ->map(fn (AkunRelawan $akun) => [
                'id'        => $akun->id,
                'nama'      => $akun->relawan?->pengguna?->name,
                'latitude'  => $akun->latitude,
                'longitude' => $akun->longitude,
            ])
            ->values();

        return response()->json([
            'success'       => true,
            'faskes_saya'   => [
                'id'        => $faskes?->id,
                'nama'      => $faskes?->nama,
                'latitude'  => $faskes?->latitude,
                'longitude' => $faskes?->longitude,
            ],
            'laporan'       => $laporan,
            'relawan_aktif' => $relawanAktif,
        ]);
    }

    // GET /faskes/profil
    public function profil(Request $request): JsonResponse
    {
        $akun = $request->user('akun_faskes')->load('faskes.ambulans');

        return response()->json([
            'success' => true,
            'profil'  => $akun->faskes,
        ]);
    }

    // GET /faskes/notifikasi
    public function notifikasi(Request $request): JsonResponse
    {
        // Faskes mendapat notifikasi dari history laporan yang masuk di area faskes.
        // Di sini kita return laporan terbaru dalam radius 15 km dari faskes.
        $akun   = $request->user('akun_faskes');
        $faskes = $akun->faskes;

        $lat    = (float) ($faskes?->latitude  ?? 0);
        $lng    = (float) ($faskes?->longitude ?? 0);

        $query = LaporanBencana::query()
            ->whereNotNull('latitude')
            ->whereNotNull('longitude');

        $laporan = $this->haversine->scopeQuery($query, $lat, $lng, 15)
            ->orderByDesc('created_at')
            ->paginate(15);

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
}
