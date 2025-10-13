<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Masters Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration for different types of masters
    | that can be easily cloned and customized for different projects.
    |
    */

    // Default configuration for auto mechanics
    'auto_mechanics' => [
        'type' => 'auto_mechanics',
        'type_key' => 'auto_mechanics',
        'title' => 'Автомайстри та автомайстерні',
        'description' => 'Знайдіть найкращих автомайстрів та автомайстерні у вашому місті. Якісний ремонт автомобілів, діагностика та обслуговування.',
        'keywords' => 'автомайстри, автомайстерні, ремонт автомобілів, діагностика, обслуговування, технічний огляд',
        'city' => 'Київ',
        'region' => 'Київська область',
        'country' => 'Україна',
        'currency' => 'UAH',
        'phone_country_code' => '+380',

        'specializations' => [
            'Діагностика',
            'Ремонт двигуна',
            'Ремонт ходової',
            'Ремонт електроніки',
            'Кузовний ремонт',
            'Ремонт гальм',
            'Ремонт кондиціонера',
            'Ремонт АКПП',
            'Заміна масла',
            'Технічний огляд',
        ],

        'services' => [
            'Технічний огляд',
            'Заміна масла',
            'Ремонт гальм',
            'Ремонт кондиціонера',
            'Ремонт АКПП',
            'Діагностика двигуна',
            'Ремонт ходової частини',
            'Кузовний ремонт',
            'Ремонт електроніки',
            'Заміна фільтрів',
        ],

        'seo' => [
            'enable_structured_data' => true,
            'enable_sitemap' => true,
            'enable_robots' => true,
            'enable_analytics' => true,
        ],
    ],

    // Configuration for beauty salons (example for easy cloning)
    'beauty_salons' => [
        'type' => 'beauty_salons',
        'type_key' => 'beauty_salons',
        'title' => 'Майстри краси та салони краси',
        'description' => 'Знайдіть найкращих майстрів краси та салони краси у вашому місті. Якісні послуги з догляду за обличчям, волоссям та тілом.',
        'keywords' => 'майстри краси, салони краси, догляд за обличчям, догляд за волоссям, манікюр, педикюр, масаж',
        'city' => 'Київ',
        'region' => 'Київська область',
        'country' => 'Україна',
        'currency' => 'UAH',
        'phone_country_code' => '+380',

        'specializations' => [
            'Догляд за обличчям',
            'Догляд за волоссям',
            'Манікюр',
            'Педикюр',
            'Масаж',
            'Макияж',
            'Перукарські послуги',
            'Косметологія',
            'Епіляція',
            'SPA процедури',
        ],

        'services' => [
            'Чистка обличчя',
            'Масаж обличчя',
            'Маска для обличчя',
            'Стрижка',
            'Фарбування волосся',
            'Манікюр',
            'Педикюр',
            'Масаж тіла',
            'Макияж',
            'Епіляція',
        ],

        'seo' => [
            'enable_structured_data' => true,
            'enable_sitemap' => true,
            'enable_robots' => true,
            'enable_analytics' => true,
        ],
    ],

    // Configuration for home services (example for easy cloning)
    'home_services' => [
        'type' => 'home_services',
        'type_key' => 'home_services',
        'title' => 'Майстри домашніх послуг',
        'description' => 'Знайдіть надійних майстрів для домашніх послуг. Ремонт, прибирання, електроніка та багато іншого.',
        'keywords' => 'домашні послуги, ремонт, прибирання, електроніка, сантехніка, електрика',
        'city' => 'Київ',
        'region' => 'Київська область',
        'country' => 'Україна',
        'currency' => 'UAH',
        'phone_country_code' => '+380',

        'specializations' => [
            'Ремонт квартир',
            'Сантехнічні роботи',
            'Електричні роботи',
            'Прибирання',
            'Ремонт техніки',
            'Встановлення меблів',
            'Ремонт електроніки',
            'Клінінг',
            'Ремонт побутової техніки',
            'Ремонт кондиціонерів',
        ],

        'services' => [
            'Ремонт квартир',
            'Заміна сантехніки',
            'Електричні роботи',
            'Прибирання після ремонту',
            'Ремонт техніки',
            'Встановлення меблів',
            'Ремонт електроніки',
            'Генеральне прибирання',
            'Ремонт побутової техніки',
            'Обслуговування кондиціонерів',
        ],

        'seo' => [
            'enable_structured_data' => true,
            'enable_sitemap' => true,
            'enable_robots' => true,
            'enable_analytics' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Configuration
    |--------------------------------------------------------------------------
    |
    | This is the default configuration that will be used if no specific
    | type is specified or if the specified type doesn't exist.
    |
    */
    'default' => 'auto_mechanics',

    /*
    |--------------------------------------------------------------------------
    | Environment Configuration
    |--------------------------------------------------------------------------
    |
    | You can override the default configuration by setting environment
    | variables. For example:
    | MASTERS_TYPE=beauty_salons
    | MASTERS_CITY=Львів
    |
    */
    'env' => [
        'type' => env('MASTERS_TYPE', 'auto_mechanics'),
        'city' => env('MASTERS_CITY', 'Київ'),
        'region' => env('MASTERS_REGION', 'Київська область'),
        'country' => env('MASTERS_COUNTRY', 'Україна'),
    ],

    /*
    |--------------------------------------------------------------------------
    | SEO Settings
    |--------------------------------------------------------------------------
    |
    | Global SEO settings that can be overridden per type.
    |
    */
    'seo' => [
        'default_locale' => 'uk',
        'supported_locales' => ['uk', 'en'],
        'enable_structured_data' => true,
        'enable_sitemap' => true,
        'enable_robots' => true,
        'enable_analytics' => true,
        'google_analytics_id' => env('GOOGLE_ANALYTICS_ID'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Pagination Settings
    |--------------------------------------------------------------------------
    |
    | Default pagination settings for masters listing.
    |
    */
    'pagination' => [
        'default_per_page' => 20,
        'available_per_page' => [10, 20, 50, 100],
        'max_per_page' => 100,
    ],

    /*
    |--------------------------------------------------------------------------
    | Filter Settings
    |--------------------------------------------------------------------------
    |
    | Default filter settings and ranges.
    |
    */
    'filters' => [
        'rating' => [
            'min' => 1.0,
            'max' => 5.0,
            'step' => 0.5,
        ],
        'age' => [
            'min' => 18,
            'max' => 80,
        ],
        'experience' => [
            'min' => 0,
            'max' => 50,
        ],
        'price' => [
            'min' => 0,
            'max' => 10000,
            'currency' => 'UAH',
        ],
        'distance' => [
            'min' => 1,
            'max' => 50,
            'unit' => 'km',
        ],
    ],
];
