import {
    detectLanguageByRegion,
    getUiText,
    type Lang,
    type UiTextKey,
} from '@/shared/guest-map-display-labels';
import { ref } from 'vue';

export function useGuestLang(options?: { onLanguageChanged?: () => void }) {
    const currentLang = ref<Lang>('en');

    function t(key: UiTextKey): string {
        return getUiText(currentLang.value, key);
    }

    function setLanguage(lang: Lang): void {
        currentLang.value = lang;
        localStorage.setItem('site_lang', lang);
        options?.onLanguageChanged?.();
    }

    function initLanguage(): void {
        const saved = localStorage.getItem('site_lang');
        if (saved === 'en' || saved === 'uk' || saved === 'de') {
            currentLang.value = saved as Lang;
            return;
        }
        currentLang.value = detectLanguageByRegion();
    }

    return { currentLang, t, setLanguage, initLanguage };
}

export type { Lang, UiTextKey };
