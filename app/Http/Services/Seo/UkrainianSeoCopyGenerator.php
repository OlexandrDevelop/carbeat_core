<?php

declare(strict_types=1);

namespace App\Http\Services\Seo;

use App\Enums\AppBrand;
use App\Models\City;
use App\Models\Service;
use Illuminate\Support\Str;

/**
 * Generates natural, SEO-friendly Ukrainian copy (title/description/intro/sections/faq)
 * for city and service landing pages, for BOTH verticals this platform runs under the
 * hood: Carbeat (auto service stations) and FloxCity (beauty salons) — see the
 * "Carbeat (automotive)" vs "FloxCity (beauty)" sections in
 * `database/seeders/ServiceTranslationsSeeder.php`, and the `/sto/{slug}` vs
 * `/salon/{slug}` profile routes. Vocabulary (station vs salon, СТО vs beauty master,
 * AutoRepair vs BeautySalon schema.org type) branches on `AppBrand` via `vertical()`;
 * everything else (city declension, numeral agreement, page structure) is shared.
 *
 * There is no morphology library in this project, and the `cities` table is not limited
 * to the ~25 oblast capitals seeded by migration — the ratelist.top import pipeline can
 * create arbitrary new city rows from freeform scraped addresses. So city names cannot be
 * inflected via a small hardcoded table alone; instead `locativeCityPhrase()` runs a small
 * set of suffix-based Ukrainian declension rules (locative/місцевий відмінок) that covers
 * the regular toponym patterns (-ів/-їв, -ськ/-цьк, -ський/-цький, -а/-я, -ка/-га/-ха, -е/-о),
 * with a tiny exception list only for the genuinely irregular pluralia tantum names
 * (Суми, Черкаси, Чернівці and a few similar towns). Any name that still doesn't match
 * anything safely falls back to the always-grammatical "у місті {Name}" form. Service names stay in the
 * nominative case everywhere (quoted as a standalone term), which avoids needing to decline
 * them too.
 */
class UkrainianSeoCopyGenerator
{
    private const VERTICAL_CARBEAT = [
        'entityOne' => 'станція',
        'entityFew' => 'станції',
        'entityMany' => 'станцій',
        'placeNounTitle' => 'СТО та автосервіси',
        'placeNounMid' => 'СТО та автосервіси',
        'placeNounSingular' => 'автосервіс',
        'schemaType' => 'AutoRepair',
    ];

    private const VERTICAL_FLOXCITY = [
        'entityOne' => 'салон',
        'entityFew' => 'салони',
        'entityMany' => 'салонів',
        'placeNounTitle' => 'Салони краси та майстри',
        'placeNounMid' => 'салони краси та майстрів',
        'placeNounSingular' => 'салон краси',
        'schemaType' => 'BeautySalon',
    ];

    private const CITY_LOCATIVE_EXCEPTIONS = [
        'Суми' => 'Сумах',
        'Черкаси' => 'Черкасах',
        'Чернівці' => 'Чернівцях',
        'Ромни' => 'Ромнах',
        'Лубни' => 'Лубнах',
        'Прилуки' => 'Прилуках',
    ];

    private const SOFT_CONSONANTS_BEFORE_IV = ['ч', 'щ', 'й', 'ц'];

    public function locativeCityPhrase(City $city): string
    {
        $name = trim($city->name);

        if ($name === '') {
            return 'поруч';
        }

        $form = $this->locativeForm($name);

        return ($this->startsWithVowel($form) ? 'в ' : 'у ') . $form;
    }

