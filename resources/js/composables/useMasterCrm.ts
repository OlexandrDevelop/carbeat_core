import axios from 'axios';
import { ref } from 'vue';
import type { CrmChange, CrmSnapshot } from '../types/master-crm';

/**
 * Thin client for /master-api/crm/*. Unlike the mobile app, the web portal
 * doesn't need an offline queue/local cache — every sync round-trips
 * immediately and refreshes the snapshot from the response.
 */
export function useMasterCrm() {
    const snapshot = ref<CrmSnapshot | null>(null);
    const isLoading = ref(false);
    const error = ref<string | null>(null);

    async function loadSnapshot(date: string): Promise<void> {
        isLoading.value = true;
        error.value = null;
        try {
            const { data } = await axios.get<CrmSnapshot>(
                '/master-api/crm/snapshot',
                {
                    params: { date },
                },
            );
            snapshot.value = data;
        } catch (e: unknown) {
            error.value = extractErrorMessage(e);
        } finally {
            isLoading.value = false;
        }
    }

    async function sync(
        businessDay: string,
        changes: CrmChange[],
    ): Promise<boolean> {
        isLoading.value = true;
        error.value = null;
        try {
            const { data } = await axios.post<CrmSnapshot>(
                '/master-api/crm/sync',
                {
                    businessDay,
                    changes,
                },
            );
            snapshot.value = data;
            return true;
        } catch (e: unknown) {
            error.value = extractErrorMessage(e);
            return false;
        } finally {
            isLoading.value = false;
        }
    }

    return { snapshot, isLoading, error, loadSnapshot, sync };
}

function extractErrorMessage(e: unknown): string {
    if (typeof e === 'object' && e !== null && 'response' in e) {
        const response = (
            e as { response?: { data?: { message?: string; error?: string } } }
        ).response;
        return (
            response?.data?.message ?? response?.data?.error ?? 'Request failed'
        );
    }
    return 'Request failed';
}
