<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\PesanAdminResource;
use App\Models\AkunFaskes;
use App\Models\AkunRelawan;
use App\Models\NotifikasiAdminPenerima;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AdminPesanController extends Controller
{
    // GET /relawan/pesan-admin
    public function indexRelawan(Request $request): JsonResponse
    {
        /** @var AkunRelawan $akun */
        $akun = $request->user('akun_relawan');

        return $this->indexForPenerima($akun::class, $akun->id);
    }

    // GET /relawan/pesan-admin/{id}
    public function showRelawan(Request $request, int $id): JsonResponse
    {
        /** @var AkunRelawan $akun */
        $akun = $request->user('akun_relawan');

        return $this->showForPenerima($akun::class, $akun->id, $id);
    }

    // PUT /relawan/pesan-admin/{id}/baca
    public function tandaiBacaRelawan(Request $request, int $id): JsonResponse
    {
        /** @var AkunRelawan $akun */
        $akun = $request->user('akun_relawan');

        return $this->tandaiBaca($akun::class, $akun->id, $id);
    }

    // GET /faskes/pesan-admin
    public function indexFaskes(Request $request): JsonResponse
    {
        /** @var AkunFaskes $akun */
        $akun = $request->user('akun_faskes');

        return $this->indexForPenerima($akun::class, $akun->id);
    }

    // GET /faskes/pesan-admin/{id}
    public function showFaskes(Request $request, int $id): JsonResponse
    {
        /** @var AkunFaskes $akun */
        $akun = $request->user('akun_faskes');

        return $this->showForPenerima($akun::class, $akun->id, $id);
    }

    // PUT /faskes/pesan-admin/{id}/baca
    public function tandaiBacaFaskes(Request $request, int $id): JsonResponse
    {
        /** @var AkunFaskes $akun */
        $akun = $request->user('akun_faskes');

        return $this->tandaiBaca($akun::class, $akun->id, $id);
    }

    private function indexForPenerima(string $penerimaType, int $penerimaId): JsonResponse
    {
        $query = NotifikasiAdminPenerima::query()
            ->with(['notifikasi.admin'])
            ->where('penerima_type', $penerimaType)
            ->where('penerima_id', $penerimaId)
            ->orderByDesc('created_at');

        $pesan = $query->paginate(15);

        $unreadCount = NotifikasiAdminPenerima::query()
            ->where('penerima_type', $penerimaType)
            ->where('penerima_id', $penerimaId)
            ->where('sudah_dibaca', false)
            ->count();

        return response()->json([
            'success' => true,
            'data' => PesanAdminResource::collection($pesan->items())->resolve(),
            'unread_count' => $unreadCount,
            'meta' => [
                'current_page' => $pesan->currentPage(),
                'last_page' => $pesan->lastPage(),
                'total' => $pesan->total(),
            ],
        ]);
    }

    private function showForPenerima(string $penerimaType, int $penerimaId, int $inboxId): JsonResponse
    {
        $inbox = NotifikasiAdminPenerima::query()
            ->with(['notifikasi.admin'])
            ->where('id', $inboxId)
            ->where('penerima_type', $penerimaType)
            ->where('penerima_id', $penerimaId)
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => (new PesanAdminResource($inbox))->resolve(),
        ]);
    }

    private function tandaiBaca(string $penerimaType, int $penerimaId, int $inboxId): JsonResponse
    {
        $inbox = NotifikasiAdminPenerima::query()
            ->where('id', $inboxId)
            ->where('penerima_type', $penerimaType)
            ->where('penerima_id', $penerimaId)
            ->firstOrFail();

        $inbox->update([
            'sudah_dibaca' => true,
            'dibaca_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pesan admin ditandai dibaca',
        ]);
    }
}
