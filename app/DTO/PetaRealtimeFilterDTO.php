<?php

declare(strict_types=1);

namespace App\DTO;

final readonly class PetaRealtimeFilterDTO
{
    public function __construct(
        public ?int $wilayahId = null,
        public ?string $provinsi = null,
        public ?string $pulau = null,
        public ?string $kota = null,
        public ?string $jenisKejadian = null,
        public ?string $statusLaporan = null,
        public ?string $statusPenanganan = null,
        public bool $tampilkanLaporan = true,
        public bool $tampilkanRelawan = true,
        public bool $tampilkanFaskes = true,
        public bool $tampilkanEvakuasi = true,
        public bool $tampilkanPetugas = true,
        public ?float $centerLat = null,
        public ?float $centerLng = null,
        public ?float $radiusKm = null,
        public int $relawanStaleMinutes = 30,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            wilayahId: isset($data['wilayahId']) && $data['wilayahId'] !== ''
                ? (int) $data['wilayahId']
                : null,
            provinsi: self::nullableString($data['provinsi'] ?? null),
            pulau: self::nullableString($data['pulau'] ?? null),
            kota: self::nullableString($data['kota'] ?? null),
            jenisKejadian: self::nullableString($data['jenisKejadian'] ?? null),
            statusLaporan: self::nullableString($data['statusLaporan'] ?? null),
            statusPenanganan: self::nullableString($data['statusPenanganan'] ?? null),
            tampilkanLaporan: (bool) ($data['tampilkanLaporan'] ?? true),
            tampilkanRelawan: (bool) ($data['tampilkanRelawan'] ?? true),
            tampilkanFaskes: (bool) ($data['tampilkanFaskes'] ?? true),
            tampilkanEvakuasi: (bool) ($data['tampilkanEvakuasi'] ?? true),
            tampilkanPetugas: (bool) ($data['tampilkanPetugas'] ?? true),
            centerLat: self::nullableFloat($data['centerLat'] ?? null),
            centerLng: self::nullableFloat($data['centerLng'] ?? null),
            radiusKm: self::nullableFloat($data['radiusKm'] ?? null),
            relawanStaleMinutes: max(1, (int) ($data['relawanStaleMinutes'] ?? 30)),
        );
    }

    public function hasRadiusFilter(): bool
    {
        return $this->centerLat !== null
            && $this->centerLng !== null
            && $this->radiusKm !== null
            && $this->radiusKm > 0;
    }

    private static function nullableString(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (string) $value;
    }

    private static function nullableFloat(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (float) $value;
    }
}
