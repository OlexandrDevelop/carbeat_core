function getEnvSocketUrl(): string | null {
    const envUrl = import.meta.env.VITE_SOCKET_IO_URL;

    if (typeof envUrl !== 'string') {
        return null;
    }

    const trimmed = envUrl.trim();

    return trimmed !== '' ? trimmed : null;
}

export function resolveSocketUrl(): string {
    const envUrl = getEnvSocketUrl();
    if (envUrl) {
        return envUrl;
    }

    if (typeof window === 'undefined') {
        return '/';
    }

    const { origin, hostname } = window.location;
    const normalizedHost = hostname.replace(/^www\./i, '');

    if (normalizedHost === 'carbeat.online' || normalizedHost === 'flox.city') {
        return `https://socket.${normalizedHost}`;
    }

    return origin;
}

export function resolveSocketPath(): string {
    const envPath = import.meta.env.VITE_SOCKET_IO_PATH;

    if (typeof envPath !== 'string') {
        return '/socket.io/';
    }

    const trimmed = envPath.trim();

    return trimmed !== '' ? trimmed : '/socket.io/';
}
