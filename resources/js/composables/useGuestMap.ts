import L from 'leaflet';
import 'leaflet.markercluster';
import 'leaflet.markercluster/dist/MarkerCluster.css';
import 'leaflet/dist/leaflet.css';
import { onBeforeUnmount } from 'vue';

export type Master = {
    id: number;
    name: string;
    latitude: number;
    longitude: number;
    available?: boolean;
    main_thumb_url?: string | null;
    main_photo?: string | null;
    [key: string]: unknown;
};

export interface MapMovedContext {
    center: L.LatLng;
    bounds: L.LatLngBounds;
    zoom: number;
}

export interface UseGuestMapOptions {
    onMapMoved?: (ctx: MapMovedContext) => void;
    onMapClick?: () => void;
    onMarkerClick?: (master: Master) => void;
    photoUrl: (path?: string | null) => string | null;
    /** Debounce in ms before `onMapMoved` fires after pan/zoom. */
    moveDebounceMs?: number;
    tileUrl?: string;
    tileAttribution?: string;
}

interface IconState {
    available: boolean;
    selected: boolean;
    photoKey: string;
}

const SVG_FALLBACK = `<div class="marker-avatar-fallback" aria-hidden="true">
    <svg class="marker-avatar-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M14.3 3.8a1 1 0 0 1 1.4 0l4.5 4.5a1 1 0 0 1 0 1.4l-2.1 2.1-5.9-5.9 2.1-2.1Z" fill="currentColor"/>
        <path d="m11.4 6.6 5.9 5.9-5.5 5.5a3 3 0 0 1-1.2.7l-3.3 1.1a1 1 0 0 1-1.3-1.3l1.1-3.3a3 3 0 0 1 .7-1.2l5.6-5.4Z" fill="currentColor"/>
        <circle cx="7" cy="7" r="3" stroke="currentColor" stroke-width="1.5"/>
    </svg>
</div>`;

function escapeHtml(value: string): string {
    return value.replace(/[&<>"']/g, (ch) => {
        switch (ch) {
            case '&':
                return '&amp;';
            case '<':
                return '&lt;';
            case '>':
                return '&gt;';
            case '"':
                return '&quot;';
            case "'":
                return '&#39;';
            default:
                return ch;
        }
    });
}

function iconStateKey(state: IconState): string {
    return `${state.available ? 1 : 0}|${state.selected ? 1 : 0}|${state.photoKey}`;
}

/** Icons are immutable; reusing fallback icons across markers cuts DOM allocation. */
const sharedIconCache = new Map<string, L.DivIcon>();

function buildMasterIcon(
    state: IconState,
    photo: string | null,
    name: string,
): L.DivIcon {
    const showPhoto = state.photoKey !== '' && !!photo;
    // Master photos are unique per marker, so they bypass the shared cache.
    const sharedKey = showPhoto
        ? null
        : `${state.available ? 'a' : 'u'}-${state.selected ? 's' : 'n'}-fallback`;

    if (sharedKey) {
        const cached = sharedIconCache.get(sharedKey);
        if (cached) return cached;
    }

    const availabilityClass = state.available
        ? 'available-marker'
        : 'unavailable-marker';
    const activeClass = state.selected ? 'active-marker' : '';
    const inner = showPhoto
        ? `<img src="${photo}" alt="${escapeHtml(name)}" loading="lazy" decoding="async" class="marker-avatar-img" />`
        : SVG_FALLBACK;

    const icon = L.divIcon({
        className: 'master-marker-wrapper',
        html: `<div class="master-marker ${availabilityClass} ${activeClass}">${inner}</div>`,
        iconSize: [44, 44],
        iconAnchor: [22, 22],
    });

    if (sharedKey) sharedIconCache.set(sharedKey, icon);
    return icon;
}

function buildClusterIcon(count: number, hasAvailable: boolean): L.DivIcon {
    const sizeBucket = count < 10 ? 'sm' : count < 100 ? 'md' : 'lg';
    return L.divIcon({
        className: `cluster-marker cluster-${sizeBucket} ${hasAvailable ? 'cluster-has-available' : ''}`,
        html: `<div class="cluster-inner">${count}</div>`,
        iconSize: [42, 42],
        iconAnchor: [21, 21],
    });
}

export interface GuestMapHandle {
    init: (
        el: HTMLElement,
        initial: { center: [number, number]; zoom: number },
    ) => void;
    destroy: () => void;
    syncMasters: (masters: Master[]) => void;
    setSelected: (
        masterId: number | null,
        options?: { reveal?: boolean; offsetY?: number },
    ) => void;
    applyAvailability: (masterId: number, available: boolean) => void;
    setUserPosition: (latlng: [number, number]) => void;
    flyTo: (latlng: [number, number], zoom?: number) => void;
    getView: () => MapMovedContext | null;
}

