import type { UiTextKey } from '@/composables/useGuestLang';
import { computed, ref } from 'vue';

interface ApiInstance {
    post: (
        url: string,
        data: unknown,
    ) => Promise<{ data: Record<string, unknown> }>;
}

interface UseStatusRequestOptions {
    apiInstance: ApiInstance;
    getMasterId: () => number | null | undefined;
    t: (key: UiTextKey) => string;
}

export function useStatusRequest({
    apiInstance,
    getMasterId,
    t,
}: UseStatusRequestOptions) {
    const isSendingStatusRequest = ref(false);
    const statusRequestMessage = ref('');
    const statusCooldownUntil = ref<Date | null>(null);

    const canRequestMasterStatus = computed(() => {
        if (!getMasterId()) return false;
        if (!statusCooldownUntil.value) return true;
        return statusCooldownUntil.value.getTime() <= Date.now();
    });

    function getGuestDeviceId(): string {
        const storageKey = 'guest_status_request_device_id';
        const existing = localStorage.getItem(storageKey);
        if (existing) return existing;

        let generated: string;
        try {
            if (
                typeof crypto !== 'undefined' &&
                typeof crypto.randomUUID === 'function'
            ) {
                generated = crypto.randomUUID();
            } else {
                generated = `guest-${Date.now()}-${Math.random().toString(16).slice(2)}`;
            }
        } catch {
            generated = `guest-${Date.now()}-${Math.random().toString(16).slice(2)}`;
        }

        localStorage.setItem(storageKey, generated);
        return generated;
    }

    function requestedStatusStorageKey(): string {
        return `guest_status_requested_masters_${getGuestDeviceId()}`;
    }

    function getRequestedStatusMasterIds(): number[] {
        try {
            const raw = localStorage.getItem(requestedStatusStorageKey());
            if (!raw) return [];
            const parsed = JSON.parse(raw) as unknown;
            if (!Array.isArray(parsed)) return [];
            return parsed
                .map((value) => Number(value))
                .filter((value) => Number.isFinite(value) && value > 0);
        } catch {
            return [];
        }
    }

    function setRequestedStatusMasterIds(ids: number[]): void {
        localStorage.setItem(requestedStatusStorageKey(), JSON.stringify(ids));
    }

    function rememberRequestedStatus(masterId: number): void {
        const ids = getRequestedStatusMasterIds();
        if (ids.includes(masterId)) return;
        ids.push(masterId);
        setRequestedStatusMasterIds(ids);
    }

    function forgetRequestedStatus(masterId: number): void {
        const next = getRequestedStatusMasterIds().filter(
            (id) => id !== masterId,
        );
        setRequestedStatusMasterIds(next);
    }

    function cooldownStorageKey(masterId: number): string {
        return `master_status_request_cooldown_${masterId}`;
    }

    function loadCooldown(masterId: number): void {
        const raw = localStorage.getItem(cooldownStorageKey(masterId));
        if (!raw) {
            statusCooldownUntil.value = null;
            return;
        }
        const parsed = new Date(raw);
        statusCooldownUntil.value = Number.isNaN(parsed.getTime())
            ? null
            : parsed;
    }

    function saveCooldown(
        masterId: number,
        rawDate: string | null | undefined,
    ): void {
        if (!rawDate) return;
        localStorage.setItem(cooldownStorageKey(masterId), rawDate);
        loadCooldown(masterId);
    }

    async function requestMasterStatus(): Promise<void> {
        const masterId = getMasterId();
        if (
            !masterId ||
            !canRequestMasterStatus.value ||
            isSendingStatusRequest.value
        )
            return;

        isSendingStatusRequest.value = true;
        statusRequestMessage.value = '';

        try {
            const response = await apiInstance.post('/request-status', {
                master_id: masterId,
                guest_device_id: getGuestDeviceId(),
                guest_platform: 'web',
            });
            rememberRequestedStatus(masterId);
            saveCooldown(
                masterId,
                response.data?.cooldown_expires_at as string | undefined,
            );
            statusRequestMessage.value = t('statusSent');
        } catch (error: unknown) {
            const responseData =
                (error as { response?: { data?: Record<string, unknown> } })
                    ?.response?.data ?? {};
            const cooldown = responseData.cooldown_expires_at;
            if (typeof cooldown === 'string') {
                saveCooldown(masterId, cooldown);
            }
            const message = responseData.message;
            statusRequestMessage.value =
                typeof message === 'string' ? message : t('statusError');
            if (typeof cooldown === 'string' || typeof message === 'string') {
                rememberRequestedStatus(masterId);
            }
        } finally {
            isSendingStatusRequest.value = false;
        }
    }

    return {
        isSendingStatusRequest,
        statusRequestMessage,
        statusCooldownUntil,
        canRequestMasterStatus,
        loadCooldown,
        saveCooldown,
        requestMasterStatus,
        getGuestDeviceId,
        getRequestedStatusMasterIds,
        rememberRequestedStatus,
        forgetRequestedStatus,
    };
}
