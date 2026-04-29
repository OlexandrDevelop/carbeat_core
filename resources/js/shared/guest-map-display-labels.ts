export type Lang = 'en' | 'uk' | 'de';

export type UiTextKey =
    | 'allServices'
    | 'availableOnly'
    | 'appDownloadCta'
    | 'myGeo'
    | 'loading'
    | 'call'
    | 'profile'
    | 'route'
    | 'claim'
    | 'askStatus'
    | 'sending'
    | 'mainService'
    | 'extraServices'
    | 'reviews'
    | 'noReviews'
    | 'anonymous'
    | 'statusSent'
    | 'statusError';

export const UI_TEXT: Record<Lang, Record<UiTextKey, string>> = {
    en: {
        allServices: 'All services',
        availableOnly: 'Available',
        appDownloadCta: 'Better in the app',
        myGeo: 'My location',
        loading: 'Loading...',
        call: 'Call',
        profile: 'Profile',
        route: 'Route',
        claim: "It's me",
        askStatus: 'Ask if available now',
        sending: 'Sending...',
        mainService: 'Main service',
        extraServices: 'Additional services',
        reviews: 'Reviews',
        noReviews: 'No reviews yet',
        anonymous: 'Anonymous',
        statusSent: 'Request sent to master.',
        statusError: 'Failed to send request.',
    },
    uk: {
        allServices: 'Всі послуги',
        availableOnly: 'Вільні',
        appDownloadCta: 'В додатку зручніше',
        myGeo: 'Моя гео',
        loading: 'Завантаження...',
        call: 'Подзвонити',
        profile: 'Профіль',
        route: 'Маршрут',
        claim: 'Це я',
        askStatus: 'Запитати доступність',
        sending: 'Надсилаємо...',
        mainService: 'Основна послуга',
        extraServices: 'Додаткові послуги',
        reviews: 'Відгуки',
        noReviews: 'Поки що немає відгуків',
        anonymous: 'Анонім',
        statusSent: 'Запит відправлено майстру.',
        statusError: 'Не вдалося відправити запит.',
    },
    de: {
        allServices: 'Alle Leistungen',
        availableOnly: 'Verfügbar',
        appDownloadCta: 'In der App bequemer',
        myGeo: 'Mein Standort',
        loading: 'Laden...',
        call: 'Anrufen',
        profile: 'Profil',
        route: 'Route',
        claim: 'Das bin ich',
        askStatus: 'Verfügbarkeit anfragen',
        sending: 'Wird gesendet...',
        mainService: 'Hauptleistung',
        extraServices: 'Zusätzliche Leistungen',
        reviews: 'Bewertungen',
        noReviews: 'Noch keine Bewertungen',
        anonymous: 'Anonym',
        statusSent: 'Anfrage wurde gesendet.',
        statusError: 'Anfrage konnte nicht gesendet werden.',
    },
};