    /**
     * Suffix-based Ukrainian locative declension. Rules are ordered from most to least
     * specific so a longer, more distinctive suffix (e.g. "-ське", "-ський") is always
     * checked before a shorter one it could otherwise be mistaken for (e.g. plain "-е").
     */
    private function locativeForm(string $name): string
    {
        $lower = mb_strtolower($name, 'UTF-8');

        if (isset(self::CITY_LOCATIVE_EXCEPTIONS[$name])) {
            return self::CITY_LOCATIVE_EXCEPTIONS[$name];
        }

        // Adjectival neuter "-ське"/"-цьке" → "-ському"/"-цькому" (Кам'янське → Кам'янському)
        if (Str::endsWith($lower, 'ьке')) {
            return mb_substr($name, 0, -1) . 'ому';
        }

        // Adjectival masculine "-ський"/"-цький"/"-зький" → "-ському"/"-цькому"/"-зькому"
        // (Кропивницький → Кропивницькому) — checked before the generic "-ьк" rule below,
        // and deliberately NOT a bare "-ий" check, since plenty of real toponyms end in
        // "-ий" without being this adjectival pattern (e.g. Стрий).
        if (Str::endsWith($lower, ['ський', 'цький', 'зький'])) {
            return mb_substr($name, 0, -2) . 'ому';
        }

        // Adjectival masculine "-ськ"/"-цьк" → append "у" (Донецьк → Донецьку, Луцьк → Луцьку)
        if (Str::endsWith($lower, 'ьк')) {
            return $name . 'у';
        }

        // Patronymic "-ів"/"-їв" toponyms (Львів → Львові, Київ → Києві, Харків → Харкові,
        // Миколаїв → Миколаєві) — extremely common Ukrainian city-name suffix.
        if (Str::endsWith($lower, 'їв')) {
            return mb_substr($name, 0, -2) . 'єві';
        }
        if (Str::endsWith($lower, 'ів')) {
            $before = mb_substr($lower, -3, 1);
            $soft = in_array($before, self::SOFT_CONSONANTS_BEFORE_IV, true);

            return mb_substr($name, 0, -2) . ($soft ? 'еві' : 'ові');
        }

        // Pluralia tantum toponyms ending in "-и"/"-ці" (Бровари → Броварах, Ромни → Ромнах,
        // Прилуки → Прилуках, Чернівці → Чернівцях) — no real Ukrainian city name ends in a
        // bare singular "-и", so this suffix reliably signals a plural-only toponym.
        if (Str::endsWith($lower, 'ці')) {
            return mb_substr($name, 0, -1) . 'ях';
        }
        if (Str::endsWith($lower, 'и')) {
            return mb_substr($name, 0, -1) . 'ах';
        }

        // Feminine velar-stem palatalization (Волноваха → Волновасі)
        if (Str::endsWith($lower, 'ка')) {
            return mb_substr($name, 0, -2) . 'ці';
        }
        if (Str::endsWith($lower, 'га')) {
            return mb_substr($name, 0, -2) . 'зі';
        }
        if (Str::endsWith($lower, 'ха')) {
            return mb_substr($name, 0, -2) . 'сі';
        }

        // Regular feminine "-а"/"-я" → "-і" (Одеса → Одесі, Полтава → Полтаві, Вінниця → Вінниці)
        if (Str::endsWith($lower, 'а') || Str::endsWith($lower, 'я')) {
            return mb_substr($name, 0, -1) . 'і';
        }

        // Neuter "-е"/"-є" → "-ому" (Рівне → Рівному)
        if (Str::endsWith($lower, 'е') || Str::endsWith($lower, 'є')) {
            return mb_substr($name, 0, -1) . 'ому';
        }

        // "-о" ending (Дніпро → Дніпрі, Мукачево → Мукачеві)
        if (Str::endsWith($lower, 'о')) {
            return mb_substr($name, 0, -1) . 'і';
        }

        // "-іль" with the common і/о stem alternation (Тернопіль → Тернополі) — checked
        // before the generic "-ь" default below, which would otherwise match it too.
        if (Str::endsWith($lower, 'іль')) {
            return mb_substr($name, 0, -3) . 'олі';
        }

        // Default: hard/soft-consonant-ending masculine → "-і" (Житомир → Житомирі,
        // Ужгород → Ужгороді, Сімферополь → Сімферополі).
        if (Str::endsWith($lower, 'ь')) {
            return mb_substr($name, 0, -1) . 'і';
        }

        return $name . 'і';
    }

    private function startsWithVowel(string $value): bool
    {
        return (bool) preg_match('/^[аеєиіїоуюя]/iu', $value);
    }

