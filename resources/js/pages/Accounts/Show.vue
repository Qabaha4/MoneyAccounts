<template>
  <AppLayout :title="`${t('accounts.title')}: ${account.name}`">
    <template #header>
      <!-- Error Alert -->
      <Alert v-if="error" variant="destructive" class="mb-6">
        <AlertCircle class="h-4 w-4" />
        <AlertDescription>{{ error }}</AlertDescription>
      </Alert>

      <div class="flex items-center justify-between">
        <div v-if="isLoading">
          <Skeleton class="h-8 w-48" />
        </div>
        <h2 v-else class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
          {{ account.name }}
        </h2>
        <div class="flex items-center space-x-2">
          <Badge :variant="account.is_active ? 'default' : 'secondary'">
            {{ account.is_active ? t('accounts.active') : t('accounts.inactive') }}
          </Badge>
          <Button variant="outline" size="sm" as-child>
            <Link :href="accounts.edit({ account: account.id }).url">
              <Edit class="w-4 h-4 mr-1" />
              {{ t('accounts.edit') }}
            </Link>
          </Button>
          <Button variant="destructive" size="sm" @click="confirmDelete" :disabled="isLoading">
            <Trash class="w-4 h-4 mr-1" />
            {{ t('accounts.delete') }}
          </Button>
        </div>
      </div>
    </template>

    <div class="py-12">
      <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <!-- Account Summary -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <!-- Main Account Info -->
          <Card class="lg:col-span-2">
            <CardHeader>
              <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <CardTitle>
                  <div v-if="isLoading">
                    <Skeleton class="h-7 w-48" />
                  </div>
                  <span v-else>{{ t('accounts.account_summary') }}</span>
                </CardTitle>
                <div v-if="isLoading">
                  <Skeleton class="h-9 w-20" />
                </div>
                <Button v-else variant="outline" size="sm" as-child>
                  <Link :href="accounts.edit({ account: account.id }).url">
                    <Edit class="w-4 h-4 mr-1" />
                    {{ t('accounts.edit') }}
                  </Link>
                </Button>
              </div>
            </CardHeader>
            <CardContent>
              <div v-if="isLoading" class="space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                  <Skeleton class="h-5 w-32" />
                  <Skeleton class="h-8 w-24" />
                </div>
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                  <Skeleton class="h-5 w-28" />
                  <Skeleton class="h-6 w-16" />
                </div>
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                  <Skeleton class="h-5 w-24" />
                  <Skeleton class="h-6 w-20" />
                </div>
              </div>
              <div v-else class="space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                  <span class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ t('accounts.current_balance') }}</span>
                  <span class="text-2xl font-bold" :class="account.balance >= 0 ? 'text-green-600' : 'text-red-600'">
                    {{ account.currency.symbol }}{{ Number(account.balance).toLocaleString() }}
                  </span>
                </div>
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                  <span class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ t('accounts.currency') }}</span>
                  <span class="text-sm text-gray-900 dark:text-gray-100">{{ account.currency.code }} - {{ account.currency.name }}</span>
                </div>
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                  <span class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ t('accounts.initial_balance') }}</span>
                  <span class="text-sm text-gray-900 dark:text-gray-100">
                    {{ account.currency.symbol }}{{ Number(account.initial_balance).toLocaleString() }}
                  </span>
                </div>
              </div>
            </CardContent>
          </Card>

          <!-- Account Description -->
          <Card>
            <CardHeader>
              <CardTitle class="text-lg">
                <div v-if="isLoading">
                  <Skeleton class="h-6 w-24" />
                </div>
                <span v-else>{{ t('accounts.description') }}</span>
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div v-if="isLoading">
                <Skeleton class="h-16 w-full" />
              </div>
              <div v-else-if="account.description" class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed">
                {{ account.description }}
              </div>
              <div v-else class="text-sm text-gray-500 dark:text-gray-400 italic">
                {{ t('transactions.no_description') }}
              </div>
            </CardContent>
          </Card>
        </div>

        <!-- Recent Transactions -->
        <Card>
          <CardHeader>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
              <CardTitle>
                <div v-if="isLoading">
                  <Skeleton class="h-6 w-40" />
                </div>
                <span v-else>{{ t('transactions.recent_transactions') }}</span>
              </CardTitle>
              <div class="flex flex-col sm:flex-row gap-2">
                <div v-if="isLoading">
                  <Skeleton class="h-9 w-24" />
                </div>
                <Button v-else size="sm" as-child>
                  <Link :href="transactions.create().url + `?account_id=${account.id}`">
                    <Plus class="w-4 h-4 mr-1" />
                    {{ t('transactions.add_transaction') }}
                  </Link>
                </Button>
                <div v-if="isLoading">
                  <Skeleton class="h-9 w-24" />
                </div>
                <Button v-else variant="outline" size="sm" as-child>
                  <Link :href="transactions.index().url + `?account_id=${account.id}`">
                    {{ t('transactions.view_all') }}
                  </Link>
                </Button>
              </div>
            </div>
          </CardHeader>
          <CardContent>
            <div v-if="isLoading" class="space-y-4">
              <div v-for="i in 3" :key="i" class="flex flex-col sm:flex-row sm:items-center sm:justify-between p-4 border rounded-lg gap-3">
                <div class="flex items-center space-x-3 flex-1">
                  <Skeleton class="h-8 w-8 rounded-full" />
                  <div class="space-y-2 flex-1">
                    <Skeleton class="h-4 w-32" />
                    <Skeleton class="h-3 w-24" />
                  </div>
                </div>
                <div class="text-right space-y-2">
                  <Skeleton class="h-5 w-20" />
                  <Skeleton class="h-4 w-16" />
                </div>
              </div>
            </div>
            <div v-else-if="account.transactions && account.transactions.length > 0" class="space-y-4">
              <div 
                v-for="transaction in account.transactions" 
                :key="transaction.id"
                class="flex flex-col sm:flex-row sm:items-center sm:justify-between p-4 border rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors gap-3"
              >
                <div class="flex items-center space-x-3 flex-1">
                  <div 
                    class="w-10 h-10 rounded-full flex items-center justify-center text-white text-sm font-medium flex-shrink-0"
                    :class="getTransactionVariant(transaction.type) === 'default' ? 'bg-green-500' : getTransactionVariant(transaction.type) === 'destructive' ? 'bg-red-500' : getTransactionVariant(transaction.type) === 'secondary' ? 'bg-blue-500' : 'bg-gray-500'"
                  >
                    {{ getAmountPrefix(transaction.type) }}
                  </div>
                  <div class="flex-1 min-w-0">
                    <p class="font-medium text-gray-900 dark:text-gray-100 truncate">
                      {{ transaction.description || t('transactions.no_description') }}
                    </p>
                    <div class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-2">
                      <p class="text-sm text-gray-600 dark:text-gray-400">
                        {{ formatDate(transaction.transaction_date) }}
                      </p>
                      <span v-if="transaction.type === 'transfer' && transaction.transfer_to_account" class="text-sm text-gray-600 dark:text-gray-400">
                        <span class="hidden sm:inline">•</span>
                        <span class="sm:ml-1">→ {{ transaction.transfer_to_account.name }}</span>
                      </span>
                    </div>
                  </div>
                </div>
                <div class="text-right sm:text-right flex-shrink-0">
                  <p 
                    class="font-semibold text-lg"
                    :class="getAmountColor(transaction.type)"
                  >
                    {{ getAmountPrefix(transaction.type) }}{{ account.currency.symbol }}{{ Number(transaction.amount).toLocaleString() }}
                  </p>
                  <p class="text-sm text-gray-600 dark:text-gray-400 capitalize">
                    {{ t(`transactions.${transaction.type}`) }}
                  </p>
                </div>
              </div>
            </div>
            <div v-else class="text-center py-8">
              <div class="mx-auto w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mb-4">
                <Receipt class="w-8 h-8 text-gray-400" />
              </div>
              <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">{{ t('transactions.no_transactions') }}</h3>
              <p class="text-gray-600 dark:text-gray-400 mb-4">{{ t('transactions.start_adding') }}</p>
              <Button as-child>
                <Link :href="transactions.create().url + `?account_id=${account.id}`">
                  <Plus class="w-4 h-4 mr-2" />
                  {{ t('transactions.add_transaction') }}
                </Link>
              </Button>
            </div>
          </CardContent>
        </Card>

        <!-- Actions -->
        <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
          <Button variant="outline" as-child>
            <Link :href="accounts.index().url">
              <ArrowLeft class="w-4 h-4 mr-2" />
              {{ t('accounts.back_to_accounts') }}
            </Link>
          </Button>
          <div class="flex flex-col sm:flex-row gap-2">
            <Button variant="outline" as-child>
              <Link :href="transactions.index().url + `?account_id=${account.id}`">
                {{ t('transactions.view_all') }}
                <ArrowRight class="w-4 h-4 ml-2" />
              </Link>
            </Button>
            <Button variant="destructive" size="sm" @click="confirmDelete" :disabled="isLoading">
              <Trash class="w-4 h-4 mr-2" />
              {{ isLoading ? t('accounts.deleting') : t('accounts.delete') }}
            </Button>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import AppLayout from '@/layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Label } from '@/components/ui/label'
