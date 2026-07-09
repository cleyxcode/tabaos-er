<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\AmbulansResource;
use App\Models\Ambulans;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AmbulansController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $query = Ambulans::with('faskes');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('jenis_layanan')) {
            $query->where('jenis_layanan', $request->jenis_layanan);
        }

        $ambulans = $query->get();

        return $this->success(
            AmbulansResource::collection($ambulans),
            'Data ambulans berhasil diambil.'
        );
    }
}
