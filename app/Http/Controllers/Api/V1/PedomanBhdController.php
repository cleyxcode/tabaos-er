<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\PedomanBhdResource;
use App\Models\PedomanBhd;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PedomanBhdController extends Controller
{
    use ApiResponse;

    /**
     * GET /api/v1/edukasi (alias: /pedoman-bhd)
     * Materi edukasi & aplikasi simulasi publik: foto, PDF, video, dokumen, APK.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'tipe_file' => 'nullable|in:pdf,video,gambar,dokumen,aplikasi',
        ]);

        $query = PedomanBhd::query();

        if ($request->filled('tipe_file')) {
            $query->where('tipe_file', $request->tipe_file);
        }

        $items = $query->latest()->get();

        return $this->success(
            PedomanBhdResource::collection($items),
            'Data edukasi dan simulasi berhasil diambil.'
        );
    }

    public function show(PedomanBhd $pedomanBhd): JsonResponse
    {
        return $this->success(
            new PedomanBhdResource($pedomanBhd),
            'Detail edukasi dan simulasi berhasil diambil.'
        );
    }
}
