<?php

declare(strict_types=1);

namespace App\Service;

final class GeoResult
{
    public function __construct(
        public readonly string $label,
        public readonly float $latitude,
        public readonly float $longitude,
    ) {}
}
