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

    public function index(Request $request): JsonResponse
    {
        $query = PedomanBhd::query();

        if ($request->filled('tipe_file')) {
            $query->where('tipe_file', $request->tipe_file);
        }

        $pedoman = $query->latest()->get();

        return $this->success(
            PedomanBhdResource::collection($pedoman),
            'Data pedoman BHD berhasil diambil.'
        );
    }

    public function show(PedomanBhd $pedomanBhd): JsonResponse
    {
        return $this->success(
            new PedomanBhdResource($pedomanBhd),
            'Detail pedoman BHD berhasil diambil.'
        );
    }
}
