<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils'

const props = defineProps<{
  class?: HTMLAttributes['class']
}>()
</script>

<template>
  <div
    data-slot="card"
    :class="
      cn(
        'bg-card text-card-foreground rounded-xl border border-border/50 shadow-sm hover:shadow-md transition-shadow',
        props.class,
      )
    "
  >
    <slot />
  </div>
</template>