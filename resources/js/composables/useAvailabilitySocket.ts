import { resolveSocketPath, resolveSocketUrl } from '@/lib/socket-config';
import { io } from 'socket.io-client';
import { onBeforeUnmount } from 'vue';

interface UseAvailabilitySocketOptions {
    onAvailabilityUpdate: (payload: unknown) => void;
}

export function useAvailabilitySocket({
    onAvailabilityUpdate,
}: UseAvailabilitySocketOptions) {
    let socket: ReturnType<typeof io> | null = null;

    function connect(): void {
        const socketUrl = resolveSocketUrl();
        const socketPath = resolveSocketPath();
        socket = io(socketUrl, {
            transports: ['websocket', 'polling'],
            path: socketPath,
        });
        socket.on('availability:update', onAvailabilityUpdate);
    }

    function disconnect(): void {
        if (socket) {
            socket.off('availability:update', onAvailabilityUpdate);
            socket.disconnect();
            socket = null;
        }
    }

    onBeforeUnmount(disconnect);

    return { connect, disconnect };
}
