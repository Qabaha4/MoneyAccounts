<script setup lang="ts">
import { ref, computed, nextTick } from 'vue';
import { X, CheckCircle, XCircle } from 'lucide-vue-next';
import { useToast } from '@/composables/useToast';
import { cn } from '@/lib/utils';

const { toasts, removeToast, pauseToast, resumeToast, prefersReducedMotion } = useToast();

interface DragState {
    toastId: number;
    startX: number;
    startY: number;
    currentX: number;
    currentY: number;
    isDragging: boolean;
    direction: 'horizontal' | 'vertical';
    pointerId: number;
}

const dragState = ref<DragState | null>(null);
const toastEls = new Map<number, HTMLElement>();
const isMobile = ref(false);

function setToastRef(toastId: number, el: unknown) {
    if (el instanceof HTMLElement) {
        toastEls.set(toastId, el);
    } else {
        toastEls.delete(toastId);
    }
}

function getDragTransform(toastId: number): string {
    if (!dragState.value || dragState.value.toastId !== toastId) {
        return '';
    }
    const { currentX, startX, currentY, startY } = dragState.value;
    return `translate(${currentX - startX}px, ${currentY - startY}px)`;
}

function getDragTransition(toastId: number): string {
    if (!dragState.value || dragState.value.toastId !== toastId) {
        return '';
    }
    return dragState.value.isDragging ? 'none' : 'transform 0.45s cubic-bezier(0.22,0.61,0.36,1), opacity 0.45s cubic-bezier(0.22,0.61,0.36,1)';
}

function getDragOpacity(toastId: number): number {
    if (!dragState.value || dragState.value.toastId !== toastId || !dragState.value.isDragging) {
        return 1;
    }
    const el = toastEls.get(toastId);
    if (!el) return 1;

    const { currentX, startX, currentY, startY, direction } = dragState.value;
    const distX = Math.abs(currentX - startX);
    const distY = Math.abs(currentY - startY);
    const distance = direction === 'horizontal' ? distX : distY;
    const maxDist = direction === 'horizontal' ? el.offsetWidth * 0.8 : el.offsetHeight * 1.5;
    return Math.max(0.2, 1 - distance / maxDist);
}

const dragStyle = computed(() => {
    if (!dragState.value || !dragState.value.isDragging) return {};
    const toastId = dragState.value.toastId;
    return {
        transform: getDragTransform(toastId),
        transition: getDragTransition(toastId),
        opacity: String(getDragOpacity(toastId)),
    };
});

function onPointerDown(e: PointerEvent, toastId: number) {
    if (prefersReducedMotion.value) return;
    const el = toastEls.get(toastId);
    if (!el) return;

    isMobile.value = window.innerWidth < 640;

    dragState.value = {
        toastId,
        startX: e.clientX,
        startY: e.clientY,
        currentX: e.clientX,
        currentY: e.clientY,
        isDragging: false,
        direction: isMobile.value ? 'vertical' : 'horizontal',
        pointerId: e.pointerId,
    };

    el.setPointerCapture(e.pointerId);
    pauseToast(toastId);
    e.preventDefault();
}

function onPointerMove(e: PointerEvent) {
    if (!dragState.value || e.pointerId !== dragState.value.pointerId) return;

    const dx = Math.abs(e.clientX - dragState.value.startX);
    const dy = Math.abs(e.clientY - dragState.value.startY);

    if (!dragState.value.isDragging && (dx > 3 || dy > 3)) {
        dragState.value.isDragging = true;
    }

    dragState.value.currentX = e.clientX;
    dragState.value.currentY = e.clientY;
    e.preventDefault();
}

function onPointerUp(e: PointerEvent) {
    if (!dragState.value || e.pointerId !== dragState.value.pointerId) return;

    const el = toastEls.get(dragState.value.toastId);
    const state = { ...dragState.value };

    if (el) el.releasePointerCapture(state.pointerId);

    if (!state.isDragging) {
        dragState.value = null;
        resumeToast(state.toastId);
        return;
    }

    const dx = state.currentX - state.startX;
    const dy = state.currentY - state.startY;
    const absDx = Math.abs(dx);
    const absDy = Math.abs(dy);

    let shouldDismiss = false;

    if (state.direction === 'vertical') {
        shouldDismiss = dy < -10 && absDy > (el?.offsetHeight ?? 40) * 0.3;
    } else {
        shouldDismiss = absDx > (el?.offsetWidth ?? 100) * 0.4;
    }

    if (shouldDismiss) {
        removeToast(state.toastId);
        nextTick(() => { dragState.value = null; });
    } else {
        dragState.value = {
            ...state,
            isDragging: false,
            currentX: state.startX,
            currentY: state.startY,
        };
        resumeToast(state.toastId);
        setTimeout(() => {
            if (
                dragState.value &&
                dragState.value.toastId === state.toastId &&
                !dragState.value.isDragging
            ) {
                dragState.value = null;
            }
        }, 300);
    }
}


</script>

