<?php

declare(strict_types=1);

namespace App\DTO;

final readonly class KirimNotifikasiAdminDTO
{
    /**
     * @param  list<int>  $akunRelawanIds
     */
    public function __construct(
        public int $adminId,
        public string $judul,
        public string $pesan,
        public ?string $gambar,
        public bool $kirimKeRelawan,
        public bool $kirimSemuaRelawan = true,
        public array $akunRelawanIds = [],
    ) {}

    public function hasTarget(): bool
    {
        if (! $this->kirimKeRelawan) {
            return false;
        }

        return $this->kirimSemuaRelawan || $this->akunRelawanIds !== [];
    }
}
