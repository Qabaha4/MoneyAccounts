import { ref, watch, readonly, onMounted, onUnmounted } from 'vue';
import { usePage } from '@inertiajs/vue3';

export type ToastType = 'success' | 'error';

export interface Toast {
    id: number;
    type: ToastType;
    message: string;
}

interface ToastTimer {
    timeoutId: ReturnType<typeof setTimeout> | null;
    remainingMs: number;
    startedAt: number;
}

const toasts = ref<Toast[]>([]);
const timers = new Map<number, ToastTimer>();
let nextId = 0;

const DISMISS_MS: Record<ToastType, number> = {
    success: 5000,
    error: 8000,
};

const prefersReducedMotion = ref(false);

function clearToastTimer(id: number): void {
    const timer = timers.get(id);
    if (timer && timer.timeoutId !== null && timer.timeoutId !== undefined) {
        clearTimeout(timer.timeoutId);
        timer.timeoutId = null;
    }
}

function scheduleTimer(id: number, durationMs: number): void {
    const timer = timers.get(id);
    if (!timer) return;
    clearToastTimer(id);
    timer.startedAt = Date.now();
    timer.remainingMs = durationMs;
    timer.timeoutId = setTimeout(() => {
        timers.delete(id);
        const index = toasts.value.findIndex((t) => t.id === id);
        if (index !== -1) {
            toasts.value.splice(index, 1);
        }
    }, durationMs);
}

export function useToast() {
    function removeToast(id: number) {
        clearToastTimer(id);
        timers.delete(id);
        const index = toasts.value.findIndex((t) => t.id === id);
        if (index !== -1) {
            toasts.value.splice(index, 1);
        }
    }

    function pauseToast(id: number): void {
        const timer = timers.get(id);
        if (!timer || timer.timeoutId === null) return;
        const elapsed = Date.now() - timer.startedAt;
        timer.remainingMs = Math.max(0, timer.remainingMs - elapsed);
        clearToastTimer(id);
    }

    function resumeToast(id: number): void {
        const timer = timers.get(id);
        if (!timer) return;
        scheduleTimer(id, timer.remainingMs);
    }

    function addToast(type: ToastType, message: string): void {
        const id = ++nextId;
        toasts.value.push({ id, type, message });

        if (toasts.value.length > 5) {
            const removed = toasts.value.shift();
            if (removed) {
                clearToastTimer(removed.id);
                timers.delete(removed.id);
            }
        }

        timers.set(id, {
            timeoutId: null,
            remainingMs: DISMISS_MS[type],
            startedAt: Date.now(),
        });
        scheduleTimer(id, DISMISS_MS[type]);
    }

    const page = usePage();
    watch(
        () => page.props.flash as { success?: string; error?: string } | undefined,
        (flash) => {
            if (flash?.success) addToast('success', flash.success);
            if (flash?.error)   addToast('error',   flash.error);
        },
        { immediate: true, deep: true },
    );

    onMounted(() => {
        const mq = window.matchMedia('(prefers-reduced-motion: reduce)');
        prefersReducedMotion.value = mq.matches;
        const handler = (e: MediaQueryListEvent) => {
            prefersReducedMotion.value = e.matches;
        };
        mq.addEventListener('change', handler);
        onUnmounted(() => mq.removeEventListener('change', handler));
    });

    return {
        toasts: readonly(toasts),
        addToast,
        removeToast,
        pauseToast,
        resumeToast,
        prefersReducedMotion: readonly(prefersReducedMotion),
    };
}
