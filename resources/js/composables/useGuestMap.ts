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

// viewBox "0 0 744.09 1052.4" — same SVG geometry as the Flutter mobile pin
// Circle center (373, 370), radius 250 — evenodd rule creates a transparent hole for the photo
const PIN_SVG_PATH =
    'M373.3 58.058 C189.87 58.316 41.25 207.1 41.25 390.59 ' +
    'c0 121.52 65.173 227.8 162.48 285.82 ' +
    '94.942 70.715 159.22 180.26 169.37 305.13 ' +
    'l0.19675 0.33729 0.0843-0.14058 0.0562 0.14058 ' +
    '0.19675-0.33729 ' +
    'c10.16-124.88 74.43-234.42 169.38-305.13 ' +
    '97.312-58.012 162.48-164.3 162.48-285.82 ' +
    '0-183.49-148.62-332.27-332.05-332.53 ' +
    '-0.0469-0.000063-0.0937 0.000045-0.14053 0z ' +
    'M373 120 a250 250 0 1 1 0 500 a250 250 0 1 1 0 -500 z';

// SVG geometry fractions (matching Flutter constants)
const PIN_CX_F = 373.0 / 744.09; // 0.5013
const PIN_CY_F = 370.0 / 1052.4; // 0.3516
const PIN_R_F = 250.0 / 744.09; // 0.3361

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
    // Master photos are unique per marker; only fallback icons share the cache.
    const sharedKey = showPhoto
        ? null
        : `pin-${state.available ? 'a' : 'u'}-${state.selected ? 's' : 'n'}-fallback`;

    if (sharedKey) {
        const cached = sharedIconCache.get(sharedKey);
        if (cached) return cached;
    }

    const colorClass = state.selected
        ? 'pin-active'
        : state.available
          ? 'pin-available'
          : 'pin-unavailable';

    const loadingMode = state.selected ? 'eager' : 'lazy';
    const fetchPriority = state.selected ? 'high' : 'low';
    const decodingMode = state.selected ? 'sync' : 'async';
    const avatarInner = showPhoto
        ? `<img src="${photo}" alt="${escapeHtml(name)}" loading="${loadingMode}" fetchpriority="${fetchPriority}" decoding="${decodingMode}" class="marker-avatar-img" />`
        : SVG_FALLBACK;

    // Pin dimensions matching Flutter: width × (1052.4/744.09) aspect ratio
    const pinW = state.selected ? 52 : 40;
    const pinH = Math.round(pinW * (1052.4 / 744.09)); // 74 or 57

    // Photo circle position derived from SVG geometry fractions
    const cx = PIN_CX_F * pinW;
    const cy = PIN_CY_F * pinH;
    const r = PIN_R_F * pinW;
    const bgL = Math.round((cx - r) * 10) / 10;
    const bgT = Math.round((cy - r) * 10) / 10;
    const bgD = Math.round(r * 2 * 10) / 10;

    const inner = `
        <div class="marker-bg" style="left:${bgL}px;top:${bgT}px;width:${bgD}px;height:${bgD}px">${avatarInner}</div>
        <svg class="marker-pin-svg" viewBox="0 0 744.09 1052.4" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <path class="marker-pin-path" fill-rule="evenodd" d="${PIN_SVG_PATH}"/>
        </svg>`;

    const icon = L.divIcon({
        className: 'master-marker-wrapper',
        html: `<div class="master-marker ${colorClass}">${inner}</div>`,
        iconSize: [pinW, pinH],
        // Anchor at the pin tip — bottom center
        iconAnchor: [pinW / 2, pinH],
    });

    if (sharedKey) sharedIconCache.set(sharedKey, icon);
    return icon;
}

function buildClusterIcon(count: number, hasAvailable: boolean): L.DivIcon {
    // Bucket sizes mirror Flutter ClusterCircle
    const sizeBucket =
        count < 10
            ? 'sm'
            : count < 100
              ? 'md'
              : count < 1000
                ? 'lg'
                : count < 10000
                  ? 'xl'
                  : 'xxl';
    const px =
        count < 10
            ? 52
            : count < 100
              ? 60
              : count < 1000
                ? 72
                : count < 10000
                  ? 88
                  : 104;
    return L.divIcon({
        className: `cluster-marker cluster-${sizeBucket} ${hasAvailable ? 'cluster-has-available' : 'cluster-all-unavailable'}`,
        html: `<div class="cluster-inner">${count}</div>`,
        iconSize: [px, px],
        iconAnchor: [px / 2, px / 2],
    });
}

