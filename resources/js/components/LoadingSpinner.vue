<script setup lang="ts">
import { Loader2 } from 'lucide-vue-next'
import { cn } from '@/lib/utils'

interface LoadingSpinnerProps {
  size?: 'sm' | 'md' | 'lg' | 'xl'
  class?: string
  text?: string
}

const props = withDefaults(defineProps<LoadingSpinnerProps>(), {
  size: 'md'
})

const sizeClasses = {
  sm: 'w-4 h-4',
  md: 'w-6 h-6',
  lg: 'w-8 h-8',
  xl: 'w-12 h-12'
}
</script>

<template>
  <div class="flex flex-col items-center justify-center gap-2">
    <Loader2 
      :class="cn(
        'animate-spin text-muted-foreground',
        sizeClasses[size],
        props.class
      )" 
    />
    <p v-if="text" class="text-sm text-muted-foreground animate-pulse">
      {{ text }}
    </p>
  </div>
</template>