<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <template #header>
      <div class="flex items-center justify-between gap-2 py-0.5">
        <h2 class="font-semibold text-base sm:text-lg text-foreground truncate">
          {{ account.name }}
        </h2>
        <div class="flex items-center gap-1">
          <Badge 
            variant="secondary"
            class="px-2 py-0.5 text-xs"
          >
            {{ account.is_active ? t('accounts.active') : t('accounts.inactive') }}
          </Badge>
          <Button size="sm" variant="ghost" @click="openPrintReport" class="h-7 w-7 p-0 text-muted-foreground hover:text-foreground hover:bg-accent/50" title="Print Report">
            <Printer class="w-4 h-4" />
          </Button>
          <Button size="sm" variant="ghost" @click="openAccountEditModal" class="h-7 w-7 p-0 text-muted-foreground hover:text-foreground hover:bg-accent/50">
            <Edit class="w-4 h-4" />
          </Button>
        </div>
      </div>
    </template>

    <div class="py-4 sm:py-6">
      <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        <!-- Hero Section -->
        <HeroSection 
          :main-sec-val="`${formatAmount(totalBalance)} ${account.currency.symbol}`"
          :main-sec-label="t('dashboard.total_balance')"
          :sub-sec-p1-val="account.name"
          :sub-sec-p1-label="t('dashboard.account_name')"
          :sub-sec-p2-val="`${monthlyTransactionsCount}`"
          :sub-sec-p2-label="t('dashboard.transactions_this_month')"
          :sub-sec-p3-val="recentTransactionsForHero.length.toString()"
          :sub-sec-p3-label="t('dashboard.recent_activity')"
          :show-status="true"
          :status-val="account.is_active ? t('accounts.active') : t('accounts.inactive')"
          :show-edit-button="true"
          @edit="openAccountEditModal"
        />

        <!-- Account Description (if exists) -->
        <Card v-if="account.description">
          <CardContent class="p-4 sm:p-6">
            <div class="flex items-start gap-3">
              <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0"
                   :class="getAccountTypeStyle(account.type).gradientClass">
                <component :is="getAccountTypeStyle(account.type).icon" class="w-5 h-5 text-white" />
              </div>
              <div>
                <div class="text-xs font-semibold text-muted-foreground uppercase tracking-wider mb-2">
                  {{ t('accounts.description') }}
                </div>
                <p class="text-sm text-foreground/80 leading-relaxed">
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
              <h3 class="text-lg font-bold text-foreground">
                {{ t('transactions.recent_transactions') }}
              </h3>
              <div class="flex items-center gap-2">
                <Button variant="ghost" size="sm" @click="openPrintReport" class="h-8" title="Print Report">
                  <Printer class="w-4 h-4" />
                </Button>
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
              <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 text-muted-foreground h-4 w-4" />
              <Input
                v-model="searchQuery"
                type="text"
                :placeholder="t('transactions.search_placeholder')"
                class="pl-10"
              />
            </div>

            <div v-if="filteredTransactions.length > 0" class="space-y-2">
              <div 
                v-for="transaction in filteredTransactions" 
                :key="transaction.id"
                :data-transaction-id="transaction.id"
                class="group flex items-center gap-3 p-3 rounded-xl hover:bg-accent/30 transition-all cursor-pointer border border-border/50 relative overflow-hidden"
                :class="highlightedTxId === transaction.id ? 'bg-primary/5 highlight-pulse' : ''"
                @click="openViewModal(transaction)"
              >
                <div v-if="highlightedTxId === transaction.id" class="absolute left-0 top-0 bottom-0 w-1 bg-primary highlight-bar" />
                <!-- Icon -->
                <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl bg-secondary/50 flex items-center justify-center flex-shrink-0">
                  <span 
                    class="text-lg font-bold text-muted-foreground"
                  >
                    {{ getAmountPrefix(transaction.type) }}
                  </span>
                </div>

                <!-- Info -->
                <div class="flex-1 min-w-0">
                  <h4 class="font-semibold text-sm text-foreground truncate mb-1">
                    {{ transaction.description || t('transactions.no_description') }}
                  </h4>
                  <div class="flex items-center gap-2 text-xs text-muted-foreground">
                    <span>{{ formatDate(transaction.transaction_date) }}</span>
                    <span v-if="transaction.type === 'transfer'">
                      <template v-if="transaction.is_incoming_transfer">
                        ← from 
                        <button 
                          @click.stop="navigateToAccount(transaction.account.id)"
                          class="text-foreground/70 hover:text-foreground underline font-medium"
                        >
                          {{ transaction.account.name }}
                        </button>
                      </template>
                      <template v-else-if="transaction.transfer_to_account">
                         → to 
                         <button 
                           @click.stop="navigateToAccount(transaction.transfer_to_account.id)"
                           class="text-foreground/70 hover:text-foreground underline font-medium"
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
                      {{ getAmountPrefix(transaction.type, transaction.is_incoming_transfer) }}{{ account.currency.symbol }}{{ formatAmount(getEffectiveAmount(transaction)) }}
                    </div>
                    <div class="text-xs text-muted-foreground capitalize">
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

            <!-- Load More -->
            <div v-if="filteredTransactions.length > 0 && hasMore" class="pt-2">
              <Button
                variant="outline"
                class="w-full"
                :disabled="loadingMore"
                @click="loadMoreTransactions"
              >
                <Loader2 v-if="loadingMore" class="w-4 h-4 me-2 animate-spin" />
                {{ loadingMore ? t('transactions.loading') : t('transactions.view_more') }}
              </Button>
            </div>

            <div v-if="filteredTransactions.length === 0" class="text-center py-12">
              <div class="rounded-full w-16 h-16 bg-secondary/50 flex items-center justify-center mx-auto mb-4">
                <component :is="searchQuery.trim() ? Search : Receipt" class="w-8 h-8 text-muted-foreground" />
              </div>
              <h3 class="text-lg font-bold text-foreground mb-2">
                {{ searchQuery.trim() ? 'No transactions found' : t('transactions.no_transactions') }}
              </h3>
              <p class="text-sm text-muted-foreground mb-4">
                {{ searchQuery.trim() ? `No transactions match "${searchQuery.trim()}". Try a different search term.` : t('transactions.start_adding') }}
              </p>
              <Button v-if="searchQuery.trim()" @click="searchQuery = ''" variant="outline" class="me-2">
                Clear Search
              </Button>
              <Button @click="openCreateModal">
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
      :is-open="isTransactionModalOpen"
      :accounts="props.accounts"
      :transaction="editingTransaction"
      :default-account-id="account.id"
      :allow-account-change="!!editingTransaction"
      @update:is-open="isTransactionModalOpen = $event; handleEditClose($event)"
      @success="handleTransactionSuccess"
    />

    <!-- Transaction View Modal -->
    <TransactionDetailModal
      :is-open="isViewModalOpen"
      :transaction="viewingTransaction"
      hide-actions
      allow-edit
      @update:is-open="isViewModalOpen = $event"
      @edit="handleViewEdit"
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
import { computed, ref, watch, onMounted, nextTick } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import AppLayout from '@/layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Label } from '@/components/ui/label'