export const SERVICE_LABELS: Record<string, Record<Lang, string>> = {
    tire_service: { en: 'Tire service', uk: 'Шиномонтаж', de: 'Reifenservice' },
    tire_balancing: {
        en: 'Wheel balancing',
        uk: 'Балансування коліс',
        de: 'Radauswuchten',
    },
    tire_alignment: {
        en: 'Wheel alignment',
        uk: 'Розвал-сходження',
        de: 'Achsvermessung',
    },
    car_service: {
        en: 'Car service',
        uk: 'Автосервіс (СТО)',
        de: 'Autoservice',
    },
    car_repair: { en: 'Car repair', uk: 'Ремонт авто', de: 'Autoreparatur' },
    engine_repair: {
        en: 'Engine repair',
        uk: 'Ремонт двигуна',
        de: 'Motorreparatur',
    },
    transmission_repair: {
        en: 'Transmission repair',
        uk: 'Ремонт трансмісії',
        de: 'Getriebereparatur',
    },
    electrical_repair: {
        en: 'Auto electrical',
        uk: 'Автоелектрика',
        de: 'Autoelektrik',
    },
    diagnostics: { en: 'Diagnostics', uk: 'Діагностика', de: 'Diagnose' },
    oil_change: { en: 'Oil change', uk: 'Заміна оливи', de: 'Ölwechsel' },
    car_glass: { en: 'Car glass', uk: 'Автоскло', de: 'Autoglas' },
    car_audio: { en: 'Car audio', uk: 'Автозвук', de: 'Car Audio' },
    car_alarm: { en: 'Car alarm', uk: 'Сигналізація', de: 'Alarmanlage' },
    car_painting: {
        en: 'Car painting',
        uk: 'Фарбування авто',
        de: 'Lackierung',
    },
    car_body_repair: {
        en: 'Body repair',
        uk: 'Кузовний ремонт',
        de: 'Karosseriereparatur',
    },
    car_air_conditioning: {
        en: 'A/C service',
        uk: 'Кондиціонер авто',
        de: 'Klimaservice',
    },
    suspension_repair: {
        en: 'Suspension repair',
        uk: 'Ремонт ходової',
        de: 'Fahrwerksreparatur',
    },
    welding: { en: 'Welding', uk: 'Зварювальні роботи', de: 'Schweißarbeiten' },
    car_restoration: {
        en: 'Car restoration',
        uk: 'Реставрація авто',
        de: 'Fahrzeugrestaurierung',
    },
    equipment_repair: {
        en: 'Equipment repair',
        uk: 'Ремонт обладнання',
        de: 'Gerätereparatur',
    },
    car_tuning: { en: 'Car tuning', uk: 'Тюнінг авто', de: 'Fahrzeugtuning' },
    motorcycle_repair: {
        en: 'Motorcycle repair',
        uk: 'Ремонт мотоциклів',
        de: 'Motorradreparatur',
    },
    window_tinting: {
        en: 'Window tinting',
        uk: 'Тонування скла',
        de: 'Scheibentönung',
    },
    lpg_installation: {
        en: 'LPG installation',
        uk: 'Установка ГБО',
        de: 'LPG-Installation',
    },
    interior_cleaning: {
        en: 'Interior cleaning',
        uk: 'Хімчистка салону',
        de: 'Innenreinigung',
    },
    manual_transmission_repair: {
        en: 'Manual gearbox repair',
        uk: 'Ремонт МКПП',
        de: 'Schaltgetriebe-Reparatur',
    },
    hydraulic_repair: {
        en: 'Hydraulic repair',
        uk: 'Ремонт гідравліки',
        de: 'Hydraulikreparatur',
    },
    agricultural_equipment_repair: {
        en: 'Agro equipment repair',
        uk: 'Ремонт с/г обладнання',
        de: 'Reparatur landw. Geräte',
    },
    radiator_repair: {
        en: 'Radiator repair',
        uk: 'Ремонт радіаторів',
        de: 'Kühlerreparatur',
    },
    auto_dismantling: {
        en: 'Auto dismantling',
        uk: 'Авторозбірка',
        de: 'Autoverwertung',
    },
    trailer_repair: {
        en: 'Trailer repair',
        uk: 'Ремонт трейлерів',
        de: 'Anhängerreparatur',
    },
    construction_equipment_repair: {
        en: 'Construction equipment repair',
        uk: 'Ремонт будівельної техніки',
        de: 'Baumaschinenreparatur',
    },
    tractor_repair: {
        en: 'Tractor repair',
        uk: 'Ремонт тракторів',
        de: 'Traktorreparatur',
    },
    air_compressor_repair: {
        en: 'Air compressor repair',
        uk: 'Ремонт компресорів',
        de: 'Kompressorreparatur',
    },
    atelier_services: {
        en: 'Atelier services',
        uk: 'Ательє послуг',
        de: 'Atelierdienste',
    },
    locksmith_services: {
        en: 'Locksmith services',
        uk: 'Аварійне відкривання замків',
        de: 'Schlüsseldienst',
    },
    vehicle_inspection: {
        en: 'Vehicle inspection',
        uk: 'Техогляд / TÜV',
        de: 'Fahrzeugprüfung (TÜV)',
    },
    smart_repair: {
        en: 'Smart repair',
        uk: 'Smart Repair',
        de: 'Smart Repair',
    },
    detailing: { en: 'Detailing', uk: 'Детейлінг', de: 'Aufbereitung' },
    other_services: {
        en: 'Other services',
        uk: 'Інші послуги',
        de: 'Sonstige Leistungen',
    },
    transmission_and_brakes: {
        en: 'Transmission & brakes',
        uk: 'Трансмісія та гальма',
        de: 'Getriebe & Bremsen',
    },
};

export function detectLanguageByRegion(): Lang {
    const raw = (navigator.language || 'en').toLowerCase();
    const [lang, regionRaw] = raw.split('-');
    const region = (regionRaw ?? '').toUpperCase();

    if (region === 'UA' || lang === 'uk') return 'uk';
    if (region === 'DE' || lang === 'de') return 'de';
    if (lang === 'en') return 'en';

    return 'en';
}

export function getUiText(lang: Lang, key: UiTextKey): string {
    return UI_TEXT[lang][key] ?? UI_TEXT.en[key] ?? key;
}
