<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <template #header>
      <!-- Error Alert -->
      <Alert v-if="error" variant="destructive" class="mb-6">
        <AlertCircle class="h-4 w-4" />
        <AlertDescription>{{ error }}</AlertDescription>
      </Alert>

      <div class="flex items-center justify-between gap-2 py-0.5">
        <div v-if="loading">
          <Skeleton class="h-8 w-48" />
        </div>
        <h2 v-else class="font-semibold text-base sm:text-lg text-gray-900 dark:text-gray-100 truncate">
          {{ account.name }}
        </h2>
        <div class="flex items-center gap-1">
          <Badge 
            :variant="account.is_active ? 'outline' : 'secondary'" 
            :class="account.is_active ? 'bg-blue-100 text-blue-700 border-blue-200 dark:bg-blue-900/30 dark:text-blue-300 dark:border-blue-700' : ''"
            class="px-2 py-0.5 text-xs"
          >
            {{ account.is_active ? t('accounts.active') : t('accounts.inactive') }}
          </Badge>
          <Button size="sm" variant="ghost" @click="openAccountEditModal" class="h-7 w-7 p-0 text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20">
            <Edit class="w-4 h-4" />
          </Button>
        </div>
      </div>
    </template>

    <div class="py-4 sm:py-6">
      <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        <!-- Hero Section -->
        <HeroSection 
          :main-sec-val="`${Number(totalBalance).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} ${account.currency.symbol}`"
          :main-sec-label="'Total Balance'"
          :sub-sec-p1-val="account.name"
          :sub-sec-p1-label="'Account Name'"
          :sub-sec-p2-val="`${monthlyTransactionsCount}`"
          :sub-sec-p2-label="'Transactions This Month'"
          :sub-sec-p3-val="recentTransactionsForHero.length.toString()"
          :sub-sec-p3-label="'Recent Activity'"
          :show-status="true"
          :status-val="account.is_active ? 'Active' : 'Inactive'"
          :show-edit-button="true"
          @edit="openAccountEditModal"
        />

        <!-- Account Description (if exists) -->
        <Card v-if="account.description" class="border-l-4 border-l-blue-500">
          <CardContent class="p-4 sm:p-6">
            <div class="flex items-start gap-3">
              <div class="w-10 h-10 rounded-xl bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center flex-shrink-0">
                <Receipt class="w-5 h-5 text-blue-600 dark:text-blue-400" />
              </div>
              <div>
                <div class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">
                  {{ t('accounts.description') }}
                </div>
                <p class="text-sm text-slate-700 dark:text-slate-300 leading-relaxed">
                  {{ account.description }}
                </p>
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- Recent Transactions -->
        <Card>
          <CardContent class="p-4 sm:p-6">
            <div class="flex items-center justify-between mb-6">
              <h3 class="text-lg font-bold text-slate-900 dark:text-slate-100">
                {{ t('transactions.recent_transactions') }}
              </h3>
              <div class="flex items-center gap-2">
                <Button variant="ghost" size="sm" @click="showAllTransactions" class="h-8" title="View all transactions">
                  <Receipt class="w-4 h-4" />
                </Button>
                <Button variant="ghost" size="sm" @click="openCreateModal" class="h-8">
                  <Plus class="w-4 h-4" />
                </Button>
              </div>
            </div>

            <!-- Search Bar -->
            <div class="relative mb-6">
              <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 h-4 w-4" />
              <Input
                v-model="searchQuery"
                type="text"
                placeholder="Search transactions..."
                class="pl-10"
              />
            </div>

            <div v-if="loading" class="space-y-3">
              <div v-for="i in 3" :key="i" class="flex items-center gap-3 p-3 border rounded-xl">
                <Skeleton class="h-12 w-12 rounded-xl" />
                <div class="flex-1 space-y-2">
                  <Skeleton class="h-4 w-32" />
                  <Skeleton class="h-3 w-24" />
                </div>
                <Skeleton class="h-5 w-20" />
              </div>
            </div>

            <div v-else-if="filteredTransactions.length > 0" class="space-y-2">
              <div 
                v-for="transaction in filteredTransactions" 
                :key="transaction.id"
                class="group flex items-center gap-3 p-3 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-all cursor-pointer border-l-4"
                :class="{
                  'border-l-emerald-500': transaction.type === 'income',
                  'border-l-rose-500': transaction.type === 'expense',
                  'border-l-blue-500': transaction.type === 'transfer'
                }"
                @click="openEditModal(transaction)"
              >
                <!-- Icon -->
                <div 
                  class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl flex items-center justify-center flex-shrink-0"
                  :class="{
                    'bg-emerald-100 dark:bg-emerald-900/30': transaction.type === 'income',
                    'bg-rose-100 dark:bg-rose-900/30': transaction.type === 'expense',
                    'bg-blue-100 dark:bg-blue-900/30': transaction.type === 'transfer'
                  }"
                >
                  <span 
                    class="text-lg font-bold"
                    :class="{
                      'text-emerald-600 dark:text-emerald-400': transaction.type === 'income',
                      'text-rose-600 dark:text-rose-400': transaction.type === 'expense',
                      'text-blue-600 dark:text-blue-400': transaction.type === 'transfer'
                    }"
                  >
                    {{ getAmountPrefix(transaction.type) }}
                  </span>
                </div>

                <!-- Info -->
                <div class="flex-1 min-w-0">
                  <h4 class="font-semibold text-sm text-slate-900 dark:text-slate-100 truncate mb-1">
                    {{ transaction.description || t('transactions.no_description') }}
                  </h4>
                  <div class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
                    <span>{{ formatDate(transaction.transaction_date) }}</span>
                    <span v-if="transaction.type === 'transfer'">
                      <template v-if="transaction.is_incoming_transfer">
                        ← from 
                        <button 
                          @click.stop="navigateToAccount(transaction.account.id)"
                          class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 underline font-medium"
                        >
                          {{ transaction.account.name }}
                        </button>
                      </template>
                      <template v-else-if="transaction.transfer_to_account">
                         → to 
                         <button 
                           @click.stop="navigateToAccount(transaction.transfer_to_account.id)"
                           class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 underline font-medium"
                         >
                           {{ transaction.transfer_to_account.name }}
                         </button>
                       </template>
                    </span>
                  </div>
                </div>

                <!-- Amount -->
                <div class="text-right flex items-center gap-2">
                  <div>
                    <div 
                      class="text-base sm:text-lg font-bold"
                      :class="getAmountColor(transaction.type, transaction.is_incoming_transfer)"
                    >
                      {{ getAmountPrefix(transaction.type, transaction.is_incoming_transfer) }}{{ account.currency.symbol }}{{ getEffectiveAmount(transaction).toLocaleString() }}
                    </div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 capitalize">
                      {{ getTransactionLabel(transaction.type, transaction.is_incoming_transfer) }}
                    </div>
                  </div>
                  <Button 
                    variant="ghost" 
                    size="sm" 
                    class="opacity-0 group-hover:opacity-100 transition-opacity h-8 w-8 p-0"
                  >
                    <Edit class="w-4 h-4" />
                  </Button>
                </div>
              </div>
            </div>

            <div v-else class="text-center py-12">
              <div class="bg-gradient-to-br from-slate-100 to-slate-200 dark:from-slate-800 dark:to-slate-900 rounded-full w-16 h-16 flex items-center justify-center mx-auto mb-4">
                <component :is="searchQuery.trim() ? Search : Receipt" class="w-8 h-8 text-slate-400" />
              </div>
              <h3 class="text-lg font-bold text-slate-900 dark:text-slate-100 mb-2">
                {{ searchQuery.trim() ? 'No transactions found' : t('transactions.no_transactions') }}
              </h3>
              <p class="text-sm text-slate-600 dark:text-slate-400 mb-4">
                {{ searchQuery.trim() ? `No transactions match "${searchQuery.trim()}". Try a different search term.` : t('transactions.start_adding') }}
              </p>
              <Button v-if="searchQuery.trim()" @click="searchQuery = ''" variant="outline" class="me-2">
                Clear Search
              </Button>
              <Button @click="openCreateModal" class="bg-gradient-to-r from-blue-600 to-blue-700">
                <Plus class="w-4 h-4 me-2" />
                {{ t('transactions.add_transaction') }}
              </Button>
            </div>
          </CardContent>
        </Card>


      </div>
    </div>

    <!-- Transaction Modal -->
    <TransactionModal
      v-model:is-open="isTransactionModalOpen"
      :accounts="props.accounts"
      :transaction="editingTransaction"
      :default-account-id="account.id"
      :allow-account-change="!!editingTransaction"
      @success="handleTransactionSuccess"
    />

    <!-- Account Edit Modal -->
    <AccountFormModal
      v-model:open="isAccountModalOpen"
      :account="props.account"
      :currencies="props.currencies"
      @success="handleAccountSuccess"
    />
  </AppLayout>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import AppLayout from '@/layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Label } from '@/components/ui/label'
