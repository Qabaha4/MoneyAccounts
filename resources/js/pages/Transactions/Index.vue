<template>
  <AppLayout :title="t('transactions.title')">
    <template #header>
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <h2 class="font-bold text-xl sm:text-2xl text-gray-900 dark:text-gray-100">
          {{ t('transactions.title') }}
        </h2>
        <div class="flex items-center gap-2">
          <Button variant="outline" size="sm" @click="toggleView" class="h-9">
            <component :is="viewMode === 'grid' ? List : Grid" class="w-4 h-4 sm:me-2" />
            <span class="hidden sm:inline">{{ viewMode === 'grid' ? 'List' : 'Grid' }}</span>
          </Button>
          <Button size="sm" @click="openCreateModal" class="h-9 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800">
            <Plus class="w-4 h-4 sm:me-2" />
            <span class="hidden sm:inline">{{ t('transactions.add_transaction') }}</span>
          </Button>
        </div>
      </div>
    </template>

    <div class="py-4 sm:py-6">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Summary Cards -->
        <div class="mb-6">
          <!-- Mobile: Horizontal Scroll -->
          <div class="flex gap-3 overflow-x-auto pb-2 snap-x snap-mandatory scrollbar-hide md:hidden">
            <div class="flex-shrink-0 w-40 snap-start">
              <div class="bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-2xl p-4 text-white shadow-lg">
                <TrendingUp class="w-5 h-5 mb-2 opacity-90" />
                <div class="text-xs font-medium opacity-90 mb-1">Income</div>
                <div class="text-xl font-bold">+${{ totalIncome.toFixed(2) }}</div>
              </div>
            </div>
            
            <div class="flex-shrink-0 w-40 snap-start">
              <div class="bg-gradient-to-br from-rose-500 to-rose-600 rounded-2xl p-4 text-white shadow-lg">
                <TrendingDown class="w-5 h-5 mb-2 opacity-90" />
                <div class="text-xs font-medium opacity-90 mb-1">Expenses</div>
                <div class="text-xl font-bold">-${{ totalExpenses.toFixed(2) }}</div>
              </div>
            </div>
            
            <div class="flex-shrink-0 w-40 snap-start">
              <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl p-4 text-white shadow-lg">
                <ArrowUpDown class="w-5 h-5 mb-2 opacity-90" />
                <div class="text-xs font-medium opacity-90 mb-1">Transfers</div>
                <div class="text-xl font-bold">{{ totalTransfers }}</div>
              </div>
            </div>
            
            <div class="flex-shrink-0 w-40 snap-start">
              <div class="bg-gradient-to-br from-slate-700 to-slate-800 rounded-2xl p-4 text-white shadow-lg">
                <DollarSign class="w-5 h-5 mb-2 opacity-90" />
                <div class="text-xs font-medium opacity-90 mb-1">Net Balance</div>
                <div class="text-xl font-bold">{{ netBalance >= 0 ? '+' : '' }}${{ netBalance.toFixed(2) }}</div>
              </div>
            </div>
          </div>

          <!-- Desktop: Grid -->
          <div class="hidden md:grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-2xl p-5 text-white shadow-lg hover:shadow-xl transition-shadow">
              <div class="flex items-center justify-between mb-3">
                <div class="text-sm font-medium opacity-90">Total Income</div>
                <TrendingUp class="w-5 h-5 opacity-90" />
              </div>
              <div class="text-2xl font-bold">+${{ totalIncome.toFixed(2) }}</div>
            </div>
            
            <div class="bg-gradient-to-br from-rose-500 to-rose-600 rounded-2xl p-5 text-white shadow-lg hover:shadow-xl transition-shadow">
              <div class="flex items-center justify-between mb-3">
                <div class="text-sm font-medium opacity-90">Total Expenses</div>
                <TrendingDown class="w-5 h-5 opacity-90" />
              </div>
              <div class="text-2xl font-bold">-${{ totalExpenses.toFixed(2) }}</div>
            </div>
            
            <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl p-5 text-white shadow-lg hover:shadow-xl transition-shadow">
              <div class="flex items-center justify-between mb-3">
                <div class="text-sm font-medium opacity-90">Transfers</div>
                <ArrowUpDown class="w-5 h-5 opacity-90" />
              </div>
              <div class="text-2xl font-bold">{{ totalTransfers }}</div>
            </div>
            
            <div class="bg-gradient-to-br from-slate-700 to-slate-800 rounded-2xl p-5 text-white shadow-lg hover:shadow-xl transition-shadow">
              <div class="flex items-center justify-between mb-3">
                <div class="text-sm font-medium opacity-90">Net Balance</div>
                <DollarSign class="w-5 h-5 opacity-90" />
              </div>
              <div class="text-2xl font-bold">{{ netBalance >= 0 ? '+' : '' }}${{ netBalance.toFixed(2) }}</div>
            </div>
          </div>
        </div>

        <!-- Filters -->
        <Card class="mb-6 overflow-hidden">
          <CardContent class="p-4">
            <div class="flex items-center justify-between mb-3 cursor-pointer" @click="toggleFilters">
              <div class="flex items-center gap-2">
                <Filter class="w-4 h-4 text-slate-600 dark:text-slate-400" />
                <span class="text-sm font-semibold text-slate-900 dark:text-slate-100">
                  Filters
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
                  ref="searchInputRef"
                  v-model="filterForm.search"
                  placeholder="Search transactions..."
                  class="pl-9 h-9 text-sm"
                  @input="debounceSearch"
                />
              </div>

              <!-- Filters Grid -->
              <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
                <div>
                  <Label class="text-xs font-medium mb-1.5 block">Account</Label>
                  <Select 
                    :key="`account-${filterForm.account_id}`"
                    v-model="filterForm.account_id" 
                    @update:model-value="applyFilters"
                  >
                    <SelectTrigger class="h-9 text-sm">
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
                
                <div>
                  <Label class="text-xs font-medium mb-1.5 block">Type</Label>
                  <Select 
                    :key="`type-${filterForm.type}`"
                    v-model="filterForm.type" 
                    @update:model-value="applyFilters"
                  >
                    <SelectTrigger class="h-9 text-sm">
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
                
                <div>
                  <Label class="text-xs font-medium mb-1.5 block">Sort By</Label>
                  <Select 
                    :key="`sort-${filterForm.sort_by}`"
                    v-model="filterForm.sort_by" 
                    @update:model-value="applyFilters"
                  >
                    <SelectTrigger class="h-9 text-sm">
                      <SelectValue :placeholder="getSortLabel(filterForm.sort_by)" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="transaction_date_desc">Newest first</SelectItem>
                      <SelectItem value="transaction_date_asc">Oldest first</SelectItem>
                      <SelectItem value="amount_desc">Highest amount</SelectItem>
                      <SelectItem value="amount_asc">Lowest amount</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
                
                <div>
                  <Label class="text-xs font-medium mb-1.5 block">From Date</Label>
                  <DatePicker 
                    v-model="filterForm.date_from" 
                    @update:model-value="applyFilters"
                    placeholder="Select start date"
                    class="h-9 text-sm"
                  />
                </div>
                
                <div>
                  <Label class="text-xs font-medium mb-1.5 block">To Date</Label>
                  <DatePicker 
                    v-model="filterForm.date_to" 
                    @update:model-value="applyFilters"
                    placeholder="Select end date"
                    class="h-9 text-sm"
                  />
                </div>
                
                <div class="flex items-end">
                  <Button variant="outline" @click="clearFilters" class="w-full h-9 text-sm">
                    <X class="w-4 h-4 me-1.5" />
                    Clear
                  </Button>
                </div>
              </div>

              <!-- Active Filters -->
              <div v-if="activeFiltersCount > 0" class="flex flex-wrap gap-2 pt-2 border-t">
                <Badge 
                  v-if="filterForm.account_id !== 'all'" 
                  variant="secondary" 
                  class="flex items-center gap-1 text-xs px-2 py-1 cursor-pointer hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors"
                  @click="clearAccountFilter"
                >
                  {{ getAccountName(filterForm.account_id) }}
                  <X class="w-3 h-3 pointer-events-none" />
                </Badge>
                <Badge 
                  v-if="filterForm.type !== 'all'" 
                  variant="secondary"
                  class="flex items-center gap-1 text-xs px-2 py-1 cursor-pointer hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors"
                  @click="clearTypeFilter"
                >
                  {{ filterForm.type }}
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

        <!-- Transactions Display -->
        <div v-if="isLoading || isFilterLoading" class="space-y-4">
           <TransactionSkeleton :count="6" :view-mode="viewMode" />
         </div>
        
        <div v-else-if="transactions.data.length > 0">
          <!-- Grid View -->
          <div v-if="viewMode === 'grid'" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            <Card 
              v-for="transaction in transactions.data" 
              :key="transaction.id" 
              class="group hover:shadow-xl transition-all duration-300 cursor-pointer overflow-hidden border-none"
              @click="viewTransaction(transaction)"
            >
              <div 
                class="h-1.5 w-full"
                :class="{
                  'bg-gradient-to-r from-emerald-500 to-emerald-600': transaction.type === 'income',
                  'bg-gradient-to-r from-rose-500 to-rose-600': transaction.type === 'expense',
                  'bg-gradient-to-r from-blue-500 to-blue-600': transaction.type === 'transfer'
                }"
              ></div>
              <CardContent class="p-4">
                <div class="flex items-center justify-between mb-3">
                  <div 
                    class="w-10 h-10 rounded-xl flex items-center justify-center"
                    :class="{
                      'bg-emerald-100 dark:bg-emerald-900/30': transaction.type === 'income',
                      'bg-rose-100 dark:bg-rose-900/30': transaction.type === 'expense',
                      'bg-blue-100 dark:bg-blue-900/30': transaction.type === 'transfer'
                    }"
                  >
                    <component 
                      :is="getTransactionIcon(transaction.type)" 
                      class="w-5 h-5"
                      :class="{
                        'text-emerald-600 dark:text-emerald-400': transaction.type === 'income',
                        'text-rose-600 dark:text-rose-400': transaction.type === 'expense',
                        'text-blue-600 dark:text-blue-400': transaction.type === 'transfer'
                      }"
                    />
                  </div>
                  <Badge 
                    :variant="getTransactionVariant(transaction.type)"
                    class="text-xs px-2 py-0.5 font-medium"
                  >
                    {{ transaction.type }}
                  </Badge>
                </div>
                
                <div 
                  class="text-2xl font-bold mb-2"
                  :class="getAmountColor(transaction.type, transaction.is_incoming_transfer)"
                >
                  {{ getAmountPrefix(transaction.type, transaction.is_incoming_transfer) }}{{ transaction.account.currency.symbol }}{{ Number(transaction.amount).toLocaleString() }}
                </div>
                
                <p class="font-medium text-sm text-slate-900 dark:text-slate-100 truncate mb-2">
                  {{ transaction.description || 'No description' }}
                </p>
                
                <div class="text-xs text-slate-500 dark:text-slate-400 space-y-1">
                  <!-- Account Information -->
                  <template v-if="transaction.type === 'transfer'">
                    <div class="space-y-1">
                      <p class="truncate text-xs">
                        <span class="font-medium">{{ t('transactions.from') }}</span>
                        <button 
                          @click.stop="handleNavigateToAccount(transaction.account.id)"
                          class="text-blue-600 dark:text-blue-400 hover:underline mx-1"
                        >
                          {{ transaction.account.name }}
                        </button>
                        <span class="font-medium" v-if="transaction.transfer_to_account">{{ t('transactions.to') }}</span>
                        <button 
                          v-if="transaction.transfer_to_account"
                          @click.stop="handleNavigateToAccount(transaction.transfer_to_account.id)"
                          class="text-blue-600 dark:text-blue-400 hover:underline ml-1"
                        >
                          {{ transaction.transfer_to_account.name }}
                        </button>
                      </p>
                      <div class="flex items-center gap-2">
                        <Badge 
                          :variant="transaction.is_incoming_transfer ? 'default' : 'secondary'"
                          class="text-xs px-2 py-0.5"
                        >
                          {{ getTransactionLabel(transaction.type, transaction.is_incoming_transfer) }}
                        </Badge>
                      </div>
                    </div>
                  </template>
                  <template v-else>
                    <p class="truncate">
                      <button 
                        @click.stop="handleNavigateToAccount(transaction.account.id)"
                        class="text-blue-600 dark:text-blue-400 hover:underline"
                      >
                        {{ transaction.account.name }}
                      </button>
                    </p>
                  </template>
                  <p>{{ formatDate(transaction.transaction_date) }}</p>
                </div>
              </CardContent>
            </Card>
          </div>

          <!-- List View -->
          <div v-else class="space-y-2">
            <Card 
              v-for="transaction in transactions.data" 
              :key="transaction.id" 
              class="group hover:shadow-lg transition-all duration-200 cursor-pointer overflow-hidden border-l-4"
              :class="{
                'border-l-emerald-500': transaction.type === 'income',
                'border-l-rose-500': transaction.type === 'expense',
                'border-l-blue-500': transaction.type === 'transfer'
              }"
              @click="viewTransaction(transaction)"
            >
              <CardContent class="p-3 sm:p-4">
                <div class="flex items-center gap-3">
                  <!-- Icon -->
                  <div 
                    class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl flex items-center justify-center flex-shrink-0"
                    :class="{
                      'bg-emerald-100 dark:bg-emerald-900/30': transaction.type === 'income',
                      'bg-rose-100 dark:bg-rose-900/30': transaction.type === 'expense',
                      'bg-blue-100 dark:bg-blue-900/30': transaction.type === 'transfer'
                    }"
                  >
                    <component 
                      :is="getTransactionIcon(transaction.type)" 
                      class="w-5 h-5 sm:w-6 sm:h-6"
                      :class="{
                        'text-emerald-600 dark:text-emerald-400': transaction.type === 'income',
                        'text-rose-600 dark:text-rose-400': transaction.type === 'expense',
                        'text-blue-600 dark:text-blue-400': transaction.type === 'transfer'
                      }"
                    />
                  </div>
                  
                  <!-- Info -->
                  <div class="flex-1 min-w-0">
                    <h3 class="font-semibold text-sm sm:text-base text-slate-900 dark:text-slate-100 truncate mb-1">
                      {{ transaction.description || 'No description' }}
                    </h3>
                    <div class="space-y-1">
                      <!-- Transfer Information -->
                       <template v-if="transaction.type === 'transfer'">
                         <div class="flex items-center gap-1 text-xs">
                           <span class="text-slate-500 dark:text-slate-400">{{ t('transactions.from') }}</span>
                           <button 
                             @click.stop="handleNavigateToAccount(transaction.account.id)"
                             class="text-blue-600 dark:text-blue-400 hover:underline truncate"
                           >
                             {{ transaction.account.name }}
                           </button>
                           <span class="text-slate-500 dark:text-slate-400" v-if="transaction.transfer_to_account">{{ t('transactions.to') }}</span>
                           <button 
                             v-if="transaction.transfer_to_account"
                             @click.stop="handleNavigateToAccount(transaction.transfer_to_account.id)"
                             class="text-blue-600 dark:text-blue-400 hover:underline truncate"
                           >
                             {{ transaction.transfer_to_account.name }}
                           </button>
                         </div>
                        <div class="flex items-center gap-2">
                          <Badge 
                            :variant="transaction.is_incoming_transfer ? 'default' : 'secondary'"
                            class="text-xs px-2 py-0.5"
                          >
                            {{ getTransactionLabel(transaction.type, transaction.is_incoming_transfer) }}
                          </Badge>
                          <span class="text-xs text-slate-400 dark:text-slate-500">•</span>
                          <span class="text-xs text-slate-500 dark:text-slate-400 hidden sm:inline">
                            {{ formatDate(transaction.transaction_date) }}
                          </span>
                        </div>
                      </template>
                      <!-- Regular Transaction Information -->
                      <template v-else>
                        <div class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
                          <button 
                            @click.stop="handleNavigateToAccount(transaction.account.id)"
                            class="text-blue-600 hover:text-blue-700 dark:text-blue-400 font-medium hover:underline"
                          >
                            {{ transaction.account.name }}
                          </button>
                          <span>•</span>
                          <span class="hidden sm:inline">{{ formatDate(transaction.transaction_date) }}</span>
                        </div>
                      </template>
                    </div>
                  </div>
                  
                  <!-- Amount -->
                  <div class="text-right flex-shrink-0">
                    <div 
                      class="text-base sm:text-lg font-bold"
                      :class="getAmountColor(transaction.type, transaction.is_incoming_transfer)"
                    >
                      {{ getAmountPrefix(transaction.type, transaction.is_incoming_transfer) }}{{ transaction.account.currency.symbol }}{{ Number(transaction.amount).toLocaleString() }}
                    </div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 sm:hidden mt-0.5">
                      {{ formatDate(transaction.transaction_date) }}
                    </div>
                  </div>
                </div>
              </CardContent>
            </Card>
          </div>

          <!-- Pagination -->
          <div v-if="transactions.last_page > 1" class="flex justify-center mt-6">
            <div class="flex items-center gap-1 sm:gap-2">
              <Button
                variant="outline"
                size="sm"
                @click="goToPage(transactions.current_page - 1)"
                :disabled="transactions.current_page === 1 || isLoading"
                class="h-9"
              >
                <LoadingSpinner v-if="isLoading" class="w-4 h-4" />
                <ChevronLeft v-else class="w-4 h-4" />
                <span class="hidden sm:inline ms-1">Previous</span>
              </Button>
              
              <div class="flex gap-1">
                <Button
                  v-for="page in paginationPages"
                  :key="page"
                  :variant="page === transactions.current_page ? 'default' : 'outline'"
                  size="sm"
                  @click="goToPage(page)"
                  :disabled="isLoading"
                  class="min-w-[36px] h-9 text-sm"
                  :class="{'bg-gradient-to-r from-blue-600 to-blue-700': page === transactions.current_page}"
                >
                  <LoadingSpinner v-if="isLoading && page === transactions.current_page" class="w-4 h-4" />
                  <span v-else>{{ page }}</span>
                </Button>
              </div>
              
              <Button
                variant="outline"
                size="sm"
                @click="goToPage(transactions.current_page + 1)"
                :disabled="transactions.current_page === transactions.last_page || isLoading"
                class="h-9"
              >
                <span class="hidden sm:inline me-1">Next</span>
                <LoadingSpinner v-if="isLoading" class="w-4 h-4" />
                <ChevronRight v-else class="w-4 h-4" />
              </Button>
            </div>
          </div>
        </div>

        <!-- Empty State -->
        <Card v-else-if="!isLoading && !isFilterLoading" class="text-center py-12 sm:py-16">
          <CardContent>
            <div class="max-w-md mx-auto">
              <div class="bg-gradient-to-br from-slate-100 to-slate-200 dark:from-slate-800 dark:to-slate-900 rounded-full w-16 h-16 sm:w-20 sm:h-20 flex items-center justify-center mx-auto mb-4 sm:mb-6">
                <Receipt class="w-8 h-8 sm:w-10 sm:h-10 text-slate-400" />
              </div>
              <h3 class="text-lg sm:text-xl font-bold text-slate-900 dark:text-slate-100 mb-2 sm:mb-3">
                {{ hasActiveFilters ? 'No matching transactions' : 'No transactions yet' }}
              </h3>
              <p class="text-sm text-slate-600 dark:text-slate-400 mb-4 sm:mb-6">
                {{ hasActiveFilters 
                   ? 'Try adjusting your filters to see more transactions.' 
                   : 'Get started by creating your first transaction.' }}
              </p>
              <div class="flex flex-col sm:flex-row gap-3 justify-center">
                <Button v-if="hasActiveFilters" variant="outline" @click="clearFilters" class="h-10">
                  <X class="w-4 h-4 me-2" />
                  Clear Filters
                </Button>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>

    <!-- Transaction Detail Modal -->
    <TransactionDetailModal
      v-model:isOpen="isModalOpen"
      :transaction="selectedTransaction"
      :isDeleting="isDeleting"
      @edit="handleEditTransaction"
      @delete="handleDeleteTransaction"
      @navigateToAccount="handleNavigateToAccount"
    />

    <!-- Transaction Edit Modal -->
    <TransactionModal
      v-model:is-open="isEditModalOpen"
      :accounts="props.accounts"
      :transaction="editingTransaction"
      :allow-account-change="false"
      @success="handleTransactionEditSuccess"
    />
  </AppLayout>
