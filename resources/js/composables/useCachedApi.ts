import axios, { type AxiosInstance, type AxiosRequestConfig } from 'axios';

type CacheEntry<T> = {
    expiresAt: number;
    value: T;
};

type PendingEntry<T> = {
    promise: Promise<T>;
    controller: AbortController;
};

export interface CachedApiOptions {
    baseURL: string;
    defaultTtlMs?: number;
    maxEntries?: number;
}

export interface CachedRequestOptions extends AxiosRequestConfig {
    /** Cache TTL in milliseconds. Defaults to `defaultTtlMs`. */
    ttlMs?: number;
    /**
     * Logical group identifier for in-flight requests. When a new request is
     * issued for the same group, any previous in-flight request from that
     * group is aborted (via AbortController). Defaults to the cache key.
     */
    group?: string;
    /** Force a network round-trip even when a fresh cache entry exists. */
    forceRefresh?: boolean;
}

export interface CachedApi {
    instance: AxiosInstance;
    getCached: <T>(
        key: string,
        url: string,
        options?: CachedRequestOptions,
    ) => Promise<T>;
    invalidate: (predicate?: (key: string) => boolean) => void;
    abortAll: () => void;
    abortGroup: (group: string) => void;
    isCancel: (error: unknown) => boolean;
}

/**
 * Lightweight in-memory cache + dedupe + abort wrapper around axios.
 *
 * - TTL-based per-key cache (LRU-trimmed by insertion order).
 * - Concurrent calls with the same key share a single Promise (dedupe).
 * - Calls with the same `group` cancel previous in-flight requests for that
 *   group, so e.g. fast map panning never piles up stale `/masters` requests.
 */
export function createCachedApi(options: CachedApiOptions): CachedApi {
    const instance = axios.create({
        baseURL: options.baseURL,
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
    });

    const defaultTtlMs = options.defaultTtlMs ?? 60_000;
    const maxEntries = options.maxEntries ?? 200;

    const cache = new Map<string, CacheEntry<unknown>>();
    const pending = new Map<string, PendingEntry<unknown>>();
    const groupControllers = new Map<string, AbortController>();

    function trimCache(): void {
        if (cache.size <= maxEntries) return;
        const overflow = cache.size - maxEntries;
        let dropped = 0;
        for (const key of cache.keys()) {
            if (dropped >= overflow) break;
            cache.delete(key);
            dropped += 1;
        }
    }

    function readFresh<T>(key: string): T | null {
        const entry = cache.get(key) as CacheEntry<T> | undefined;
        if (!entry) return null;
        if (entry.expiresAt < Date.now()) {
            cache.delete(key);
            return null;
        }
        return entry.value;
    }

    async function getCached<T>(
        key: string,
        url: string,
        opts: CachedRequestOptions = {},
    ): Promise<T> {
        const { ttlMs, group, forceRefresh, ...config } = opts;

        if (!forceRefresh) {
            const fresh = readFresh<T>(key);
            if (fresh !== null) return fresh;
        }

        const dedup = pending.get(key) as PendingEntry<T> | undefined;
        if (dedup) return dedup.promise;

        const groupKey = group ?? key;
        const previous = groupControllers.get(groupKey);
        if (previous) previous.abort();

        const controller = new AbortController();
        groupControllers.set(groupKey, controller);

        const promise: Promise<T> = instance
            .get<T>(url, { ...config, signal: controller.signal })
            .then((response) => {
                const value = response.data;
                cache.set(key, {
                    value,
                    expiresAt: Date.now() + (ttlMs ?? defaultTtlMs),
                });
                trimCache();
                return value;
            })
            .finally(() => {
                pending.delete(key);
                if (groupControllers.get(groupKey) === controller) {
                    groupControllers.delete(groupKey);
                }
            });

        pending.set(key, { promise, controller });
        return promise;
    }

    function invalidate(predicate?: (key: string) => boolean): void {
        if (!predicate) {
            cache.clear();
            return;
        }
        for (const key of [...cache.keys()]) {
            if (predicate(key)) cache.delete(key);
        }
    }

    function abortGroup(group: string): void {
        const controller = groupControllers.get(group);
        if (!controller) return;
        controller.abort();
        groupControllers.delete(group);
    }

    function abortAll(): void {
        groupControllers.forEach((controller) => controller.abort());
        groupControllers.clear();
        pending.clear();
    }

    return {
        instance,
        getCached,
        invalidate,
        abortAll,
        abortGroup,
        isCancel: axios.isCancel,
    };
}