import { Skeleton } from '@/components/ui/skeleton'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { Input } from '@/components/ui/input'
import { Plus, Eye, Edit, ArrowLeft, ArrowRight, Receipt, AlertCircle, Search } from 'lucide-vue-next'
import accounts from '@/routes/accounts'
import transactions from '@/routes/transactions'
import TransactionModal from '@/components/TransactionModal.vue'
import AccountFormModal from '@/components/AccountFormModal.vue'
import HeroSection from '@/components/HeroSection.vue'
import { type BreadcrumbItem } from '@/types'

interface Currency {
  id: number
  code: string
  symbol: string
  name: string
  is_active: boolean
  decimal_places: number
}

interface Transaction {
  id: number
  type: 'income' | 'expense' | 'transfer'
  amount: string
  description: string
  transaction_date: string
  account: Account
  transfer_to_account?: Account
  is_incoming_transfer?: boolean
  exchange_rate?: string | null
  converted_amount?: string | null
  exchange_rate_source?: string | null
}

interface Account {
  id: number
  name: string
  type: string
  balance: number
  initial_balance: number
  is_active: boolean
  currency: Currency
  currency_id: number
  transactions?: Transaction[]
  description: string | null
  created_at: string
  updated_at: string
}

const props = defineProps<{
  account: Account
  accounts: Account[]
  currencies: Currency[]
}>()

