<template>
  <AppLayout title="Edit Account">
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
        Edit Account: {{ account.name }}
      </h2>
    </template>

    <div class="py-12">
      <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 text-gray-900 dark:text-gray-100">
            <form @submit.prevent="submit" class="space-y-6">
              <div class="grid gap-6">
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

                <!-- Current Balance (Read-only) -->
                <div class="space-y-2">
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
                    v-model:checked="form.is_active"
                  />
                  <Label for="is_active">Active Account</Label>
                </div>
              </div>

              <!-- Form Actions -->
              <div class="flex items-center justify-end space-x-4 pt-6 border-t">
                <Button variant="outline" type="button" as-child>
                  <Link :href="accounts.index().url">Cancel</Link>
                </Button>
                <Button type="submit" :disabled="form.processing">
                  <span v-if="form.processing">Updating...</span>
                  <span v-else>Update Account</span>
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
import AppLayout from '@/layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import Select from '@/components/ui/select/Select.vue'
import SelectContent from '@/components/ui/select/SelectContent.vue'
import SelectItem from '@/components/ui/select/SelectItem.vue'
import SelectTrigger from '@/components/ui/select/SelectTrigger.vue'
import SelectValue from '@/components/ui/select/SelectValue.vue'
import Switch from '@/components/ui/switch/Switch.vue'
import accounts from '@/routes/accounts'

interface Currency {
  id: number
  code: string
  symbol: string
  name: string
}

interface Account {
  id: number
  name: string
  type: string
  balance: number
  initial_balance: number
  is_active: boolean
  currency: Currency
  created_at: string
  updated_at: string
}

const props = defineProps<{
  account: Account
  currencies: Currency[]
}>()

const form = useForm({
  name: props.account.name,
  currency_id: props.account.currency.id.toString(),
  type: props.account.type,
  is_active: props.account.is_active,
})

const submit = () => {
  form.put(accounts.update(props.account.id).url)
}
</script>