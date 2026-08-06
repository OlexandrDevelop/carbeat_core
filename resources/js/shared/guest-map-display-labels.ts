export type Lang = 'en' | 'uk' | 'de';

export type UiTextKey =
    | 'availableOnly'
    | 'appDownloadCta'
    | 'repairRequestCta'
    | 'call'
    | 'profile'
    | 'route'
    | 'claim'
    | 'askStatus'
    | 'sending'
    | 'mainService'
    | 'extraServices'
    | 'reviews'
    | 'anonymous'
    | 'statusSent'
    | 'statusError'
    | 'writeReview'
    | 'yourName'
    | 'yourReview'
    | 'submit'
    | 'cancel'
    | 'reply'
    | 'yourReply'
    | 'selectRating'
    | 'reviewSubmitError'
    | 'replySubmitError'
    | 'becomeMaster'
    | 'onboardTitle'
    | 'onboardPhoneLabel'
    | 'onboardContinue'
    | 'onboardFoundProfile'
    | 'onboardCodeLabel'
    | 'onboardVerify'
    | 'onboardResend'
    | 'onboardRegisterTitle'
    | 'onboardNameLabel'
    | 'onboardServiceLabel'
    | 'onboardGenericError'
    | 'rrBadge'
    | 'rrHeadingPrefix'
    | 'rrHeadingHighlight'
    | 'rrSubtitle'
    | 'rrSuccessTitle'
    | 'rrSuccessMessage'
    | 'rrBackHome'
    | 'rrCarMakeLabel'
    | 'rrCarMakePlaceholder'
    | 'rrOther'
    | 'rrCarModelLabel'
    | 'rrOptional'
    | 'rrCarModelPlaceholder'
    | 'rrCarYearLabel'
    | 'rrCityLabel'
    | 'rrCityPlaceholder'
    | 'rrServiceLabel'
    | 'rrServicePlaceholder'
    | 'rrDescriptionLabel'
    | 'rrDescriptionPlaceholder'
    | 'rrNameLabel'
    | 'rrNamePlaceholder'
    | 'rrPhoneLabel'
    | 'rrSubmitSending'
    | 'rrSubmitCta'
    | 'rrOtpTitle'
    | 'rrOtpSubtitle'
    | 'rrOtpVerifying'
    | 'rrOtpVerifyCta'
    | 'rrOtpChangeData'
    | 'rrOtpResend'
    | 'rrValidationError'
    | 'rrGenericError';