const { t } = useI18n()

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: t('accounts.title'),
    href: accounts.index().url,
  },
  {
    title: props.account.name,
    href: accounts.show({ account: props.account.id }).url,
  },
]

const loading = ref(false)
const error = ref<string | null>(null)
const isTransactionModalOpen = ref(false)
const editingTransaction = ref<Transaction | null>(null)
const isAccountModalOpen = ref(false)
const searchQuery = ref('')

// Computed properties for HeroSection component
const totalBalance = computed(() => props.account.balance)
const accountsForHero = computed(() => [props.account])
const recentTransactionsForHero = computed(() => props.account.transactions?.slice(0, 3) || [])

// Filtered transactions based on search query
const filteredTransactions = computed(() => {
  if (!props.account.transactions) return []
  
  if (!searchQuery.value.trim()) {
    return props.account.transactions
  }
  
  const query = searchQuery.value.toLowerCase().trim()
  return props.account.transactions.filter(transaction => {
    return (
      transaction.description?.toLowerCase().includes(query) ||
      transaction.type.toLowerCase().includes(query) ||
      transaction.amount.toString().includes(query) ||
      transaction.transaction_date.includes(query) ||
      (transaction.transfer_to_account?.name?.toLowerCase().includes(query)) ||
      (transaction.account?.name?.toLowerCase().includes(query))
    )
  })
})

