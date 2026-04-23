<?php

namespace App\Enums;

enum AppBrand: string
{
    case CARBEAT = 'carbeat';
    case FLOXCITY = 'floxcity';

    public static function fromHeader(?string $header): self
    {
        return match (strtolower((string) $header)) {
            self::FLOXCITY->value => self::FLOXCITY,
            default => self::CARBEAT,
        };
    }

    public static function fromHost(string $host): self
    {
        $host = strtolower($host);
        if (str_contains($host, 'flox')) {
            return self::FLOXCITY;
        }
        return self::CARBEAT;
    }
}
