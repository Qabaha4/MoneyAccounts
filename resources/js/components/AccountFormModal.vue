<template>
  <Dialog v-model:open="isOpen">
    <DialogContent class="sm:max-w-[500px]">
      <DialogHeader>
        <DialogTitle>{{ isEditing ? 'Edit Account' : 'Create Account' }}</DialogTitle>
        <DialogDescription>
          {{ isEditing ? 'Update your account information.' : 'Create a new account to manage your finances.' }}
        </DialogDescription>
      </DialogHeader>

      <form @submit.prevent="submit" class="space-y-4">
        <div class="grid gap-4">
          <!-- Account Name -->
          <div class="space-y-2">
            <Label for="name">Account Name</Label>
            <Input
              id="name"
              v-model="form.name"
              type="text"
              placeholder="Enter account name"
              :class="{ 'border-red-500': form.errors.name }"
              required
            />
            <p v-if="form.errors.name" class="text-sm text-red-600">{{ form.errors.name }}</p>
          </div>

          <!-- Currency -->
          <div class="space-y-2">
            <Label for="currency_id">Currency</Label>
            <Select v-model="form.currency_id" required>
              <SelectTrigger :class="{ 'border-red-500': form.errors.currency_id }">
                <SelectValue placeholder="Select currency" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem 
                  v-for="currency in currencies" 
                  :key="currency.id" 
                  :value="currency.id.toString()"
                >
                  {{ currency.code }} - {{ currency.name }} ({{ currency.symbol }})
                </SelectItem>
              </SelectContent>
            </Select>
            <p v-if="form.errors.currency_id" class="text-sm text-red-600">{{ form.errors.currency_id }}</p>
          </div>

          <!-- Account Type -->
          <div class="space-y-2">
            <Label for="type">Account Type</Label>
            <Select v-model="form.type" required>
              <SelectTrigger :class="{ 'border-red-500': form.errors.type }">
                <SelectValue placeholder="Select account type" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="checking">Checking</SelectItem>
                <SelectItem value="savings">Savings</SelectItem>
                <SelectItem value="credit">Credit</SelectItem>
                <SelectItem value="investment">Investment</SelectItem>
                <SelectItem value="cash">Cash</SelectItem>
                <SelectItem value="other">Other</SelectItem>
              </SelectContent>
            </Select>
            <p v-if="form.errors.type" class="text-sm text-red-600">{{ form.errors.type }}</p>
          </div>

          <!-- Initial Balance (only for create) -->
          <div v-if="!isEditing" class="space-y-2">
            <Label for="initial_balance">Initial Balance</Label>
            <Input
              id="initial_balance"
              v-model="form.initial_balance"
              type="number"
              step="0.01"
              min="0"
              placeholder="0.00"
              :class="{ 'border-red-500': form.errors.initial_balance }"
            />
            <p v-if="form.errors.initial_balance" class="text-sm text-red-600">{{ form.errors.initial_balance }}</p>
          </div>

          <!-- Current Balance (read-only for edit) -->
          <div v-if="isEditing && account" class="space-y-2">
            <Label>Current Balance</Label>
            <div class="p-3 bg-gray-50 dark:bg-gray-700 rounded-md">
              <span class="text-lg font-semibold" :class="account.balance >= 0 ? 'text-green-600' : 'text-red-600'">
                {{ account.currency.symbol }}{{ Number(account.balance).toLocaleString() }}
              </span>
            </div>
            <p class="text-sm text-gray-600 dark:text-gray-400">
              Balance is automatically calculated from transactions
            </p>
          </div>

          <!-- Active Status -->
          <div class="flex items-center space-x-2">
            <Switch
              id="is_active"
              v-model="form.is_active"
            />
            <Label for="is_active">Active Account</Label>
          </div>
        </div>

        <DialogFooter class="flex justify-between sm:justify-between items-center w-full">
          <Button 
            v-if="isEditing" 
            variant="destructive" 
            type="button" 
            @click="confirmDelete"
            :disabled="deleting || form.processing"
            class="transition-all duration-200 hover:scale-105 hover:shadow-lg active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <LoadingSpinner v-if="deleting" class="w-4 h-4 me-2" />
            <Trash2 v-else class="w-4 h-4 me-2" />
            {{ deleting ? t('accounts.deleting') : t('common.delete') }}
          </Button>
          <span v-else></span>
          
          <div class="flex gap-2">
            <Button 
              type="submit" 
              :disabled="form.processing || deleting"
              class="transition-all duration-200 hover:scale-105 hover:shadow-lg active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <LoadingSpinner v-if="form.processing" class="w-4 h-4 me-2" />
              <Save v-else-if="isEditing" class="w-4 h-4 me-2" />
              <Plus v-else class="w-4 h-4 me-2" />
              {{ form.processing 
                ? (isEditing ? t('accounts.updating') : t('accounts.creating'))
                : (isEditing ? t('accounts.update_account') : t('accounts.create_account')) }}
            </Button>
          </div>
        </DialogFooter>
      </form>
    </DialogContent>
  </Dialog>