// Calculate transactions for current month
const monthlyTransactionsCount = computed(() => {
  if (!props.account.transactions) return 0
  
  const currentDate = new Date()
  const currentMonth = currentDate.getMonth()
  const currentYear = currentDate.getFullYear()
  
  return props.account.transactions.filter(transaction => {
    const transactionDate = new Date(transaction.transaction_date)
    return transactionDate.getMonth() === currentMonth && 
           transactionDate.getFullYear() === currentYear
  }).length
})

const handleError = (errorMessage: string) => {
  error.value = errorMessage
  console.error('Account Show Error:', errorMessage)
}

const setLoading = (loadingState: boolean) => {
  loading.value = loadingState
}

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

const getAmountColor = (type: string, isIncomingTransfer?: boolean) => {
  switch (type) {
    case 'income':
      return 'text-emerald-600 dark:text-emerald-400'
    case 'expense':
      return 'text-rose-600 dark:text-rose-400'
    case 'transfer':
      return isIncomingTransfer 
        ? 'text-emerald-600 dark:text-emerald-400' 
        : 'text-blue-600 dark:text-blue-400'
    default:
      return 'text-gray-600 dark:text-gray-400'
  }
}

const getAmountPrefix = (type: string, isIncomingTransfer?: boolean) => {
  switch (type) {
    case 'income':
      return '+'
    case 'expense':
      return '-'
    case 'transfer':
      return isIncomingTransfer ? '+' : '→'
    default:
      return ''
  }
}

const getTransactionLabel = (type: string, isIncomingTransfer?: boolean) => {
  if (type === 'transfer') {
    return isIncomingTransfer ? t('transactions.transfer_in') : t('transactions.transfer_out')
  }
  return t(`transactions.${type}`)
}

// Get the effective amount for display (converted amount for cross-currency incoming transfers)
const getEffectiveAmount = (transaction: Transaction) => {
  if (transaction.type === 'transfer' && transaction.is_incoming_transfer && transaction.converted_amount) {
    return Number(transaction.converted_amount)
  }
  return Number(transaction.amount)
}

const formatDate = (dateString: string) => {
  const date = new Date(dateString)
  const dateStr = date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
  const timeStr = date.toLocaleTimeString('en-US', {
    hour: '2-digit',
    minute: '2-digit'
  })
  return `${dateStr} ${timeStr}`
}

onMounted(() => {
  try {
    if (!props.account) {
      handleError('Account data not found')
      return
    }
    
    if (!props.account.currency) {
      handleError('Account currency information is missing')
      return
    }
    
    error.value = null
  } catch (err) {
    handleError(err instanceof Error ? err.message : 'Failed to load account data')
  }
})

const showAllTransactions = () => {
  router.visit(transactions.index(), {
    data: { account_id: props.account.id }
  })
}

const openCreateModal = () => {
  editingTransaction.value = null
  isTransactionModalOpen.value = true
}

const openEditModal = (transaction: Transaction) => {
  editingTransaction.value = transaction
  isTransactionModalOpen.value = true
}

const handleTransactionSuccess = () => {
  router.reload({ only: ['account'] })
}

const openAccountEditModal = () => {
  isAccountModalOpen.value = true
}

const handleAccountSuccess = () => {
  router.reload({ only: ['account'] })
}

const navigateToAccount = (accountId: number) => {
  router.visit(accounts.show({ account: accountId }).url)
}
</script>

<style scoped>
.bg-grid-white\/\[0\.02\] {
  background-image: linear-gradient(to right, rgba(255, 255, 255, 0.02) 1px, transparent 1px),
    linear-gradient(to bottom, rgba(255, 255, 255, 0.02) 1px, transparent 1px);
}
</style>