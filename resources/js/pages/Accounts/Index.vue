<template>
  <AppLayout :title="t('accounts.title')">
    <template #header>
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
          {{ t('accounts.title') }}
        </h2>
        <Button
          @click="openCreateModal"
          class="inline-flex items-center w-full sm:w-auto justify-center"
        >
          <PlusIcon class="w-4 h-4 me-2" />
          {{ t('accounts.create') }}
        </Button>
      </div>
    </template>

    <div class="py-4 sm:py-6 lg:py-8">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Filters Section -->
        <Card class="mb-6 overflow-hidden">
          <CardContent class="p-4">
            <div class="flex items-center justify-between mb-3 cursor-pointer" @click="toggleFilters">
              <div class="flex items-center gap-2">
                <Filter class="w-4 h-4 text-slate-600 dark:text-slate-400" />
                <span class="text-sm font-semibold text-slate-900 dark:text-slate-100">
                  {{ t('accounts.filter') }}
                  <Badge v-if="activeFiltersCount > 0" variant="secondary" class="ms-2 text-xs px-2 py-0.5">
                    {{ activeFiltersCount }}
                  </Badge>
                </span>
              </div>
              <Button variant="ghost" size="sm" class="h-7 w-7 p-0">
                <component :is="filtersExpanded ? ChevronUp : ChevronDown" class="w-4 h-4" />
              </Button>
            </div>

            <div v-if="filtersExpanded" class="space-y-3 pt-3 border-t">
              <!-- Search -->
              <div class="relative">
                <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-slate-400" />
                <Input
                  v-model="filterForm.search"
                  :placeholder="t('accounts.search_placeholder')"
                  class="pl-9 h-9 text-sm"
                  @input="debounceSearch"
                />
              </div>

              <!-- Filters Grid -->
              <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
                <div>
                  <Label class="text-xs font-medium mb-1.5 block">{{ t('accounts.type_label') }}</Label>
                  <Select
                    :key="`type-${resetKey}`"
                    v-model="filterForm.type"
                    @update:model-value="applyFilters"
                  >
                    <SelectTrigger class="h-9 text-sm">
                      <SelectValue :placeholder="t('accounts.all_types')" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="all">{{ t('accounts.all_types') }}</SelectItem>
                      <SelectItem value="checking">{{ t('accounts.checking') }}</SelectItem>
                      <SelectItem value="savings">{{ t('accounts.savings') }}</SelectItem>
                      <SelectItem value="credit">{{ t('accounts.credit') }}</SelectItem>
                      <SelectItem value="investment">{{ t('accounts.investment') }}</SelectItem>
                      <SelectItem value="cash">{{ t('accounts.cash') }}</SelectItem>
                      <SelectItem value="other">{{ t('accounts.other') }}</SelectItem>
                    </SelectContent>
                  </Select>
                </div>

                <div>
                  <Label class="text-xs font-medium mb-1.5 block">{{ t('accounts.currency') }}</Label>
                  <Select
                    :key="`currency-${resetKey}`"
                    v-model="filterForm.currency_id"
                    @update:model-value="applyFilters"
                  >
                    <SelectTrigger class="h-9 text-sm">
                      <SelectValue :placeholder="t('accounts.all_currencies')" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="all">{{ t('accounts.all_currencies') }}</SelectItem>
                      <SelectItem
                        v-for="currency in props.currencies"
                        :key="currency.id"
                        :value="currency.id.toString()"
                      >
                        {{ currency.code }} - {{ currency.name }}
                      </SelectItem>
                    </SelectContent>
                  </Select>
                </div>

                <div>
                  <Label class="text-xs font-medium mb-1.5 block">{{ t('accounts.status') }}</Label>
                  <Select
                    :key="`status-${resetKey}`"
                    v-model="filterForm.status"
                    @update:model-value="applyFilters"
                  >
                    <SelectTrigger class="h-9 text-sm">
                      <SelectValue :placeholder="t('accounts.all_statuses')" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="all">{{ t('accounts.all_statuses') }}</SelectItem>
                      <SelectItem value="active">{{ t('accounts.active_only') }}</SelectItem>
                      <SelectItem value="inactive">{{ t('accounts.inactive_only') }}</SelectItem>
                    </SelectContent>
                  </Select>
                </div>

                <div>
                  <Label class="text-xs font-medium mb-1.5 block">{{ t('accounts.sort_by') }}</Label>
                  <Select
                    :key="`sort-${resetKey}`"
                    v-model="filterForm.sort_by"
                    @update:model-value="applyFilters"
                  >
                    <SelectTrigger class="h-9 text-sm">
                      <SelectValue :placeholder="getSortLabel(filterForm.sort_by)" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="name_asc">{{ t('accounts.sort_name_asc') }}</SelectItem>
                      <SelectItem value="name_desc">{{ t('accounts.sort_name_desc') }}</SelectItem>
                      <SelectItem value="balance_asc">{{ t('accounts.sort_balance_asc') }}</SelectItem>
                      <SelectItem value="balance_desc">{{ t('accounts.sort_balance_desc') }}</SelectItem>
                      <SelectItem value="created_asc">{{ t('accounts.sort_created_asc') }}</SelectItem>
                      <SelectItem value="created_desc">{{ t('accounts.sort_created_desc') }}</SelectItem>
                      <SelectItem value="type_asc">{{ t('accounts.sort_type_asc') }}</SelectItem>
                    </SelectContent>
                  </Select>
                </div>

                <div class="flex items-end">
                  <Button variant="outline" @click="clearFilters" class="w-full h-9 text-sm">
                    <X class="w-4 h-4 me-1.5" />
                    {{ t('transactions.clear_filters') }}
                  </Button>
                </div>
              </div>

              <!-- Active Filters -->
              <div v-if="activeFiltersCount > 0" class="flex flex-wrap gap-2 pt-2 border-t">
                <Badge
                  v-if="filterForm.type !== 'all'"
                  variant="secondary"
                  class="flex items-center gap-1 text-xs px-2 py-1 cursor-pointer hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors"
                  @click="clearTypeFilter"
                >
                  {{ getTypeLabel(filterForm.type) }}
                  <X class="w-3 h-3 pointer-events-none" />
                </Badge>
                <Badge
                  v-if="filterForm.currency_id !== 'all'"
                  variant="secondary"
                  class="flex items-center gap-1 text-xs px-2 py-1 cursor-pointer hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors"
                  @click="clearCurrencyFilter"
                >
                  {{ getCurrencyName(filterForm.currency_id) }}
                  <X class="w-3 h-3 pointer-events-none" />
                </Badge>
                <Badge
                  v-if="filterForm.status !== 'all'"
                  variant="secondary"
                  class="flex items-center gap-1 text-xs px-2 py-1 cursor-pointer hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors"
                  @click="clearStatusFilter"
                >
                  {{ filterForm.status === 'active' ? t('accounts.active') : t('accounts.inactive') }}
                  <X class="w-3 h-3 pointer-events-none" />
                </Badge>
                <Badge
                  v-if="filterForm.search.trim()"
                  variant="secondary"
                  class="flex items-center gap-1 text-xs px-2 py-1 cursor-pointer hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors"
                  @click="clearSearchFilter"
                >
                  "{{ filterForm.search.trim() }}"
                  <X class="w-3 h-3 pointer-events-none" />
                </Badge>
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- Loading State -->
        <div v-if="isLoading || isRefreshing">
          <AccountSkeleton :count="6" />
        </div>

        <!-- Accounts Grid -->
        <div v-else-if="props.accounts.length > 0" class="grid gap-3 sm:gap-4 grid-cols-1 md:grid-cols-2 xl:grid-cols-3 auto-rows-max">
          <Card 
            v-for="account in props.accounts" 
            :key="account.id" 
            class="overflow-hidden hover:shadow-xl transition-all duration-300 hover:scale-[1.02] group h-fit max-h-[350px] flex flex-col cursor-pointer"
            @click="$inertia.visit(accountRoutes.show(account.id).url)"
          >
            <!-- Header with Type and Currency -->
            <div class="relative bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 px-3 py-1.5 flex-shrink-0">
              <div class="absolute inset-0 bg-grid-white/[0.02] bg-[size:12px_12px]"></div>
              
              <div class="relative flex items-center justify-between">
                <!-- Account Type -->
                <div class="flex items-center gap-1 text-slate-300">
                  <component :is="getTypeIcon(account.type)" class="w-3 h-3 flex-shrink-0" />
                  <span class="text-xs capitalize truncate">
                    {{ getTypeLabel(account.type) }}
                  </span>
                </div>

                <!-- Currency and Action Buttons -->
                <div class="flex items-center gap-2 flex-shrink-0">
                  <div class="text-xs text-slate-400 font-mono">
                    {{ account.currency.symbol }}
                  </div>
                  <Button
                    variant="ghost"
                    size="sm"
                    @click.stop="openPrintReport(account.id)"
                    class="h-6 w-6 p-0 text-slate-300 hover:text-white hover:bg-white/10"
                    :title="t('accounts.print_report')"
                  >
                    <Printer class="w-3 h-3" />
                  </Button>
                  <Button
                    variant="ghost"
                    size="sm"
                    @click.stop="openEditModal(account)"
                    class="h-6 w-6 p-0 text-slate-300 hover:text-white hover:bg-white/10"
                  >
                    <Edit class="w-3 h-3" />
                  </Button>
                </div>
              </div>
            </div>

            <!-- Body with Account Name and Balance -->
            <div class="px-3 py-4 flex-1 flex flex-col justify-center">
              <div class="flex items-center justify-between gap-4">
                <!-- Account Name -->
                <div class="flex-1 min-w-0">
                  <div class="flex items-center gap-2 mb-1">
                    <div class="w-0.5 h-3 bg-green-500 rounded-full flex-shrink-0"></div>
                    <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                      {{ t('accounts.name') }}
                    </span>
                  </div>
                  <div class="ps-2 flex items-center">
                    <div class="flex items-center gap-2 min-w-0 flex-1">
                      <div class="w-5 h-5 rounded-lg bg-gradient-to-br from-green-500 to-green-600 flex items-center justify-center flex-shrink-0">
                        <span class="text-xs font-bold text-white">A</span>
                      </div>
                      <div class="min-w-0 flex-1">
                        <div class="text-sm font-semibold text-slate-900 dark:text-slate-100 truncate">
                          {{ account.name }}
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Balance -->
                <div class="flex-shrink-0">
                  <div class="flex items-center gap-2 mb-1 justify-end">
                    <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                      {{ t('accounts.balance') }}
                    </span>
                    <div class="w-0.5 h-3 bg-blue-500 rounded-full flex-shrink-0"></div>
                  </div>
                  <div class="pe-2 flex items-center justify-end">
                    <div class="flex items-center gap-2">
                      <div class="min-w-0">
                        <div 
                          class="text-sm font-semibold text-right"
                          :class="{
                            'text-emerald-600 dark:text-emerald-400': account.balance >= 0,
                            'text-red-600 dark:text-red-400': account.balance < 0
                          }"
                        >
                          {{ formatCurrency(account.balance, account.currency) }}
                        </div>
                      </div>
                      <div class="w-5 h-5 rounded-lg bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center flex-shrink-0">
                        <span class="text-xs font-bold text-white">{{ account.currency.symbol }}</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </Card>
        </div>

        <!-- Empty State -->
        <div v-else-if="!isLoading && !isRefreshing" class="flex items-center justify-center py-16">
          <div class="text-center py-8 px-4">
            <div class="mx-auto w-16 h-16 sm:w-20 sm:h-20 bg-gray-100 dark:bg-gray-800/80 rounded-full flex items-center justify-center mb-4 dark:border dark:border-gray-700">
              <component :is="hasActiveFilters ? Search : Wallet" class="w-8 h-8 sm:w-10 sm:h-10 text-gray-400" />
            </div>
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">
              {{ hasActiveFilters ? t('accounts.no_accounts_found') : t('dashboard.no_accounts') }}
            </h3>
            <p class="text-gray-600 dark:text-gray-400 mb-6 max-w-md mx-auto text-sm">
              {{ hasActiveFilters ? t('accounts.no_accounts_match', { query: filterForm.search || '' }) : t('dashboard.get_started_account') }}
            </p>
            <div class="flex flex-col sm:flex-row gap-3 justify-center">
              <Button v-if="hasActiveFilters" variant="outline" @click="clearFilters" class="w-full sm:w-auto">
                {{ t('transactions.clear_filters') }}
              </Button>
              <Button @click="openCreateModal" class="w-full sm:w-auto">
                <PlusIcon class="w-4 h-4 me-2" />
                {{ t('accounts.create_account') }}
              </Button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Account Form Modal -->
     <AccountFormModal
       v-model:open="isModalOpen"
       :account="editingAccount"
       :currencies="props.currencies"
       @success="handleModalSuccess"
     />
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, computed, reactive, nextTick } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Plus as PlusIcon, Eye, Edit, Wallet, Search, Printer, Landmark, PiggyBank, CreditCard, TrendingUp, Banknote, MoreHorizontal, Filter, X, ChevronDown, ChevronUp } from 'lucide-vue-next'
import { useI18n } from 'vue-i18n'
import { useFormatting } from '@/composables/useFormatting'
import accountRoutes from '@/routes/accounts'
import AccountFormModal from '@/components/AccountFormModal.vue'
import LoadingSpinner from '@/components/LoadingSpinner.vue'
import AccountSkeleton from '@/components/AccountSkeleton.vue'