import { Input } from '@/components/ui/input'
import { Plus, Eye, Edit, ArrowLeft, ArrowRight, Receipt, Search, Printer, Loader2 } from 'lucide-vue-next'
import accounts from '@/routes/accounts'
import transactions from '@/routes/transactions'
import TransactionModal from '@/components/TransactionModal.vue'
import TransactionDetailModal from '@/components/TransactionDetailModal.vue'
import AccountFormModal from '@/components/AccountFormModal.vue'
import HeroSection from '@/components/HeroSection.vue'
import { type BreadcrumbItem } from '@/types'
import { useFormatting } from '@/composables/useFormatting'
import { useAccountType } from '@/composables/useAccountType'

interface Currency {
  id: number
  code: string
  symbol: string
  name: string
  is_active: boolean
  decimal_places: number
}

interface Transaction {
  id: string
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
  id: string
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
  transactionsMeta?: {
    current_page: number
    per_page: number
    total: number
    last_page: number
  }
}>()

const { t } = useI18n()
const { formatDateTime: fmtDateTime, formatAmount } = useFormatting()
const { getAccountTypeStyle } = useAccountType()

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

const isTransactionModalOpen = ref(false)
const isViewModalOpen = ref(false)
const editingTransaction = ref<Transaction | null>(null)
const viewingTransaction = ref<Transaction | null>(null)
const returningFromView = ref(false)
const isAccountModalOpen = ref(false)
const searchQuery = ref('')
const allTransactions = ref<Transaction[]>([])
const loadingMore = ref(false)
const isAppendingTransactions = ref(false)
const highlightedTxId = ref<string | null>(null)

// Initialize accumulated transactions from server prop
const currentPage = ref(props.transactionsMeta?.current_page ?? 1)
const hasMore = computed(() => props.transactionsMeta ? currentPage.value < props.transactionsMeta.last_page : false)

// Sync transactions whenever props update (form submit, page reload, etc.)
watch(() => props.account?.transactions, (transactions) => {
  if (!transactions) return
  if (isAppendingTransactions.value) {
    isAppendingTransactions.value = false
  } else {
    allTransactions.value = transactions
    currentPage.value = 1
  }
}, { immediate: true })

// Computed properties for HeroSection component
const totalBalance = computed(() => props.account.balance)
const accountsForHero = computed(() => [props.account])
const recentTransactionsForHero = computed(() => allTransactions.value.slice(0, 3))

