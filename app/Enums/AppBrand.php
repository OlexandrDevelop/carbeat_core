<?php

namespace App\Enums;

enum AppBrand: string
{
    case CARBEAT = 'carbeat';
    case FLOXCITY = 'floxcity';

    public static function fromHeader(?string $header): self
    {
        return match (strtolower((string) $header)) {
            self::CARBEAT->value => self::CARBEAT,
            self::FLOXCITY->value => self::FLOXCITY,
            default => self::CARBEAT,
        };
    }
}
