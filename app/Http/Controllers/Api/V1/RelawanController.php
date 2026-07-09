<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreRelawanRequest;
use App\Http\Resources\Api\RelawanResource;
use App\Models\Relawan;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class RelawanController extends Controller
{
    use ApiResponse;

    /**
     * POST /api/v1/relawan — Daftar jadi relawan
     */
    public function store(StoreRelawanRequest $request): JsonResponse
    {
        $pengguna = auth('pengguna')->user();

        // Cek jika sudah pernah mendaftar
        if ($pengguna->relawan()->exists()) {
            return $this->error('Kamu sudah terdaftar sebagai relawan.', 409);
        }

        $relawan = Relawan::create([
            'pengguna_id' => $pengguna->id,
            'nik'         => $request->nik,
            'alamat'      => $request->alamat,
            'keahlian'    => $request->keahlian,
            'status'      => 'pending',
        ]);

        return $this->success(
            new RelawanResource($relawan),
            'Pendaftaran relawan berhasil dikirim. Tunggu verifikasi dari admin.',
            201
        );
    }

    /**
     * GET /api/v1/relawan/status — Cek status pendaftaran relawan
     */
    public function status(): JsonResponse
    {
        $relawan = auth('pengguna')->user()->relawan;

        if (! $relawan) {
            return $this->error('Kamu belum mendaftar sebagai relawan.', 404);
        }

        return $this->success(
            new RelawanResource($relawan),
            'Status relawan berhasil diambil.'
        );
    }
}
