<template>
  <Dialog v-model:open="isOpen">
    <DialogContent class="sm:max-w-[500px]">
      <DialogHeader>
        <DialogTitle>{{ isEditing ? t('accounts.edit_title') : t('accounts.create_title') }}</DialogTitle>
        <DialogDescription>
          {{ isEditing ? t('accounts.edit_description') : t('accounts.create_description') }}
        </DialogDescription>
      </DialogHeader>

      <form @submit.prevent="submit" class="space-y-4">
        <div class="grid gap-4">
          <!-- Account Name -->
          <div class="space-y-2">
            <Label for="name">{{ t('accounts.name_label') }}</Label>
            <Input
              id="name"
              v-model="form.name"
              type="text"
              :placeholder="t('accounts.name_placeholder')"
              :class="{ 'border-red-500': form.errors.name }"
              required
            />
            <p v-if="form.errors.name" class="text-sm text-red-600">{{ form.errors.name }}</p>
          </div>

          <!-- Currency -->
          <div class="space-y-2">
            <Label for="currency_id">{{ t('accounts.currency_label') }}</Label>
            <Select v-model="form.currency_id" required>
              <SelectTrigger :class="{ 'border-red-500': form.errors.currency_id }">
                <SelectValue :placeholder="t('accounts.currency_placeholder')" />
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
            <Label for="type">{{ t('accounts.type_label') }}</Label>
            <Select v-model="form.type" required>
              <SelectTrigger :class="{ 'border-red-500': form.errors.type }">
                <SelectValue :placeholder="t('accounts.type_placeholder')" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="checking">{{ t('accounts.checking') }}</SelectItem>
                <SelectItem value="savings">{{ t('accounts.savings') }}</SelectItem>
                <SelectItem value="credit">{{ t('accounts.credit') }}</SelectItem>
                <SelectItem value="investment">{{ t('accounts.investment') }}</SelectItem>
                <SelectItem value="cash">{{ t('accounts.cash') }}</SelectItem>
                <SelectItem value="other">{{ t('accounts.other') }}</SelectItem>
              </SelectContent>
            </Select>
            <p v-if="form.errors.type" class="text-sm text-red-600">{{ form.errors.type }}</p>
          </div>

          <!-- Initial Balance (only for create) -->
          <div v-if="!isEditing" class="space-y-2">
            <Label for="initial_balance">{{ t('accounts.initial_balance_label') }}</Label>
            <Input
              id="initial_balance"
              v-model="form.initial_balance"
              type="number"
              step="0.01"
              min="0"
              :placeholder="t('accounts.initial_balance_placeholder')"
              :class="{ 'border-red-500': form.errors.initial_balance }"
            />
            <p v-if="form.errors.initial_balance" class="text-sm text-red-600">{{ form.errors.initial_balance }}</p>
          </div>

          <!-- Current Balance (read-only for edit) -->
          <div v-if="isEditing && account" class="space-y-2">
            <Label>{{ t('accounts.current_balance') }}</Label>
            <div class="p-3 bg-muted rounded-lg border border-border/50">
              <span class="text-lg font-semibold" :class="(account.balance ?? 0) >= 0 ? 'text-success' : 'text-destructive'">
                {{ account.currency.symbol }}{{ formatAmount(account.balance) }}
              </span>
            </div>
            <p class="text-sm text-muted-foreground">
              {{ t('accounts.balance_auto_calc') }}
            </p>
          </div>

          <!-- Active Status -->
          <div dir="ltr" class="flex rtl:justify-end items-center space-x-2 ">
            <Switch
              id="is_active"
              v-model="form.is_active"
            />
            <Label for="is_active">{{ t('accounts.active_account') }}</Label>
          </div>

          <!-- Passcode (edit only) -->
          <div v-if="isEditing" class="space-y-2 pt-4 border-t">
            <Label for="edit-passcode">{{ t('passcode.edit_passcode_label') }}</Label>
            <Input
              id="edit-passcode"
              v-model="form.passcode"
              type="password"
              inputmode="numeric"
              maxlength="6"
              autocomplete="off"
              :disabled="!props.hasPasscode"
              :placeholder="t('passcode.edit_passcode_placeholder')"
            />
            <p v-if="form.errors.passcode" class="text-sm text-red-600">{{ form.errors.passcode }}</p>
          </div>

          <!-- Lock & Hide Toggles (edit only) -->
          <div v-if="isEditing" class="flex flex-col gap-3 pt-4 border-t">
            <div dir="ltr" class="flex rtl:justify-end items-center space-x-2">
              <Switch
                id="is_locked"
                v-model="form.is_locked"
                :disabled="!props.hasPasscode"
              />
              <Label for="is_locked">{{ t('passcode.lock_account') }}</Label>
            </div>
            <div dir="ltr" class="flex rtl:justify-end items-center space-x-2">
              <Switch
                id="hide_balance"
                v-model="form.hide_balance"
                :disabled="!props.hasPasscode"
              />
              <Label for="hide_balance">{{ t('passcode.hide_balance') }}</Label>
            </div>
            <p v-if="isEditing && !props.hasPasscode" class="text-sm text-muted-foreground">
              {{ t('passcode.setup_required') }}
              <Link :href="'/settings/passcode'" class="text-primary underline underline-offset-2">
                {{ t('passcode.settings_link') }}
              </Link>
            </p>
          </div>
        </div>

        <DialogFooter class="flex justify-between sm:justify-between items-center w-full">
          <Button 
            v-if="isEditing" 
            variant="destructive" 
            type="button" 
            @click="confirmDelete"
            :disabled="deleting || form.processing"
            class="disabled:opacity-50 disabled:cursor-not-allowed"
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
              class="disabled:opacity-50 disabled:cursor-not-allowed"
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
    <ConfirmDialog
      :open="showDeleteConfirm"
      :message="t('accounts.confirm_delete_with_name', { name: props.account?.name || '' })"
      @update:open="showDeleteConfirm = $event"
      @confirm="handleDeleteConfirmed"
      @cancel="showDeleteConfirm = false"
    />
  </Dialog>
