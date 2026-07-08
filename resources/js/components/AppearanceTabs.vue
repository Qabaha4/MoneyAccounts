<script setup lang="ts">
import { useI18n } from 'vue-i18n';
import { useAppearance } from '@/composables/useAppearance';
import { Monitor, Moon, Sun } from 'lucide-vue-next';

const { t } = useI18n();
const { appearance, updateAppearance } = useAppearance();

const tabs = [
    { value: 'light', Icon: Sun, label: t('appearance.light') },
    { value: 'dark', Icon: Moon, label: t('appearance.dark') },
    { value: 'system', Icon: Monitor, label: t('appearance.system') },
] as const;
</script>

<template>
    <div
        class="inline-flex gap-1 rounded-lg bg-secondary p-1"
    >
        <button
            v-for="{ value, Icon, label } in tabs"
            :key="value"
            @click="updateAppearance(value)"
            :class="[
                'flex items-center rounded-md px-3.5 py-1.5 transition-colors',
                appearance === value
                    ? 'bg-card shadow-sm text-foreground'
                    : 'text-muted-foreground hover:text-foreground hover:bg-accent/50',
            ]"
        >
            <component :is="Icon" class="-ms-1 h-4 w-4" />
            <span class="ms-1.5 text-sm">{{ label }}</span>
        </button>
    </div>
</template>
