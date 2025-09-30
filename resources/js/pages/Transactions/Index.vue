<template>
  <AppLayout :title="t('transactions.title')">
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
        {{ t('transactions.title') }}
      </h2>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 text-gray-900 dark:text-gray-100">
            <!-- Header with Create Button -->
            <div class="flex justify-between items-center mb-6">
              <h3 class="text-lg font-medium">{{ t('transactions.title') }}</h3>
              <Button as-child>
                <Link :href="transactionRoutes.create().url" class="inline-flex items-center">
                  <Plus class="w-4 h-4 mr-2" />
                  {{ t('transactions.add_transaction') }}
                </Link>
              </Button>
            </div>

            <!-- Filters -->
            <Card class="mb-6">
              <CardContent class="pt-6">
                <div class="grid gap-4 md:grid-cols-3">
                  <div class="space-y-2">
                    <Label for="account_filter">Filter by Account</Label>
                    <Select v-model="filterForm.account_id" @update:model-value="applyFilters">
                      <SelectTrigger>
                        <SelectValue placeholder="All accounts" />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="all">All accounts</SelectItem>
                        <SelectItem 
                          v-for="account in accounts" 
                          :key="account.id" 
                          :value="account.id.toString()"
                        >
                          {{ account.name }}
                        </SelectItem>
                      </SelectContent>
                    </Select>
                  </div>
                  <div class="space-y-2">
                    <Label for="type_filter">Filter by Type</Label>
                    <Select v-model="filterForm.type" @update:model-value="applyFilters">
                      <SelectTrigger>
                        <SelectValue placeholder="All types" />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="all">All types</SelectItem>
                        <SelectItem value="income">Income</SelectItem>
                        <SelectItem value="expense">Expense</SelectItem>
                        <SelectItem value="transfer">Transfer</SelectItem>
                      </SelectContent>
                    </Select>
                  </div>
                  <div class="flex items-end">
                    <Button variant="outline" @click="clearFilters" class="w-full">
                      Clear Filters
                    </Button>
                  </div>
                </div>
              </CardContent>
            </Card>

            <!-- Transactions List -->
            <div v-if="transactions.data.length > 0" class="space-y-4">
              <Card v-for="transaction in transactions.data" :key="transaction.id" class="hover:shadow-md transition-shadow">
                <CardContent class="pt-6">
                  <div class="flex items-center justify-between">
                    <div class="flex-1">
                      <div class="flex items-center space-x-3 mb-2">
                        <Badge 
                          :variant="transaction.type === 'income' ? 'default' : transaction.type === 'expense' ? 'destructive' : 'secondary'"
                        >
                          {{ transaction.type }}
                        </Badge>
                        <span class="font-medium">{{ transaction.description || 'No description' }}</span>
                      </div>
                      <div class="flex items-center space-x-4 text-sm text-gray-600 dark:text-gray-400">
                        <span>{{ transaction.account.name }}</span>
                        <span>•</span>
                        <span>{{ new Date(transaction.transaction_date).toLocaleDateString() }}</span>
                        <span v-if="transaction.transfer_to_account">
                          → {{ transaction.transfer_to_account.name }}
                        </span>
                      </div>
                    </div>
                    <div class="flex items-center space-x-4">
                      <div class="text-right">
                        <div 
                          class="text-lg font-semibold"
                          :class="transaction.type === 'income' ? 'text-green-600' : 'text-red-600'"
                        >
                          {{ transaction.type === 'income' ? '+' : '-' }}{{ transaction.account.currency.symbol }}{{ Number(transaction.amount).toLocaleString() }}
                        </div>
                      </div>
                      <div class="flex space-x-2">
                        <Button variant="ghost" size="sm" as-child>
                          <Link :href="transactionRoutes.show({ transaction: transaction.id }).url">
                            <Eye class="w-4 h-4" />
                          </Link>
                        </Button>
                        <Button variant="ghost" size="sm" as-child>
                          <Link :href="transactionRoutes.edit({ transaction: transaction.id }).url">
                            <Edit class="w-4 h-4" />
                          </Link>
                        </Button>
                        <Button 
                          variant="ghost" 
                          size="sm" 
                          @click="deleteTransaction(transaction)"
                          class="text-red-600 hover:text-red-700"
                        >
                          <Trash2 class="w-4 h-4" />
                        </Button>
                      </div>
                    </div>
                  </div>
                </CardContent>
              </Card>

              <!-- Pagination -->
              <div v-if="transactions.last_page > 1" class="flex justify-center mt-6">
                <div class="flex space-x-2">
                  <Button 
                    v-for="page in paginationPages" 
                    :key="page"
                    :variant="page === transactions.current_page ? 'default' : 'outline'"
                    size="sm"
                    @click="goToPage(page)"
                    :disabled="page === '...'"
                  >
                    {{ page }}
                  </Button>
                </div>
              </div>
            </div>

            <!-- Empty State -->
            <div v-else class="text-center py-12">
              <div class="mx-auto w-24 h-24 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mb-4">
                <Receipt class="w-12 h-12 text-gray-400" />
              </div>
              <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">No transactions found</h3>
              <p class="text-gray-600 dark:text-gray-400 mb-6">
                {{ Object.values(filters).some(f => f) ? 'Try adjusting your filters or create a new transaction.' : 'Get started by adding your first transaction.' }}
              </p>
              <Button as-child>
                <Link :href="transactionRoutes.create().url">
                  <Plus class="w-4 h-4 mr-2" />
                  {{ t('transactions.add_transaction') }}
                </Link>
              </Button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { computed, reactive } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import AppLayout from '@/layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Label } from '@/components/ui/label'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Plus, Eye, Edit, Trash2, Receipt } from 'lucide-vue-next'
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
  type: string
  amount: number
  description: string
  transaction_date: string
  account: Account
  transfer_to_account?: Account
}