import { Skeleton } from '@/components/ui/skeleton'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { Plus, Eye, Edit, ArrowLeft, ArrowRight, Receipt, Trash, AlertCircle } from 'lucide-vue-next'
import accounts from '@/routes/accounts'
import transactions from '@/routes/transactions'

interface Currency {
  id: number
  code: string
  symbol: string
  name: string
}

interface Transaction {
  id: number
  type: string
  amount: number
  description: string
  transaction_date: string
  transfer_to_account?: Account
}

interface Account {
  id: number
  name: string
  balance: number
  initial_balance: number
  is_active: boolean
  currency: Currency
  transactions?: Transaction[]
  description?: string
  created_at: string
  updated_at: string
}

const props = defineProps<{
  account: Account
}>()

const { t } = useI18n()

// Loading and error states
const isLoading = ref(false)
const error = ref<string | null>(null)

// Handle potential data loading errors
const handleError = (errorMessage: string) => {
  error.value = errorMessage
  console.error('Account Show Error:', errorMessage)
}

// Simulate loading state for dynamic operations
const setLoading = (loading: boolean) => {
  isLoading.value = loading
}

// Helper functions for transaction display
const getTransactionVariant = (type: string) => {
  switch (type) {
    case 'income':
      return 'default'
    case 'expense':
      return 'destructive'
    case 'transfer':
      return 'secondary'
    default:
      return 'outline'
  }
}