    public function citySeo(City $city, int $mastersCount, array $popularServiceNames, AppBrand $brand, string $brandName): array
    {
        $vertical = $this->vertical($brand);
        $locative = $this->locativeCityPhrase($city);
        $entityWord = $this->entityWord($mastersCount, $vertical);
        $servicesClause = $popularServiceNames !== []
            ? ', зокрема ' . implode(', ', array_map(fn (string $name) => mb_strtolower($name), $popularServiceNames))
            : '';

        $titleBase = match ($this->variant($city->name . $brandName, 3)) {
            0 => "{$vertical['placeNounTitle']} {$locative}",
            1 => "{$vertical['placeNounTitle']} {$locative}: рейтинг, адреси та відгуки",
            default => $brand === AppBrand::FLOXCITY
                ? "Де знайти майстра краси {$locative}"
                : "Де відремонтувати авто {$locative}",
        };

        $description = mb_substr(
            "Знайдіть перевірені {$vertical['placeNounMid']} {$locative}. На карті {$brandName} — {$mastersCount} {$entityWord} " .
            "із рейтингами, адресами та прямими профілями{$servicesClause}. Порівнюйте варіанти та обирайте найближчий {$vertical['placeNounSingular']} за кілька хвилин.",
            0,
            160,
        );

        return [
            'title' => $titleBase,
            'metaTitle' => "{$titleBase} · {$brandName}",
            'description' => $description,
            'intro' => "Порівняйте {$vertical['placeNounMid']} {$locative} — рейтинги, адреси, послуги та контакти на інтерактивній карті {$brandName}.",
            'sections' => [
                [
                    'heading' => "Як обрати {$vertical['placeNounSingular']} {$locative}",
                    'body' => "Скористайтеся фільтрами на карті {$brandName}, щоб порівняти {$vertical['entityFew']} за рейтингом, відстанню та видом послуг. Відкрийте профіль обраного, щоб побачити адресу, графік роботи та відгуки інших клієнтів.",
                ],
                [
                    'heading' => "Популярні послуги {$locative}",
                    'body' => $popularServiceNames !== []
                        ? 'Найчастіше клієнти шукають: ' . implode(', ', $popularServiceNames) . '. Скористайтеся посиланнями на послуги нижче, щоб одразу перейти до потрібної категорії.'
                        : "Скористайтеся посиланнями на послуги нижче, щоб звузити список {$locative} до конкретного виду послуги.",
                ],
            ],
            'faq' => [
                [
                    'q' => "Як знайти надійний {$vertical['placeNounSingular']} {$locative}?",
                    'a' => "Відкрийте карту {$brandName} для міста {$city->name}: порівняйте рейтинги, адреси та відгуки, а потім перейдіть у профіль обраного, щоб подзвонити або прокласти маршрут.",
                ],
                [
                    'q' => "Чи можна відфільтрувати {$vertical['placeNounMid']} {$locative} за видом послуги?",
                    'a' => 'Так. Використовуйте посилання на послуги нижче, щоб звузити список до конкретного виду послуги.',
                ],
            ],
        ];
    }

    public function serviceSeo(Service $service, string $serviceName, int $mastersCount, AppBrand $brand, string $brandName): array
    {
        $vertical = $this->vertical($brand);
        $entityWord = $this->entityWord($mastersCount, $vertical);

        $titleBase = match ($this->variant($service->name . $brandName, 3)) {
            0 => $brand === AppBrand::FLOXCITY
                ? "{$serviceName} — салони та майстри поруч"
                : "{$serviceName} — СТО та майстри поруч",
            1 => "{$serviceName}: де зробити та скільки коштує",
            default => $brand === AppBrand::FLOXCITY
                ? "{$serviceName} в Україні: рейтинг салонів"
                : "{$serviceName} в Україні: рейтинг станцій",
        };

        $description = mb_substr(
            "«{$serviceName}» — послуга, яку пропонують {$mastersCount} {$entityWord} на карті {$brandName}. " .
            "Порівняйте рейтинги, адреси та відгуки перевірених {$vertical['entityMany']}, і оберіть найближчий {$vertical['placeNounSingular']} у своєму місті.",
            0,
            160,
        );

        return [
            'title' => $titleBase,
            'metaTitle' => "{$titleBase} · {$brandName}",
            'description' => $description,
            'intro' => "Порівняйте {$vertical['entityFew']}, що надають послугу «{$serviceName}», — рейтинги, адреси та контакти на карті {$brandName} по всій Україні.",
            'sections' => [
                [
                    'heading' => "Як обрати {$vertical['placeNounSingular']} для послуги «{$serviceName}»",
                    'body' => "Відкрийте карту {$brandName} і порівняйте {$vertical['entityFew']} за рейтингом та відстанню. У кожному профілі ви побачите адресу, графік роботи та відгуки клієнтів.",
                ],
                [
                    'heading' => "Де саме шукати «{$serviceName}»",
                    'body' => 'Скористайтеся посиланнями на міста нижче, щоб звузити пошук до потрібного населеного пункту.',
                ],
            ],
            'faq' => [
                [
                    'q' => "Де зробити «{$serviceName}» поруч?",
                    'a' => "Скористайтеся картою {$brandName}, щоб побачити всі {$vertical['entityFew']} з послугою «{$serviceName}» у вашому місті, порівняти рейтинги та відкрити профіль з контактами.",
                ],
                [
                    'q' => 'Чи можна звузити пошук до одного міста?',
                    'a' => "Так. Оберіть потрібне місто в списку нижче, щоб переглянути лише {$vertical['entityFew']} цього населеного пункту.",
                ],
            ],
        ];
    }