</template>

<script setup lang="ts">
import { computed, watch, ref } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Switch } from '@/components/ui/switch'
import { Trash2, Save, Plus } from 'lucide-vue-next'
import accountRoutes from '@/routes/accounts'
import LoadingSpinner from '@/components/LoadingSpinner.vue'

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
  balance: number
  initial_balance: number
  is_active: boolean
  type: string
  currency_id: number
  currency: Currency
  created_at: string
  updated_at: string
}

interface Props {
  open: boolean
  currencies: Currency[]
  account?: Account | null
}

interface Emits {
  (e: 'update:open', value: boolean): void
  (e: 'success'): void
}

const props = withDefaults(defineProps<Props>(), {
  account: null
})

const emit = defineEmits<Emits>()

const isOpen = computed({
  get: () => props.open,
  set: (value) => emit('update:open', value)
})

const isEditing = computed(() => !!props.account)
const deleting = ref(false)

const form = useForm({
  name: '',
  currency_id: '',
  type: 'checking',
  initial_balance: '',
  is_active: true,
})

// Watch for account changes to populate form
watch(() => props.account, (account) => {
  if (account) {
    form.name = account.name
    form.currency_id = account.currency_id.toString()
    form.type = account.type
    form.is_active = account.is_active
    form.initial_balance = account.initial_balance.toString()
  } else {
    // Reset form for create
    form.reset()
    form.type = 'checking'
    form.is_active = true
  }
}, { immediate: true })

// Watch for modal open/close to populate or reset form
watch(isOpen, (open) => {
  if (open && props.account) {
    // Populate form when modal opens for editing
    form.name = props.account.name
    form.currency_id = props.account.currency_id.toString()
    form.type = props.account.type
    form.is_active = props.account.is_active
    form.initial_balance = props.account.initial_balance.toString()
    form.clearErrors()
  } else if (!open) {
    // Reset form when modal closes
    form.reset()
    form.clearErrors()
  }
})

// Debug watcher for is_active changes
watch(() => form.is_active, (newValue, oldValue) => {
  console.log('is_active changed:', { oldValue, newValue })
})

const submit = () => {
  if (isEditing.value && props.account) {
    // Update existing account
    console.log('Updating account with form data:', {
      name: form.name,
      currency_id: form.currency_id,
      type: form.type,
      is_active: form.is_active,
      is_active_type: typeof form.is_active,
      initial_balance: form.initial_balance
    })
    console.log('Full form data object:', form.data())
    form.put(accountRoutes.update(props.account.id).url, {
      onSuccess: () => {
        closeModal()
        emit('success')
      }
    })
  } else {
    // Create new account
    console.log('Creating account with form data:', {
      name: form.name,
      currency_id: form.currency_id,
      type: form.type,
      is_active: form.is_active,
      is_active_type: typeof form.is_active,
      initial_balance: form.initial_balance
    })
    console.log('Full form data object:', form.data())
    form.post(accountRoutes.store().url, {
      onSuccess: () => {
        closeModal()
        emit('success')
      }
    })
  }
}

const closeModal = () => {
  isOpen.value = false
}

const confirmDelete = () => {
  if (!props.account) return
  
  const confirmMessage = t('accounts.confirm_delete_with_name', { name: props.account.name })
  
  if (confirm(confirmMessage)) {
    deleting.value = true
    
    router.delete(accountRoutes.destroy(props.account.id).url, {
      onSuccess: () => {
        closeModal()
        emit('success')
      },
      onError: (errors) => {
        console.error('Delete failed:', errors)
        // Handle error - could show a toast or alert
      },
      onFinish: () => {
        deleting.value = false
      }
    })
  }
}
</script>