</template>

<script setup lang="ts">
import { computed, watch, ref } from 'vue'
import { useForm, router, Link } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import { useFormatting } from '@/composables/useFormatting'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Switch } from '@/components/ui/switch'
import ConfirmDialog from '@/components/ConfirmDialog.vue'
import { Trash2, Save, Plus } from 'lucide-vue-next'
import accountRoutes from '@/routes/accounts'
import LoadingSpinner from '@/components/LoadingSpinner.vue'

const { t } = useI18n()
const { formatAmount } = useFormatting()

interface Currency {
  id: number
  code: string
  symbol: string
  name: string
}

interface Account {
  id: string
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
  hasPasscode: boolean
}

interface Emits {
  (e: 'update:open', value: boolean): void
  (e: 'success'): void
}

const props = withDefaults(defineProps<Props>(), {
  account: null,
  hasPasscode: false,
})

const emit = defineEmits<Emits>()

const isOpen = computed({
  get: () => props.open,
  set: (value) => emit('update:open', value)
})

const isEditing = computed(() => !!props.account)
const deleting = ref(false)
const showDeleteConfirm = ref(false)

const form = useForm({
  name: '',
  currency_id: '',
  type: 'checking',
  initial_balance: '',
  is_active: true,
  passcode: '',
  is_locked: false,
  hide_balance: false,
})

// Watch for account changes to populate form
watch(() => props.account, (account) => {
  if (account) {
    form.name = account.name
    form.currency_id = account.currency_id.toString()
    form.type = account.type
    form.is_active = account.is_active
    form.initial_balance = account.initial_balance.toString()
    form.is_locked = (account as any).is_locked ?? false
    form.hide_balance = (account as any).hide_balance ?? false
    form.passcode = ''
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
    form.is_locked = (props.account as any).is_locked ?? false
    form.hide_balance = (props.account as any).hide_balance ?? false
    form.passcode = ''
    form.clearErrors()
  } else if (!open) {
    // Reset form when modal closes
    form.reset()
    form.clearErrors()
  }
})

const submit = () => {
  if (isEditing.value && props.account) {
    form.put(accountRoutes.update(props.account.id).url, {
      onSuccess: () => {
        closeModal()
        emit('success')
      }
    })
  } else {
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
  showDeleteConfirm.value = true
}

const handleDeleteConfirmed = () => {
  if (!props.account) return
  showDeleteConfirm.value = false
  deleting.value = true

  router.delete(accountRoutes.destroy(props.account.id).url, {
    onSuccess: () => {
      closeModal()
      emit('success')
    },
    onError: (errors) => {
      console.error('Delete failed:', errors)
    },
    onFinish: () => {
      deleting.value = false
    }
  })
}
</script>