<template>
  <Dialog :open="isOpen" @update:open="handleOpenChange">
    <DialogContent class="sm:max-w-md">
      <DialogHeader>
        <DialogTitle>
          {{ isEditing ? t('transactions.edit_transaction') : t('transactions.add_transaction') }}
        </DialogTitle>
        <DialogDescription>
          {{ isEditing ? t('transactions.edit_description') : t('transactions.create_description') }}
        </DialogDescription>
      </DialogHeader>

      <form @submit.prevent="submit" class="space-y-4">
        <div class="grid gap-4">
          <!-- Account (only show when account change is allowed and not editing) -->
          <div v-if="allowAccountChange && !isEditing" class="space-y-2">
            <Label for="account_id">{{ t('transactions.account') }}</Label>
            <Select v-model="form.account_id" required>
              <SelectTrigger :class="{ 'border-red-500': form.errors.account_id }">
                <SelectValue :placeholder="t('accounts.select_account')" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem 
                  v-for="account in accounts" 
                  :key="account.id" 
                  :value="account.id.toString()"
                >
                  {{ account.name }} ({{ account.currency?.symbol || '' }})
                </SelectItem>
              </SelectContent>
            </Select>
            <p v-if="form.errors.account_id" class="text-sm text-red-600">{{ form.errors.account_id }}</p>
          </div>

          <!-- Transaction Type -->
          <div class="space-y-2">
            <Label for="type">{{ t('transactions.type') }}</Label>
            <Select :key="`type-${transaction?.id || 'new'}`" v-model="form.type" required>
              <SelectTrigger :class="{ 'border-red-500': form.errors.type }">
                <SelectValue :placeholder="t('transactions.select_type')" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="income">{{ t('transactions.income') }}</SelectItem>
                <SelectItem value="expense">{{ t('transactions.expense') }}</SelectItem>
                <SelectItem value="transfer">{{ t('transactions.transfer') }}</SelectItem>
              </SelectContent>
            </Select>
            <p v-if="form.errors.type" class="text-sm text-red-600">{{ form.errors.type }}</p>
          </div>

          <!-- Transfer To Account (only for transfers) -->
          <div v-if="form.type === 'transfer'" class="space-y-2">
            <Label for="transfer_to_account_id">{{ t('transactions.transfer_to_account') }}</Label>
            <Select :key="`transfer-${transaction?.id || 'new'}`" v-model="form.transfer_to_account_id" required>
              <SelectTrigger :class="{ 'border-red-500': form.errors.transfer_to_account_id }">
                <SelectValue :placeholder="t('transactions.select_destination_account')" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem 
                  v-for="account in availableTransferAccounts" 
                  :key="account.id" 
                  :value="account.id.toString()"
                >
                  {{ account.name }} ({{ account.currency?.symbol || '' }})
                </SelectItem>
              </SelectContent>
            </Select>
            <p v-if="form.errors.transfer_to_account_id" class="text-sm text-red-600">{{ form.errors.transfer_to_account_id }}</p>
          </div>

          <!-- Currency Conversion (only for cross-currency transfers) -->
          <div v-if="isCrossCurrencyTransfer" class="space-y-4 p-4 bg-blue-50 dark:bg-blue-950/20 rounded-lg border border-blue-200 dark:border-blue-800">
            <div class="flex items-center gap-2">
              <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
              <span class="text-sm font-semibold text-blue-700 dark:text-blue-300">Currency Conversion</span>
            </div>
            
            <div class="grid gap-4">
              <!-- Exchange Rate -->
              <div class="space-y-2">
                <Label for="exchange_rate">Exchange Rate</Label>
                <div class="flex items-center gap-2">
                  <span class="text-sm text-gray-600 dark:text-gray-400">
                    {{ sourceCurrency?.symbol || '' }} 1 = {{ destinationCurrency?.symbol || '' }}
                  </span>
                  <Input
                    id="exchange_rate"
                    v-model="form.exchange_rate"
                    type="number"
                    step="0.000001"
                    min="0.000001"
                    placeholder="1.000000"
                    class="flex-1"
                    :class="{ 'border-red-500': form.errors.exchange_rate }"
                  />
                </div>
                <p v-if="form.errors.exchange_rate" class="text-sm text-red-600">{{ form.errors.exchange_rate }}</p>
              </div>

              <!-- Converted Amount -->
              <div class="space-y-2">
                <Label for="converted_amount">Converted Amount</Label>
                <div class="flex items-center gap-2">
                  <span class="text-sm text-gray-600 dark:text-gray-400">{{ destinationCurrency?.symbol || '' }}</span>
                  <Input
                    id="converted_amount"
                    v-model="form.converted_amount"
                    type="number"
                    step="0.01"
                    min="0.01"
                    placeholder="0.00"
                    class="flex-1"
                    :class="{ 'border-red-500': form.errors.converted_amount }"
                  />
                </div>
                <p v-if="form.errors.converted_amount" class="text-sm text-red-600">{{ form.errors.converted_amount }}</p>
                <p class="text-xs text-gray-500">
                  Amount that will be added to the destination account
                </p>
              </div>
            </div>
          </div>

          <!-- Amount -->
          <div class="space-y-2">
            <Label for="amount">{{ t('transactions.amount') }}</Label>
            <Input
              id="amount"
              v-model="form.amount"
              type="number"
              step="0.01"
              min="0.01"
              placeholder="0.00"
              required
              :class="{ 'border-red-500': form.errors.amount }"
            />
            <p v-if="form.errors.amount" class="text-sm text-red-600">{{ form.errors.amount }}</p>
          </div>

          <!-- Description -->
          <div class="space-y-2">
            <Label for="description">{{ t('transactions.description') }} <span class="text-gray-500">({{ t('common.optional') }})</span></Label>
            <Input
              id="description"
              v-model="form.description"
              type="text"
              :placeholder="t('transactions.description_placeholder')"
              :class="{ 'border-red-500': form.errors.description }"
            />
            <p v-if="form.errors.description" class="text-sm text-red-600">{{ form.errors.description }}</p>
          </div>

          <!-- Transaction Date -->
          <div class="space-y-2">
            <Label for="transaction_date">{{ t('transactions.date') }}</Label>
            <DateTimePicker
              v-model="form.transaction_date"
              :placeholder="t('transactions.select_date_time')"
              :class="form.errors.transaction_date ? 'border-red-500' : ''"
            />
            <p v-if="form.errors.transaction_date" class="text-sm text-red-600">{{ form.errors.transaction_date }}</p>
          </div>
        </div>

        <!-- Form Actions -->
        <div class="flex items-center justify-between pt-4 w-full">
          <div>
            <Button 
              v-if="isEditing" 
              variant="destructive" 
              type="button" 
              @click="handleDelete"
              :disabled="form.processing || isDeleting"
              class="transition-all duration-200 hover:scale-105 hover:shadow-lg active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <LoadingSpinner v-if="isDeleting" class="w-4 h-4" />
              <Trash2 v-else class="w-4 h-4" />
              {{ isDeleting ? t('transactions.deleting') : t('common.delete') }}
            </Button>
            <span v-else></span>
          </div>
          <div class="flex justify-end">
            <Button 
              type="submit" 
              :disabled="form.processing || isDeleting"
              class="transition-all duration-200 hover:scale-105 hover:shadow-lg active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <LoadingSpinner v-if="form.processing" class="w-4 h-4" />
              <Save v-else-if="isEditing" class="w-4 h-4" />
              <Plus v-else class="w-4 h-4" />
              {{ form.processing 
                ? (isEditing ? t('transactions.updating') : t('transactions.creating'))
                : (isEditing ? t('transactions.update_transaction') : t('transactions.create_transaction')) }}
            </Button>
          </div>
        </div>
      </form>
    </DialogContent>
  </Dialog>
