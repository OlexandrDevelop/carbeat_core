import { nextTick, onBeforeUnmount, onMounted, ref, type Ref } from 'vue';

interface UseHorizontalVirtualListOptions {
    containerRef: Ref<HTMLElement | null>;
    itemSelector: string;
    itemCount: () => number;
    estimatedItemWidth?: number;
    overscan?: number;
}

export function useHorizontalVirtualList({
    containerRef,
    itemSelector,
    itemCount,
    estimatedItemWidth = 96,
    overscan = 6,
}: UseHorizontalVirtualListOptions) {
    const startIndex = ref(0);
    const endIndex = ref(0);
    const leftSpacerPx = ref(0);
    const rightSpacerPx = ref(0);

    let itemWidth = estimatedItemWidth;
    let rafId: number | null = null;
    let resizeObserver: ResizeObserver | null = null;

    const measureItemWidth = () => {
        const container = containerRef.value;
        if (!container) return;

        const item = container.querySelector(
            itemSelector,
        ) as HTMLElement | null;
        if (!item) return;

        const gap =
            parseFloat(getComputedStyle(container).columnGap || '0') || 0;
        itemWidth = item.getBoundingClientRect().width + gap;
    };

    const recompute = () => {
        const container = containerRef.value;
        const count = itemCount();
        if (!container || count === 0 || itemWidth <= 0) {
            startIndex.value = 0;
            endIndex.value = count;
            leftSpacerPx.value = 0;
            rightSpacerPx.value = 0;
            return;
        }

        const scrollLeft = container.scrollLeft;
        const containerWidth = container.clientWidth;
        const visibleCount = Math.ceil(containerWidth / itemWidth) + 1;

        const start = Math.max(
            0,
            Math.floor(scrollLeft / itemWidth) - overscan,
        );
        const end = Math.min(count, start + visibleCount + overscan * 2);

        startIndex.value = start;
        endIndex.value = end;
        leftSpacerPx.value = start * itemWidth;
        rightSpacerPx.value = (count - end) * itemWidth;
    };

    const scheduleRecompute = () => {
        if (rafId !== null) return;
        rafId = requestAnimationFrame(() => {
            rafId = null;
            recompute();
        });
    };

    const onScroll = () => scheduleRecompute();

    onMounted(async () => {
        await nextTick();
        measureItemWidth();
        recompute();

        const container = containerRef.value;
        if (container) {
            container.addEventListener('scroll', onScroll, { passive: true });

            resizeObserver = new ResizeObserver(() => {
                measureItemWidth();
                scheduleRecompute();
            });
            resizeObserver.observe(container);
        }
    });

    onBeforeUnmount(() => {
        if (rafId !== null) cancelAnimationFrame(rafId);
        containerRef.value?.removeEventListener('scroll', onScroll);
        resizeObserver?.disconnect();
    });

    return { startIndex, endIndex, leftSpacerPx, rightSpacerPx, recompute };
}
