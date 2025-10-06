<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { format, parse, isValid } from 'date-fns'
import { Calendar as CalendarIcon, Clock } from 'lucide-vue-next'
import { cn } from '@/lib/utils'
import { Button } from '@/components/ui/button'
import { Calendar } from '@/components/ui/calendar'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
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
  placeholder: 'Pick a date and time',
  disabled: false,
})

const emit = defineEmits<Emits>()

const open = ref(false)
const timeInputRef = ref<HTMLInputElement>()

/**
 * Parse datetime value from backend
 * The backend sends datetime converted from UTC to app timezone
 */
const parseDateTime = (value: string) => {
  if (!value) return { date: undefined, time: '' }
  
  try {
    // Handle ISO string format from backend
    const date = new Date(value)
    if (isValid(date)) {
      return {
        date,
        time: format(date, 'HH:mm')
      }
    }
    
    // Handle datetime-local format (YYYY-MM-DDTHH:mm) as fallback
    if (value.includes('T') && !value.includes('Z') && !value.includes('+')) {
      const [datePart, timePart] = value.split('T')
      const parsedDate = parse(datePart, 'yyyy-MM-dd', new Date())
      return {
        date: isValid(parsedDate) ? parsedDate : undefined,
        time: timePart || '12:00'
      }
    }
  } catch (error) {
    console.warn('Failed to parse datetime:', value)
  }
  
  return { date: undefined, time: '12:00' }
}

const { date: initialDate, time: initialTime } = parseDateTime(props.modelValue || '')

const selectedDate = ref(initialDate)
const selectedTime = ref(initialTime)

// Watch for external changes to modelValue
watch(() => props.modelValue, (newValue) => {
  const { date, time } = parseDateTime(newValue || '')
  selectedDate.value = date
  selectedTime.value = time
})

const displayValue = computed(() => {
  if (!selectedDate.value) return props.placeholder
  
  const dateStr = format(selectedDate.value, 'PPP')
  const timeStr = selectedTime.value || '12:00'
  return `${dateStr} at ${timeStr}`
})

/**
 * Emit datetime in original format with timezone information
 * The backend will handle timezone conversion from user's timezone to UTC
 */
const emitDateTime = () => {
  if (!selectedDate.value) {
    emit('update:modelValue', '')
    return
  }
  
  const dateStr = format(selectedDate.value, 'yyyy-MM-dd')
  const timeStr = selectedTime.value || '12:00'
  
  // Create a Date object in user's local timezone
  const localDateTime = new Date(`${dateStr}T${timeStr}`)
  
  // Send as ISO string which includes timezone information
  const isoString = localDateTime.toISOString()
  
  emit('update:modelValue', isoString)
}

const handleDateSelect = (date: any) => {
  if (date) {
    const dateToSet = date instanceof Date ? date : new Date(date.toString())
    selectedDate.value = dateToSet
    emitDateTime()
  }
}

const handleTimeChange = (event: Event) => {
  const target = event.target as HTMLInputElement
  selectedTime.value = target.value
  emitDateTime()
}

const handleTimeBlur = () => {
  // Validate time format and set default if invalid
  if (!selectedTime.value || !/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/.test(selectedTime.value)) {
    selectedTime.value = '12:00'
    emitDateTime()
  }
}

const isValidDateTime = computed(() => {
  return selectedDate.value && selectedTime.value && /^([01]?[0-9]|2[0-3]):[0-5][0-9]$/.test(selectedTime.value)
})

const setCurrentDateTime = () => {
  const now = new Date()
  selectedDate.value = now
  selectedTime.value = format(now, 'HH:mm')
  emitDateTime()
  open.value = false
}

const setCurrentTime = () => {
  const now = new Date()
  selectedTime.value = format(now, 'HH:mm')
  emitDateTime()
}

const clearDateTime = () => {
  selectedDate.value = undefined
  selectedTime.value = '12:00'
  emit('update:modelValue', '')
  open.value = false
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
        <CalendarIcon class="mr-2 h-4 w-4" />
        {{ displayValue }}
      </Button>
    </PopoverTrigger>
    <PopoverContent class="w-auto p-0" align="start">
      <div class="p-4 space-y-4">
        <!-- Calendar -->
        <Calendar
          @update:model-value="handleDateSelect"
          initial-focus
        />
        
        <!-- Time Selection -->
        <div class="space-y-3 border-t pt-4">
          <Label class="text-sm font-medium flex items-center gap-2">
            <Clock class="h-4 w-4" />
            Time
          </Label>
          
          <!-- Time Input -->
          <div class="flex items-center gap-2">
            <Input
              ref="timeInputRef"
              type="time"
              :value="selectedTime"
              @input="handleTimeChange"
              @blur="handleTimeBlur"
              class="flex-1"
              placeholder="12:00"
            />
            <Button
              variant="outline"
              size="sm"
              @click="setCurrentTime"
              class="px-3"
              type="button"
            >
              Now
            </Button>
          </div>
          
          <!-- Time Display -->
          <div class="text-xs text-muted-foreground text-center">
            {{ selectedTime ? `Selected: ${selectedTime}` : 'No time selected' }}
          </div>
        </div>
        
        <!-- Action Buttons -->
        <div class="flex gap-2 pt-2">
          <Button
            variant="outline"
            size="sm"
            @click="setCurrentDateTime"
            class="flex-1"
          >
            Now
          </Button>
          <Button
            variant="outline"
            size="sm"
            @click="clearDateTime"
            class="flex-1"
          >
            Clear
          </Button>
        </div>
      </div>
    </PopoverContent>
  </Popover>
</template>