export function useGuestMap(options: UseGuestMapOptions): GuestMapHandle {
    const moveDebounceMs = options.moveDebounceMs ?? 300;
    const tileUrl =
        options.tileUrl ??
        'https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png';
    const tileAttribution =
        options.tileAttribution ??
        '&copy; OpenStreetMap contributors &copy; CARTO';

    let map: L.Map | null = null;
    let cluster: L.MarkerClusterGroup | null = null;
    let userMarker: L.CircleMarker | null = null;
    let moveTimer: number | null = null;
    let selectedId: number | null = null;

    const markersById = new Map<number, L.Marker>();
    const masterById = new Map<number, Master>();
    const iconKeyById = new Map<number, string>();

    function deriveState(master: Master, selected: boolean): IconState {
        const photo =
            options.photoUrl(master.main_thumb_url ?? master.main_photo) ?? '';
        return {
            available: !!master.available,
            selected,
            photoKey: photo,
        };
    }

    function applyIcon(
        marker: L.Marker,
        master: Master,
        selected: boolean,
    ): boolean {
        marker.setZIndexOffset(selected ? 1200 : 0);
        const state = deriveState(master, selected);
        const nextKey = iconStateKey(state);
        if (iconKeyById.get(master.id) === nextKey) return false;
        iconKeyById.set(master.id, nextKey);
        const photo = options.photoUrl(master.main_thumb_url ?? master.main_photo);
        marker.setIcon(buildMasterIcon(state, photo, master.name));
        return true;
    }

    function scheduleMoveCallback(): void {
        if (!map) return;
        if (moveTimer !== null) window.clearTimeout(moveTimer);
        moveTimer = window.setTimeout(() => {
            moveTimer = null;
            if (!map) return;
            options.onMapMoved?.({
                center: map.getCenter(),
                bounds: map.getBounds(),
                zoom: map.getZoom(),
            });
        }, moveDebounceMs);
    }

    function handleMoveEnd(): void {
        scheduleMoveCallback();
    }

    function handleMapClick(): void {
        options.onMapClick?.();
    }

    function init(
        el: HTMLElement,
        initial: { center: [number, number]; zoom: number },
    ): void {
        if (map) return;

        map = L.map(el, {
            zoomControl: false,
            minZoom: 2,
            maxZoom: 18,
            zoomAnimation: true,
            fadeAnimation: true,
            markerZoomAnimation: true,
            zoomSnap: 0.25,
            zoomDelta: 0.5,
            wheelPxPerZoomLevel: 90,
            preferCanvas: true,
        }).setView(initial.center, initial.zoom);

        L.control.zoom({ position: 'bottomright' }).addTo(map);

        L.tileLayer(tileUrl, {
            maxZoom: 18,
            crossOrigin: true,
            keepBuffer: 4,
            updateWhenIdle: true,
            updateWhenZooming: false,
            attribution: tileAttribution,
        }).addTo(map);

        cluster = L.markerClusterGroup({
            chunkedLoading: true,
            chunkInterval: 80,
            chunkDelay: 30,
            removeOutsideVisibleBounds: true,
            disableClusteringAtZoom: 17,
            spiderfyOnMaxZoom: true,
            zoomToBoundsOnClick: true,
            showCoverageOnHover: false,
            animate: true,
            animateAddingMarkers: false,
            maxClusterRadius: (zoom: number) => (zoom >= 14 ? 40 : 70),
            iconCreateFunction: (group) => {
                const hasAvailable = group
                    .getAllChildMarkers()
                    .some((marker) => {
                        const id = (
                            marker as L.Marker & { __masterId?: number }
                        ).__masterId;
                        if (typeof id !== 'number') return false;
                        return !!masterById.get(id)?.available;
                    });
                return buildClusterIcon(group.getChildCount(), hasAvailable);
            },
        });
        cluster.addTo(map);

        map.on('moveend', handleMoveEnd);
        map.on('zoomend', handleMoveEnd);
        map.on('click', handleMapClick);
    }

    function syncMasters(masters: Master[]): void {
        if (!map || !cluster) return;

        const incoming = new Set<number>();
        const toAdd: L.Marker[] = [];
        const toRemove: L.Marker[] = [];
        let shouldRefreshClusters = false;

        for (const master of masters) {
            if (
                !Number.isFinite(master.latitude) ||
                !Number.isFinite(master.longitude)
            )
                continue;
            incoming.add(master.id);

            const existing = markersById.get(master.id);
            const previous = masterById.get(master.id);
            masterById.set(master.id, master);

            if (existing) {
                if (
                    previous &&
                    (previous.latitude !== master.latitude ||
                        previous.longitude !== master.longitude)
                ) {
                    existing.setLatLng([master.latitude, master.longitude]);
                }
                if (applyIcon(existing, master, master.id === selectedId)) {
                    shouldRefreshClusters = true;
                }
                continue;
            }

            const state = deriveState(master, master.id === selectedId);
            const photo = options.photoUrl(master.main_photo);
            const marker = L.marker([master.latitude, master.longitude], {
                icon: buildMasterIcon(state, photo, master.name),
                keyboard: false,
                riseOnHover: true,
            });
            marker.on('click', (event) => {
                if ('originalEvent' in event) {
                    L.DomEvent.stopPropagation(event.originalEvent as Event);
                }
                options.onMarkerClick?.(master);
            });
            marker.bindTooltip(master.name, {
                direction: 'top',
                opacity: 0.9,
                offset: L.point(0, -10),
            });
            (marker as L.Marker & { __masterId?: number }).__masterId =
                master.id;
            iconKeyById.set(master.id, iconStateKey(state));
            markersById.set(master.id, marker);
            toAdd.push(marker);
        }

        markersById.forEach((marker, id) => {
            if (incoming.has(id)) return;
            toRemove.push(marker);
            markersById.delete(id);
            masterById.delete(id);
            iconKeyById.delete(id);
            if (selectedId === id) selectedId = null;
        });

        if (toRemove.length) cluster.removeLayers(toRemove);
        if (toAdd.length) cluster.addLayers(toAdd);
        if (shouldRefreshClusters || toAdd.length || toRemove.length) {
            cluster.refreshClusters?.();
        }
    }

    function setSelected(
        masterId: number | null,
        options?: { reveal?: boolean; offsetY?: number },
    ): void {
        if (!map) return;
        const previous = selectedId;
        selectedId = masterId;

        if (previous !== null && previous !== masterId) {
            const marker = markersById.get(previous);
            const master = masterById.get(previous);
            if (marker && master) applyIcon(marker, master, false);
        }

        if (masterId === null) return;
        const marker = markersById.get(masterId);
        const master = masterById.get(masterId);
        if (marker && master) {
            applyIcon(marker, master, true);

            if (options?.reveal && cluster) {
                cluster.zoomToShowLayer(marker, () => {
                    if (!map) return;

                    const targetZoom = Math.max(map.getZoom(), 15);
                    let targetLatLng = L.latLng(master.latitude, master.longitude);

                    if (options.offsetY) {
                        const projected = map.project(targetLatLng, targetZoom);
                        projected.y += options.offsetY;
                        targetLatLng = map.unproject(projected, targetZoom);
                    }

                    map.flyTo(targetLatLng, targetZoom, {
                        duration: 0.45,
                        easeLinearity: 0.25,
                    });
                });
            }
        }
    }

    function applyAvailability(masterId: number, available: boolean): void {
        if (!map || !cluster) return;
        const master = masterById.get(masterId);
        if (!master) return;
        master.available = available;
        const marker = markersById.get(masterId);
        if (!marker) return;
        const changed = applyIcon(marker, master, masterId === selectedId);
        if (changed) cluster.refreshClusters?.(marker);
    }

    function setUserPosition(latlng: [number, number]): void {
        if (!map) return;
        if (userMarker) {
            userMarker.setLatLng(latlng);
            return;
        }
        userMarker = L.circleMarker(latlng, {
            radius: 8,
            color: '#ffffff',
            weight: 2,
            fillColor: '#2563eb',
            fillOpacity: 1,
        }).addTo(map);
    }

    function flyTo(latlng: [number, number], zoom?: number): void {
        if (!map) return;
        const target = zoom ?? Math.max(map.getZoom(), 13);
        map.flyTo(latlng, target, { duration: 0.45, easeLinearity: 0.25 });
    }

    function getView(): MapMovedContext | null {
        if (!map) return null;
        return {
            center: map.getCenter(),
            bounds: map.getBounds(),
            zoom: map.getZoom(),
        };
    }

    function destroy(): void {
        if (moveTimer !== null) {
            window.clearTimeout(moveTimer);
            moveTimer = null;
        }
        if (cluster) {
            cluster.clearLayers();
            cluster.remove();
            cluster = null;
        }
        if (userMarker) {
            userMarker.remove();
            userMarker = null;
        }
        if (map) {
            map.off();
            map.remove();
            map = null;
        }
        markersById.clear();
        masterById.clear();
        iconKeyById.clear();
        selectedId = null;
    }

    onBeforeUnmount(() => destroy());

    return {
        init,
        destroy,
        syncMasters,
        setSelected,
        applyAvailability,
        setUserPosition,
        flyTo,
        getView,
    };
}
