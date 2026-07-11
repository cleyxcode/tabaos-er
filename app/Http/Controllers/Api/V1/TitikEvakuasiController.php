<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\TitikEvakuasiResource;
use App\Models\TitikEvakuasi;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TitikEvakuasiController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $query = TitikEvakuasi::with('zona:id,nama_zona');

        if ($request->filled('zona_id')) {
            $query->where('zona_id', $request->zona_id);
        }

        $titik = $query->get();

        return $this->success(
            TitikEvakuasiResource::collection($titik),
            'Data titik evakuasi berhasil diambil.'
        );
    }
}
