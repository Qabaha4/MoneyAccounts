<script setup lang="ts">
import { computed, type Component } from 'vue'
import { useI18n } from 'vue-i18n'
import { AlertTriangle, AlertCircle, Info } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import {
  Dialog,
  DialogClose,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogTitle,
} from '@/components/ui/dialog'
import LoadingSpinner from '@/components/LoadingSpinner.vue'

const { t } = useI18n()

type ConfirmType = 'danger' | 'warning' | 'info'

const props = withDefaults(defineProps<{
  open?: boolean
  title?: string
  message?: string
  confirmText?: string
  cancelText?: string
  type?: ConfirmType
  loading?: boolean
}>(), {
  type: 'danger',
})

const emit = defineEmits<{
  'update:open': [value: boolean]
  'confirm': []
  'cancel': []
}>()

const iconMap: Record<ConfirmType, Component> = {
  danger: AlertTriangle,
  warning: AlertCircle,
  info: Info,
}

const icon = computed(() => iconMap[props.type])
</script>

<template>
  <Dialog :open="open" @update:open="(val) => { if (!val) emit('cancel'); emit('update:open', val) }">
    <DialogContent class="sm:max-w-md !gap-0 p-0">
      <div
        class="flex flex-col items-center gap-4 px-6 pb-0 pt-8 text-center sm:flex-row sm:items-start sm:gap-5 sm:pt-6 sm:text-start"
      >
        <div
          class="flex size-12 shrink-0 items-center justify-center rounded-full"
          :class="{
            'bg-destructive/10': type === 'danger',
            'bg-amber-500/10': type === 'warning',
            'bg-primary/10': type === 'info',
          }"
        >
          <component
            :is="icon"
            class="size-6"
            :class="{
              'text-destructive': type === 'danger',
              'text-amber-500': type === 'warning',
              'text-primary': type === 'info',
            }"
          />
        </div>
        <div class="flex min-w-0 flex-col gap-1">
          <DialogTitle class="text-base sm:text-lg">
            {{ title || t('common.confirm') }}
          </DialogTitle>
          <DialogDescription class="text-sm text-muted-foreground">
           {{ message }}
          </DialogDescription>
        </div>
      </div>

      <slot />

      <DialogFooter class="flex-col gap-2 px-6 pb-6 pt-4 sm:flex-row">
        <DialogClose as-child>
          <Button variant="secondary" class="w-full sm:w-auto" @click="emit('cancel')">
            {{ cancelText || t('common.cancel') }}
          </Button>
        </DialogClose>
        <Button
          :variant="type === 'danger' ? 'destructive' : 'default'"
          class="w-full sm:w-auto"
          :disabled="loading"
          @click="emit('confirm')"
        >
          <LoadingSpinner v-if="loading" size="sm" class="text-inherit" />
          {{ confirmText || t('common.confirm') }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
