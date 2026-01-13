<script setup lang="ts">
import { Wallet } from 'lucide-vue-next';
</script>

<template>
    <div
        class="flex aspect-square size-8 items-center justify-center rounded-md bg-gradient-to-br from-blue-500 to-purple-600"
    >
        <Wallet class="w-5 h-5 text-white" />
    </div>
    <div class="ms-1 grid flex-1 text-left text-sm">
        <span class="mb-0.5 truncate leading-tight font-semibold"
            >M.A.M.</span
        >
    </div>
</template>