</template>

<script setup lang="ts">
import { computed, reactive, ref, nextTick, onMounted } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import AppLayout from '@/layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Label } from '@/components/ui/label'
import { Input } from '@/components/ui/input'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { DatePicker } from '@/components/ui/date-picker'
import LoadingSpinner from '@/components/LoadingSpinner.vue'
import TransactionSkeleton from '@/components/TransactionSkeleton.vue'
import { 
  Receipt, Plus, Search, Filter, X, Grid, List, MoreHorizontal,
  TrendingUp, TrendingDown, ArrowUpDown, DollarSign,
  ChevronLeft, ChevronRight, ArrowUpRight, ArrowDownLeft,
  ArrowRightLeft, ChevronUp, ChevronDown
} from 'lucide-vue-next'
import TransactionDetailModal from '@/components/TransactionDetailModal.vue'
import TransactionModal from '@/components/TransactionModal.vue'

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
  amount: string
  type: 'income' | 'expense' | 'transfer'
  description: string | null
  transaction_date: string
  account: Account
  transfer_to_account?: Account | null
  is_incoming_transfer?: boolean
}

interface PaginatedTransactions {
  data: Transaction[]
  current_page: number
  last_page: number
  per_page: number
  total: number
}

interface Props {
  transactions: PaginatedTransactions
  accounts: Account[]
  filters: {
    account_id?: string
    type?: string
    search?: string
    sort_by?: string
    date_from?: string
    date_to?: string
  }
}

