<?php

declare(strict_types=1);

namespace App\DTO;

final readonly class KirimNotifikasiAdminDTO
{
    /**
     * @param  list<int>  $akunRelawanIds
     * @param  list<int>  $akunFaskesIds
     */
    public function __construct(
        public int $adminId,
        public string $judul,
        public string $pesan,
        public ?string $gambar,
        public bool $kirimKeRelawan,
        public bool $kirimKeFaskes,
        public bool $kirimSemuaRelawan = true,
        public bool $kirimSemuaFaskes = true,
        public array $akunRelawanIds = [],
        public array $akunFaskesIds = [],
    ) {}

    public function hasTarget(): bool
    {
        if ($this->kirimKeRelawan) {
            if ($this->kirimSemuaRelawan || $this->akunRelawanIds !== []) {
                return true;
            }
        }

        if ($this->kirimKeFaskes) {
            if ($this->kirimSemuaFaskes || $this->akunFaskesIds !== []) {
                return true;
            }
        }

        return false;
    }
}
