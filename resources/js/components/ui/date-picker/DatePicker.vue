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
</script>

<template>
  <Popover v-model:open="open">
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