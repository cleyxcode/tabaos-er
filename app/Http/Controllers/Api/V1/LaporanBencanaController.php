<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreLaporanBencanaRequest;
use App\Http\Resources\Api\LaporanBencanaResource;
use App\Models\LaporanBencana;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class LaporanBencanaController extends Controller
{
    use ApiResponse;

    /**
     * GET /api/v1/laporan — Riwayat laporan milik user yang login
     */
    public function index(): JsonResponse
    {
        $laporan = LaporanBencana::with(['wilayah', 'pengguna'])
            ->where('pengguna_id', auth('pengguna')->id())
            ->latest()
            ->paginate(15);

        return $this->successPaginated($laporan, 'Riwayat laporan berhasil diambil.');
    }

    /**
     * GET /api/v1/laporan/{id} — Detail laporan (harus milik user sendiri)
     */
    public function show(LaporanBencana $laporanBencana): JsonResponse
    {
        if ($laporanBencana->pengguna_id !== auth('pengguna')->id()) {
            return $this->error('Anda tidak berhak mengakses laporan ini.', 403);
        }

        $laporanBencana->load(['wilayah', 'pengguna']);

        return $this->success(
            new LaporanBencanaResource($laporanBencana),
            'Detail laporan berhasil diambil.'
        );
    }

    /**
     * POST /api/v1/laporan — Buat laporan baru
     */
    public function store(StoreLaporanBencanaRequest $request): JsonResponse
    {
        $data = $request->safe()->except('foto');

        // Otomatis set pengguna_id dan status
        $data['pengguna_id'] = auth('pengguna')->id();
        $data['status']      = 'pending';

        // Simpan laporan dulu tanpa foto
        $laporan = LaporanBencana::create($data);

        // Upload foto jika ada
        if ($request->hasFile('foto')) {
            $fotoPaths = [];
            foreach ($request->file('foto') as $file) {
                $path = $file->store("laporan-bencana/{$laporan->id}", 'public');
                $fotoPaths[] = $path;
            }
            $laporan->update(['foto' => $fotoPaths]);
        }

        return $this->success(
            new LaporanBencanaResource($laporan->load(['wilayah', 'pengguna'])),
            'Laporan berhasil dibuat.',
            201
        );
    }
}