// Filtered transactions based on search query
const filteredTransactions = computed(() => {
  if (allTransactions.value.length === 0) return []

  if (!searchQuery.value.trim()) {
    return allTransactions.value
  }

  const query = searchQuery.value.toLowerCase().trim()
  return allTransactions.value.filter(transaction => {
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
  if (allTransactions.value.length === 0) return 0

  const currentDate = new Date()
  const currentMonth = currentDate.getMonth()
  const currentYear = currentDate.getFullYear()

  return allTransactions.value.filter(transaction => {
    const transactionDate = new Date(transaction.transaction_date)
    return transactionDate.getMonth() === currentMonth &&
           transactionDate.getFullYear() === currentYear
  }).length
})

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
      return 'text-success'
    case 'expense':
      return 'text-destructive'
    case 'transfer':
      return isIncomingTransfer 
        ? 'text-success' 
        : 'text-muted-foreground'
    default:
      return 'text-muted-foreground'
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
  return fmtDateTime(dateString)
}

const loadMoreTransactions = () => {
  if (loadingMore.value || !hasMore.value) return

  loadingMore.value = true
  isAppendingTransactions.value = true
  const nextPage = currentPage.value + 1

  router.reload({
    data: { page: nextPage },
    only: ['account', 'transactionsMeta'],
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => {
      const newTransactions = props.account.transactions || []
      allTransactions.value = [...allTransactions.value, ...newTransactions]
      currentPage.value = nextPage
      loadingMore.value = false
    },
    onError: () => {
      isAppendingTransactions.value = false
      loadingMore.value = false
    },
  })
}

const showAllTransactions = () => {
  router.visit(transactions.index(), {
    data: { account_id: props.account.id }
  })
}

const openCreateModal = () => {
  editingTransaction.value = null
  returningFromView.value = false
  isTransactionModalOpen.value = true
}

const openViewModal = (transaction: Transaction) => {
  viewingTransaction.value = transaction
  isViewModalOpen.value = true
}

const handleViewEdit = (transaction: any) => {
  isViewModalOpen.value = false
  editingTransaction.value = transaction
  returningFromView.value = true
  nextTick(() => {
    isTransactionModalOpen.value = true
  })
}

const handleEditClose = (open: boolean) => {
  if (!open && returningFromView.value && viewingTransaction.value) {
    returningFromView.value = false
    isViewModalOpen.value = true
  }
}

const openEditModal = (transaction: Transaction) => {
  editingTransaction.value = transaction
  returningFromView.value = false
  isTransactionModalOpen.value = true
}

const handleTransactionSuccess = () => {
  if (returningFromView.value && editingTransaction.value) {
    const updatedTx = props.account?.transactions?.find(
      t => t.id === editingTransaction.value!.id
    )
    if (updatedTx) {
      viewingTransaction.value = updatedTx
    } else {
      returningFromView.value = false
      viewingTransaction.value = null
    }
  }
}

const openAccountEditModal = () => {
  isAccountModalOpen.value = true
}

const handleAccountSuccess = () => {
  // Form submission already redirected and updated props; watch handles sync
}

const navigateToAccount = (accountId: string) => {
  router.visit(accounts.show({ account: accountId }).url)
}

// Parse highlight param and scroll to transaction
const highlightTxId = computed(() => {
  const params = new URLSearchParams(window.location.search)
  const id = params.get('highlight')
  return id || null
})

onMounted(async () => {
  if (!highlightTxId.value) return
  highlightedTxId.value = highlightTxId.value
  await nextTick()
  const el = document.querySelector(`[data-transaction-id="${highlightedTxId.value}"]`)
  if (el) {
    el.scrollIntoView({ behavior: 'smooth', block: 'center' })
    el.classList.add('highlight-active')
    setTimeout(() => {
      el.classList.remove('highlight-active')
      highlightedTxId.value = null
    }, 4000)
  }
})

const openPrintReport = () => {
  router.visit(`/accounts/${props.account.id}/report`)
}
</script>

<style scoped>
.bg-grid-white\/\[0\.02\] {
  background-image: linear-gradient(to right, rgba(255, 255, 255, 0.02) 1px, transparent 1px),
    linear-gradient(to bottom, rgba(255, 255, 255, 0.02) 1px, transparent 1px);
}

.highlight-bar {
  animation: barIn 0.4s ease-out;
}

.highlight-pulse {
  animation: pulseGlow 2s ease-in-out;
}

.highlight-active {
  box-shadow: 0 0 0 2px hsl(var(--primary)), 0 0 20px -4px hsl(var(--primary) / 0.3);
  transition: box-shadow 0.8s ease-out;
}

.highlight-active.highlight-active {
  animation: none;
}

@keyframes barIn {
  from {
    transform: scaleY(0);
    opacity: 0;
  }
  to {
    transform: scaleY(1);
    opacity: 1;
  }
}

@keyframes pulseGlow {
  0%, 100% {
    box-shadow: none;
  }
  20% {
    box-shadow: 0 0 0 2px hsl(var(--primary)), 0 0 24px -4px hsl(var(--primary) / 0.35);
  }
  60% {
    box-shadow: 0 0 0 2px hsl(var(--primary)), 0 0 12px -4px hsl(var(--primary) / 0.15);
  }
}
</style>