</template>

<script setup lang="ts">
import { computed, watch, ref, nextTick } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { Trash2, Save, Plus } from 'lucide-vue-next'
import transactionRoutes from '@/routes/transactions'
import LoadingSpinner from '@/components/LoadingSpinner.vue'
import DateTimePicker from '@/components/ui/date-time-picker/DateTimePicker.vue'

const { t } = useI18n()

interface Currency {
  id: number
  code: string
  symbol: string
  name: string
}

interface Account {
  id: number
  name: string
  currency: Currency
}

interface Transaction {
  id: number
  type: string
  amount: string
  description: string | null
  transaction_date: string
  account: Account
  transfer_to_account?: Account
  exchange_rate?: string | null
  converted_amount?: string | null
  exchange_rate_source?: string | null
}

interface Props {
  isOpen: boolean
  accounts: Account[]
  transaction?: Transaction | null
  defaultAccountId?: number
  allowAccountChange?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  allowAccountChange: true
})

const emit = defineEmits<{
  'update:isOpen': [value: boolean]
  'success': []
}>()

const isEditing = computed(() => !!props.transaction)
const isDeleting = ref(false)

// Helper function to format datetime for datetime-local input
const formatDateTimeLocal = (date: Date | string): string => {
  const d = new Date(date)
  const year = d.getFullYear()
  const month = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  const hours = String(d.getHours()).padStart(2, '0')
  const minutes = String(d.getMinutes()).padStart(2, '0')
  return `${year}-${month}-${day}T${hours}:${minutes}`
}

// Helper function to format datetime for backend submission
const formatDateTimeForSubmission = (dateTimeLocal: string): string => {
  // Convert datetime-local format to ISO string without timezone
  const date = new Date(dateTimeLocal)
  return date.toISOString().slice(0, 19).replace('T', ' ')
}

