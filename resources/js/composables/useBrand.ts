import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import type { PageProps } from '../types';
import type { Flavor } from '../types/master-crm';

/**
 * Reads the `brand` prop shared globally by HandleInertiaRequests
 * (app/Http/Middleware/HandleInertiaRequests.php) on every Inertia response.
 */
export function useBrand() {
    const page = usePage<PageProps<{ brand?: string }>>();

    const flavor = computed<Flavor>(() =>
        page.props.brand === 'floxcity' ? 'floxcity' : 'carbeat',
    );
    const isFloxcity = computed(() => flavor.value === 'floxcity');
    const brandName = computed(() =>
        isFloxcity.value ? 'Floxcity' : 'Carbeat',
    );
    const portalThemeClass = computed(() =>
        isFloxcity.value ? 'master-portal-floxcity' : 'master-portal-carbeat',
    );

    return { flavor, isFloxcity, brandName, portalThemeClass };
}