const props = defineProps<Props>()
const { t } = useI18n()

const viewMode = ref<'grid' | 'list'>('list')
const searchTimeout = ref<number | null>(null)
const filtersExpanded = ref(false)
const searchInputRef = ref<any>(null)
const isModalOpen = ref(false)
const selectedTransaction = ref<Transaction | null>(null)
const isDeleting = ref(false)
const isEditModalOpen = ref(false)
const editingTransaction = ref<Transaction | null>(null)
const isLoading = ref(false)
const isFilterLoading = ref(false)

// Helper function to format date
const formatDateLocal = (date: Date) => {
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

const getStartOfLastWeek = () => {
  const now = new Date()
  const lastWeek = new Date(now.getTime() - 7 * 24 * 60 * 60 * 1000)
  return formatDateLocal(lastWeek)
}

const getCurrentDate = () => {
  return formatDateLocal(new Date())
}

const filterForm = reactive({
  account_id: props.filters.account_id || 'all',
  type: props.filters.type || 'all',
  search: props.filters.search || '',
  sort_by: props.filters.sort_by || 'transaction_date_desc',
  date_from: props.filters.date_from || getStartOfLastWeek(),
  date_to: props.filters.date_to || getCurrentDate()
})

const paginationPages = computed(() => {
  const pages = []
  const maxPages = 7
  const current = props.transactions.current_page
  const last = props.transactions.last_page
  
  if (last <= maxPages) {
    for (let i = 1; i <= last; i++) {
      pages.push(i)
    }
  } else {
    if (current <= 4) {
      for (let i = 1; i <= 5; i++) {
        pages.push(i)
      }
      pages.push('...')
      pages.push(last)
    } else if (current >= last - 3) {
      pages.push(1)
      pages.push('...')
      for (let i = last - 4; i <= last; i++) {
        pages.push(i)
      }
    } else {
      pages.push(1)
      pages.push('...')
      for (let i = current - 1; i <= current + 1; i++) {
        pages.push(i)
      }
      pages.push('...')
      pages.push(last)
    }
  }
  
  return pages
})

const totalIncome = computed(() => {
  return props.transactions.data
    .filter(t => t.type === 'income')
    .reduce((sum, t) => sum + parseFloat(t.amount), 0)
})

const totalExpenses = computed(() => {
  return props.transactions.data
    .filter(t => t.type === 'expense')
    .reduce((sum, t) => sum + parseFloat(t.amount), 0)
})

const totalTransfers = computed(() => {
  return props.transactions.data.filter(t => t.type === 'transfer').length
})

const netBalance = computed(() => {
  return totalIncome.value - totalExpenses.value
})

const hasActiveFilters = computed(() => {
  return filterForm.account_id !== 'all' || 
         filterForm.type !== 'all' || 
         filterForm.search.trim() !== ''
})

const activeFiltersCount = computed(() => {
  let count = 0
  if (filterForm.account_id !== 'all') count++
  if (filterForm.type !== 'all') count++
  if (filterForm.search.trim()) count++
  return count
})

const toggleView = () => {
  viewMode.value = viewMode.value === 'grid' ? 'list' : 'grid'
}

const openCreateModal = () => {
  router.visit('/transactions/create')
}

const viewTransaction = (transaction: Transaction) => {
  selectedTransaction.value = transaction
  isModalOpen.value = true
}

const getTransactionIcon = (type: string) => {
  switch (type) {
    case 'income': return ArrowUpRight
    case 'expense': return ArrowDownLeft
    case 'transfer': return ArrowRightLeft
    default: return Receipt
  }
}

const getTransactionVariant = (type: string) => {
  switch (type) {
    case 'income': return 'default'
    case 'expense': return 'destructive'
    case 'transfer': return 'secondary'
    default: return 'outline'
  }
}

const getAmountPrefix = (type: string, isIncomingTransfer?: boolean) => {
  switch (type) {
    case 'income': return '+'
    case 'expense': return '-'
    case 'transfer': return isIncomingTransfer ? '+' : '→'
    default: return ''
  }
}

const getAmountColor = (type: string, isIncomingTransfer?: boolean) => {
  switch (type) {
    case 'income': return 'text-emerald-600 dark:text-emerald-400'
    case 'expense': return 'text-rose-600 dark:text-rose-400'
    case 'transfer': return isIncomingTransfer 
      ? 'text-emerald-600 dark:text-emerald-400' 
      : 'text-blue-600 dark:text-blue-400'
    default: return 'text-gray-600 dark:text-gray-400'
  }
}

const getTransactionLabel = (type: string, isIncomingTransfer?: boolean) => {
  if (type === 'transfer') {
    return isIncomingTransfer ? t('transactions.transfer_in') : t('transactions.transfer_out')
  }
  return t(`transactions.${type}`)
}

const getTransferAccountInfo = (transaction: Transaction) => {
  if (transaction.type !== 'transfer') return null
  
  if (transaction.is_incoming_transfer) {
    return {
      label: t('transactions.from'),
      account: transaction.account,
      direction: 'from'
    }
  } else {
    return {
      label: t('transactions.to'),
      account: transaction.transfer_to_account,
      direction: 'to'
    }
  }
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

const debounceSearch = () => {
  if (searchTimeout.value) {
    clearTimeout(searchTimeout.value)
  }
  
  searchTimeout.value = setTimeout(() => {
    applyFilters()
  }, 1000)
}

const applyFilters = () => {
  const filters: Record<string, string> = {}
  
  if (filterForm.account_id !== 'all') {
    filters.account_id = filterForm.account_id
  }
  
  if (filterForm.type !== 'all') {
    filters.type = filterForm.type
  }
  
  if (filterForm.search.trim()) {
    filters.search = filterForm.search.trim()
  }
  
  if (filterForm.sort_by !== 'transaction_date_desc') {
    filters.sort_by = filterForm.sort_by
  }
  
  if (filterForm.date_from) {
    filters.date_from = filterForm.date_from
  }
  
  if (filterForm.date_to) {
    filters.date_to = filterForm.date_to
  }
  
  isFilterLoading.value = true
  
  router.get('/transactions', filters, {
    preserveState: true,
    preserveScroll: true,
    onFinish: () => {
      isFilterLoading.value = false
      // Maintain focus on search input after search completes
      if (searchInputRef.value) {
        nextTick(() => {
          const inputElement = searchInputRef.value?.$el || searchInputRef.value
          if (inputElement && typeof inputElement.focus === 'function') {
            inputElement.focus()
          }
        })
      }
    }
  })
}

const clearFilters = () => {
  filterForm.account_id = 'all'
  filterForm.type = 'all'
  filterForm.search = ''
  filterForm.sort_by = 'transaction_date_desc'
  // filterForm.date_from = getStartOfLastWeek()
  // filterForm.date_to = getCurrentDate()
  
  router.get('/transactions', {}, {
    preserveState: true,
    preserveScroll: true
  })
}

const goToPage = (page: number | string) => {
  if (typeof page === 'string') return
  
  const filters: Record<string, string | number> = { page }
  
  if (filterForm.account_id !== 'all') {
    filters.account_id = filterForm.account_id
  }
  
  if (filterForm.type !== 'all') {
    filters.type = filterForm.type
  }
  
  if (filterForm.search.trim()) {
    filters.search = filterForm.search.trim()
  }
  
  if (filterForm.sort_by !== 'transaction_date_desc') {
    filters.sort_by = filterForm.sort_by
  }
  
  isLoading.value = true
  
  router.get('/transactions', filters, {
    preserveState: true,
    preserveScroll: true,
    onFinish: () => {
      isLoading.value = false
    }
  })
}

const toggleFilters = () => {
  filtersExpanded.value = !filtersExpanded.value
}

const getSortLabel = (sortBy: string) => {
  switch (sortBy) {
    case 'transaction_date_desc': return 'Date (newest first)'
    case 'transaction_date_asc': return 'Date (oldest first)'
    case 'amount_desc': return 'Amount (highest first)'
    case 'amount_asc': return 'Amount (lowest first)'
    case 'type_asc': return 'Type (A-Z)'
    case 'description_asc': return 'Description (A-Z)'
    default: return 'Date (newest first)'
  }
}

const getAccountName = (accountId: string) => {
  const account = props.accounts.find(a => a.id.toString() === accountId)
  return account ? account.name : 'Unknown Account'
}

const clearAccountFilter = () => {
  filterForm.account_id = 'all'
  applyFilters()
}

const clearTypeFilter = () => {
  filterForm.type = 'all'
  applyFilters()
}

const clearSearchFilter = () => {
  filterForm.search = ''
  applyFilters()
}

const handleEditTransaction = (transaction: Transaction) => {
  isModalOpen.value = false
  editingTransaction.value = transaction
  isEditModalOpen.value = true
}

const handleDeleteTransaction = async (transaction: Transaction) => {
  if (!confirm('Are you sure you want to delete this transaction?')) {
    return
  }
  
  isDeleting.value = true
  
  try {
    await router.delete(`/transactions/${transaction.id}`, {
      onSuccess: () => {
        isModalOpen.value = false
        selectedTransaction.value = null
      },
      onFinish: () => {
        isDeleting.value = false
      }
    })
  } catch (error) {
    isDeleting.value = false
    console.error('Error deleting transaction:', error)
  }
}

const handleNavigateToAccount = (accountId: number) => {
  isModalOpen.value = false
  router.visit(`/accounts/${accountId}`)
}

const handleTransactionEditSuccess = () => {
  isEditModalOpen.value = false
  editingTransaction.value = null
  router.reload()
}

// Apply default filters on mount if no filters are currently applied
onMounted(() => {
  // Check if no date filters are currently applied from the backend
  if (!props.filters.date_from && !props.filters.date_to) {
    // Apply default last week filter
    applyFilters()
  }
})
</script>

<style scoped>
.scrollbar-hide::-webkit-scrollbar {
  display: none;
}

.scrollbar-hide {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
</style>