<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\PetugasEmergencyResource;
use App\Models\PetugasEmergency;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PetugasEmergencyController extends Controller
{
    use ApiResponse;

    // Nomor darurat resmi statis
    private const NOMOR_DARURAT = [
        ['nama' => 'Call Center Layanan Emergency Kesehatan', 'nomor' => '119', 'kategori' => 'medis'],
        ['nama' => 'Call Center Darurat Ambon',               'nomor' => '112', 'kategori' => 'darurat'],
        ['nama' => 'Damkar',                                  'nomor' => '113', 'kategori' => 'kebakaran'],
        ['nama' => 'BASARNAS',                                'nomor' => '115', 'kategori' => 'sar'],
        ['nama' => 'BNPB',                                    'nomor' => '117', 'kategori' => 'bencana'],
        ['nama' => 'Call Center Kepolisian',                  'nomor' => '110', 'kategori' => 'keamanan'],
    ];

    public function index(Request $request): JsonResponse
    {
        $query = PetugasEmergency::where('status', 'aktif');

        if ($request->filled('kategori')) {
            $query->where('kategori', $request->kategori);
        }

        if ($request->filled('search')) {
            $query->where('nama', 'like', '%' . $request->search . '%');
        }

        $petugas = $query->get();

        return $this->success([
            'nomor_darurat' => self::NOMOR_DARURAT,
            'petugas'       => PetugasEmergencyResource::collection($petugas),
        ], 'Data petugas emergency berhasil diambil.');
    }
}
