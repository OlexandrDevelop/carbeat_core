import { onBeforeUnmount, ref } from 'vue';

export interface StatusBeacon {
    masterId: number;
    masterName: string;
}

export function useStatusBeacon() {
    const statusBeacon = ref<StatusBeacon | null>(null);
    let beaconTimer: number | null = null;

    function showStatusBeacon(masterId: number, masterName: string): void {
        if (beaconTimer !== null) {
            window.clearTimeout(beaconTimer);
            beaconTimer = null;
        }
        statusBeacon.value = { masterId, masterName };
        beaconTimer = window.setTimeout(() => {
            statusBeacon.value = null;
            beaconTimer = null;
        }, 5000);
    }

    function closeStatusBeacon(): void {
        if (beaconTimer !== null) {
            window.clearTimeout(beaconTimer);
            beaconTimer = null;
        }
        statusBeacon.value = null;
    }

    onBeforeUnmount(closeStatusBeacon);

    return { statusBeacon, showStatusBeacon, closeStatusBeacon };
}