export const UI_TEXT: Record<Lang, Record<UiTextKey, string>> = {
    en: {
        availableOnly: 'Available',
        appDownloadCta: 'Better in the app',
        repairRequestCta: 'Create a repair request',
        call: 'Call',
        profile: 'Profile',
        route: 'Route',
        claim: "It's me",
        askStatus: 'Ask if available now',
        sending: 'Sending...',
        mainService: 'Main service',
        extraServices: 'Additional:',
        reviews: 'Reviews',
        anonymous: 'Anonymous',
        statusSent: 'Request sent to master.',
        statusError: 'Failed to send request.',
        writeReview: 'Write a review',
        yourName: 'Your name',
        yourReview: 'Your review',
        submit: 'Submit',
        cancel: 'Cancel',
        reply: 'Reply',
        yourReply: 'Your reply',
        selectRating: 'Please select a rating',
        reviewSubmitError: 'Failed to submit review. Try again.',
        replySubmitError: 'Failed to submit reply. Try again.',
        becomeMaster: "I'm a pro",
        onboardTitle: 'Add your business',
        onboardPhoneLabel: 'Phone number',
        onboardContinue: 'Continue',
        onboardFoundProfile: 'Is this your profile?',
        onboardCodeLabel: 'SMS code',
        onboardVerify: 'Confirm',
        onboardResend: 'Resend code',
        onboardRegisterTitle: 'Tell us about your business',
        onboardNameLabel: 'Business name',
        onboardServiceLabel: 'Service category',
        onboardGenericError: 'Something went wrong. Try again.',
        rrBadge: 'Repair request',
        rrHeadingPrefix: 'Describe the problem —',
        rrHeadingHighlight: "we'll find you a repair shop",
        rrSubtitle:
            'Fill in the form and confirm your phone number with an SMS code — your request will reach trusted repair shops near you.',
        rrSuccessTitle: 'Request received!',
        rrSuccessMessage:
            'Thank you, {0}. A repair shop will contact you soon about your {1}.',
        rrBackHome: 'Back to home',
        rrCarMakeLabel: 'Car make',
        rrCarMakePlaceholder: 'Select a make',
        rrOther: 'Other',
        rrCarModelLabel: 'Model',
        rrOptional: '(optional)',
        rrCarModelPlaceholder: 'e.g. Camry',
        rrCarYearLabel: 'Year',
        rrCityLabel: 'City',
        rrCityPlaceholder: 'Start typing your city…',
        rrServiceLabel: 'Issue type',
        rrServicePlaceholder: 'Select an issue type',
        rrDescriptionLabel: 'Problem description',
        rrDescriptionPlaceholder: 'Describe what happened to the car…',
        rrNameLabel: 'Your name',
        rrNamePlaceholder: 'Name',
        rrPhoneLabel: 'Phone number',
        rrSubmitSending: 'Sending…',
        rrSubmitCta: 'Send confirmation code',
        rrOtpTitle: 'Confirm your phone number',
        rrOtpSubtitle: 'We sent an SMS code to {0}',
        rrOtpVerifying: 'Verifying…',
        rrOtpVerifyCta: 'Confirm and submit request',
        rrOtpChangeData: 'Edit details',
        rrOtpResend: 'Resend code',
        rrValidationError: 'Please check the form fields',
        rrGenericError: 'Something went wrong. Please try again.',
    },
    uk: {
        availableOnly: 'Вільні',
        appDownloadCta: 'В додатку зручніше',
        repairRequestCta: 'Створити заявку на ремонт',
        call: 'Подзвонити',
        profile: 'Профіль',
        route: 'Маршрут',
        claim: 'Це я',
        askStatus: 'Запитати доступність',
        sending: 'Надсилаємо...',
        mainService: 'Основна послуга',
        extraServices: 'Додатково:',
        reviews: 'Відгуки',
        anonymous: 'Анонім',
        statusSent: 'Запит відправлено майстру.',
        statusError: 'Не вдалося відправити запит.',
        writeReview: 'Написати відгук',
        yourName: "Ваше ім'я",
        yourReview: 'Ваш відгук',
        submit: 'Надіслати',
        cancel: 'Скасувати',
        reply: 'Відповісти',
        yourReply: 'Ваша відповідь',
        selectRating: 'Оберіть оцінку',
        reviewSubmitError: 'Не вдалося надіслати відгук. Спробуйте ще раз.',
        replySubmitError: 'Не вдалося надіслати відповідь. Спробуйте ще раз.',
        becomeMaster: 'Я майстер',
        onboardTitle: 'Додайте свій бізнес',
        onboardPhoneLabel: 'Номер телефону',
        onboardContinue: 'Продовжити',
        onboardFoundProfile: 'Це ваш профіль?',
        onboardCodeLabel: 'Код з SMS',
        onboardVerify: 'Підтвердити',
        onboardResend: 'Надіслати код повторно',
        onboardRegisterTitle: 'Розкажіть коротко про бізнес',
        onboardNameLabel: 'Назва бізнесу',
        onboardServiceLabel: 'Категорія послуг',
        onboardGenericError: 'Щось пішло не так. Спробуйте ще раз.',
        rrBadge: 'Заявка на ремонт',
        rrHeadingPrefix: 'Опишіть поломку —',
        rrHeadingHighlight: 'ми підберемо автосервіс',
        rrSubtitle:
            "Заповніть форму, підтвердіть номер телефону кодом з SMS — і ваша заявка потрапить до перевірених СТО поряд з вами.",
        rrSuccessTitle: 'Заявку прийнято!',
        rrSuccessMessage:
            "Дякуємо, {0}. Найближчим часом з вами зв'яжеться автосервіс щодо ремонту {1}.",
        rrBackHome: 'На головну',
        rrCarMakeLabel: 'Марка авто',
        rrCarMakePlaceholder: 'Оберіть марку',
        rrOther: 'Інше',
        rrCarModelLabel: 'Модель',
        rrOptional: "(необов'язково)",
        rrCarModelPlaceholder: 'Напр. Camry',
        rrCarYearLabel: 'Рік випуску',
        rrCityLabel: 'Місто',
        rrCityPlaceholder: 'Почніть вводити назву міста…',
        rrServiceLabel: 'Тип поломки',
        rrServicePlaceholder: 'Оберіть тип поломки',
        rrDescriptionLabel: 'Опис проблеми',
        rrDescriptionPlaceholder: 'Опишіть, що сталося з автомобілем…',
        rrNameLabel: "Ваше ім'я",
        rrNamePlaceholder: "Ім'я",
        rrPhoneLabel: 'Номер телефону',
        rrSubmitSending: 'Надсилаємо…',
        rrSubmitCta: 'Надіслати код підтвердження',
        rrOtpTitle: 'Підтвердіть номер телефону',
        rrOtpSubtitle: 'Ми надіслали SMS-код на {0}',
        rrOtpVerifying: 'Перевіряємо…',
        rrOtpVerifyCta: 'Підтвердити та надіслати заявку',
        rrOtpChangeData: 'Змінити дані',
        rrOtpResend: 'Надіслати код повторно',
        rrValidationError: 'Перевірте, будь ласка, поля форми',
        rrGenericError: 'Щось пішло не так. Спробуйте ще раз.',
    },
    de: {
        availableOnly: 'Verfügbar',
        appDownloadCta: 'In der App bequemer',
        repairRequestCta: 'Reparaturanfrage erstellen',
        call: 'Anrufen',
        profile: 'Profil',
        route: 'Route',
        claim: 'Das bin ich',
        askStatus: 'Verfügbarkeit anfragen',
        sending: 'Wird gesendet...',
        mainService: 'Hauptleistung',
        extraServices: 'Extra:',
        reviews: 'Bewertungen',
        anonymous: 'Anonym',
        statusSent: 'Anfrage wurde gesendet.',
        statusError: 'Anfrage konnte nicht gesendet werden.',
        writeReview: 'Bewertung schreiben',
        yourName: 'Ihr Name',
        yourReview: 'Ihre Bewertung',
        submit: 'Absenden',
        cancel: 'Abbrechen',
        reply: 'Antworten',
        yourReply: 'Ihre Antwort',
        selectRating: 'Bitte wählen Sie eine Bewertung',
        reviewSubmitError:
            'Bewertung konnte nicht gesendet werden. Versuchen Sie es erneut.',
        replySubmitError:
            'Antwort konnte nicht gesendet werden. Versuchen Sie es erneut.',
        becomeMaster: 'Ich bin Profi',
        onboardTitle: 'Business hinzufügen',
        onboardPhoneLabel: 'Telefonnummer',
        onboardContinue: 'Weiter',
        onboardFoundProfile: 'Ist das Ihr Profil?',
        onboardCodeLabel: 'SMS-Code',
        onboardVerify: 'Bestätigen',
        onboardResend: 'Code erneut senden',
        onboardRegisterTitle: 'Erzählen Sie uns von Ihrem Unternehmen',
        onboardNameLabel: 'Firmenname',
        onboardServiceLabel: 'Dienstleistungskategorie',
        onboardGenericError:
            'Etwas ist schiefgelaufen. Versuchen Sie es erneut.',
        rrBadge: 'Reparaturanfrage',
        rrHeadingPrefix: 'Beschreiben Sie das Problem —',
        rrHeadingHighlight: 'wir finden eine passende Werkstatt',
        rrSubtitle:
            'Füllen Sie das Formular aus und bestätigen Sie Ihre Telefonnummer mit einem SMS-Code — Ihre Anfrage erreicht geprüfte Werkstätten in Ihrer Nähe.',
        rrSuccessTitle: 'Anfrage erhalten!',
        rrSuccessMessage:
            'Danke, {0}. Eine Werkstatt wird Sie bald bezüglich Ihres {1} kontaktieren.',
        rrBackHome: 'Zur Startseite',
        rrCarMakeLabel: 'Automarke',
        rrCarMakePlaceholder: 'Marke auswählen',
        rrOther: 'Sonstige',
        rrCarModelLabel: 'Modell',
        rrOptional: '(optional)',
        rrCarModelPlaceholder: 'z. B. Camry',
        rrCarYearLabel: 'Baujahr',
        rrCityLabel: 'Stadt',
        rrCityPlaceholder: 'Stadt eingeben…',
        rrServiceLabel: 'Art des Problems',
        rrServicePlaceholder: 'Problemart auswählen',
        rrDescriptionLabel: 'Problembeschreibung',
        rrDescriptionPlaceholder: 'Beschreiben Sie, was mit dem Auto passiert ist…',
        rrNameLabel: 'Ihr Name',
        rrNamePlaceholder: 'Name',
        rrPhoneLabel: 'Telefonnummer',
        rrSubmitSending: 'Wird gesendet…',
        rrSubmitCta: 'Bestätigungscode senden',
        rrOtpTitle: 'Telefonnummer bestätigen',
        rrOtpSubtitle: 'Wir haben einen SMS-Code an {0} gesendet',
        rrOtpVerifying: 'Wird überprüft…',
        rrOtpVerifyCta: 'Bestätigen und Anfrage senden',
        rrOtpChangeData: 'Angaben ändern',
        rrOtpResend: 'Code erneut senden',
        rrValidationError: 'Bitte überprüfen Sie die Formularfelder',
        rrGenericError: 'Etwas ist schiefgelaufen. Versuchen Sie es erneut.',
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

/**
 * Substitutes {0}, {1}, ... placeholders in a translated string — used for
 * the handful of keys whose wording depends on interpolated values (e.g.
 * `rrSuccessMessage`), where word order can differ across languages so the
 * placeholder must live inside the translated sentence itself.
 */
export function getUiTextWithParams(
    lang: Lang,
    key: UiTextKey,
    params: string[],
): string {
    return params.reduce(
        (text, param, index) => text.replaceAll(`{${index}}`, param),
        getUiText(lang, key),
    );
}
