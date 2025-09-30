<template>
  <AppLayout :title="t('transactions.edit_transaction')">
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
        {{ t('transactions.edit_transaction') }}
      </h2>
    </template>

    <div class="py-12">
      <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 text-gray-900 dark:text-gray-100">
            <form @submit.prevent="submit" class="space-y-6">
              <div class="grid gap-6">
                <!-- Account -->
                <div class="space-y-2">
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
                        {{ account.name }} ({{ account.currency.code }})
                      </SelectItem>
                    </SelectContent>
                  </Select>
                  <p v-if="form.errors.account_id" class="text-sm text-red-600">{{ form.errors.account_id }}</p>
                </div>

                <!-- Transaction Type -->
                <div class="space-y-2">
                  <Label for="type">{{ t('transactions.transaction_type') }}</Label>
                  <Select v-model="form.type" required>
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
                  <Select v-model="form.transfer_to_account_id" required>
                    <SelectTrigger :class="{ 'border-red-500': form.errors.transfer_to_account_id }">
                      <SelectValue :placeholder="t('transactions.select_destination_account')" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem 
                        v-for="account in availableTransferAccounts" 
                        :key="account.id" 
                        :value="account.id.toString()"
                      >
                        {{ account.name }} ({{ account.currency.code }})
                      </SelectItem>
                    </SelectContent>
                  </Select>
                  <p v-if="form.errors.transfer_to_account_id" class="text-sm text-red-600">{{ form.errors.transfer_to_account_id }}</p>
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
                    :class="{ 'border-red-500': form.errors.amount }"
                    required
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
                  <Input
                    id="transaction_date"
                    v-model="form.transaction_date"
                    type="date"
                    :class="{ 'border-red-500': form.errors.transaction_date }"
                    required
                  />
                  <p v-if="form.errors.transaction_date" class="text-sm text-red-600">{{ form.errors.transaction_date }}</p>
                </div>
              </div>

              <!-- Form Actions -->
              <div class="flex items-center justify-end space-x-4">
                <Button variant="outline" @click="$inertia.visit(transactionRoutes.index().url)">
                  {{ t('common.cancel') }}
                </Button>
                <Button type="submit" :disabled="form.processing">
                  <span v-if="form.processing">{{ t('transactions.updating') }}</span>
                  <span v-else>{{ t('transactions.update_transaction') }}</span>
                </Button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { useForm, Link } from '@inertiajs/vue3'
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import AppLayout from '@/layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import Select from '@/components/ui/select/Select.vue'
import SelectContent from '@/components/ui/select/SelectContent.vue'
import SelectItem from '@/components/ui/select/SelectItem.vue'
import SelectTrigger from '@/components/ui/select/SelectTrigger.vue'
import SelectValue from '@/components/ui/select/SelectValue.vue'
import transactions from '@/routes/transactions'
import transactionRoutes from '@/routes/transactions'

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
  account_id: number
  type: string
  amount: string
  description: string
  transaction_date: string
  transfer_to_account_id?: number
}

const props = defineProps<{
  transaction: Transaction
  accounts: Account[]
}>()

const form = useForm({
  account_id: props.transaction.account_id.toString(),
  type: props.transaction.type,
  amount: props.transaction.amount,
  description: props.transaction.description || '',
  transaction_date: props.transaction.transaction_date,
  transfer_to_account_id: props.transaction.transfer_to_account_id?.toString() || '',
})

const availableTransferAccounts = computed(() => {
  return props.accounts.filter(account => account.id.toString() !== form.account_id)
})

const submit = () => {
  form.put(transactionRoutes.update({ transaction: props.transaction.id }).url, {
    onSuccess: () => {
      form.reset();
    },
  });
}
</script>