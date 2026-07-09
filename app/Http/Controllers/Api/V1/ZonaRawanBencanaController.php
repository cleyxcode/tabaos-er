<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\ZonaRawanBencanaResource;
use App\Models\ZonaRawanBencana;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ZonaRawanBencanaController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $query = ZonaRawanBencana::with(['wilayah', 'titikEvakuasi']);

        if ($request->filled('tingkat_risiko')) {
            $query->where('tingkat_risiko', $request->tingkat_risiko);
        }

        if ($request->filled('wilayah_id')) {
            $query->where('wilayah_id', $request->wilayah_id);
        }

        if ($request->filled('search')) {
            $query->where('nama_zona', 'like', '%' . $request->search . '%');
        }

        $zona = $query->get();

        return $this->success(
            ZonaRawanBencanaResource::collection($zona),
            'Data zona rawan bencana berhasil diambil.'
        );
    }

    public function show(ZonaRawanBencana $zonaRawanBencana): JsonResponse
    {
        $zonaRawanBencana->load(['wilayah', 'titikEvakuasi']);

        return $this->success(
            new ZonaRawanBencanaResource($zonaRawanBencana),
            'Detail zona rawan bencana berhasil diambil.'
        );
    }
}