export interface GuestMapHandle {
    init: (
        el: HTMLElement,
        initial: { center: [number, number]; zoom: number },
    ) => void;
    destroy: () => void;
    syncMasters: (masters: Master[]) => void;
    setSelected: (masterId: number | null) => void;
    applyAvailability: (masterId: number, available: boolean) => void;
    setUserPosition: (latlng: [number, number]) => void;
    flyTo: (latlng: [number, number], zoom?: number) => void;
    getView: () => MapMovedContext | null;
    /** Projects a lat/lng to a pixel point relative to the map container's top-left corner. */
    latLngToContainerPoint: (
        lat: number,
        lng: number,
    ) => { x: number; y: number } | null;
}

export function useGuestMap(options: UseGuestMapOptions): GuestMapHandle {
    const moveDebounceMs = options.moveDebounceMs ?? 300;
    const tileUrl =
        options.tileUrl ??
        `https://api.mapbox.com/styles/v1/rotting/claqrpplh000g14mmffvd0767/tiles/256/{z}/{x}/{y}@2x?access_token=${import.meta.env.VITE_MAPBOX_TOKEN}`;
    const tileAttribution =
        options.tileAttribution ??
        '&copy; <a href="https://www.mapbox.com/">Mapbox</a> &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors';

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
        const photo = options.photoUrl(
            master.main_thumb_url ?? master.main_photo,
        );
        marker.setIcon(buildMasterIcon(state, photo, master.name));
        return true;
    }

    // Pulls a marker out of the cluster group and onto the map directly, so it can never be absorbed into a cluster bubble.
    function excludeFromCluster(marker: L.Marker): void {
        if (!cluster || !map) return;
        if (cluster.hasLayer(marker)) cluster.removeLayer(marker);
        if (!map.hasLayer(marker)) marker.addTo(map);
    }

    function includeInCluster(marker: L.Marker): void {
        if (!cluster || !map) return;
        if (map.hasLayer(marker)) map.removeLayer(marker);
        if (!cluster.hasLayer(marker)) cluster.addLayer(marker);
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
            const photo = options.photoUrl(
                master.main_thumb_url ?? master.main_photo,
            );
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
            if (master.id === selectedId) {
                marker.setZIndexOffset(1200);
                marker.addTo(map);
            } else {
                toAdd.push(marker);
            }
        }

        markersById.forEach((marker, id) => {
            if (incoming.has(id)) return;
            if (map!.hasLayer(marker)) {
                map!.removeLayer(marker);
            } else {
                toRemove.push(marker);
            }
            markersById.delete(id);
            masterById.delete(id);
            iconKeyById.delete(id);
            if (selectedId === id) selectedId = null;
        });

        // Add/remove synchronously — leaflet.markercluster chunks addLayers internally and can't cancel a pending add.
        if (toRemove.length) cluster.removeLayers(toRemove);
        if (toAdd.length) cluster.addLayers(toAdd);
        if (shouldRefreshClusters || toRemove.length || toAdd.length) {
            cluster.refreshClusters?.();
        }
    }

    function setSelected(masterId: number | null): void {
        if (!map) return;
        const previous = selectedId;
        selectedId = masterId;

        if (previous !== null && previous !== masterId) {
            const marker = markersById.get(previous);
            const master = masterById.get(previous);
            if (marker && master) {
                applyIcon(marker, master, false);
                includeInCluster(marker);
            }
        }

        if (masterId === null) return;
        const marker = markersById.get(masterId);
        const master = masterById.get(masterId);
        if (marker && master) {
            applyIcon(marker, master, true);
            excludeFromCluster(marker);
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
        if (changed && cluster.hasLayer(marker))
            cluster.refreshClusters?.(marker);
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

    function latLngToContainerPoint(
        lat: number,
        lng: number,
    ): { x: number; y: number } | null {
        if (!map) return null;
        const point = map.latLngToContainerPoint([lat, lng]);
        return { x: point.x, y: point.y };
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
        latLngToContainerPoint,
    };
}