const { t } = useI18n()
const { formatCurrency } = useFormatting()
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
  description?: string | null
  created_at: string
  updated_at: string
}

interface Filters {
  search?: string
  type?: string
  currency_id?: string
  status?: string
  balance_min?: string
  balance_max?: string
  sort_by?: string
}

const props = defineProps<{
  accounts: Account[]
  currencies: Currency[]
  filters?: Filters
}>()

// Modal state
const isModalOpen = ref(false)
const editingAccount = ref<Account | null>(null)

// Loading states
const isLoading = ref(false)
const isRefreshing = ref(false)

// Filters state
const filtersExpanded = ref(false)
const searchTimeout = ref<number | null>(null)
const resetKey = ref(0)

const filterForm = reactive({
  search: props.filters?.search || '',
  type: props.filters?.type || 'all',
  currency_id: props.filters?.currency_id || 'all',
  status: props.filters?.status || 'all',
  sort_by: props.filters?.sort_by || 'name_asc',
})

const hasActiveFilters = computed(() => {
  return filterForm.type !== 'all' ||
    filterForm.currency_id !== 'all' ||
    filterForm.status !== 'all' ||
    filterForm.search.trim() !== ''
})

const activeFiltersCount = computed(() => {
  let count = 0
  if (filterForm.type !== 'all') count++
  if (filterForm.currency_id !== 'all') count++
  if (filterForm.status !== 'all') count++
  if (filterForm.search.trim()) count++
  return count
})

