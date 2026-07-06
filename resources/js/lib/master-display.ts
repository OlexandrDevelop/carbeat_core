import type { MasterDetails } from '@/types/guest-map';

export const SERVICE_COLORS = [
    '#FF8C42',
    '#3B82F6',
    '#10B981',
    '#A855F7',
    '#EC4899',
    '#F59E0B',
    '#06B6D4',
    '#EF4444',
] as const;

export function serviceEmoji(name: string): string {
    const n = name.toLowerCase();
    if (n.includes('електр')) return '⚡';
    if (n.includes('сантех') || n.includes('водо') || n.includes('труб'))
        return '🔧';
    if (n.includes('манік') || n.includes('педік')) return '💅';
    if (n.includes('зварю') || n.includes('кузов')) return '🔩';
    if (n.includes('гума') || n.includes('шин') || n.includes('колес'))
        return '🛞';
    if (n.includes('мийк') || n.includes('миття')) return '🫧';
    if (n.includes('фарб') || n.includes('покрас')) return '🎨';
    if (n.includes('стрижк') || n.includes('перукар') || n.includes('барбер'))
        return '✂️';
    if (n.includes('масаж')) return '💆';
    if (n.includes('краса') || n.includes('косметик')) return '💄';
    if (n.includes('авто')) return '🚗';
    if (n.includes('тонув')) return '🪟';
    return '🔨';
}

export function masterServiceEmoji(
    master: Pick<MasterDetails, 'services'>,
): string {
    const name = master.services?.[0]?.name ?? '';
    return name ? serviceEmoji(name) : '🔨';
}

export function masterServiceColor(
    master: Pick<MasterDetails, 'id' | 'services'>,
): string {
    const id = master.services?.[0]?.id ?? master.id;
    return SERVICE_COLORS[id % SERVICE_COLORS.length];
}