    public function cityServiceSeo(City $city, Service $service, string $serviceName, int $mastersCount, AppBrand $brand, string $brandName): array
    {
        $vertical = $this->vertical($brand);
        $locative = $this->locativeCityPhrase($city);
        $entityWord = $this->entityWord($mastersCount, $vertical);
        $titleBase = "{$serviceName} {$locative}";

        $description = mb_substr(
            "Шукаєте «{$serviceName}» {$locative}? На карті {$brandName} — {$mastersCount} {$entityWord} із рейтингами та адресами. " .
            'Порівняйте варіанти та оберіть найближчий.',
            0,
            160,
        );

        return [
            'title' => $titleBase,
            'metaTitle' => "{$titleBase} · {$brandName}",
            'description' => $description,
            'intro' => "Порівняйте {$vertical['entityFew']} з послугою «{$serviceName}» {$locative} — рейтинги, адреси та контакти на карті {$brandName}.",
            'sections' => [
                [
                    'heading' => "Знайти «{$serviceName}» {$locative}",
                    'body' => "Ця сторінка звужує карту {$brandName} до {$vertical['entityMany']} із послугою «{$serviceName}» {$locative}. Так порівнювати варіанти швидше, ніж на загальній карті міста.",
                ],
                [
                    'heading' => 'Як користуватись цією сторінкою',
                    'body' => "Відкрийте профіль будь-якого зі списку, щоб побачити адресу, графік роботи та відгуки. Щоб побачити всі {$vertical['entityFew']} міста, поверніться на сторінку міста за посиланням нижче.",
                ],
            ],
            'faq' => [
                [
                    'q' => "Де зробити «{$serviceName}» {$locative}?",
                    'a' => "Скористайтеся списком нижче, щоб порівняти {$vertical['entityFew']} з послугою «{$serviceName}» {$locative}, а потім відкрийте профіль обраного на карті.",
                ],
                [
                    'q' => "Чи можна переглянути всі {$vertical['entityFew']} міста?",
                    'a' => "Так. Сторінка міста показує всі {$vertical['entityFew']} {$locative}, а ця — звужує список до «{$serviceName}».",
                ],
            ],
        ];
    }

    /**
     * Exposed so callers outside this class (master-profile / generic-landing SEO builders
     * in PublicGuestMapController) can reuse the same vocabulary without duplicating it.
     */
    public function vertical(AppBrand $brand): array
    {
        return $brand === AppBrand::FLOXCITY ? self::VERTICAL_FLOXCITY : self::VERTICAL_CARBEAT;
    }

    /**
     * Ukrainian numeral agreement (1 → entityOne, 2-4 → entityFew, 0/5-20 → entityMany),
     * with the actual words coming from the brand's vertical (станція/станції/станцій for
     * Carbeat, салон/салони/салонів for FloxCity).
     */
    private function entityWord(int $count, array $vertical): string
    {
        $mod100 = $count % 100;
        $mod10 = $count % 10;

        if ($mod100 >= 11 && $mod100 <= 14) {
            return $vertical['entityMany'];
        }

        return match (true) {
            $mod10 === 1 => $vertical['entityOne'],
            $mod10 >= 2 && $mod10 <= 4 => $vertical['entityFew'],
            default => $vertical['entityMany'],
        };
    }

    private function variant(string $seed, int $count): int
    {
        return $count > 0 ? crc32($seed) % $count : 0;
    }
}