const getAmountColor = (type: string) => {
  switch (type) {
    case 'income':
      return 'text-green-600 dark:text-green-400'
    case 'expense':
      return 'text-red-600 dark:text-red-400'
    case 'transfer':
      return 'text-blue-600 dark:text-blue-400'
    default:
      return 'text-gray-600 dark:text-gray-400'
  }
}

const getAmountPrefix = (type: string) => {
  switch (type) {
    case 'income':
      return '+'
    case 'expense':
      return '-'
    case 'transfer':
      return '→'
    default:
      return ''
  }
}

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString()
}

const confirmDelete = () => {
  const confirmMessage = t('accounts.confirm_delete_with_name', { name: props.account.name })
  
  if (confirm(confirmMessage)) {
    setLoading(true)
    error.value = null
    
    router.delete(accounts.destroy({ account: props.account.id }).url, {
      onSuccess: () => {
        // Success message will be handled by the backend flash message
        // Redirect to accounts index after successful deletion
      },
      onError: (errors) => {
        console.error('Delete failed:', errors)
        
        // Check if the error is due to existing transactions
        if (errors.message && errors.message.includes('transactions')) {
          error.value = t('accounts.delete_error_has_transactions')
        } else {
          error.value = t('accounts.delete_error')
        }
        
        setLoading(false)
      },
      onFinish: () => {
        setLoading(false)
      }
    })
  }
}

// Add error handling for data validation
onMounted(() => {
  try {
    // Validate account data
    if (!props.account) {
      handleError('Account data not found')
      return
    }
    
    if (!props.account.currency) {
      handleError('Account currency information is missing')
      return
    }
    
    // Clear any previous errors
    error.value = null
  } catch (err) {
    handleError(err instanceof Error ? err.message : 'Failed to load account data')
  }
})
</script>