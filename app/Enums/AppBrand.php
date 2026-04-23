<?php

namespace App\Enums;

enum AppBrand: string
{
    case CARBEAT = 'carbeat';
    case FLOXCITY = 'flox';

    public static function fromHeader(?string $header): self
    {
        return match (strtolower((string) $header)) {
            self::FLOXCITY->value => self::FLOXCITY,
            default => self::CARBEAT,
        };
    }

    public static function fromHost(string $host): self
    {
        foreach (self::cases() as $brand) {
            if (str_contains(strtolower($host), $brand->value)) {
                return $brand;
            }
        }
        return self::CARBEAT;
    }
}
