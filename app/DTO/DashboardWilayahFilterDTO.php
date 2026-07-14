<?php

declare(strict_types=1);

namespace App\DTO;

use Illuminate\Database\Eloquent\Builder;

final readonly class DashboardWilayahFilterDTO
{
    public function __construct(
        public ?string $provinsi = null,
        public ?string $pulau = null,
        public ?string $kota = null,
        public ?int $wilayahId = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            provinsi: self::nullableString($data['provinsi'] ?? null),
            pulau: self::nullableString($data['pulau'] ?? null),
            kota: self::nullableString($data['kota'] ?? null),
            wilayahId: isset($data['wilayah_id']) && $data['wilayah_id'] !== '' && $data['wilayah_id'] !== null
                ? (int) $data['wilayah_id']
                : (isset($data['wilayahId']) && $data['wilayahId'] !== '' && $data['wilayahId'] !== null
                    ? (int) $data['wilayahId']
                    : null),
        );
    }

    public function isEmpty(): bool
    {
        return $this->provinsi === null
            && $this->pulau === null
            && $this->kota === null
            && $this->wilayahId === null;
    }

    /**
     * Filter model yang punya kolom wilayah_id / relasi wilayah.
     *
     * @param  Builder<\Illuminate\Database\Eloquent\Model>  $query
     */
    public function applyToQuery(Builder $query, string $relation = 'wilayah'): void
    {
        if ($this->isEmpty()) {
            return;
        }

        if ($this->wilayahId !== null) {
            $query->where('wilayah_id', $this->wilayahId);

            return;
        }

        $query->whereHas($relation, function (Builder $q): void {
            $this->applyToWilayahQuery($q);
        });
    }

    /**
     * Filter langsung pada model Wilayah.
     *
     * @param  Builder<\Illuminate\Database\Eloquent\Model>  $query
     */
    public function applyToWilayahQuery(Builder $query): void
    {
        if ($this->wilayahId !== null) {
            $query->where('id', $this->wilayahId);

            return;
        }

        if ($this->provinsi !== null) {
            $query->where('provinsi', $this->provinsi);
        }

        if ($this->pulau !== null) {
            $query->where('pulau', $this->pulau);
        }

        if ($this->kota !== null) {
            $query->where('kota', $this->kota);
        }
    }

    public function cacheKey(): string
    {
        return md5(implode('|', [
            $this->provinsi ?? '',
            $this->pulau ?? '',
            $this->kota ?? '',
            (string) ($this->wilayahId ?? ''),
        ]));
    }

    private static function nullableString(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (string) $value;
    }
}
