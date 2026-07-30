<script setup lang="ts">
import { computed, ref } from 'vue'
import { format } from 'date-fns'
import { Calendar as CalendarIcon } from 'lucide-vue-next'
import { cn } from '@/lib/utils'
import { Button } from '@/components/ui/button'
import { Calendar } from '@/components/ui/calendar'
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from '@/components/ui/popover'

const isIOS = computed(() => {
  if (typeof navigator === 'undefined' || typeof navigator.userAgent !== 'string') return false
  return /iPad|iPhone|iPod/.test(navigator.userAgent) ||
         (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1)
})

interface Props {
  modelValue?: string
  placeholder?: string
  disabled?: boolean
  class?: string
}

interface Emits {
  (e: 'update:modelValue', value: string): void
}

const props = withDefaults(defineProps<Props>(), {
  placeholder: 'Pick a date',
  disabled: false,
})

const emit = defineEmits<Emits>()

const open = ref(false)

const selectedDate = computed({
  get: () => props.modelValue ? new Date(props.modelValue) : undefined,
  set: (value: any) => {
    if (value) {
      // Handle both Date objects and DateValue objects from reka-ui
      const dateToFormat = value instanceof Date ? value : new Date(value.toString())
      emit('update:modelValue', format(dateToFormat, 'yyyy-MM-dd'))
      open.value = false
    }
  }
})

const displayValue = computed(() => {
  return selectedDate.value ? format(selectedDate.value, 'PPP') : props.placeholder
})

function handleDateSelect(date: any) {
  if (date) {
    const dateToFormat = date instanceof Date ? date : new Date(date.toString())
    emit('update:modelValue', format(dateToFormat, 'yyyy-MM-dd'))
    open.value = false
  }
}

const nativeValue = computed({
  get: () => props.modelValue || '',
  set: (val: string) => {
    emit('update:modelValue', val)
  }
})
</script>

<template>
  <!-- iOS: native date input -->
  <div v-if="isIOS" class="relative">
    <input
      v-model="nativeValue"
      type="date"
      dir="rtl"
      data-slot="input"
      :disabled="disabled"
      :class="cn(
        'file:text-foreground placeholder:text-muted-foreground selection:bg-primary selection:text-primary-foreground flex h-9 w-full min-w-0 rounded-xl border bg-transparent px-3 py-1 text-base shadow-xs transition-[color,box-shadow] outline-none file:inline-flex file:h-7 file:border-0 file:bg-transparent file:text-sm file:font-medium disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 md:text-sm',
        'appearance-none border-border/50 focus-visible:border-ring/50 focus-visible:ring-ring/20 focus-visible:ring-[3px]',
        props.class,
      )"
    />
  </div>

  <!-- Other OS: custom popover picker -->
  <Popover v-else v-model:open="open">
    <PopoverTrigger as-child>
      <Button
        variant="outline"
        :class="cn(
          'w-full justify-start text-left font-normal',
          !selectedDate && 'text-muted-foreground',
          props.class
        )"
        :disabled="disabled"
      >
        <CalendarIcon class="me-2 h-4 w-4" />
        {{ displayValue }}
      </Button>
    </PopoverTrigger>
    <PopoverContent class="w-auto p-0" align="start">
      <Calendar
        @update:model-value="handleDateSelect"
        initial-focus
      />
    </PopoverContent>
  </Popover>
</template>