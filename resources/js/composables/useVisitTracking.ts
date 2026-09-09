import { router } from '@inertiajs/vue3';
import axios from 'axios';

/**
 * Visitor analytics for the public site (see Admin/Visits). Deliberately
 * skips /admin and /master — those are authenticated staff areas, not the
 * traffic this dashboard is meant to analyze.
 */
const STORAGE_KEY = 'cb_visit_session_token';
const EXCLUDED_PREFIXES = [
    '/admin',
    '/admin-auth',
    '/master',
    '/master-login',
    '/master-logout',
];

let initialized = false;
let previousPath: string | null = null;
let lastPageview: { path: string; at: number } | null = null;

// Guards against a page load reporting itself twice — e.g. Inertia firing a
// second 'navigate' for a same-URL history sync shortly after the initial
// load. A genuine repeat visit to the same path is never this fast.
const PAGEVIEW_DEDUPE_WINDOW_MS = 1500;

function isTrackedPath(path: string): boolean {
    return !EXCLUDED_PREFIXES.some(
        (prefix) => path === prefix || path.startsWith(`${prefix}/`),
    );
}

function generateUuid(): string {
    if (typeof crypto !== 'undefined' && 'randomUUID' in crypto) {
        return crypto.randomUUID();
    }

    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (c) => {
        const r = (Math.random() * 16) | 0;
        const v = c === 'x' ? r : (r & 0x3) | 0x8;
        return v.toString(16);
    });
}

function getSessionToken(): string | null {
    try {
        let token = sessionStorage.getItem(STORAGE_KEY);
        if (!token) {
            token = generateUuid();
            sessionStorage.setItem(STORAGE_KEY, token);
        }
        return token;
    } catch {
        return null;
    }
}

interface ElementDescriptor {
    tag: string;
    text?: string;
    id?: string;
    class?: string;
    href?: string;
}

function describeElement(
    target: EventTarget | null,
): ElementDescriptor | undefined {
    if (!(target instanceof Element)) {
        return undefined;
    }

    const interactive = target.closest('a, button, [role="button"]') ?? target;

    return {
        tag: interactive.tagName.toLowerCase(),
        text: (interactive.textContent ?? '').trim().slice(0, 100) || undefined,
        id: interactive.id || undefined,
        class:
            typeof interactive.className === 'string'
                ? interactive.className
                : undefined,
        href:
            interactive instanceof HTMLAnchorElement
                ? interactive.href
                : undefined,
    };
}

function send(payload: Record<string, unknown>) {
    // A click that triggers real navigation unloads the page before an
    // axios/fetch promise resolves, silently cancelling it — sendBeacon is
    // built to survive that. It can't carry a CSRF header, so /track/event
    // is exempted from CSRF verification (see bootstrap/app.php).
    if (typeof navigator !== 'undefined' && 'sendBeacon' in navigator) {
        const blob = new Blob([JSON.stringify(payload)], {
            type: 'application/json',
        });
        if (navigator.sendBeacon('/track/event', blob)) {
            return;
        }
    }

    axios.post('/track/event', payload).catch(() => {
        // Best-effort analytics — never surface a tracking failure to the visitor.
    });
}

function trackPageview() {
    const path = window.location.pathname;
    if (!isTrackedPath(path)) {
        return;
    }

    const now = Date.now();
    if (
        lastPageview &&
        lastPageview.path === path &&
        now - lastPageview.at < PAGEVIEW_DEDUPE_WINDOW_MS
    ) {
        return;
    }

    const token = getSessionToken();
    if (!token) {
        return;
    }

    const referrer = previousPath ?? document.referrer ?? null;
    previousPath = path;
    lastPageview = { path, at: now };

    send({
        session_token: token,
        type: 'pageview',
        path,
        url: window.location.href,
        referrer,
    });
}

function trackClick(event: MouseEvent) {
    const path = window.location.pathname;
    if (!isTrackedPath(path)) {
        return;
    }

    const token = getSessionToken();
    if (!token) {
        return;
    }

    send({
        session_token: token,
        type: 'click',
        path,
        url: window.location.href,
        element: describeElement(event.target),
    });
}

export function useVisitTracking(): void {
    if (initialized) {
        return;
    }
    initialized = true;

    trackPageview();
    router.on('navigate', () => trackPageview());
    document.addEventListener('click', trackClick, { capture: true });
}
