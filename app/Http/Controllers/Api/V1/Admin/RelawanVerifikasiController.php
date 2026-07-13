<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\RelawanResource;
use App\Models\Relawan;
use App\Services\RelawanVerifikasiService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RelawanVerifikasiController extends Controller
{
    use ApiResponse;

    /**
     * POST /api/v1/admin/relawan/{relawan}/verifikasi
     * Verifikasi pendaftaran relawan dari masyarakat → buat akun relawan aktif.
     */
    public function verifikasi(Request $request, Relawan $relawan, RelawanVerifikasiService $service): JsonResponse
    {
        $request->validate([
            'admin_id' => ['nullable', 'integer', 'exists:users,id'],
            'password' => ['nullable', 'string', 'min:8', 'max:255'],
        ]);

        try {
            $adminId = (int) ($request->input('admin_id') ?? 1);
            $akun = $service->verifikasi(
                $relawan,
                $adminId,
                $request->input('password'),
            );
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 422);
        }

        $relawan->refresh()->load(['pengguna', 'akunRelawan']);

        return $this->success([
            'relawan'      => new RelawanResource($relawan),
            'akun_relawan' => [
                'id'     => $akun->id,
                'email'  => $akun->email,
                'status' => $akun->status,
            ],
        ], 'Relawan berhasil diverifikasi. Akun relawan telah dibuat.');
    }

    /**
     * POST /api/v1/admin/relawan/{relawan}/tolak
     */
    public function tolak(Request $request, Relawan $relawan, RelawanVerifikasiService $service): JsonResponse
    {
        $request->validate([
            'admin_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        try {
            $adminId = (int) ($request->input('admin_id') ?? 1);
            $relawan = $service->tolak($relawan, $adminId);
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 422);
        }

        return $this->success(
            new RelawanResource($relawan->load('pengguna')),
            'Pendaftaran relawan ditolak.',
        );
    }
}
