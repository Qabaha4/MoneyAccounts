<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

interface Props {
    className?: HTMLAttributes['class'];
}

defineProps<Props>();
</script>

<template>
    <div
        :class="className"
        class="w-8 h-8 bg-gradient-to-br from-blue-500 to-purple-600 rounded-lg flex items-center justify-center"
        v-bind="$attrs"
    >
        <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M4 4a2 2 0 0 0-2 2v1h16V6a2 2 0 0 0-2-2H4zM2 9v5a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9H2zm6 3a1 1 0 1 1 2 0 1 1 0 0 1-2 0z" clip-rule="evenodd"/>
        </svg>
    </div>
</template>
