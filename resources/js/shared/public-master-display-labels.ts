import type { Lang } from '@/shared/guest-map-display-labels';

export type MasterTextKey =
    | 'shareProfileText'
    | 'verified'
    | 'call'
    | 'claim'
    | 'share'
    | 'route'
    | 'owner'
    | 'openInApp'
    | 'aboutMaster'
    | 'details'
    | 'experience'
    | 'years'
    | 'services'
    | 'city'
    | 'workSchedule'
    | 'portfolio'
    | 'allServices'
    | 'scanOpenInApp'
    | 'copiedLink'
    | 'dayOff';

export const MASTER_UI_TEXT: Record<Lang, Record<MasterTextKey, string>> = {
    en: {
        shareProfileText: 'My beauty master profile in Floxcity',
        verified: 'Verified',
        call: 'Call',
        claim: "It's me",
        share: 'Share',
        route: 'Route',
        owner: 'Owner?',
        openInApp: 'Open in app',
        aboutMaster: 'About master',
        details: 'Details',
        experience: 'Experience',
        years: 'years',
        services: 'Services',
        city: 'City',
        workSchedule: 'Work schedule',
        portfolio: 'Portfolio',
        allServices: 'All services',
        scanOpenInApp: 'Scan to open in Floxcity',
        copiedLink: 'Link copied',
        dayOff: 'Day off',
    },
    uk: {
        shareProfileText: 'Мій профіль майстра краси у Floxcity',
        verified: 'Підтверджено',
        call: 'Подзвонити',
        claim: 'Це я',
        share: 'Поділитися',
        route: 'Маршрут',
        owner: 'Власник?',
        openInApp: 'Відкрити у додатку',
        aboutMaster: 'Про майстра',
        details: 'Деталі',
        experience: 'Досвід',
        years: 'років',
        services: 'Послуги',
        city: 'Місто',
        workSchedule: 'Графік роботи',
        portfolio: 'Портфоліо',
        allServices: 'Всі послуги',
        scanOpenInApp: 'Відскануйте, щоб відкрити у Floxcity',
        copiedLink: 'Посилання скопійовано',
        dayOff: 'Вихідний',
    },
    de: {
        shareProfileText: 'Mein Beauty-Profil bei Floxcity',
        verified: 'Bestätigt',
        call: 'Anrufen',
        claim: 'Das bin ich',
        share: 'Teilen',
        route: 'Route',
        owner: 'Inhaber?',
        openInApp: 'In App öffnen',
        aboutMaster: 'Über den Meister',
        details: 'Details',
        experience: 'Erfahrung',
        years: 'Jahre',
        services: 'Leistungen',
        city: 'Stadt',
        workSchedule: 'Arbeitszeiten',
        portfolio: 'Portfolio',
        allServices: 'Alle Leistungen',
        scanOpenInApp: 'Scannen, um in Floxcity zu öffnen',
        copiedLink: 'Link kopiert',
        dayOff: 'Ruhetag',
    },
};

const DAY_LABELS: Record<Lang, Record<string, string>> = {
    en: {
        mon: 'Mon',
        tue: 'Tue',
        wed: 'Wed',
        thu: 'Thu',
        fri: 'Fri',
        sat: 'Sat',
        sun: 'Sun',
    },
    uk: {
        mon: 'Пн',
        tue: 'Вт',
        wed: 'Ср',
        thu: 'Чт',
        fri: 'Пт',
        sat: 'Сб',
        sun: 'Нд',
    },
    de: {
        mon: 'Mo',
        tue: 'Di',
        wed: 'Mi',
        thu: 'Do',
        fri: 'Fr',
        sat: 'Sa',
        sun: 'So',
    },
};

export function masterText(lang: Lang, key: MasterTextKey): string {
    return MASTER_UI_TEXT[lang][key] ?? MASTER_UI_TEXT.en[key] ?? key;
}

export function dayLabel(lang: Lang, dayKey: string): string {
    return DAY_LABELS[lang]?.[dayKey] ?? DAY_LABELS.en[dayKey] ?? dayKey;
}
