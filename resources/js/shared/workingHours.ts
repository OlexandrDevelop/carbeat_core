export type WorkingHoursData = Record<
    string,
    Array<{ open: string; close: string }> | null | undefined
>;

export type WorkStatus = {
    isOpen: boolean;
    nextTime: string;
    nextDay: string | null; // null = today, otherwise full day name e.g. 'wednesday'
};

const DAY_INDEX = [
    'sunday',
    'monday',
    'tuesday',
    'wednesday',
    'thursday',
    'friday',
    'saturday',
];

export const SCHEDULE_ORDER = [
    'monday',
    'tuesday',
    'wednesday',
    'thursday',
    'friday',
    'saturday',
    'sunday',
];

const SHORT_DAY: Record<string, Record<string, string>> = {
    en: {
        monday: 'Mon',
        tuesday: 'Tue',
        wednesday: 'Wed',
        thursday: 'Thu',
        friday: 'Fri',
        saturday: 'Sat',
        sunday: 'Sun',
    },
    uk: {
        monday: 'Пн',
        tuesday: 'Вт',
        wednesday: 'Ср',
        thursday: 'Чт',
        friday: 'Пт',
        saturday: 'Сб',
        sunday: 'Нд',
    },
    de: {
        monday: 'Mo',
        tuesday: 'Di',
        wednesday: 'Mi',
        thursday: 'Do',
        friday: 'Fr',
        saturday: 'Sa',
        sunday: 'So',
    },
};

export function getShortDayLabel(lang: string, dayKey: string): string {
    return SHORT_DAY[lang]?.[dayKey] ?? SHORT_DAY.en?.[dayKey] ?? dayKey;
}

function toMinutes(time: string): number {
    const [h = 0, m = 0] = time.split(':').map(Number);
    return h * 60 + m;
}

export function getWorkStatus(
    hours: WorkingHoursData | null | undefined,
): WorkStatus | null {
    if (!hours || typeof hours !== 'object' || Array.isArray(hours))
        return null;

    const now = new Date();
    const todayIndex = now.getDay();
    const currentMin = now.getHours() * 60 + now.getMinutes();
    const todayKey = DAY_INDEX[todayIndex];
    if (!todayKey) return null;

    const todaySlots = hours[todayKey];
    if (Array.isArray(todaySlots) && todaySlots.length > 0) {
        const slot = todaySlots[0];
        if (slot) {
            const openMin = toMinutes(slot.open);
            const closeMin = toMinutes(slot.close);
            if (currentMin >= openMin && currentMin < closeMin) {
                return { isOpen: true, nextTime: slot.close, nextDay: null };
            }
            if (currentMin < openMin) {
                return { isOpen: false, nextTime: slot.open, nextDay: null };
            }
        }
    }

    for (let i = 1; i <= 7; i++) {
        const nextKey = DAY_INDEX[(todayIndex + i) % 7];
        if (!nextKey) continue;
        const nextSlots = hours[nextKey];
        if (Array.isArray(nextSlots) && nextSlots.length > 0 && nextSlots[0]) {
            return {
                isOpen: false,
                nextTime: nextSlots[0].open,
                nextDay: nextKey,
            };
        }
    }

    return null;
}

export function formatOpenLabel(lang: string, isOpen: boolean): string {
    if (isOpen) {
        if (lang === 'uk') return 'Відчинено';
        if (lang === 'de') return 'Geöffnet';
        return 'Open';
    }
    if (lang === 'uk') return 'Зачинено';
    if (lang === 'de') return 'Geschlossen';
    return 'Closed';
}

export function formatNextEventLabel(lang: string, status: WorkStatus): string {
    const dayStr = status.nextDay
        ? `${getShortDayLabel(lang, status.nextDay)} `
        : '';
    if (status.isOpen) {
        if (lang === 'uk') return `Зачиняється о ${status.nextTime}`;
        if (lang === 'de') return `Schließt um ${status.nextTime}`;
        return `Closes at ${status.nextTime}`;
    }
    if (lang === 'uk') return `Відчиняється ${dayStr}о ${status.nextTime}`;
    if (lang === 'de') return `Öffnet ${dayStr}um ${status.nextTime}`;
    return `Opens ${dayStr}at ${status.nextTime}`;
}

export function scheduleEnter(el: Element): void {
    const h = el as HTMLElement;
    h.style.height = '0';
    h.style.opacity = '0';
    h.style.overflow = 'hidden';
    requestAnimationFrame(() => {
        h.style.transition =
            'height 0.38s cubic-bezier(0.4,0,0.2,1), opacity 0.32s ease';
        h.style.height = h.scrollHeight + 'px';
        h.style.opacity = '1';
    });
}

export function scheduleAfterEnter(el: Element): void {
    const h = el as HTMLElement;
    h.style.height = '';
    h.style.opacity = '';
    h.style.overflow = '';
    h.style.transition = '';
}

export function scheduleLeave(el: Element): void {
    const h = el as HTMLElement;
    h.style.height = h.scrollHeight + 'px';
    h.style.overflow = 'hidden';
    requestAnimationFrame(() => {
        h.style.transition =
            'height 0.3s cubic-bezier(0.4,0,0.2,1), opacity 0.22s ease';
        h.style.height = '0';
        h.style.opacity = '0';
    });
}

export function scheduleAfterLeave(el: Element): void {
    const h = el as HTMLElement;
    h.style.height = '';
    h.style.opacity = '';
    h.style.overflow = '';
    h.style.transition = '';
}

const SCHEMA_DAY: Record<string, string> = {
    monday: 'https://schema.org/Monday',
    tuesday: 'https://schema.org/Tuesday',
    wednesday: 'https://schema.org/Wednesday',
    thursday: 'https://schema.org/Thursday',
    friday: 'https://schema.org/Friday',
    saturday: 'https://schema.org/Saturday',
    sunday: 'https://schema.org/Sunday',
};

export function buildOpeningHoursSpecification(
    hours: WorkingHoursData | null | undefined,
): Array<Record<string, string>> | null {
    if (!hours || typeof hours !== 'object' || Array.isArray(hours))
        return null;

    const specs = SCHEDULE_ORDER.flatMap((key) => {
        const slots = hours[key];
        if (!Array.isArray(slots)) return [];
        return slots
            .filter((slot) => slot && slot.open && slot.close)
            .map((slot) => ({
                '@type': 'OpeningHoursSpecification',
                dayOfWeek: SCHEMA_DAY[key] ?? key,
                opens: slot.open,
                closes: slot.close,
            }));
    });

    return specs.length ? specs : null;
}

export function formatWorkingHours(
    hours: WorkingHoursData | null | undefined,
): Array<{ dayKey: string; value: string | null }> {
    if (!hours || typeof hours !== 'object' || Array.isArray(hours)) return [];

    return SCHEDULE_ORDER.filter((key) => key in hours).map((key) => {
        const slots = hours[key];
        if (!Array.isArray(slots) || slots.length === 0)
            return { dayKey: key, value: null };
        const slot = slots[0];
        if (slot && 'open' in slot && 'close' in slot) {
            return { dayKey: key, value: `${slot.open} – ${slot.close}` };
        }
        return { dayKey: key, value: null };
    });
}
