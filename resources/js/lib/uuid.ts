/**
 * `crypto.randomUUID()` only works in secure contexts (https, or exactly
 * `localhost`/`127.0.0.1`) — it throws `crypto.randomUUID is not a
 * function` when the app is served over plain http on any other host
 * (LAN IP, a custom dev domain, etc). `crypto.getRandomValues()` has no
 * such restriction, so build an RFC 4122 v4 UUID from it instead.
 */
export function uuid(): string {
    if (
        typeof crypto !== 'undefined' &&
        typeof crypto.randomUUID === 'function'
    ) {
        try {
            return crypto.randomUUID();
        } catch {
            // Fall through to the getRandomValues-based implementation below.
        }
    }

    const bytes = new Uint8Array(16);
    crypto.getRandomValues(bytes);
    bytes[6] = (bytes[6] & 0x0f) | 0x40; // version 4
    bytes[8] = (bytes[8] & 0x3f) | 0x80; // variant 10

    const hex = Array.from(bytes, (b) => b.toString(16).padStart(2, '0')).join(
        '',
    );

    return [
        hex.slice(0, 8),
        hex.slice(8, 12),
        hex.slice(12, 16),
        hex.slice(16, 20),
        hex.slice(20, 32),
    ].join('-');
}