const getTypeIcon = (type: string) => {
  switch (type) {
    case 'checking': return Landmark
    case 'savings': return PiggyBank
    case 'credit': return CreditCard
    case 'investment': return TrendingUp
    case 'cash': return Banknote
    default: return Wallet
  }
}

const getTypeLabel = (type: string) => {
  const key = `accounts.${type}`
  const translated = t(key)
  return translated !== key ? translated : type
}

const getSortLabel = (sortBy: string) => {
  switch (sortBy) {
    case 'name_asc': return t('accounts.sort_name_asc')
    case 'name_desc': return t('accounts.sort_name_desc')
    case 'balance_asc': return t('accounts.sort_balance_asc')
    case 'balance_desc': return t('accounts.sort_balance_desc')
    case 'created_asc': return t('accounts.sort_created_asc')
    case 'created_desc': return t('accounts.sort_created_desc')
    case 'type_asc': return t('accounts.sort_type_asc')
    default: return t('accounts.sort_name_asc')
  }
}

const getCurrencyName = (currencyId: string) => {
  const currency = props.currencies.find(c => c.id.toString() === currencyId)
  return currency ? `${currency.code} - ${currency.name}` : ''
}

const toggleFilters = () => {
  filtersExpanded.value = !filtersExpanded.value
}