interface PaginatedTransactions {
  data: Transaction[]
  current_page: number
  last_page: number
  per_page: number
  total: number
}

const props = defineProps<{
  transactions: PaginatedTransactions
  accounts: Account[]
  filters: {
    account_id?: string
    type?: string
  }
}>()

const filterForm = reactive({
  account_id: props.filters.account_id || 'all',
  type: props.filters.type || 'all',
})

const paginationPages = computed(() => {
  const pages = []
  const current = props.transactions.current_page
  const last = props.transactions.last_page
  
  if (last <= 7) {
    for (let i = 1; i <= last; i++) {
      pages.push(i)
    }
  } else {
    pages.push(1)
    if (current > 4) pages.push('...')
    
    const start = Math.max(2, current - 1)
    const end = Math.min(last - 1, current + 1)
    
    for (let i = start; i <= end; i++) {
      pages.push(i)
    }
    
    if (current < last - 3) pages.push('...')
    pages.push(last)
  }
  
  return pages
})

const applyFilters = () => {
  const filters = {
    account_id: filterForm.account_id === 'all' ? '' : filterForm.account_id,
    type: filterForm.type === 'all' ? '' : filterForm.type,
  }
  router.get(transactionRoutes.index().url, filters, {
    preserveState: true,
    replace: true,
  })
}

const clearFilters = () => {
  filterForm.account_id = 'all'
  filterForm.type = 'all'
  router.get(transactionRoutes.index().url, {}, {
    preserveState: true,
    replace: true,
  })
}

const goToPage = (page: number | string) => {
  if (page === '...' || page === props.transactions.current_page) return
  
  const filters = {
    account_id: filterForm.account_id === 'all' ? '' : filterForm.account_id,
    type: filterForm.type === 'all' ? '' : filterForm.type,
    page,
  }
  router.get(transactionRoutes.index().url, filters, {
    preserveState: true,
    replace: true,
  })
}

const deleteTransaction = (transaction: Transaction) => {
  if (confirm(t('common.confirm_delete', { name: transaction.description || 'this transaction' }))) {
    router.delete(transactionRoutes.destroy({ transaction: transaction.id }).url)
  }
}
</script>