const form = useForm({
  account_id: '',
  type: '',
  amount: '',
  description: '',
  transaction_date: formatDateTimeLocal(new Date()),
  transfer_to_account_id: '',
  exchange_rate: '',
  converted_amount: '',
  exchange_rate_source: 'manual',
})

const availableTransferAccounts = computed(() => {
  if (!form.account_id) {
    return props.accounts
  }
  return props.accounts.filter(account => account.id.toString() !== form.account_id)
})

// Currency conversion computed properties
const sourceAccount = computed(() => {
  return props.accounts.find(account => account.id.toString() === form.account_id)
})

const destinationAccount = computed(() => {
  return props.accounts.find(account => account.id.toString() === form.transfer_to_account_id)
})

const sourceCurrency = computed(() => {
  return sourceAccount.value?.currency
})

const destinationCurrency = computed(() => {
  return destinationAccount.value?.currency
})

const isCrossCurrencyTransfer = computed(() => {
  return form.type === 'transfer' && 
         sourceCurrency.value && 
         destinationCurrency.value && 
         sourceCurrency.value.id !== destinationCurrency.value.id
})

// Auto-calculate converted amount when exchange rate or amount changes
watch([() => form.exchange_rate, () => form.amount], () => {
  if (isCrossCurrencyTransfer.value && form.exchange_rate && form.amount) {
    const rate = parseFloat(form.exchange_rate)
    const amount = parseFloat(form.amount)
    if (!isNaN(rate) && !isNaN(amount) && rate > 0 && amount > 0) {
      form.converted_amount = (amount * rate).toFixed(4)
    }
  }
})

// Auto-calculate exchange rate when converted amount changes
watch(() => form.converted_amount, () => {
  if (isCrossCurrencyTransfer.value && form.converted_amount && form.amount) {
    const convertedAmount = parseFloat(form.converted_amount)
    const amount = parseFloat(form.amount)
    if (!isNaN(convertedAmount) && !isNaN(amount) && amount > 0) {
      form.exchange_rate = (convertedAmount / amount).toFixed(6)
    }
  }
})

// Initialize form data when modal opens or transaction changes
watch([() => props.isOpen, () => props.transaction], () => {
  if (props.isOpen) {
    if (props.transaction) {
      // Edit mode - populate with existing transaction data
      // Use nextTick to ensure proper initialization order
      nextTick(() => {
        form.account_id = props.transaction.account?.id?.toString() || props.defaultAccountId?.toString() || ''
        form.type = props.transaction.type || ''
        form.amount = props.transaction.amount || ''
        form.description = props.transaction.description || ''
        // Format the stored datetime for datetime-local input
        form.transaction_date = formatDateTimeLocal(props.transaction.transaction_date)
        form.transfer_to_account_id = props.transaction.transfer_to_account?.id?.toString() || ''
        // Exchange rate fields
        form.exchange_rate = props.transaction.exchange_rate?.toString() || ''
        form.converted_amount = props.transaction.converted_amount?.toString() || ''
        form.exchange_rate_source = props.transaction.exchange_rate_source || 'manual'
      })
    } else {
      // Create mode - reset form with defaults
      form.reset()
      form.transaction_date = formatDateTimeLocal(new Date())
      if (props.defaultAccountId) {
        form.account_id = props.defaultAccountId.toString()
      }
    }
  }
}, { immediate: true })

const handleOpenChange = (open: boolean) => {
  emit('update:isOpen', open)
  if (!open) {
    form.reset()
    form.clearErrors()
  }
}

const submit = () => {
  // Store original datetime value
  const originalDateTime = form.transaction_date
  
  // Format datetime for backend submission
  form.transaction_date = formatDateTimeForSubmission(form.transaction_date)

  if (isEditing.value && props.transaction) {
    // Update existing transaction
    form.put(transactionRoutes.update({ transaction: props.transaction.id }).url, {
      onSuccess: () => {
        emit('success')
        emit('update:isOpen', false)
        form.reset()
      },
      onError: () => {
        // Restore original datetime on error
        form.transaction_date = originalDateTime
      }
    })
  } else {
    // Create new transaction
    form.post(transactionRoutes.store().url, {
      onSuccess: () => {
        emit('success')
        emit('update:isOpen', false)
        form.reset()
      },
      onError: () => {
        // Restore original datetime on error
        form.transaction_date = originalDateTime
      }
    })
  }
}

const handleDelete = () => {
  if (!props.transaction) return
  
  const confirmMessage = t('common.confirm_delete', { 
    name: props.transaction.description || 'this transaction' 
  })
  
  if (confirm(confirmMessage)) {
    isDeleting.value = true
    
    router.delete(transactionRoutes.destroy({ transaction: props.transaction.id }).url, {
      onSuccess: () => {
        emit('success')
        emit('update:isOpen', false)
        form.reset()
      },
      onError: () => {
        isDeleting.value = false
      },
      onFinish: () => {
        isDeleting.value = false
      }
    })
  }
}
</script>