<template>
    <TransitionGroup
        tag="div"
        :name="prefersReducedMotion ? '' : 'toast'"
        move-class="toast-move"
        aria-live="polite"
        class="fixed z-[100] inset-x-0 flex flex-col items-center sm:items-end gap-2 sm:gap-3 pointer-events-none"
        :class="prefersReducedMotion ? 'top-[calc(env(safe-area-inset-top,0.75rem)+1rem)]' : 'top-[calc(env(safe-area-inset-top,0.75rem)+1.5rem)]'"
    >
        <div
            v-for="toast in toasts"
            :key="toast.id"
            :ref="(el: unknown) => setToastRef(toast.id, el)"
            :class="cn(
                'toast-item pointer-events-auto relative flex w-[18rem] max-w-[calc(100vw-1.5rem)] sm:w-full sm:max-w-sm items-center gap-2 sm:gap-2.5 overflow-hidden rounded-[20px] p-3 sm:p-4 isolate select-none shadow-[0_8px_32px_-8px_rgba(0,0,0,0.12)]',
                dragState?.toastId === toast.id && dragState.isDragging ? 'cursor-grabbing' : 'cursor-default',
            )"
            :style="dragState?.toastId === toast.id ? { ...dragStyle, transition: getDragTransition(toast.id) } : {}"
            role="alert"
            @pointerdown="(e: PointerEvent) => onPointerDown(e, toast.id)"
            @pointermove="onPointerMove"
            @pointerup="onPointerUp"
            @pointercancel="
                () => {
                    if (!dragState || dragState.toastId !== toast.id) return;
                    const el = toastEls.get(toast.id);
                    const state = dragState;
                    dragState = null;
                    resumeToast(toast.id);
                    if (el) el.releasePointerCapture(state.pointerId);
                }
            "
        >
            <!-- Gradient brand background + 3D bevel shadow (matches HeroSection) -->
            <div
                class="absolute inset-0 rounded-[20px] pointer-events-none gradient-brand"
                style="box-shadow: inset 0 1px 0 0 rgba(255,255,255,0.12);"
            />

            <!-- Subtle glow orb -->
            <div class="absolute -top-12 -start-12 w-28 h-28 bg-white/15 blur-3xl rounded-full pointer-events-none"></div>

            <!-- Dot-grid overlay (matching HeroSection dot texture) -->
            <div
                class="absolute inset-0 rounded-[20px] opacity-[0.04] pointer-events-none"
                style="background-image: radial-gradient(circle, #fff 1px, transparent 1px); background-size: 18px 18px;"
            />

            <!-- Icon -->
            <div
                class="relative z-10 flex size-7 sm:size-8 shrink-0 items-center justify-center text-white"
            >
                <CheckCircle v-if="toast.type === 'success'" class="size-4 sm:size-[18px]" />
                <XCircle v-if="toast.type === 'error'" class="size-4 sm:size-[18px]" />
            </div>

            <!-- Message -->
            <p
                class="relative z-10 flex-1 min-w-0 text-xs sm:text-sm font-medium leading-snug text-white"
            >
                {{ toast.message }}
            </p>

            <!-- Close button -->
            <button
                type="button"
                @click.stop="removeToast(toast.id)"
                class="relative z-10 inline-flex size-6 sm:size-7 shrink-0 items-center justify-center rounded-lg transition-colors self-start -mt-0.5 -me-0.5 sm:-mt-1 sm:-me-1 text-white/70 hover:text-white hover:bg-white/10"
                aria-label="Close"
            >
                <X class="size-3.5 sm:size-4" />
            </button>

            <!-- Progress bar -->
            <span
                class="absolute bottom-0 start-0 h-[2px] rounded-full bg-white/50"
                :style="{ animationDuration: toast.type === 'success' ? '5s' : '8s' }"
                style="animation: toast-shrink linear forwards;"
            />
        </div>
    </TransitionGroup>
</template>

<style scoped>
.toast-enter-active {
    transition: transform 0.5s cubic-bezier(0.34, 1.56, 0.64, 1), opacity 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.toast-leave-active {
    transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: absolute;
}
.toast-move {
    transition: transform 0.4s cubic-bezier(0.22, 0.61, 0.36, 1);
}
.toast-enter-from {
    opacity: 0;
    transform: translateY(-24px) scale(0.92) translateZ(0);
}
.toast-leave-to {
    opacity: 0;
    transform: scale(0.88) translateY(-12px) translateZ(0);
}

.toast-item {
    will-change: transform, opacity;
}

@keyframes toast-shrink {
    from {
        width: 100%;
    }
    to {
        width: 0%;
    }
}

@media (prefers-reduced-motion: reduce) {
    .toast-enter-active,
    .toast-leave-active,
    .toast-move {
        transition: opacity 0.2s !important;
    }
    .toast-enter-from,
    .toast-leave-to {
        filter: none !important;
        transform: none !important;
        opacity: 0;
    }
    .toast-enter-to,
    .toast-leave-from {
        opacity: 1;
    }
}
</style>
