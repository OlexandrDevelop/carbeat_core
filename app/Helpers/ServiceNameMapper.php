<?php

declare(strict_types=1);

namespace App\Helpers;

class ServiceNameMapper
{
    /**
     * Canonical English service keys used across imports.
     *
     * @return array<string>
     */
    public static function canonicalKeys(): array
    {
        return [
            'tire_service',
            'tire_balancing',
            'tire_alignment',
            'car_service',
            'car_repair',
            'engine_repair',
            'transmission_repair',
            'electrical_repair',
            'diagnostics',
            'oil_change',
            'car_glass',
            'car_audio',
            'car_alarm',
            'car_painting',
            'car_body_repair',
            'car_air_conditioning',
            'suspension_repair',
            'welding',
            'car_restoration',
            'equipment_repair',
            'car_tuning',
            'motorcycle_repair',
            'window_tinting',
            'lpg_installation',
            'interior_cleaning',
            'manual_transmission_repair',
            'hydraulic_repair',
            'agricultural_equipment_repair',
            'radiator_repair',
            'auto_dismantling',
            'trailer_repair',
            'construction_equipment_repair',
            'tractor_repair',
            'air_compressor_repair',
            'atelier_services',
            'locksmith_services',
            'vehicle_inspection',
            'smart_repair',
            'detailing',
            'other_services',
            'transmission_and_brakes',
        ];
    }

    public static function toCanonical(string $rawName): string
    {
        $rawName = trim($rawName);
        if ($rawName === '') {
            return '';
        }

        $normalized = self::normalize($rawName);

        $exact = self::exactAliasMap();
        if (isset($exact[$normalized])) {
            return $exact[$normalized];
        }

        // Keep already canonical values untouched.
        if (in_array($normalized, self::canonicalKeys(), true)) {
            return $normalized;
        }

        foreach (self::aliasMap() as $canonical => $aliases) {
            foreach ($aliases as $alias) {
                if (str_contains($normalized, $alias)) {
                    return $canonical;
                }
            }
        }

        // Keep unknown names untouched to avoid destructive merges.
        return $rawName;
    }

    private static function normalize(string $raw): string
    {
        $value = mb_strtolower(trim($raw));
        $value = str_replace(['’', '\''], ['', ''], $value);
        $value = (string) preg_replace('/\s+/u', ' ', $value);

        return $value;
    }

    /**
     * @return array<string,array<string>>
     */
    private static function aliasMap(): array
    {
        return [
            'tire_service' => ['tire service', 'tire', 'reifen', 'reifenservice', 'шиномонтаж', 'шини', 'колеса'],
            'tire_balancing' => ['tire balancing', 'balancing', 'reifen auswuchten', 'балансировка', 'балансування'],
            'tire_alignment' => ['wheel alignment', 'tire alignment', 'achsvermessung', 'развал', 'сходження'],
            'car_service' => ['car service', 'autoservice', 'autowerkstatt', 'sto', 'сервис', 'автосервіс', 'сто'],
            'car_repair' => ['car repair', 'autorepair', 'auto repair', 'авторемонт', 'ремонт авто'],
            'engine_repair' => ['engine repair', 'motorreparatur', 'двигун', 'мотор'],
            'transmission_repair' => ['transmission', 'gearbox', 'getriebe', 'трансміс', 'коробка'],
            'electrical_repair' => ['electrical', 'elektrik', 'elektro', 'автоелектрик', 'електрика'],
            'diagnostics' => ['diagnostics', 'diagnose', 'діагност', 'диагност'],
            'oil_change' => ['oil change', 'olwechsel', 'zamina masla', 'заміна масла', 'масло'],
            'car_glass' => ['car glass', 'autoglas', 'скло', 'лобове', 'windshield'],
            'car_audio' => ['car audio', 'audio', 'musik', 'звук', 'музика'],
            'car_alarm' => ['car alarm', 'alarm', 'сигналізац', 'сигнализац'],
            'car_painting' => ['painting', 'lackierung', 'фарбуван', 'покраск'],
            'car_body_repair' => ['body repair', 'karosserie', 'кузовн', 'кузов'],
            'car_air_conditioning' => ['air conditioning', 'klima', 'кондиціонер', 'кондиционер'],
        ];
    }

    /**
     * Hardcoded complete aliases observed in current data/imports.
     *
     * @return array<string,string>
     */
    private static function exactAliasMap(): array
    {
        return [
            'ремонт ходової автомобіля' => 'suspension_repair',
            'зварювальні роботи' => 'welding',
            'реставрація автомобілів' => 'car_restoration',
            'ремонт обладнання' => 'equipment_repair',
            'тюнінг автомобілів' => 'car_tuning',
            'ремонт мотоциклів' => 'motorcycle_repair',
            'тонування скла' => 'window_tinting',
            'установка гбо (газо-балонного обладнання)' => 'lpg_installation',
            'хімчистка' => 'interior_cleaning',
            'ремонт механічних коробок передач' => 'manual_transmission_repair',
            'ремонт гідравлічного обладнання' => 'hydraulic_repair',
            'ремонт сільсько-господарського обладнання' => 'agricultural_equipment_repair',
            'заміна оливи, мастил та фільтрів' => 'oil_change',
            'ремонт радіаторів' => 'radiator_repair',
            'авторозбірка' => 'auto_dismantling',
            'ремонт трейлерів' => 'trailer_repair',
            'піскоструминні роботи (піскоструй)' => 'car_body_repair',
            'ремонт дорожньо-будівельної техніки' => 'construction_equipment_repair',
            'ремонт тракторів' => 'tractor_repair',
            'ремонт повітряних компресорів' => 'air_compressor_repair',
            'ательє' => 'atelier_services',
            'локсмайстер (аварійне відкривання замків)' => 'locksmith_services',
            'autoreparatur' => 'car_repair',
            'inspektion' => 'car_service',
            'tuning & hifi' => 'car_audio',
            'autoaufbereitung' => 'detailing',
            'tüv' => 'vehicle_inspection',
            'tuv' => 'vehicle_inspection',
            'smart repair / spot repair' => 'smart_repair',
            'sonstiges' => 'other_services',
            'schaltung & bremsen' => 'transmission_and_brakes',
        ];
    }
}