const debounceSearch = () => {
  if (searchTimeout.value) {
    clearTimeout(searchTimeout.value)
  }
  searchTimeout.value = setTimeout(() => {
    applyFilters()
  }, 500)
}

const applyFilters = () => {
  const filters: Record<string, string> = {}

  if (filterForm.search.trim()) {
    filters.search = filterForm.search.trim()
  }
  if (filterForm.type !== 'all') {
    filters.type = filterForm.type
  }
  if (filterForm.currency_id !== 'all') {
    filters.currency_id = filterForm.currency_id
  }
  if (filterForm.status !== 'all') {
    filters.status = filterForm.status
  }
  if (filterForm.sort_by !== 'name_asc') {
    filters.sort_by = filterForm.sort_by
  }

  isRefreshing.value = true

  router.get('/accounts', filters, {
    preserveState: true,
    preserveScroll: true,
    onFinish: () => {
      isRefreshing.value = false
    }
  })
}

const clearFilters = () => {
  filterForm.search = ''
  filterForm.type = 'all'
  filterForm.currency_id = 'all'
  filterForm.status = 'all'
  filterForm.sort_by = 'name_asc'
  resetKey.value++

  router.get('/accounts', {}, {
    preserveState: true,
    preserveScroll: true
  })
}

const clearTypeFilter = () => {
  filterForm.type = 'all'
  applyFilters()
}

const clearCurrencyFilter = () => {
  filterForm.currency_id = 'all'
  applyFilters()
}

const clearStatusFilter = () => {
  filterForm.status = 'all'
  applyFilters()
}

const clearSearchFilter = () => {
  filterForm.search = ''
  applyFilters()
}

const openCreateModal = () => {
  editingAccount.value = null
  isModalOpen.value = true
}

const openEditModal = (account: Account) => {
  editingAccount.value = account
  isModalOpen.value = true
}

const handleModalSuccess = () => {
  isRefreshing.value = true
  router.reload({
    onFinish: () => {
      isRefreshing.value = false
    }
  })
}

const openPrintReport = (accountId: number) => {
  router.visit(`/accounts/${accountId}/report`)
}
</script>

<style scoped>
.bg-grid-white\/\[0\.02\] {
  background-image: linear-gradient(to right, rgba(255, 255, 255, 0.02) 1px, transparent 1px),
    linear-gradient(to bottom, rgba(255, 255, 255, 0.02) 1px, transparent 1px);
}
</style>
