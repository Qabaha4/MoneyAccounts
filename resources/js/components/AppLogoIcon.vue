<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { Wallet } from 'lucide-vue-next';

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
        <Wallet class="w-5 h-5 text-white" />
    </div>
</template>
